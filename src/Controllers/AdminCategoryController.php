<?php

namespace HBM\Controllers;

use HBM\Services\AdminCategoryService;
use HBM\Helpers\Response;
use Exception;

class AdminCategoryController {
    private AdminCategoryService $service;

    public function __construct() {
        $this->service = new AdminCategoryService();
    }

    public function listCategories(): void {
        try {
            $categories = $this->service->getAllCategories();
            Response::success('Categories fetched successfully', ['categories' => $categories]);
        } catch (Exception $e) {
            Response::error($e->getMessage(), 500);
        }
    }

    public function getCategory(int $id): void {
        try {
            $category = $this->service->getCategoryById($id);
            if (!$category) {
                Response::error("Category not found.", 404);
            }
            Response::success('Category fetched successfully', ['category' => $category]);
        } catch (Exception $e) {
            Response::error($e->getMessage(), 500);
        }
    }

    public function createCategory(): void {
        global $authUser;
        $input = json_decode(file_get_contents('php://input'), true) ?? [];

        try {
            $id = $this->service->createCategory($input);
            \HBM\Services\AdminActivityLogService::log($authUser['id'], 'CATEGORY_CREATED', 'product_categories', $id);
            Response::success('Category created successfully', ['id' => $id], 201);
        } catch (Exception $e) {
            Response::error($e->getMessage(), 400);
        }
    }

    public function updateCategory(int $id): void {
        global $authUser;
        $input = json_decode(file_get_contents('php://input'), true) ?? [];

        try {
            $this->service->updateCategory($id, $input);
            \HBM\Services\AdminActivityLogService::log($authUser['id'], 'CATEGORY_UPDATED', 'product_categories', $id);
            Response::success('Category updated successfully');
        } catch (Exception $e) {
            Response::error($e->getMessage(), 400);
        }
    }

    public function deleteCategory(int $id): void {
        global $authUser;

        try {
            $this->service->deleteCategory($id);
            \HBM\Services\AdminActivityLogService::log($authUser['id'], 'CATEGORY_DELETED', 'product_categories', $id);
            Response::success('Category deleted successfully');
        } catch (Exception $e) {
            Response::error($e->getMessage(), 400);
        }
    }
}
