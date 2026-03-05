<?php
declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use App\Config\Env;
use App\Middleware\ErrorHandler;
use App\Routes\Router;

Env::load(__DIR__ . '/../.env');

header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: no-referrer');
header('X-XSS-Protection: 0');

$frontendUrl = $_ENV['FRONTEND_URL'] ?? 'http://localhost:5173';
header("Access-Control-Allow-Origin: {$frontendUrl}");
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if (($_ENV['NODE_ENV'] ?? 'development') === 'development') {
    error_log(sprintf('[%s] %s %s', date('c'), $_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']));
}

$windowMs = (int)($_ENV['RATE_LIMIT_WINDOW_MS'] ?? (15 * 60 * 1000));
$maxRequests = (int)($_ENV['RATE_LIMIT_MAX_REQUESTS'] ?? 1000);
\App\Middleware\RateLimiter::enforce('/api/', $windowMs, $maxRequests);

try {
    $router = new Router();
    $router->registerRoutes();
    $router->dispatch();
} catch (Throwable $e) {
    ErrorHandler::handle($e);
}
