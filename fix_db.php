<?php
$mysqli = new mysqli('localhost', 'hbm_app', 'HBM_Dev_2026_Strong!', 'healthy_bharat_mission');
if ($mysqli->connect_error) {
    die('Connect Error (' . $mysqli->connect_errno . ') ' . $mysqli->connect_error);
}

// Check if column exists
$result = $mysqli->query("SHOW COLUMNS FROM articles LIKE 'read_time_minutes'");
if ($result->num_rows == 0) {
    echo "Adding read_time_minutes...\n";
    if ($mysqli->query("ALTER TABLE articles ADD COLUMN read_time_minutes INT DEFAULT 5 AFTER status")) {
        echo "Success.\n";
    } else {
        echo "Error: " . $mysqli->error . "\n";
    }
} else {
    echo "Column already exists.\n";
}

$mysqli->close();
