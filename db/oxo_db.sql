-- OXO Furniture Database Backup
-- Generated: 2026-08-03 20:59:16 IST
-- Host: localhost | Database: oxo_db

SET FOREIGN_KEY_CHECKS=0;

-- --------------------------------------------------------
-- Table structure for table `admins`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `admins`;
CREATE TABLE `admins` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table `admins` (1 rows)
INSERT INTO `admins` (`id`, `username`, `password`, `updated_at`) VALUES ('1', 'admin', '$2y$10$UFnHeM8lcXQdXaryUSQetu12gDRNWRNcF0JPbbe5U1a7wsp6gkkWm', '2026-07-07 14:32:46');

-- --------------------------------------------------------
-- Table structure for table `attributes`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `attributes`;
CREATE TABLE `attributes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `code` varchar(100) NOT NULL,
  `type` enum('text','select','toggle','number') NOT NULL,
  `options` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=31 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table `attributes` (15 rows)
INSERT INTO `attributes` (`id`, `name`, `code`, `type`, `options`) VALUES ('1', 'Upholstery Material', 'upholstery_material', 'select', '[\"Velvet\",\"Leather\",\"Boucl\\u00e9\",\"Linen\"]');
INSERT INTO `attributes` (`id`, `name`, `code`, `type`, `options`) VALUES ('2', 'Max Weight Capacity', 'max_weight_capacity', 'number', NULL);
INSERT INTO `attributes` (`id`, `name`, `code`, `type`, `options`) VALUES ('3', 'Ergonomic Rating', 'ergonomic_rating', 'number', NULL);
INSERT INTO `attributes` (`id`, `name`, `code`, `type`, `options`) VALUES ('4', 'Bed Size', 'bed_size', 'select', '[\"King\",\"Queen\",\"Twin\"]');
INSERT INTO `attributes` (`id`, `name`, `code`, `type`, `options`) VALUES ('5', 'Headboard Type', 'headboard_type', 'text', NULL);
INSERT INTO `attributes` (`id`, `name`, `code`, `type`, `options`) VALUES ('6', 'Mattress Included', 'mattress_included', 'toggle', NULL);
INSERT INTO `attributes` (`id`, `name`, `code`, `type`, `options`) VALUES ('7', 'Number of Drawers', 'number_of_drawers', 'number', NULL);
INSERT INTO `attributes` (`id`, `name`, `code`, `type`, `options`) VALUES ('8', 'Cable Management Holes', 'cable_management_holes', 'toggle', NULL);
INSERT INTO `attributes` (`id`, `name`, `code`, `type`, `options`) VALUES ('9', 'Material Type', 'material_type', 'select', '[\"Oak\",\"Walnut\",\"Steel\"]');
INSERT INTO `attributes` (`id`, `name`, `code`, `type`, `options`) VALUES ('10', 'Shape', 'shape', 'text', NULL);
INSERT INTO `attributes` (`id`, `name`, `code`, `type`, `options`) VALUES ('11', 'Seating Capacity', 'seating_capacity', 'number', NULL);
INSERT INTO `attributes` (`id`, `name`, `code`, `type`, `options`) VALUES ('12', 'Material', 'material', 'text', NULL);
INSERT INTO `attributes` (`id`, `name`, `code`, `type`, `options`) VALUES ('13', 'Dimensions', 'dimensions', 'text', NULL);
INSERT INTO `attributes` (`id`, `name`, `code`, `type`, `options`) VALUES ('14', 'Designer', 'designer', 'text', NULL);
INSERT INTO `attributes` (`id`, `name`, `code`, `type`, `options`) VALUES ('15', 'Origin', 'origin', 'text', NULL);

-- --------------------------------------------------------
-- Table structure for table `categories`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `categories`;
CREATE TABLE `categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table `categories` (6 rows)
INSERT INTO `categories` (`id`, `name`, `slug`) VALUES ('1', 'Chair', 'chair');
INSERT INTO `categories` (`id`, `name`, `slug`) VALUES ('2', 'Cot Set', 'cot-set');
INSERT INTO `categories` (`id`, `name`, `slug`) VALUES ('3', 'Study Table', 'study-table');
INSERT INTO `categories` (`id`, `name`, `slug`) VALUES ('4', 'Table', 'table');
INSERT INTO `categories` (`id`, `name`, `slug`) VALUES ('5', 'Lighting', 'lighting');
INSERT INTO `categories` (`id`, `name`, `slug`) VALUES ('6', 'Sofas', 'sofas');

-- --------------------------------------------------------
-- Table structure for table `category_attributes`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `category_attributes`;
CREATE TABLE `category_attributes` (
  `category_id` int(11) NOT NULL,
  `attribute_id` int(11) NOT NULL,
  PRIMARY KEY (`category_id`,`attribute_id`),
  KEY `attribute_id` (`attribute_id`),
  CONSTRAINT `category_attributes_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE,
  CONSTRAINT `category_attributes_ibfk_2` FOREIGN KEY (`attribute_id`) REFERENCES `attributes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table `category_attributes` (11 rows)
INSERT INTO `category_attributes` (`category_id`, `attribute_id`) VALUES ('1', '1');
INSERT INTO `category_attributes` (`category_id`, `attribute_id`) VALUES ('1', '2');
INSERT INTO `category_attributes` (`category_id`, `attribute_id`) VALUES ('1', '3');
INSERT INTO `category_attributes` (`category_id`, `attribute_id`) VALUES ('2', '4');
INSERT INTO `category_attributes` (`category_id`, `attribute_id`) VALUES ('2', '5');
INSERT INTO `category_attributes` (`category_id`, `attribute_id`) VALUES ('2', '6');
INSERT INTO `category_attributes` (`category_id`, `attribute_id`) VALUES ('3', '7');
INSERT INTO `category_attributes` (`category_id`, `attribute_id`) VALUES ('3', '8');
INSERT INTO `category_attributes` (`category_id`, `attribute_id`) VALUES ('3', '9');
INSERT INTO `category_attributes` (`category_id`, `attribute_id`) VALUES ('4', '10');
INSERT INTO `category_attributes` (`category_id`, `attribute_id`) VALUES ('4', '11');

-- --------------------------------------------------------
-- Table structure for table `oxo_admins`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `oxo_admins`;
CREATE TABLE `oxo_admins` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `whatsapp` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table `oxo_admins` (1 rows)
INSERT INTO `oxo_admins` (`id`, `username`, `password`, `created_at`, `whatsapp`) VALUES ('1', 'admin', '$2y$10$rhNISvpbHQtYKoKnsHiBA.xB2T/70bd28iEIb8OzGviUd0wvIKD/G', '2026-07-09 08:43:19', '8943804920');

-- --------------------------------------------------------
-- Table structure for table `oxo_announcements`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `oxo_announcements`;
CREATE TABLE `oxo_announcements` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) DEFAULT NULL,
  `subtitle` text DEFAULT NULL,
  `image_path` varchar(255) NOT NULL,
  `link_url` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table `oxo_announcements` (1 rows)
INSERT INTO `oxo_announcements` (`id`, `title`, `subtitle`, `image_path`, `link_url`, `is_active`, `created_at`) VALUES ('2', '', '', 'assets/images/uploads/announcement_1785767837_892.jpg', '', '1', '2026-08-03 20:07:17');

-- --------------------------------------------------------
-- Table structure for table `oxo_brands`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `oxo_brands`;
CREATE TABLE `oxo_brands` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `logo_path` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table `oxo_brands` (7 rows)
INSERT INTO `oxo_brands` (`id`, `name`, `logo_path`, `created_at`) VALUES ('6', 'Eclipse Lighting', 'assets/images/logo.png', '2026-07-09 09:02:16');
INSERT INTO `oxo_brands` (`id`, `name`, `logo_path`, `created_at`) VALUES ('7', 'nmb', 'assets/images/uploads/brand_1783568282_768.png', '2026-07-09 09:08:02');
INSERT INTO `oxo_brands` (`id`, `name`, `logo_path`, `created_at`) VALUES ('9', 'Nilkamal Furniture', 'assets/images/uploads/brand_1784789164_856.png', '2026-07-23 09:21:50');
INSERT INTO `oxo_brands` (`id`, `name`, `logo_path`, `created_at`) VALUES ('10', 'Applecart', 'assets/images/uploads/brands/applecart_logo.png', '2026-07-23 10:05:33');
INSERT INTO `oxo_brands` (`id`, `name`, `logo_path`, `created_at`) VALUES ('11', 'Supreme Furniture', 'assets/images/uploads/brands/supremefurniture_logo.png', '2026-07-23 12:29:28');
INSERT INTO `oxo_brands` (`id`, `name`, `logo_path`, `created_at`) VALUES ('12', 'Indroyal', 'assets/images/uploads/brands/indroyal_logo.png', '2026-07-28 11:04:59');
INSERT INTO `oxo_brands` (`id`, `name`, `logo_path`, `created_at`) VALUES ('13', 'Peps India', 'assets/images/uploads/brands/pepsindia_logo.png', '2026-08-03 20:07:48');

