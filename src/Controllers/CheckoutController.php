<?php

namespace HBM\Controllers;

use HBM\Services\CheckoutService;
use HBM\Helpers\Response;
use Exception;

class CheckoutController {
    private CheckoutService $checkoutService;

    public function __construct() {
        $this->checkoutService = new CheckoutService();
    }

    public function getAddresses(): void {
        global $authUser;
        try {
            $addresses = $this->checkoutService->getUserAddresses($authUser['id']);
            Response::success('Addresses fetched successfully.', $addresses);
        } catch (Exception $e) {
            Response::error($e->getMessage(), 400);
        }
    }

    public function saveAddress(): void {
        global $authUser;
        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        
        try {
            $address = $this->checkoutService->saveAddress($authUser['id'], $input);
            Response::success('Address saved successfully.', $address, 201);
        } catch (Exception $e) {
            Response::error($e->getMessage(), 400);
        }
    }

    public function updateAddress(int $id): void {
        global $authUser;
        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        
        try {
            $address = $this->checkoutService->updateAddress($authUser['id'], $id, $input);
            Response::success('Address updated successfully.', $address);
        } catch (Exception $e) {
            Response::error($e->getMessage(), 400);
        }
    }

    public function deleteAddress(int $id): void {
        global $authUser;
        try {
            $this->checkoutService->deleteAddress($authUser['id'], $id);
            Response::success('Address deleted successfully.');
        } catch (Exception $e) {
            Response::error($e->getMessage(), 400);
        }
    }

    public function createOrder(): void {
        global $authUser;
        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        
        try {
            $order = $this->checkoutService->createOrder($authUser['id'], $input);
            Response::success('Order created successfully.', $order, 201);
        } catch (Exception $e) {
            Response::error($e->getMessage(), 400);
        }
    }

    public function verifyPayment(): void {
        global $authUser;
        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        
        try {
            $result = $this->checkoutService->verifyPayment($authUser['id'], $input);
            Response::success('Payment verified successfully.', $result);
        } catch (Exception $e) {
            Response::error($e->getMessage(), 400);
        }
    }

    public function getOrders(): void {
        global $authUser;
        try {
            $orders = $this->checkoutService->getUserOrders($authUser['id']);
            Response::success('Orders fetched successfully.', ['orders' => $orders]);
        } catch (Exception $e) {
            Response::error($e->getMessage(), 400);
        }
    }

    public function getOrderDetails(int $id): void {
        global $authUser;
        try {
            $order = $this->checkoutService->getOrderDetails($id, $authUser['id']);
            Response::success('Order details fetched successfully.', $order);
        } catch (Exception $e) {
            Response::error($e->getMessage(), 404);
        }
    }
}
