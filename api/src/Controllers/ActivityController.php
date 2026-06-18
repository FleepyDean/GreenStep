<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Models\ActivityLog;
use App\Models\ActivityType;
use App\Services\CarbonCalculator;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * ActivityController
 * 
 * Handles activity logging API endpoints:
 * - POST /api/activities - Log new activity
 * - GET /api/activities - Get user's activities
 * - GET /api/activities/today - Get today's activities
 * - GET /api/activities/stats - Get activity statistics
 * - DELETE /api/activities/{id} - Delete activity
 */
class ActivityController
{
    private ActivityLog $activityLogModel;
    private ActivityType $activityTypeModel;

    public function __construct()
    {
        $this->activityLogModel = new ActivityLog();
        $this->activityTypeModel = new ActivityType();
    }

    /**
     * POST /api/activities
     * Log a new activity
     * 
     * Request body:
     * {
     *   "activity_type_id": 1,
     *   "amount": 15.5,
     *   "date": "2026-06-17"
     * }
     */
    public function create(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();
        
        // Validate required fields
        if (empty($data['activity_type_id']) || empty($data['amount'])) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Missing required fields: activity_type_id and amount are required'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        $activityTypeId = (int) $data['activity_type_id'];
        $amount = (float) $data['amount'];
        $date = $data['date'] ?? date('Y-m-d');

        // Validate amount is positive
        if ($amount <= 0) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Amount must be greater than 0'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        // Get activity type and emission factor
        $activityType = $this->activityTypeModel->getById($activityTypeId);
        if (!$activityType) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Activity type not found'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
        }

        // Calculate carbon footprint
        $emissionFactor = (float) $activityType['kg_co2_per_unit'];
        $carbonFootprint = CarbonCalculator::calculate($amount, $emissionFactor);

        // Get user ID from JWT token (set by JwtMiddleware)
        $user = $request->getAttribute('user');
        $userId = $user['id'] ?? null;

        // Create activity log
        $logId = $this->activityLogModel->create(
            $userId,
            $activityTypeId,
            $amount,
            $carbonFootprint,
            $date
        );

        if (!$logId) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Failed to log activity'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }

        // Return success response
        $response->getBody()->write(json_encode([
            'success' => true,
            'message' => 'Activity logged successfully',
            'activity' => [
                'id' => $logId,
                'activity_type_id' => $activityTypeId,
                'category' => $activityType['category'],
                'activity_name' => $activityType['name'],
                'unit' => $activityType['unit'],
                'amount' => $amount,
                'carbon_footprint' => $carbonFootprint,
                'emission_factor' => $emissionFactor,
                'logged_on' => $date
            ]
        ]));

        return $response->withHeader('Content-Type', 'application/json')->withStatus(201);
    }

    /**
     * GET /api/activities
     * Get all activities for the current user
     * 
     * Query parameters:
     * - start_date: YYYY-MM-DD (optional)
     * - end_date: YYYY-MM-DD (optional)
     */
    public function index(Request $request, Response $response): Response
    {
        $queryParams = $request->getQueryParams();
        $startDate = $queryParams['start_date'] ?? null;
        $endDate = $queryParams['end_date'] ?? null;

        // Get user ID from JWT token
        $user = $request->getAttribute('user');
        $userId = $user['id'] ?? null;

        $activities = $this->activityLogModel->getByUser($userId, $startDate, $endDate);

        $response->getBody()->write(json_encode([
            'success' => true,
            'count' => count($activities),
            'activities' => $activities
        ]));

        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
    }

    /**
     * GET /api/activities/today
     * Get today's activities for the current user
     */
    public function today(Request $request, Response $response): Response
    {
        // Get user ID from JWT token
        $user = $request->getAttribute('user');
        $userId = $user['id'] ?? null;

        $activities = $this->activityLogModel->getToday($userId);
        
        // Calculate total carbon footprint for today
        $total = 0;
        foreach ($activities as $activity) {
            $total += $activity['carbon_footprint'] ?? 0;
        }

        $response->getBody()->write(json_encode([
            'success' => true,
            'date' => date('Y-m-d'),
            'count' => count($activities),
            'total_footprint' => round($total, 4),
            'activities' => $activities
        ]));

        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
    }

    /**
     * GET /api/activities/stats
     * Get activity statistics
     * 
     * Query parameters:
     * - start_date: YYYY-MM-DD (optional)
     * - end_date: YYYY-MM-DD (optional)
     */
    public function stats(Request $request, Response $response): Response
    {
        $queryParams = $request->getQueryParams();
        $startDate = $queryParams['start_date'] ?? null;
        $endDate = $queryParams['end_date'] ?? null;

        // Get user ID from JWT token
        $user = $request->getAttribute('user');
        $userId = $user['id'] ?? null;

        $stats = $this->activityLogModel->getStats($userId, $startDate, $endDate);

        $response->getBody()->write(json_encode([
            'success' => true,
            'stats' => $stats
        ]));

        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
    }

    /**
     * DELETE /api/activities/{id}
     * Delete an activity log
     */
    public function delete(Request $request, Response $response, array $args): Response
    {
        $id = (int) ($args['id'] ?? 0);

        if ($id <= 0) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Invalid activity ID'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        // Get user ID from JWT token
        $user = $request->getAttribute('user');
        $userId = $user['id'] ?? null;

        // Check if activity exists and belongs to user
        $activity = $this->activityLogModel->getById($id);
        if (!$activity) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Activity not found'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
        }

        if ($activity['user_id'] != $userId) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Unauthorized to delete this activity'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(403);
        }

        // Delete the activity
        $deleted = $this->activityLogModel->delete($id, $userId);

        if ($deleted) {
            $response->getBody()->write(json_encode([
                'success' => true,
                'message' => 'Activity deleted successfully'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        } else {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Failed to delete activity'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }
}
