<?php
/**
 * Database Exporter for OXO Furniture (Admin Tool)
 */
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../includes/db.php';

// Force authentication
require_admin_login();

$db = get_db_connection();

if (!$db) {
    die("Error: Could not connect to MySQL database.");
}

$tables = [];
$stmt = $db->query("SHOW TABLES");
while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
    $tables[] = $row[0];
}

$output = "-- OXO Furniture Database Backup\n";
$output .= "-- Generated: " . date('Y-m-d H:i:s') . "\n";
$output .= "-- Host: localhost | Database: oxo_db\n\n";
$output .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

foreach ($tables as $table) {
    $output .= "-- --------------------------------------------------------\n";
    $output .= "-- Table structure for table `{$table}`\n";
    $output .= "-- --------------------------------------------------------\n";
    $output .= "DROP TABLE IF EXISTS `{$table}`;\n";
    
    $row2 = $db->query("SHOW CREATE TABLE `{$table}`")->fetch(PDO::FETCH_NUM);
    $output .= $row2[1] . ";\n\n";
    
    $rows = $db->query("SELECT * FROM `{$table}`")->fetchAll(PDO::FETCH_ASSOC);
    if (!empty($rows)) {
        $output .= "-- Dumping data for table `{$table}` (" . count($rows) . " rows)\n";
        foreach ($rows as $row) {
            $cols = array_keys($row);
            $escaped_cols = array_map(function($c) { return "`$c`"; }, $cols);
            $escaped_vals = array_map(function($v) use ($db) {
                if ($v === null) return "NULL";
                return $db->quote($v);
            }, array_values($row));
            
            $output .= "INSERT INTO `{$table}` (" . implode(", ", $escaped_cols) . ") VALUES (" . implode(", ", $escaped_vals) . ");\n";
        }
        $output .= "\n";
    }
}

$output .= "SET FOREIGN_KEY_CHECKS=1;\n";

$db_dir = __DIR__ . '/../db/';
if (!file_exists($db_dir)) {
    mkdir($db_dir, 0777, true);
}

// Remove any secondary dated backup files so ONLY single oxo_db.sql file remains
foreach (glob($db_dir . '*.sql') as $file) {
    if (basename($file) !== 'oxo_db.sql') {
        @unlink($file);
    }
}

// Update single main backup file: oxo_db.sql
file_put_contents($db_dir . 'oxo_db.sql', $output);

// Direct file download
header('Content-Type: application/sql');
header('Content-Disposition: attachment; filename="oxo_db.sql"');
header('Content-Length: ' . strlen($output));
header('Cache-Control: must-revalidate');
header('Pragma: public');

echo $output;
exit;

