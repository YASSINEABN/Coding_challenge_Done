#!/usr/bin/env php
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

$logger = new \AbandonedCart\Infrastructure\Logger\FileLogger(
    __DIR__ . '/../logs',
    $config['app']['log_level']
);

$redisClient = new \Predis\Client([
    'scheme' => 'tcp',
    'host' => $config['redis']['host'],
    'port' => $config['redis']['port'],
    'password' => $config['redis']['password'],
    'database' => $config['redis']['database'],
]);

$cartRepository = new \AbandonedCart\Infrastructure\Repository\RedisCartRepository(
    $redisClient,
    $logger
);

$emailService = new \AbandonedCart\Infrastructure\Email\SmtpEmailService(
    $config['email'],
    $logger
);

$reminderService = new \AbandonedCart\Application\Service\ReminderService(
    $cartRepository,
    $emailService,
    $logger,
    $config['reminders']
);

$logger->info('Reminder worker started');

$sleepSeconds = 60;

while (true) {
    try {
        $processedCount = $reminderService->processAbandonedCarts();
        
        if ($processedCount > 0) {
            $logger->info('Worker cycle completed', ['processed' => $processedCount]);
        }
    } catch (\Exception $e) {
        $logger->error('Worker cycle failed', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);
    }
    
    sleep($sleepSeconds);
}
