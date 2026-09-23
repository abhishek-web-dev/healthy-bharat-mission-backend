<?php
// Test for PDO backup logic
$pdo = new PDO('mysql:host=127.0.0.1;port=3306;dbname=hbm_db', 'root', '');
$tables = [];
$stmt = $pdo->query('SHOW TABLES');
while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
    $tables[] = $row[0];
}
echo "Found " . count($tables) . " tables.\n";
