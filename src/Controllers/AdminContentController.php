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
        $data = Request::getJson();
        // TODO: Validate & Insert via Repository
        Response::success("Admin: Program created stub", [], 201);
    }

    public function updateProgram(string $id): void {
        $data = Request::getJson();
        // TODO: Validate & Update via Repository
        Response::success("Admin: Program updated stub", []);
    }

    public function deleteProgram(string $id): void {
        // TODO: Soft Delete via Repository
        Response::success("Admin: Program deleted stub", []);
    }

    public function createArticle(): void {
        $data = Request::getJson();
        global $authUser;
        $data['author_id'] = $authUser['id'];
        // TODO: Validate & Insert via Repository
        Response::success("Admin: Article created stub", [], 201);
    }

    // ... Other CRUD methods for FAQs, Success Stories, Health Conditions
    
    // View inquiries / subscribers
    public function listInquiries(): void {
        // TODO: Fetch from Repository
        Response::success("Admin: Contact Inquiries retrieved", []);
    }

    public function listSubscribers(): void {
        // TODO: Fetch from Repository
        Response::success("Admin: Newsletter Subscribers retrieved", []);
    }
}
