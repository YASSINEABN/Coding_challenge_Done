<?php

declare(strict_types=1);

namespace AbandonedCart\Infrastructure\Email;

use AbandonedCart\Domain\Entity\Cart;

interface EmailServiceInterface
{
    public function sendReminderEmail(Cart $cart, int $reminderNumber): bool;
}
