<?php
/**
 * Database Exporter for OXO Furniture (Admin Tool)
 * Exports database in standard phpMyAdmin / mysqldump format.
 */
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../includes/db.php';

// Force authentication
require_admin_login();

$db = get_db_connection();

if (!$db) {
    die("Error: Could not connect to MySQL database.");
}

date_default_timezone_set('Asia/Kolkata');

$output = "";
$mysqldump_bin = 'C:\\xampp\\mysql\\bin\\mysqldump.exe';

if (!file_exists($mysqldump_bin)) {
    $mysqldump_bin = 'mysqldump';
}

// Try running mysqldump command
$pass_arg = DB_PASS !== '' ? '-p' . escapeshellarg(DB_PASS) : '';
$cmd = sprintf(
    '"%s" -h %s -u %s %s --routines --triggers --single-transaction %s',
    $mysqldump_bin,
    escapeshellarg(DB_HOST),
    escapeshellarg(DB_USER),
    $pass_arg,
    escapeshellarg(DB_NAME)
);

$dump_output = [];
$return_var = -1;
@exec($cmd, $dump_output, $return_var);

if ($return_var === 0 && !empty($dump_output)) {
    $output = implode("\n", $dump_output);
} else {
    // Fallback: Manual PHP export format
    $tables = [];
    $stmt = $db->query("SHOW TABLES");
    while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
        $tables[] = $row[0];
    }

    $output = "-- phpMyAdmin SQL Dump\n";
    $output .= "-- version 5.2.1\n";
    $output .= "-- https://www.phpmyadmin.net/\n--\n";
    $output .= "-- Host: " . DB_HOST . "\n";
    $output .= "-- Generation Time: " . date('M d, Y \a\t h:i A') . "\n";
    $output .= "-- Database: `" . DB_NAME . "`\n\n";
    $output .= "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\n";
    $output .= "START TRANSACTION;\n";
    $output .= "SET time_zone = \"+00:00\";\n\n";
    $output .= "/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;\n";
    $output .= "/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;\n";
    $output .= "/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;\n";
    $output .= "/*!40101 SET NAMES utf8mb4 */;\n\n";

    foreach ($tables as $table) {
        $output .= "-- --------------------------------------------------------\n\n";
        $output .= "--\n-- Table structure for table `{$table}`\n--\n\n";
        
        $row2 = $db->query("SHOW CREATE TABLE `{$table}`")->fetch(PDO::FETCH_NUM);
        $create_sql = $row2[1];
        $output .= $create_sql . ";\n\n";
        
        $rows = $db->query("SELECT * FROM `{$table}`")->fetchAll(PDO::FETCH_ASSOC);
        if (!empty($rows)) {
            $output .= "--\n-- Dumping data for table `{$table}`\n--\n\n";
            $output .= "INSERT INTO `{$table}` VALUES\n";
            $values = [];
            foreach ($rows as $row) {
                $escaped_vals = array_map(function($v) use ($db) {
                    if ($v === null) return "NULL";
                    return $db->quote($v);
                }, array_values($row));
                $values[] = "(" . implode(", ", $escaped_vals) . ")";
            }
            $output .= implode(",\n", $values) . ";\n\n";
        }
    }
    $output .= "COMMIT;\n\n";
    $output .= "/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;\n";
    $output .= "/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;\n";
    $output .= "/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;\n";
}

$db_dir = __DIR__ . '/../db/';
if (!file_exists($db_dir)) {
    mkdir($db_dir, 0777, true);
}

// Update single master backup file: oxo_db.sql in db directory
file_put_contents($db_dir . 'oxo_db.sql', $output);

// Direct file download header
header('Content-Type: application/sql');
header('Content-Disposition: attachment; filename="oxo_db.sql"');
header('Content-Length: ' . strlen($output));
header('Cache-Control: must-revalidate');
header('Pragma: public');

echo $output;
exit;

