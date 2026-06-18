<?php
declare(strict_types=1);

namespace App\Models;

use App\Config\Database;
use PDO;
use PDOException;

/**
 * ActivityLog Model
 * 
 * Handles database operations for user activity logs
 */
class ActivityLog
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    /**
     * Create a new activity log
     * 
     * @param int $userId User ID
     * @param int $activityTypeId Activity type ID
     * @param float $amount Amount of activity
     * @param float $carbonFootprint Calculated carbon footprint
     * @param string $date Date of activity (YYYY-MM-DD)
     * @return int|false ID of the created log or false on failure
     */
    public function create(int $userId, int $activityTypeId, float $amount, float $carbonFootprint, string $date): int|false
    {
        $sql = "INSERT INTO ActivityLog (user_id, activity_type_id, amount, carbon_footprint, logged_on) 
                VALUES (:user_id, :activity_type_id, :amount, :carbon_footprint, :logged_on)";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':user_id' => $userId,
                ':activity_type_id' => $activityTypeId,
                ':amount' => $amount,
                ':carbon_footprint' => $carbonFootprint,
                ':logged_on' => $date
            ]);
            
            return (int) $this->db->lastInsertId();
        } catch (PDOException $e) {
            error_log("ActivityLog::create error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get all activities for a user with optional date and category filtering
     * 
     * @param int $userId User ID
     * @param string|null $startDate Start date (YYYY-MM-DD)
     * @param string|null $endDate End date (YYYY-MM-DD)
     * @param string|null $category Category filter (Transport, Diet, Energy, Recycling)
     * @return array Array of activity logs
     */
    public function getByUser(int $userId, ?string $startDate = null, ?string $endDate = null, ?string $category = null): array
    {
        $sql = "SELECT 
                    al.id,
                    al.amount,
                    al.carbon_footprint,
                    al.logged_on,
                    at.id as activity_type_id,
                    at.category,
                    at.name as activity_name,
                    at.unit,
                    at.kg_co2_per_unit as emission_factor
                FROM ActivityLog al
                JOIN ActivityType at ON al.activity_type_id = at.id
                WHERE al.user_id = :user_id";
        
        $params = [':user_id' => $userId];
        
        if ($startDate) {
            $sql .= " AND al.logged_on >= :start_date";
            $params[':start_date'] = $startDate;
        }
        
        if ($endDate) {
            $sql .= " AND al.logged_on <= :end_date";
            $params[':end_date'] = $endDate;
        }

        if ($category) {
            $sql .= " AND at.category = :category";
            $params[':category'] = $category;
        }
        
        $sql .= " ORDER BY al.logged_on DESC, al.created_at DESC";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("ActivityLog::getByUser error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get activities for today
     * 
     * @param int $userId User ID
     * @return array Array of today's activity logs
     */
    public function getToday(int $userId): array
    {
        $today = date('Y-m-d');
        return $this->getByUser($userId, $today, $today);
    }

    /**
     * Get activity statistics for a date range
     * 
     * @param int $userId User ID
     * @param string|null $startDate Start date
     * @param string|null $endDate End date
     * @return array Statistics including total, count, category breakdown
     */
    public function getStats(int $userId, ?string $startDate = null, ?string $endDate = null): array
    {
        $activities = $this->getByUser($userId, $startDate, $endDate);
        
        $total = 0;
        $count = count($activities);
        $categories = [];
        
        foreach ($activities as $activity) {
            $carbon = $activity['carbon_footprint'] ?? 0;
            $total += $carbon;
            
            $category = $activity['category'] ?? 'Unknown';
            if (!isset($categories[$category])) {
                $categories[$category] = 0;
            }
            $categories[$category] += $carbon;
        }
        
        // Round category totals
        foreach ($categories as $key => $value) {
            $categories[$key] = round($value, 4);
        }
        
        return [
            'total_footprint' => round($total, 4),
            'activity_count' => $count,
            'category_breakdown' => $categories,
            'period_start' => $startDate,
            'period_end' => $endDate
        ];
    }

    /**
     * Delete an activity log
     * 
     * @param int $id Activity log ID
     * @param int $userId User ID (for verification)
     * @return bool True if deleted, false otherwise
     */
    public function delete(int $id, int $userId): bool
    {
        $sql = "DELETE FROM ActivityLog WHERE id = :id AND user_id = :user_id";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':id' => $id,
                ':user_id' => $userId
            ]);
            
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log("ActivityLog::delete error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get single activity by ID
     * 
     * @param int $id Activity log ID
     * @return array|null Activity data or null if not found
     */
    public function getById(int $id): ?array
    {
        $sql = "SELECT 
                    al.*,
                    at.category,
                    at.name as activity_name,
                    at.unit
                FROM ActivityLog al
                JOIN ActivityType at ON al.activity_type_id = at.id
                WHERE al.id = :id";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':id' => $id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ?: null;
        } catch (PDOException $e) {
            error_log("ActivityLog::getById error: " . $e->getMessage());
            return null;
        }
    }
}
