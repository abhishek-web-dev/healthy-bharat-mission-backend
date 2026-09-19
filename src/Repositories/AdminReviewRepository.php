<?php

namespace HBM\Repositories;

use HBM\Core\Database;
use PDO;
use Exception;

class AdminReviewRepository {
    public function getReviewCounts(): array {
        $db = Database::getConnection();
        $stmt = $db->query("
            SELECT status, COUNT(*) as count 
            FROM product_reviews 
            GROUP BY status
        ");
        
        $counts = ['pending' => 0, 'approved' => 0, 'rejected' => 0];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $counts[$row['status']] = (int)$row['count'];
        }
        return $counts;
    }

    public function getReviews(array $filters = []): array {
        $db = Database::getConnection();
        
        $query = "
            SELECT r.id, r.product_id, r.reviewer_name, r.rating, r.title, r.status, r.created_at, r.variant,
                   p.name as product_name, p.sku as product_sku, p.thumbnail_url as product_image
            FROM product_reviews r
            JOIN products p ON r.product_id = p.id
            WHERE 1=1
        ";
        $params = [];

        if (!empty($filters['status']) && $filters['status'] !== 'all') {
            $query .= " AND r.status = ?";
            $params[] = $filters['status'];
        }

        if (!empty($filters['search'])) {
            $query .= " AND (p.name LIKE ? OR r.reviewer_name LIKE ? OR p.sku LIKE ? OR r.title LIKE ?)";
            $search = '%' . $filters['search'] . '%';
            $params = array_merge($params, [$search, $search, $search, $search]);
        }

        $query .= " ORDER BY r.created_at DESC";
        
        $limit = isset($filters['limit']) ? (int)$filters['limit'] : 50;
        $offset = isset($filters['offset']) ? (int)$filters['offset'] : 0;
        $query .= " LIMIT $limit OFFSET $offset";

        $stmt = $db->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getReviewById(int $id): ?array {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT r.*, 
                   p.name as product_name, p.sku as product_sku, p.thumbnail_url as product_image, p.price, p.mrp, p.slug as product_slug
            FROM product_reviews r
            JOIN products p ON r.product_id = p.id
            WHERE r.id = ?
        ");
        $stmt->execute([$id]);
        $review = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($review) {
            $photoStmt = $db->prepare("SELECT image_path FROM review_photos WHERE review_id = ?");
            $photoStmt->execute([$id]);
            $review['photos'] = $photoStmt->fetchAll(PDO::FETCH_COLUMN);
        }
        
        return $review ?: null;
    }

    public function updateReviewStatus(int $id, string $status): void {
        $db = Database::getConnection();
        $stmt = $db->prepare("UPDATE product_reviews SET status = ? WHERE id = ?");
        $stmt->execute([$status, $id]);
        if ($stmt->rowCount() === 0) {
            throw new Exception("Review not found or status is already set to $status");
        }
    }
}
