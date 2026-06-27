<?php

namespace Src\Controllers;

use PDO;

class BadgeController {
    private $db;

    public function __construct($dbConnection) {
        $this->db = $dbConnection;
    }

    public function getUserBadges($userId) {
        // 1. Set the correct headers to fix CORS and output JSON
        header("Access-Control-Allow-Origin: http://localhost:5173");
        header("Access-Control-Allow-Headers: Content-Type, Authorization, Accept");
        header("Access-Control-Allow-Methods: GET, OPTIONS");
        header("Content-Type: application/json");

        // Handle preflight requests
        if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
            exit(0);
        }

        try {
            // 2. Run the Left Join Query
            $query = "
                SELECT 
                    b.id, 
                    b.name, 
                    JSON_UNQUOTE(JSON_EXTRACT(b.criteria_json, '$.description')) AS criteria_description,
                    CASE WHEN ub.earned_at IS NOT NULL THEN 1 ELSE 0 END AS unlocked
                FROM Badge b
                LEFT JOIN UserBadge ub ON b.id = ub.badge_id AND ub.user_id = :user_id
            ";

            $stmt = $this->db->prepare($query);
            $stmt->execute(['user_id' => $userId]);
            $badges = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode($badges);
            exit;

        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(["error" => "Failed to fetch badges: " . $e->getMessage()]);
            exit;
        }
    }
}