<?php
declare(strict_types=1);

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use PDO;

class BadgeController 
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * POST /api/admin/badges
     * Allows admins to save custom badges into the system
     */
    public function createCustomBadge(Request $request, Response $response): Response
    {
        $user = $request->getAttribute('user');
        if (($user['role'] ?? '') !== 'admin') {
            $response->getBody()->write(json_encode(['success' => false, 'message' => 'Admin access required']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(403);
        }

        $data = json_decode((string)$request->getBody(), true);
        
        $name = $data['name'] ?? '';
        $description = $data['description'] ?? '';
        $icon = $data['icon'] ?? '';
        $categoryRule = $data['category_rule'] ?? '';
        $thresholdValue = $data['threshold_value'] ?? 0;
        $activityTypeIds = $data['activity_type_ids'] ?? [];

        // Encode activity type IDs as comma-separated string
        if (is_array($activityTypeIds) && !empty($activityTypeIds)) {
            $activityTypeIds = implode(',', array_filter(array_map('intval', $activityTypeIds)));
        } else {
            $activityTypeIds = null;
        }

        if (empty($name) || empty($description) || empty($icon) || empty($categoryRule) || $thresholdValue <= 0) {
            $response->getBody()->write(json_encode(['success' => false, 'message' => 'All badge requirement fields are mandatory']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        try {
            $stmt = $this->db->prepare("
                INSERT INTO Badge (name, description, icon, category_rule, activity_type_ids, threshold_value) 
                VALUES (:name, :description, :icon, :category_rule, :activity_type_ids, :threshold_value)
            ");
            
            $stmt->execute([
                'name' => $name,
                'description' => $description,
                'icon' => $icon,
                'category_rule' => $categoryRule,
                'activity_type_ids' => $activityTypeIds,
                'threshold_value' => $thresholdValue
            ]);

            $response->getBody()->write(json_encode(['success' => true, 'message' => 'Custom badge added successfully']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(201);
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(['success' => false, 'error' => $e->getMessage()]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }

    /**
     * GET /api/admin/badges
     * Get all badges for admin management
     */
    public function getAllBadges(Request $request, Response $response): Response
    {
        $user = $request->getAttribute('user');
        if (($user['role'] ?? '') !== 'admin') {
            $response->getBody()->write(json_encode(['success' => false, 'message' => 'Admin access required']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(403);
        }

        try {
            $stmt = $this->db->query("SELECT * FROM Badge ORDER BY id ASC");
            $badges = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $response->getBody()->write(json_encode(['success' => true, 'badges' => $badges]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(['success' => false, 'error' => $e->getMessage()]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }

    /**
     * DELETE /api/admin/badges/{id}
     * Delete a badge (admin only)
     */
    public function deleteBadge(Request $request, Response $response, array $args): Response
    {
        $user = $request->getAttribute('user');
        if (($user['role'] ?? '') !== 'admin') {
            $response->getBody()->write(json_encode(['success' => false, 'message' => 'Admin access required']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(403);
        }

        $id = (int) ($args['id'] ?? 0);
        if (!$id) {
            $response->getBody()->write(json_encode(['success' => false, 'message' => 'Invalid badge ID']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        try {
            $stmt = $this->db->prepare("DELETE FROM Badge WHERE id = :id");
            $stmt->execute([':id' => $id]);
            if ($stmt->rowCount() === 0) {
                $response->getBody()->write(json_encode(['success' => false, 'message' => 'Badge not found']));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
            }
            $response->getBody()->write(json_encode(['success' => true, 'message' => 'Badge deleted']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(['success' => false, 'error' => $e->getMessage()]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }

    /**
     * GET /api/user/badges
     * Dynamically renders standard streak badges alongside admin custom category metric badges
     */
    public function getUserBadges(Request $request, Response $response): Response 
    {
        date_default_timezone_set('Asia/Kuala_Lumpur');

        $user = $request->getAttribute('user');
        $userId = $user['id'] ?? 1; 

        try {
            // 1. Calculate user streaks
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
                $newestDate = new \DateTime($dates[0]);

                if ($newestDate->format('Y-m-d') === $today->format('Y-m-d') || $newestDate->format('Y-m-d') === $yesterday->format('Y-m-d')) {
                    $dailyStreak = 1;
                    for ($i = 0; $i < count($dates) - 1; $i++) {
                        $current = new \DateTime($dates[$i]);
                        $next = new \DateTime($dates[$i + 1]);
                        $diff = $current->diff($next)->days;

                        if ($diff === 1) {
                            $dailyStreak++;
                        } elseif ($diff > 1) {
                            break;
                        }
                    }
                }
            }

            // 2. Aggregate user summary stats grouped by activity category
            $statsStmt = $this->db->prepare("
                SELECT act.category, SUM(al.amount) as total_amount
                FROM ActivityLog al
                JOIN ActivityType act ON al.activity_type_id = act.id
                WHERE al.user_id = :userId
                GROUP BY act.category
            ");
            $statsStmt->execute(['userId' => $userId]);
            $userCategoryTotals = $statsStmt->fetchAll(PDO::FETCH_KEY_PAIR) ?: [];

            // 2b. Aggregate per activity_type_id totals for specific-type badge checks
            $typeStmt = $this->db->prepare("
                SELECT al.activity_type_id, SUM(al.amount) as total_amount
                FROM ActivityLog al
                WHERE al.user_id = :userId
                GROUP BY al.activity_type_id
            ");
            $typeStmt->execute(['userId' => $userId]);
            $userTypeTotals = $typeStmt->fetchAll(PDO::FETCH_KEY_PAIR) ?: [];

            // 3. Select all system badges and evaluate conditional status
            $allBadgesStmt = $this->db->query("SELECT * FROM Badge");
            $allBadges = $allBadgesStmt->fetchAll(PDO::FETCH_ASSOC);

            $finalBadges = [];
            foreach ($allBadges as $badge) {
                $unlocked = 0;

                // Evaluate streak hardcoded default badges
                if ($badge['id'] == 6 && $dailyStreak >= 1) $unlocked = 1;
                elseif ($badge['id'] == 7 && $dailyStreak >= 3) $unlocked = 1;
                elseif ($badge['id'] == 8 && $dailyStreak >= 5) $unlocked = 1;
                elseif ($badge['id'] == 9 && $dailyStreak >= 10) $unlocked = 1;
                
                // Evaluate Dynamic Admin Created Category Rule Badge
                if (!empty($badge['category_rule'])) {
                    // If specific activity types are set, sum only those
                    if (!empty($badge['activity_type_ids'])) {
                        $typeIds = array_filter(array_map('intval', explode(',', (string) $badge['activity_type_ids'])));
                        $totalForTypes = 0;
                        foreach ($typeIds as $tid) {
                            $totalForTypes += (float) ($userTypeTotals[$tid] ?? 0);
                        }
                        if ($totalForTypes >= $badge['threshold_value']) {
                            $unlocked = 1;
                        }
                    } elseif (isset($userCategoryTotals[$badge['category_rule']])) {
                        if ($userCategoryTotals[$badge['category_rule']] >= $badge['threshold_value']) {
                            $unlocked = 1;
                        }
                    }
                }

                // Append status field matching the requested app layout specification
                $badge['unlocked'] = $unlocked;
                $finalBadges[] = $badge;
            }

            $response->getBody()->write(json_encode($finalBadges));
            return $response->withHeader('Content-Type', 'application/json');

        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(["error" => $e->getMessage()]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }
}