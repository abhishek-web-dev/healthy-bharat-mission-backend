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
            $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
            $emailSent = $this->service->submitContactInquiry($data, $ip);
            
            if ($emailSent) {
                Response::success("Thank you. Your inquiry has been submitted and our team has been notified.", ['email_sent' => true], 201);
            } else {
                Response::success("Thank you. Your inquiry has been saved successfully, but we are experiencing a slight delay with email notifications. Our team will review it shortly.", ['email_sent' => false], 201);
            }
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

    // Contact Options
    public function getContactOptions(): void {
        $options = $this->service->getContactOptions(true);
        Response::success("Contact options retrieved", $options);
    }

    public function getAdminContactOptions(): void {
        $options = $this->service->getContactOptions(false);
        Response::success("Admin contact options retrieved", $options);
    }

    public function createContactOption(): void {
        try {
            $data = Request::getJson();
            $id = $this->service->createContactOption($data);
            Response::success("Contact option created", ['id' => $id], 201);
        } catch (Exception $e) {
            Response::error($e->getMessage(), $e->getCode() ?: 400);
        }
    }

    public function updateContactOption(int $id): void {
        try {
            $data = Request::getJson();
            $this->service->updateContactOption($id, $data);
            Response::success("Contact option updated");
        } catch (Exception $e) {
            Response::error($e->getMessage(), $e->getCode() ?: 400);
        }
    }

    public function deleteContactOption(int $id): void {
        try {
            $this->service->deleteContactOption($id);
            Response::success("Contact option deleted");
        } catch (Exception $e) {
            Response::error($e->getMessage(), $e->getCode() ?: 400);
        }
    }
}
