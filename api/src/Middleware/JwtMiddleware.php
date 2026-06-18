<?php
declare(strict_types=1);

namespace App\Middleware;

use App\Services\JwtService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Response as SlimResponse;

/**
 * JwtMiddleware
 * 
 * Validates JWT tokens for protected routes
 * Attaches user data to the request for use in controllers
 */
class JwtMiddleware implements MiddlewareInterface
{
    private JwtService $jwtService;

    public function __construct()
    {
        $this->jwtService = new JwtService();
    }

    public function process(Request $request, RequestHandlerInterface $handler): Response
    {
        // Get Authorization header
        $authHeader = $request->getHeaderLine('Authorization');

        if (empty($authHeader)) {
            return $this->unauthorizedResponse('Authorization header missing');
        }

        // Extract token from header
        $token = JwtService::extractTokenFromHeader($authHeader);

        if (!$token) {
            return $this->unauthorizedResponse('Invalid token format. Use: Bearer TOKEN');
        }

        // Decode and verify token
        $decoded = $this->jwtService->decodeToken($token);

        if (!$decoded) {
            return $this->unauthorizedResponse('Invalid or expired token');
        }

        // Add user data to request attributes for access in controllers
        $request = $request->withAttribute('user', [
            'id' => $decoded->sub,
            'email' => $decoded->email,
            'role' => $decoded->role
        ]);

        // Proceed to the next middleware/handler
        return $handler->handle($request);
    }

    /**
     * Create unauthorized response
     */
    private function unauthorizedResponse(string $message): Response
    {
        $response = new SlimResponse();
        $response->getBody()->write(json_encode([
            'success' => false,
            'message' => $message
        ]));
        
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(401);
    }
}
