<?php
require 'c:/laragon/www/GreenStep/api/config/database.php';
use App\Config\Database;

$allowed = [
    '127.0.0.1',
    '::1',
    'localhost'
];

if (!in_array($_SERVER['REMOTE_ADDR'] ?? '', $allowed) && !empty($_SERVER['REMOTE_ADDR'])) {
    http_response_code(403);
    die('Forbidden');
}

try {
    $db = Database::getConnection();
    $columns = $db->query("SHOW COLUMNS FROM Challenge")->fetchAll(PDO::FETCH_COLUMN);

    if (!in_array('target_category', $columns)) {
        $db->exec("ALTER TABLE Challenge ADD COLUMN target_category VARCHAR(50) NULL DEFAULT 'All' AFTER target_co2_reduction");
    }

    if (!in_array('target_activity_type_id', $columns)) {
        $db->exec("ALTER TABLE Challenge ADD COLUMN target_activity_type_id INT(10) UNSIGNED NULL AFTER target_category");
    }

    $db->exec("UPDATE Challenge SET target_category = 'All' WHERE target_category IS NULL");
    echo "Challenge columns updated successfully.";
} catch (PDOException $e) {
    http_response_code(500);
    echo "Error: " . $e->getMessage();
}
