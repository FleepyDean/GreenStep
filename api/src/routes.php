<?php
declare(strict_types=1);

use App\Controllers\ActivityController;
use App\Controllers\ActivityTypeController;
use App\Controllers\AuthController;
use App\Controllers\ChallengeController;
use App\Controllers\HealthController;
use App\Controllers\SocialController;
use App\Controllers\TipController;
use App\Middleware\JwtMiddleware;
use Slim\App;

/** @var App $app */

// Health check endpoint
$app->get('/', [HealthController::class, 'check']);

// API Routes (all prefixed with /api)
$app->group('/api', function ($group) {
    
    // Health check
    $group->get('/health', [HealthController::class, 'check']);
    
    // ============================================
    // Authentication Routes (Public)
    // ============================================
    $group->post('/auth/register', [AuthController::class, 'register']);
    $group->post('/auth/login', [AuthController::class, 'login']);
    
    // ============================================
    // Activity Types (Public - for dropdown selection)
    // ============================================
    $group->get('/activity-types', [ActivityTypeController::class, 'index']);
    $group->get('/activity-types/categories', [ActivityTypeController::class, 'categories']);
    $group->get('/activity-types/category/{category}', [ActivityTypeController::class, 'byCategory']);
    
    // ============================================
    // Protected Routes (Require JWT Token)
    // ============================================
    
    // Auth - Get current user
    $group->get('/auth/me', [AuthController::class, 'me'])
        ->add(new JwtMiddleware());
    
    // Activity Logging (Protected)
    $group->get('/activities', [ActivityController::class, 'index'])
        ->add(new JwtMiddleware());
    $group->get('/activities/today', [ActivityController::class, 'today'])
        ->add(new JwtMiddleware());
    $group->get('/activities/stats', [ActivityController::class, 'stats'])
        ->add(new JwtMiddleware());
    $group->post('/activities', [ActivityController::class, 'create'])
        ->add(new JwtMiddleware());
    $group->delete('/activities/{id}', [ActivityController::class, 'delete'])
        ->add(new JwtMiddleware());
    $group->get('/tips/random', [TipController::class, 'getRandom'])
        ->add(new JwtMiddleware());
    $group->get('/tips', [TipController::class, 'getAll'])
        ->add(new JwtMiddleware());
    $group->post('/tips', [TipController::class, 'create'])
        ->add(new JwtMiddleware());
    $group->delete('/tips/{id}', [TipController::class, 'delete'])
        ->add(new JwtMiddleware());
    
    // Social Features (Protected)
    $group->get('/friends', [SocialController::class, 'getFriends'])
        ->add(new JwtMiddleware());
    $group->post('/friends/request', [SocialController::class, 'sendRequest'])
        ->add(new JwtMiddleware());
    $group->put('/friends/request/{id}', [SocialController::class, 'updateRequest'])
        ->add(new JwtMiddleware());
    
    // Leaderboard (Protected)
    $group->get('/leaderboard', [SocialController::class, 'getLeaderboard'])
        ->add(new JwtMiddleware());
    
    // Eco Challenges (Protected)
    $group->get('/challenges', [ChallengeController::class, 'index'])
        ->add(new JwtMiddleware());
    $group->get('/challenges/{id}/details', [ChallengeController::class, 'details'])
        ->add(new JwtMiddleware());
    $group->post('/challenges', [ChallengeController::class, 'create'])
        ->add(new JwtMiddleware());
    $group->put('/challenges/{id}', [ChallengeController::class, 'update'])
        ->add(new JwtMiddleware());
    $group->delete('/challenges/{id}', [ChallengeController::class, 'delete'])
        ->add(new JwtMiddleware());
    $group->post('/challenges/{id}/join', [ChallengeController::class, 'join'])
        ->add(new JwtMiddleware());
    $group->delete('/challenges/{id}/leave', [ChallengeController::class, 'leave'])
        ->add(new JwtMiddleware());
});
