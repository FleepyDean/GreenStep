<?php
declare(strict_types=1);

namespace App\Models;

use App\Config\Database;
use PDO;

class Admin
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function getDashboardStats(): array
    {
        // Total Users
        $totalUsers = $this->db
            ->query("SELECT COUNT(*) FROM User")
            ->fetchColumn();

        // Total Activities
        $totalActivities = $this->db
            ->query("SELECT COUNT(*) FROM ActivityLog")
            ->fetchColumn();

        // Total Tips
        $totalTips = $this->db
            ->query("SELECT COUNT(*) FROM Tip")
            ->fetchColumn();

        // Average Carbon Footprint
        $averageFootprint = $this->db
            ->query("
                SELECT ROUND(AVG(carbon_footprint),2)
                FROM ActivityLog
            ")
            ->fetchColumn();

        // Latest Users
        $latestUsers = $this->db
            ->query("
                SELECT
                    id,
                    name,
                    email,
                    role,
                    joined_at
                FROM User
                ORDER BY joined_at DESC
                LIMIT 5
            ")
            ->fetchAll(PDO::FETCH_ASSOC);

        // Latest Activities
        $latestActivities = $this->db
            ->query("
                SELECT
                    u.name,
                    at.name AS activity_name,
                    al.amount,
                    al.carbon_footprint,
                    al.logged_on
                FROM ActivityLog al
                JOIN User u
                    ON al.user_id=u.id
                JOIN ActivityType at
                    ON at.id=al.activity_type_id
                ORDER BY al.logged_on DESC
                LIMIT 10
            ")
            ->fetchAll(PDO::FETCH_ASSOC);

        // Category Breakdown
        $categoryBreakdown = $this->db
            ->query("
                SELECT
                    at.category,
                    ROUND(SUM(al.carbon_footprint),2) AS total
                FROM ActivityLog al
                JOIN ActivityType at
                    ON at.id=al.activity_type_id
                GROUP BY at.category
            ")
            ->fetchAll(PDO::FETCH_ASSOC);

        return [

            "total_users"=>$totalUsers,

            "total_activities"=>$totalActivities,

            "total_tips"=>$totalTips,

            "average_footprint"=>$averageFootprint,

            "latest_users"=>$latestUsers,

            "latest_activities"=>$latestActivities,

            "category_breakdown"=>$categoryBreakdown

        ];
    }

    
}