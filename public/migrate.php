<?php
// One-time migration script - DELETE THIS FILE AFTER RUNNING!
$url = parse_url(getenv('MYSQL_URL') ?: 'mysql://root:@127.0.0.1:3306/railway');
$host = $url['host'];
$port = $url['port'] ?? 3306;
$user = $url['user'];
$pass = $url['pass'] ?? '';
$db   = ltrim($url['path'], '/');

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$db", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $files = [
        '../database/schema.sql',
        '../database/seeds/01_roles.sql',
        '../database/seeds/02_users.sql',
    ];

    foreach ($files as $file) {
        $sql = file_get_contents(__DIR__ . '/' . $file);
        // Split on semicolons but keep stored procedures intact
        $statements = array_filter(array_map('trim', explode(';', $sql)));
        foreach ($statements as $stmt) {
            if (!empty($stmt)) {
                $pdo->exec($stmt);
            }
        }
        echo "✅ Imported: $file<br>";
    }
    echo "<br><strong>✅ All done! Database is ready. Now delete this file.</strong>";
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage();
}
