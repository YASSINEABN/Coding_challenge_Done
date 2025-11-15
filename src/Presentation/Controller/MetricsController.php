<?php

declare(strict_types=1);

namespace AbandonedCart\Presentation\Controller;

use AbandonedCart\Infrastructure\Monitoring\MetricsCollector;
use AbandonedCart\Infrastructure\Logger\LoggerInterface;

class MetricsController
{
    private MetricsCollector $metricsCollector;
    private LoggerInterface $logger;

    public function __construct(
        MetricsCollector $metricsCollector,
        LoggerInterface $logger
    ) {
        $this->metricsCollector = $metricsCollector;
        $this->logger = $logger;
    }

    public function getMetrics(): void
    {
        try {
            $metrics = $this->metricsCollector->getMetrics();
            
            http_response_code(200);
            echo json_encode($metrics, JSON_PRETTY_PRINT);
        } catch (\Exception $e) {
            $this->logger->error('Failed to retrieve metrics', [
                'error' => $e->getMessage(),
            ]);

            http_response_code(500);
            echo json_encode(['error' => 'Failed to retrieve metrics']);
        }
    }
}
