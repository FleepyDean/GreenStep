<?php
declare(strict_types=1);

use App\Controllers\ActivityController;
use App\Controllers\ActivityTypeController;
use App\Controllers\HealthController;
use Slim\App;

/** @var App $app */

// Health check endpoint
$app->get('/', [HealthController::class, 'check']);

// API Routes (all prefixed with /api)
$app->group('/api', function ($group) {
    
    // Health check
    $group->get('/health', [HealthController::class, 'check']);
    
    // ============================================
    // Activity Types (Public - for dropdown selection)
    // ============================================
    $group->get('/activity-types', [ActivityTypeController::class, 'index']);
    $group->get('/activity-types/categories', [ActivityTypeController::class, 'categories']);
    $group->get('/activity-types/category/{category}', [ActivityTypeController::class, 'byCategory']);
    
    // ============================================
    // Activity Logging (Protected - require auth)
    // ============================================
    $group->get('/activities', [ActivityController::class, 'index']);
    $group->get('/activities/today', [ActivityController::class, 'today']);
    $group->get('/activities/stats', [ActivityController::class, 'stats']);
    $group->post('/activities', [ActivityController::class, 'create']);
    $group->delete('/activities/{id}', [ActivityController::class, 'delete']);
    
    // Authentication routes (public)
    // $group->post('/auth/register', [AuthController::class, 'register']);
    // $group->post('/auth/login', [AuthController::class, 'login']);
    
    // Protected routes (require JWT)
    // $group->get('/users/profile', [UserController::class, 'profile']);
    
});
