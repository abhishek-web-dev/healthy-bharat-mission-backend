<?php
// Temporary account fix script - DELETE AFTER USE
$host = getenv('MYSQLHOST') ?: getenv('DB_HOST') ?: 'localhost';
$port = getenv('MYSQLPORT') ?: getenv('DB_PORT') ?: '3306';
$db   = getenv('MYSQL_DATABASE') ?: getenv('DB_DATABASE') ?: 'railway';
$user = getenv('MYSQLUSER') ?: getenv('DB_USERNAME') ?: 'root';
$pass = getenv('MYSQLPASSWORD') ?: getenv('DB_PASSWORD') ?: '';

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

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
        'all_users' => $users
    ], JSON_PRETTY_PRINT);
} catch (Exception $e) {
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
