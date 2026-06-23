<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Models\ActivityType;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * ActivityTypeController
 * 
 * Handles activity type and emission factor endpoints:
 * - GET /api/activity-types - Get all activity types
 * - GET /api/activity-types/categories - Get available categories
 */
class ActivityTypeController
{
    private ActivityType $model;

    public function __construct()
    {
        $this->model = new ActivityType();
    }

    /**
     * GET /api/activity-types
     * Get all activity types, optionally grouped by category
     * 
     * Query parameters:
     * - grouped: true/false (default: true)
     */
    public function index(Request $request, Response $response): Response
    {
        $queryParams = $request->getQueryParams();
        $grouped = isset($queryParams['grouped']) ? filter_var($queryParams['grouped'], FILTER_VALIDATE_BOOL) : true;

        if ($grouped) {
            $activityTypes = $this->model->getGroupedByCategory();
        } else {
            $activityTypes = $this->model->getAll();
        }

        $response->getBody()->write(json_encode([
            'success' => true,
            'count' => is_array($activityTypes) && isset($activityTypes[0]) 
                ? count($activityTypes) 
                : array_sum(array_map('count', $activityTypes)),
            'activity_types' => $activityTypes
        ]));

        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
    }

    /**
     * GET /api/activity-types/categories
     * Get list of available categories
     */
    public function categories(Request $request, Response $response): Response
    {
        $categories = $this->model->getCategories();

        $response->getBody()->write(json_encode([
            'success' => true,
            'categories' => $categories
        ]));

        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
    }

    /**
     * GET /api/activity-types/category/{category}
     * Get activity types for a specific category
     */
    public function byCategory(Request $request, Response $response, array $args): Response
    {
        $category = $args['category'] ?? '';

        if (empty($category)) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Category parameter is required'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        // Validate category is one of the allowed values
        $validCategories = ['Transport', 'Diet', 'Energy', 'Recycling'];
        if (!in_array($category, $validCategories)) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Invalid category. Valid categories: ' . implode(', ', $validCategories)
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        $activityTypes = $this->model->getByCategory($category);

        $response->getBody()->write(json_encode([
            'success' => true,
            'category' => $category,
            'count' => count($activityTypes),
            'activity_types' => $activityTypes
        ]));

        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
    }

    /**
     * POST /api/admin/activity-types
     * Create a new activity type (admin only)
     */
    public function store(Request $request, Response $response): Response
    {
        $user = $request->getAttribute('user');
        if (($user['role'] ?? '') !== 'admin') {
            $response->getBody()->write(json_encode(['success' => false, 'message' => 'Admin access required']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(403);
        }

        $data = json_decode($request->getBody()->getContents(), true);

        $validCategories = ['Transport', 'Diet', 'Energy', 'Recycling'];
        $category = $data['category'] ?? '';
        $name     = trim($data['name'] ?? '');
        $unit     = trim($data['unit'] ?? '');
        $factor   = isset($data['kg_co2_per_unit']) ? (float) $data['kg_co2_per_unit'] : null;

        if (!in_array($category, $validCategories) || empty($name) || empty($unit) || $factor === null) {
            $response->getBody()->write(json_encode(['success' => false, 'message' => 'Missing or invalid fields: category, name, unit, kg_co2_per_unit are required']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        $id = $this->model->create($category, $name, $unit, $factor);

        if (!$id) {
            $response->getBody()->write(json_encode(['success' => false, 'message' => 'Failed to create activity type']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }

        $response->getBody()->write(json_encode([
            'success' => true,
            'message' => 'Activity type created',
            'activity_type' => $this->model->getById($id)
        ]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(201);
    }

    /**
     * PUT /api/admin/activity-types/{id}
     * Update an activity type (admin only)
     */
    public function update(Request $request, Response $response, array $args): Response
    {
        $user = $request->getAttribute('user');
        if (($user['role'] ?? '') !== 'admin') {
            $response->getBody()->write(json_encode(['success' => false, 'message' => 'Admin access required']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(403);
        }

        $id   = (int) ($args['id'] ?? 0);
        $data = json_decode($request->getBody()->getContents(), true);

        $existing = $this->model->getById($id);
        if (!$existing) {
            $response->getBody()->write(json_encode(['success' => false, 'message' => 'Activity type not found']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
        }

        $validCategories = ['Transport', 'Diet', 'Energy', 'Recycling'];
        $category = $data['category'] ?? $existing['category'];
        $name     = trim($data['name'] ?? $existing['name']);
        $unit     = trim($data['unit'] ?? $existing['unit']);
        $factor   = isset($data['kg_co2_per_unit']) ? (float) $data['kg_co2_per_unit'] : (float) $existing['kg_co2_per_unit'];

        if (!in_array($category, $validCategories)) {
            $response->getBody()->write(json_encode(['success' => false, 'message' => 'Invalid category']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        $this->model->update($id, $category, $name, $unit, $factor);

        $response->getBody()->write(json_encode([
            'success' => true,
            'message' => 'Activity type updated',
            'activity_type' => $this->model->getById($id)
        ]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
    }

    /**
     * DELETE /api/admin/activity-types/{id}
     * Delete an activity type (admin only)
     */
    public function destroy(Request $request, Response $response, array $args): Response
    {
        $user = $request->getAttribute('user');
        if (($user['role'] ?? '') !== 'admin') {
            $response->getBody()->write(json_encode(['success' => false, 'message' => 'Admin access required']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(403);
        }

        $id = (int) ($args['id'] ?? 0);

        if (!$this->model->getById($id)) {
            $response->getBody()->write(json_encode(['success' => false, 'message' => 'Activity type not found']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
        }

        $deleted = $this->model->delete($id);

        if (!$deleted) {
            $response->getBody()->write(json_encode(['success' => false, 'message' => 'Failed to delete. It may be referenced by existing activity logs.']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(409);
        }

        $response->getBody()->write(json_encode(['success' => true, 'message' => 'Activity type deleted']));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
    }
}
