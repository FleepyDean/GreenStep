<?php
declare(strict_types=1);

use Dotenv\Dotenv;
use Slim\Factory\AppFactory;
use Slim\Middleware\BodyParsingMiddleware;

require __DIR__ . '/../vendor/autoload.php';

// Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->safeLoad();

// Create Slim app
$app = AppFactory::create();
$app->addRoutingMiddleware();

// Add Error Middleware (catches all errors)
$app->addErrorMiddleware(true, true, true);

// Load routes FIRST
require __DIR__ . '/../src/routes.php';

// Add middleware in order of execution (first added runs last)

// 1. Completely Updated CORS Middleware with Preflight handling
$app->add(function ($request, $handler) {
    // If the browser sends an OPTIONS preflight request, intercept it immediately
    if ($request->getMethod() === 'OPTIONS') {
        $response = new \Slim\Psr7\Response();
        return $response
            ->withHeader('Access-Control-Allow-Origin', 'http://localhost:5173')
            ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
            ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS')
            ->withStatus(200);
    }

    $response = $handler->handle($request);
    
    // Attach headers to standard GET/POST responses
    return $response
        ->withHeader('Access-Control-Allow-Origin', 'http://localhost:5173')
        ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
        ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS')
        ->withHeader('Access-Control-Allow-Credentials', 'true');
});

// 2. Add Body Parsing Middleware LAST (runs FIRST to parse JSON)
$app->add(new BodyParsingMiddleware());

$app->run();