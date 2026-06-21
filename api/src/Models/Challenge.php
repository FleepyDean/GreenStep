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
            return $result ?: null;
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
    public function create(array $data): int|false
    {
        $sql = "INSERT INTO Challenge (name, description, target_co2_reduction, duration_days, start_date, end_date)
                VALUES (:name, :description, :target_co2_reduction, :duration_days, :start_date, :end_date)";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':name' => $data['name'],
                ':description' => $data['description'],
                ':target_co2_reduction' => (float) $data['target_co2_reduction'],
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
}
