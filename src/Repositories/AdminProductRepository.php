<?php

namespace HBM\Repositories;

use HBM\Core\Database;
use PDO;

class AdminProductRepository {
    private function getDb(): PDO {
        return Database::getConnection();
    }

    public function createProduct(array $data): int {
        $stmt = $this->getDb()->prepare("
            INSERT INTO products (
                category_id, name, slug, description, price, stock, is_active, is_digital, 
                thumbnail_url
            ) VALUES (
                :category_id, :name, :slug, :description, :price, :stock, :is_active, :is_digital,
                :thumbnail_url
            )
        ");

        $stmt->execute([
            'category_id' => $data['category_id'],
            'name' => $data['name'],
            'slug' => $data['slug'],
            'description' => $data['description'] ?? null,
            'price' => $data['price'],
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

        $allowedFields = ['category_id', 'name', 'slug', 'description', 'price', 'stock', 'is_active', 'is_digital', 'thumbnail_url'];
        
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
}
