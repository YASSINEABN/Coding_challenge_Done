<?php

declare(strict_types=1);

namespace AbandonedCart\Infrastructure\Repository;

use AbandonedCart\Domain\Entity\Cart;
use AbandonedCart\Domain\Repository\CartRepositoryInterface;
use AbandonedCart\Infrastructure\Logger\LoggerInterface;
use Predis\Client;

class RedisCartRepository implements CartRepositoryInterface
{
    private const KEY_PREFIX = 'cart:';
    private const INDEX_ABANDONED = 'carts:abandoned';

    private Client $redis;
    private LoggerInterface $logger;

    public function __construct(Client $redis, LoggerInterface $logger)
    {
        $this->redis = $redis;
        $this->logger = $logger;
    }

    public function save(Cart $cart): void
    {
        $key = $this->getKey($cart->getId());
        $data = json_encode($cart->toArray());

        $this->redis->set($key, $data);

        if (!$cart->isFinalized()) {
            $this->redis->zadd(self::INDEX_ABANDONED, [$cart->getId() => $cart->getCreatedAt()]);
        } else {
            $this->redis->zrem(self::INDEX_ABANDONED, $cart->getId());
        }

        $this->logger->info('Cart saved', [
            'cart_id' => $cart->getId(),
            'finalized' => $cart->isFinalized(),
        ]);
    }

    public function findById(string $id): ?Cart
    {
        $key = $this->getKey($id);
        $data = $this->redis->get($key);

        if ($data === null) {
            $this->logger->debug('Cart not found', ['cart_id' => $id]);
            return null;
        }

        $cartData = json_decode($data, true);
        return Cart::fromArray($cartData);
    }

    public function findAbandonedCarts(): array
    {
        $cartIds = $this->redis->zrange(self::INDEX_ABANDONED, 0, -1);
        $carts = [];

        foreach ($cartIds as $cartId) {
            $cart = $this->findById($cartId);
            if ($cart !== null && !$cart->isFinalized()) {
                $carts[] = $cart;
            }
        }

        $this->logger->debug('Found abandoned carts', ['count' => count($carts)]);
        return $carts;
    }

    public function delete(string $id): void
    {
        $key = $this->getKey($id);
        $this->redis->del([$key]);
        $this->redis->zrem(self::INDEX_ABANDONED, $id);

        $this->logger->info('Cart deleted', ['cart_id' => $id]);
    }

    private function getKey(string $id): string
    {
        return self::KEY_PREFIX . $id;
    }
}
