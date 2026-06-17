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
}
