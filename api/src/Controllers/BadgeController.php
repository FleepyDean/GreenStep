<?php
namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use PDO;

class BadgeController {

    public function getUserBadges(Request $request, Response $response): Response {
        // Automatically fetch the user id attached by your JwtMiddleware token check.
        // Falls back to user_id 1 if token mapping isn't fully bound yet.
        $userId = $request->getAttribute('userId') ?? $request->getAttribute('user_id') ?? 1; 

        try {
            // 1. Manually establish a quick connection using your Laragon credentials 
            // This completely bypasses Slim's container mapping issues
            $host = '127.0.0.1';
            $db   = 'greenstep_db'; // Adjust if your database name is different
            $user = 'root';
            $pass = ''; // Default Laragon password is blank
            $charset = 'utf8mb4';

            $dsn = "mysql:host=$host;dbname=$db;charset=$charset";
            $pdo = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);

            // 2. Your original LEFT JOIN milestone checking query
            $query = "
                SELECT 
                    b.id, 
                    b.name, 
                    JSON_UNQUOTE(JSON_EXTRACT(b.criteria_json, '$.description')) AS criteria_description,
                    CASE WHEN ub.earned_at IS NOT NULL THEN 1 ELSE 0 END AS unlocked
                FROM Badge b
                LEFT JOIN UserBadge ub ON b.id = ub.badge_id AND ub.user_id = :user_id
            ";

            $stmt = $pdo->prepare($query);
            $stmt->execute(['user_id' => $userId]);
            $badges = $stmt->fetchAll();

            // 3. Return clean JSON payload back to your Axios Vue client
            $response->getBody()->write(json_encode($badges));
            return $response->withHeader('Content-Type', 'application/json');

        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(["error" => $e->getMessage()]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }
}