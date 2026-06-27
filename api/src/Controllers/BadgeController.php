<?php
declare(strict_types=1);

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use PDO;

class BadgeController 
{
    // The centralized DB connection is injected and held here
    private PDO $db;

    // Type-hinting PDO allows your DI container to automatically inject the connection
    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function getUserBadges(Request $request, Response $response): Response 
    {
        // Automatically fetch the user id attached by your JwtMiddleware token check.
        $user = $request->getAttribute('user');
        $userId = $user['id'] ?? 1; 

        // Define today's date uniformly using PHP's current runtime clock
        $todayStr = date('Y-m-d');

        try {
            // ==================================================================
            // 🎯 CONSECUTIVE DAILY STREAK CALCULATION LOGIC (Dashboard Sync)
            // ==================================================================
            // FIXED: Changed ORDER BY clause to target logged_date to fix the DISTINCT strict-mode error 3065
            $streakStmt = $this->db->prepare("
                SELECT DISTINCT DATE(logged_on) as logged_date
                FROM activitylog 
                WHERE user_id = :userId 
                ORDER BY logged_date DESC
            ");
            $streakStmt->execute(['userId' => $userId]);
            $loggedDates = $streakStmt->fetchAll(PDO::FETCH_COLUMN);

            $dailyStreak = 0;
            if (!empty($loggedDates)) {
                $yesterdayStr = date('Y-m-d', strtotime('-1 day'));
                
                // The streak remains alive only if they logged something today OR yesterday
                if ($loggedDates[0] === $todayStr || $loggedDates[0] === $yesterdayStr) {
                    $dailyStreak = 1; // Base case: starts at 1 day
                    
                    // Walk backwards through the array to check for perfect daily continuations
                    for ($i = 0; $i < count($loggedDates) - 1; $i++) {
                        $currentDate = strtotime($loggedDates[$i]);
                        $nextDate = strtotime($loggedDates[$i + 1]);
                        
                        // Compute exact day variations
                        $dayDifference = ($currentDate - $nextDate) / (60 * 60 * 24);
                        
                        if ($dayDifference == 1) {
                            $dailyStreak++;
                        } else {
                            // Chain broken, terminate analysis loop early
                            break;
                        }
                    }
                }
            }

            // ==================================================================
            // 🚀 DYNAMIC BADGE FETCHING WITH INJECTED STREAK VALUES
            // ==================================================================
            $query = "
                SELECT 
                    b.id, 
                    b.name, 
                    JSON_UNQUOTE(JSON_EXTRACT(b.criteria_json, '$.description')) AS criteria_description,
                    CASE 
                        -- Streak Milestone Badges calculated via your dashboard loop metrics
                        WHEN b.id = 6 AND :streak_1 >= 1 THEN 1
                        WHEN b.id = 7 AND :streak_3 >= 3 THEN 1
                        WHEN b.id = 8 AND :streak_5 >= 5 THEN 1
                        WHEN b.id = 9 AND :streak_10 >= 10 THEN 1

                        -- All other challenge badges fall back onto userbadge bridge links
                        WHEN ub.earned_at IS NOT NULL THEN 1 
                        
                        ELSE 0 
                    END AS unlocked
                FROM badge b
                LEFT JOIN userbadge ub ON b.id = ub.badge_id AND ub.user_id = :user_id
            ";

            $stmt = $this->db->prepare($query);
            $stmt->execute([
                'streak_1'  => $dailyStreak,
                'streak_3'  => $dailyStreak,
                'streak_5'  => $dailyStreak,
                'streak_10' => $dailyStreak,
                'user_id'   => $userId
            ]);
            $badges = $stmt->fetchAll();

            // Return clean payload to Vue Profile Layout
            $response->getBody()->write(json_encode($badges));
            return $response->withHeader('Content-Type', 'application/json');

        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(["error" => $e->getMessage()]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }
}