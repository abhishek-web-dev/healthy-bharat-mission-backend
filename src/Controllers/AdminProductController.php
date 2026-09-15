<?php

namespace HBM\Controllers;

use HBM\Services\AdminProductService;
use HBM\Helpers\Response;
use Exception;

class AdminProductController {
    private AdminProductService $service;

    public function __construct() {
        $this->service = new AdminProductService();
    }
    public function listProducts(): void {
        try {
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 100;
            $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
            $products = $this->service->getAllProducts($limit, $offset);
            Response::success('Products fetched successfully', ['products' => $products]);
        } catch (Exception $e) {
            Response::error($e->getMessage(), 500);
        }
    }


    public function createProduct(): void {
        global $authUser;
        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        
        try {
            $id = $this->service->createProduct($authUser['id'], $input);
            \HBM\Services\AdminActivityLogService::log($authUser['id'], 'PRODUCT_CREATED', 'products', $id);
            Response::success('Product created successfully', ['id' => $id], 201);
        } catch (Exception $e) {
            Response::error($e->getMessage(), 400);
        }
    }

    public function updateProduct(int $id): void {
        global $authUser;
        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        
        try {
            $this->service->updateProduct($authUser['id'], $id, $input);
            \HBM\Services\AdminActivityLogService::log($authUser['id'], 'PRODUCT_UPDATED', 'products', $id);
            Response::success('Product updated successfully');
        } catch (Exception $e) {
            Response::error($e->getMessage(), 400);
        }
    }
}
