<?php

namespace HBM\Repositories;

use HBM\Core\Database;
use PDO;

class ContentRepository {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    // =========================================================
    // HELPER: Pagination
    // =========================================================
    private function paginate(string $query, array $params, int $page, int $perPage): array {
        $offset = ($page - 1) * $perPage;
        
        // Count total
        $countQuery = preg_replace('/SELECT .*? FROM/is', 'SELECT COUNT(*) FROM', $query);
        // Remove ORDER BY and LIMIT from count query if present simply
        $countQuery = preg_replace('/ORDER BY.*/is', '', $countQuery);
        $countStmt = $this->db->prepare($countQuery);
        $countStmt->execute($params);
        $total = (int)$countStmt->fetchColumn();

        // Get data
        $query .= " LIMIT $perPage OFFSET $offset";
        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        $data = $stmt->fetchAll();

        return [
            'data' => $data,
            'meta' => [
                'page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'total_pages' => ceil($total / $perPage)
            ]
        ];
    }

    // =========================================================
    // HEALTH CONDITIONS
    // =========================================================
    public function getHealthConditions(bool $activeOnly = true, int $page = 1, int $perPage = 12): array {
        $query = "SELECT id, name, slug, description, image_url, is_active FROM health_conditions";
        $params = [];
        if ($activeOnly) {
            $query .= " WHERE is_active = 1";
        }
        $query .= " ORDER BY name ASC";
        return $this->paginate($query, $params, $page, $perPage);
    }

    public function getHealthConditionBySlug(string $slug, bool $activeOnly = true): ?array {
        $query = "SELECT * FROM health_conditions WHERE slug = ?";
        if ($activeOnly) {
            $query .= " AND is_active = 1";
        }
        $stmt = $this->db->prepare($query);
        $stmt->execute([$slug]);
        return $stmt->fetch() ?: null;
    }

    // =========================================================
    // PROGRAMS
    // =========================================================
    public function getPrograms(bool $activeOnly = true, int $page = 1, int $perPage = 12): array {
        $query = "SELECT id, title, slug, description, price, is_free, image_url, is_active FROM programs";
        $params = [];
        if ($activeOnly) {
            $query .= " WHERE is_active = 1 AND deleted_at IS NULL";
        } else {
            $query .= " WHERE deleted_at IS NULL";
        }
        $query .= " ORDER BY created_at DESC";
        return $this->paginate($query, $params, $page, $perPage);
    }

    public function getProgramBySlug(string $slug, bool $activeOnly = true): ?array {
        $query = "SELECT * FROM programs WHERE slug = ? AND deleted_at IS NULL";
        if ($activeOnly) {
            $query .= " AND is_active = 1";
        }
        $stmt = $this->db->prepare($query);
        $stmt->execute([$slug]);
        $program = $stmt->fetch();

        if ($program) {
            $stmtMods = $this->db->prepare("SELECT id, title, module_order, video_url, content FROM program_modules WHERE program_id = ? ORDER BY module_order ASC");
            $stmtMods->execute([$program['id']]);
            $program['modules'] = $stmtMods->fetchAll();
        }

        return $program ?: null;
    }

    // =========================================================
    // ARTICLES & LIBRARY
    // =========================================================
    public function getArticles(array $filters = [], int $page = 1, int $perPage = 12): array {
        $query = "
            SELECT a.id, a.title, a.slug, a.excerpt, a.image_url, a.status, a.published_at, 
                   c.name as category_name, c.slug as category_slug,
                   u.first_name as author_first_name, u.last_name as author_last_name
            FROM articles a
            LEFT JOIN article_categories c ON a.category_id = c.id
            LEFT JOIN users u ON a.author_id = u.id
            WHERE a.deleted_at IS NULL
        ";
        $params = [];

        if (!empty($filters['status'])) {
            $query .= " AND a.status = ?";
            $params[] = $filters['status'];
        }
        
        if (!empty($filters['category'])) {
            $query .= " AND c.slug = ?";
            $params[] = $filters['category'];
        }

        if (!empty($filters['search'])) {
            $query .= " AND (a.title LIKE ? OR a.excerpt LIKE ?)";
            $params[] = "%{$filters['search']}%";
            $params[] = "%{$filters['search']}%";
        }

        // Tag filtering requires a subquery or join, keeping it simple for now
        // Assuming tags might be implemented later if needed

        $query .= " ORDER BY a.published_at DESC, a.created_at DESC";
        
        return $this->paginate($query, $params, $page, $perPage);
    }

    public function getArticleBySlug(string $slug, bool $publishedOnly = true): ?array {
        $query = "
            SELECT a.*, 
                   c.name as category_name, c.slug as category_slug,
                   u.first_name as author_first_name, u.last_name as author_last_name
            FROM articles a
            LEFT JOIN article_categories c ON a.category_id = c.id
            LEFT JOIN users u ON a.author_id = u.id
            WHERE a.slug = ? AND a.deleted_at IS NULL
        ";
        
        if ($publishedOnly) {
            $query .= " AND a.status = 'published'";
        }

        $stmt = $this->db->prepare($query);
        $stmt->execute([$slug]);
        return $stmt->fetch() ?: null;
    }

    public function getArticleCategories(): array {
        $stmt = $this->db->query("SELECT * FROM article_categories ORDER BY name ASC");
        return $stmt->fetchAll();
    }

    public function getArticleTags(): array {
        $stmt = $this->db->query("SELECT * FROM article_tags ORDER BY name ASC");
        return $stmt->fetchAll();
    }

    // =========================================================
    // FAQS
    // =========================================================
    public function getFaqs(bool $activeOnly = true): array {
        $query = "SELECT * FROM faqs";
        if ($activeOnly) {
            $query .= " WHERE is_active = 1";
        }
        $query .= " ORDER BY category ASC, sort_order ASC";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    // =========================================================
    // SUCCESS STORIES
    // =========================================================
    public function getSuccessStories(bool $activeOnly = true, int $page = 1, int $perPage = 12): array {
        $query = "SELECT id, name, story, image_url, video_url, is_verified, is_active FROM success_stories";
        $params = [];
        if ($activeOnly) {
            $query .= " WHERE is_active = 1";
        }
        $query .= " ORDER BY created_at DESC";
        return $this->paginate($query, $params, $page, $perPage);
    }

    // =========================================================
    // FORMS (Contact & Newsletter)
    // =========================================================
    public function createContactInquiry(array $data): void {
        $stmt = $this->db->prepare("
            INSERT INTO contact_inquiries (name, email, phone, subject, message)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $data['name'], $data['email'], $data['phone'] ?? null, 
            $data['subject'], $data['message']
        ]);
    }

    public function subscribeNewsletter(string $email): void {
        $stmt = $this->db->prepare("
            INSERT IGNORE INTO newsletter_subscribers (email) VALUES (?)
        ");
        $stmt->execute([$email]);
    }
}
