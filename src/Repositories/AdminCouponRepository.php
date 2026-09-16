<?php

namespace HBM\Repositories;

use HBM\Core\Database;
use PDO;
use PDOException;
use Exception;

class AdminCouponRepository {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    public function getCoupons(array $filters = []): array {
        $where = [];
        $params = [];

        if (!empty($filters['status']) && $filters['status'] !== 'all') {
            $where[] = "status = :status";
            $params[':status'] = $filters['status'];
        }

        if (!empty($filters['search'])) {
            $where[] = "(name LIKE :search OR code LIKE :search)";
            $params[':search'] = '%' . $filters['search'] . '%';
        }

        $whereSql = '';
        if (!empty($where)) {
            $whereSql = "WHERE " . implode(" AND ", $where);
        }

        $limit = $filters['limit'] ?? 50;
        $offset = $filters['offset'] ?? 0;

        $sql = "SELECT * FROM coupons $whereSql ORDER BY created_at DESC LIMIT " . (int)$limit . " OFFSET " . (int)$offset;
        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();
        $coupons = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Fetch targets if applicable
        $couponIds = array_column($coupons, 'id');
        if (!empty($couponIds)) {
            $placeholders = implode(',', array_fill(0, count($couponIds), '?'));
            $targetSql = "SELECT * FROM coupon_targets WHERE coupon_id IN ($placeholders)";
            $targetStmt = $this->db->prepare($targetSql);
            $targetStmt->execute($couponIds);
            $targets = $targetStmt->fetchAll(PDO::FETCH_ASSOC);

            $targetsByCoupon = [];
            foreach ($targets as $t) {
                $targetsByCoupon[$t['coupon_id']][] = $t;
            }

            foreach ($coupons as &$coupon) {
                if ($coupon['applicable_to'] !== 'all') {
                    $coupon['targets'] = $targetsByCoupon[$coupon['id']] ?? [];
                } else {
                    $coupon['targets'] = [];
                }
            }
        }

        return $coupons;
    }

    public function getCouponById(int $id): ?array {
        $stmt = $this->db->prepare("SELECT * FROM coupons WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $coupon = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$coupon) return null;

        if ($coupon['applicable_to'] !== 'all') {
            $tStmt = $this->db->prepare("SELECT * FROM coupon_targets WHERE coupon_id = :id");
            $tStmt->execute([':id' => $id]);
            $coupon['targets'] = $tStmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            $coupon['targets'] = [];
        }

        return $coupon;
    }

    public function createCoupon(array $data): int {
        try {
            $this->db->beginTransaction();

            $sql = "INSERT INTO coupons (
                code, name, description, discount_type, discount_value, 
                start_date, end_date, status, applicable_to,
                usage_limit, per_user_limit, min_order_value
            ) VALUES (
                :code, :name, :description, :discount_type, :discount_value, 
                :start_date, :end_date, :status, :applicable_to,
                :usage_limit, :per_user_limit, :min_order_value
            )";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':code' => strtoupper($data['code']),
                ':name' => $data['name'],
                ':description' => $data['description'] ?? null,
                ':discount_type' => $data['discount_type'],
                ':discount_value' => $data['discount_value'],
                ':start_date' => !empty($data['start_date']) ? $data['start_date'] : null,
                ':end_date' => !empty($data['end_date']) ? $data['end_date'] : null,
                ':status' => $data['status'] ?? 'inactive',
                ':applicable_to' => $data['applicable_to'] ?? 'all',
                ':usage_limit' => !empty($data['usage_limit']) ? (int)$data['usage_limit'] : null,
                ':per_user_limit' => !empty($data['per_user_limit']) ? (int)$data['per_user_limit'] : null,
                ':min_order_value' => !empty($data['min_order_value']) ? (float)$data['min_order_value'] : null,
            ]);

            $couponId = (int)$this->db->lastInsertId();

            if (!empty($data['targets']) && $data['applicable_to'] !== 'all') {
                $this->insertTargets($couponId, $data['targets']);
            }

            $this->db->commit();
            return $couponId;
        } catch (PDOException $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function updateCoupon(int $id, array $data): void {
        try {
            $this->db->beginTransaction();

            $sql = "UPDATE coupons SET 
                code = :code,
                name = :name,
                description = :description,
                discount_type = :discount_type,
                discount_value = :discount_value,
                start_date = :start_date,
                end_date = :end_date,
                status = :status,
                applicable_to = :applicable_to,
                usage_limit = :usage_limit,
                per_user_limit = :per_user_limit,
                min_order_value = :min_order_value
            WHERE id = :id";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':id' => $id,
                ':code' => strtoupper($data['code']),
                ':name' => $data['name'],
                ':description' => $data['description'] ?? null,
                ':discount_type' => $data['discount_type'],
                ':discount_value' => $data['discount_value'],
                ':start_date' => !empty($data['start_date']) ? $data['start_date'] : null,
                ':end_date' => !empty($data['end_date']) ? $data['end_date'] : null,
                ':status' => $data['status'] ?? 'inactive',
                ':applicable_to' => $data['applicable_to'] ?? 'all',
                ':usage_limit' => !empty($data['usage_limit']) ? (int)$data['usage_limit'] : null,
                ':per_user_limit' => !empty($data['per_user_limit']) ? (int)$data['per_user_limit'] : null,
                ':min_order_value' => !empty($data['min_order_value']) ? (float)$data['min_order_value'] : null,
            ]);

            // Manage targets
            $delStmt = $this->db->prepare("DELETE FROM coupon_targets WHERE coupon_id = :id");
            $delStmt->execute([':id' => $id]);

            if (!empty($data['targets']) && $data['applicable_to'] !== 'all') {
                $this->insertTargets($id, $data['targets']);
            }

            $this->db->commit();
        } catch (PDOException $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function deleteCoupon(int $id): void {
        $stmt = $this->db->prepare("DELETE FROM coupons WHERE id = :id");
        $stmt->execute([':id' => $id]);
    }

    private function insertTargets(int $couponId, array $targets): void {
        $sql = "INSERT INTO coupon_targets (coupon_id, target_id) VALUES ";
        $values = [];
        $params = [];
        
        foreach ($targets as $index => $targetId) {
            $values[] = "(:c{$index}, :t{$index})";
            $params[":c{$index}"] = $couponId;
            $params[":t{$index}"] = (int)$targetId;
        }

        if (!empty($values)) {
            $stmt = $this->db->prepare($sql . implode(", ", $values));
            $stmt->execute($params);
        }
    }
}
