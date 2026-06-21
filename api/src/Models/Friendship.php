<?php
declare(strict_types=1);

namespace App\Models;

use App\Config\Database;
use PDO;
use PDOException;

/**
 * Friendship Model
 * 
 * Handles database operations for friend requests and friendships
 */
class Friendship
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    /**
     * Get accepted friends and pending requests involving the user
     * 
     * @param int $userId User ID
     * @return array Array of friendship records with friend details
     */
    public function getFriendsAndRequests(int $userId): array
    {
        $sql = "SELECT 
                    f.id,
                    f.sender_id,
                    f.receiver_id,
                    f.status,
                    f.created_at,
                    sender.name as sender_name,
                    sender.email as sender_email,
                    receiver.name as receiver_name,
                    receiver.email as receiver_email
                FROM Friendship f
                JOIN User sender ON f.sender_id = sender.id
                JOIN User receiver ON f.receiver_id = receiver.id
                WHERE (f.sender_id = :sender_id OR f.receiver_id = :receiver_id)
                  AND f.status IN ('pending', 'accepted')
                ORDER BY 
                    CASE f.status WHEN 'accepted' THEN 0 ELSE 1 END,
                    f.created_at DESC";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':sender_id' => $userId, ':receiver_id' => $userId]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($results as &$row) {
                $isSender = (int) $row['sender_id'] === $userId;
                $row['friend_name'] = $isSender ? $row['receiver_name'] : $row['sender_name'];
                $row['friend_email'] = $isSender ? $row['receiver_email'] : $row['sender_email'];
                $row['direction'] = $isSender ? 'sent' : 'received';
                unset($row['sender_name'], $row['sender_email'], $row['receiver_name'], $row['receiver_email']);
            }
            unset($row);

            return $results;
        } catch (PDOException $e) {
            error_log("Friendship::getFriendsAndRequests error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get accepted friend IDs for a user
     * 
     * @param int $userId User ID
     * @return array Array of friend user IDs
     */
    public function getAcceptedFriendIds(int $userId): array
    {
        $sql = "SELECT sender_id, receiver_id
                FROM Friendship
                WHERE (sender_id = :sender_id OR receiver_id = :receiver_id)
                  AND status = 'accepted'";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':sender_id' => $userId, ':receiver_id' => $userId]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $friendIds = [];
            foreach ($results as $row) {
                $friendIds[] = (int) $row['sender_id'] === $userId
                    ? (int) $row['receiver_id']
                    : (int) $row['sender_id'];
            }

            return $friendIds;
        } catch (PDOException $e) {
            error_log("Friendship::getAcceptedFriendIds error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get a single friendship request by ID
     * 
     * @param int $requestId Friendship request ID
     * @return array|null Friendship record or null
     */
    public function getById(int $requestId): ?array
    {
        $sql = "SELECT * FROM Friendship WHERE id = :id LIMIT 1";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':id' => $requestId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ?: null;
        } catch (PDOException $e) {
            error_log("Friendship::getById error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Create a friend request
     * 
     * @param int $senderId Sender user ID
     * @param int $receiverId Receiver user ID
     * @return int|false Request ID or false on failure
     */
    public function createRequest(int $senderId, int $receiverId): int|false
    {
        if ($senderId === $receiverId) {
            return false;
        }

        $sql = "INSERT INTO Friendship (sender_id, receiver_id, status) 
                VALUES (:sender_id, :receiver_id, 'pending')";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':sender_id' => $senderId,
                ':receiver_id' => $receiverId
            ]);
            return (int) $this->db->lastInsertId();
        } catch (PDOException $e) {
            error_log("Friendship::createRequest error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Check if a friendship or request already exists between two users
     * 
     * @param int $userId First user ID
     * @param int $otherId Second user ID
     * @return array|null Friendship record or null
     */
    public function getBetweenUsers(int $userId, int $otherId): ?array
    {
        $sql = "SELECT * FROM Friendship 
                WHERE (sender_id = :user_id_1 AND receiver_id = :other_id_1)
                   OR (sender_id = :other_id_2 AND receiver_id = :user_id_2)
                LIMIT 1";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':user_id_1' => $userId,
                ':other_id_1' => $otherId,
                ':other_id_2' => $otherId,
                ':user_id_2' => $userId
            ]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ?: null;
        } catch (PDOException $e) {
            error_log("Friendship::getBetweenUsers error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Update the status of a friendship request
     * 
     * @param int $requestId Friendship request ID
     * @param string $status New status ('accepted' or 'declined')
     * @return bool True on success
     */
    public function updateStatus(int $requestId, string $status): bool
    {
        if (!in_array($status, ['accepted', 'declined'], true)) {
            return false;
        }

        $sql = "UPDATE Friendship 
                SET status = :status 
                WHERE id = :id AND status = 'pending'";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':status' => $status,
                ':id' => $requestId
            ]);
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log("Friendship::updateStatus error: " . $e->getMessage());
            return false;
        }
    }
}
