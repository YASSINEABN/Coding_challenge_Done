<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

if (file_exists(__DIR__ . '/../.env')) {
    $lines = file(__DIR__ . '/../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        
        [$name, $value] = explode('=', $line, 2);
        $_ENV[trim($name)] = trim($value);
        putenv(trim($name) . '=' . trim($value));
    }
}

$config = require __DIR__ . '/../config/app.php';

$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$requestMethod = $_SERVER['REQUEST_METHOD'];
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($requestMethod === 'OPTIONS') {
    http_response_code(200);
    exit;
}

header('Content-Type: application/json');

try {
    $redisClient = new \Predis\Client([
        'scheme' => 'tcp',
        'host' => $config['redis']['host'],
        'port' => $config['redis']['port'],
        'password' => $config['redis']['password'],
        'database' => $config['redis']['database'],
    ]);
    
    $logger = new \AbandonedCart\Infrastructure\Logger\FileLogger(
        __DIR__ . '/../logs',
        $config['app']['log_level']
    );
    
    $cartRepository = new \AbandonedCart\Infrastructure\Repository\RedisCartRepository(
        $redisClient,
        $logger
    );
    
    $emailService = new \AbandonedCart\Infrastructure\Email\SmtpEmailService(
        $config['email'],
        $logger
    );
    
    $metricsCollector = new \AbandonedCart\Infrastructure\Monitoring\MetricsCollector(
        $redisClient,
        $logger
    );
    
    // Route handling
    if ($requestUri === '/api/cart/add' && $requestMethod === 'POST') {
        $controller = new \AbandonedCart\Presentation\Controller\CartController(
            $cartRepository,
            $logger
        );
        $controller->addProduct();
    } elseif ($requestUri === '/api/cart/finalize' && $requestMethod === 'POST') {
        $controller = new \AbandonedCart\Presentation\Controller\CartController(
            $cartRepository,
            $logger
        );
        $controller->finalizeCart();
    } elseif (preg_match('#^/api/cart/([a-zA-Z0-9\-]+)$#', $requestUri, $matches) && $requestMethod === 'GET') {
        $controller = new \AbandonedCart\Presentation\Controller\CartController(
            $cartRepository,
            $logger
        );
        $controller->getCart($matches[1]);
    } elseif ($requestUri === '/api/metrics' && $requestMethod === 'GET') {
        $controller = new \AbandonedCart\Presentation\Controller\MetricsController(
            $metricsCollector,
            $logger
        );
        $controller->getMetrics();
    } elseif ($requestUri === '/health' && $requestMethod === 'GET') {
        echo json_encode([
            'status' => 'healthy',
            'timestamp' => time(),
            'redis' => $redisClient->ping() ? 'connected' : 'disconnected',
        ]);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Not found']);
    }
} catch (\Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Internal server error',
        'message' => $config['app']['debug'] ? $e->getMessage() : 'An error occurred',
    ]);
    
    if (isset($logger)) {
        $logger->error('Request failed: ' . $e->getMessage(), [
            'exception' => get_class($e),
            'trace' => $e->getTraceAsString(),
        ]);
    }
}
