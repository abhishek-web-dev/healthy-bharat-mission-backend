<?php

namespace HBM\Repositories;

use HBM\Core\Database;
use PDO;
use Exception;

class CheckoutRepository {
    private function getDb(): PDO {
        return Database::getConnection();
    }

    // --- Address Operations ---

    public function getUserAddresses(int $userId): array {
        $stmt = $this->getDb()->prepare("SELECT * FROM user_addresses WHERE user_id = :user_id ORDER BY is_default DESC, id DESC");
        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAddressById(int $id, int $userId): ?array {
        $stmt = $this->getDb()->prepare("SELECT * FROM user_addresses WHERE id = :id AND user_id = :user_id");
        $stmt->execute(['id' => $id, 'user_id' => $userId]);
        $address = $stmt->fetch(PDO::FETCH_ASSOC);
        return $address ?: null;
    }

    public function saveAddress(int $userId, array $data): int {
        if (!empty($data['is_default'])) {
            $this->getDb()->prepare("UPDATE user_addresses SET is_default = 0 WHERE user_id = :user_id")
                     ->execute(['user_id' => $userId]);
        }

        $stmt = $this->getDb()->prepare("
            INSERT INTO user_addresses (
                user_id, type, first_name, last_name, phone, email, 
                address_line_1, address_line_2, city, state, pincode, landmark, is_default
            ) VALUES (
                :user_id, :type, :first_name, :last_name, :phone, :email, 
                :address_line_1, :address_line_2, :city, :state, :pincode, :landmark, :is_default
            )
        ");
        $stmt->execute([
            'user_id' => $userId,
            'type' => $data['type'] ?? 'home',
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'phone' => $data['phone'],
            'email' => $data['email'] ?? null,
            'address_line_1' => $data['address_line_1'],
            'address_line_2' => $data['address_line_2'] ?? null,
            'city' => $data['city'],
            'state' => $data['state'],
            'pincode' => $data['pincode'],
            'landmark' => $data['landmark'] ?? null,
            'is_default' => !empty($data['is_default']) ? 1 : 0
        ]);
        return (int)$this->getDb()->lastInsertId();
    }

    // --- Order Operations ---

    public function beginTransaction(): void {
        $this->getDb()->beginTransaction();
    }

    public function commit(): void {
        $this->getDb()->commit();
    }

    public function rollBack(): void {
        if ($this->getDb()->inTransaction()) {
            $this->getDb()->rollBack();
        }
    }

    public function createOrder(array $orderData): int {
        $stmt = $this->getDb()->prepare("
            INSERT INTO orders (
                order_number, user_id, subtotal, shipping_fee, total_amount, 
                payment_method, payment_status, order_status,
                shipping_first_name, shipping_last_name, shipping_phone, shipping_email,
                shipping_address_line_1, shipping_address_line_2, shipping_city,
                shipping_state, shipping_pincode, shipping_landmark
            ) VALUES (
                :order_number, :user_id, :subtotal, :shipping_fee, :total_amount,
                :payment_method, :payment_status, :order_status,
                :shipping_first_name, :shipping_last_name, :shipping_phone, :shipping_email,
                :shipping_address_line_1, :shipping_address_line_2, :shipping_city,
                :shipping_state, :shipping_pincode, :shipping_landmark
            )
        ");
        $stmt->execute($orderData);
        return (int)$this->getDb()->lastInsertId();
    }

    public function createOrderItem(array $itemData): void {
        $stmt = $this->getDb()->prepare("
            INSERT INTO order_items (
                order_id, product_id, product_name_snapshot, price_snapshot, quantity, is_digital
            ) VALUES (
                :order_id, :product_id, :product_name_snapshot, :price_snapshot, :quantity, :is_digital
            )
        ");
        $stmt->execute($itemData);
    }

    public function createPaymentRecord(array $paymentData): int {
        $stmt = $this->getDb()->prepare("
            INSERT INTO payments (
                order_id, amount, currency, payment_method, status, razorpay_order_id
            ) VALUES (
                :order_id, :amount, :currency, :payment_method, :status, :razorpay_order_id
            )
        ");
        $stmt->execute($paymentData);
        return (int)$this->getDb()->lastInsertId();
    }

    public function updateProductStock(int $productId, int $quantityToReduce): void {
        $stmt = $this->getDb()->prepare("UPDATE products SET stock = stock - :qty1 WHERE id = :id AND stock >= :qty2");
        $stmt->execute(['qty1' => $quantityToReduce, 'qty2' => $quantityToReduce, 'id' => $productId]);
        if ($stmt->rowCount() === 0) {
            throw new Exception("Stock update failed for product $productId. It might be out of stock.");
        }
    }

    public function clearCart(int $userId): void {
        $stmt = $this->getDb()->prepare("DELETE FROM cart_items WHERE user_id = :user_id");
        $stmt->execute(['user_id' => $userId]);
    }

    public function getUserOrders(int $userId): array {
        $stmt = $this->getDb()->prepare("SELECT * FROM orders WHERE user_id = :user_id ORDER BY created_at DESC");
        $stmt->execute(['user_id' => $userId]);
        $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($orders as &$order) {
            $stmtItems = $this->getDb()->prepare("
                SELECT oi.*, p.thumbnail_url as image_url 
                FROM order_items oi
                LEFT JOIN products p ON oi.product_id = p.id
                WHERE oi.order_id = :order_id
            ");
            $stmtItems->execute(['order_id' => $order['id']]);
            $order['items'] = $stmtItems->fetchAll(PDO::FETCH_ASSOC);
        }

        return $orders;
    }

    public function getOrderDetails(int $orderId, int $userId): ?array {
        $stmt = $this->getDb()->prepare("SELECT * FROM orders WHERE id = :id AND user_id = :user_id");
        $stmt->execute(['id' => $orderId, 'user_id' => $userId]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$order) {
            return null;
        }

        $stmtItems = $this->getDb()->prepare("
            SELECT oi.*, p.thumbnail_url as image_url, p.slug
            FROM order_items oi
            LEFT JOIN products p ON oi.product_id = p.id
            WHERE oi.order_id = :order_id
        ");
        $stmtItems->execute(['order_id' => $order['id']]);
        $order['items'] = $stmtItems->fetchAll(PDO::FETCH_ASSOC);

        $stmtPayment = $this->getDb()->prepare("SELECT * FROM payments WHERE order_id = :order_id");
        $stmtPayment->execute(['order_id' => $order['id']]);
        $order['payment'] = $stmtPayment->fetch(PDO::FETCH_ASSOC) ?: null;

        return $order;
    }

    public function updateOrderStatus(int $orderId, string $status): void {
        $stmt = $this->getDb()->prepare("UPDATE orders SET order_status = :status WHERE id = :id");
        $stmt->execute(['status' => $status, 'id' => $orderId]);
    }

    public function updatePaymentStatus(int $orderId, string $status, array $razorpayData = []): void {
        $stmtOrder = $this->getDb()->prepare("UPDATE orders SET payment_status = :status WHERE id = :id");
        $stmtOrder->execute(['status' => $status === 'captured' ? 'success' : 'failed', 'id' => $orderId]);

        $query = "UPDATE payments SET status = :status";
        $params = ['status' => $status, 'order_id' => $orderId];

        if (!empty($razorpayData['razorpay_payment_id'])) {
            $query .= ", razorpay_payment_id = :rp_payment_id, razorpay_signature = :rp_signature";
            $params['rp_payment_id'] = $razorpayData['razorpay_payment_id'];
            $params['rp_signature'] = $razorpayData['razorpay_signature'] ?? null;
        }

        $query .= " WHERE order_id = :order_id";
        
        $stmtPayment = $this->getDb()->prepare($query);
        $stmtPayment->execute($params);
    }
}
