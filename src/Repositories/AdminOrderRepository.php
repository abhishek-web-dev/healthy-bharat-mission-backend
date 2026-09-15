<?php

namespace HBM\Repositories;

use HBM\Core\Database;
use PDO;

class AdminOrderRepository {
    private function getDb(): PDO {
        return Database::getConnection();
    }

    public function getAllOrders(int $limit = 50, int $offset = 0): array {
        $db = $this->getDb();
        $stmt = $db->prepare("SELECT * FROM orders ORDER BY created_at DESC LIMIT :limit OFFSET :offset");
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getOrderById(int $id): ?array {
        $stmt = $this->getDb()->prepare("SELECT * FROM orders WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($order) {
            $stmtItems = $this->getDb()->prepare("SELECT * FROM order_items WHERE order_id = :order_id");
            $stmtItems->execute(['order_id' => $id]);
            $order['items'] = $stmtItems->fetchAll(PDO::FETCH_ASSOC);
            
            $stmtPayment = $this->getDb()->prepare("SELECT * FROM payments WHERE order_id = :order_id");
            $stmtPayment->execute(['order_id' => $id]);
            $order['payment'] = $stmtPayment->fetch(PDO::FETCH_ASSOC) ?: null;
        }

        return $order ?: null;
    }

    public function updateOrderStatus(int $id, string $status): void {
        $stmt = $this->getDb()->prepare("UPDATE orders SET order_status = :status WHERE id = :id");
        $stmt->execute(['status' => $status, 'id' => $id]);
    }

    public function updatePaymentStatus(int $id, string $status): void {
        $stmt = $this->getDb()->prepare("UPDATE orders SET payment_status = :status WHERE id = :id");
        $stmt->execute(['status' => $status, 'id' => $id]);
    }
}
