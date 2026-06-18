<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Models\User;
use App\Services\JwtService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * AuthController
 * 
 * Handles authentication endpoints:
 * - POST /api/auth/register - User registration
 * - POST /api/auth/login - User login
 */
class AuthController
{
    private User $userModel;
    private JwtService $jwtService;

    public function __construct()
    {
        $this->userModel = new User();
        $this->jwtService = new JwtService();
    }

    /**
     * POST /api/auth/register
     * Register a new user
     * 
     * Request body:
     * {
     *   "name": "John Doe",
     *   "email": "john@example.com",
     *   "password": "securepassword123"
     * }
     */
    public function register(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();

        // Check if body was parsed
        if ($data === null) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Invalid JSON or empty request body',
                'debug' => [
                    'content_type' => $request->getHeaderLine('Content-Type'),
                    'body' => (string) $request->getBody()
                ]
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        // Validate required fields
        $errors = $this->validateRegistration($data);
        if (!empty($errors)) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $errors
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        $name = trim($data['name']);
        $email = strtolower(trim($data['email']));
        $password = $data['password'];

        // Check if email already exists
        if ($this->userModel->emailExists($email)) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Email already registered'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(409);
        }

        // Hash password
        $passwordHash = User::hashPassword($password);

        // Create user
        $userId = $this->userModel->create($name, $email, $passwordHash);

        if (!$userId) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Failed to create user'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }

        // Generate JWT token
        $token = $this->jwtService->generateToken($userId, $email, 'end-user');

        // Return success response
        $response->getBody()->write(json_encode([
            'success' => true,
            'message' => 'User registered successfully',
            'token' => $token,
            'expires_in' => $this->jwtService->getExpiry(),
            'user' => [
                'id' => $userId,
                'name' => $name,
                'email' => $email,
                'role' => 'end-user'
            ]
        ]));

        return $response->withHeader('Content-Type', 'application/json')->withStatus(201);
    }

    /**
     * POST /api/auth/login
     * Authenticate user and return JWT token
     * 
     * Request body:
     * {
     *   "email": "john@example.com",
     *   "password": "securepassword123"
     * }
     */
    public function login(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();

        // Validate required fields
        if (empty($data['email']) || empty($data['password'])) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Email and password are required'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        $email = strtolower(trim($data['email']));
        $password = $data['password'];

        // Find user by email
        $user = $this->userModel->getByEmail($email);

        if (!$user) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Invalid credentials'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
        }

        // Verify password
        if (!User::verifyPassword($password, $user['password_hash'])) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Invalid credentials'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
        }

        // Generate JWT token
        $token = $this->jwtService->generateToken(
            (int) $user['id'],
            $user['email'],
            $user['role']
        );

        // Return success response
        $response->getBody()->write(json_encode([
            'success' => true,
            'message' => 'Login successful',
            'token' => $token,
            'expires_in' => $this->jwtService->getExpiry(),
            'user' => [
                'id' => (int) $user['id'],
                'name' => $user['name'],
                'email' => $user['email'],
                'role' => $user['role'],
                'joined_at' => $user['joined_at']
            ]
        ]));

        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
    }

    /**
     * GET /api/auth/me
     * Get current authenticated user profile
     * (Protected route - requires JWT)
     */
    public function me(Request $request, Response $response): Response
    {
        // Get user data from request attribute (set by JWT middleware)
        $user = $request->getAttribute('user');

        if (!$user) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Not authenticated'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
        }

        // Get full user details from database
        $userDetails = $this->userModel->getById($user['id']);

        if (!$userDetails) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'User not found'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
        }

        $response->getBody()->write(json_encode([
            'success' => true,
            'user' => $userDetails
        ]));

        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
    }

    /**
     * Validate registration data
     * 
     * @param array $data Registration data
     * @return array Array of validation errors
     */
    private function validateRegistration(array $data): array
    {
        $errors = [];

        // Name validation
        if (empty($data['name'])) {
            $errors['name'] = 'Name is required';
        } elseif (strlen($data['name']) < 2 || strlen($data['name']) > 100) {
            $errors['name'] = 'Name must be between 2 and 100 characters';
        }

        // Email validation
        if (empty($data['email'])) {
            $errors['email'] = 'Email is required';
        } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Invalid email format';
        } elseif (strlen($data['email']) > 150) {
            $errors['email'] = 'Email must not exceed 150 characters';
        }

        // Password validation
        if (empty($data['password'])) {
            $errors['password'] = 'Password is required';
        } elseif (strlen($data['password']) < 8) {
            $errors['password'] = 'Password must be at least 8 characters';
        }

        return $errors;
    }
}
