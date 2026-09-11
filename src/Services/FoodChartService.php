<?php

namespace HBM\Services;

use HBM\Repositories\FoodChartRepository;
use Exception;

class FoodChartService {
    private FoodChartRepository $foodChartRepo;

    public function __construct() {
        $this->foodChartRepo = new FoodChartRepository();
    }

    public function getUserFoodChart(int $userId): ?array {
        $dietPlan = $this->foodChartRepo->getDietPlanByUserId($userId);
        
        if (!$dietPlan) {
            return null; // User doesn't have a plan assigned
        }

        $meals = $this->foodChartRepo->getMealsByDietPlanId($dietPlan['id']);

        // Calculate consumed calories based on meals
        $totalCalories = 0;
        foreach ($meals as $meal) {
            $totalCalories += (int) $meal['calories'];
        }

        // Add summary logic to plan
        $dietPlan['total_calories_planned'] = $totalCalories;
        $dietPlan['meals'] = $meals;

        return $dietPlan;
    }
}
