<?php
declare(strict_types=1);

use App\Controllers\HealthController;
use Slim\App;

/** @var App $app */

// Health check endpoint
$app->get('/', [HealthController::class, 'check']);

// API Routes (all prefixed with /api)
$app->group('/api', function ($group) {
    
    // Health check
    $group->get('/health', [HealthController::class, 'check']);
    
    // Authentication routes (public)
    // $group->post('/auth/register', [AuthController::class, 'register']);
    // $group->post('/auth/login', [AuthController::class, 'login']);
    
    // Protected routes (require JWT)
    // $group->get('/users/profile', [UserController::class, 'profile']);
    // $group->get('/activities', [ActivityController::class, 'index']);
    // etc.
    
});
