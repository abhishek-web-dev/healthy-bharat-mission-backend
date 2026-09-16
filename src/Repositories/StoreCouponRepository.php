<?php

namespace HBM\Repositories;

use HBM\Core\Database;
use PDO;

class StoreCouponRepository {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    public function getActiveCoupons(): array {
        $sql1 = "SELECT id, code, name, description, discount_type, discount_value, min_order_value, applicable_to, 'coupon' as type 
                FROM coupons 
                WHERE status = 'active' 
                AND (start_date IS NULL OR start_date <= NOW())
                AND (end_date IS NULL OR end_date >= NOW())";
        
        $sql2 = "SELECT id, code, name, '' as description, discount_type, discount_value, 0 as min_order_value, applicable_to, 'offer' as type 
                FROM offers 
                WHERE status = 'active' 
                AND code IS NOT NULL AND code != ''
                AND (start_date IS NULL OR start_date <= NOW())
                AND (end_date IS NULL OR end_date >= NOW())";
        
        $coupons = $this->db->query($sql1)->fetchAll(PDO::FETCH_ASSOC);
        $offers = $this->db->query($sql2)->fetchAll(PDO::FETCH_ASSOC);
        
        $all = array_merge($coupons, $offers);
        // Sort by code ASC
        usort($all, function($a, $b) {
            return strcmp($a['code'] ?? '', $b['code'] ?? '');
        });
        
        return array_slice($all, 0, 20);
    }

    public function getCouponByCode(string $code): ?array {
        $sql1 = "SELECT id, code, name, description, discount_type, discount_value, min_order_value, applicable_to, 'coupon' as type 
                FROM coupons 
                WHERE code = :code
                AND status = 'active'
                AND (start_date IS NULL OR start_date <= NOW())
                AND (end_date IS NULL OR end_date >= NOW())";
        $stmt1 = $this->db->prepare($sql1);
        $stmt1->execute([':code' => strtoupper($code)]);
        $coupon = $stmt1->fetch(PDO::FETCH_ASSOC);
        if ($coupon) return $coupon;
        
        $sql2 = "SELECT id, code, name, '' as description, discount_type, discount_value, 0 as min_order_value, applicable_to, 'offer' as type 
                FROM offers 
                WHERE code = :code
                AND status = 'active'
                AND (start_date IS NULL OR start_date <= NOW())
                AND (end_date IS NULL OR end_date >= NOW())";
        $stmt2 = $this->db->prepare($sql2);
        $stmt2->execute([':code' => strtoupper($code)]);
        $offer = $stmt2->fetch(PDO::FETCH_ASSOC);
        
        return $offer ?: null;
    }
}
