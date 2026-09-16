<?php

namespace HBM\Controllers;

use HBM\Repositories\AdminCouponRepository;
use HBM\Helpers\Response;
use HBM\Services\AdminActivityLogService;
use Exception;

class AdminCouponController {
    private AdminCouponRepository $repo;

    public function __construct() {
        $this->repo = new AdminCouponRepository();
    }

    public function getCoupons(): void {
        try {
            $filters = [
                'status' => $_GET['status'] ?? 'all',
                'search' => $_GET['search'] ?? '',
                'limit'  => isset($_GET['limit']) ? (int)$_GET['limit'] : 50,
                'offset' => isset($_GET['offset']) ? (int)$_GET['offset'] : 0,
            ];
            
            $coupons = $this->repo->getCoupons($filters);
            Response::success('Coupons fetched successfully', ['coupons' => $coupons]);
        } catch (Exception $e) {
            Response::error($e->getMessage(), 500);
        }
    }

    public function getCouponById(int $id): void {
        try {
            $coupon = $this->repo->getCouponById($id);
            if (!$coupon) {
                Response::error("Coupon not found", 404);
            }
            Response::success('Coupon fetched successfully', ['coupon' => $coupon]);
        } catch (Exception $e) {
            Response::error($e->getMessage(), 500);
        }
    }

    public function createCoupon(): void {
        global $authUser;
        $input = json_decode(file_get_contents('php://input'), true) ?? [];

        try {
            if (empty($input['code']) || empty($input['name']) || empty($input['discount_type']) || !isset($input['discount_value'])) {
                throw new Exception("Code, name, discount type, and discount value are required.");
            }

            if (!preg_match('/^[A-Z0-9_-]{3,20}$/i', $input['code'])) {
                throw new Exception("Invalid coupon code format. Use 3-20 letters, numbers, hyphens, or underscores.");
            }

            if ($input['discount_value'] <= 0) {
                throw new Exception("Discount value must be greater than zero.");
            }

            if ($input['discount_type'] === 'percentage' && $input['discount_value'] > 100) {
                throw new Exception("Percentage discount cannot exceed 100%.");
            }

            $couponId = $this->repo->createCoupon($input);

            AdminActivityLogService::log(
                $authUser['id'], 
                'COUPON_CREATED', 
                'coupons', 
                $couponId
            );

            Response::success('Coupon created successfully', ['id' => $couponId]);
        } catch (Exception $e) {
            if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                Response::error("Coupon code must be unique.", 400);
            }
            Response::error($e->getMessage(), 400);
        }
    }

    public function updateCoupon(int $id): void {
        global $authUser;
        $input = json_decode(file_get_contents('php://input'), true) ?? [];

        try {
            if (empty($input['code']) || empty($input['name']) || empty($input['discount_type']) || !isset($input['discount_value'])) {
                throw new Exception("Code, name, discount type, and discount value are required.");
            }

            if (!preg_match('/^[A-Z0-9_-]{3,20}$/i', $input['code'])) {
                throw new Exception("Invalid coupon code format. Use 3-20 letters, numbers, hyphens, or underscores.");
            }

            if ($input['discount_value'] <= 0) {
                throw new Exception("Discount value must be greater than zero.");
            }

            if ($input['discount_type'] === 'percentage' && $input['discount_value'] > 100) {
                throw new Exception("Percentage discount cannot exceed 100%.");
            }

            $this->repo->updateCoupon($id, $input);

            AdminActivityLogService::log(
                $authUser['id'], 
                'COUPON_UPDATED', 
                'coupons', 
                $id
            );

            Response::success('Coupon updated successfully');
        } catch (Exception $e) {
            if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                Response::error("Coupon code must be unique.", 400);
            }
            Response::error($e->getMessage(), 400);
        }
    }

    public function deleteCoupon(int $id): void {
        global $authUser;
        try {
            $this->repo->deleteCoupon($id);

            AdminActivityLogService::log(
                $authUser['id'], 
                'COUPON_DELETED', 
                'coupons', 
                $id
            );

            Response::success('Coupon deleted successfully');
        } catch (Exception $e) {
            Response::error($e->getMessage(), 400);
        }
    }
}
