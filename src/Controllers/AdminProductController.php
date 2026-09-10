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

    public function createProduct(): void {
        global $authUser;
        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        
        try {
            $id = $this->service->createProduct($authUser['id'], $input);
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
            Response::success('Product updated successfully');
        } catch (Exception $e) {
            Response::error($e->getMessage(), 400);
        }
    }
}
