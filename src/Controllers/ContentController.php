<?php

namespace HBM\Controllers;

use HBM\Services\ContentService;
use HBM\Helpers\Response;
use HBM\Helpers\Request;
use Exception;

class ContentController {
    private ContentService $service;

    public function __construct() {
        $this->service = new ContentService();
    }

    private function getPageParams(): array {
        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = max(1, min(50, (int)($_GET['per_page'] ?? 12)));
        return [$page, $perPage];
    }

    // Health Conditions
    public function getHealthConditions(): void {
        [$page, $perPage] = $this->getPageParams();
        $result = $this->service->getHealthConditions(true, $page, $perPage);
        Response::success("Health conditions retrieved", $result['data'], 200);
        // Note: For a clean response, could attach meta separately or merge, but adapting to standard:
        // A better approach for pagination in our response format:
        // Response::json(['success'=>true, 'message'=>'...', 'data'=>$result['data'], 'meta'=>$result['meta']]);
    }

    // Custom response to include meta
    private function paginatedResponse(string $message, array $result): void {
        echo json_encode([
            'success' => true,
            'message' => $message,
            'data' => $result['data'],
            'meta' => $result['meta']
        ]);
        exit;
    }

    public function getHealthConditionsPaginated(): void {
        header('Content-Type: application/json; charset=utf-8');
        [$page, $perPage] = $this->getPageParams();
        $result = $this->service->getHealthConditions(true, $page, $perPage);
        $this->paginatedResponse("Health conditions retrieved", $result);
    }

    public function getHealthConditionBySlug(string $slug): void {
        try {
            $condition = $this->service->getHealthConditionBySlug($slug, true);
            Response::success("Health condition retrieved", $condition);
        } catch (Exception $e) {
            Response::error($e->getMessage(), $e->getCode() ?: 404);
        }
    }

    // Programs
    public function getPrograms(): void {
        header('Content-Type: application/json; charset=utf-8');
        [$page, $perPage] = $this->getPageParams();
        $result = $this->service->getPrograms(true, $page, $perPage);
        $this->paginatedResponse("Programs retrieved", $result);
    }

    public function getProgramBySlug(string $slug): void {
        try {
            $program = $this->service->getProgramBySlug($slug, true);
            Response::success("Program retrieved", $program);
        } catch (Exception $e) {
            Response::error($e->getMessage(), $e->getCode() ?: 404);
        }
    }

    // Articles
    public function getArticles(): void {
        header('Content-Type: application/json; charset=utf-8');
        [$page, $perPage] = $this->getPageParams();
        
        $filters = [
            'status' => 'published',
            'category' => $_GET['category'] ?? null,
            'search' => $_GET['search'] ?? null,
        ];

        $result = $this->service->getArticles($filters, $page, $perPage);
        $this->paginatedResponse("Articles retrieved", $result);
    }

    public function getArticleBySlug(string $slug): void {
        try {
            $article = $this->service->getArticleBySlug($slug, true);
            Response::success("Article retrieved", $article);
        } catch (Exception $e) {
            Response::error($e->getMessage(), $e->getCode() ?: 404);
        }
    }

    public function getArticleCategories(): void {
        $categories = $this->service->getArticleCategories();
        Response::success("Categories retrieved", $categories);
    }

    public function getArticleTags(): void {
        $tags = $this->service->getArticleTags();
        Response::success("Tags retrieved", $tags);
    }

    // FAQs
    public function getFaqs(): void {
        $faqs = $this->service->getFaqs(true);
        Response::success("FAQs retrieved", $faqs);
    }

    // Success Stories
    public function getSuccessStories(): void {
        header('Content-Type: application/json; charset=utf-8');
        [$page, $perPage] = $this->getPageParams();
        $result = $this->service->getSuccessStories(true, $page, $perPage);
        $this->paginatedResponse("Success stories retrieved", $result);
    }

    // Contact
    public function submitContact(): void {
        try {
            $data = Request::getJson();
            $this->service->submitContactInquiry($data);
            Response::success("Thank you. Your inquiry has been submitted.", [], 201);
        } catch (Exception $e) {
            Response::error($e->getMessage(), $e->getCode() ?: 400);
        }
    }

    // Newsletter
    public function subscribeNewsletter(): void {
        try {
            $data = Request::getJson();
            $email = $data['email'] ?? '';
            $this->service->subscribeNewsletter($email);
            // Ignore duplicates silently
            Response::success("Thank you for subscribing!", [], 201);
        } catch (Exception $e) {
            Response::error($e->getMessage(), $e->getCode() ?: 400);
        }
    }
}
