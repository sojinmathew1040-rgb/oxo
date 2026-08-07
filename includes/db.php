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

    // Migration: Auto-fix any 0 or null product prices back to valid positive values
    $pdo->exec("UPDATE `oxo_products` SET `price` = 18500 WHERE `price` <= 0 OR `price` IS NULL;");

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
            ["storage", "Storage", "rgba(30, 40, 36, 0.015)"],
            ["beds", "Beds & Mattresses", "rgba(180, 140, 90, 0.03)"]
        ];
        foreach ($default_cats as $cat) {
            $insert_cat->execute([$cat[0], $cat[1], $cat[2]]);
        }
    } else {
        // Migration: ensure beds category exists
        $check_beds = $pdo->query("SELECT COUNT(*) FROM `oxo_categories` WHERE `slug` = 'beds'")->fetchColumn();
        if ($check_beds == 0) {
            $insert_cat = $pdo->prepare("INSERT INTO `oxo_categories` (`slug`, `name`, `bg_color`) VALUES (?, ?, ?)");
            $insert_cat->execute(["beds", "Beds & Mattresses", "rgba(180, 140, 90, 0.03)"]);
        }

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
    
    // Check if column source_url exists in oxo_products, add if missing
    $col_stmt = $pdo->query("SHOW COLUMNS FROM `oxo_products` LIKE 'source_url'");
    if (!$col_stmt->fetch()) {
        $pdo->exec("ALTER TABLE `oxo_products` ADD COLUMN `source_url` TEXT DEFAULT NULL");
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

    // 8. Create announcements table for landing page poster pop-ups
    $pdo->exec("CREATE TABLE IF NOT EXISTS `oxo_announcements` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `title` VARCHAR(255) DEFAULT NULL,
        `subtitle` TEXT DEFAULT NULL,
        `image_path` VARCHAR(255) NOT NULL,
        `link_url` VARCHAR(255) DEFAULT NULL,
        `is_active` TINYINT(1) DEFAULT 1,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB;");

    // 9. Create site_content table for dynamic static text, images, videos, contact details & footer text
    $pdo->exec("CREATE TABLE IF NOT EXISTS `oxo_site_content` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `content_key` VARCHAR(100) NOT NULL UNIQUE,
        `content_value` LONGTEXT DEFAULT NULL,
        `content_group` VARCHAR(50) DEFAULT 'general',
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB;");

    // Seed site content defaults if not exists
    $default_site_contents = [
        // General & Header
        'site_title' => 'OXO — Premium Furniture Store',
        'site_description' => 'Discover OXO, a premium high-end furniture store offering curated luxury sofas, accent chairs, marble dining tables, and designer lighting. Attract visual excellence.',
        
        // Hero Section
        'hero_tag' => 'Collection 2026',
        'hero_title_1' => 'Silent Luxury',
        'hero_title_2' => 'For Modern Spaces',
        'hero_desc' => 'Explore a curated assembly of luxury furniture designed for high-end residential interiors. Sculpted shapes, premium textures, and cinematic aesthetics.',
        'hero_media_path' => 'assets/images/HERO.mp4',
        'hero_btn_primary_text' => 'Explore Catalog',
        'hero_btn_primary_link' => 'shop.php',
        'hero_btn_secondary_text' => 'Our Legacy',
        'hero_btn_secondary_link' => '#about',

        // Homepage About Section
        'about_home_image' => 'assets/images/sofa_1.png',
        'about_home_stat_val' => '15+ Years',
        'about_home_stat_label' => 'Master Italian Joinery',
        'about_home_tag' => 'Our Core Philosophy',
        'about_home_title' => 'Architecting Silent Luxury',
        'about_home_p1' => 'At OXO, furniture is not merely functional—it is spatial sculpture. Each creation is curated to define elite residential sanctuaries. We harmonise traditional Italian joinery with progressive architectural proportions.',
        'about_home_p2' => 'Sourcing rare Calacatta marble pedestals, top-grain aniline leathers, and kiln-dried walnut timbers, our master artisans elevate raw earth elements into tactile works of art.',
        'about_home_bento1_val' => '15+',
        'about_home_bento1_label' => 'Years Legacy',
        'about_home_bento2_val' => '100%',
        'about_home_bento2_label' => 'Bespoke Design',
        'about_home_bento3_val' => '8,000+',
        'about_home_bento3_label' => 'Elite Residences',
        'about_home_btn_text' => 'Read Our Full Story',
        'about_home_btn_link' => 'about.php',

        // Dedicated About Us Page
        'about_page_hero_title' => 'Crafting Timeless Elegance',
        'about_page_hero_subtitle' => 'Since 2008, OXO has redefined luxury living through Italian design, master craftsmanship, and sustainable luxury.',
        'about_page_heritage_tag' => 'Heritage & Craftsmanship',
        'about_page_heritage_title' => 'Born in Milan, Crafted for the World',
        'about_page_heritage_p1' => 'Founded in the heart of Lombardy, OXO began as an artisanal workshop dedicated to bespoke joinery and leather sculpting. Over two decades, we have evolved into a global luxury house, blending traditional techniques with cutting-edge architectural design.',
        'about_page_heritage_p2' => 'Every sofa frame, dining table, and lighting fixture undergoes over 120 hours of hand-finishing by master craftsmen, ensuring unparalleled quality and enduring beauty.',
        'about_page_heritage_img' => 'assets/images/about-craftsman.png',
        'about_page_showroom_tag' => 'Flagship Sanctuary',
        'about_page_showroom_title' => 'Experience OXO in Person',
        'about_page_showroom_p1' => 'Step into our sanctuary of spatial architecture. Our flagship gallery offers private viewings, tactile material swatches, and dedicated interior design consultation.',
        'about_page_showroom_p2' => 'Discover how our architectural silhouettes transform luxury residential spaces into refined artistic sanctuaries.',
        'about_page_showroom_img' => 'assets/images/flagship-facade.jpg',

        'about_card1_icon' => 'fa-gem',
        'about_card1_title' => 'Bespoke Artistry',
        'about_card1_desc' => 'Every piece is crafted to individual client specifications with rare materials.',
        'about_card2_icon' => 'fa-leaf',
        'about_card2_title' => 'Sustainable Luxury',
        'about_card2_desc' => 'Responsibly sourced timber, eco-certified leathers, and zero-waste production.',
        'about_card3_icon' => 'fa-shield-halved',
        'about_card3_title' => '10-Year Warranty',
        'about_card3_desc' => 'Enduring structural integrity backed by our house guarantee of perfection.',

        // Contact & Concierge Section
        'contact_tag' => 'Bespoke Concierge',
        'contact_title' => 'Connect With OXO Private Service',
        'contact_subtitle' => 'Have questions regarding custom modular dimensions, bespoke leathers, or private showroom viewings?',
        'contact_address' => '84 Luxury Avenue, Suite 900, Mumbai, India',
        'contact_email' => 'concierge@oxo.design',
        'contact_phone' => '+91 (22) 8800-4400',
        'contact_instagram' => '#',
        'contact_facebook' => '#',
        'contact_map' => 'https://maps.google.com',

        // Footer Section
        'footer_desc' => 'Architecting spaces of silent luxury, cinematic elegance, and bespoke Italian craftsmanship. Designed to inspire elite sanctuaries.',
        'footer_copyright' => 'OXO Furniture. All rights reserved.',
        'footer_dev_credit' => 'Designed and Developed by peru',
        'footer_dev_link' => '#'
    ];

    $seed_sc_stmt = $pdo->prepare("INSERT IGNORE INTO `oxo_site_content` (`content_key`, `content_value`, `content_group`) VALUES (?, ?, ?)");
    foreach ($default_site_contents as $key => $val) {
        $group = 'general';
        if (strpos($key, 'hero_') === 0) $group = 'hero';
        elseif (strpos($key, 'about_home_') === 0) $group = 'about_home';
        elseif (strpos($key, 'about_page_') === 0 || strpos($key, 'about_card') === 0) $group = 'about_page';
        elseif (strpos($key, 'contact_') === 0) $group = 'contact';
        elseif (strpos($key, 'footer_') === 0) $group = 'footer';
        $seed_sc_stmt->execute([$key, $val, $group]);
    }
    
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

function get_site_content($key = null, $default = '') {
    static $content_cache = null;
    $db = get_db_connection();
    if ($content_cache === null && $db) {
        try {
            $stmt = $db->query("SELECT `content_key`, `content_value` FROM `oxo_site_content`");
            $content_cache = $stmt->fetchAll(PDO::FETCH_KEY_PAIR) ?: [];
        } catch (\Exception $e) {
            $content_cache = [];
        }
    }
    if ($key === null) {
        return $content_cache ?: [];
    }
    return (isset($content_cache[$key]) && $content_cache[$key] !== '') ? $content_cache[$key] : $default;
}

function set_site_content($key, $value, $group = 'general') {
    $db = get_db_connection();
    if (!$db) return false;
    try {
        $stmt = $db->prepare("INSERT INTO `oxo_site_content` (`content_key`, `content_value`, `content_group`) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE `content_value` = VALUES(`content_value`), `content_group` = VALUES(`content_group`)");
        return $stmt->execute([$key, $value, $group]);
    } catch (\Exception $e) {
        return false;
    }
}

function get_shop_images($only_active = true) {
    $db = get_db_connection();
    if (!$db) return [];
    try {
        $db->exec("CREATE TABLE IF NOT EXISTS `oxo_shop_images` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `title` varchar(255) NOT NULL,
            `caption` text DEFAULT NULL,
            `image_path` varchar(255) NOT NULL,
            `sort_order` int(11) NOT NULL DEFAULT 0,
            `is_active` tinyint(1) NOT NULL DEFAULT 1,
            `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $count_stmt = $db->query("SELECT COUNT(*) FROM `oxo_shop_images`");
        if ($count_stmt->fetchColumn() == 0) {
            $seed_items = [
                ['Shop Storefront & Facade', 'Corrugated Dark Cladding & Signature Orange Framing', 'assets/images/flagship-facade.jpg', 1],
                ['Living Room Atelier', 'Bespoke Modular Lounge Display', 'assets/images/sofa_1.png', 2],
                ['Master Joinery Studio', 'Artisan Hand-Finishing Station', 'assets/images/about-craftsman.png', 3],
                ['Architectural Dining Gallery', 'Honed Italian Travertine & Marble Displays', 'assets/images/table_2.png', 4],
                ['Lighting & Material Sanctuary', 'Curated Lighting & Aniline Leather Samples', 'assets/images/light_2.png', 5],
                ['Private Client Lounge', 'Consultation Suite for Custom Interior Joinery', 'assets/images/chair_2.png', 6],
            ];
            $ins = $db->prepare("INSERT INTO `oxo_shop_images` (`title`, `caption`, `image_path`, `sort_order`, `is_active`) VALUES (?, ?, ?, ?, 1)");
            foreach ($seed_items as $item) {
                $ins->execute($item);
            }
        }

        $sql = "SELECT * FROM `oxo_shop_images` ";
        if ($only_active) {
            $sql .= "WHERE `is_active` = 1 ";
        }
        $sql .= "ORDER BY `sort_order` ASC, `id` DESC";
        $stmt = $db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (\Exception $e) {
        return [];
    }
}

