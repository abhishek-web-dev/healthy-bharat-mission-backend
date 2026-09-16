<?php

namespace HBM\Repositories;

use HBM\Core\Database;
use PDO;
use Exception;

class AdminOfferRepository {

    public function getOffers(array $filters = []): array {
        $db = Database::getConnection();
        
        $query = "SELECT * FROM offers WHERE 1=1";
        $params = [];

        if (!empty($filters['status']) && $filters['status'] !== 'all') {
            $query .= " AND status = ?";
            $params[] = $filters['status'];
        }

        if (!empty($filters['search'])) {
            $query .= " AND (name LIKE ? OR code LIKE ?)";
            $search = '%' . $filters['search'] . '%';
            $params = array_merge($params, [$search, $search]);
        }

        $query .= " ORDER BY created_at DESC";
        
        $limit = isset($filters['limit']) ? (int)$filters['limit'] : 50;
        $offset = isset($filters['offset']) ? (int)$filters['offset'] : 0;
        $query .= " LIMIT $limit OFFSET $offset";

        $stmt = $db->prepare($query);
        $stmt->execute($params);
        $offers = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Fetch targets if needed
        foreach ($offers as &$offer) {
            if ($offer['applicable_to'] !== 'all') {
                $stmtTargets = $db->prepare("SELECT target_type, target_id FROM offer_targets WHERE offer_id = ?");
                $stmtTargets->execute([$offer['id']]);
                $offer['targets'] = $stmtTargets->fetchAll(PDO::FETCH_ASSOC);
            } else {
                $offer['targets'] = [];
            }
        }

        return $offers;
    }

    public function getOfferById(int $id): ?array {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM offers WHERE id = ?");
        $stmt->execute([$id]);
        $offer = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$offer) return null;

        if ($offer['applicable_to'] !== 'all') {
            $stmtTargets = $db->prepare("SELECT target_type, target_id FROM offer_targets WHERE offer_id = ?");
            $stmtTargets->execute([$id]);
            $offer['targets'] = $stmtTargets->fetchAll(PDO::FETCH_ASSOC);
        } else {
            $offer['targets'] = [];
        }

        return $offer;
    }

    public function createOffer(array $data): int {
        $db = Database::getConnection();
        $db->beginTransaction();

        try {
            $stmt = $db->prepare("
                INSERT INTO offers (name, code, discount_type, discount_value, start_date, end_date, status, applicable_to, usage_limit)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $data['name'],
                $data['code'] ?: null,
                $data['discount_type'],
                $data['discount_value'],
                $data['start_date'] ?: null,
                $data['end_date'] ?: null,
                $data['status'] ?? 'inactive',
                $data['applicable_to'] ?? 'all',
                $data['usage_limit'] ?: null
            ]);
            
            $offerId = (int)$db->lastInsertId();

            if (($data['applicable_to'] === 'products' || $data['applicable_to'] === 'categories') && !empty($data['targets'])) {
                $stmtTarget = $db->prepare("INSERT INTO offer_targets (offer_id, target_type, target_id) VALUES (?, ?, ?)");
                foreach ($data['targets'] as $targetId) {
                    $targetType = rtrim($data['applicable_to'], 's'); // 'product' or 'category'
                    $stmtTarget->execute([$offerId, $targetType, $targetId]);
                }
            }

            $db->commit();
            return $offerId;
        } catch (Exception $e) {
            $db->rollBack();
            throw $e;
        }
    }

    public function updateOffer(int $id, array $data): void {
        $db = Database::getConnection();
        $db->beginTransaction();

        try {
            $stmt = $db->prepare("
                UPDATE offers 
                SET name = ?, code = ?, discount_type = ?, discount_value = ?, start_date = ?, end_date = ?, status = ?, applicable_to = ?, usage_limit = ?
                WHERE id = ?
            ");
            $stmt->execute([
                $data['name'],
                $data['code'] ?: null,
                $data['discount_type'],
                $data['discount_value'],
                $data['start_date'] ?: null,
                $data['end_date'] ?: null,
                $data['status'],
                $data['applicable_to'],
                $data['usage_limit'] ?: null,
                $id
            ]);

            // Delete old targets
            $stmtDelete = $db->prepare("DELETE FROM offer_targets WHERE offer_id = ?");
            $stmtDelete->execute([$id]);

            // Insert new targets
            if (($data['applicable_to'] === 'products' || $data['applicable_to'] === 'categories') && !empty($data['targets'])) {
                $stmtTarget = $db->prepare("INSERT INTO offer_targets (offer_id, target_type, target_id) VALUES (?, ?, ?)");
                foreach ($data['targets'] as $targetId) {
                    $targetType = rtrim($data['applicable_to'], 's');
                    $stmtTarget->execute([$id, $targetType, $targetId]);
                }
            }

            $db->commit();
        } catch (Exception $e) {
            $db->rollBack();
            throw $e;
        }
    }

    public function deleteOffer(int $id): void {
        $db = Database::getConnection();
        $stmt = $db->prepare("DELETE FROM offers WHERE id = ?");
        $stmt->execute([$id]);
        if ($stmt->rowCount() === 0) {
            throw new Exception("Offer not found");
        }
    }
}
