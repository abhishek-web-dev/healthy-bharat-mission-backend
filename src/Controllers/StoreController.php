<?php

namespace HBM\Controllers;

use HBM\Services\StoreService;
use HBM\Helpers\Response;
use Exception;

class StoreController {
    private StoreService $service;

    public function __construct() {
        $this->service = new StoreService();
    }

    // --- Products ---

    public function getProducts(): void {
        try {
            $categorySlug = $_GET['category'] ?? null;
            $filters = [];
            if ($categorySlug) {
                $filters['category_slug'] = $categorySlug;
            }

            $products = $this->service->getProducts($filters);
            Response::success("Products fetched successfully.", $products);
        } catch (Exception $e) {
            error_log("Error in StoreController::getProducts: " . $e->getMessage());
            Response::error("Failed to fetch products: " . $e->getMessage(), 500);
        }
    }

    public function getProductDetails(string $identifier): void {
        try {
            $product = $this->service->getProductDetails($identifier);
            if (!$product) {
                Response::error("Product not found.", 404);
                return;
            }
            Response::success("Product details fetched successfully.", $product);
        } catch (Exception $e) {
            error_log("Error in StoreController::getProductDetails: " . $e->getMessage());
            Response::error("Failed to fetch product details: " . $e->getMessage(), 500);
        }
    }

    public function getCategories(): void {
        try {
            $categories = $this->service->getCategories();
            Response::success("Categories fetched successfully.", $categories);
        } catch (Exception $e) {
            error_log("Error in StoreController::getCategories: " . $e->getMessage());
            Response::error("Failed to fetch categories: " . $e->getMessage(), 500);
        }
    }

    // --- Cart ---
    
    private function getUserId(): int {
        global $authUser;
        return $authUser['id'];
    }

    public function getCart(): void {
        try {
            $userId = $this->getUserId();
            $cart = $this->service->getCart($userId);
            Response::success("Cart fetched successfully.", $cart);
        } catch (Exception $e) {
            error_log("Error in StoreController::getCart: " . $e->getMessage());
            Response::error("Failed to fetch cart: " . $e->getMessage(), 500);
        }
    }
    
    public function getCartCount(): void {
        try {
            $userId = $this->getUserId();
            $count = $this->service->getCartCount($userId);
            Response::success("Cart count fetched successfully.", ['count' => $count]);
        } catch (Exception $e) {
            error_log("Error in StoreController::getCartCount: " . $e->getMessage());
            Response::error("Failed to fetch cart count: " . $e->getMessage(), 500);
        }
    }

    public function addToCart(): void {
        try {
            $userId = $this->getUserId();
            $data = json_decode(file_get_contents('php://input'), true);
            
            $productId = $data['product_id'] ?? null;
            $quantity = $data['quantity'] ?? 1;

            if (!$productId) {
                Response::error("Product ID is required.", 400);
                return;
            }

            $cart = $this->service->addToCart($userId, (int)$productId, (int)$quantity);
            Response::success("Item added to cart.", $cart);
        } catch (Exception $e) {
            error_log("Error in StoreController::addToCart: " . $e->getMessage());
            Response::error($e->getMessage(), 400);
        }
    }

    public function updateCartQuantity(int $cartItemId): void {
        try {
            $userId = $this->getUserId();
            $data = json_decode(file_get_contents('php://input'), true);
            
            $quantity = $data['quantity'] ?? null;

            if ($quantity === null) {
                Response::error("Quantity is required.", 400);
                return;
            }

            $cart = $this->service->updateCartItemQuantity($userId, $cartItemId, (int)$quantity);
            Response::success("Cart updated.", $cart);
        } catch (Exception $e) {
            error_log("Error in StoreController::updateCartQuantity: " . $e->getMessage());
            Response::error("Failed to update cart: " . $e->getMessage(), 400);
        }
    }

    public function removeCartItem(int $cartItemId): void {
        try {
            $userId = $this->getUserId();
            $cart = $this->service->removeCartItem($userId, $cartItemId);
            Response::success("Item removed from cart.", $cart);
        } catch (Exception $e) {
            error_log("Error in StoreController::removeCartItem: " . $e->getMessage());
            Response::error("Failed to remove item: " . $e->getMessage(), 400);
        }
    }

    // --- Wishlist ---

    public function getWishlist(): void {
        try {
            $userId = $this->getUserId();
            $wishlist = $this->service->getWishlist($userId);
            Response::success("Wishlist fetched successfully.", $wishlist);
        } catch (Exception $e) {
            error_log("Error in StoreController::getWishlist: " . $e->getMessage());
            Response::error("Failed to fetch wishlist: " . $e->getMessage(), 500);
        }
    }

    public function toggleWishlist(): void {
        try {
            $userId = $this->getUserId();
            $data = json_decode(file_get_contents('php://input'), true);
            
            $productId = $data['product_id'] ?? null;

            if (!$productId) {
                Response::error("Product ID is required.", 400);
                return;
            }

            $result = $this->service->toggleWishlist($userId, (int)$productId);
            Response::success("Wishlist updated.", $result);
        } catch (Exception $e) {
            error_log("Error in StoreController::toggleWishlist: " . $e->getMessage());
            Response::error($e->getMessage(), 400);
        }
    }
    
    public function removeWishlistItem(int $productId): void {
        try {
            $userId = $this->getUserId();
            $wishlist = $this->service->removeFromWishlist($userId, $productId);
            Response::success("Item removed from wishlist.", $wishlist);
        } catch (Exception $e) {
            error_log("Error in StoreController::removeWishlistItem: " . $e->getMessage());
            Response::error("Failed to remove item: " . $e->getMessage(), 400);
        }
    }
}
