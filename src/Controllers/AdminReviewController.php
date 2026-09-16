<?php

namespace HBM\Controllers;

use HBM\Repositories\AdminReviewRepository;
use HBM\Helpers\Response;
use HBM\Services\AdminActivityLogService;
use Exception;

class AdminReviewController {
    private AdminReviewRepository $repo;

    public function __construct() {
        $this->repo = new AdminReviewRepository();
    }

    public function getReviewCounts(): void {
        try {
            $counts = $this->repo->getReviewCounts();
            Response::success('Review counts fetched successfully', ['counts' => $counts]);
        } catch (Exception $e) {
            Response::error($e->getMessage(), 500);
        }
    }

    public function getReviews(): void {
        try {
            $filters = [
                'status' => $_GET['status'] ?? 'all',
                'search' => $_GET['search'] ?? '',
                'limit'  => isset($_GET['limit']) ? (int)$_GET['limit'] : 50,
                'offset' => isset($_GET['offset']) ? (int)$_GET['offset'] : 0,
            ];
            
            $reviews = $this->repo->getReviews($filters);
            Response::success('Reviews fetched successfully', ['reviews' => $reviews]);
        } catch (Exception $e) {
            Response::error($e->getMessage(), 500);
        }
    }

    public function getReviewById(int $id): void {
        try {
            $review = $this->repo->getReviewById($id);
            if (!$review) {
                Response::error("Review not found", 404);
            }
            Response::success('Review fetched successfully', ['review' => $review]);
        } catch (Exception $e) {
            Response::error($e->getMessage(), 500);
        }
    }

    public function updateReviewStatus(int $id): void {
        global $authUser;
        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        
        try {
            if (empty($input['status']) || !in_array($input['status'], ['approved', 'rejected'])) {
                throw new Exception("Invalid status. Must be 'approved' or 'rejected'.");
            }
            
            $this->repo->updateReviewStatus($id, $input['status']);
            
            AdminActivityLogService::log(
                $authUser['id'], 
                'REVIEW_' . strtoupper($input['status']), 
                'product_reviews', 
                $id
            );
            
            Response::success('Review ' . $input['status'] . ' successfully');
        } catch (Exception $e) {
            Response::error($e->getMessage(), 400);
        }
    }
}
