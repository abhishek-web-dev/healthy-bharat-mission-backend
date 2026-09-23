<?php

namespace HBM\Services;

use HBM\Repositories\AdminOrderRepository;
use HBM\Services\AdminActivityLogger;
use Exception;

class AdminOrderService {
    private AdminOrderRepository $repo;

    public function __construct() {
        $this->repo = new AdminOrderRepository();
    }

    public function getAllOrders(int $limit = 50, int $offset = 0): array {
        return $this->repo->getAllOrders($limit, $offset);
    }

    public function getOrderById(int $id): array {
        $order = $this->repo->getOrderById($id);
        if (!$order) {
            throw new Exception("Order not found");
        }
        return $order;
    }

    public function updateOrderStatus(int $adminId, int $id, string $status): void {
        $validStatuses = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];
        if (!in_array($status, $validStatuses)) {
            throw new Exception("Invalid order status");
        }

        $order = $this->repo->getOrderById($id);
        if (!$order) {
            throw new Exception("Order not found");
        }

        $currentStatus = $order['order_status'] ?? 'pending';
        
        // Define allowed transitions
        $allowedTransitions = [
            'pending' => ['processing', 'shipped', 'delivered', 'cancelled'],
            'processing' => ['shipped', 'delivered', 'cancelled'],
            'shipped' => ['delivered', 'cancelled'],
            'delivered' => [],
            'cancelled' => []
        ];

        if ($currentStatus !== $status) {
            if (!isset($allowedTransitions[$currentStatus]) || !in_array($status, $allowedTransitions[$currentStatus])) {
                throw new Exception("Invalid order transition from '{$currentStatus}' to '{$status}'.");
            }
            $this->repo->updateOrderStatus($id, $status);
            AdminActivityLogger::log($adminId, 'UPDATE_ORDER_STATUS', 'orders', $id, ['new_status' => $status]);
        }
    }

    public function updatePaymentStatus(int $adminId, int $id, string $status): void {
        $validStatuses = ['pending', 'success', 'failed', 'refunded'];
        if (!in_array($status, $validStatuses)) {
            throw new Exception("Invalid payment status");
        }

        $order = $this->repo->getOrderById($id);
        if (!$order) {
            throw new Exception("Order not found");
        }

        $this->repo->updatePaymentStatus($id, $status);
        AdminActivityLogger::log($adminId, 'UPDATE_PAYMENT_STATUS', 'orders', $id, ['new_status' => $status]);
    }
}
