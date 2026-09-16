<?php

namespace HBM\Repositories;

use HBM\Core\Database;
use PDO;

class AdminProductRepository {
    private function getDb(): PDO {
        return Database::getConnection();
    }
    public function getAllProducts(int $limit = 100, int $offset = 0): array {
        $stmt = $this->getDb()->prepare("
            SELECT p.*, c.name as category_name 
            FROM products p 
            LEFT JOIN product_categories c ON p.category_id = c.id 
            ORDER BY p.id DESC 
            LIMIT :limit OFFSET :offset
        ");
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Attach images
        $stmtImg = $this->getDb()->prepare("SELECT product_id, image_url, is_primary FROM product_images ORDER BY sort_order ASC, is_primary DESC");
        $stmtImg->execute();
        $images = $stmtImg->fetchAll(PDO::FETCH_ASSOC);
        
        $imagesByProduct = [];
        foreach ($images as $img) {
            $imagesByProduct[$img['product_id']][] = $img;
        }
        
        foreach ($products as &$product) {
            $product['images'] = $imagesByProduct[$product['id']] ?? [];
        }

        return $products;
    }


    public function createProduct(array $data): int {
        $stmt = $this->getDb()->prepare("
            INSERT INTO products (
                category_id, name, slug, description, price, mrp, stock, is_active, is_digital, 
                thumbnail_url
            ) VALUES (
                :category_id, :name, :slug, :description, :price, :mrp, :stock, :is_active, :is_digital,
                :thumbnail_url
            )
        ");

        $stmt->execute([
            'category_id' => $data['category_id'],
            'name' => $data['name'],
            'slug' => $data['slug'],
            'description' => $data['description'] ?? null,
            'price' => $data['price'],
            'mrp' => $data['mrp'] ?? null,
            'stock' => $data['stock'] ?? 0,
            'is_active' => $data['is_active'] ?? 1,
            'is_digital' => $data['is_digital'] ?? 0,
            'thumbnail_url' => $data['thumbnail_url'] ?? null
        ]);

        return (int)$this->getDb()->lastInsertId();
    }

    public function updateProduct(int $id, array $data): void {
        $fields = [];
        $params = ['id' => $id];

        $allowedFields = ['category_id', 'name', 'slug', 'description', 'price', 'mrp', 'stock', 'is_active', 'is_digital', 'thumbnail_url'];
        
        foreach ($allowedFields as $field) {
            if (array_key_exists($field, $data)) {
                $fields[] = "$field = :$field";
                $params[$field] = $data[$field];
            }
        }

        if (empty($fields)) return;

        $query = "UPDATE products SET " . implode(', ', $fields) . " WHERE id = :id";
        $stmt = $this->getDb()->prepare($query);
        $stmt->execute($params);
    }

    public function saveProductImages(int $productId, array $images): void {
        $stmt = $this->getDb()->prepare("DELETE FROM product_images WHERE product_id = ?");
        $stmt->execute([$productId]);

        if (empty($images)) return;

        $stmt = $this->getDb()->prepare("INSERT INTO product_images (product_id, image_url, is_primary, sort_order) VALUES (?, ?, ?, ?)");
        $sort = 0;
        foreach ($images as $index => $imgUrl) {
            $isPrimary = ($index === 0) ? 1 : 0;
            $stmt->execute([$productId, $imgUrl, $isPrimary, $sort++]);
        }
    }
}
