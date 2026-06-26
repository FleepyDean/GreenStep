<?php
declare(strict_types=1);

require_once __DIR__ . '/../../api/vendor/autoload.php';

use App\Config\Database;

try {
    $db = Database::getConnection();

    $columns = $db->query("SHOW COLUMNS FROM User")->fetchAll(PDO::FETCH_COLUMN);

    if (!in_array('target_reduction_percent', $columns)) {
        $db->exec("ALTER TABLE User
            ADD COLUMN target_reduction_percent DECIMAL(5,2) DEFAULT 20.00 AFTER role,
            ADD COLUMN goal_duration_days INT DEFAULT 30 AFTER target_reduction_percent,
            ADD COLUMN goal_start_date DATE DEFAULT NULL AFTER goal_duration_days,
            ADD COLUMN baseline_footprint DECIMAL(10,4) DEFAULT NULL AFTER goal_start_date");
        echo "Goal columns added to User table.\n";
    } else {
        echo "Goal columns already exist.\n";
    }

    echo "Migration complete.\n";
} catch (PDOException $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
