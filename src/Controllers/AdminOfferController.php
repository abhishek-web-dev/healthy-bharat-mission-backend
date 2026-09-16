<?php

namespace HBM\Controllers;

use HBM\Repositories\AdminOfferRepository;
use HBM\Helpers\Response;
use HBM\Services\AdminActivityLogService;
use Exception;

class AdminOfferController {
    private AdminOfferRepository $repo;

    public function __construct() {
        $this->repo = new AdminOfferRepository();
    }

    public function getOffers(): void {
        try {
            $filters = [
                'status' => $_GET['status'] ?? 'all',
                'search' => $_GET['search'] ?? '',
                'limit'  => isset($_GET['limit']) ? (int)$_GET['limit'] : 50,
                'offset' => isset($_GET['offset']) ? (int)$_GET['offset'] : 0,
            ];
            
            $offers = $this->repo->getOffers($filters);
            Response::success('Offers fetched successfully', ['offers' => $offers]);
        } catch (Exception $e) {
            Response::error($e->getMessage(), 500);
        }
    }

    public function getOfferById(int $id): void {
        try {
            $offer = $this->repo->getOfferById($id);
            if (!$offer) {
                Response::error("Offer not found", 404);
            }
            Response::success('Offer fetched successfully', ['offer' => $offer]);
        } catch (Exception $e) {
            Response::error($e->getMessage(), 500);
        }
    }

    public function createOffer(): void {
        global $authUser;
        $input = json_decode(file_get_contents('php://input'), true) ?? [];

        try {
            if (empty($input['name']) || empty($input['discount_type']) || !isset($input['discount_value'])) {
                throw new Exception("Name, discount type, and discount value are required.");
            }

            $offerId = $this->repo->createOffer($input);

            AdminActivityLogService::log(
                $authUser['id'], 
                'OFFER_CREATED', 
                'offers', 
                $offerId
            );

            Response::success('Offer created successfully', ['id' => $offerId]);
        } catch (Exception $e) {
            if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                Response::error("Offer code must be unique.", 400);
            }
            Response::error($e->getMessage(), 400);
        }
    }

    public function updateOffer(int $id): void {
        global $authUser;
        $input = json_decode(file_get_contents('php://input'), true) ?? [];

        try {
            if (empty($input['name']) || empty($input['discount_type']) || !isset($input['discount_value'])) {
                throw new Exception("Name, discount type, and discount value are required.");
            }

            $this->repo->updateOffer($id, $input);

            AdminActivityLogService::log(
                $authUser['id'], 
                'OFFER_UPDATED', 
                'offers', 
                $id
            );

            Response::success('Offer updated successfully');
        } catch (Exception $e) {
            if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                Response::error("Offer code must be unique.", 400);
            }
            Response::error($e->getMessage(), 400);
        }
    }

    public function deleteOffer(int $id): void {
        global $authUser;
        try {
            $this->repo->deleteOffer($id);

            AdminActivityLogService::log(
                $authUser['id'], 
                'OFFER_DELETED', 
                'offers', 
                $id
            );

            Response::success('Offer deleted successfully');
        } catch (Exception $e) {
            Response::error($e->getMessage(), 400);
        }
    }
}
