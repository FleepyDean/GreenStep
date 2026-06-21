<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Config\Database;
use App\Models\ActivityLog;
use App\Models\Friendship;
use App\Models\User;
use PDO;
use PDOException;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * SocialController
 * 
 * Handles friend connections and community leaderboard endpoints
 */
class SocialController
{
    private Friendship $friendshipModel;
    private User $userModel;
    private ActivityLog $activityLogModel;

    public function __construct()
    {
        $this->friendshipModel = new Friendship();
        $this->userModel = new User();
        $this->activityLogModel = new ActivityLog();
    }

    /**
     * GET /api/friends
     * Get accepted friends and pending friend requests for the authenticated user
     */
    public function getFriends(Request $request, Response $response): Response
    {
        $user = $request->getAttribute('user');
        $userId = (int) ($user['id'] ?? 0);

        $friendships = $this->friendshipModel->getFriendsAndRequests($userId);

        $acceptedFriends = [];
        $pendingRequests = [];

        foreach ($friendships as $friendship) {
            $formatted = [
                'friendship_id' => (int) $friendship['id'],
                'friend_id' => (int) ($friendship['sender_id'] == $userId
                    ? $friendship['receiver_id']
                    : $friendship['sender_id']),
                'friend_name' => $friendship['friend_name'],
                'friend_email' => $friendship['friend_email'],
                'status' => $friendship['status'],
                'direction' => $friendship['direction'],
                'requested_at' => $friendship['created_at']
            ];

            if ($friendship['status'] === 'accepted') {
                $acceptedFriends[] = $formatted;
            } else {
                $pendingRequests[] = $formatted;
            }
        }

        $response->getBody()->write(json_encode([
            'success' => true,
            'friends' => $acceptedFriends,
            'pending_requests' => $pendingRequests
        ]));

        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
    }

    /**
     * POST /api/friends/request
     * Send a friend request to another user by email or user ID
     */
    public function sendRequest(Request $request, Response $response): Response
    {
        $user = $request->getAttribute('user');
        $senderId = (int) ($user['id'] ?? 0);

        $body = $request->getParsedBody() ?? [];
        $identifier = trim($body['identifier'] ?? '');

        if (empty($identifier)) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Email or user ID is required'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        // Resolve identifier to a user
        if (is_numeric($identifier)) {
            $receiver = $this->userModel->getById((int) $identifier);
        } else {
            $receiver = $this->userModel->getByEmail($identifier);
        }

        if (!$receiver) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'User not found'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
        }

        $receiverId = (int) $receiver['id'];

        if ($receiverId === $senderId) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'You cannot send a friend request to yourself'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        // Check for existing friendship or pending request
        $existing = $this->friendshipModel->getBetweenUsers($senderId, $receiverId);
        if ($existing) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'A friend request or friendship already exists with this user',
                'status' => $existing['status']
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(409);
        }

        $requestId = $this->friendshipModel->createRequest($senderId, $receiverId);

        if (!$requestId) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Failed to send friend request'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }

        $response->getBody()->write(json_encode([
            'success' => true,
            'message' => 'Friend request sent successfully',
            'request' => [
                'id' => $requestId,
                'receiver_id' => $receiverId,
                'receiver_name' => $receiver['name'],
                'receiver_email' => $receiver['email'],
                'status' => 'pending'
            ]
        ]));

        return $response->withHeader('Content-Type', 'application/json')->withStatus(201);
    }

    /**
     * PUT /api/friends/request/{id}
     * Accept or decline a pending friend request
     */
    public function updateRequest(Request $request, Response $response, array $args): Response
    {
        $user = $request->getAttribute('user');
        $userId = (int) ($user['id'] ?? 0);

        $requestId = (int) ($args['id'] ?? 0);
        $body = $request->getParsedBody() ?? [];
        $status = strtolower(trim($body['status'] ?? ''));

        if (!in_array($status, ['accepted', 'declined'], true)) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Status must be either "accepted" or "declined"'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        $friendship = $this->friendshipModel->getById($requestId);

        if (!$friendship) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Friend request not found'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
        }

        // Only the receiver can respond to the request
        if ((int) $friendship['receiver_id'] !== $userId) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Unauthorized to respond to this request'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(403);
        }

        if ($friendship['status'] !== 'pending') {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'This request has already been responded to'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(409);
        }

        $success = $this->friendshipModel->updateStatus($requestId, $status);

        if (!$success) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Failed to update friend request'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }

        $response->getBody()->write(json_encode([
            'success' => true,
            'message' => $status === 'accepted' ? 'Friend request accepted' : 'Friend request declined',
            'status' => $status
        ]));

        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
    }

    /**
     * GET /api/leaderboard
     * Get global or friends-only leaderboard rankings based on carbon footprint
     */
    public function getLeaderboard(Request $request, Response $response): Response
    {
        $user = $request->getAttribute('user');
        $userId = (int) ($user['id'] ?? 0);

        $queryParams = $request->getQueryParams();
        $filter = strtolower(trim($queryParams['filter'] ?? 'global'));
        $filterFriends = $filter === 'friends';

        $userIds = null;
        if ($filterFriends) {
            $friendIds = $this->friendshipModel->getAcceptedFriendIds($userId);
            $userIds = array_merge([$userId], $friendIds);
        }

        $leaderboard = $this->fetchLeaderboardRankings($userIds);

        // Add rank and current user flag
        $ranked = [];
        foreach ($leaderboard as $index => $row) {
            $ranked[] = [
                'rank' => $index + 1,
                'user_id' => (int) $row['user_id'],
                'name' => $row['name'],
                'email' => $row['email'],
                'total_footprint' => (float) $row['total_footprint'],
                'is_current_user' => (int) $row['user_id'] === $userId
            ];
        }

        $response->getBody()->write(json_encode([
            'success' => true,
            'filter' => $filterFriends ? 'friends' : 'global',
            'leaderboard' => $ranked
        ]));

        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
    }

    /**
     * Fetch leaderboard rankings from the database
     * 
     * @param array|null $userIds Optional list of user IDs to filter by
     * @return array Ranked users ordered by total carbon footprint ascending
     */
    private function fetchLeaderboardRankings(?array $userIds = null): array
    {
        $sql = "SELECT 
                    u.id as user_id,
                    u.name,
                    u.email,
                    COALESCE(SUM(al.carbon_footprint), 0) as total_footprint
                FROM User u
                LEFT JOIN ActivityLog al ON u.id = al.user_id
                WHERE 1=1";

        $params = [];

        if ($userIds !== null && !empty($userIds)) {
            $placeholders = [];
            foreach ($userIds as $index => $id) {
                $placeholder = ":user_id_{$index}";
                $placeholders[] = $placeholder;
                $params[$placeholder] = (int) $id;
            }
            $sql .= " AND u.id IN (" . implode(',', $placeholders) . ")";
        }

        $sql .= " GROUP BY u.id, u.name, u.email
                  ORDER BY total_footprint ASC, u.name ASC";

        try {
            $db = Database::getConnection();
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("SocialController::fetchLeaderboardRankings error: " . $e->getMessage());
            return [];
        }
    }
}
