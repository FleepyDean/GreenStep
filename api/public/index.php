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
$app->setBasePath('/GreenStep/api/public');
$app->addRoutingMiddleware();

// Add Error Middleware (catches all errors)
$app->addErrorMiddleware(true, true, true);

// Load routes FIRST
require __DIR__ . '/../src/routes.php';

// Add middleware in order of execution (first added runs last)
// Execution order: CORS -> BodyParsing -> Routes

// 1. Add CORS first (runs LAST before routes)
$app->add(function ($request, $handler) {
    $response = $handler->handle($request);
    return $response
        ->withHeader('Access-Control-Allow-Origin', '*')
        ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
        ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS');
});

// 2. Add Body Parsing Middleware LAST (runs FIRST to parse JSON)
$app->add(new BodyParsingMiddleware());

$app->run();
