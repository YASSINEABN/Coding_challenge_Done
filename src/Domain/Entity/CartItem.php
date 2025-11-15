<?php

declare(strict_types=1);

namespace AbandonedCart\Domain\Entity;

class CartItem
{
    private Product $product;
    private int $quantity;

    public function __construct(Product $product, int $quantity = 1)
    {
        if ($quantity < 1) {
            throw new \InvalidArgumentException('Quantity must be at least 1');
        }

        $this->product = $product;
        $this->quantity = $quantity;
    }

    public function getProduct(): Product
    {
        return $this->product;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function getTotalPrice(): float
    {
        return $this->product->getPrice() * $this->quantity;
    }

    public function toArray(): array
    {
        return [
            'product' => $this->product->toArray(),
            'quantity' => $this->quantity,
            'total_price' => $this->getTotalPrice(),
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            Product::fromArray($data['product']),
            (int)$data['quantity']
        );
    }
}
