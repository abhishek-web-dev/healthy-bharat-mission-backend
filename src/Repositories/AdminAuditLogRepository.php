<?php
namespace HBM\Repositories;

use HBM\Core\Database;
use PDO;

class AdminAuditLogRepository {
    private function getDb(): PDO {
        return Database::getConnection();
    }

    public function getLogs(int $page = 1, int $perPage = 50, string $search = '', string $actionFilter = ''): array {
        $db = $this->getDb();
        $offset = ($page - 1) * $perPage;

        $query = "SELECT l.*, u.first_name, u.last_name, u.email 
                  FROM admin_activity_logs l 
                  LEFT JOIN users u ON l.user_id = u.id 
                  WHERE 1=1";
        $params = [];

        if (!empty($search)) {
            $query .= " AND (u.first_name LIKE ? OR u.last_name LIKE ? OR u.email LIKE ? OR l.action LIKE ? OR l.entity_type LIKE ?)";
            $searchStr = "%$search%";
            $params = array_merge($params, [$searchStr, $searchStr, $searchStr, $searchStr, $searchStr]);
        }

        if (!empty($actionFilter)) {
            $query .= " AND l.action = ?";
            $params[] = $actionFilter;
        }

        $countQuery = preg_replace('/SELECT .* FROM/', 'SELECT COUNT(*) FROM', $query);
        $stmtCount = $db->prepare($countQuery);
        $stmtCount->execute($params);
        $total = $stmtCount->fetchColumn();

        $query .= " ORDER BY l.created_at DESC LIMIT $perPage OFFSET $offset";
        $stmt = $db->prepare($query);
        $stmt->execute($params);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Decode JSON details securely
        foreach ($data as &$row) {
            if ($row['details']) {
                $row['details'] = json_decode($row['details'], true);
            }
        }

        return [
            'data' => $data,
            'pagination' => [
                'total' => (int)$total,
                'per_page' => $perPage,
                'current_page' => $page,
                'total_pages' => ceil($total / $perPage)
            ]
        ];
    }
}
