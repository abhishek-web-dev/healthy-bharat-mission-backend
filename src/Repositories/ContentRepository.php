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

    
    public function createProgram(array $data): int {
        $stmt = $this->db->prepare("
            INSERT INTO programs (title, slug, description, price, is_free, image_url, is_active)
            VALUES (:title, :slug, :description, :price, :is_free, :image_url, :is_active)
        ");
        $stmt->execute([
            'title' => $data['title'],
            'slug' => $data['slug'],
            'description' => $data['description'] ?? null,
            'price' => $data['price'] ?? 0,
            'is_free' => $data['is_free'] ?? 0,
            'image_url' => $data['image_url'] ?? null,
            'is_active' => $data['is_active'] ?? 1
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function updateProgram(int $id, array $data): void {
        $fields = [];
        $params = ['id' => $id];
        $allowedFields = ['title', 'slug', 'description', 'price', 'is_free', 'image_url', 'is_active'];
        
        foreach ($allowedFields as $field) {
            if (array_key_exists($field, $data)) {
                $fields[] = "$field = :$field";
                $params[$field] = $data[$field];
            }
        }
        if (empty($fields)) return;
        $query = "UPDATE programs SET " . implode(', ', $fields) . " WHERE id = :id";
        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
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

    
    public function getArticleById(int $id): ?array {
        $stmt = $this->db->prepare("
            SELECT a.*, 
                   c.name as category_name, c.slug as category_slug,
                   u.first_name as author_first_name, u.last_name as author_last_name
            FROM articles a
            LEFT JOIN article_categories c ON a.category_id = c.id
            LEFT JOIN users u ON a.author_id = u.id
            WHERE a.id = ? AND a.deleted_at IS NULL
        ");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function createArticle(array $data, int $authorId): int {
        $stmt = $this->db->prepare("
            INSERT INTO articles (title, slug, excerpt, content, category_id, image_url, status, author_id, published_at, read_time_minutes)
            VALUES (:title, :slug, :excerpt, :content, :category_id, :image_url, :status, :author_id, :published_at, :read_time_minutes)
        ");
        $status = $data['status'] ?? 'draft';
        $stmt->execute([
            'title' => $data['title'],
            'slug' => $data['slug'],
            'excerpt' => $data['excerpt'] ?? null,
            'content' => $data['content'],
            'category_id' => !empty($data['category_id']) ? $data['category_id'] : null,
            'image_url' => $data['image_url'] ?? null,
            'status' => $status,
            'author_id' => $authorId,
            'published_at' => ($status === 'published') ? date('Y-m-d H:i:s') : null,
            'read_time_minutes' => $data['read_time_minutes'] ?? 5
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function updateArticle(int $id, array $data): void {
        $fields = [];
        $params = ['id' => $id];
        $allowedFields = ['title', 'slug', 'excerpt', 'content', 'category_id', 'image_url', 'status', 'read_time_minutes'];
        
        foreach ($allowedFields as $field) {
            if (array_key_exists($field, $data)) {
                $fields[] = "$field = :$field";
                if ($field === 'category_id' && empty($data[$field])) {
                    $params[$field] = null;
                } else {
                    $params[$field] = $data[$field];
                }
            }
        }
        if (array_key_exists('status', $data) && $data['status'] === 'published') {
            $fields[] = "published_at = COALESCE(published_at, NOW())";
        }
        
        if (empty($fields)) return;
        $query = "UPDATE articles SET " . implode(', ', $fields) . " WHERE id = :id";
        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
    }

    
    public function getAdminHealthConditions(int $page = 1, int $perPage = 50): array {
        $query = "SELECT id, name, slug, description, image_url, is_active FROM health_conditions ORDER BY name ASC";
        return $this->paginate($query, [], $page, $perPage);
    }

    public function createHealthCondition(array $data): int {
        $stmt = $this->db->prepare("
            INSERT INTO health_conditions (name, slug, description, image_url, is_active)
            VALUES (:name, :slug, :description, :image_url, :is_active)
        ");
        $stmt->execute([
            'name' => $data['name'],
            'slug' => $data['slug'],
            'description' => $data['description'] ?? null,
            'image_url' => $data['image_url'] ?? null,
            'is_active' => $data['is_active'] ?? 1
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function updateHealthCondition(int $id, array $data): void {
        $fields = [];
        $params = ['id' => $id];
        $allowedFields = ['name', 'slug', 'description', 'image_url', 'is_active'];
        
        foreach ($allowedFields as $field) {
            if (array_key_exists($field, $data)) {
                $fields[] = "$field = :$field";
                $params[$field] = $data[$field];
            }
        }
        
        if (empty($fields)) return;
        $query = "UPDATE health_conditions SET " . implode(', ', $fields) . " WHERE id = :id";
        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
    }

    
    public function getAdminInquiries(int $page = 1, int $perPage = 50): array {
        $query = "SELECT id, name, email, phone, subject, message, status, created_at FROM contact_inquiries ORDER BY created_at DESC";
        return $this->paginate($query, [], $page, $perPage);
    }

    public function updateInquiryStatus(int $id, string $status): void {
        $stmt = $this->db->prepare("UPDATE contact_inquiries SET status = :status WHERE id = :id");
        $stmt->execute(['status' => $status, 'id' => $id]);
    }

    public function getAdminSubscribers(int $page = 1, int $perPage = 50): array {
        $query = "SELECT id, email, is_active, created_at FROM newsletter_subscribers ORDER BY created_at DESC";
        return $this->paginate($query, [], $page, $perPage);
    }

    public function updateSubscriberStatus(int $id, int $is_active): void {
        $stmt = $this->db->prepare("UPDATE newsletter_subscribers SET is_active = :is_active WHERE id = :id");
        $stmt->execute(['is_active' => $is_active, 'id' => $id]);
    }

// =========================================================
    // FAQS

    public function getAdminFaqs(int $page = 1, int $perPage = 50): array {
        $query = "SELECT id, question, answer, category, is_active, sort_order FROM faqs ORDER BY sort_order ASC, id DESC";
        return $this->paginate($query, [], $page, $perPage);
    }

    public function createFaq(array $data): int {
        $stmt = $this->db->prepare("
            INSERT INTO faqs (question, answer, category, is_active, sort_order)
            VALUES (:question, :answer, :category, :is_active, :sort_order)
        ");
        $stmt->execute([
            'question' => $data['question'],
            'answer' => $data['answer'],
            'category' => $data['category'] ?? 'general',
            'is_active' => $data['is_active'] ?? 1,
            'sort_order' => $data['sort_order'] ?? 0
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function updateFaq(int $id, array $data): void {
        $fields = [];
        $params = ['id' => $id];
        $allowedFields = ['question', 'answer', 'category', 'is_active', 'sort_order'];
        
        foreach ($allowedFields as $field) {
            if (array_key_exists($field, $data)) {
                $fields[] = "$field = :$field";
                $params[$field] = $data[$field];
            }
        }
        
        if (empty($fields)) return;
        $query = "UPDATE faqs SET " . implode(', ', $fields) . " WHERE id = :id";
        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
    }

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

    // =========================================================
    // CONTACT OPTIONS
    // =========================================================
    public function getContactOptions(bool $activeOnly = false): array {
        $query = "SELECT * FROM contact_interest_options";
        if ($activeOnly) {
            $query .= " WHERE is_active = 1";
        }
        $query .= " ORDER BY sort_order ASC, id ASC";
        $stmt = $this->db->query($query);
        return $stmt->fetchAll();
    }

    public function createContactOption(array $data): int {
        $stmt = $this->db->prepare("
            INSERT INTO contact_interest_options (label, value, is_active, sort_order)
            VALUES (:label, :value, :is_active, :sort_order)
        ");
        $stmt->execute([
            'label' => $data['label'],
            'value' => $data['value'],
            'is_active' => $data['is_active'] ?? 1,
            'sort_order' => $data['sort_order'] ?? 0
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function updateContactOption(int $id, array $data): void {
        $fields = [];
        $params = ['id' => $id];
        $allowedFields = ['label', 'value', 'is_active', 'sort_order'];
        
        foreach ($allowedFields as $field) {
            if (array_key_exists($field, $data)) {
                $fields[] = "$field = :$field";
                $params[$field] = $data[$field];
            }
        }
        
        if (empty($fields)) return;
        $query = "UPDATE contact_interest_options SET " . implode(', ', $fields) . " WHERE id = :id";
        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
    }

    public function deleteContactOption(int $id): void {
        // Soft delete logic can be applied if needed, but the plan is to allow physical deletion if not used, or just let foreign keys (if any) handle it.
        // The instructions said "Prefer disable/archive". Admin UI should prefer toggling is_active.
        $stmt = $this->db->prepare("DELETE FROM contact_interest_options WHERE id = :id");
        $stmt->execute(['id' => $id]);
    }
}
