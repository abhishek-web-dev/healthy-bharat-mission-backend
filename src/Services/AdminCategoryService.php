<?php

namespace HBM\Services;

use HBM\Core\Database;
use PDO;
use Exception;

class AdminCategoryService {

    public function getAllCategories(): array {
        $db = Database::getConnection();
        $stmt = $db->query("
            SELECT c.*, COUNT(p.id) as product_count
            FROM product_categories c
            LEFT JOIN products p ON c.id = p.category_id AND p.deleted_at IS NULL
            GROUP BY c.id
            ORDER BY c.name ASC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getCategoryById(int $id): ?array {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM product_categories WHERE id = ?");
        $stmt->execute([$id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    public function createCategory(array $data): int {
        $db = Database::getConnection();
        
        $name = trim($data['name'] ?? '');
        $slug = trim($data['slug'] ?? '');
        $imageUrl = trim($data['image_url'] ?? '');

        if (empty($name) || empty($slug)) {
            throw new Exception("Name and Slug are required.");
        }

        // Check if slug exists
        $stmt = $db->prepare("SELECT id FROM product_categories WHERE slug = ?");
        $stmt->execute([$slug]);
        if ($stmt->fetch()) {
            throw new Exception("Category slug already exists.");
        }

        $stmt = $db->prepare("INSERT INTO product_categories (name, slug, image_url) VALUES (?, ?, ?)");
        $stmt->execute([$name, $slug, $imageUrl]);
        return (int)$db->lastInsertId();
    }

    public function updateCategory(int $id, array $data): void {
        $db = Database::getConnection();
        
        $category = $this->getCategoryById($id);
        if (!$category) {
            throw new Exception("Category not found.");
        }

        $name = trim($data['name'] ?? '');
        $slug = trim($data['slug'] ?? '');
        $imageUrl = trim($data['image_url'] ?? '');

        if (empty($name) || empty($slug)) {
            throw new Exception("Name and Slug are required.");
        }

        // Check if slug exists for other categories
        $stmt = $db->prepare("SELECT id FROM product_categories WHERE slug = ? AND id != ?");
        $stmt->execute([$slug, $id]);
        if ($stmt->fetch()) {
            throw new Exception("Category slug already exists.");
        }

        $stmt = $db->prepare("UPDATE product_categories SET name = ?, slug = ?, image_url = ? WHERE id = ?");
        $stmt->execute([$name, $slug, $imageUrl, $id]);
    }

    public function deleteCategory(int $id): void {
        $db = Database::getConnection();
        
        // Check if there are products attached
        $stmt = $db->prepare("SELECT id FROM products WHERE category_id = ? AND deleted_at IS NULL LIMIT 1");
        $stmt->execute([$id]);
        if ($stmt->fetch()) {
            throw new Exception("Cannot delete category because it has active products attached.");
        }
        
        $stmt = $db->prepare("DELETE FROM product_categories WHERE id = ?");
        $stmt->execute([$id]);
    }
}
