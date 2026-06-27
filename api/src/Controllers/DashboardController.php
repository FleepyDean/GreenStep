<?php
declare(strict_types=1);

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use PDO; 

class DashboardController
{
    private function getDB()
    {
        $host = '127.0.0.1'; // Standardized local database loopback configuration
        $user = 'root';
        $pass = '';
        $dbname = 'greenstep_db'; 

        $pdo = new PDO("mysql:host={$host};dbname={$dbname};charset=utf8mb4", $user, $pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        return $pdo;
    }

    public function getMetrics(Request $request, Response $response, array $args): Response
    {
        $userId = (int)$args['userId'];

        try {
            $db = $this->getDB();

            // 1. Calculate Today's Footprint Summary
            $todayStmt = $db->prepare("
                SELECT SUM(carbon_footprint) as total FROM activitylog 
                WHERE user_id = :userId AND logged_on = CURDATE()
            ");
            $todayStmt->execute(['userId' => $userId]);
            $todayResult = $todayStmt->fetch();

            // 2. Calculate Last 7 Days Total Footprint
            $weeklyStmt = $db->prepare("
                SELECT SUM(carbon_footprint) as total FROM activitylog 
                WHERE user_id = :userId AND logged_on >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
            ");
            $weeklyStmt->execute(['userId' => $userId]);
            $weeklyResult = $weeklyStmt->fetch();

            // 3. Compile Data points for the Line Chart (Monday - Sunday)
            $trendStmt = $db->prepare("
                SELECT DAYOFWEEK(logged_on) as day_num, SUM(carbon_footprint) as total 
                FROM activitylog 
                WHERE user_id = :userId AND YEARWEEK(logged_on, 1) = YEARWEEK(CURDATE(), 1)
                GROUP BY DAYOFWEEK(logged_on)
            ");
            $trendStmt->execute(['userId' => $userId]);
            $trendRows = $trendStmt->fetchAll();

            $weeklyTrendArray = [0, 0, 0, 0, 0, 0, 0];
            foreach ($trendRows as $row) {
                $dayNum = (int)$row['day_num'];
                $targetIndex = ($dayNum === 1) ? 6 : $dayNum - 2;
                $weeklyTrendArray[$targetIndex] = (float)$row['total'];
            }

            // 💡 3b. NEW: Compile Data points for Monthly Trend Chart (Jan - Dec)
            $monthlyTrendStmt = $db->prepare("
                SELECT MONTH(logged_on) as month_num, SUM(carbon_footprint) as total 
                FROM activitylog 
                WHERE user_id = :userId AND YEAR(logged_on) = YEAR(CURDATE())
                GROUP BY MONTH(logged_on)
            ");
            $monthlyTrendStmt->execute(['userId' => $userId]);
            $monthlyRows = $monthlyTrendStmt->fetchAll();

            $monthlyTrendArray = [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0]; // 12 Months
            foreach ($monthlyRows as $row) {
                $monthNum = (int)$row['month_num'];
                $monthlyTrendArray[$monthNum - 1] = (float)$row['total']; // Months are 1-12, index is 0-11
            }

            // 4. Group totals by category for the Doughnut Chart
            $breakdownStmt = $db->prepare("
                SELECT t.category, SUM(a.carbon_footprint) as total 
                FROM activitylog a
                INNER JOIN activitytype t ON a.activity_type_id = t.id
                WHERE a.user_id = :userId 
                GROUP BY t.category
            ");
            $breakdownStmt->execute(['userId' => $userId]);
            $breakdownRows = $breakdownStmt->fetchAll();

            $categoryBreakdown = [
                "Transport" => 0,
                "Diet" => 0,
                "Energy" => 0,
                "Recycling" => 0,
                "General" => 0
            ];
            
            foreach ($breakdownRows as $row) {
                $dbCategory = ucfirst(strtolower(trim((string)$row['category']))); 
                if (array_key_exists($dbCategory, $categoryBreakdown)) {
                    $categoryBreakdown[$dbCategory] = (float)$row['total'];
                }
            }

            // ==================================================================
            // 🔥 CONSECUTIVE DAILY STREAK CALCULATION LOGIC
            // ==================================================================
            $streakStmt = $db->prepare("
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
                
                if ($loggedDates[0] === $todayStr || $loggedDates[0] === $yesterdayStr) {
                    $dailyStreak = 1; 
                    
                    for ($i = 0; $i < count($loggedDates) - 1; $i++) {
                        $currentDate = strtotime($loggedDates[$i]);
                        $nextDate = strtotime($loggedDates[$i + 1]);
                        
                        $dayDifference = ($currentDate - $nextDate) / (60 * 60 * 24);
                        
                        if ($dayDifference == 1) {
                            $dailyStreak++;
                        } else {
                            break;
                        }
                    }
                }
            }

            // ==================================================================
            // 🚀 ✨ NEW DYNAMIC SYNCHRONIZED BADGE COUNTER
            // ==================================================================
            $badgeQuery = "
                SELECT COUNT(*) as total_unlocked
                FROM badge b
                LEFT JOIN userbadge ub ON b.id = ub.badge_id AND ub.user_id = :user_id
                WHERE 
                    (b.id = 6 AND :streak_1 >= 1) OR
                    (b.id = 7 AND :streak_3 >= 3) OR
                    (b.id = 8 AND :streak_5 >= 5) OR
                    (b.id = 9 AND :streak_10 >= 10) OR
                    (b.id NOT IN (6,7,8,9) AND ub.earned_at IS NOT NULL)
            ";
            
            $badgeStmt = $db->prepare($badgeQuery);
            $badgeStmt->execute([
                'user_id'   => $userId,
                'streak_1'  => $dailyStreak,
                'streak_3'  => $dailyStreak,
                'streak_5'  => $dailyStreak,
                'streak_10' => $dailyStreak
            ]);
            $badgesUnlockedCount = (int)$badgeStmt->fetch()['total_unlocked'];

            // Assemble matching payload structure
            $metrics = [
                "todayFootprint" => (float)($todayResult['total'] ?? 0),
                "weeklyTotal" => (float)($weeklyResult['total'] ?? 0),
                "dailyStreak" => $dailyStreak, 
                "badgesCount" => $badgesUnlockedCount,
                "weeklyTrendArray" => $weeklyTrendArray,
                "monthlyTrendArray" => $monthlyTrendArray, // 💡 NEW: Appended structural parameter
                "categoryBreakdown" => $categoryBreakdown
            ];

            $response->getBody()->write(json_encode(["success" => true, "data" => $metrics]));
            return $response->withHeader('Content-Type', 'application/json');

        } catch (\PDOException $e) {
            $response->getBody()->write(json_encode(["success" => false, "message" => $e->getMessage()]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }
}