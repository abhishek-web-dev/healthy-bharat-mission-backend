<?php

namespace HBM\Services;

use HBM\Repositories\StoreRepository;
use Exception;

class StoreService {
    private StoreRepository $repository;

    public function __construct() {
        $this->repository = new StoreRepository();
    }

    // --- Products ---

    public function getProducts(array $filters = []): array {
        return $this->repository->getProducts($filters);
    }

    public function getProductDetails(string $identifier): ?array {
        return $this->repository->getProductByIdOrSlug($identifier);
    }

    public function getCategories(): array {
        return $this->repository->getProductCategories();
    }

    // --- Reviews ---

    public function submitReviewWithPhotos(int $productId, string $reviewerName, string $reviewerEmail, int $rating, string $title, string $content, string $variant, array $files): int {
        // Validate product exists
        $product = $this->repository->getProductByIdOrSlug((string)$productId);
        if (!$product) {
            throw new Exception("Product not found.");
        }

        return $this->repository->addReviewWithPhotos(
            $productId,
            $reviewerName,
            $reviewerEmail,
            $rating,
            $title,
            $content,
            $variant,
            'pending',
            $files
        );
    }

    public function getProductReviewsWithStats(string $identifier): ?array {
        $product = $this->repository->getProductByIdOrSlug($identifier);
        if (!$product) {
            return null;
        }

        $reviews = $this->repository->getProductReviews($product['id']);
        $stats = $this->repository->getProductReviewStats($product['id']);
        $allPhotos = $this->repository->getProductPhotos($product['id']);

        return [
            'reviews' => $reviews,
            'stats' => $stats,
            'photos' => $allPhotos
        ];
    }

    // --- Cart ---

    public function getCart(int $userId): array {
        $items = $this->repository->getCartByUserId($userId);
        
        $subtotal = 0;
        foreach ($items as &$item) {
            $item['total_price'] = $item['price'] * $item['quantity'];
            $subtotal += $item['total_price'];
        }

        return [
            'items' => $items,
            'subtotal' => $subtotal,
            'total_items' => count($items)
        ];
    }

    public function addToCart(int $userId, int $productId, int $quantity): array {
        // Validate product
        $product = $this->repository->getProductByIdOrSlug((string)$productId);
        if (!$product) {
            throw new Exception("Product not found or unavailable.");
        }
        
        // Ensure quantity is positive
        if ($quantity <= 0) {
            throw new Exception("Quantity must be at least 1.");
        }

        // Check stock
        if ($product['stock'] < $quantity) {
            throw new Exception("Not enough stock available.");
        }

        // Check if item already in cart
        $existingItem = $this->repository->getCartItem($userId, $productId);
        
        if ($existingItem) {
            $newQuantity = $existingItem['quantity'] + $quantity;
            if ($product['stock'] < $newQuantity) {
                throw new Exception("Not enough stock available to add more.");
            }
            $this->repository->updateCartQuantity($existingItem['id'], $userId, $newQuantity);
        } else {
            $this->repository->addToCart($userId, $productId, $quantity);
        }

        return $this->getCart($userId);
    }

    public function updateCartItemQuantity(int $userId, int $cartItemId, int $quantity): array {
        if ($quantity <= 0) {
            // Remove item if quantity is 0 or less
            $this->repository->removeFromCart($cartItemId, $userId);
        } else {
            // Update quantity (we should really check stock here, but keeping it simple)
            $this->repository->updateCartQuantity($cartItemId, $userId, $quantity);
        }

        return $this->getCart($userId);
    }

    public function removeCartItem(int $userId, int $cartItemId): array {
        $this->repository->removeFromCart($cartItemId, $userId);
        return $this->getCart($userId);
    }
    
    public function getCartCount(int $userId): int {
        return $this->repository->getCartCount($userId);
    }

    // --- Wishlist ---

    public function getWishlist(int $userId): array {
        $items = $this->repository->getWishlistByUserId($userId);
        return [
            'items' => $items,
            'total_items' => count($items)
        ];
    }

    public function toggleWishlist(int $userId, int $productId): array {
        // Validate product
        $product = $this->repository->getProductByIdOrSlug((string)$productId);
        if (!$product) {
            throw new Exception("Product not found or unavailable.");
        }

        $existingItem = $this->repository->getWishlistItem($userId, $productId);
        
        if ($existingItem) {
            $this->repository->removeFromWishlist($userId, $productId);
            $action = 'removed';
        } else {
            $this->repository->addToWishlist($userId, $productId);
            $action = 'added';
        }

        return [
            'action' => $action,
            'wishlist' => $this->getWishlist($userId)
        ];
    }
    
    public function removeFromWishlist(int $userId, int $productId): array {
        $this->repository->removeFromWishlist($userId, $productId);
        return $this->getWishlist($userId);
    }
}
