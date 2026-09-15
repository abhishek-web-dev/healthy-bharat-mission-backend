<?php

namespace HBM\Controllers;

use HBM\Helpers\Response;
use HBM\Services\FoodChartService;
use Exception;

class FoodChartController {
    private FoodChartService $foodChartService;

    public function __construct() {
        $this->foodChartService = new FoodChartService();
    }

    public function getFoodCharts(): void {
        global $authUser;
        try {
            $foodChart = $this->foodChartService->getUserFoodChart($authUser['id']);
            
            if (!$foodChart) {
                Response::success('No active food chart found.', null);
                return;
            }

            Response::success('Food chart fetched successfully.', $foodChart);
        } catch (Exception $e) {
            Response::error($e->getMessage(), 400);
        }
    }
}
