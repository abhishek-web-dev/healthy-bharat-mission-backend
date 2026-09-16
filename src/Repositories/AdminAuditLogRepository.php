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

        $whereClause = "WHERE 1=1";
        $params = [];

        $joinClause = "";
        if (!empty($search)) {
            $joinClause = "LEFT JOIN users u ON l.user_id = u.id";
            $whereClause .= " AND (u.first_name LIKE ? OR u.last_name LIKE ? OR u.email LIKE ? OR l.action LIKE ? OR l.entity_type LIKE ?)";
            $searchStr = "%$search%";
            $params = array_merge($params, [$searchStr, $searchStr, $searchStr, $searchStr, $searchStr]);
        }

        if (!empty($actionFilter)) {
            $whereClause .= " AND l.action = ?";
            $params[] = $actionFilter;
        }

        // 1. Efficient Count Query (No Join if not searching users)
        $countQuery = "SELECT COUNT(l.id) FROM admin_activity_logs l $joinClause $whereClause";
        $stmtCount = $db->prepare($countQuery);
        $stmtCount->execute($params);
        $total = (int)$stmtCount->fetchColumn();

        // 2. Deferred Join for Pagination (Solves Error 1038 Out of sort memory)
        $paginatedIdsQuery = "
            SELECT l.id 
            FROM admin_activity_logs l 
            $joinClause 
            $whereClause 
            ORDER BY l.id DESC 
            LIMIT $perPage OFFSET $offset
        ";
        
        $stmtIds = $db->prepare($paginatedIdsQuery);
        $stmtIds->execute($params);
        $ids = $stmtIds->fetchAll(PDO::FETCH_COLUMN);
        
        $data = [];
        if (!empty($ids)) {
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $finalQuery = "
                SELECT l.id, l.user_id, l.action, l.entity_type, l.entity_id, l.details, l.ip_address, l.created_at, 
                       u.first_name, u.last_name, u.email 
                FROM admin_activity_logs l 
                LEFT JOIN users u ON l.user_id = u.id 
                WHERE l.id IN ($placeholders)
                ORDER BY l.id DESC
            ";
            
            $stmtFinal = $db->prepare($finalQuery);
            $stmtFinal->execute($ids);
            $data = $stmtFinal->fetchAll(PDO::FETCH_ASSOC);

            // Decode JSON details securely
            foreach ($data as &$row) {
                if ($row['details']) {
                    $row['details'] = json_decode($row['details'], true);
                }
            }
        }

        return [
            'data' => $data,
            'pagination' => [
                'total' => $total,
                'per_page' => $perPage,
                'current_page' => $page,
                'total_pages' => ceil($total / $perPage) ?: 1
            ]
        ];
    }
}
