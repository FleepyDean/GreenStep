<?php
declare(strict_types=1);

namespace App\Models;

use App\Config\Database;
use PDO;
use PDOException;

/**
 * Challenge Model
 *
 * Handles database operations for eco challenges and challenge memberships
 */
class Challenge
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    /**
     * Get all challenges with member count and joined status for the requesting user
     *
     * @param int $userId Requesting user's ID
     * @return array Array of challenge records
     */
    public function getAllWithMemberCount(int $userId): array
    {
        $sql = "SELECT 
                    c.id,
                    c.name,
                    c.description,
                    c.target_co2_reduction,
                    c.target_category,
                    c.target_activity_type_id,
                    c.duration_days,
                    c.start_date,
                    c.end_date,
                    c.created_at,
                    COUNT(cm.id) as member_count,
                    EXISTS(
                        SELECT 1 FROM ChallengeMember cm2
                        WHERE cm2.challenge_id = c.id AND cm2.user_id = :user_id
                    ) as has_joined
                FROM Challenge c
                LEFT JOIN ChallengeMember cm ON c.id = cm.challenge_id
                GROUP BY c.id, c.name, c.description, c.target_co2_reduction,
                         c.target_category, c.target_activity_type_id,
                         c.duration_days, c.start_date, c.end_date, c.created_at
                ORDER BY c.start_date DESC";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':user_id' => $userId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Challenge::getAllWithMemberCount error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get a single challenge by ID
     *
     * @param int $id Challenge ID
     * @return array|null Challenge record or null
     */
    public function getById(int $id): ?array
    {
        $sql = "SELECT * FROM Challenge WHERE id = :id LIMIT 1";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':id' => $id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$result) return null;
            $result['target_activity_type_ids'] = $this->decodeActivityTypeIds($result['target_activity_type_id'] ?? null);
            return $result;
        } catch (PDOException $e) {
            error_log("Challenge::getById error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Create a new challenge
     *
     * @param array $data Challenge data (name, description, target_co2_reduction, duration_days, start_date, end_date)
     * @return int|false Challenge ID or false on failure
     */
    private function encodeActivityTypeIds(mixed $ids): ?string
    {
        if (empty($ids)) return null;
        if (is_array($ids)) {
            $filtered = array_filter(array_map('intval', $ids));
            return empty($filtered) ? null : implode(',', $filtered);
        }
        return (string) $ids;
    }

    private function decodeActivityTypeIds(?string $ids): array
    {
        if (empty($ids)) return [];
        return array_values(array_filter(array_map('intval', explode(',', $ids))));
    }

    public function create(array $data): int|false
    {
        $sql = "INSERT INTO Challenge (name, description, target_co2_reduction, target_category, target_activity_type_id, duration_days, start_date, end_date)
                VALUES (:name, :description, :target_co2_reduction, :target_category, :target_activity_type_id, :duration_days, :start_date, :end_date)";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':name' => $data['name'],
                ':description' => $data['description'],
                ':target_co2_reduction' => (float) $data['target_co2_reduction'],
                ':target_category' => $data['target_category'] ?? 'All',
                ':target_activity_type_id' => $this->encodeActivityTypeIds($data['target_activity_type_ids'] ?? $data['target_activity_type_id'] ?? null),
                ':duration_days' => (int) $data['duration_days'],
                ':start_date' => $data['start_date'],
                ':end_date' => $data['end_date']
            ]);
            return (int) $this->db->lastInsertId();
        } catch (PDOException $e) {
            error_log("Challenge::create error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Update an existing challenge
     *
     * @param int $id Challenge ID
     * @param array $data Challenge data (name, description, target_co2_reduction, duration_days, start_date, end_date)
     * @return bool True on success
     */
    public function update(int $id, array $data): bool
    {
        $sql = "UPDATE Challenge
                SET name = :name,
                    description = :description,
                    target_co2_reduction = :target_co2_reduction,
                    target_category = :target_category,
                    target_activity_type_id = :target_activity_type_id,
                    duration_days = :duration_days,
                    start_date = :start_date,
                    end_date = :end_date
                WHERE id = :id";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':id' => $id,
                ':name' => $data['name'],
                ':description' => $data['description'],
                ':target_co2_reduction' => (float) $data['target_co2_reduction'],
                ':target_category' => $data['target_category'] ?? 'All',
                ':target_activity_type_id' => $this->encodeActivityTypeIds($data['target_activity_type_ids'] ?? $data['target_activity_type_id'] ?? null),
                ':duration_days' => (int) $data['duration_days'],
                ':start_date' => $data['start_date'],
                ':end_date' => $data['end_date']
            ]);
            return $stmt->rowCount() >= 0;
        } catch (PDOException $e) {
            error_log("Challenge::update error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete a challenge and its memberships
     *
     * @param int $id Challenge ID
     * @return bool True on success
     */
    public function delete(int $id): bool
    {
        try {
            $this->db->beginTransaction();

            $stmt = $this->db->prepare("DELETE FROM ChallengeMember WHERE challenge_id = :challenge_id");
            $stmt->execute([':challenge_id' => $id]);

            $stmt = $this->db->prepare("DELETE FROM Challenge WHERE id = :id");
            $stmt->execute([':id' => $id]);
            $deleted = $stmt->rowCount() > 0;

            $this->db->commit();
            return $deleted;
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("Challenge::delete error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Join a challenge (insert into ChallengeMember)
     *
     * @param int $challengeId Challenge ID
     * @param int $userId User ID
     * @return bool True on success
     */
    public function join(int $challengeId, int $userId): bool
    {
        $sql = "INSERT INTO ChallengeMember (challenge_id, user_id)
                VALUES (:challenge_id, :user_id)";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':challenge_id' => $challengeId,
                ':user_id' => $userId
            ]);
            return true;
        } catch (PDOException $e) {
            error_log("Challenge::join error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Leave a challenge (delete from ChallengeMember)
     *
     * @param int $challengeId Challenge ID
     * @param int $userId User ID
     * @return bool True on success
     */
    public function leave(int $challengeId, int $userId): bool
    {
        $sql = "DELETE FROM ChallengeMember
                WHERE challenge_id = :challenge_id AND user_id = :user_id";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':challenge_id' => $challengeId,
                ':user_id' => $userId
            ]);
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log("Challenge::leave error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Check if a user has already joined a challenge
     *
     * @param int $challengeId Challenge ID
     * @param int $userId User ID
     * @return bool True if user is a member
     */
    public function isMember(int $challengeId, int $userId): bool
    {
        $sql = "SELECT 1 FROM ChallengeMember
                WHERE challenge_id = :challenge_id AND user_id = :user_id
                LIMIT 1";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':challenge_id' => $challengeId,
                ':user_id' => $userId
            ]);
            return (bool) $stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("Challenge::isMember error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get all members of a challenge with their user details
     *
     * @param int $challengeId Challenge ID
     * @return array List of member records with user_id, name, email, joined_at
     */
    public function getMembers(int $challengeId): array
    {
        $sql = "SELECT 
                    cm.user_id,
                    u.name,
                    u.email,
                    cm.joined_at
                FROM ChallengeMember cm
                JOIN User u ON cm.user_id = u.id
                WHERE cm.challenge_id = :challenge_id
                ORDER BY cm.joined_at ASC";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':challenge_id' => $challengeId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Challenge::getMembers error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get the total CO2 reduction by challenge members during the challenge timeframe
     *
     * Calculates SUM(ActivityLog.amount * ActivityType.kg_co2_per_unit) for all members
     * Optionally filtered by ActivityType.category or a specific ActivityType.id.
     *
     * @param int $challengeId Challenge ID
     * @param string $startDate Challenge start date (Y-m-d)
     * @param string $endDate Challenge end date (Y-m-d)
     * @param string|null $targetCategory Activity category filter (NULL or 'All' for all categories)
     * @param int|null $targetActivityTypeId Specific activity type filter (NULL for all types)
     * @return float Total CO2 reduction achieved by members during the challenge
     */
    public function getCommunityProgress(
        int $challengeId,
        string $startDate,
        string $endDate,
        ?string $targetCategory = null,
        array|null $targetActivityTypeId = null
    ): float {
        $category = $targetCategory ?? 'All';
        $activityTypeId = $targetActivityTypeId;

        $sql = "SELECT COALESCE(SUM(al.amount * at.kg_co2_per_unit), 0) as total_reduction
                FROM ActivityLog al
                INNER JOIN ActivityType at ON al.activity_type_id = at.id
                INNER JOIN ChallengeMember cm ON al.user_id = cm.user_id
                WHERE cm.challenge_id = :challenge_id
                  AND al.logged_on BETWEEN :start_date AND :end_date";
        $params = [
            ':challenge_id' => $challengeId,
            ':start_date' => $startDate,
            ':end_date' => $endDate
        ];

        if ($category !== 'All') {
            $sql .= " AND at.category = :target_category";
            $params[':target_category'] = $category;
        }

        if (!empty($activityTypeId)) {
            $ids = $activityTypeId;
            if (!empty($ids)) {
                $namedPlaceholders = [];
                foreach ($ids as $i => $typeId) {
                    $key = ':at_id_' . $i;
                    $namedPlaceholders[] = $key;
                    $params[$key] = $typeId;
                }
                $sql .= " AND al.activity_type_id IN (" . implode(',', $namedPlaceholders) . ")";
            }
        }

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return (float) ($result['total_reduction'] ?? 0);
        } catch (PDOException $e) {
            error_log("Challenge::getCommunityProgress error: " . $e->getMessage());
            return 0.0;
        }
    }
}
