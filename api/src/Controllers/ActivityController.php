<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Models\ActivityLog;
use App\Models\ActivityType;
use App\Services\CarbonCalculator;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use PDO;

class ActivityController
{
    private ActivityLog $activityLogModel;
    private ActivityType $activityTypeModel;

    public function __construct()
    {
        $this->activityLogModel = new ActivityLog();
        $this->activityTypeModel = new ActivityType();
    }

    private function getDB()
    {
        $host = '127.0.0.1';
        $user = 'root';
        $pass = 'admin123';
        $dbname = 'greenstep_db';
        
        $pdo = new PDO("mysql:host={$host};dbname={$dbname};charset=utf8mb4", $user, $pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
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

        // Handle optional photo upload
        $photoUrl = null;
        $uploadedFiles = $request->getUploadedFiles();
        if (!empty($uploadedFiles['photo'])) {
            $photo = $uploadedFiles['photo'];
            if ($photo->getError() === UPLOAD_ERR_OK) {
                $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                $mimeType = $photo->getClientMediaType();
                if (in_array($mimeType, $allowed)) {
                    $uploadDir = __DIR__ . '/../../public/uploads/activities/';
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0755, true);
                    }
                    $ext = pathinfo($photo->getClientFilename(), PATHINFO_EXTENSION);
                    $filename = 'activity_' . $userId . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                    $photo->moveTo($uploadDir . $filename);
                    $photoUrl = '/uploads/activities/' . $filename;
                }
            }
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
        // 🎯 NEW DYNAMIC ACTION/CHALLENGE BADGES ENGINE
        // =============================================================
        try {
            $db = $this->getDB();
            $categoryClean = strtolower(trim((string)$activityType['category']));

            // Map incoming category types directly to their explicit category target badge ids
            $categoryToBadgeMap = [
                'transport' => 1, // Green Commuter
                'diet'      => 2, // Plant-Based Hero
                'energy'    => 3, // Energy Saver
                'recycling' => 4, // Recycling Champion
                'general'   => 5  // Eco Innovator
            ];

            if (array_key_exists($categoryClean, $categoryToBadgeMap)) {
                $targetBadgeId = $categoryToBadgeMap[$categoryClean];

                // INSERT IGNORE safely ignores if they have already unlocked it before
                $unlockQuery = "INSERT IGNORE INTO userbadge (user_id, badge_id, earned_at) VALUES (:user_id, :badge_id, NOW())";
                $unlockStmt = $db->prepare($unlockQuery);
                $unlockStmt->execute([
                    'user_id'  => $userId,
                    'badge_id' => $targetBadgeId
                ]);
            }
        } catch (\Exception $e) {
            error_log("Challenge badge unlock handling error: " . $e->getMessage());
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