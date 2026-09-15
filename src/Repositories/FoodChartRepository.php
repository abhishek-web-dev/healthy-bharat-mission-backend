<?php

namespace HBM\Repositories;

use HBM\Core\Database;
use PDO;

class FoodChartRepository {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    public function getDietPlanByUserId(int $userId): ?array {
        $stmt = $this->db->prepare("
            SELECT * FROM user_diet_plans 
            WHERE user_id = :user_id 
            ORDER BY created_at DESC 
            LIMIT 1
        ");
        $stmt->execute(['user_id' => $userId]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    public function getMealsByDietPlanId(int $dietPlanId): array {
        $stmt = $this->db->prepare("
            SELECT * FROM user_meals 
            WHERE diet_plan_id = :diet_plan_id 
            ORDER BY display_order ASC, created_at ASC
        ");
        $stmt->execute(['diet_plan_id' => $dietPlanId]);
        return $stmt->fetchAll();
    }
}
