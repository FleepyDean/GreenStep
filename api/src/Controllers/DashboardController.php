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
        $host = 'localhost';
        $user = 'root';
        $pass = 'admin123';
        $dbname = 'greenstep_db'; // Matches your schema exactly

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
            // ✅ FIXED: Table name changed to ActivityLog, column changed to carbon_footprint, date column changed to logged_on
            $todayStmt = $db->prepare("
                SELECT SUM(carbon_footprint) as total FROM ActivityLog 
                WHERE user_id = :userId AND logged_on = CURDATE()
            ");
            $todayStmt->execute(['userId' => $userId]);
            $todayResult = $todayStmt->fetch();

            // 2. Calculate Last 7 Days Total Footprint
            // ✅ FIXED: Using logged_on date tracking
            $weeklyStmt = $db->prepare("
                SELECT SUM(carbon_footprint) as total FROM ActivityLog 
                WHERE user_id = :userId AND logged_on >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
            ");
            $weeklyStmt->execute(['userId' => $userId]);
            $weeklyResult = $weeklyStmt->fetch();

            // 3. Compile Data points for the Line Chart (Monday - Sunday)
            // ✅ FIXED: Grouping by day using logged_on
            $trendStmt = $db->prepare("
                SELECT DAYOFWEEK(logged_on) as day_num, SUM(carbon_footprint) as total 
                FROM ActivityLog 
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

            // 4. Group totals by category for the Doughnut Chart
            // ✅ FIXED: Added INNER JOIN on ActivityType to safely extract the category ENUM
            $breakdownStmt = $db->prepare("
                SELECT t.category, SUM(a.carbon_footprint) as total 
                FROM ActivityLog a
                INNER JOIN ActivityType t ON a.activity_type_id = t.id
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

            // 5. Dynamic Badge Count Fetch
            // ✅ BONUS: Dynamically checks your UserBadge table count
            $badgeStmt = $db->prepare("SELECT COUNT(*) as total FROM UserBadge WHERE user_id = :userId");
            $badgeStmt->execute(['userId' => $userId]);
            $badgeCount = $badgeStmt->fetch();

            // Assemble matching payload structure
            $metrics = [
                "todayFootprint" => (float)($todayResult['total'] ?? 0),
                "weeklyTotal" => (float)($weeklyResult['total'] ?? 0),
                "dailyStreak" => 5,  // Temporary mockup placeholder
                "badgesCount" => (int)($badgeCount['total'] ?? 0),
                "weeklyTrendArray" => $weeklyTrendArray,
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