<?php
declare(strict_types=1);

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use PDO; // Using PHP's built-in database connection manager

class DashboardController
{
    // If your app uses dependency injection for PDO, inject it here. 
    // Otherwise, this fallback establishes a connection using native standard variables.
    private function getDB()
    {
        $host = 'localhost';
        $user = 'root';
        $pass = '';
        $dbname = 'greenstep_db'; // Ensure this matches your Laragon database name

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
                SELECT SUM(co2_amount) as total FROM carbon_logs 
                WHERE user_id = :userId AND DATE(created_at) = CURDATE()
            ");
            $todayStmt->execute(['userId' => $userId]);
            $todayResult = $todayStmt->fetch();

            // 2. Calculate Last 7 Days Total Footprint
            $weeklyStmt = $db->prepare("
                SELECT SUM(co2_amount) as total FROM carbon_logs 
                WHERE user_id = :userId AND created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
            ");
            $weeklyStmt->execute(['userId' => $userId]);
            $weeklyResult = $weeklyStmt->fetch();

            // 3. Compile Data points for the Line Chart (Monday - Sunday)
            $trendStmt = $db->prepare("
                SELECT DAYOFWEEK(created_at) as day_num, SUM(co2_amount) as total 
                FROM carbon_logs 
                WHERE user_id = :userId AND YEARWEEK(created_at, 1) = YEARWEEK(CURDATE(), 1)
                GROUP BY DAYOFWEEK(created_at)
            ");
            $trendStmt->execute(['userId' => $userId]);
            $trendRows = $trendStmt->fetchAll();

            // Format standard 0-6 array keys matching Mon-Sun for Chart.js
            $weeklyTrendArray = [0, 0, 0, 0, 0, 0, 0];
            foreach ($trendRows as $row) {
                $dayNum = (int)$row['day_num'];
                // Normalize: MySQL DAYOFWEEK (1=Sun, 2=Mon... 7=Sat) to index positions (Mon=0... Sun=6)
                $targetIndex = ($dayNum === 1) ? 6 : $dayNum - 2;
                $weeklyTrendArray[$targetIndex] = (float)$row['total'];
            }

            // 4. Group totals by category for the Doughnut Chart
            $breakdownStmt = $db->prepare("
                SELECT category, SUM(co2_amount) as total 
                FROM carbon_logs 
                WHERE user_id = :userId 
                GROUP BY category
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
                $catName = ucfirst(strtolower($row['category']));
                if (array_key_exists($catName, $categoryBreakdown)) {
                    $categoryBreakdown[$catName] = (float)$row['total'];
                }
            }

            // Assemble payload structure matching Slim + your Vue charts expectations
            $metrics = [
                "todayFootprint" => (float)($todayResult['total'] ?? 0),
                "weeklyTotal" => (float)($weeklyResult['total'] ?? 0),
                "dailyStreak" => 12, // Replace with dynamic streak calculation later
                "badgesCount" => 3,  // Replace with user badges table count later
                "weeklyTrendArray" => $weeklyTrendArray,
                "categoryBreakdown" => $categoryBreakdown
            ];

            // In older Slim versions use: return $response->withJson(["success" => true, "data" => $metrics]);
            // If using standard modern Slim 4 PSR-7 formatting instead:
            $response->getBody()->write(json_encode(["success" => true, "data" => $metrics]));
            return $response->withHeader('Content-Type', 'application/json');

        } catch (\PDOException $e) {
            $response->getBody()->write(json_encode(["success" => false, "message" => $e->getMessage()]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }
}