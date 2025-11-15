<?php

declare(strict_types=1);

namespace AbandonedCart\Application\Service;

use AbandonedCart\Domain\Entity\Cart;
use AbandonedCart\Domain\Repository\CartRepositoryInterface;
use AbandonedCart\Infrastructure\Email\EmailServiceInterface;
use AbandonedCart\Infrastructure\Logger\LoggerInterface;

class ReminderService
{
    private const MAX_REMINDERS = 3;

    private CartRepositoryInterface $cartRepository;
    private EmailServiceInterface $emailService;
    private LoggerInterface $logger;
    private array $reminderIntervals;

    public function __construct(
        CartRepositoryInterface $cartRepository,
        EmailServiceInterface $emailService,
        LoggerInterface $logger,
        array $reminderIntervals
    ) {
        $this->cartRepository = $cartRepository;
        $this->emailService = $emailService;
        $this->logger = $logger;
        $this->reminderIntervals = $reminderIntervals;
    }

    public function processAbandonedCarts(): int
    {
        $abandonedCarts = $this->cartRepository->findAbandonedCarts();
        $processedCount = 0;

        $this->logger->info('Processing abandoned carts', [
            'total_carts' => count($abandonedCarts),
        ]);

        foreach ($abandonedCarts as $cart) {
            if ($this->shouldSendReminder($cart)) {
                if ($this->sendReminder($cart)) {
                    $processedCount++;
                }
            }
        }

        $this->logger->info('Finished processing abandoned carts', [
            'processed' => $processedCount,
        ]);

        return $processedCount;
    }

    private function shouldSendReminder(Cart $cart): bool
    {
        if ($cart->isFinalized()) {
            return false;
        }

        if ($cart->getRemindersSent() >= self::MAX_REMINDERS) {
            return false;
        }

        $reminderNumber = $cart->getRemindersSent() + 1;
        $requiredInterval = $this->getReminderInterval($reminderNumber);
        
        if ($requiredInterval === null) {
            return false;
        }

        $referenceTime = $cart->getLastReminderAt() ?? $cart->getCreatedAt();
        $timeSinceReference = time() - $referenceTime;
        $requiredSeconds = $requiredInterval * 3600;

        return $timeSinceReference >= $requiredSeconds;
    }

    private function sendReminder(Cart $cart): bool
    {
        $reminderNumber = $cart->getRemindersSent() + 1;

        $this->logger->info('Attempting to send reminder', [
            'cart_id' => $cart->getId(),
            'reminder_number' => $reminderNumber,
        ]);

        $success = $this->emailService->sendReminderEmail($cart, $reminderNumber);

        if ($success) {
            $cart->markReminderSent();
            $this->cartRepository->save($cart);

            $this->logger->info('Reminder sent successfully', [
                'cart_id' => $cart->getId(),
                'reminder_number' => $reminderNumber,
            ]);
        } else {
            $this->logger->error('Failed to send reminder', [
                'cart_id' => $cart->getId(),
                'reminder_number' => $reminderNumber,
            ]);
        }

        return $success;
    }

    private function getReminderInterval(int $reminderNumber): ?int
    {
        $intervals = [
            1 => $this->reminderIntervals['first_interval_hours'],
            2 => $this->reminderIntervals['second_interval_hours'],
            3 => $this->reminderIntervals['third_interval_hours'],
        ];

        return $intervals[$reminderNumber] ?? null;
    }
}
