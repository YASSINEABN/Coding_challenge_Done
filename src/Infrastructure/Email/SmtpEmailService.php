<?php

declare(strict_types=1);

namespace AbandonedCart\Infrastructure\Email;

use AbandonedCart\Domain\Entity\Cart;
use AbandonedCart\Infrastructure\Logger\LoggerInterface;

class SmtpEmailService implements EmailServiceInterface
{
    private array $config;
    private LoggerInterface $logger;

    public function __construct(array $config, LoggerInterface $logger)
    {
        $this->config = $config;
        $this->logger = $logger;
    }

    public function sendReminderEmail(Cart $cart, int $reminderNumber): bool
    {
        try {
            $subject = $this->getSubject($reminderNumber);
            $body = $this->getBody($cart, $reminderNumber);
            
            $headers = [
                'From: ' . $this->config['from_name'] . ' <' . $this->config['from'] . '>',
                'Reply-To: ' . $this->config['from'],
                'X-Mailer: PHP/' . phpversion(),
                'MIME-Version: 1.0',
                'Content-Type: text/html; charset=UTF-8',
            ];

            $success = mail(
                $cart->getCustomerEmail(),
                $subject,
                $body,
                implode("\r\n", $headers)
            );

            if ($success) {
                $this->logger->info('Reminder email sent', [
                    'cart_id' => $cart->getId(),
                    'customer_email' => $cart->getCustomerEmail(),
                    'reminder_number' => $reminderNumber,
                ]);
            } else {
                $this->logger->error('Failed to send reminder email', [
                    'cart_id' => $cart->getId(),
                    'reminder_number' => $reminderNumber,
                ]);
            }

            return $success;
        } catch (\Exception $e) {
            $this->logger->error('Exception while sending email', [
                'cart_id' => $cart->getId(),
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    private function getSubject(int $reminderNumber): string
    {
        $subjects = [
            1 => 'You left items in your cart!',
            2 => 'Still interested? Your cart is waiting',
            3 => 'Last chance! Complete your order',
        ];

        return $subjects[$reminderNumber] ?? 'Cart reminder';
    }

    private function getBody(Cart $cart, int $reminderNumber): string
    {
        $items = $cart->getItems();
        $itemsHtml = '';

        foreach ($items as $item) {
            $product = $item->getProduct();
            $itemsHtml .= sprintf(
                '<li>%s - Quantity: %d - $%.2f</li>',
                htmlspecialchars($product->getName()),
                $item->getQuantity(),
                $item->getTotalPrice()
            );
        }

        $finalizeUrl = sprintf(
            'http://%s/api/cart/finalize?cart_id=%s',
            $_SERVER['HTTP_HOST'] ?? 'localhost',
            $cart->getId()
        );

        $messages = [
            1 => 'You have items waiting in your cart. Complete your purchase now!',
            2 => 'Your cart is still waiting for you. Don\'t miss out on these great products!',
            3 => 'This is your last reminder. Complete your order before these items are gone!',
        ];

        $message = $messages[$reminderNumber] ?? 'Complete your order.';

        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Cart Reminder</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <h2>Reminder #{$reminderNumber}: Complete Your Order</h2>
        <p>{$message}</p>
        
        <h3>Your Cart Items:</h3>
        <ul>
            {$itemsHtml}
        </ul>
        
        <p><strong>Total: \${$cart->getTotalAmount()}</strong></p>
        
        <p>
            <a href="{$finalizeUrl}" 
               style="display: inline-block; padding: 10px 20px; background-color: #007bff; 
                      color: white; text-decoration: none; border-radius: 5px;">
                Complete Your Order
            </a>
        </p>
        
        <p style="color: #666; font-size: 12px; margin-top: 30px;">
            If you have already completed your order, please ignore this email.
        </p>
    </div>
</body>
</html>
HTML;
    }
}
