<?php
declare(strict_types=1);

namespace App\Models;

use App\Config\Database;
use PDO;
use PDOException;

/**
 * User Model
 * 
 * Handles database operations for user management
 */
class User
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    /**
     * Create a new user
     * 
     * @param string $name User full name
     * @param string $email User email
     * @param string $passwordHash Hashed password
     * @return int|false User ID or false on failure
     */
    public function create(string $name, string $email, string $passwordHash): int|false
    {
        $sql = "INSERT INTO User (name, email, password_hash, role) 
                VALUES (:name, :email, :password_hash, 'end-user')";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':name' => $name,
                ':email' => $email,
                ':password_hash' => $passwordHash
            ]);
            
            return (int) $this->db->lastInsertId();
        } catch (PDOException $e) {
            error_log("User::create error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get user by email
     * 
     * @param string $email User email
     * @return array|null User data or null if not found
     */
    public function getByEmail(string $email): ?array
    {
        $sql = "SELECT * FROM User WHERE email = :email LIMIT 1";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':email' => $email]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ?: null;
        } catch (PDOException $e) {
            error_log("User::getByEmail error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get user by ID
     * 
     * @param int $id User ID
     * @return array|null User data or null if not found
     */
    public function getById(int $id): ?array
    {
        $sql = "SELECT id, name, email, role, joined_at FROM User WHERE id = :id LIMIT 1";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':id' => $id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ?: null;
        } catch (PDOException $e) {
            error_log("User::getById error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Check if email already exists
     * 
     * @param string $email Email to check
     * @return bool True if email exists
     */
    public function emailExists(string $email): bool
    {
        return $this->getByEmail($email) !== null;
    }

    /**
     * Verify user password
     * 
     * @param string $inputPassword Plain text password
     * @param string $storedHash Stored password hash
     * @return bool True if password matches
     */
    public static function verifyPassword(string $inputPassword, string $storedHash): bool
    {
        return password_verify($inputPassword, $storedHash);
    }

    /**
     * Hash password using bcrypt
     * 
     * @param string $password Plain text password
     * @return string Hashed password
     */
    public static function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    }

    /**
     * Update user profile
     * 
     * @param int $id User ID
     * @param array $data Fields to update (name, email)
     * @return bool True on success
     */
    public function update(int $id, array $data): bool
    {
        $allowedFields = ['name', 'email'];
        $updates = [];
        $params = [':id' => $id];

        foreach ($data as $field => $value) {
            if (in_array($field, $allowedFields)) {
                $updates[] = "$field = :$field";
                $params[":$field"] = $value;
            }
        }

        if (empty($updates)) {
            return false;
        }

        $sql = "UPDATE User SET " . implode(', ', $updates) . " WHERE id = :id";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log("User::update error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get all users (admin only)
     * 
     * @return array Array of users
     */
    public function getAll(): array
    {
        $sql = "SELECT id, name, email, role, joined_at FROM User ORDER BY joined_at DESC";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("User::getAll error: " . $e->getMessage());
            return [];
        }
    }
}
