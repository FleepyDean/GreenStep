<?php
declare(strict_types=1);

namespace App\Models;

use App\Config\Database;
use PDO;
use PDOException;

/**
 * ActivityType Model
 * 
 * Handles database operations for activity types and emission factors
 */
class ActivityType
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    /**
     * Get all activity types
     * 
     * @return array Array of all activity types
     */
    public function getAll(): array
    {
        $sql = "SELECT * FROM ActivityType ORDER BY category, name";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("ActivityType::getAll error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get activity types grouped by category
     * 
     * @return array Activity types grouped by category
     */
    public function getGroupedByCategory(): array
    {
        $all = $this->getAll();
        $grouped = [];
        
        foreach ($all as $type) {
            $category = $type['category'];
            if (!isset($grouped[$category])) {
                $grouped[$category] = [];
            }
            $grouped[$category][] = $type;
        }
        
        return $grouped;
    }

    /**
     * Get activity type by ID
     * 
     * @param int $id Activity type ID
     * @return array|null Activity type data or null if not found
     */
    public function getById(int $id): ?array
    {
        $sql = "SELECT * FROM ActivityType WHERE id = :id";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':id' => $id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ?: null;
        } catch (PDOException $e) {
            error_log("ActivityType::getById error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get activity types by category
     * 
     * @param string $category Category name
     * @return array Array of activity types in the category
     */
    public function getByCategory(string $category): array
    {
        $sql = "SELECT * FROM ActivityType WHERE category = :category ORDER BY name";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':category' => $category]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("ActivityType::getByCategory error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get emission factor for an activity type
     * 
     * @param int $id Activity type ID
     * @return float Emission factor or 0 if not found
     */
    public function getEmissionFactor(int $id): float
    {
        $type = $this->getById($id);
        return $type ? (float) $type['kg_co2_per_unit'] : 0.0;
    }

    /**
     * Get available categories
     * 
     * @return array Array of distinct categories
     */
    public function getCategories(): array
    {
        $sql = "SELECT DISTINCT category FROM ActivityType ORDER BY category";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $results = $stmt->fetchAll(PDO::FETCH_COLUMN);
            return $results;
        } catch (PDOException $e) {
            error_log("ActivityType::getCategories error: " . $e->getMessage());
            return [];
        }
    }
}
