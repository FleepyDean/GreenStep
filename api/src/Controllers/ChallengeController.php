<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Models\Challenge;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * ChallengeController
 *
 * Handles eco challenge endpoints with Role-Based Access Control
 */
class ChallengeController
{
    private Challenge $challengeModel;

    public function __construct()
    {
        $this->challengeModel = new Challenge();
    }

    /**
     * GET /api/challenges
     * Fetch all challenges with member count and joined status
     */
    public function index(Request $request, Response $response): Response
    {
        $user = $request->getAttribute('user');
        $userId = (int) ($user['id'] ?? 0);

        $challenges = $this->challengeModel->getAllWithMemberCount($userId);

        $formatted = array_map(function ($c) {
            return [
                'id' => (int) $c['id'],
                'name' => $c['name'],
                'description' => $c['description'],
                'target_co2_reduction' => (float) $c['target_co2_reduction'],
                'duration_days' => (int) $c['duration_days'],
                'start_date' => $c['start_date'],
                'end_date' => $c['end_date'],
                'member_count' => (int) $c['member_count'],
                'has_joined' => (bool) $c['has_joined']
            ];
        }, $challenges);

        $response->getBody()->write(json_encode([
            'success' => true,
            'challenges' => $formatted
        ]));

        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
    }

    /**
     * POST /api/challenges
     * Create a new challenge (Admin Only)
     */
    public function create(Request $request, Response $response): Response
    {
        $user = $request->getAttribute('user');

        if (($user['role'] ?? '') !== 'admin') {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Admin access required'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(403);
        }

        $body = $request->getParsedBody() ?? [];

        $required = ['name', 'description', 'target_co2_reduction', 'duration_days', 'start_date', 'end_date'];
        foreach ($required as $field) {
            if (!isset($body[$field]) || $body[$field] === '') {
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'message' => "Field '{$field}' is required"
                ]));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
            }
        }

        $challengeId = $this->challengeModel->create([
            'name' => trim($body['name']),
            'description' => trim($body['description']),
            'target_co2_reduction' => (float) $body['target_co2_reduction'],
            'duration_days' => (int) $body['duration_days'],
            'start_date' => $body['start_date'],
            'end_date' => $body['end_date']
        ]);

        if (!$challengeId) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Failed to create challenge'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }

        $response->getBody()->write(json_encode([
            'success' => true,
            'message' => 'Challenge created successfully',
            'challenge' => [
                'id' => $challengeId,
                'name' => trim($body['name']),
                'description' => trim($body['description']),
                'target_co2_reduction' => (float) $body['target_co2_reduction'],
                'duration_days' => (int) $body['duration_days'],
                'start_date' => $body['start_date'],
                'end_date' => $body['end_date'],
                'member_count' => 0,
                'has_joined' => false
            ]
        ]));

        return $response->withHeader('Content-Type', 'application/json')->withStatus(201);
    }

    /**
     * POST /api/challenges/{id}/join
     * Join a challenge
     */
    public function join(Request $request, Response $response, array $args): Response
    {
        $user = $request->getAttribute('user');
        $userId = (int) ($user['id'] ?? 0);
        $challengeId = (int) ($args['id'] ?? 0);

        $challenge = $this->challengeModel->getById($challengeId);
        if (!$challenge) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Challenge not found'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
        }

        if ($this->challengeModel->isMember($challengeId, $userId)) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'You have already joined this challenge'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(409);
        }

        $success = $this->challengeModel->join($challengeId, $userId);

        if (!$success) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Failed to join challenge'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }

        $response->getBody()->write(json_encode([
            'success' => true,
            'message' => 'Successfully joined challenge'
        ]));

        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
    }

    /**
     * DELETE /api/challenges/{id}/leave
     * Leave a challenge
     */
    public function leave(Request $request, Response $response, array $args): Response
    {
        $user = $request->getAttribute('user');
        $userId = (int) ($user['id'] ?? 0);
        $challengeId = (int) ($args['id'] ?? 0);

        $success = $this->challengeModel->leave($challengeId, $userId);

        if (!$success) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'You are not a member of this challenge'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
        }

        $response->getBody()->write(json_encode([
            'success' => true,
            'message' => 'Successfully left challenge'
        ]));

        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
    }
}
