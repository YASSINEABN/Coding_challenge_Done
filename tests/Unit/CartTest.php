<?php

declare(strict_types=1);

namespace AbandonedCart\Tests\Unit;

use AbandonedCart\Domain\Entity\Cart;
use AbandonedCart\Domain\Entity\CartItem;
use AbandonedCart\Domain\Entity\Product;
use PHPUnit\Framework\TestCase;

class CartTest extends TestCase
{
    public function testCanCreateCart(): void
    {
        $cart = new Cart('cart-123', 'customer@example.com');
        
        $this->assertEquals('cart-123', $cart->getId());
        $this->assertEquals('customer@example.com', $cart->getCustomerEmail());
        $this->assertFalse($cart->isFinalized());
        $this->assertEquals(0, $cart->getRemindersSent());
    }

    public function testCanAddItemToCart(): void
    {
        $cart = new Cart('cart-123', 'customer@example.com');
        $product = new Product('prod-1', 'Test Product', 19.99);
        $item = new CartItem($product, 2);
        
        $cart->addItem($item);
        
        $items = $cart->getItems();
        $this->assertCount(1, $items);
        $this->assertEquals(39.98, $cart->getTotalAmount());
    }

    public function testCannotAddItemToFinalizedCart(): void
    {
        $this->expectException(\RuntimeException::class);
        
        $cart = new Cart('cart-123', 'customer@example.com');
        $product = new Product('prod-1', 'Test Product', 19.99);
        $item = new CartItem($product, 1);
        
        $cart->addItem($item);
        $cart->finalize();
        $cart->addItem($item);
    }

    public function testCanFinalizeCart(): void
    {
        $cart = new Cart('cart-123', 'customer@example.com');
        $product = new Product('prod-1', 'Test Product', 19.99);
        $item = new CartItem($product, 1);
        
        $cart->addItem($item);
        $cart->finalize();
        
        $this->assertTrue($cart->isFinalized());
    }

    public function testCannotFinalizeEmptyCart(): void
    {
        $this->expectException(\RuntimeException::class);
        
        $cart = new Cart('cart-123', 'customer@example.com');
        $cart->finalize();
    }

    public function testMarkReminderSent(): void
    {
        $cart = new Cart('cart-123', 'customer@example.com');
        
        $this->assertEquals(0, $cart->getRemindersSent());
        $this->assertNull($cart->getLastReminderAt());
        
        $cart->markReminderSent();
        
        $this->assertEquals(1, $cart->getRemindersSent());
        $this->assertNotNull($cart->getLastReminderAt());
    }

    public function testCartToArray(): void
    {
        $cart = new Cart('cart-123', 'customer@example.com');
        $product = new Product('prod-1', 'Test Product', 19.99);
        $item = new CartItem($product, 2);
        $cart->addItem($item);
        
        $array = $cart->toArray();
        
        $this->assertIsArray($array);
        $this->assertEquals('cart-123', $array['id']);
        $this->assertEquals('customer@example.com', $array['customer_email']);
        $this->assertCount(1, $array['items']);
        $this->assertEquals(39.98, $array['total_amount']);
    }
}
