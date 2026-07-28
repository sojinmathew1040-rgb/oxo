<?php
date_default_timezone_set('Asia/Kolkata');
require_once __DIR__ . '/../includes/db.php';

$db = get_db_connection();
if (!$db) {
    echo "Error: Database connection failed.\n";
    exit(1);
}

$tables = [];
$stmt = $db->query("SHOW TABLES");
while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
    $tables[] = $row[0];
}

$output = "-- OXO Furniture Master Database Backup\n";
$output .= "-- Generated: " . date('Y-m-d H:i:s') . " IST\n";
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

$target_file = __DIR__ . '/../db/oxo_db.sql';
file_put_contents($target_file, $output);
echo "Successfully updated database dump in {$target_file} with timestamp: " . date('Y-m-d H:i:s') . " IST\n";
