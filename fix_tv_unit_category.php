<?php
/**
 * Auto-heal script for OXO database categories
 * Ensures 'tv-units' category exists and reclassifies imported TV Unit products
 */

require_once __DIR__ . '/includes/db.php';

$db = get_db_connection();
if (!$db) {
    echo "Database connection offline.\n";
    exit;
}

try {
    // 1. Ensure 'tv-units' category exists in `oxo_categories` table
    $stmt = $db->prepare("SELECT COUNT(*) FROM `oxo_categories` WHERE `slug` = 'tv-units'");
    $stmt->execute();
    if ($stmt->fetchColumn() == 0) {
        $ins = $db->prepare("INSERT INTO `oxo_categories` (`slug`, `name`, `bg_color`) VALUES ('tv-units', 'TV Units', 'rgba(95, 173, 138, 0.03)')");
        $ins->execute();
        echo "Created 'tv-units' category in database.\n";
    } else {
        echo "'tv-units' category already exists.\n";
    }

    // 2. Reclassify 'www-walton-tv-unit' and all TV unit / TV stand products to 'tv-units'
    $up = $db->prepare("UPDATE `oxo_products` SET `category` = 'tv-units' WHERE `id` = 'www-walton-tv-unit' OR LOWER(`title`) LIKE '%tv unit%' OR LOWER(`title`) LIKE '%tv cabinet%' OR LOWER(`title`) LIKE '%tv stand%' OR LOWER(`id`) LIKE '%tv-unit%'");
    $up->execute();
    $affected = $up->rowCount();
    echo "Updated $affected product(s) to 'tv-units' category.\n";

} catch (\Exception $e) {
    echo "Error updating database: " . $e->getMessage() . "\n";
}
?>
