<?php
declare(strict_types=1);

namespace App\Services;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

/**
 * JwtService
 * 
 * Handles JWT token generation and verification
 */
class JwtService
{
    private string $secret;
    private int $expiry;

    public function __construct()
    {
        $this->secret = $_ENV['JWT_SECRET'] ?? 'default-secret-change-this';
        $this->expiry = (int) ($_ENV['JWT_EXPIRY'] ?? 86400); // 24 hours default
    }

    /**
     * Generate a JWT token for a user
     * 
     * @param int $userId User ID
     * @param string $email User email
     * @param string $role User role
     * @return string JWT token
     */
    public function generateToken(int $userId, string $email, string $role): string
    {
        $issuedAt = time();
        $expiration = $issuedAt + $this->expiry;

        $payload = [
            'iat' => $issuedAt,            // Issued at
            'exp' => $expiration,          // Expiration
            'sub' => $userId,              // Subject (user ID)
            'email' => $email,             // User email
            'role' => $role                // User role
        ];

        return JWT::encode($payload, $this->secret, 'HS256');
    }

    /**
     * Decode and verify a JWT token
     * 
     * @param string $token JWT token
     * @return object|null Decoded token payload or null if invalid
     */
    public function decodeToken(string $token): ?object
    {
        try {
            return JWT::decode($token, new Key($this->secret, 'HS256'));
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Extract token from Authorization header
     * 
     * @param string $header Authorization header value
     * @return string|null Token or null if not found
     */
    public static function extractTokenFromHeader(string $header): ?string
    {
        // Check for "Bearer TOKEN" format
        if (preg_match('/Bearer\s+(\S+)/i', $header, $matches)) {
            return $matches[1];
        }
        return null;
    }

    /**
     * Get token expiration time in seconds
     * 
     * @return int Expiration time in seconds
     */
    public function getExpiry(): int
    {
        return $this->expiry;
    }
}
