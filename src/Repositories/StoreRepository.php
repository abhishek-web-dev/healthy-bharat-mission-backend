<?php

namespace HBM\Repositories;

use HBM\Core\Database;
use PDO;

class StoreRepository {
    // --- Products ---

    public function getProducts(array $filters = [], int $limit = 100): array {
        $db = Database::getConnection();
        
        $sql = "SELECT p.*, c.name as category_name, c.slug as category_slug 
                FROM products p 
                LEFT JOIN product_categories c ON p.category_id = c.id 
                WHERE p.is_active = 1 AND p.deleted_at IS NULL";
        
        $params = [];

        if (!empty($filters['category_slug'])) {
            $sql .= " AND c.slug = ?";
            $params[] = $filters['category_slug'];
        }

        $sql .= " ORDER BY p.id DESC LIMIT ?";
        $params[] = $limit;

        $stmt = $db->prepare($sql);
        // Bind integer parameter properly for LIMIT
        foreach ($params as $key => $value) {
            $stmt->bindValue($key + 1, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        
        $stmt->execute();
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Fetch primary images for all these products
        if (!empty($products)) {
            $productIds = array_column($products, 'id');
            $placeholders = implode(',', array_fill(0, count($productIds), '?'));
            $imgSql = "SELECT product_id, image_url FROM product_images WHERE product_id IN ($placeholders) AND is_primary = 1";
            $imgStmt = $db->prepare($imgSql);
            $imgStmt->execute($productIds);
            $primaryImages = $imgStmt->fetchAll(PDO::FETCH_ASSOC);

            // Map images to products
            $imageMap = [];
            foreach ($primaryImages as $img) {
                $imageMap[$img['product_id']] = $img['image_url'];
            }

            foreach ($products as &$product) {
                $product['primary_image'] = $imageMap[$product['id']] ?? $product['thumbnail_url'];
            }
        }

        return $products;
    }

    public function getProductByIdOrSlug(string $identifier): ?array {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT p.*, c.name as category_name, c.slug as category_slug 
            FROM products p 
            LEFT JOIN product_categories c ON p.category_id = c.id 
            WHERE (p.id = ? OR p.slug = ?) AND p.is_active = 1 AND p.deleted_at IS NULL
            LIMIT 1
        ");
        $stmt->execute([$identifier, $identifier]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($product) {
            // Fetch all images for this product
            $imgStmt = $db->prepare("SELECT image_url, is_primary FROM product_images WHERE product_id = ? ORDER BY sort_order ASC, is_primary DESC");
            $imgStmt->execute([$product['id']]);
            $product['images'] = $imgStmt->fetchAll(PDO::FETCH_ASSOC);
        }

        return $product ?: null;
    }

    public function getProductCategories(): array {
        $db = Database::getConnection();
        $stmt = $db->query("SELECT * FROM product_categories ORDER BY name ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // --- Cart ---

    public function getCartByUserId(int $userId): array {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT c.id as cart_item_id, c.quantity, p.*, 
                   cat.name as category_name, cat.slug as category_slug,
                   (SELECT image_url FROM product_images pi WHERE pi.product_id = p.id AND pi.is_primary = 1 LIMIT 1) as primary_image
            FROM cart_items c
            JOIN products p ON c.product_id = p.id
            LEFT JOIN product_categories cat ON p.category_id = cat.id
            WHERE c.user_id = ? AND p.is_active = 1 AND p.deleted_at IS NULL
            ORDER BY c.created_at DESC
        ");
        $stmt->execute([$userId]);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Fallback to thumbnail_url if no primary image found in subquery
        foreach ($items as &$item) {
            if (empty($item['primary_image'])) {
                $item['primary_image'] = $item['thumbnail_url'];
            }
        }
        
        return $items;
    }

    public function getCartItem(int $userId, int $productId): ?array {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM cart_items WHERE user_id = ? AND product_id = ? LIMIT 1");
        $stmt->execute([$userId, $productId]);
        $item = $stmt->fetch(PDO::FETCH_ASSOC);
        return $item ?: null;
    }

    public function addToCart(int $userId, int $productId, int $quantity): int {
        $db = Database::getConnection();
        $stmt = $db->prepare("INSERT INTO cart_items (user_id, product_id, quantity) VALUES (?, ?, ?)");
        $stmt->execute([$userId, $productId, $quantity]);
        return (int) $db->lastInsertId();
    }

    public function updateCartQuantity(int $cartItemId, int $userId, int $quantity): bool {
        $db = Database::getConnection();
        $stmt = $db->prepare("UPDATE cart_items SET quantity = ? WHERE id = ? AND user_id = ?");
        return $stmt->execute([$quantity, $cartItemId, $userId]);
    }

    public function removeFromCart(int $cartItemId, int $userId): bool {
        $db = Database::getConnection();
        $stmt = $db->prepare("DELETE FROM cart_items WHERE id = ? AND user_id = ?");
        return $stmt->execute([$cartItemId, $userId]);
    }

    public function getCartCount(int $userId): int {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT SUM(c.quantity) as count 
            FROM cart_items c
            JOIN products p ON c.product_id = p.id
            WHERE c.user_id = ? AND p.is_active = 1 AND p.deleted_at IS NULL
        ");
        $stmt->execute([$userId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int) ($result['count'] ?? 0);
    }

    // --- Wishlist ---

    public function getWishlistByUserId(int $userId): array {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT w.id as wishlist_item_id, p.*, 
                   cat.name as category_name, cat.slug as category_slug,
                   (SELECT image_url FROM product_images pi WHERE pi.product_id = p.id AND pi.is_primary = 1 LIMIT 1) as primary_image
            FROM wishlist_items w
            JOIN products p ON w.product_id = p.id
            LEFT JOIN product_categories cat ON p.category_id = cat.id
            WHERE w.user_id = ? AND p.is_active = 1 AND p.deleted_at IS NULL
            ORDER BY w.created_at DESC
        ");
        $stmt->execute([$userId]);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Fallback to thumbnail_url if no primary image found in subquery
        foreach ($items as &$item) {
            if (empty($item['primary_image'])) {
                $item['primary_image'] = $item['thumbnail_url'];
            }
        }
        
        return $items;
    }

    public function getWishlistItem(int $userId, int $productId): ?array {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM wishlist_items WHERE user_id = ? AND product_id = ? LIMIT 1");
        $stmt->execute([$userId, $productId]);
        $item = $stmt->fetch(PDO::FETCH_ASSOC);
        return $item ?: null;
    }

    public function addToWishlist(int $userId, int $productId): int {
        $db = Database::getConnection();
        $stmt = $db->prepare("INSERT INTO wishlist_items (user_id, product_id) VALUES (?, ?)");
        $stmt->execute([$userId, $productId]);
        return (int) $db->lastInsertId();
    }

    public function removeFromWishlist(int $userId, int $productId): bool {
        $db = Database::getConnection();
        $stmt = $db->prepare("DELETE FROM wishlist_items WHERE user_id = ? AND product_id = ?");
        return $stmt->execute([$userId, $productId]);
    }
}
