<?php
/**
 * Database Importer for OXO Furniture (Admin Tool)
 * Allows admins to upload an SQL backup file and restore the database schema/data.
 */
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../includes/db.php';

// Force authentication
require_admin_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_FILES['sql_file'])) {
    header("Location: index.php?import=error&message=" . urlencode("No SQL file was selected for upload."));
    exit;
}

$file = $_FILES['sql_file'];

if ($file['error'] !== UPLOAD_ERR_OK) {
    $error_messages = [
        UPLOAD_ERR_INI_SIZE   => "The uploaded file exceeds the upload_max_filesize directive in php.ini.",
        UPLOAD_ERR_FORM_SIZE  => "The uploaded file exceeds the MAX_FILE_SIZE directive in the HTML form.",
        UPLOAD_ERR_PARTIAL    => "The uploaded file was only partially uploaded.",
        UPLOAD_ERR_NO_FILE    => "No file was uploaded.",
        UPLOAD_ERR_NO_TMP_DIR => "Missing a temporary folder.",
        UPLOAD_ERR_CANT_WRITE => "Failed to write file to disk.",
        UPLOAD_ERR_EXTENSION  => "A PHP extension stopped the file upload."
    ];
    $msg = $error_messages[$file['error']] ?? ("Upload error code: " . $file['error']);
    header("Location: index.php?import=error&message=" . urlencode($msg));
    exit;
}

$filename = $file['name'];
$ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

if ($ext !== 'sql') {
    header("Location: index.php?import=error&message=" . urlencode("Invalid file extension (.$ext). Please upload a valid .sql file."));
    exit;
}

$sql_content = file_get_contents($file['tmp_name']);

if (empty(trim($sql_content))) {
    header("Location: index.php?import=error&message=" . urlencode("The uploaded SQL file is empty."));
    exit;
}

$db = get_db_connection();

if (!$db) {
    header("Location: index.php?import=error&message=" . urlencode("Could not connect to MySQL database."));
    exit;
}

try {
    // Disable foreign key checks for clean restore
    $db->exec("SET FOREIGN_KEY_CHECKS=0");

    // Enable emulation of prepared statements to execute batch SQL scripts
    $db->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);

    try {
        $db->exec($sql_content);
    } catch (\PDOException $e) {
        // Fallback: line-by-line query execution
        $lines = explode("\n", $sql_content);
        $query = '';
        foreach ($lines as $line) {
            $trimmed = trim($line);
            if (empty($trimmed) || strpos($trimmed, '--') === 0 || strpos($trimmed, '/*') === 0 || strpos($trimmed, '#') === 0) {
                continue;
            }
            $query .= $line . "\n";
            if (substr($trimmed, -1) === ';') {
                $db->exec($query);
                $query = '';
            }
        }
        if (!empty(trim($query))) {
            $db->exec($query);
        }
    }

    $db->exec("SET FOREIGN_KEY_CHECKS=1");

    // Update single main backup file oxo_db.sql in db/ folder with latest imported state
    $db_dir = __DIR__ . '/../db/';
    if (!file_exists($db_dir)) {
        mkdir($db_dir, 0777, true);
    }
    file_put_contents($db_dir . 'oxo_db.sql', $sql_content);

    header("Location: index.php?import=success");
    exit;
} catch (\Exception $ex) {
    try {
        $db->exec("SET FOREIGN_KEY_CHECKS=1");
    } catch (\Exception $e) {}
    header("Location: index.php?import=error&message=" . urlencode($ex->getMessage()));
    exit;
}
