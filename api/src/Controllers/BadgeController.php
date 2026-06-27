<?php
namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use PDO;

class BadgeController {
    private $db;

    // Slim apps typically inject container elements or PDO connections directly
    public function __construct(PDO $db) {
        $this->db = $db;
    }

    public function getUserBadges(Request $request, Response $response): Response {
        // Grab the user id dynamically attached by your JwtMiddleware token check
        // (Adjust attribute name string to match how AuthController::me grabs it)
        $userId = $request->getAttribute('userId') ?? 1; 

        try {
            $query = "
                SELECT 
                    b.id, 
                    b.name, 
                    JSON_UNQUOTE(JSON_EXTRACT(b.criteria_json, '$.description')) AS criteria_description,
                    CASE WHEN ub.earned_at IS NOT NULL THEN 1 ELSE 0 END AS unlocked
                FROM Badge b
                LEFT JOIN UserBadge ub ON b.id = ub.badge_id AND ub.user_id = :user_id
            ";

            $stmt = $this->db->prepare($query);
            $stmt->execute(['user_id' => $userId]);
            $badges = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $response->getBody()->write(json_encode($badges));
            return $response->withHeader('Content-Type', 'application/json');

        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(["error" => $e->getMessage()]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }
}