-- --------------------------------------------------------
-- Table structure for table `oxo_categories`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `oxo_categories`;
CREATE TABLE `oxo_categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `slug` varchar(50) NOT NULL,
  `name` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `bg_color` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table `oxo_categories` (7 rows)
INSERT INTO `oxo_categories` (`id`, `slug`, `name`, `created_at`, `bg_color`) VALUES ('1', 'sofas', 'Sofas & Recliners', '2026-07-09 09:18:24', 'rgba(95, 173, 138, 0.03)');
INSERT INTO `oxo_categories` (`id`, `slug`, `name`, `created_at`, `bg_color`) VALUES ('2', 'chairs', 'Chairs & Seating', '2026-07-09 09:18:24', '#FAF9F6');
INSERT INTO `oxo_categories` (`id`, `slug`, `name`, `created_at`, `bg_color`) VALUES ('3', 'tables', 'Tables & Dining', '2026-07-09 09:18:24', 'rgba(10, 46, 36, 0.02)');
INSERT INTO `oxo_categories` (`id`, `slug`, `name`, `created_at`, `bg_color`) VALUES ('4', 'lighting', 'Lighting & Decor', '2026-07-09 09:18:24', 'rgba(200, 162, 118, 0.035)');
INSERT INTO `oxo_categories` (`id`, `slug`, `name`, `created_at`, `bg_color`) VALUES ('5', 'storage', 'Storage & Wardrobes', '2026-07-09 09:18:24', 'rgba(30, 40, 36, 0.015)');
INSERT INTO `oxo_categories` (`id`, `slug`, `name`, `created_at`, `bg_color`) VALUES ('6', 'beds', 'Beds & Bedroom', '2026-07-22 14:39:09', 'rgba(210, 180, 140, 0.03)');
INSERT INTO `oxo_categories` (`id`, `slug`, `name`, `created_at`, `bg_color`) VALUES ('7', 'study', 'Study & Office', '2026-07-22 14:39:09', 'rgba(70, 130, 180, 0.03)');

-- --------------------------------------------------------
-- Table structure for table `oxo_colors`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `oxo_colors`;
CREATE TABLE `oxo_colors` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `hex` varchar(7) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=26 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table `oxo_colors` (25 rows)
INSERT INTO `oxo_colors` (`id`, `name`, `hex`, `created_at`) VALUES ('1', 'Charcoal Black', '#1a1a1a', '2026-07-09 11:43:22');
INSERT INTO `oxo_colors` (`id`, `name`, `hex`, `created_at`) VALUES ('2', 'Off-White Linen', '#faf9f6', '2026-07-09 11:43:22');
INSERT INTO `oxo_colors` (`id`, `name`, `hex`, `created_at`) VALUES ('3', 'Gold Sand', '#bf8f54', '2026-07-09 11:43:22');
INSERT INTO `oxo_colors` (`id`, `name`, `hex`, `created_at`) VALUES ('4', 'Smoked Oak', '#4a3b32', '2026-07-09 11:43:22');
INSERT INTO `oxo_colors` (`id`, `name`, `hex`, `created_at`) VALUES ('5', 'Red', '#E74C3C', '2026-07-23 09:21:50');
INSERT INTO `oxo_colors` (`id`, `name`, `hex`, `created_at`) VALUES ('6', 'Fairy Pink', '#E84393', '2026-07-23 09:21:50');
INSERT INTO `oxo_colors` (`id`, `name`, `hex`, `created_at`) VALUES ('7', 'Green', '#2ECC71', '2026-07-23 09:21:50');
INSERT INTO `oxo_colors` (`id`, `name`, `hex`, `created_at`) VALUES ('8', 'Yellow', '#F1C40F', '2026-07-23 09:21:50');
INSERT INTO `oxo_colors` (`id`, `name`, `hex`, `created_at`) VALUES ('9', 'Pepsi Blue', '#3498DB', '2026-07-23 09:21:50');
INSERT INTO `oxo_colors` (`id`, `name`, `hex`, `created_at`) VALUES ('10', 'Pink', '#E84393', '2026-07-23 09:21:52');
INSERT INTO `oxo_colors` (`id`, `name`, `hex`, `created_at`) VALUES ('11', 'Blue', '#3498DB', '2026-07-23 09:21:57');
INSERT INTO `oxo_colors` (`id`, `name`, `hex`, `created_at`) VALUES ('12', 'Teak', '#A0522D', '2026-07-23 10:06:08');
INSERT INTO `oxo_colors` (`id`, `name`, `hex`, `created_at`) VALUES ('13', 'Black', '#1A1A1A', '2026-07-23 12:29:58');
INSERT INTO `oxo_colors` (`id`, `name`, `hex`, `created_at`) VALUES ('14', 'White', '#FAF9F6', '2026-07-23 12:30:05');
INSERT INTO `oxo_colors` (`id`, `name`, `hex`, `created_at`) VALUES ('15', 'Brown', '#5C4033', '2026-07-23 12:30:32');
INSERT INTO `oxo_colors` (`id`, `name`, `hex`, `created_at`) VALUES ('16', 'Orange', '#E67E22', '2026-07-23 12:31:11');
INSERT INTO `oxo_colors` (`id`, `name`, `hex`, `created_at`) VALUES ('17', 'Walnut', '#4A3B32', '2026-07-28 10:41:06');
INSERT INTO `oxo_colors` (`id`, `name`, `hex`, `created_at`) VALUES ('18', 'Gray', '#95A5A6', '2026-08-03 20:43:11');
INSERT INTO `oxo_colors` (`id`, `name`, `hex`, `created_at`) VALUES ('19', 'Black & Red', '#E74C3C', '2026-08-03 20:52:22');
INSERT INTO `oxo_colors` (`id`, `name`, `hex`, `created_at`) VALUES ('20', 'Black & Blue', '#3498DB', '2026-08-03 20:52:22');
INSERT INTO `oxo_colors` (`id`, `name`, `hex`, `created_at`) VALUES ('21', 'Black & Beige', '#1A1A1A', '2026-08-03 20:52:22');
INSERT INTO `oxo_colors` (`id`, `name`, `hex`, `created_at`) VALUES ('22', 'Black &amp; Red', '#E74C3C', '2026-08-03 20:52:22');
INSERT INTO `oxo_colors` (`id`, `name`, `hex`, `created_at`) VALUES ('23', 'Black &amp; Blue', '#3498DB', '2026-08-03 20:52:22');
INSERT INTO `oxo_colors` (`id`, `name`, `hex`, `created_at`) VALUES ('24', 'Black &amp; Beige', '#1A1A1A', '2026-08-03 20:52:22');
INSERT INTO `oxo_colors` (`id`, `name`, `hex`, `created_at`) VALUES ('25', 'Beige', '#F5F5DC', '2026-08-03 20:53:20');

-- --------------------------------------------------------
-- Table structure for table `oxo_consultations`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `oxo_consultations`;
CREATE TABLE `oxo_consultations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `product_title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `status` varchar(50) DEFAULT 'Pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `whatsapp` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table `oxo_consultations` (5 rows)
INSERT INTO `oxo_consultations` (`id`, `name`, `email`, `product_title`, `message`, `status`, `created_at`, `whatsapp`) VALUES ('1', 'SOJIN MATHEW', 'sojinmathew1040@gmail.com', 'General Contact', 'hello', 'Addressed', '2026-07-13 11:35:29', '9946020724');
INSERT INTO `oxo_consultations` (`id`, `name`, `email`, `product_title`, `message`, `status`, `created_at`, `whatsapp`) VALUES ('2', 'SOJIN MATHEW', 'sojinmathew1040@gmail.com', 'General Contact', 'hi', 'Addressed', '2026-07-13 11:39:34', '9946020724');
INSERT INTO `oxo_consultations` (`id`, `name`, `email`, `product_title`, `message`, `status`, `created_at`, `whatsapp`) VALUES ('3', 'SOJIN MATHEW', 'sojinmathew1040@gmail.com', 'test', 'is it avalable', 'Addressed', '2026-07-13 15:18:04', '8943804920');
INSERT INTO `oxo_consultations` (`id`, `name`, `email`, `product_title`, `message`, `status`, `created_at`, `whatsapp`) VALUES ('4', 'SOJIN MATHEW', 'sojinmathew1040@gmail.com', 'test', 'hi', 'Addressed', '2026-07-21 09:48:18', '9946020724');
INSERT INTO `oxo_consultations` (`id`, `name`, `email`, `product_title`, `message`, `status`, `created_at`, `whatsapp`) VALUES ('5', 'Test User', 'testuser@example.com', 'Nirvana Modular Sofa', 'Need a custom length', 'Addressed', '2026-07-21 09:54:06', '9999999999');

-- --------------------------------------------------------
-- Table structure for table `oxo_materials`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `oxo_materials`;
CREATE TABLE `oxo_materials` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `slug` varchar(50) NOT NULL,
  `name` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=699 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table `oxo_materials` (7 rows)
INSERT INTO `oxo_materials` (`id`, `slug`, `name`, `created_at`) VALUES ('1', 'wood', 'Solid Wood', '2026-07-09 09:18:24');
INSERT INTO `oxo_materials` (`id`, `slug`, `name`, `created_at`) VALUES ('2', 'metal', 'Brushed Metal', '2026-07-09 09:18:24');
INSERT INTO `oxo_materials` (`id`, `slug`, `name`, `created_at`) VALUES ('3', 'glass', 'Tempered Glass', '2026-07-09 09:18:24');
INSERT INTO `oxo_materials` (`id`, `slug`, `name`, `created_at`) VALUES ('4', 'fabric', 'Organic Fabric', '2026-07-09 09:18:24');
INSERT INTO `oxo_materials` (`id`, `slug`, `name`, `created_at`) VALUES ('5', 'plastic', 'Recycled Plastic', '2026-07-09 09:18:24');
INSERT INTO `oxo_materials` (`id`, `slug`, `name`, `created_at`) VALUES ('8', 'leather', 'Leather', '2026-07-23 11:44:48');
INSERT INTO `oxo_materials` (`id`, `slug`, `name`, `created_at`) VALUES ('12', 'marble', 'Marble', '2026-07-23 11:44:48');

-- --------------------------------------------------------
-- Table structure for table `oxo_products`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `oxo_products`;
CREATE TABLE `oxo_products` (
  `id` varchar(50) NOT NULL,
  `title` varchar(255) NOT NULL,
  `price` int(11) NOT NULL,
  `category` varchar(100) NOT NULL,
  `image` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `specs` text NOT NULL,
  `details` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `material_slug` varchar(100) DEFAULT 'wood',
  `brand_id` int(11) DEFAULT NULL,
  `gallery` text DEFAULT NULL,
  `height_cm` int(11) DEFAULT 85,
  `width_cm` int(11) DEFAULT 100,
  `length_cm` int(11) DEFAULT 240,
  `color_id` int(11) DEFAULT NULL,
  `color_ids` text DEFAULT NULL,
  `source_url` text DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table `oxo_products` (11 rows)
INSERT INTO `oxo_products` (`id`, `title`, `price`, `category`, `image`, `description`, `specs`, `details`, `created_at`, `material_slug`, `brand_id`, `gallery`, `height_cm`, `width_cm`, `length_cm`, `color_id`, `color_ids`, `source_url`) VALUES ('chair-1', 'Aurelia Accent Chair', '85000', 'chairs', 'assets/images/chair_1.png', 'A sculptural masterpiece featuring a curved silhouette and cozy boucle upholstery, anchored securely by solid smoked oak wood legs.', 'Dimensions: W: 85cm x D: 80cm x H: 75cm | Fabric: Premium Boucle | Legs: Solid Smoked Oak', '{\"Material\":\"Luxury heavyweight boucle yarn (75% acrylic, 25% wool) with soft tactile texture.\",\"Construction\":\"Contoured steel inner frame padded with multi-density ergonomic foam.\",\"Care Instructions\":\"Blot spills immediately with a clean, dry white cloth. Do not rub.\",\"Shipping\":\"Fully assembled. Ships in custom reinforced wooden crate. Delivered in 3-5 business days.\"}', '2026-07-09 08:44:00', 'wood', NULL, NULL, '85', '100', '240', NULL, NULL, NULL);
INSERT INTO `oxo_products` (`id`, `title`, `price`, `category`, `image`, `description`, `specs`, `details`, `created_at`, `material_slug`, `brand_id`, `gallery`, `height_cm`, `width_cm`, `length_cm`, `color_id`, `color_ids`, `source_url`) VALUES ('chair-2', 'Vesper Lounge Chair', '95000', 'chairs', 'assets/images/chair_2.png', 'A sleek combination of hand-woven premium saddle leather and a matte black powder-coated steel frame. A perfect minimalist statement.', 'Dimensions: W: 70cm x D: 75cm x H: 80cm | Material: Top-grain Italian Leather | Frame: Alloy Steel', '{\"Material\":\"4mm thick vegetable-tanned, top-grain Italian saddle leather strap weave.\",\"Construction\":\"TIG-welded seamless steel pipe frame with satin-black powder-coated finish.\",\"Care Instructions\":\"Treat with high-quality leather conditioner twice a year. Keep away from direct sunlight.\",\"Shipping\":\"Fully assembled. Free threshold delivery. Ships in 4-6 business days.\"}', '2026-07-09 08:44:00', 'wood', NULL, NULL, '85', '100', '240', NULL, NULL, NULL);
INSERT INTO `oxo_products` (`id`, `title`, `price`, `category`, `image`, `description`, `specs`, `details`, `created_at`, `material_slug`, `brand_id`, `gallery`, `height_cm`, `width_cm`, `length_cm`, `color_id`, `color_ids`, `source_url`) VALUES ('light-2', 'Solstice Floor Lamp', '65000', 'lighting', 'assets/images/light_2.png', 'An elegant floor lamp crafted from a solid green marble base and hand-blown frosted glass sphere diffuser.', 'Dimensions: H: 165cm x Dia: 35cm | Base: Verde Guatemala Marble | Glass: Mouth-blown opal glass', '{\"Material\":\"Verde Guatemala marble base (honed), solid brass frame stem, hand-blown acid-etched glass globe.\",\"Illumination\":\"E27 bulb socket (10W warm dimmable LED bulb included). Foot switch on cloth-wrapped cord.\",\"Care Instructions\":\"Wipe globe with damp glass cleaner cloth. Clean base with stone sealer friendly wipes.\",\"Shipping\":\"Delivered in two packages (globe + base assembly). Ships in 3-5 business days.\"}', '2026-07-09 08:44:00', 'wood', NULL, NULL, '85', '100', '240', NULL, NULL, NULL);
INSERT INTO `oxo_products` (`id`, `title`, `price`, `category`, `image`, `description`, `specs`, `details`, `created_at`, `material_slug`, `brand_id`, `gallery`, `height_cm`, `width_cm`, `length_cm`, `color_id`, `color_ids`, `source_url`) VALUES ('nk-nilkamal-genius-ludo-table-with-2-chairs-set', 'Nilkamal Genius Ludo Table + 2 Chairs Kid\'s Study Set', '2370', 'chairs', 'assets/images/uploads/nilkamal/nilkamal-genius-ludo-table-with-2-chairs-set_img_0_1784778710.jpg', 'Nilkamal Genius Ludo Table + 2 Chairs Kid\'s Study Set', 'Brand: Nilkamal Furniture | Model: Nilkamal Genius Ludo Table + 2 Chairs Kid\'s Study Set | SKU: K2GENIUS5260NWBRDN', '{\"Material\":\"Home Furniture\",\"Construction\":\"Engineered for high durability and ergonomic support.\",\"Care Instructions\":\"Wipe clean with a dry or slightly damp cloth. Avoid harsh chemicals.\",\"Shipping\":\"Delivered directly to doorstep. Free standard shipping included.\"}', '2026-07-23 09:14:01', 'wood', '9', '[{\"path\":\"assets\\/images\\/uploads\\/nilkamal\\/nilkamal-genius-ludo-table-with-2-chairs-set_img_0_1784778710.jpg\",\"color_id\":5},{\"path\":\"assets\\/images\\/uploads\\/nilkamal\\/nilkamal-genius-ludo-table-with-2-chairs-set_img_1_1784778710.jpg\",\"color_id\":5},{\"path\":\"assets\\/images\\/uploads\\/nilkamal\\/nilkamal-genius-ludo-table-with-2-chairs-set_img_2_1784778711.jpg\",\"color_id\":5},{\"path\":\"assets\\/images\\/uploads\\/nilkamal\\/nilkamal-genius-ludo-table-with-2-chairs-set_img_3_1784778711.jpg\",\"color_id\":5},{\"path\":\"assets\\/images\\/uploads\\/nilkamal\\/nilkamal-genius-ludo-table-with-2-chairs-set_img_4_1784778711.jpg\",\"color_id\":5},{\"path\":\"assets\\/images\\/uploads\\/nilkamal\\/nilkamal-genius-ludo-table-with-2-chairs-set_img_5_1784778711.jpg\",\"color_id\":5},{\"path\":\"assets\\/images\\/uploads\\/nilkamal\\/nilkamal-genius-ludo-table-with-2-chairs-set_img_6_1784778711.jpg\",\"color_id\":5},{\"path\":\"assets\\/images\\/uploads\\/nilkamal\\/nilkamal-genius-ludo-table-with-2-chairs-set_img_7_1784778711.jpg\",\"color_id\":5},{\"path\":\"assets\\/images\\/uploads\\/nilkamal\\/nilkamal-genius-ludo-table-with-2-chairs-set_img_8_1784778711.jpg\",\"color_id\":5},{\"path\":\"assets\\/images\\/uploads\\/nilkamal\\/nilkamal-genius-ludo-table-with-2-chairs-set_img_9_1784778711.jpg\",\"color_id\":5},{\"path\":\"assets\\/images\\/uploads\\/nilkamal\\/nilkamal-genius-ludo-table-with-2-chairs-set_img_10_1784778712.jpg\",\"color_id\":5},{\"path\":\"assets\\/images\\/uploads\\/nilkamal\\/nilkamal-genius-ludo-table-with-2-chairs-set_img_11_1784778712.jpg\",\"color_id\":5},{\"path\":\"assets\\/images\\/uploads\\/nilkamal\\/nilkamal-genius-ludo-table-with-2-chairs-set_img_12_1784778712.jpg\",\"color_id\":5},{\"path\":\"assets\\/images\\/uploads\\/nilkamal\\/nilkamal-genius-ludo-table-with-2-chairs-set_img_13_1784778712.jpg\",\"color_id\":5},{\"path\":\"assets\\/images\\/uploads\\/nilkamal\\/nilkamal-genius-ludo-table-with-2-chairs-set_img_14_1784778712.jpg\",\"color_id\":6},{\"path\":\"assets\\/images\\/uploads\\/nilkamal\\/nilkamal-genius-ludo-table-with-2-chairs-set_img_15_1784778712.jpg\",\"color_id\":10},{\"path\":\"assets\\/images\\/uploads\\/nilkamal\\/nilkamal-genius-ludo-table-with-2-chairs-set_img_16_1784778712.jpg\",\"color_id\":10},{\"path\":\"assets\\/images\\/uploads\\/nilkamal\\/nilkamal-genius-ludo-table-with-2-chairs-set_img_17_1784778713.jpg\",\"color_id\":10},{\"path\":\"assets\\/images\\/uploads\\/nilkamal\\/nilkamal-genius-ludo-table-with-2-chairs-set_img_18_1784778713.jpg\",\"color_id\":10},{\"path\":\"assets\\/images\\/uploads\\/nilkamal\\/nilkamal-genius-ludo-table-with-2-chairs-set_img_19_1784778713.jpg\",\"color_id\":10},{\"path\":\"assets\\/images\\/uploads\\/nilkamal\\/nilkamal-genius-ludo-table-with-2-chairs-set_img_20_1784778713.jpg\",\"color_id\":10},{\"path\":\"assets\\/images\\/uploads\\/nilkamal\\/nilkamal-genius-ludo-table-with-2-chairs-set_img_21_1784778713.jpg\",\"color_id\":10},{\"path\":\"assets\\/images\\/uploads\\/nilkamal\\/nilkamal-genius-ludo-table-with-2-chairs-set_img_22_1784778713.jpg\",\"color_id\":10},{\"path\":\"assets\\/images\\/uploads\\/nilkamal\\/nilkamal-genius-ludo-table-with-2-chairs-set_img_23_1784778713.jpg\",\"color_id\":10},{\"path\":\"assets\\/images\\/uploads\\/nilkamal\\/nilkamal-genius-ludo-table-with-2-chairs-set_img_24_1784778713.jpg\",\"color_id\":10},{\"path\":\"assets\\/images\\/uploads\\/nilkamal\\/nilkamal-genius-ludo-table-with-2-chairs-set_img_25_1784778713.jpg\",\"color_id\":10},{\"path\":\"assets\\/images\\/uploads\\/nilkamal\\/nilkamal-genius-ludo-table-with-2-chairs-set_img_26_1784778714.jpg\",\"color_id\":7},{\"path\":\"assets\\/images\\/uploads\\/nilkamal\\/nilkamal-genius-ludo-table-with-2-chairs-set_img_27_1784778714.jpg\",\"color_id\":null},{\"path\":\"assets\\/images\\/uploads\\/nilkamal\\/nilkamal-genius-ludo-table-with-2-chairs-set_img_28_1784778714.jpg\",\"color_id\":7},{\"path\":\"assets\\/images\\/uploads\\/nilkamal\\/nilkamal-genius-ludo-table-with-2-chairs-set_img_29_1784778714.jpg\",\"color_id\":7},{\"path\":\"assets\\/images\\/uploads\\/nilkamal\\/nilkamal-genius-ludo-table-with-2-chairs-set_img_30_1784778714.jpg\",\"color_id\":7},{\"path\":\"assets\\/images\\/uploads\\/nilkamal\\/nilkamal-genius-ludo-table-with-2-chairs-set_img_31_1784778714.jpg\",\"color_id\":7},{\"path\":\"assets\\/images\\/uploads\\/nilkamal\\/nilkamal-genius-ludo-table-with-2-chairs-set_img_32_1784778714.jpg\",\"color_id\":7},{\"path\":\"assets\\/images\\/uploads\\/nilkamal\\/nilkamal-genius-ludo-table-with-2-chairs-set_img_33_1784778714.jpg\",\"color_id\":7},{\"path\":\"assets\\/images\\/uploads\\/nilkamal\\/nilkamal-genius-ludo-table-with-2-chairs-set_img_34_1784778714.jpg\",\"color_id\":7},{\"path\":\"assets\\/images\\/uploads\\/nilkamal\\/nilkamal-genius-ludo-table-with-2-chairs-set_img_35_1784778715.jpg\",\"color_id\":7},{\"path\":\"assets\\/images\\/uploads\\/nilkamal\\/nilkamal-genius-ludo-table-with-2-chairs-set_img_36_1784778715.jpg\",\"color_id\":7},{\"path\":\"assets\\/images\\/uploads\\/nilkamal\\/nilkamal-genius-ludo-table-with-2-chairs-set_img_37_1784778715.jpg\",\"color_id\":7},{\"path\":\"assets\\/images\\/uploads\\/nilkamal\\/nilkamal-genius-ludo-table-with-2-chairs-set_img_38_1784778715.jpg\",\"color_id\":7},{\"path\":\"assets\\/images\\/uploads\\/nilkamal\\/nilkamal-genius-ludo-table-with-2-chairs-set_img_39_1784778715.jpg\",\"color_id\":7},{\"path\":\"assets\\/images\\/uploads\\/nilkamal\\/nilkamal-genius-ludo-table-with-2-chairs-set_img_40_1784778715.jpg\",\"color_id\":7},{\"path\":\"assets\\/images\\/uploads\\/nilkamal\\/nilkamal-genius-ludo-table-with-2-chairs-set_img_41_1784778715.jpg\",\"color_id\":8},{\"path\":\"assets\\/images\\/uploads\\/nilkamal\\/nilkamal-genius-ludo-table-with-2-chairs-set_img_42_1784778715.jpg\",\"color_id\":8},{\"path\":\"assets\\/images\\/uploads\\/nilkamal\\/nilkamal-genius-ludo-table-with-2-chairs-set_img_43_1784778716.jpg\",\"color_id\":8},{\"path\":\"assets\\/images\\/uploads\\/nilkamal\\/nilkamal-genius-ludo-table-with-2-chairs-set_img_44_1784778716.jpg\",\"color_id\":8},{\"path\":\"assets\\/images\\/uploads\\/nilkamal\\/nilkamal-genius-ludo-table-with-2-chairs-set_img_45_1784778716.jpg\",\"color_id\":8},{\"path\":\"assets\\/images\\/uploads\\/nilkamal\\/nilkamal-genius-ludo-table-with-2-chairs-set_img_46_1784778716.jpg\",\"color_id\":8},{\"path\":\"assets\\/images\\/uploads\\/nilkamal\\/nilkamal-genius-ludo-table-with-2-chairs-set_img_47_1784778716.jpg\",\"color_id\":8},{\"path\":\"assets\\/images\\/uploads\\/nilkamal\\/nilkamal-genius-ludo-table-with-2-chairs-set_img_48_1784778716.jpg\",\"color_id\":8},{\"path\":\"assets\\/images\\/uploads\\/nilkamal\\/nilkamal-genius-ludo-table-with-2-chairs-set_img_49_1784778716.jpg\",\"color_id\":8},{\"path\":\"assets\\/images\\/uploads\\/nilkamal\\/nilkamal-genius-ludo-table-with-2-chairs-set_img_50_1784778716.jpg\",\"color_id\":8},{\"path\":\"assets\\/images\\/uploads\\/nilkamal\\/nilkamal-genius-ludo-table-with-2-chairs-set_img_51_1784778717.jpg\",\"color_id\":8},{\"path\":\"assets\\/images\\/uploads\\/nilkamal\\/nilkamal-genius-ludo-table-with-2-chairs-set_img_52_1784778717.jpg\",\"color_id\":11},{\"path\":\"assets\\/images\\/uploads\\/nilkamal\\/nilkamal-genius-ludo-table-with-2-chairs-set_img_53_1784778717.jpg\",\"color_id\":11},{\"path\":\"assets\\/images\\/uploads\\/nilkamal\\/nilkamal-genius-ludo-table-with-2-chairs-set_img_54_1784778717.jpg\",\"color_id\":9},{\"path\":\"assets\\/images\\/uploads\\/nilkamal\\/nilkamal-genius-ludo-table-with-2-chairs-set_img_55_1784778717.jpg\",\"color_id\":11},{\"path\":\"assets\\/images\\/uploads\\/nilkamal\\/nilkamal-genius-ludo-table-with-2-chairs-set_img_56_1784778717.jpg\",\"color_id\":11},{\"path\":\"assets\\/images\\/uploads\\/nilkamal\\/nilkamal-genius-ludo-table-with-2-chairs-set_img_57_1784778717.jpg\",\"color_id\":11},{\"path\":\"assets\\/images\\/uploads\\/nilkamal\\/nilkamal-genius-ludo-table-with-2-chairs-set_img_58_1784778717.jpg\",\"color_id\":11},{\"path\":\"assets\\/images\\/uploads\\/nilkamal\\/nilkamal-genius-ludo-table-with-2-chairs-set_img_59_1784778718.jpg\",\"color_id\":11},{\"path\":\"assets\\/images\\/uploads\\/nilkamal\\/nilkamal-genius-ludo-table-with-2-chairs-set_img_60_1784778718.jpg\",\"color_id\":11},{\"path\":\"assets\\/images\\/uploads\\/nilkamal\\/nilkamal-genius-ludo-table-with-2-chairs-set_img_61_1784778718.jpg\",\"color_id\":11},{\"path\":\"assets\\/images\\/uploads\\/nilkamal\\/nilkamal-genius-ludo-table-with-2-chairs-set_img_62_1784778718.jpg\",\"color_id\":11},{\"path\":\"assets\\/images\\/uploads\\/nilkamal\\/nilkamal-genius-ludo-table-with-2-chairs-set_img_63_1784778718.jpg\",\"color_id\":11},{\"path\":\"assets\\/images\\/uploads\\/nilkamal\\/nilkamal-genius-ludo-table-with-2-chairs-set_img_64_1784778718.jpg\",\"color_id\":11}]', '85', '100', '240', '5', '[5,6,7,8,9,10,11]', NULL);
INSERT INTO `oxo_products` (`id`, `title`, `price`, `category`, `image`, `description`, `specs`, `details`, `created_at`, `material_slug`, `brand_id`, `gallery`, `height_cm`, `width_cm`, `length_cm`, `color_id`, `color_ids`, `source_url`) VALUES ('sofa-1', 'Nirvana Modular Sofa', '185000', 'sofas', 'assets/images/uploads/sofa-1_asset_0_1783571329.jpg', 'A plush, deep-seated modular sofa wrapped in premium performance linen. Experience ultimate luxury, tailored scale, and custom modular configurations.', 'Dimensions: W: 100cm x D: 240cm x H: 85cm', '{\"Material\":\"Performance textured linen (80% polyester, 20% linen), stain-resistant and durable.\",\"Construction\":\"Double-doweled kiln-dried birch wood frame with pocket coil spring suspension.\",\"Care Instructions\":\"Professional upholstery cleaning recommended. Vacuum weekly using a soft brush attachment.\",\"Shipping\":\"Delivered in 3 modular sections. Free white-glove inside delivery & assembly within 7-10 business days.\"}', '2026-07-09 08:44:00', 'wood', '6', '[\"assets\\/images\\/sofa_1.png\"]', '85', '100', '240', NULL, NULL, NULL);
INSERT INTO `oxo_products` (`id`, `title`, `price`, `category`, `image`, `description`, `specs`, `details`, `created_at`, `material_slug`, `brand_id`, `gallery`, `height_cm`, `width_cm`, `length_cm`, `color_id`, `color_ids`, `source_url`) VALUES ('sup-supreme-web-plastic-77', 'Supreme Web Plastic Without-Arm Chair', '4720', 'chairs', 'assets/images/uploads/supremefurniture/sup-supreme-web-plastic-77_img_0_1784790200.jpg', 'A marvel of modern design, highly creative web patterned design and bright colours making it ideal for contemporary homes & offices, call centers, cafeterias and other locations. ', 'Brand Partner | Model: Supreme Web Plastic Without-Arm Chair | SKU: SUP-SUPREME-WEB-PLASTIC-77', '{\"Material\":\"Plastic\",\"Construction\":\"Engineered for luxury durability & silent ergonomic comfort.\",\"Care Instructions\":\"Wipe clean with a soft dry cloth. Avoid abrasive cleaners.\",\"Shipping\":\"White-glove doorstep delivery and inside setup included.\"}', '2026-07-23 12:33:36', 'plastic', '11', '[{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-web-plastic-77_img_0_1784790200.jpg\",\"color_id\":13},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-web-plastic-77_img_1_1784790200.jpg\",\"color_id\":null},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-web-plastic-77_img_2_1784790201.jpg\",\"color_id\":null},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-web-plastic-77_img_3_1784790201.jpg\",\"color_id\":null},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-web-plastic-77_img_4_1784790201.jpg\",\"color_id\":null},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-web-plastic-77_img_5_1784790201.jpg\",\"color_id\":null},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-web-plastic-77_img_6_1784790201.jpg\",\"color_id\":null},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-web-plastic-77_img_7_1784790201.jpg\",\"color_id\":null},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-web-plastic-77_img_8_1784790201.jpg\",\"color_id\":null},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-web-plastic-77_img_9_1784790202.jpg\",\"color_id\":null},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-web-plastic-77_img_10_1784790202.jpg\",\"color_id\":null},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-web-plastic-77_img_11_1784790202.jpg\",\"color_id\":null},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-web-plastic-77_img_12_1784790202.jpg\",\"color_id\":14},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-web-plastic-77_img_13_1784790202.jpg\",\"color_id\":null},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-web-plastic-77_img_14_1784790202.jpg\",\"color_id\":null},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-web-plastic-77_img_15_1784790202.jpg\",\"color_id\":null},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-web-plastic-77_img_16_1784790203.jpg\",\"color_id\":null},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-web-plastic-77_img_17_1784790203.jpg\",\"color_id\":null},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-web-plastic-77_img_18_1784790203.jpg\",\"color_id\":null},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-web-plastic-77_img_19_1784790203.jpg\",\"color_id\":null},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-web-plastic-77_img_20_1784790203.jpg\",\"color_id\":null},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-web-plastic-77_img_21_1784790203.jpg\",\"color_id\":null},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-web-plastic-77_img_22_1784790203.jpg\",\"color_id\":null},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-web-plastic-77_img_23_1784790203.jpg\",\"color_id\":null},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-web-plastic-77_img_24_1784790204.jpg\",\"color_id\":null},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-web-plastic-77_img_25_1784790204.jpg\",\"color_id\":5},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-web-plastic-77_img_26_1784790204.jpg\",\"color_id\":null},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-web-plastic-77_img_27_1784790204.jpg\",\"color_id\":null},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-web-plastic-77_img_28_1784790204.jpg\",\"color_id\":null},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-web-plastic-77_img_29_1784790204.jpg\",\"color_id\":null},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-web-plastic-77_img_30_1784790204.jpg\",\"color_id\":null},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-web-plastic-77_img_31_1784790205.jpg\",\"color_id\":null},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-web-plastic-77_img_32_1784790205.jpg\",\"color_id\":null},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-web-plastic-77_img_33_1784790205.jpg\",\"color_id\":null},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-web-plastic-77_img_34_1784790205.jpg\",\"color_id\":null},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-web-plastic-77_img_35_1784790205.jpg\",\"color_id\":null},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-web-plastic-77_img_36_1784790205.jpg\",\"color_id\":null},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-web-plastic-77_img_37_1784790205.jpg\",\"color_id\":null},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-web-plastic-77_img_38_1784790206.jpg\",\"color_id\":15},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-web-plastic-77_img_39_1784790206.jpg\",\"color_id\":null},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-web-plastic-77_img_40_1784790206.jpg\",\"color_id\":null},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-web-plastic-77_img_41_1784790206.jpg\",\"color_id\":null},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-web-plastic-77_img_42_1784790206.jpg\",\"color_id\":null},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-web-plastic-77_img_43_1784790206.jpg\",\"color_id\":null},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-web-plastic-77_img_44_1784790206.jpg\",\"color_id\":null},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-web-plastic-77_img_45_1784790207.jpg\",\"color_id\":null},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-web-plastic-77_img_46_1784790207.jpg\",\"color_id\":null},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-web-plastic-77_img_47_1784790207.jpg\",\"color_id\":null},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-web-plastic-77_img_48_1784790207.jpg\",\"color_id\":null},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-web-plastic-77_img_49_1784790207.jpg\",\"color_id\":null},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-web-plastic-77_img_50_1784790207.jpg\",\"color_id\":7},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-web-plastic-77_img_51_1784790207.jpg\",\"color_id\":null},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-web-plastic-77_img_52_1784790208.jpg\",\"color_id\":null},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-web-plastic-77_img_53_1784790208.jpg\",\"color_id\":null},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-web-plastic-77_img_54_1784790208.jpg\",\"color_id\":null},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-web-plastic-77_img_55_1784790208.jpg\",\"color_id\":null},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-web-plastic-77_img_56_1784790208.jpg\",\"color_id\":null},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-web-plastic-77_img_57_1784790208.jpg\",\"color_id\":null},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-web-plastic-77_img_58_1784790208.jpg\",\"color_id\":null},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-web-plastic-77_img_59_1784790208.jpg\",\"color_id\":null},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-web-plastic-77_img_60_1784790209.jpg\",\"color_id\":null},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-web-plastic-77_img_61_1784790209.jpg\",\"color_id\":null},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-web-plastic-77_img_62_1784790209.jpg\",\"color_id\":7},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-web-plastic-77_img_63_1784790209.jpg\",\"color_id\":null},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-web-plastic-77_img_64_1784790209.jpg\",\"color_id\":null},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-web-plastic-77_img_65_1784790209.jpg\",\"color_id\":null},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-web-plastic-77_img_66_1784790210.jpg\",\"color_id\":null},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-web-plastic-77_img_67_1784790210.jpg\",\"color_id\":null},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-web-plastic-77_img_68_1784790210.jpg\",\"color_id\":null},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-web-plastic-77_img_69_1784790210.jpg\",\"color_id\":null},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-web-plastic-77_img_70_1784790210.jpg\",\"color_id\":null},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-web-plastic-77_img_71_1784790210.jpg\",\"color_id\":null},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-web-plastic-77_img_72_1784790210.jpg\",\"color_id\":null},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-web-plastic-77_img_73_1784790211.jpg\",\"color_id\":null},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-web-plastic-77_img_74_1784790211.jpg\",\"color_id\":8},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-web-plastic-77_img_75_1784790211.jpg\",\"color_id\":null},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-web-plastic-77_img_76_1784790211.jpg\",\"color_id\":null},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-web-plastic-77_img_77_1784790211.jpg\",\"color_id\":null},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-web-plastic-77_img_78_1784790211.jpg\",\"color_id\":null},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-web-plastic-77_img_79_1784790211.jpg\",\"color_id\":null},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-web-plastic-77_img_80_1784790212.jpg\",\"color_id\":null},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-web-plastic-77_img_81_1784790212.jpg\",\"color_id\":null},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-web-plastic-77_img_82_1784790212.jpg\",\"color_id\":null},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-web-plastic-77_img_83_1784790212.jpg\",\"color_id\":null},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-web-plastic-77_img_84_1784790212.jpg\",\"color_id\":null},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-web-plastic-77_img_85_1784790212.jpg\",\"color_id\":null},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-web-plastic-77_img_86_1784790212.jpg\",\"color_id\":11},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-web-plastic-77_img_87_1784790213.jpg\",\"color_id\":null},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-web-plastic-77_img_88_1784790213.jpg\",\"color_id\":null},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-web-plastic-77_img_89_1784790213.jpg\",\"color_id\":null},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-web-plastic-77_img_90_1784790213.jpg\",\"color_id\":null},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-web-plastic-77_img_91_1784790213.jpg\",\"color_id\":null},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-web-plastic-77_img_92_1784790213.jpg\",\"color_id\":null},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-web-plastic-77_img_93_1784790213.jpg\",\"color_id\":null},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-web-plastic-77_img_94_1784790213.jpg\",\"color_id\":null},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-web-plastic-77_img_95_1784790214.jpg\",\"color_id\":null},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-web-plastic-77_img_96_1784790214.jpg\",\"color_id\":null},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-web-plastic-77_img_97_1784790214.jpg\",\"color_id\":null},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-web-plastic-77_img_98_1784790214.jpg\",\"color_id\":16},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-web-plastic-77_img_99_1784790214.jpg\",\"color_id\":null},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-web-plastic-77_img_100_1784790214.jpg\",\"color_id\":null},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-web-plastic-77_img_101_1784790214.jpg\",\"color_id\":null},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-web-plastic-77_img_102_1784790214.jpg\",\"color_id\":null},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-web-plastic-77_img_103_1784790215.jpg\",\"color_id\":null},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-web-plastic-77_img_104_1784790215.jpg\",\"color_id\":null},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-web-plastic-77_img_105_1784790215.jpg\",\"color_id\":16},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-web-plastic-77_img_106_1784790215.jpg\",\"color_id\":null},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-web-plastic-77_img_107_1784790215.jpg\",\"color_id\":null},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-web-plastic-77_img_108_1784790215.jpg\",\"color_id\":null},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-web-plastic-77_img_109_1784790216.jpg\",\"color_id\":null}]', '525', '510', '2', '13', '[13,14,5,15,7,8,11,16]', NULL);
INSERT INTO `oxo_products` (`id`, `title`, `price`, `category`, `image`, `description`, `specs`, `details`, `created_at`, `material_slug`, `brand_id`, `gallery`, `height_cm`, `width_cm`, `length_cm`, `color_id`, `color_ids`, `source_url`) VALUES ('table-1', 'Zephyr Dining Table', '240000', 'tables', 'assets/images/table_1.png', 'A monolithic dining table crafted from solid Calacatta marble. Featuring soft chamfered edges and cylindrical fluted pedestals.', 'Dimensions: L: 200cm x W: 100cm x H: 75cm | Top: Italian Calacatta Marble | Base: Marble fluting', '{\"Material\":\"Genuine Italian Calacatta Oro marble top with honed matte finish, pre-sealed.\",\"Construction\":\"Fibre-reinforced concrete structural inner core clad in natural fluted marble tiles.\",\"Care Instructions\":\"Always use coasters. Wipe with warm water and neutral pH stone soap. Avoid acidic cleaners.\",\"Shipping\":\"Extremely heavy (280kg). Ships in 2 crates. Requires white-glove setup (included) in 12-15 business days.\"}', '2026-07-09 08:44:00', 'wood', NULL, NULL, '85', '100', '240', NULL, NULL, NULL);
INSERT INTO `oxo_products` (`id`, `title`, `price`, `category`, `image`, `description`, `specs`, `details`, `created_at`, `material_slug`, `brand_id`, `gallery`, `height_cm`, `width_cm`, `length_cm`, `color_id`, `color_ids`, `source_url`) VALUES ('table-2', 'Helios Coffee Table', '120000', 'tables', 'assets/images/table_2.png', 'A circular, low-profile coffee table combining a dark green travertine top with brushed brass metal accent trim.', 'Dimensions: Dia: 90cm x H: 38cm | Stone: Green Travertine | Detailing: Solid Brushed Brass', '{\"Material\":\"Iranian Forest Green travertine with natural cavities left unfilled for organic textures.\",\"Construction\":\"Solid travertine base with thick solid brass inlay ring detail.\",\"Care Instructions\":\"Clean with dry microfiber cloth. Promptly clean liquids (especially wine\\/coffee) to prevent staining.\",\"Shipping\":\"Crated delivery. Requires basic assembly (attaching top to base). Ships in 5-7 business days.\"}', '2026-07-09 08:44:00', 'wood', NULL, NULL, '85', '100', '240', NULL, NULL, NULL);
INSERT INTO `oxo_products` (`id`, `title`, `price`, `category`, `image`, `description`, `specs`, `details`, `created_at`, `material_slug`, `brand_id`, `gallery`, `height_cm`, `width_cm`, `length_cm`, `color_id`, `color_ids`, `source_url`) VALUES ('test', 'test', '15626', 'lighting', 'assets/images/uploads/test_asset_0_1783589735.webp', 'Contrary to popular belief, Lorem Ipsum is not simply random text. It has roots in a piece of classical Latin literature from 45 BC, making it over 2000 years old. Richard McClintock, a Latin professor at Hampden-Sydney College in Virginia, looked up one of the more obscure Latin words, consectetur, from a Lorem Ipsum passage, and going through the cites of the word in classical literature, discovered the undoubtable source. Lorem Ipsum comes from sections 1.10.32 and 1.10.33 of \"de Finibus Bonorum et Malorum\" (The Extremes of Good and Evil) by Cicero, written in 45 BC. This book is a treatise on the theory of ethics, very popular during the Renaissance. The first line of Lorem Ipsum, \"Lorem ipsum dolor sit amet..\", comes from a line in section 1.10.32.', 'Dimensions: W: 100cm x D: 240cm x H: 85cm', '{\"Material\":\"\",\"Construction\":\"\",\"Care Instructions\":\"\",\"Shipping\":\"\"}', '2026-07-09 15:05:35', 'fabric', '6', '[{\"path\":\"assets\\/images\\/uploads\\/test_asset_1_1783589735.webp\",\"color_id\":3},{\"path\":\"assets\\/images\\/uploads\\/test_asset_2_1783589735.webp\",\"color_id\":3},{\"path\":\"assets\\/images\\/uploads\\/test_asset_3_1783589735.webp\",\"color_id\":1},{\"path\":\"assets\\/images\\/uploads\\/test_asset_4_1783589735.webp\",\"color_id\":1},{\"path\":\"assets\\/images\\/uploads\\/test_asset_5_1783589735.webp\",\"color_id\":1}]', '85', '100', '240', '3', '[1,3]', NULL);
INSERT INTO `oxo_products` (`id`, `title`, `price`, `category`, `image`, `description`, `specs`, `details`, `created_at`, `material_slug`, `brand_id`, `gallery`, `height_cm`, `width_cm`, `length_cm`, `color_id`, `color_ids`, `source_url`) VALUES ('www-mono-coffee-table', 'Nilkamal Mono Coffee Table (Legno Oak/Frosty White)', '4490', 'tables', 'assets/images/uploads/nilkamalfurniture/www-mono-coffee-table_img_0_1785216107.jpg', 'Nilkamal Mono Coffee Table (Legno Oak/Frosty White)', 'Brand Partner | Model: Nilkamal Mono Coffee Table (Legno Oak/Frosty White) | SKU: WWW-MONO-COFFEE-TABLE', '{\"Material\":\"Wood\",\"Construction\":\"Engineered for luxury durability & silent ergonomic comfort.\",\"Care Instructions\":\"Wipe clean with a soft dry cloth. Avoid abrasive cleaners.\",\"Shipping\":\"White-glove doorstep delivery and inside setup included.\"}', '2026-07-28 10:51:55', 'wood', '9', '[{\"path\":\"assets\\/images\\/uploads\\/nilkamalfurniture\\/www-mono-coffee-table_img_0_1785216107.jpg\",\"color_id\":14},{\"path\":\"assets\\/images\\/uploads\\/nilkamalfurniture\\/www-mono-coffee-table_img_1_1785216107.jpg\",\"color_id\":14},{\"path\":\"assets\\/images\\/uploads\\/nilkamalfurniture\\/www-mono-coffee-table_img_2_1785216108.jpg\",\"color_id\":14},{\"path\":\"assets\\/images\\/uploads\\/nilkamalfurniture\\/www-mono-coffee-table_img_3_1785216109.jpg\",\"color_id\":14},{\"path\":\"assets\\/images\\/uploads\\/nilkamalfurniture\\/www-mono-coffee-table_img_4_1785216109.jpg\",\"color_id\":14},{\"path\":\"assets\\/images\\/uploads\\/nilkamalfurniture\\/www-mono-coffee-table_img_5_1785216110.jpg\",\"color_id\":14},{\"path\":\"assets\\/images\\/uploads\\/nilkamalfurniture\\/www-mono-coffee-table_img_6_1785216110.jpg\",\"color_id\":14},{\"path\":\"assets\\/images\\/uploads\\/nilkamalfurniture\\/www-mono-coffee-table_img_7_1785216111.jpg\",\"color_id\":14},{\"path\":\"assets\\/images\\/uploads\\/nilkamalfurniture\\/www-mono-coffee-table_img_8_1785216112.jpg\",\"color_id\":14},{\"path\":\"assets\\/images\\/uploads\\/nilkamalfurniture\\/www-mono-coffee-table_img_9_1785216112.jpg\",\"color_id\":14},{\"path\":\"assets\\/images\\/uploads\\/nilkamalfurniture\\/www-mono-coffee-table_img_10_1785216113.jpg\",\"color_id\":14},{\"path\":\"assets\\/images\\/uploads\\/nilkamalfurniture\\/www-mono-coffee-table_img_11_1785216114.jpg\",\"color_id\":14},{\"path\":\"assets\\/images\\/uploads\\/nilkamalfurniture\\/www-mono-coffee-table_img_12_1785216114.jpg\",\"color_id\":14}]', NULL, '2', NULL, '14', '[14]', 'https://www.nilkamalfurniture.com/products/nilkamal-mono-engineered-wood-coffee-table-legno-oak-frosty-white');
INSERT INTO `oxo_products` (`id`, `title`, `price`, `category`, `image`, `description`, `specs`, `details`, `created_at`, `material_slug`, `brand_id`, `gallery`, `height_cm`, `width_cm`, `length_cm`, `color_id`, `color_ids`, `source_url`) VALUES ('www-mozart-door-mirror', 'Nilkamal Mozart 4 Door Mirror Wardrobe (Walnut)', '29890', 'storage', 'assets/images/uploads/nilkamalfurniture/www-mozart-door-mirror_img_0_1785216057.webp', 'Nilkamal Mozart 4 Door Mirror Wardrobe (Walnut)', 'Brand Partner | Model: Nilkamal Mozart 4 Door Mirror Wardrobe (Walnut) | SKU: WWW-MOZART-DOOR-MIRROR', '{\"Material\":\"Wood\",\"Construction\":\"Engineered for luxury durability & silent ergonomic comfort.\",\"Care Instructions\":\"Wipe clean with a soft dry cloth. Avoid abrasive cleaners.\",\"Shipping\":\"White-glove doorstep delivery and inside setup included.\"}', '2026-07-28 10:50:59', 'wood', '9', '[{\"path\":\"assets\\/images\\/uploads\\/nilkamalfurniture\\/www-mozart-door-mirror_img_0_1785216057.webp\",\"color_id\":17},{\"path\":\"assets\\/images\\/uploads\\/nilkamalfurniture\\/www-mozart-door-mirror_img_1_1785216058.webp\",\"color_id\":17},{\"path\":\"assets\\/images\\/uploads\\/nilkamalfurniture\\/www-mozart-door-mirror_img_2_1785216058.webp\",\"color_id\":17},{\"path\":\"assets\\/images\\/uploads\\/nilkamalfurniture\\/www-mozart-door-mirror_img_3_1785216058.webp\",\"color_id\":17},{\"path\":\"assets\\/images\\/uploads\\/nilkamalfurniture\\/www-mozart-door-mirror_img_4_1785216058.webp\",\"color_id\":17},{\"path\":\"assets\\/images\\/uploads\\/nilkamalfurniture\\/www-mozart-door-mirror_img_5_1785216058.webp\",\"color_id\":17},{\"path\":\"assets\\/images\\/uploads\\/nilkamalfurniture\\/www-mozart-door-mirror_img_6_1785216059.webp\",\"color_id\":17}]', NULL, NULL, NULL, '17', '[17]', 'https://www.nilkamalfurniture.com/products/nilkamal-mozart-4-door-mirror-wardrobe-walnut');

-- --------------------------------------------------------
-- Table structure for table `oxo_users`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `oxo_users`;
CREATE TABLE `oxo_users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table `oxo_users` (2 rows)
INSERT INTO `oxo_users` (`id`, `name`, `email`, `password`, `phone`, `address`, `created_at`) VALUES ('1', 'Sojinmathew10testuser Oxo', 'sojinmathew10testuser.oxo@gmail.com40@gmail.com', '$2y$10$kY4HFZJDRzq05.eTk5tgLODp6fim1BXk.9VxpnEWvTrDtwVvGOKnO', NULL, NULL, '2026-07-13 10:49:36');
INSERT INTO `oxo_users` (`id`, `name`, `email`, `password`, `phone`, `address`, `created_at`) VALUES ('2', 'SOJIN MATHEW', 'sojinmathew1040@gmail.com', '$2y$10$G8XppuI4MowCDwB3noLvOu0timWNZ5giBCeRBPRkOuMwZ2NA.v73q', NULL, NULL, '2026-07-13 10:53:40');

