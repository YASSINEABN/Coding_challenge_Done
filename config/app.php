<?php

/**
 * Application configuration
 * 
 * Loads environment variables and provides centralized configuration access
 */

return [
    'redis' => [
        'host' => getenv('REDIS_HOST') ?: '127.0.0.1',
        'port' => (int)(getenv('REDIS_PORT') ?: 6379),
        'password' => getenv('REDIS_PASSWORD') ?: null,
        'database' => (int)(getenv('REDIS_DATABASE') ?: 0),
    ],
    
    'email' => [
        'from' => getenv('EMAIL_FROM') ?: 'noreply@example.com',
        'from_name' => getenv('EMAIL_FROM_NAME') ?: 'Shop',
        'smtp' => [
            'host' => getenv('SMTP_HOST') ?: 'localhost',
            'port' => (int)(getenv('SMTP_PORT') ?: 1025),
            'username' => getenv('SMTP_USERNAME') ?: '',
            'password' => getenv('SMTP_PASSWORD') ?: '',
        ],
    ],
    
    'reminders' => [
        'first_interval_hours' => (int)(getenv('FIRST_REMINDER_HOURS') ?: 24),
        'second_interval_hours' => (int)(getenv('SECOND_REMINDER_HOURS') ?: 48),
        'third_interval_hours' => (int)(getenv('THIRD_REMINDER_HOURS') ?: 72),
    ],
    
    'app' => [
        'env' => getenv('APP_ENV') ?: 'production',
        'debug' => filter_var(getenv('APP_DEBUG'), FILTER_VALIDATE_BOOLEAN),
        'log_level' => getenv('LOG_LEVEL') ?: 'info',
    ],
];
