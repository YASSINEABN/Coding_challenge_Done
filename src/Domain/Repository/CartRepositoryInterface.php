<?php

declare(strict_types=1);

namespace AbandonedCart\Domain\Repository;

use AbandonedCart\Domain\Entity\Cart;

interface CartRepositoryInterface
{
    public function save(Cart $cart): void;

    public function findById(string $id): ?Cart;

    public function findAbandonedCarts(): array;

    public function delete(string $id): void;
}
