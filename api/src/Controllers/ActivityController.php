<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Models\ActivityLog;
use App\Models\ActivityType;
use App\Services\CarbonCalculator;
use App\Services\CloudinaryUploader;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use PDO;

class ActivityController
{
    private ActivityLog $activityLogModel;
    private ActivityType $activityTypeModel;
    private PDO $db;

    // Inject PDO centrally via the Constructor 
    public function __construct(PDO $db)
    {
        $this->db = $db;
        $this->activityLogModel = new ActivityLog($db);
        $this->activityTypeModel = new ActivityType($db);
    }

    /**
     * POST /api/activities
     */
    public function create(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();
        
        if (empty($data['activity_type_id']) || empty($data['amount'])) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Missing required fields: activity_type_id and amount are required'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        $activityTypeId = (int) $data['activity_type_id'];
        $amount = (float) $data['amount'];
        $date = $data['date'] ?? date('Y-m-d');

        if ($amount <= 0) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Amount must be greater than 0'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        $activityType = $this->activityTypeModel->getById($activityTypeId);
        if (!$activityType) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Activity type not found'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
        }

        $emissionFactor = (float) $activityType['kg_co2_per_unit'];
        $carbonFootprint = CarbonCalculator::calculate($amount, $emissionFactor);

        $user = $request->getAttribute('user');
        $userId = $user['id'] ?? null;

        // Handle optional photo upload to Cloudinary
        $photoUrl = null;
        $uploadedFiles = $request->getUploadedFiles();
        if (!empty($uploadedFiles['photo'])) {
            error_log('Photo upload detected for user ' . $userId);
            $photo = $uploadedFiles['photo'];
            error_log('Photo upload error code: ' . $photo->getError());
            
            if ($photo->getError() === UPLOAD_ERR_OK) {
                $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                $mimeType = $photo->getClientMediaType();
                error_log('Photo MIME type: ' . $mimeType);
                
                if (in_array($mimeType, $allowed)) {
                    // Save to temporary file first
                    $tmpDir = sys_get_temp_dir();
                    $tmpFile = $tmpDir . '/activity_' . uniqid() . '.tmp';
                    error_log('Saving photo to temp file: ' . $tmpFile);
                    $photo->moveTo($tmpFile);
                    
                    // Upload to Cloudinary
                    error_log('Uploading to Cloudinary...');
                    $cloudinary = new CloudinaryUploader();
                    $photoUrl = $cloudinary->upload($tmpFile, 'greenstep/activities');
                    
                    // Clean up temp file
                    if (file_exists($tmpFile)) {
                        unlink($tmpFile);
                    }
                    
                    if ($photoUrl) {
                        error_log('Photo uploaded successfully: ' . $photoUrl);
                    } else {
                        error_log('Failed to upload photo to Cloudinary for user ' . $userId);
                    }
                } else {
                    error_log('Invalid photo MIME type: ' . $mimeType);
                }
            } else {
                $errorCode = $photo->getError();
                $errorMessages = [
                    UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize (check .user.ini)',
                    UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE',
                    UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
                    UPLOAD_ERR_NO_FILE => 'No file was uploaded',
                    UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
                    UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
                    UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the upload',
                ];
                $errorMsg = $errorMessages[$errorCode] ?? 'Unknown error';
                error_log("Photo upload error code $errorCode: $errorMsg");
            }
        } else {
            error_log('No photo in uploaded files');
        }

        // Create activity log
        $logId = $this->activityLogModel->create(
            $userId,
            $activityTypeId,
            $amount,
            $carbonFootprint,
            $date,
            $photoUrl
        );

        if (!$logId) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Failed to log activity'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }

