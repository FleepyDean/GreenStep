<?php
declare(strict_types=1);

use App\Config\Database;
use Dotenv\Dotenv;
use Slim\Factory\AppFactory;
use Slim\Middleware\BodyParsingMiddleware;
use DI\Container;

require __DIR__ . '/../vendor/autoload.php';

// 1. Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->safeLoad();

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
$app->addErrorMiddleware(true, true, true);

// 5. Load routes AFTER setting up the container so controllers resolve properly
require __DIR__ . '/../src/routes.php';

// ==================================================================
// MIDDLEWARE STACK (Ordered by execution: first added runs last)
// ==================================================================

// 6. CORS Middleware with Preflight handling
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

// 7. Add Body Passing Middleware (Runs first to parse JSON bodies)
$app->add(new BodyParsingMiddleware());

$app->run();