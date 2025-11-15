<?php

declare(strict_types=1);

namespace AbandonedCart\Domain\Entity;

class Cart
{
    private string $id;
    private string $customerEmail;
    private array $items = [];
    private int $createdAt;
    private bool $finalized = false;
    private int $remindersSent = 0;
    private ?int $lastReminderAt = null;

    public function __construct(
        string $id,
        string $customerEmail,
        int $createdAt = null
    ) {
        $this->id = $id;
        $this->customerEmail = $customerEmail;
        $this->createdAt = $createdAt ?? time();
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getCustomerEmail(): string
    {
        return $this->customerEmail;
    }

    public function getItems(): array
    {
        return $this->items;
    }

    public function getCreatedAt(): int
    {
        return $this->createdAt;
    }

    public function isFinalized(): bool
    {
        return $this->finalized;
    }

    public function getRemindersSent(): int
    {
        return $this->remindersSent;
    }

    public function getLastReminderAt(): ?int
    {
        return $this->lastReminderAt;
    }

    public function addItem(CartItem $item): void
    {
        if ($this->finalized) {
            throw new \RuntimeException('Cannot add items to a finalized cart');
        }

        $productId = $item->getProduct()->getId();
        
        if (isset($this->items[$productId])) {
            $existingItem = $this->items[$productId];
            $newQuantity = $existingItem->getQuantity() + $item->getQuantity();
            $this->items[$productId] = new CartItem($item->getProduct(), $newQuantity);
        } else {
            $this->items[$productId] = $item;
        }
    }

    public function finalize(): void
    {
        if ($this->finalized) {
            throw new \RuntimeException('Cart is already finalized');
        }

        if (empty($this->items)) {
            throw new \RuntimeException('Cannot finalize an empty cart');
        }

        $this->finalized = true;
    }

    public function markReminderSent(): void
    {
        $this->remindersSent++;
        $this->lastReminderAt = time();
    }

    public function getTotalAmount(): float
    {
        $total = 0.0;
        foreach ($this->items as $item) {
            $total += $item->getTotalPrice();
        }
        return $total;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'customer_email' => $this->customerEmail,
            'items' => array_map(fn($item) => $item->toArray(), array_values($this->items)),
            'created_at' => $this->createdAt,
            'finalized' => $this->finalized,
            'reminders_sent' => $this->remindersSent,
            'last_reminder_at' => $this->lastReminderAt,
            'total_amount' => $this->getTotalAmount(),
        ];
    }

    public static function fromArray(array $data): self
    {
        $cart = new self(
            $data['id'],
            $data['customer_email'],
            $data['created_at']
        );

        foreach ($data['items'] as $itemData) {
            $cart->items[$itemData['product']['id']] = CartItem::fromArray($itemData);
        }

        $cart->finalized = $data['finalized'];
        $cart->remindersSent = $data['reminders_sent'];
        $cart->lastReminderAt = $data['last_reminder_at'];

        return $cart;
    }
}
