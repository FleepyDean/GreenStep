<?php
namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use PDO;

class BadgeController {

    public function getUserBadges(Request $request, Response $response): Response {
        // Automatically fetch the user id attached by your JwtMiddleware token check.
        $user = $request->getAttribute('user');
        $userId = $user['id'] ?? 1; 

        try {
            $host = '127.0.0.1';
            $db   = 'greenstep_db'; 
            $dbUser = 'root';
            $pass = ''; 
            $charset = 'utf8mb4';

            $dsn = "mysql:host=$host;dbname=$db;charset=$charset";
            $pdo = new PDO($dsn, $dbUser, $pass, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);

            // ==================================================================
            // 🔥 CONSECUTIVE DAILY STREAK CALCULATION LOGIC (Dashboard Sync)
            // ==================================================================
            $streakStmt = $pdo->prepare("
                SELECT DISTINCT logged_on 
                FROM activitylog 
                WHERE user_id = :userId 
                ORDER BY logged_on DESC
            ");
            $streakStmt->execute(['userId' => $userId]);
            $loggedDates = $streakStmt->fetchAll(PDO::FETCH_COLUMN);

            $dailyStreak = 0;
            if (!empty($loggedDates)) {
                $todayStr = date('Y-m-d');
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

            $stmt = $pdo->prepare($query);
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