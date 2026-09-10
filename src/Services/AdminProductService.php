<?php

namespace HBM\Services;

use HBM\Repositories\AdminProductRepository;
use HBM\Services\AdminActivityLogger;
use Exception;

class AdminProductService {
    private AdminProductRepository $repo;

    public function __construct() {
        $this->repo = new AdminProductRepository();
    }

    public function createProduct(int $adminId, array $data): int {
        if (empty($data['name']) || empty($data['price']) || empty($data['category_id'])) {
            throw new Exception("Name, price, and category_id are required.");
        }

        if (empty($data['slug'])) {
            $data['slug'] = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $data['name'])));
        }

        $productId = $this->repo->createProduct($data);
        AdminActivityLogger::log($adminId, 'CREATE', 'products', $productId, ['name' => $data['name']]);
        return $productId;
    }

    public function updateProduct(int $adminId, int $id, array $data): void {
        if (empty($data)) {
            throw new Exception("No data provided for update.");
        }

        $this->repo->updateProduct($id, $data);
        AdminActivityLogger::log($adminId, 'UPDATE', 'products', $id, $data);
    }
}
