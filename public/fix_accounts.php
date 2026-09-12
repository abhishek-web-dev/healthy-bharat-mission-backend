<?php
// Temporary account fix script - DELETE AFTER USE

// Use same connection logic as Database.php
$mysqlUrl = getenv('MYSQL_URL');
if (!empty($mysqlUrl)) {
    $parsedUrl = parse_url($mysqlUrl);
    $host = $parsedUrl['host'] ?? '127.0.0.1';
    $port = $parsedUrl['port'] ?? '3306';
    $db   = ltrim($parsedUrl['path'] ?? '/railway', '/');
    $user = $parsedUrl['user'] ?? 'root';
    $pass = $parsedUrl['pass'] ?? '';
} else {
    $host = getenv('MYSQLHOST') ?: '127.0.0.1';
    $port = getenv('MYSQLPORT') ?: '3306';
    $db   = getenv('MYSQLDATABASE') ?: getenv('MYSQL_DATABASE') ?: 'railway';
    $user = getenv('MYSQLUSER') ?: 'root';
    $pass = getenv('MYSQLPASSWORD') ?: '';
}

if ($host === 'localhost') $host = '127.0.0.1';

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    // Activate ALL inactive (pending) user accounts
    $stmt = $pdo->prepare("UPDATE users SET status = 'active' WHERE status = 'inactive'");
    $stmt->execute();
    $count = $stmt->rowCount();

    // Show all users
    $users = $pdo->query("SELECT id, first_name, last_name, email, status FROM users ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);

    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'activated_count' => $count,
        'message' => "Activated $count accounts.",
        'all_users' => $users,
        'db_used' => $db,
        'host_used' => $host
    ], JSON_PRETTY_PRINT);
} catch (Exception $e) {
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage(), 'db' => $db, 'host' => $host, 'user' => $user]);
}