        // =============================================================
        // 🎯 Dynamic Badge Unlock Engine
        // =============================================================
        try {

            // User totals by category
            $statsStmt = $this->db->prepare("
                SELECT act.category, SUM(al.amount) as total_amount
                FROM ActivityLog al
                JOIN ActivityType act ON al.activity_type_id = act.id
                WHERE al.user_id = :userId
                GROUP BY act.category
            ");
            $statsStmt->execute(['userId' => $userId]);
            $userCategoryTotals = $statsStmt->fetchAll(PDO::FETCH_KEY_PAIR) ?: [];

            // User totals by activity type
            $typeStmt = $this->db->prepare("
                SELECT activity_type_id, SUM(amount) as total_amount
                FROM ActivityLog
                WHERE user_id = :userId
                GROUP BY activity_type_id
            ");
            $typeStmt->execute(['userId' => $userId]);
            $userTypeTotals = $typeStmt->fetchAll(PDO::FETCH_KEY_PAIR) ?: [];

            // All badges
            $badges = $this->db->query("SELECT * FROM Badge")->fetchAll(PDO::FETCH_ASSOC);

            foreach ($badges as $badge) {

                $unlock = false;

                // Handle streak badges
                $streakStmt = $this->db->prepare("
                    SELECT DISTINCT DATE(logged_on) as logged_date
                    FROM ActivityLog
                    WHERE user_id = :userId
                    ORDER BY logged_date DESC
                ");
                $streakStmt->execute(['userId' => $userId]);
                $dates = $streakStmt->fetchAll(PDO::FETCH_COLUMN);

                $dailyStreak = 0;

                if (!empty($dates)) {
                    $today = new \DateTime();
                    $yesterday = new \DateTime('yesterday');
                    $latest = new \DateTime($dates[0]);

                    if (
                        $latest->format('Y-m-d') === $today->format('Y-m-d') ||
                        $latest->format('Y-m-d') === $yesterday->format('Y-m-d')
                    ) {
                        $dailyStreak = 1;

                        for ($i = 0; $i < count($dates) - 1; $i++) {
                            $current = new \DateTime($dates[$i]);
                            $next = new \DateTime($dates[$i + 1]);

                            if ($current->diff($next)->days == 1) {
                                $dailyStreak++;
                            } else {
                                break;
                            }
                        }
                    }
                }

                if ($badge['id'] == 6 && $dailyStreak >= 1) $unlock = true;
                elseif ($badge['id'] == 7 && $dailyStreak >= 3) $unlock = true;
                elseif ($badge['id'] == 8 && $dailyStreak >= 5) $unlock = true;
                elseif ($badge['id'] == 9 && $dailyStreak >= 10) $unlock = true;

                if (!empty($badge['activity_type_ids'])) {

                    $typeIds = array_filter(array_map('intval', explode(',', $badge['activity_type_ids'])));

                    $total = 0;

                    foreach ($typeIds as $id) {
                        $total += (float)($userTypeTotals[$id] ?? 0);
                    }

                    if ($total >= $badge['threshold_value']) {
                        $unlock = true;
                    }

                } elseif (!empty($badge['category_rule'])) {

                    $categoryTotal = (float)($userCategoryTotals[$badge['category_rule']] ?? 0);

                    if ($categoryTotal >= $badge['threshold_value']) {
                        $unlock = true;
                    }
                }

                if ($unlock) {

                    $insert = $this->db->prepare("
                        INSERT IGNORE INTO UserBadge
                        (user_id,badge_id,earned_at)
                        VALUES
                        (:user,:badge,NOW())
                    ");

                    $insert->execute([
                        'user' => $userId,
                        'badge' => $badge['id']
                    ]);
                }
            }

        } catch (\Exception $e) {
            error_log($e->getMessage());
        }
        // =============================================================

        $response->getBody()->write(json_encode([
            'success' => true,
            'message' => 'Activity logged successfully',
            'activity' => [
                'id' => $logId,
                'activity_type_id' => $activityTypeId,
                'category' => $activityType['category'],
                'activity_name' => $activityType['name'],
                'unit' => $activityType['unit'],
                'amount' => $amount,
                'carbon_footprint' => $carbonFootprint,
                'emission_factor' => $emissionFactor,
                'logged_on' => $date,
                'photo_url' => $photoUrl
            ]
        ]));

        return $response->withHeader('Content-Type', 'application/json')->withStatus(201);
    }

    /**
     * GET /api/activities
     */
    public function index(Request $request, Response $response): Response
    {
        $queryParams = $request->getQueryParams();
        $startDate = $queryParams['start_date'] ?? null;
        $endDate = $queryParams['end_date'] ?? null;
        $category = $queryParams['category'] ?? null;

        $user = $request->getAttribute('user');
        $userId = $user['id'] ?? null;

        $activities = $this->activityLogModel->getByUser($userId, $startDate, $endDate, $category);

        $response->getBody()->write(json_encode([
            'success' => true,
            'count' => count($activities),
            'activities' => $activities
        ]));

        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
    }

    /**
     * GET /api/activities/today
     */
    public function today(Request $request, Response $response): Response
    {
        $user = $request->getAttribute('user');
        $userId = $user['id'] ?? null;

        $activities = $this->activityLogModel->getToday($userId);
        
        $total = 0;
        foreach ($activities as $activity) {
            $total += $activity['carbon_footprint'] ?? 0;
        }

        $response->getBody()->write(json_encode([
            'success' => true,
            'date' => date('Y-m-d'),
            'count' => count($activities),
            'total_footprint' => round($total, 4),
            'activities' => $activities
        ]));

        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
    }

    /**
     * GET /api/activities/stats
     */
    public function stats(Request $request, Response $response): Response
    {
        $queryParams = $request->getQueryParams();
        $startDate = $queryParams['start_date'] ?? null;
        $endDate = $queryParams['end_date'] ?? null;

        $user = $request->getAttribute('user');
        $userId = $user['id'] ?? null;

        $stats = $this->activityLogModel->getStats($userId, $startDate, $endDate);

        $response->getBody()->write(json_encode([
            'success' => true,
            'stats' => $stats
        ]));

        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
    }

    /**
     * DELETE /api/activities/{id}
     */
    public function delete(Request $request, Response $response, array $args): Response
    {
        $id = (int) ($args['id'] ?? 0);

        if ($id <= 0) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Invalid activity ID'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        $user = $request->getAttribute('user');
        $userId = $user['id'] ?? null;

        $activity = $this->activityLogModel->getById($id);
        if (!$activity) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Activity not found'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
        }

        if ($activity['user_id'] != $userId) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Unauthorized to delete this activity'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(403);
        }

        $deleted = $this->activityLogModel->delete($id, $userId);

        if ($deleted) {
            $response->getBody()->write(json_encode([
                'success' => true,
                'message' => 'Activity deleted successfully'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        } else {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Failed to delete activity'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }
}