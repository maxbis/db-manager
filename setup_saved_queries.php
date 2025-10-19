<?php
/**
 * Setup Script for Saved Queries Feature
 * Run this once to create the saved_queries table
 */

// IP Authorization Check
require_once 'login/auth_check.php';
require_once 'db_config.php';

try {
    $conn = getDbConnection();
    
    // Create saved_queries table
    $sql = "CREATE TABLE IF NOT EXISTS `saved_queries` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `query_name` VARCHAR(255) NOT NULL,
        `query_sql` TEXT NOT NULL,
        `table_name` VARCHAR(255) DEFAULT NULL,
        `description` TEXT DEFAULT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `last_used_at` TIMESTAMP NULL DEFAULT NULL,
        `use_count` INT DEFAULT 0,
        INDEX `idx_table_name` (`table_name`),
        INDEX `idx_created_at` (`created_at`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    if ($conn->query($sql)) {
        echo "✅ Success! The 'saved_queries' table has been created.\n";
        echo "You can now save and load SQL queries in the Query Builder.\n";
    } else {
        echo "❌ Error creating table: " . $conn->error . "\n";
    }
    
    closeDbConnection($conn);
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>

