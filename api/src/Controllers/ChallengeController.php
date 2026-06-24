<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Models\ActivityType;
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
        $isAdmin = ($user['role'] ?? '') === 'admin';

        $challenges = $this->challengeModel->getAllWithMemberCount($userId);
        $today = date('Y-m-d');

        // Non-admin users should not see completed (expired) challenges
        if (!$isAdmin) {
            $challenges = array_values(array_filter($challenges, function ($c) use ($today) {
                return $c['end_date'] >= $today;
            }));
        }

        $formatted = array_map(function ($c) use ($today) {
            $isActive = $today >= $c['start_date'] && $today <= $c['end_date'];
            $isUpcoming = $today < $c['start_date'];

            return [
                'id' => (int) $c['id'],
                'name' => $c['name'],
                'description' => $c['description'],
                'target_co2_reduction' => (float) $c['target_co2_reduction'],
                'target_category' => $c['target_category'] ?? 'All',
                'target_activity_type_ids' => !empty($c['target_activity_type_id']) ? array_values(array_filter(array_map('intval', explode(',', $c['target_activity_type_id'])))) : [],
                'duration_days' => (int) $c['duration_days'],
                'start_date' => $c['start_date'],
                'end_date' => $c['end_date'],
                'is_active' => $isActive,
                'is_upcoming' => $isUpcoming,
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

        $targetActivityTypeIds = $body['target_activity_type_ids'] ?? [];
        if (is_string($targetActivityTypeIds)) {
            $targetActivityTypeIds = array_filter(array_map('intval', explode(',', $targetActivityTypeIds)));
        }
        $targetActivityTypeIds = array_values(array_filter(array_map('intval', (array) $targetActivityTypeIds)));

        $challengeId = $this->challengeModel->create([
            'name' => trim($body['name']),
            'description' => trim($body['description']),
            'target_co2_reduction' => (float) $body['target_co2_reduction'],
            'target_category' => $body['target_category'] ?? 'All',
            'target_activity_type_ids' => $targetActivityTypeIds,
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

        $today = date('Y-m-d');

        $response->getBody()->write(json_encode([
            'success' => true,
            'message' => 'Challenge created successfully',
            'challenge' => [
                'id' => $challengeId,
                'name' => trim($body['name']),
                'description' => trim($body['description']),
                'target_co2_reduction' => (float) $body['target_co2_reduction'],
                'target_category' => $body['target_category'] ?? 'All',
                'target_activity_type_ids' => $targetActivityTypeIds,
                'duration_days' => (int) $body['duration_days'],
                'start_date' => $body['start_date'],
                'end_date' => $body['end_date'],
                'is_active' => $today >= $body['start_date'] && $today <= $body['end_date'],
                'is_upcoming' => $today < $body['start_date'],
                'member_count' => 0,
                'has_joined' => false
            ]
        ]));

        return $response->withHeader('Content-Type', 'application/json')->withStatus(201);
    }

    /**
     * PUT /api/challenges/{id}
     * Update an existing challenge (Admin Only)
     */
    public function update(Request $request, Response $response, array $args): Response
    {
        $user = $request->getAttribute('user');

        if (($user['role'] ?? '') !== 'admin') {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Admin access required'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(403);
        }

        $challengeId = (int) ($args['id'] ?? 0);
        $challenge = $this->challengeModel->getById($challengeId);
        if (!$challenge) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Challenge not found'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
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

        $targetActivityTypeIds = $body['target_activity_type_ids'] ?? [];
        if (is_string($targetActivityTypeIds)) {
            $targetActivityTypeIds = array_filter(array_map('intval', explode(',', $targetActivityTypeIds)));
        }
        $targetActivityTypeIds = array_values(array_filter(array_map('intval', (array) $targetActivityTypeIds)));

        $success = $this->challengeModel->update($challengeId, [
            'name' => trim($body['name']),
            'description' => trim($body['description']),
            'target_co2_reduction' => (float) $body['target_co2_reduction'],
            'target_category' => $body['target_category'] ?? 'All',
            'target_activity_type_ids' => $targetActivityTypeIds,
            'duration_days' => (int) $body['duration_days'],
            'start_date' => $body['start_date'],
            'end_date' => $body['end_date']
        ]);

        if (!$success) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Failed to update challenge'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }

        $response->getBody()->write(json_encode([
            'success' => true,
            'message' => 'Challenge updated successfully'
        ]));

        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
    }

    /**
     * DELETE /api/challenges/{id}
     * Delete a challenge (Admin Only)
     */
    public function delete(Request $request, Response $response, array $args): Response
    {
        $user = $request->getAttribute('user');

        if (($user['role'] ?? '') !== 'admin') {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Admin access required'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(403);
        }

        $challengeId = (int) ($args['id'] ?? 0);
        $challenge = $this->challengeModel->getById($challengeId);
        if (!$challenge) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Challenge not found'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
        }

        $success = $this->challengeModel->delete($challengeId);

        if (!$success) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Failed to delete challenge'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }

        $response->getBody()->write(json_encode([
            'success' => true,
            'message' => 'Challenge deleted successfully'
        ]));

        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
    }

    /**
     * GET /api/challenges/{id}/details
     * Fetch comprehensive details for a single challenge including members and community progress
     */
    public function details(Request $request, Response $response, array $args): Response
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

        $members = $this->challengeModel->getMembers($challengeId);
        $isJoined = $this->challengeModel->isMember($challengeId, $userId);
        $targetCategory = $challenge['target_category'] ?? 'All';
        $targetActivityTypeIds = $challenge['target_activity_type_ids'] ?? [];
        $communityProgress = $this->challengeModel->getCommunityProgress(
            $challengeId,
            $challenge['start_date'],
            $challenge['end_date'],
            $targetCategory,
            !empty($targetActivityTypeIds) ? $targetActivityTypeIds : null
        );

        $activityTypeNames = [];
        if (!empty($targetActivityTypeIds)) {
            $activityTypeModel = new ActivityType();
            foreach ($targetActivityTypeIds as $atId) {
                $activityType = $activityTypeModel->getById($atId);
                if ($activityType) $activityTypeNames[] = $activityType['name'];
            }
        }

        $today = date('Y-m-d');
        $isActive = $today >= $challenge['start_date'] && $today <= $challenge['end_date'];
        $isUpcoming = $today < $challenge['start_date'];

        $formattedMembers = array_map(function ($m) use ($userId) {
            return [
                'user_id' => (int) $m['user_id'],
                'name' => $m['name'],
                'email' => $m['email'],
                'joined_at' => $m['joined_at'],
                'is_current_user' => (int) $m['user_id'] === $userId
            ];
        }, $members);

        $response->getBody()->write(json_encode([
            'success' => true,
            'challenge' => [
                'id' => (int) $challenge['id'],
                'name' => $challenge['name'],
                'description' => $challenge['description'],
                'target_co2_reduction' => (float) $challenge['target_co2_reduction'],
                'target_category' => $targetCategory,
                'target_activity_type_ids' => $targetActivityTypeIds,
                'target_activity_type_names' => $activityTypeNames,
                'duration_days' => (int) $challenge['duration_days'],
                'start_date' => $challenge['start_date'],
                'end_date' => $challenge['end_date'],
                'is_active' => $isActive,
                'is_upcoming' => $isUpcoming,
                'has_joined' => $isJoined,
                'member_count' => count($members),
                'members' => $formattedMembers,
                'current_progress' => $communityProgress
            ]
        ]));

        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
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
