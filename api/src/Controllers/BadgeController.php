<?php
namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use PDO;

class BadgeController {

    public function getUserBadges(Request $request, Response $response): Response {
        // Automatically fetch the user id attached by your JwtMiddleware token check.
        $user = $request->getAttribute('user');
        $userId = $user['id'] ?? 1; // Falls back to user_id 1 if token mapping isn't fully bound yet

        try {
            $host = '127.0.0.1';
            $db   = 'greenstep_db'; 
            $dbUser = 'root';
            $pass = ''; // Matches your Laragon password
            $charset = 'utf8mb4';

            $dsn = "mysql:host=$host;dbname=$db;charset=$charset";
            $pdo = new PDO($dsn, $dbUser, $pass, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);

            // 🚀 THE MASTER DYNAMIC QUERY
            // This calculates distinct logged days on the fly for IDs 6, 7, 8, 9 
            // and uses your original mapping approach for other preset badges!
            $query = "
                SELECT 
                    b.id, 
                    b.name, 
                    JSON_UNQUOTE(JSON_EXTRACT(b.criteria_json, '$.description')) AS criteria_description,
                    CASE 
                        -- Streak Milestone Badges calculated directly from activitylog
                        WHEN b.id IN (6, 7, 8, 9) AND (
                            SELECT COUNT(DISTINCT `logged_on`) 
                            FROM activitylog 
                            WHERE user_id = :user_id
                        ) >= CASE 
                            WHEN b.id = 6 THEN 1
                            WHEN b.id = 7 THEN 3
                            WHEN b.id = 8 THEN 5
                            WHEN b.id = 9 THEN 10
                        END THEN 1

                        -- All other badges fallback to the standard bridge table mapping
                        WHEN ub.earned_at IS NOT NULL THEN 1 
                        
                        ELSE 0 
                    END AS unlocked
                FROM badge b
                LEFT JOIN userbadge ub ON b.id = ub.badge_id AND ub.user_id = :user_id2
            ";

            $stmt = $pdo->prepare($query);
            // We pass the user ID for both query calculation binds
            $stmt->execute([
                'user_id'   => $userId,
                'user_id2'  => $userId
            ]);
            $badges = $stmt->fetchAll();

            // Return clean JSON payload back to your Axios Vue client
            $response->getBody()->write(json_encode($badges));
            return $response->withHeader('Content-Type', 'application/json');

        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(["error" => $e->getMessage()]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }
}