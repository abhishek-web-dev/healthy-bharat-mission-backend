<?php

namespace HBM\Controllers;

use HBM\Services\ContentService;
use HBM\Helpers\Response;
use HBM\Helpers\Request;

/**
 * Foundation for Admin CMS Endpoints
 * All endpoints require Admin / SuperAdmin role via AuthMiddleware::handleAdmin()
 */
class AdminContentController {
    private ContentService $service;

    public function __construct() {
        $this->service = new ContentService();
    }

    // =========================================================
    // LISTING (Supports viewing drafts and inactive)
    // =========================================================
    
    public function listPrograms(): void {
        $page = max(1, (int)($_GET['page'] ?? 1));
        $result = $this->service->getPrograms(false, $page, 50); // false = include inactive
        Response::success("Admin: Programs retrieved", $result);
    }

    public function listArticles(): void {
        $page = max(1, (int)($_GET['page'] ?? 1));
        $filters = [
            'status' => $_GET['status'] ?? null, // Can be draft, published, etc.
        ];
        $result = $this->service->getArticles($filters, $page, 50);
        Response::success("Admin: Articles retrieved", $result);
    }

    // =========================================================
    // STUBS FOR CRUD
    // Full validation logic would be implemented per entity
    // =========================================================
    
    public function createProgram(): void {
        try {
            global $authUser;
            $data = Request::getJson();
            $id = $this->service->createProgram($data);
            \HBM\Services\AdminActivityLogService::log($authUser['id'] ?? 0, 'PROGRAM_CREATED', 'programs', $id);
            Response::success("Program created successfully", ["id" => $id], 201);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), 400);
        }
    }

    public function updateProgram(string $id): void {
        try {
            global $authUser;
            $data = Request::getJson();
            $this->service->updateProgram((int)$id, $data);
            \HBM\Services\AdminActivityLogService::log($authUser['id'] ?? 0, 'PROGRAM_UPDATED', 'programs', (int)$id);
            Response::success("Program updated successfully");
        } catch (\Exception $e) {
            Response::error($e->getMessage(), 400);
        }
    }

    public function deleteProgram(string $id): void {
        // TODO: Soft Delete via Repository
        Response::success("Admin: Program deleted stub", []);
    }

    public function createArticle(): void {
        try {
            global $authUser;
            $data = Request::getJson();
            $id = $this->service->createArticle($data, (int)$authUser["id"]);
            \HBM\Services\AdminActivityLogService::log($authUser['id'] ?? 0, 'ARTICLE_CREATED', 'articles', $id);
            Response::success("Article created successfully", ["id" => $id], 201);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), 400);
        }
    }

    // ... Other CRUD methods for FAQs, Success Stories, Health Conditions
    
    // View inquiries / subscribers
    
    public function getArticle(string $id): void {
        try {
            $article = $this->service->getArticleById((int)$id);
            Response::success("Article retrieved", $article);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), 404);
        }
    }

    public function updateArticle(string $id): void {
        try {
            global $authUser;
            $data = Request::getJson();
            $this->service->updateArticle((int)$id, $data);
            \HBM\Services\AdminActivityLogService::log($authUser['id'] ?? 0, 'ARTICLE_UPDATED', 'articles', (int)$id);
            Response::success("Article updated successfully");
        } catch (\Exception $e) {
            Response::error($e->getMessage(), 400);
        }
    }

    
    public function listHealthConditions(): void {
        try {
            $page = (int)($_GET['page'] ?? 1);
            $perPage = (int)($_GET['per_page'] ?? 50);
            $result = $this->service->getAdminHealthConditions($page, $perPage);
            Response::success("Health conditions retrieved", $result);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), 400);
        }
    }

    public function createHealthCondition(): void {
        try {
            global $authUser;
            $data = Request::getJson();
            $id = $this->service->createHealthCondition($data);
            \HBM\Services\AdminActivityLogService::log($authUser['id'] ?? 0, 'HEALTH_CONDITION_CREATED', 'health_conditions', $id);
            Response::success("Health condition created", ["id" => $id], 201);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), 400);
        }
    }

    public function updateHealthCondition(string $id): void {
        try {
            global $authUser;
            $data = Request::getJson();
            $this->service->updateHealthCondition((int)$id, $data);
            \HBM\Services\AdminActivityLogService::log($authUser['id'] ?? 0, 'HEALTH_CONDITION_UPDATED', 'health_conditions', (int)$id);
            Response::success("Health condition updated");
        } catch (\Exception $e) {
            Response::error($e->getMessage(), 400);
        }
    }

    
    public function listFaqs(): void {
        try {
            $page = (int)($_GET['page'] ?? 1);
            $perPage = (int)($_GET['per_page'] ?? 50);
            $result = $this->service->getAdminFaqs($page, $perPage);
            Response::success("FAQs retrieved", $result);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), 400);
        }
    }

    public function createFaq(): void {
        try {
            global $authUser;
            $data = Request::getJson();
            $id = $this->service->createFaq($data);
            \HBM\Services\AdminActivityLogService::log($authUser['id'] ?? 0, 'FAQ_CREATED', 'faqs', $id);
            Response::success("FAQ created", ["id" => $id], 201);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), 400);
        }
    }

    public function updateFaq(string $id): void {
        try {
            global $authUser;
            $data = Request::getJson();
            $this->service->updateFaq((int)$id, $data);
            \HBM\Services\AdminActivityLogService::log($authUser['id'] ?? 0, 'FAQ_UPDATED', 'faqs', (int)$id);
            Response::success("FAQ updated");
        } catch (\Exception $e) {
            Response::error($e->getMessage(), 400);
        }
    }

    public function listInquiries(): void {
        try {
            $page = (int)($_GET['page'] ?? 1);
            $perPage = (int)($_GET['per_page'] ?? 50);
            $result = $this->service->getAdminInquiries($page, $perPage);
            Response::success("Admin: Contact Inquiries retrieved", $result);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), 400);
        }
    }

    public function getInquiry(string $id): void {
        try {
            $result = $this->service->getInquiryById((int)$id);
            Response::success("Admin: Inquiry retrieved", $result);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), 404);
        }
    }

    public function updateInquiryStatus(string $id): void {
        try {
            global $authUser;
            $data = Request::getJson();
            $status = $data['status'] ?? '';
            if (empty($status)) {
                throw new \Exception("Status is required");
            }
            $this->service->updateInquiryStatus((int)$id, $status);
            \HBM\Services\AdminActivityLogService::log($authUser['id'] ?? 0, 'INQUIRY_STATUS_UPDATED', 'contact_inquiries', (int)$id);
            Response::success("Inquiry status updated successfully");
        } catch (\Exception $e) {
            Response::error($e->getMessage(), 400);
        }
    }

    public function listSubscribers(): void {
        // TODO: Fetch from Repository
        Response::success("Admin: Newsletter Subscribers retrieved", []);
    }
}
