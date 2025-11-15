<?php

declare(strict_types=1);

namespace AbandonedCart\Presentation\Controller;

use AbandonedCart\Domain\Entity\Cart;
use AbandonedCart\Domain\Entity\CartItem;
use AbandonedCart\Domain\Entity\Product;
use AbandonedCart\Domain\Repository\CartRepositoryInterface;
use AbandonedCart\Infrastructure\Logger\LoggerInterface;

class CartController
{
    private CartRepositoryInterface $cartRepository;
    private LoggerInterface $logger;

    public function __construct(
        CartRepositoryInterface $cartRepository,
        LoggerInterface $logger
    ) {
        $this->cartRepository = $cartRepository;
        $this->logger = $logger;
    }

    public function addProduct(): void
    {
        try {
            $input = json_decode(file_get_contents('php://input'), true);

            if (!$this->validateAddProductInput($input)) {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid input']);
                return;
            }

            $cartId = $input['cart_id'] ?? $this->generateCartId();
            $cart = $this->cartRepository->findById($cartId);

            if ($cart === null) {
                $cart = new Cart($cartId, $input['customer_email']);
            }

            if ($cart->isFinalized()) {
                http_response_code(400);
                echo json_encode(['error' => 'Cart is already finalized']);
                return;
            }

            $product = new Product(
                $input['product_id'],
                $input['product_name'],
                (float)$input['product_price']
            );

            $quantity = (int)($input['quantity'] ?? 1);
            $cartItem = new CartItem($product, $quantity);

            $cart->addItem($cartItem);
            $this->cartRepository->save($cart);

            $this->logger->info('Product added to cart', [
                'cart_id' => $cart->getId(),
                'product_id' => $product->getId(),
                'quantity' => $quantity,
            ]);

            http_response_code(200);
            echo json_encode([
                'success' => true,
                'cart' => $cart->toArray(),
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to add product to cart', [
                'error' => $e->getMessage(),
            ]);

            http_response_code(500);
            echo json_encode(['error' => 'Failed to add product to cart']);
        }
    }

    public function finalizeCart(): void
    {
        try {
            $input = json_decode(file_get_contents('php://input'), true);

            if (!isset($input['cart_id'])) {
                http_response_code(400);
                echo json_encode(['error' => 'cart_id is required']);
                return;
            }

            $cart = $this->cartRepository->findById($input['cart_id']);

            if ($cart === null) {
                http_response_code(404);
                echo json_encode(['error' => 'Cart not found']);
                return;
            }

            $cart->finalize();
            $this->cartRepository->save($cart);

            $this->logger->info('Cart finalized', [
                'cart_id' => $cart->getId(),
                'total_amount' => $cart->getTotalAmount(),
            ]);

            http_response_code(200);
            echo json_encode([
                'success' => true,
                'cart' => $cart->toArray(),
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to finalize cart', [
                'error' => $e->getMessage(),
            ]);

            http_response_code(500);
            echo json_encode(['error' => 'Failed to finalize cart']);
        }
    }

    public function getCart(string $cartId): void
    {
        try {
            $cart = $this->cartRepository->findById($cartId);

            if ($cart === null) {
                http_response_code(404);
                echo json_encode(['error' => 'Cart not found']);
                return;
            }

            http_response_code(200);
            echo json_encode($cart->toArray());
        } catch (\Exception $e) {
            $this->logger->error('Failed to retrieve cart', [
                'cart_id' => $cartId,
                'error' => $e->getMessage(),
            ]);

            http_response_code(500);
            echo json_encode(['error' => 'Failed to retrieve cart']);
        }
    }

    public function finalizeCartFromLink(): void
    {
        try {
            $cartId = $_GET['cart_id'] ?? null;

            if (!$cartId) {
                http_response_code(400);
                echo json_encode(['error' => 'cart_id parameter is required']);
                return;
            }

            $cart = $this->cartRepository->findById($cartId);

            if ($cart === null) {
                http_response_code(404);
                echo json_encode(['error' => 'Cart not found']);
                return;
            }

            if ($cart->isFinalized()) {
                http_response_code(200);
                echo json_encode([
                    'success' => true,
                    'message' => 'Cart was already finalized',
                    'cart' => $cart->toArray(),
                ]);
                return;
            }

            $cart->finalize();
            $this->cartRepository->save($cart);

            $this->logger->info('Cart finalized from email link', [
                'cart_id' => $cart->getId(),
                'total_amount' => $cart->getTotalAmount(),
            ]);

            http_response_code(200);
            echo json_encode([
                'success' => true,
                'message' => 'Thank you! Your order has been completed.',
                'cart' => $cart->toArray(),
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to finalize cart from link', [
                'error' => $e->getMessage(),
            ]);

            http_response_code(500);
            echo json_encode(['error' => 'Failed to finalize cart']);
        }
    }

    private function validateAddProductInput(?array $input): bool
    {
        if ($input === null) {
            return false;
        }

        $requiredFields = ['customer_email', 'product_id', 'product_name', 'product_price'];

        foreach ($requiredFields as $field) {
            if (!isset($input[$field]) || empty($input[$field])) {
                return false;
            }
        }

        if (!filter_var($input['customer_email'], FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        if (!is_numeric($input['product_price']) || (float)$input['product_price'] <= 0) {
            return false;
        }

        return true;
    }

    private function generateCartId(): string
    {
        return uniqid('cart_', true);
    }
}
