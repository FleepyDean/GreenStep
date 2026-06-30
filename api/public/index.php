<?php
declare(strict_types=1);

// Serve static files directly when using PHP built-in dev server
if (php_sapi_name() === 'cli-server') {
    $staticFile = __DIR__ . parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    if (is_file($staticFile)) {
        return false; // Let the built-in server serve it
    }
}

use App\Config\Database;
use Dotenv\Dotenv;
use Slim\Factory\AppFactory;
use Slim\Middleware\BodyParsingMiddleware;
use DI\Container;

require __DIR__ . '/../vendor/autoload.php';

// 1. Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->safeLoad();

// Set timezone to Malaysia (UTC+8) so date() matches user's local time
date_default_timezone_set('Asia/Kuala_Lumpur');

// 2. Initialize Dependency Injection Container and attach PDO
$container = new Container();
$container->set(PDO::class, function () {
    return Database::getConnection();
});

// 3. Create Slim app using the configured container
AppFactory::setContainer($container);
$app = AppFactory::create();

$app->addRoutingMiddleware();

// 4. Add Error Middleware (catches all errors)
$errorMiddleware = $app->addErrorMiddleware(true, true, true);
$errorMiddleware->setDefaultErrorHandler(function ($request, $exception, $displayErrorDetails) use ($app) {
    $allowedOrigin = $_ENV['FRONTEND_URL'] ?? 'http://localhost:5173';
    $response = $app->getResponseFactory()->createResponse(500);
    $response->getBody()->write(json_encode(['success' => false, 'error' => $exception->getMessage()]));
    return $response
        ->withHeader('Content-Type', 'application/json')
        ->withHeader('Access-Control-Allow-Origin', $allowedOrigin)
        ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
        ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS');
});

// 5. Load routes AFTER setting up the container so controllers resolve properly
require __DIR__ . '/../src/routes.php';

// ==================================================================
// MIDDLEWARE STACK (Ordered by execution: first added runs last)
// ==================================================================

// 6. CORS Middleware with Preflight handling
$app->add(function ($request, $handler) {
    $allowedOrigin = $_ENV['FRONTEND_URL'] ?? 'http://localhost:5173';

    if ($request->getMethod() === 'OPTIONS') {
        $response = new \Slim\Psr7\Response();
        return $response
            ->withHeader('Access-Control-Allow-Origin', $allowedOrigin)
            ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
            ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS')
            ->withStatus(200);
    }

    $response = $handler->handle($request);

    return $response
        ->withHeader('Access-Control-Allow-Origin', $allowedOrigin)
        ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
        ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS')
        ->withHeader('Access-Control-Allow-Credentials', 'true');
});

// 7. Add Body Passing Middleware (Runs first to parse JSON bodies)
$app->add(new BodyParsingMiddleware());

$app->run();