-- --------------------------------------------------------
-- Table structure for table `product_addons`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `product_addons`;
CREATE TABLE `product_addons` (
  `product_id` int(11) NOT NULL,
  `addon_product_id` int(11) NOT NULL,
  PRIMARY KEY (`product_id`,`addon_product_id`),
  KEY `addon_product_id` (`addon_product_id`),
  CONSTRAINT `product_addons_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `product_addons_ibfk_2` FOREIGN KEY (`addon_product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `product_attribute_values`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `product_attribute_values`;
CREATE TABLE `product_attribute_values` (
  `product_id` int(11) NOT NULL,
  `attribute_id` int(11) NOT NULL,
  `value` text NOT NULL,
  PRIMARY KEY (`product_id`,`attribute_id`),
  KEY `attribute_id` (`attribute_id`),
  CONSTRAINT `product_attribute_values_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `product_attribute_values_ibfk_2` FOREIGN KEY (`attribute_id`) REFERENCES `attributes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table `product_attribute_values` (24 rows)
INSERT INTO `product_attribute_values` (`product_id`, `attribute_id`, `value`) VALUES ('3', '12', 'Opal Glass & Brushed Brass');
INSERT INTO `product_attribute_values` (`product_id`, `attribute_id`, `value`) VALUES ('3', '13', 'Ø 45 cm, Cord L 200 cm');
INSERT INTO `product_attribute_values` (`product_id`, `attribute_id`, `value`) VALUES ('3', '14', 'Elena Rossi, 2023');
INSERT INTO `product_attribute_values` (`product_id`, `attribute_id`, `value`) VALUES ('3', '15', 'Murano, Italy');
INSERT INTO `product_attribute_values` (`product_id`, `attribute_id`, `value`) VALUES ('4', '12', 'Solid White Oak & Walnut details');
INSERT INTO `product_attribute_values` (`product_id`, `attribute_id`, `value`) VALUES ('4', '13', 'W 180 x D 45 x H 65 cm');
INSERT INTO `product_attribute_values` (`product_id`, `attribute_id`, `value`) VALUES ('4', '14', 'Kenji Tanaka');
INSERT INTO `product_attribute_values` (`product_id`, `attribute_id`, `value`) VALUES ('4', '15', 'Kyoto, Japan');
INSERT INTO `product_attribute_values` (`product_id`, `attribute_id`, `value`) VALUES ('5', '12', 'Multi-density Foam & Premium Bouclé');
INSERT INTO `product_attribute_values` (`product_id`, `attribute_id`, `value`) VALUES ('5', '13', 'W 280 x D 105 x H 68 cm');
INSERT INTO `product_attribute_values` (`product_id`, `attribute_id`, `value`) VALUES ('5', '14', 'Pierre Yovanovitch');
INSERT INTO `product_attribute_values` (`product_id`, `attribute_id`, `value`) VALUES ('5', '15', 'France');
INSERT INTO `product_attribute_values` (`product_id`, `attribute_id`, `value`) VALUES ('6', '12', 'American Walnut & Saddle Leather');
INSERT INTO `product_attribute_values` (`product_id`, `attribute_id`, `value`) VALUES ('6', '13', 'W 74 x D 76 x H 72 cm');
INSERT INTO `product_attribute_values` (`product_id`, `attribute_id`, `value`) VALUES ('6', '14', 'Studio OXO');
INSERT INTO `product_attribute_values` (`product_id`, `attribute_id`, `value`) VALUES ('6', '15', 'United States');
INSERT INTO `product_attribute_values` (`product_id`, `attribute_id`, `value`) VALUES ('7', '12', 'Honed Italian Travertine');
INSERT INTO `product_attribute_values` (`product_id`, `attribute_id`, `value`) VALUES ('7', '13', 'W 110 x D 90 x H 30 cm');
INSERT INTO `product_attribute_values` (`product_id`, `attribute_id`, `value`) VALUES ('7', '14', 'Mateo Falcone');
INSERT INTO `product_attribute_values` (`product_id`, `attribute_id`, `value`) VALUES ('7', '15', 'Tuscany, Italy');
INSERT INTO `product_attribute_values` (`product_id`, `attribute_id`, `value`) VALUES ('8', '12', 'Carbon Steel & Aluminum');
INSERT INTO `product_attribute_values` (`product_id`, `attribute_id`, `value`) VALUES ('8', '13', 'Reach 180 cm, Max H 210 cm');
INSERT INTO `product_attribute_values` (`product_id`, `attribute_id`, `value`) VALUES ('8', '14', 'Achille Castiglioni Re-edition');
INSERT INTO `product_attribute_values` (`product_id`, `attribute_id`, `value`) VALUES ('8', '15', 'Germany');

-- --------------------------------------------------------
-- Table structure for table `product_variant_combinations`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `product_variant_combinations`;
CREATE TABLE `product_variant_combinations` (
  `product_variant_id` int(11) NOT NULL,
  `variant_option_value_id` int(11) NOT NULL,
  PRIMARY KEY (`product_variant_id`,`variant_option_value_id`),
  KEY `variant_option_value_id` (`variant_option_value_id`),
  CONSTRAINT `product_variant_combinations_ibfk_1` FOREIGN KEY (`product_variant_id`) REFERENCES `product_variants` (`id`) ON DELETE CASCADE,
  CONSTRAINT `product_variant_combinations_ibfk_2` FOREIGN KEY (`variant_option_value_id`) REFERENCES `variant_option_values` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `product_variants`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `product_variants`;
CREATE TABLE `product_variants` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `sku` varchar(100) NOT NULL,
  `price_modifier` decimal(10,2) DEFAULT 0.00,
  `stock_count` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `sku` (`sku`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `product_variants_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `products`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `products`;
CREATE TABLE `products` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `sale_price` decimal(10,2) DEFAULT NULL,
  `category` varchar(100) NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `brand` varchar(255) NOT NULL,
  `image` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `material` varchar(255) DEFAULT '',
  `dimensions` varchar(255) DEFAULT '',
  `designer` varchar(255) DEFAULT '',
  `origin` varchar(255) DEFAULT '',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_product_category` (`category_id`),
  CONSTRAINT `fk_product_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table `products` (6 rows)
INSERT INTO `products` (`id`, `name`, `price`, `sale_price`, `category`, `category_id`, `brand`, `image`, `description`, `material`, `dimensions`, `designer`, `origin`, `created_at`) VALUES ('3', 'Ocular Pendant Light', '890.00', NULL, 'Lighting', '5', 'Rossi Illuminazione', 'assets/images/light_1.png', 'An elegant spherical hanging luminaire that casts a warm, soft diffuse glow. Crafted with hand-blown sandblasted opal glass and brushed brass metal components.', 'Opal Glass & Brushed Brass', 'Ø 45 cm, Cord L 200 cm', 'Elena Rossi, 2023', 'Murano, Italy', '2026-07-07 14:32:46');
INSERT INTO `products` (`id`, `name`, `price`, `sale_price`, `category`, `category_id`, `brand`, `image`, `description`, `material`, `dimensions`, `designer`, `origin`, `created_at`) VALUES ('4', 'Kyoto Oak Credenza', '4500.00', NULL, 'Sofas', '6', 'Tanaka Atelier', 'assets/images/storage_1.png', 'Inspired by traditional Japanese joinery, this low credenza offers sleek sliding slatted panels, hidden soft-close drawers, and raw tactile timber finishes.', 'Solid White Oak & Walnut details', 'W 180 x D 45 x H 65 cm', 'Kenji Tanaka', 'Kyoto, Japan', '2026-07-07 14:32:46');
INSERT INTO `products` (`id`, `name`, `price`, `sale_price`, `category`, `category_id`, `brand`, `image`, `description`, `material`, `dimensions`, `designer`, `origin`, `created_at`) VALUES ('5', 'Tectonic Bouclé Sofa', '6800.00', NULL, 'Sofas', '6', 'Yovanovitch Design', 'assets/images/sofa_1.png', 'A low-profile modular sofa composed of monolithic block cushions. Configured to hug the floor, providing deep, immersive comfort with a highly premium contemporary stance.', 'Multi-density Foam & Premium Bouclé', 'W 280 x D 105 x H 68 cm', 'Pierre Yovanovitch', 'France', '2026-07-07 14:32:46');
INSERT INTO `products` (`id`, `name`, `price`, `sale_price`, `category`, `category_id`, `brand`, `image`, `description`, `material`, `dimensions`, `designer`, `origin`, `created_at`) VALUES ('6', 'Ark Armchair', '1200.00', NULL, 'Chairs', '1', 'Studio OXO', 'assets/images/chair_2.png', 'A stark architectural silhouette constructed from heavy curved walnut plates holding a suspended full-grain black leather sling seat.', 'American Walnut & Saddle Leather', 'W 74 x D 76 x H 72 cm', 'Studio OXO', 'United States', '2026-07-07 14:32:46');
INSERT INTO `products` (`id`, `name`, `price`, `sale_price`, `category`, `category_id`, `brand`, `image`, `description`, `material`, `dimensions`, `designer`, `origin`, `created_at`) VALUES ('7', 'Travertine Coffee Table', '2100.00', NULL, 'Tables', '4', 'Falcone Marmi', 'assets/images/table_2.png', 'An organic-shaped low coffee table sculpted entirely from premium honed Italian travertine stone, displaying beautiful natural gas pores and rich limestone sediment banding.', 'Honed Italian Travertine', 'W 110 x D 90 x H 30 cm', 'Mateo Falcone', 'Tuscany, Italy', '2026-07-07 14:32:46');
INSERT INTO `products` (`id`, `name`, `price`, `sale_price`, `category`, `category_id`, `brand`, `image`, `description`, `material`, `dimensions`, `designer`, `origin`, `created_at`) VALUES ('8', 'Halogen Arc Floor Lamp', '1150.00', NULL, 'Lighting', '5', 'Castiglioni Studio', 'assets/images/light_2.png', 'A dramatic sweeping floor lamp designed to overhang seating or dining settings. Finished in matte carbon black with an adjustable focal shade.', 'Carbon Steel & Aluminum', 'Reach 180 cm, Max H 210 cm', 'Achille Castiglioni Re-edition', 'Germany', '2026-07-07 14:32:46');

-- --------------------------------------------------------
-- Table structure for table `variant_option_values`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `variant_option_values`;
CREATE TABLE `variant_option_values` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `variant_option_id` int(11) NOT NULL,
  `value` varchar(100) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `variant_option_id` (`variant_option_id`),
  CONSTRAINT `variant_option_values_ibfk_1` FOREIGN KEY (`variant_option_id`) REFERENCES `variant_options` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `variant_options`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `variant_options`;
CREATE TABLE `variant_options` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `variant_options_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS=1;
