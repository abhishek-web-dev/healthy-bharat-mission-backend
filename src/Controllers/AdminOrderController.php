<?php

namespace HBM\Controllers;

use HBM\Services\AdminOrderService;
use HBM\Helpers\Response;
use Exception;

class AdminOrderController {
    private AdminOrderService $service;

    public function __construct() {
        $this->service = new AdminOrderService();
    }

    public function listOrders(): void {
        try {
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
            $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
            $orders = $this->service->getAllOrders($limit, $offset);
            Response::success('Orders fetched successfully', ['orders' => $orders]);
        } catch (Exception $e) {
            Response::error($e->getMessage(), 500);
        }
    }

    public function getOrder(int $id): void {
        try {
            $order = $this->service->getOrderById($id);
            Response::success('Order fetched successfully', $order);
        } catch (Exception $e) {
            Response::error($e->getMessage(), 404);
        }
    }

    public function updateOrderStatus(int $id): void {
        global $authUser;
        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        
        try {
            if (empty($input['status'])) {
                throw new Exception("Status is required");
            }
            $this->service->updateOrderStatus($authUser['id'], $id, $input['status']);
            \HBM\Services\AdminActivityLogService::log($authUser['id'], 'ORDER_STATUS_UPDATED', 'orders', $id, ['status' => $input['status']]);
            Response::success('Order status updated successfully');
        } catch (Exception $e) {
            Response::error($e->getMessage(), 400);
        }
    }

    public function updatePaymentStatus(int $id): void {
        global $authUser;
        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        
        try {
            if (empty($input['status'])) {
                throw new Exception("Status is required");
            }
            $this->service->updatePaymentStatus($authUser['id'], $id, $input['status']);
            \HBM\Services\AdminActivityLogService::log($authUser['id'], 'PAYMENT_STATUS_UPDATED', 'orders', $id, ['status' => $input['status']]);
            Response::success('Payment status updated successfully');
        } catch (Exception $e) {
            Response::error($e->getMessage(), 400);
        }
    }
}
