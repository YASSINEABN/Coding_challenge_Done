<?php

declare(strict_types=1);

namespace AbandonedCart\Infrastructure\Monitoring;

use AbandonedCart\Infrastructure\Logger\LoggerInterface;
use Predis\Client;

class MetricsCollector
{
    private const METRICS_KEY_PREFIX = 'metrics:';
    private const METRIC_CARTS_CREATED = 'carts:created';
    private const METRIC_CARTS_FINALIZED = 'carts:finalized';
    private const METRIC_REMINDERS_SENT = 'reminders:sent';
    private const METRIC_REMINDERS_FAILED = 'reminders:failed';

    private Client $redis;
    private LoggerInterface $logger;

    public function __construct(Client $redis, LoggerInterface $logger)
    {
        $this->redis = $redis;
        $this->logger = $logger;
    }

    public function incrementCartsCreated(): void
    {
        $this->increment(self::METRIC_CARTS_CREATED);
    }

    public function incrementCartsFinalized(): void
    {
        $this->increment(self::METRIC_CARTS_FINALIZED);
    }

    public function incrementRemindersSent(): void
    {
        $this->increment(self::METRIC_REMINDERS_SENT);
    }

    public function incrementRemindersFailed(): void
    {
        $this->increment(self::METRIC_REMINDERS_FAILED);
    }

    public function getMetrics(): array
    {
        return [
            'carts_created' => (int)$this->get(self::METRIC_CARTS_CREATED),
            'carts_finalized' => (int)$this->get(self::METRIC_CARTS_FINALIZED),
            'reminders_sent' => (int)$this->get(self::METRIC_REMINDERS_SENT),
            'reminders_failed' => (int)$this->get(self::METRIC_REMINDERS_FAILED),
            'timestamp' => time(),
        ];
    }

    public function resetMetrics(): void
    {
        $metrics = [
            self::METRIC_CARTS_CREATED,
            self::METRIC_CARTS_FINALIZED,
            self::METRIC_REMINDERS_SENT,
            self::METRIC_REMINDERS_FAILED,
        ];

        foreach ($metrics as $metric) {
            $this->redis->del([$this->getKey($metric)]);
        }

        $this->logger->info('Metrics reset');
    }

    private function increment(string $metric): void
    {
        $key = $this->getKey($metric);
        $this->redis->incr($key);
    }

    private function get(string $metric): string
    {
        $key = $this->getKey($metric);
        return $this->redis->get($key) ?? '0';
    }

    private function getKey(string $metric): string
    {
        return self::METRICS_KEY_PREFIX . $metric;
    }
}
