<?php
/**
 * Database Helper for OXO Furniture
 * Handles connection, DB auto-creation, and schema initialization.
 */

if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    session_start();
}

// Google OAuth Configuration
if (!defined('GOOGLE_CLIENT_ID')) {
    define('GOOGLE_CLIENT_ID', '105645089758-b99k8p7nia6pmr1ts8a73uo78g9av8nr.apps.googleusercontent.com');
}

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'oxo_db');

function get_db_connection() {
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }
    
    try {
        // First connect to MySQL without selecting database
        $dsn = "mysql:host=" . DB_HOST . ";charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        $temp_pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        
        // Create database if it doesn't exist
        $temp_pdo->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        
        // Connect to the specific database
        $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS, $options);
        
        // Initialize tables and seed values
        initialize_tables($pdo);
        
        return $pdo;
    } catch (\PDOException $e) {
        // Fallback gracefully so the main catalog doesn't crash if MySQL is down
        error_log("Database connection/init failed: " . $e->getMessage());
        return null;
    }
}

function initialize_tables($pdo) {
    // 1. Create admins table
    $pdo->exec("CREATE TABLE IF NOT EXISTS `oxo_admins` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `username` VARCHAR(50) NOT NULL UNIQUE,
        `password` VARCHAR(255) NOT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB;");
    
    // Check and seed default admin (username: admin, password: admin123)
    $stmt = $pdo->query("SELECT COUNT(*) FROM `oxo_admins` WHERE `username` = 'admin'");
    if ($stmt->fetchColumn() == 0) {
        $hashed_password = password_hash('admin123', PASSWORD_DEFAULT);
        $insert_stmt = $pdo->prepare("INSERT INTO `oxo_admins` (`username`, `password`) VALUES ('admin', ?)");
        $insert_stmt->execute([$hashed_password]);
    }
    
    // Check if column whatsapp exists in oxo_admins, add if missing
    $col_stmt = $pdo->query("SHOW COLUMNS FROM `oxo_admins` LIKE 'whatsapp'");
    if (!$col_stmt->fetch()) {
        $pdo->exec("ALTER TABLE `oxo_admins` ADD COLUMN `whatsapp` VARCHAR(50) DEFAULT NULL");
    }
    
    // 2. Create products table
    $pdo->exec("CREATE TABLE IF NOT EXISTS `oxo_products` (
        `id` VARCHAR(50) PRIMARY KEY,
        `title` VARCHAR(255) NOT NULL,
        `price` INT NOT NULL,
        `category` VARCHAR(100) NOT NULL,
        `image` VARCHAR(255) NOT NULL,
        `description` TEXT NOT NULL,
        `specs` TEXT NOT NULL,
        `details` TEXT NOT NULL, -- JSON-encoded array for details
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB;");

    // 3. Create consultations table
    $pdo->exec("CREATE TABLE IF NOT EXISTS `oxo_consultations` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `name` VARCHAR(255) NOT NULL,
        `email` VARCHAR(255) NOT NULL,
        `product_title` VARCHAR(255) NOT NULL,
        `message` TEXT NOT NULL,
        `status` VARCHAR(50) DEFAULT 'Pending',
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB;");

    // Check if column whatsapp exists in oxo_consultations, add if missing
    $col_stmt = $pdo->query("SHOW COLUMNS FROM `oxo_consultations` LIKE 'whatsapp'");
    if (!$col_stmt->fetch()) {
        $pdo->exec("ALTER TABLE `oxo_consultations` ADD COLUMN `whatsapp` VARCHAR(50) DEFAULT NULL");
    }

    // 4. Create brands table
    $pdo->exec("CREATE TABLE IF NOT EXISTS `oxo_brands` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `name` VARCHAR(255) NOT NULL,
        `logo_path` VARCHAR(255) NOT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB;");
    
    // Check and seed default mock brands
    $stmt = $pdo->query("SELECT COUNT(*) FROM `oxo_brands`");
    if ($stmt->fetchColumn() == 0) {
        $insert_brand = $pdo->prepare("INSERT INTO `oxo_brands` (`name`, `logo_path`) VALUES (?, ?)");
        $mock_brands = [
            ["Aethera Studio", "assets/images/logo.png"],
            ["Luminary Co", "assets/images/logo.png"],
            ["Vespera Design", "assets/images/logo.png"],
            ["Calacatta Natural", "assets/images/logo.png"],
            ["Walnut & Oak", "assets/images/logo.png"],
            ["Eclipse Lighting", "assets/images/logo.png"]
        ];
        foreach ($mock_brands as $brand) {
            $insert_brand->execute([$brand[0], $brand[1]]);
        }
    } else {
        // Migration: automatically update any mock brands with empty paths to the fallback logo
        $pdo->exec("UPDATE `oxo_brands` SET `logo_path` = 'assets/images/logo.png' WHERE `logo_path` = '' OR `logo_path` IS NULL");
    }

    // 5. Create categories table
    $pdo->exec("CREATE TABLE IF NOT EXISTS `oxo_categories` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `slug` VARCHAR(50) NOT NULL UNIQUE,
        `name` VARCHAR(100) NOT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB;");
    
    // Check if column bg_color exists in oxo_categories, add if missing
    $col_cat_stmt = $pdo->query("SHOW COLUMNS FROM `oxo_categories` LIKE 'bg_color'");
    if (!$col_cat_stmt->fetch()) {
        $pdo->exec("ALTER TABLE `oxo_categories` ADD COLUMN `bg_color` VARCHAR(50) DEFAULT NULL");
    }

    // Seed default categories
    $stmt = $pdo->query("SELECT COUNT(*) FROM `oxo_categories`");
    if ($stmt->fetchColumn() == 0) {
        $insert_cat = $pdo->prepare("INSERT INTO `oxo_categories` (`slug`, `name`, `bg_color`) VALUES (?, ?, ?)");
        $default_cats = [
            ["sofas", "Sofas", "rgba(95, 173, 138, 0.03)"],
            ["chairs", "Chairs", "#FAF9F6"],
            ["tables", "Tables", "rgba(10, 46, 36, 0.02)"],
            ["lighting", "Lighting", "rgba(200, 162, 118, 0.035)"],
            ["storage", "Storage", "rgba(30, 40, 36, 0.015)"]
        ];
        foreach ($default_cats as $cat) {
            $insert_cat->execute([$cat[0], $cat[1], $cat[2]]);
        }
    } else {
        // Seed colors for existing categories if empty
        $pdo->exec("UPDATE `oxo_categories` SET `bg_color` = '#FAF9F6' WHERE `slug` = 'chairs' AND (`bg_color` IS NULL OR `bg_color` = '')");
        $pdo->exec("UPDATE `oxo_categories` SET `bg_color` = 'rgba(200, 162, 118, 0.035)' WHERE `slug` = 'lighting' AND (`bg_color` IS NULL OR `bg_color` = '')");
        $pdo->exec("UPDATE `oxo_categories` SET `bg_color` = 'rgba(95, 173, 138, 0.03)' WHERE `slug` = 'sofas' AND (`bg_color` IS NULL OR `bg_color` = '')");
        $pdo->exec("UPDATE `oxo_categories` SET `bg_color` = 'rgba(10, 46, 36, 0.02)' WHERE `slug` = 'tables' AND (`bg_color` IS NULL OR `bg_color` = '')");
        $pdo->exec("UPDATE `oxo_categories` SET `bg_color` = 'rgba(30, 40, 36, 0.015)' WHERE `slug` = 'storage' AND (`bg_color` IS NULL OR `bg_color` = '')");
    }

    // 6. Create materials table
    $pdo->exec("CREATE TABLE IF NOT EXISTS `oxo_materials` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `slug` VARCHAR(50) NOT NULL UNIQUE,
        `name` VARCHAR(100) NOT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB;");
    
    // Seed default materials
    $stmt = $pdo->query("SELECT COUNT(*) FROM `oxo_materials`");
    if ($stmt->fetchColumn() == 0) {
        $insert_mat = $pdo->prepare("INSERT INTO `oxo_materials` (`slug`, `name`) VALUES (?, ?)");
        $default_mats = [
            ["wood", "Solid Wood"],
            ["metal", "Brushed Metal"],
            ["glass", "Tempered Glass"],
            ["fabric", "Organic Fabric"],
            ["plastic", "Recycled Plastic"]
        ];
        foreach ($default_mats as $mat) {
            $insert_mat->execute([$mat[0], $mat[1]]);
        }
    }
    
    // Check if column material_slug exists in oxo_products, add if missing
    $col_stmt = $pdo->query("SHOW COLUMNS FROM `oxo_products` LIKE 'material_slug'");
    if (!$col_stmt->fetch()) {
        $pdo->exec("ALTER TABLE `oxo_products` ADD COLUMN `material_slug` VARCHAR(100) DEFAULT 'wood'");
    }
    
    // Check if column brand_id exists in oxo_products, add if missing
    $col_stmt = $pdo->query("SHOW COLUMNS FROM `oxo_products` LIKE 'brand_id'");
    if (!$col_stmt->fetch()) {
        $pdo->exec("ALTER TABLE `oxo_products` ADD COLUMN `brand_id` INT DEFAULT NULL");
    }
    
    // Check if column gallery exists in oxo_products, add if missing
    $col_stmt = $pdo->query("SHOW COLUMNS FROM `oxo_products` LIKE 'gallery'");
    if (!$col_stmt->fetch()) {
        $pdo->exec("ALTER TABLE `oxo_products` ADD COLUMN `gallery` TEXT DEFAULT NULL");
    }
    
    // Check if column height_cm exists in oxo_products, add if missing
    $col_stmt = $pdo->query("SHOW COLUMNS FROM `oxo_products` LIKE 'height_cm'");
    if (!$col_stmt->fetch()) {
        $pdo->exec("ALTER TABLE `oxo_products` ADD COLUMN `height_cm` INT DEFAULT 85");
    }
    
    // Check if column width_cm exists in oxo_products, add if missing
    $col_stmt = $pdo->query("SHOW COLUMNS FROM `oxo_products` LIKE 'width_cm'");
    if (!$col_stmt->fetch()) {
        $pdo->exec("ALTER TABLE `oxo_products` ADD COLUMN `width_cm` INT DEFAULT 100");
    }
    
    // Check if column length_cm exists in oxo_products, add if missing
    $col_stmt = $pdo->query("SHOW COLUMNS FROM `oxo_products` LIKE 'length_cm'");
    if (!$col_stmt->fetch()) {
        $pdo->exec("ALTER TABLE `oxo_products` ADD COLUMN `length_cm` INT DEFAULT 240");
    }
    
    // Create colors table
    $pdo->exec("CREATE TABLE IF NOT EXISTS `oxo_colors` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `name` VARCHAR(100) NOT NULL,
        `hex` VARCHAR(7) NOT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB;");
    
    // Seed default colors
    $stmt = $pdo->query("SELECT COUNT(*) FROM `oxo_colors`");
    if ($stmt->fetchColumn() == 0) {
        $insert_color = $pdo->prepare("INSERT INTO `oxo_colors` (`name`, `hex`) VALUES (?, ?)");
        $default_colors = [
            ["Charcoal Black", "#1a1a1a"],
            ["Off-White Linen", "#faf9f6"],
            ["Gold Sand", "#bf8f54"],
            ["Smoked Oak", "#4a3b32"]
        ];
        foreach ($default_colors as $c) {
            $insert_color->execute([$c[0], $c[1]]);
        }
    }
    
    // Check if column color_id exists in oxo_products, add if missing
    $col_stmt = $pdo->query("SHOW COLUMNS FROM `oxo_products` LIKE 'color_id'");
    if (!$col_stmt->fetch()) {
        $pdo->exec("ALTER TABLE `oxo_products` ADD COLUMN `color_id` INT DEFAULT NULL");
    }

    // Check if column color_ids exists in oxo_products, add if missing
    $col_stmt = $pdo->query("SHOW COLUMNS FROM `oxo_products` LIKE 'color_ids'");
    if (!$col_stmt->fetch()) {
        $pdo->exec("ALTER TABLE `oxo_products` ADD COLUMN `color_ids` TEXT DEFAULT NULL");
    }
    
    // 7. Create users table
    $pdo->exec("CREATE TABLE IF NOT EXISTS `oxo_users` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `name` VARCHAR(255) NOT NULL,
        `email` VARCHAR(255) UNIQUE NOT NULL,
        `password` VARCHAR(255) NOT NULL,
        `phone` VARCHAR(50) DEFAULT NULL,
        `address` TEXT DEFAULT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB;");
    
    // Check if table is empty, if so, seed from static products-db.php
    $stmt = $pdo->query("SELECT COUNT(*) FROM `oxo_products`");
    if ($stmt->fetchColumn() == 0) {
        global $PRODUCTS_DB;
        $temp_products = [];
        
        if (isset($PRODUCTS_DB) && is_array($PRODUCTS_DB) && !empty($PRODUCTS_DB)) {
            $temp_products = $PRODUCTS_DB;
        } else {
            $db_file = __DIR__ . '/products-db.php';
            if (file_exists($db_file)) {
                include_once $db_file;
                if (isset($PRODUCTS_DB) && is_array($PRODUCTS_DB) && !empty($PRODUCTS_DB)) {
                    $temp_products = $PRODUCTS_DB;
                }
            }
        }
        
        if (!empty($temp_products)) {
            $insert_prod = $pdo->prepare("INSERT INTO `oxo_products` 
                (`id`, `title`, `price`, `category`, `image`, `description`, `specs`, `details`, `material_slug`) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            foreach ($temp_products as $p) {
                // Ensure details is properly formatted
                $details_json = is_array($p['details']) ? json_encode($p['details']) : $p['details'];
                // Set default material_slug based on category
                $mat_slug = 'wood';
                if ($p['category'] === 'sofas') {
                    $mat_slug = 'fabric';
                } elseif ($p['category'] === 'lighting') {
                    $mat_slug = 'metal';
                }
                
                $insert_prod->execute([
                    $p['id'],
                    $p['title'],
                    $p['price'],
                    $p['category'],
                    $p['image'],
                    $p['description'],
                    $p['specs'],
                    $details_json,
                    $mat_slug
                ]);
            }
        }
    }
}

function get_admin_whatsapp() {
    $db = get_db_connection();
    if ($db) {
        try {
            $stmt = $db->query("SELECT `whatsapp` FROM `oxo_admins` LIMIT 1");
            return $stmt->fetchColumn();
        } catch (\Exception $e) {
            return null;
        }
    }
    return null;
}
