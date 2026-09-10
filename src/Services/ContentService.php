<?php

namespace HBM\Services;

use HBM\Repositories\ContentRepository;
use Exception;

class ContentService {
    private ContentRepository $repo;

    public function __construct() {
        $this->repo = new ContentRepository();
    }

    public function getHealthConditions(bool $public = true, int $page = 1, int $perPage = 12): array {
        return $this->repo->getHealthConditions($public, $page, $perPage);
    }

    public function getHealthConditionBySlug(string $slug, bool $public = true): array {
        $condition = $this->repo->getHealthConditionBySlug($slug, $public);
        if (!$condition) {
            throw new Exception("Health condition not found", 404);
        }
        return $condition;
    }

    public function getPrograms(bool $public = true, int $page = 1, int $perPage = 12): array {
        return $this->repo->getPrograms($public, $page, $perPage);
    }

    public function getProgramBySlug(string $slug, bool $public = true): array {
        $program = $this->repo->getProgramBySlug($slug, $public);
        if (!$program) {
            throw new Exception("Program not found", 404);
        }
        return $program;
    }

    public function getArticles(array $filters = [], int $page = 1, int $perPage = 12): array {
        return $this->repo->getArticles($filters, $page, $perPage);
    }

    public function getArticleBySlug(string $slug, bool $public = true): array {
        $article = $this->repo->getArticleBySlug($slug, $public);
        if (!$article) {
            throw new Exception("Article not found", 404);
        }
        return $article;
    }

    public function getArticleCategories(): array {
        return $this->repo->getArticleCategories();
    }

    public function getArticleTags(): array {
        return $this->repo->getArticleTags();
    }

    public function getFaqs(bool $public = true): array {
        return $this->repo->getFaqs($public);
    }

    public function getSuccessStories(bool $public = true, int $page = 1, int $perPage = 12): array {
        return $this->repo->getSuccessStories($public, $page, $perPage);
    }

    public function submitContactInquiry(array $data): void {
        if (empty($data['name']) || empty($data['email']) || empty($data['subject']) || empty($data['message'])) {
            throw new Exception("Please fill all required fields.", 422);
        }

        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Invalid email address.", 422);
        }

        // Basic sanitize
        $data['message'] = htmlspecialchars(strip_tags($data['message']));
        
        $this->repo->createContactInquiry($data);
    }

    public function subscribeNewsletter(string $email): void {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Invalid email address.", 422);
        }
        $this->repo->subscribeNewsletter($email);
    }
}
