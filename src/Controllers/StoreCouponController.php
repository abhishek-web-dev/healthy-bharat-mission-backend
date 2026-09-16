<?php

namespace HBM\Controllers;

use HBM\Repositories\StoreCouponRepository;
use HBM\Helpers\Response;
use Exception;

class StoreCouponController {
    private StoreCouponRepository $repo;

    public function __construct() {
        $this->repo = new StoreCouponRepository();
    }

    public function getActiveCoupons(): void {
        try {
            $coupons = $this->repo->getActiveCoupons();
            Response::success('Active coupons fetched successfully', ['coupons' => $coupons]);
        } catch (Exception $e) {
            Response::error('Failed to fetch coupons', 500);
        }
    }

    public function validateCoupon(): void {
        try {
            $code = $_POST['code'] ?? ($_GET['code'] ?? '');
            $subtotal = (float)($_POST['subtotal'] ?? ($_GET['subtotal'] ?? 0));

            if (empty($code)) {
                throw new Exception("Coupon code is required.");
            }

            $coupon = $this->repo->getCouponByCode($code);

            if (!$coupon) {
                throw new Exception("Invalid or expired coupon code.");
            }

            if (!empty($coupon['min_order_value']) && $subtotal < $coupon['min_order_value']) {
                throw new Exception("This coupon requires a minimum order of ₹{$coupon['min_order_value']}.");
            }

            // Note: We're skipping usage limit, user limit, and target product logic for this simple checkout integration.
            // A fully fleshed out checkout would check these limits here and during order placement.

            // Calculate discount
            $discountAmount = 0;
            if ($coupon['discount_type'] === 'percentage') {
                $discountAmount = ($subtotal * $coupon['discount_value']) / 100;
            } else {
                $discountAmount = $coupon['discount_value'];
            }

            // Cap discount at subtotal
            if ($discountAmount > $subtotal) {
                $discountAmount = $subtotal;
            }

            Response::success('Coupon is valid', [
                'coupon' => [
                    'code' => $coupon['code'],
                    'discount_amount' => round($discountAmount, 2),
                    'discount_type' => $coupon['discount_type'],
                    'discount_value' => $coupon['discount_value'],
                    'name' => $coupon['name']
                ]
            ]);
        } catch (Exception $e) {
            Response::error($e->getMessage(), 400);
        }
    }
}
