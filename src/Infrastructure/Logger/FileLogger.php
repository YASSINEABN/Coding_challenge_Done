<?php

declare(strict_types=1);

namespace AbandonedCart\Infrastructure\Logger;

class FileLogger implements LoggerInterface
{
    private const LEVEL_DEBUG = 0;
    private const LEVEL_INFO = 1;
    private const LEVEL_WARNING = 2;
    private const LEVEL_ERROR = 3;

    private array $levelMap = [
        'debug' => self::LEVEL_DEBUG,
        'info' => self::LEVEL_INFO,
        'warning' => self::LEVEL_WARNING,
        'error' => self::LEVEL_ERROR,
    ];

    private string $logDirectory;
    private int $minLevel;

    public function __construct(string $logDirectory, string $minLevel = 'info')
    {
        $this->logDirectory = $logDirectory;
        $this->minLevel = $this->levelMap[$minLevel] ?? self::LEVEL_INFO;

        if (!is_dir($this->logDirectory)) {
            mkdir($this->logDirectory, 0755, true);
        }
    }

    public function debug(string $message, array $context = []): void
    {
        $this->log('debug', $message, $context);
    }

    public function info(string $message, array $context = []): void
    {
        $this->log('info', $message, $context);
    }

    public function warning(string $message, array $context = []): void
    {
        $this->log('warning', $message, $context);
    }

    public function error(string $message, array $context = []): void
    {
        $this->log('error', $message, $context);
    }

    private function log(string $level, string $message, array $context): void
    {
        if ($this->levelMap[$level] < $this->minLevel) {
            return;
        }

        $timestamp = date('Y-m-d H:i:s');
        $contextStr = !empty($context) ? ' ' . json_encode($context) : '';
        $logMessage = sprintf("[%s] [%s] %s%s\n", $timestamp, strtoupper($level), $message, $contextStr);

        $filename = sprintf('%s/%s.log', $this->logDirectory, $level);
        file_put_contents($filename, $logMessage, FILE_APPEND);
    }
}
