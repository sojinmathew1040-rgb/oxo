-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: oxo_db
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `admins`
--

DROP TABLE IF EXISTS `admins`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `admins` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `admins`
--

LOCK TABLES `admins` WRITE;
/*!40000 ALTER TABLE `admins` DISABLE KEYS */;
INSERT INTO `admins` VALUES (1,'admin','$2y$10$UFnHeM8lcXQdXaryUSQetu12gDRNWRNcF0JPbbe5U1a7wsp6gkkWm','2026-07-07 09:02:46');
/*!40000 ALTER TABLE `admins` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `attributes`
--

DROP TABLE IF EXISTS `attributes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `attributes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `code` varchar(100) NOT NULL,
  `type` enum('text','select','toggle','number') NOT NULL,
  `options` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=31 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `attributes`
--

LOCK TABLES `attributes` WRITE;
/*!40000 ALTER TABLE `attributes` DISABLE KEYS */;
INSERT INTO `attributes` VALUES (1,'Upholstery Material','upholstery_material','select','[\"Velvet\",\"Leather\",\"Boucl\\u00e9\",\"Linen\"]'),(2,'Max Weight Capacity','max_weight_capacity','number',NULL),(3,'Ergonomic Rating','ergonomic_rating','number',NULL),(4,'Bed Size','bed_size','select','[\"King\",\"Queen\",\"Twin\"]'),(5,'Headboard Type','headboard_type','text',NULL),(6,'Mattress Included','mattress_included','toggle',NULL),(7,'Number of Drawers','number_of_drawers','number',NULL),(8,'Cable Management Holes','cable_management_holes','toggle',NULL),(9,'Material Type','material_type','select','[\"Oak\",\"Walnut\",\"Steel\"]'),(10,'Shape','shape','text',NULL),(11,'Seating Capacity','seating_capacity','number',NULL),(12,'Material','material','text',NULL),(13,'Dimensions','dimensions','text',NULL),(14,'Designer','designer','text',NULL),(15,'Origin','origin','text',NULL);
/*!40000 ALTER TABLE `attributes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `categories`
--

DROP TABLE IF EXISTS `categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `categories`
--

LOCK TABLES `categories` WRITE;
/*!40000 ALTER TABLE `categories` DISABLE KEYS */;
INSERT INTO `categories` VALUES (1,'Chair','chair'),(2,'Cot Set','cot-set'),(3,'Study Table','study-table'),(4,'Table','table'),(5,'Lighting','lighting'),(6,'Sofas','sofas');
/*!40000 ALTER TABLE `categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `category_attributes`
--

DROP TABLE IF EXISTS `category_attributes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `category_attributes` (
  `category_id` int(11) NOT NULL,
  `attribute_id` int(11) NOT NULL,
  PRIMARY KEY (`category_id`,`attribute_id`),
  KEY `attribute_id` (`attribute_id`),
  CONSTRAINT `category_attributes_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE,
  CONSTRAINT `category_attributes_ibfk_2` FOREIGN KEY (`attribute_id`) REFERENCES `attributes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `category_attributes`
--

LOCK TABLES `category_attributes` WRITE;
/*!40000 ALTER TABLE `category_attributes` DISABLE KEYS */;
INSERT INTO `category_attributes` VALUES (1,1),(1,2),(1,3),(2,4),(2,5),(2,6),(3,7),(3,8),(3,9),(4,10),(4,11);
/*!40000 ALTER TABLE `category_attributes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `oxo_admins`
--

DROP TABLE IF EXISTS `oxo_admins`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `oxo_admins` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `whatsapp` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `oxo_admins`
--

LOCK TABLES `oxo_admins` WRITE;
/*!40000 ALTER TABLE `oxo_admins` DISABLE KEYS */;
INSERT INTO `oxo_admins` VALUES (1,'admin','$2y$10$rhNISvpbHQtYKoKnsHiBA.xB2T/70bd28iEIb8OzGviUd0wvIKD/G','2026-07-09 03:13:19','8943804920');
/*!40000 ALTER TABLE `oxo_admins` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `oxo_announcements`
--

DROP TABLE IF EXISTS `oxo_announcements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `oxo_announcements` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) DEFAULT NULL,
  `subtitle` text DEFAULT NULL,
  `image_path` varchar(255) NOT NULL,
  `link_url` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `oxo_announcements`
--

LOCK TABLES `oxo_announcements` WRITE;
/*!40000 ALTER TABLE `oxo_announcements` DISABLE KEYS */;
/*!40000 ALTER TABLE `oxo_announcements` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `oxo_brands`
--

DROP TABLE IF EXISTS `oxo_brands`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `oxo_brands` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `logo_path` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `oxo_brands`
--

LOCK TABLES `oxo_brands` WRITE;
/*!40000 ALTER TABLE `oxo_brands` DISABLE KEYS */;
INSERT INTO `oxo_brands` VALUES (9,'Nilkamal Furniture','assets/images/uploads/brand_1784789164_856.png','2026-07-23 03:51:50'),(10,'Applecart','assets/images/uploads/brands/applecart_logo.png','2026-07-23 04:35:33'),(11,'Supreme Furniture','assets/images/uploads/brands/supremefurniture_logo.png','2026-07-23 06:59:28'),(12,'Indroyal','assets/images/uploads/brands/indroyal_logo.png','2026-07-28 05:34:59');
/*!40000 ALTER TABLE `oxo_brands` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `oxo_categories`
--

DROP TABLE IF EXISTS `oxo_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `oxo_categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `slug` varchar(50) NOT NULL,
  `name` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `bg_color` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `oxo_categories`
--

LOCK TABLES `oxo_categories` WRITE;
/*!40000 ALTER TABLE `oxo_categories` DISABLE KEYS */;
INSERT INTO `oxo_categories` VALUES (1,'sofas','Sofas & Recliners','2026-07-09 03:48:24','rgba(95, 173, 138, 0.03)'),(2,'chairs','Chairs & Seating','2026-07-09 03:48:24','#FAF9F6'),(3,'tables','Tables & Dining','2026-07-09 03:48:24','rgba(10, 46, 36, 0.02)'),(4,'lighting','Lighting & Decor','2026-07-09 03:48:24','rgba(200, 162, 118, 0.035)'),(5,'storage','Storage & Wardrobes','2026-07-09 03:48:24','rgba(30, 40, 36, 0.015)'),(6,'beds','Beds & Bedroom','2026-07-22 09:09:09','rgba(210, 180, 140, 0.03)'),(7,'study','Study & Office','2026-07-22 09:09:09','rgba(70, 130, 180, 0.03)'),(8,'tv-units','TV Units','2026-08-09 05:16:53','rgba(95, 173, 138, 0.03)');
/*!40000 ALTER TABLE `oxo_categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `oxo_colors`
--

DROP TABLE IF EXISTS `oxo_colors`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `oxo_colors` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `hex` varchar(7) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=44 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `oxo_colors`
--

LOCK TABLES `oxo_colors` WRITE;
/*!40000 ALTER TABLE `oxo_colors` DISABLE KEYS */;
INSERT INTO `oxo_colors` VALUES (1,'Charcoal Black','#1a1a1a','2026-07-09 06:13:22'),(2,'Off-White Linen','#faf9f6','2026-07-09 06:13:22'),(3,'Gold Sand','#bf8f54','2026-07-09 06:13:22'),(4,'Smoked Oak','#4a3b32','2026-07-09 06:13:22'),(5,'Red','#E74C3C','2026-07-23 03:51:50'),(6,'Fairy Pink','#E84393','2026-07-23 03:51:50'),(7,'Green','#2ECC71','2026-07-23 03:51:50'),(8,'Yellow','#F1C40F','2026-07-23 03:51:50'),(9,'Pepsi Blue','#3498DB','2026-07-23 03:51:50'),(10,'Pink','#E84393','2026-07-23 03:51:52'),(11,'Blue','#3498DB','2026-07-23 03:51:57'),(12,'Teak','#A0522D','2026-07-23 04:36:08'),(13,'Black','#1A1A1A','2026-07-23 06:59:58'),(14,'White','#FAF9F6','2026-07-23 07:00:05'),(15,'Brown','#5C4033','2026-07-23 07:00:32'),(16,'Orange','#E67E22','2026-07-23 07:01:11'),(17,'Walnut','#4A3B32','2026-07-28 05:11:06'),(18,'Gray','#95A5A6','2026-08-03 15:13:11'),(19,'Black & Red','#E74C3C','2026-08-03 15:22:22'),(20,'Black & Blue','#3498DB','2026-08-03 15:22:22'),(21,'Black & Beige','#1A1A1A','2026-08-03 15:22:22'),(22,'Black &amp; Red','#E74C3C','2026-08-03 15:22:22'),(23,'Black &amp; Blue','#3498DB','2026-08-03 15:22:22'),(24,'Black &amp; Beige','#1A1A1A','2026-08-03 15:22:22'),(25,'Beige','#F5F5DC','2026-08-03 15:23:20'),(26,'MULTI-COLOUR','#333333','2026-08-04 08:18:57'),(27,'AQUA BLUE','#3498DB','2026-08-04 08:18:57'),(28,'MEHANDI GREEN','#2ECC71','2026-08-04 09:32:22'),(29,'GLOBUS BROWN','#5C4033','2026-08-04 09:32:22'),(30,'G. BROWN/DARK BEIGE','#5C4033','2026-08-04 09:44:18'),(31,'BLUE/RED','#E74C3C','2026-08-04 09:44:18'),(32,'CHARCOAL/SKY GREY','#95A5A6','2026-08-04 09:44:18'),(33,'MEHANDI GREEN/LEMON YELLOW','#2ECC71','2026-08-04 09:44:18'),(34,'SOFT BLUE','#3498DB','2026-08-05 06:43:35'),(35,'PINK/VIOLET','#E84393','2026-08-05 06:46:52'),(36,'Grey','#95A5A6','2026-08-05 06:59:34'),(37,'Red & Iron Black','#E74C3C','2026-08-05 07:15:16'),(38,'Bright Red','#E74C3C','2026-08-05 07:15:16'),(39,'Green & Black','#2ECC71','2026-08-05 07:15:16'),(40,'Red &amp; Iron Black','#E74C3C','2026-08-05 07:15:16'),(41,'Green &amp; Black','#2ECC71','2026-08-05 07:15:16'),(42,'Lush Green','#2ECC71','2026-08-05 07:19:00'),(43,'Season Rust Brown','#5C4033','2026-08-05 07:19:00');
/*!40000 ALTER TABLE `oxo_colors` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `oxo_consultations`
--

DROP TABLE IF EXISTS `oxo_consultations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `oxo_consultations`
--

LOCK TABLES `oxo_consultations` WRITE;
/*!40000 ALTER TABLE `oxo_consultations` DISABLE KEYS */;
INSERT INTO `oxo_consultations` VALUES (1,'SOJIN MATHEW','sojinmathew1040@gmail.com','General Contact','hello','Addressed','2026-07-13 06:05:29','9946020724'),(2,'SOJIN MATHEW','sojinmathew1040@gmail.com','General Contact','hi','Addressed','2026-07-13 06:09:34','9946020724'),(3,'SOJIN MATHEW','sojinmathew1040@gmail.com','test','is it avalable','Addressed','2026-07-13 09:48:04','8943804920'),(4,'SOJIN MATHEW','sojinmathew1040@gmail.com','test','hi','Addressed','2026-07-21 04:18:18','9946020724'),(5,'Test User','testuser@example.com','Nirvana Modular Sofa','Need a custom length','Addressed','2026-07-21 04:24:06','9999999999');
/*!40000 ALTER TABLE `oxo_consultations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `oxo_materials`
--

DROP TABLE IF EXISTS `oxo_materials`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `oxo_materials` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `slug` varchar(50) NOT NULL,
  `name` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=1077 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `oxo_materials`
--

LOCK TABLES `oxo_materials` WRITE;
/*!40000 ALTER TABLE `oxo_materials` DISABLE KEYS */;
INSERT INTO `oxo_materials` VALUES (1,'wood','Solid Wood','2026-07-09 03:48:24'),(2,'metal','Brushed Metal','2026-07-09 03:48:24'),(3,'glass','Tempered Glass','2026-07-09 03:48:24'),(4,'fabric','Organic Fabric','2026-07-09 03:48:24'),(5,'plastic','Recycled Plastic','2026-07-09 03:48:24'),(8,'leather','Leather','2026-07-23 06:14:48'),(12,'marble','Marble','2026-07-23 06:14:48');
/*!40000 ALTER TABLE `oxo_materials` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `oxo_products`
--

DROP TABLE IF EXISTS `oxo_products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `oxo_products`
--

LOCK TABLES `oxo_products` WRITE;
/*!40000 ALTER TABLE `oxo_products` DISABLE KEYS */;
INSERT INTO `oxo_products` VALUES ('nk-nilkamal-genius-ludo-table-with-2-chairs-set','Nilkamal Genius Ludo Table + 2 Chairs Kid\'s Study Set',2370,'chairs','assets/images/uploads/nilkamal/nilkamal-genius-ludo-table-with-2-chairs-set_img_0_1784778710.jpg','Nilkamal Genius Ludo Table + 2 Chairs Kid\'s Study Set','Brand: Nilkamal Furniture | Model: Nilkamal Genius Ludo Table + 2 Chairs Kid\'s Study Set | SKU: K2GENIUS5260NWBRDN','{\"Material\":\"Home Furniture\",\"Construction\":\"Engineered for high durability and ergonomic support.\",\"Care Instructions\":\"Wipe clean with a dry or slightly damp cloth. Avoid harsh chemicals.\",\"Shipping\":\"Delivered directly to doorstep. Free standard shipping included.\"}','2026-07-23 03:44:01','wood',9,'[{\"path\":\"assets\\/images\\/uploads\\/nilkamal\\/nilkamal-genius-ludo-table-with-2-chairs-set_img_0_1784778710.jpg\",\"color_id\":7},{\"path\":\"assets\\/images\\/uploads\\/nilkamal\\/nilkamal-genius-ludo-table-with-2-chairs-set_img_1_1784778710.jpg\",\"color_id\":7},{\"path\":\"assets\\/images\\/uploads\\/nilkamal\\/nilkamal-genius-ludo-table-with-2-chairs-set_img_2_1784778711.jpg\",\"color_id\":7},{\"path\":\"assets\\/images\\/uploads\\/nilkamal\\/nilkamal-genius-ludo-table-with-2-chairs-set_img_3_1784778711.jpg\",\"color_id\":7},{\"path\":\"assets\\/images\\/uploads\\/nilkamal\\/nilkamal-genius-ludo-table-with-2-chairs-set_img_4_1784778711.jpg\",\"color_id\":7},{\"path\":\"assets\\/images\\/uploads\\/nilkamal\\/nilkamal-genius-ludo-table-with-2-chairs-set_img_5_1784778711.jpg\",\"color_id\":7},{\"path\":\"assets\\/images\\/uploads\\/nilkamal\\/nilkamal-genius-ludo-table-with-2-chairs-set_img_6_1784778711.jpg\",\"color_id\":7},{\"path\":\"assets\\/images\\/uploads\\/nilkamal\\/nilkamal-genius-ludo-table-with-2-chairs-set_img_7_1784778711.jpg\",\"color_id\":7},{\"path\":\"assets\\/images\\/uploads\\/nilkamal\\/nilkamal-genius-ludo-table-with-2-chairs-set_img_8_1784778711.jpg\",\"color_id\":7},{\"path\":\"assets\\/images\\/uploads\\/nilkamal\\/nilkamal-genius-ludo-table-with-2-chairs-set_img_9_1784778711.jpg\",\"color_id\":7},{\"path\":\"assets\\/images\\/uploads\\/nilkamal\\/nilkamal-genius-ludo-table-with-2-chairs-set_img_10_1784778712.jpg\",\"color_id\":7},{\"path\":\"assets\\/images\\/uploads\\/nilkamal\\/nilkamal-genius-ludo-table-with-2-chairs-set_img_11_1784778712.jpg\",\"color_id\":7},{\"path\":\"assets\\/images\\/uploads\\/nilkamal\\/nilkamal-genius-ludo-table-with-2-chairs-set_img_12_1784778712.jpg\",\"color_id\":10},{\"path\":\"assets\\/images\\/uploads\\/nilkamal\\/nilkamal-genius-ludo-table-with-2-chairs-set_img_13_1784778712.jpg\",\"color_id\":10},{\"path\":\"assets\\/images\\/uploads\\/nilkamal\\/nilkamal-genius-ludo-table-with-2-chairs-set_img_14_1784778712.jpg\",\"color_id\":10},{\"path\":\"assets\\/images\\/uploads\\/nilkamal\\/nilkamal-genius-ludo-table-with-2-chairs-set_img_15_1784778712.jpg\",\"color_id\":10},{\"path\":\"assets\\/images\\/uploads\\/nilkamal\\/nilkamal-genius-ludo-table-with-2-chairs-set_img_16_1784778712.jpg\",\"color_id\":10},{\"path\":\"assets\\/images\\/uploads\\/nilkamal\\/nilkamal-genius-ludo-table-with-2-chairs-set_img_17_1784778713.jpg\",\"color_id\":10},{\"path\":\"assets\\/images\\/uploads\\/nilkamal\\/nilkamal-genius-ludo-table-with-2-chairs-set_img_18_1784778713.jpg\",\"color_id\":43},{\"path\":\"assets\\/images\\/uploads\\/nilkamal\\/nilkamal-genius-ludo-table-with-2-chairs-set_img_19_1784778713.jpg\",\"color_id\":43},{\"path\":\"assets\\/images\\/uploads\\/nilkamal\\/nilkamal-genius-ludo-table-with-2-chairs-set_img_20_1784778713.jpg\",\"color_id\":43},{\"path\":\"assets\\/images\\/uploads\\/nilkamal\\/nilkamal-genius-ludo-table-with-2-chairs-set_img_21_1784778713.jpg\",\"color_id\":43},{\"path\":\"assets\\/images\\/uploads\\/nilkamal\\/nilkamal-genius-ludo-table-with-2-chairs-set_img_22_1784778713.jpg\",\"color_id\":43},{\"path\":\"assets\\/images\\/uploads\\/nilkamal\\/nilkamal-genius-ludo-table-with-2-chairs-set_img_23_1784778713.jpg\",\"color_id\":43},{\"path\":\"assets\\/images\\/uploads\\/nilkamal\\/nilkamal-genius-ludo-table-with-2-chairs-set_img_24_1784778713.jpg\",\"color_id\":43},{\"path\":\"assets\\/images\\/uploads\\/nilkamal\\/nilkamal-genius-ludo-table-with-2-chairs-set_img_25_1784778713.jpg\",\"color_id\":43},{\"path\":\"assets\\/images\\/uploads\\/nilkamal\\/nilkamal-genius-ludo-table-with-2-chairs-set_img_26_1784778714.jpg\",\"color_id\":43},{\"path\":\"assets\\/images\\/uploads\\/nilkamal\\/nilkamal-genius-ludo-table-with-2-chairs-set_img_27_1784778714.jpg\",\"color_id\":43},{\"path\":\"assets\\/images\\/uploads\\/nilkamal\\/nilkamal-genius-ludo-table-with-2-chairs-set_img_28_1784778714.jpg\",\"color_id\":43},{\"path\":\"assets\\/images\\/uploads\\/nilkamal\\/nilkamal-genius-ludo-table-with-2-chairs-set_img_29_1784778714.jpg\",\"color_id\":43},{\"path\":\"assets\\/images\\/uploads\\/nilkamal\\/nilkamal-genius-ludo-table-with-2-chairs-set_img_30_1784778714.jpg\",\"color_id\":43},{\"path\":\"assets\\/images\\/uploads\\/nilkamal\\/nilkamal-genius-ludo-table-with-2-chairs-set_img_31_1784778714.jpg\",\"color_id\":43},{\"path\":\"assets\\/images\\/uploads\\/nilkamal\\/nilkamal-genius-ludo-table-with-2-chairs-set_img_32_1784778714.jpg\",\"color_id\":43},{\"path\":\"assets\\/images\\/uploads\\/nilkamal\\/nilkamal-genius-ludo-table-with-2-chairs-set_img_33_1784778714.jpg\",\"color_id\":43},{\"path\":\"assets\\/images\\/uploads\\/nilkamal\\/nilkamal-genius-ludo-table-with-2-chairs-set_img_34_1784778714.jpg\",\"color_id\":43},{\"path\":\"assets\\/images\\/uploads\\/nilkamal\\/nilkamal-genius-ludo-table-with-2-chairs-set_img_35_1784778715.jpg\",\"color_id\":43},{\"path\":\"assets\\/images\\/uploads\\/nilkamal\\/nilkamal-genius-ludo-table-with-2-chairs-set_img_36_1784778715.jpg\",\"color_id\":43},{\"path\":\"assets\\/images\\/uploads\\/nilkamal\\/nilkamal-genius-ludo-table-with-2-chairs-set_img_37_1784778715.jpg\",\"color_id\":43},{\"path\":\"assets\\/images\\/uploads\\/nilkamal\\/nilkamal-genius-ludo-table-with-2-chairs-set_img_38_1784778715.jpg\",\"color_id\":43},{\"path\":\"assets\\/images\\/uploads\\/nilkamal\\/nilkamal-genius-ludo-table-with-2-chairs-set_img_39_1784778715.jpg\",\"color_id\":43},{\"path\":\"assets\\/images\\/uploads\\/nilkamal\\/nilkamal-genius-ludo-table-with-2-chairs-set_img_40_1784778715.jpg\",\"color_id\":43},{\"path\":\"assets\\/images\\/uploads\\/nilkamal\\/nilkamal-genius-ludo-table-with-2-chairs-set_img_41_1784778715.jpg\",\"color_id\":43},{\"path\":\"assets\\/images\\/uploads\\/nilkamal\\/nilkamal-genius-ludo-table-with-2-chairs-set_img_42_1784778715.jpg\",\"color_id\":43},{\"path\":\"assets\\/images\\/uploads\\/nilkamal\\/nilkamal-genius-ludo-table-with-2-chairs-set_img_43_1784778716.jpg\",\"color_id\":43},{\"path\":\"assets\\/images\\/uploads\\/nilkamal\\/nilkamal-genius-ludo-table-with-2-chairs-set_img_44_1784778716.jpg\",\"color_id\":43},{\"path\":\"assets\\/images\\/uploads\\/nilkamal\\/nilkamal-genius-ludo-table-with-2-chairs-set_img_45_1784778716.jpg\",\"color_id\":43},{\"path\":\"assets\\/images\\/uploads\\/nilkamal\\/nilkamal-genius-ludo-table-with-2-chairs-set_img_46_1784778716.jpg\",\"color_id\":43},{\"path\":\"assets\\/images\\/uploads\\/nilkamal\\/nilkamal-genius-ludo-table-with-2-chairs-set_img_47_1784778716.jpg\",\"color_id\":43},{\"path\":\"assets\\/images\\/uploads\\/nilkamal\\/nilkamal-genius-ludo-table-with-2-chairs-set_img_48_1784778716.jpg\",\"color_id\":43},{\"path\":\"assets\\/images\\/uploads\\/nilkamal\\/nilkamal-genius-ludo-table-with-2-chairs-set_img_49_1784778716.jpg\",\"color_id\":43},{\"path\":\"assets\\/images\\/uploads\\/nilkamal\\/nilkamal-genius-ludo-table-with-2-chairs-set_img_50_1784778716.jpg\",\"color_id\":43},{\"path\":\"assets\\/images\\/uploads\\/nilkamal\\/nilkamal-genius-ludo-table-with-2-chairs-set_img_51_1784778717.jpg\",\"color_id\":43},{\"path\":\"assets\\/images\\/uploads\\/nilkamal\\/nilkamal-genius-ludo-table-with-2-chairs-set_img_52_1784778717.jpg\",\"color_id\":43},{\"path\":\"assets\\/images\\/uploads\\/nilkamal\\/nilkamal-genius-ludo-table-with-2-chairs-set_img_53_1784778717.jpg\",\"color_id\":43},{\"path\":\"assets\\/images\\/uploads\\/nilkamal\\/nilkamal-genius-ludo-table-with-2-chairs-set_img_54_1784778717.jpg\",\"color_id\":43},{\"path\":\"assets\\/images\\/uploads\\/nilkamal\\/nilkamal-genius-ludo-table-with-2-chairs-set_img_55_1784778717.jpg\",\"color_id\":43},{\"path\":\"assets\\/images\\/uploads\\/nilkamal\\/nilkamal-genius-ludo-table-with-2-chairs-set_img_56_1784778717.jpg\",\"color_id\":43},{\"path\":\"assets\\/images\\/uploads\\/nilkamal\\/nilkamal-genius-ludo-table-with-2-chairs-set_img_57_1784778717.jpg\",\"color_id\":43},{\"path\":\"assets\\/images\\/uploads\\/nilkamal\\/nilkamal-genius-ludo-table-with-2-chairs-set_img_58_1784778717.jpg\",\"color_id\":43},{\"path\":\"assets\\/images\\/uploads\\/nilkamal\\/nilkamal-genius-ludo-table-with-2-chairs-set_img_59_1784778718.jpg\",\"color_id\":43},{\"path\":\"assets\\/images\\/uploads\\/nilkamal\\/nilkamal-genius-ludo-table-with-2-chairs-set_img_60_1784778718.jpg\",\"color_id\":43},{\"path\":\"assets\\/images\\/uploads\\/nilkamal\\/nilkamal-genius-ludo-table-with-2-chairs-set_img_61_1784778718.jpg\",\"color_id\":43},{\"path\":\"assets\\/images\\/uploads\\/nilkamal\\/nilkamal-genius-ludo-table-with-2-chairs-set_img_62_1784778718.jpg\",\"color_id\":43},{\"path\":\"assets\\/images\\/uploads\\/nilkamal\\/nilkamal-genius-ludo-table-with-2-chairs-set_img_63_1784778718.jpg\",\"color_id\":43},{\"path\":\"assets\\/images\\/uploads\\/nilkamal\\/nilkamal-genius-ludo-table-with-2-chairs-set_img_64_1784778718.jpg\",\"color_id\":43}]',85,100,240,5,'[5,6,7,8,9,10,11]',NULL),('sup-supreme-butterfly-plasti','Supreme Butterfly Plastic Kids Chair',1520,'chairs','assets/images/uploads/supremefurniture/sup-supreme-butterfly-plasti_img_0_1785912230.jpg','Designer looks, vibrant colours and especially designed for prolonged sittings for children. Ideal for indoor or outdoor kindergarten or day care centres.','Brand Partner | Model: Supreme Butterfly Plastic Kids Chair | SKU: SUP-SUPREME-BUTTERFLY-PLASTI','{\"Material\":\"Plastic\",\"Construction\":\"Engineered for luxury durability & silent ergonomic comfort.\",\"Care Instructions\":\"Wipe clean with a soft dry cloth. Avoid abrasive cleaners.\",\"Shipping\":\"White-glove doorstep delivery and inside setup included.\"}','2026-08-05 06:44:17','plastic',11,'[{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-butterfly-plasti_img_0_1785912230.jpg\",\"color_id\":42},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-butterfly-plasti_img_1_1785912230.jpg\",\"color_id\":42},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-butterfly-plasti_img_2_1785912231.jpg\",\"color_id\":42},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-butterfly-plasti_img_3_1785912233.jpg\",\"color_id\":42},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-butterfly-plasti_img_4_1785912233.jpg\",\"color_id\":42},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-butterfly-plasti_img_5_1785912234.jpg\",\"color_id\":42},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-butterfly-plasti_img_6_1785912235.jpg\",\"color_id\":42},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-butterfly-plasti_img_7_1785912236.jpg\",\"color_id\":42},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-butterfly-plasti_img_8_1785912236.jpg\",\"color_id\":42},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-butterfly-plasti_img_9_1785912237.jpg\",\"color_id\":42},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-butterfly-plasti_img_10_1785912238.jpg\",\"color_id\":42},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-butterfly-plasti_img_11_1785912238.jpg\",\"color_id\":42},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-butterfly-plasti_img_12_1785912239.jpg\",\"color_id\":10},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-butterfly-plasti_img_13_1785912239.jpg\",\"color_id\":10},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-butterfly-plasti_img_14_1785912240.jpg\",\"color_id\":10},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-butterfly-plasti_img_15_1785912242.jpg\",\"color_id\":10},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-butterfly-plasti_img_16_1785912243.jpg\",\"color_id\":10},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-butterfly-plasti_img_17_1785912244.jpg\",\"color_id\":10},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-butterfly-plasti_img_18_1785912244.jpg\",\"color_id\":43},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-butterfly-plasti_img_19_1785912245.jpg\",\"color_id\":43},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-butterfly-plasti_img_20_1785912246.jpg\",\"color_id\":43},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-butterfly-plasti_img_21_1785912246.jpg\",\"color_id\":43},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-butterfly-plasti_img_22_1785912247.jpg\",\"color_id\":43},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-butterfly-plasti_img_23_1785912248.jpg\",\"color_id\":43},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-butterfly-plasti_img_24_1785912248.jpg\",\"color_id\":43},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-butterfly-plasti_img_25_1785912250.jpg\",\"color_id\":43},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-butterfly-plasti_img_26_1785912251.jpg\",\"color_id\":43},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-butterfly-plasti_img_27_1785912251.jpg\",\"color_id\":43},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-butterfly-plasti_img_28_1785912252.jpg\",\"color_id\":43},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-butterfly-plasti_img_29_1785912253.jpg\",\"color_id\":43},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-butterfly-plasti_img_30_1785912253.jpg\",\"color_id\":43},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-butterfly-plasti_img_31_1785912254.jpg\",\"color_id\":43},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-butterfly-plasti_img_32_1785912255.jpg\",\"color_id\":43},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-butterfly-plasti_img_33_1785912256.jpg\",\"color_id\":43},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-butterfly-plasti_img_34_1785912256.jpg\",\"color_id\":43}]',370,2,2,16,'[16,10,5,34,8]','https://supremefurniture.co.in/products/baby-chair'),('sup-supreme-cherry-spectrum','Supreme Cherry With Spectrum',21040,'chairs','assets/images/uploads/supremefurniture/sup-supreme-cherry-spectrum_img_0_1785912341.jpg','Contemporary & Stylish Premium Matt Finish Chair with ergonomically designed arched back and wide seat for extra comfort. Very sturdy, stylish, this masterpiece is for modern homes and offices. Made with superior grade Glass reinforced polymers and Gas Injection Moulding Process. Exquisite looking lightweight folding table with Round top & foldable base frame. With their designer looks these tables enhance the aesthetics of any room.','Brand Partner | Model: Supreme Cherry With Spectrum | SKU: SUP-SUPREME-CHERRY-SPECTRUM','{\"Material\":\"Glass\",\"Construction\":\"Engineered for luxury durability & silent ergonomic comfort.\",\"Care Instructions\":\"Wipe clean with a soft dry cloth. Avoid abrasive cleaners.\",\"Shipping\":\"White-glove doorstep delivery and inside setup included.\"}','2026-08-05 06:45:50','glass',11,'[{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-cherry-spectrum_img_0_1785912341.jpg\",\"color_id\":28},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-cherry-spectrum_img_1_1785912342.jpg\",\"color_id\":28},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-cherry-spectrum_img_2_1785912342.jpg\",\"color_id\":28},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-cherry-spectrum_img_3_1785912343.jpg\",\"color_id\":28},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-cherry-spectrum_img_4_1785912344.jpg\",\"color_id\":28},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-cherry-spectrum_img_5_1785912345.jpg\",\"color_id\":28},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-cherry-spectrum_img_6_1785912346.jpg\",\"color_id\":28},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-cherry-spectrum_img_7_1785912347.jpg\",\"color_id\":28},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-cherry-spectrum_img_8_1785912347.jpg\",\"color_id\":28},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-cherry-spectrum_img_9_1785912349.jpg\",\"color_id\":28}]',720,590,0,28,'[28]','https://supremefurniture.co.in/products/cherry-with-spectrum'),('sup-supreme-congo-foldable','Supreme Congo Foldable Plastic Dining Table',6880,'tables','assets/images/uploads/supremefurniture/sup-supreme-congo-foldable_img_0_1785836603.jpg','A multi position blow moulded folding centre table giving you multiple height adjustment options. Perfect for cafeterias and banquet halls.','Brand Partner | Model: Supreme Congo Foldable Plastic Dining Table | SKU: SUP-SUPREME-CONGO-FOLDABLE','{\"Material\":\"Plastic\",\"Construction\":\"Engineered for luxury durability & silent ergonomic comfort.\",\"Care Instructions\":\"Wipe clean with a soft dry cloth. Avoid abrasive cleaners.\",\"Shipping\":\"White-glove doorstep delivery and inside setup included.\"}','2026-08-04 09:43:24','plastic',11,'[{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-congo-foldable_img_0_1785836603.jpg\",\"color_id\":28},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-congo-foldable_img_1_1785836603.jpg\",\"color_id\":28},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-congo-foldable_img_2_1785836603.jpg\",\"color_id\":28},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-congo-foldable_img_3_1785836603.jpg\",\"color_id\":28},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-congo-foldable_img_4_1785836603.jpg\",\"color_id\":28},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-congo-foldable_img_5_1785836603.jpg\",\"color_id\":28},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-congo-foldable_img_6_1785836603.jpg\",\"color_id\":28},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-congo-foldable_img_7_1785836603.jpg\",\"color_id\":28},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-congo-foldable_img_8_1785836603.jpg\",\"color_id\":28},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-congo-foldable_img_9_1785836603.jpg\",\"color_id\":28},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-congo-foldable_img_10_1785836603.jpg\",\"color_id\":28},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-congo-foldable_img_11_1785836603.jpg\",\"color_id\":28},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-congo-foldable_img_12_1785836604.jpg\",\"color_id\":10},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-congo-foldable_img_13_1785836604.jpg\",\"color_id\":10},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-congo-foldable_img_14_1785836604.jpg\",\"color_id\":10},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-congo-foldable_img_15_1785836604.jpg\",\"color_id\":10},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-congo-foldable_img_16_1785836604.jpg\",\"color_id\":10},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-congo-foldable_img_17_1785836604.jpg\",\"color_id\":10},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-congo-foldable_img_18_1785836604.jpg\",\"color_id\":29},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-congo-foldable_img_19_1785836604.jpg\",\"color_id\":29},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-congo-foldable_img_20_1785836604.jpg\",\"color_id\":29},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-congo-foldable_img_21_1785836604.jpg\",\"color_id\":29}]',610,1219,0,29,'[29,28]','https://supremefurniture.co.in/products/congo'),('sup-supreme-fusion-doll','Supreme Fusion Doll Maxi Plastic Storage Cabinet',8400,'storage','assets/images/uploads/supremefurniture/sup-supreme-fusion-doll_img_0_1785912417.jpg','Cupboard has specially-designed for girls in pink colour with decorative front panels book rack in homes & offices','Brand Partner | Model: Supreme Fusion Doll Maxi Plastic Storage Cabinet | SKU: SUP-SUPREME-FUSION-DOLL','{\"Material\":\"Plastic\",\"Construction\":\"Engineered for luxury durability & silent ergonomic comfort.\",\"Care Instructions\":\"Wipe clean with a soft dry cloth. Avoid abrasive cleaners.\",\"Shipping\":\"White-glove doorstep delivery and inside setup included.\"}','2026-08-05 06:47:01','plastic',11,'[{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-fusion-doll_img_0_1785912417.jpg\",\"color_id\":35},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-fusion-doll_img_1_1785912418.jpg\",\"color_id\":35},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-fusion-doll_img_2_1785912419.jpg\",\"color_id\":35},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-fusion-doll_img_3_1785912419.jpg\",\"color_id\":35},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-fusion-doll_img_4_1785912420.jpg\",\"color_id\":35},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-fusion-doll_img_5_1785912421.jpg\",\"color_id\":35}]',375,1,0,35,'[35]','https://supremefurniture.co.in/products/supreme-fusion-doll-maxi'),('sup-supreme-fusion-fg','Supreme Fusion 01 FG Plastic Storage Cabinet',4000,'storage','assets/images/uploads/supremefurniture/sup-supreme-fusion-fg_img_0_1785836666.jpg','Cupboard has specially-designed see-thru glass panels. Ideal for storage purpose in kitchen as book rack in homes offices','Brand Partner | Model: Supreme Fusion 01 FG Plastic Storage Cabinet | SKU: SUP-SUPREME-FUSION-FG','{\"Material\":\"Plastic\",\"Construction\":\"Engineered for luxury durability & silent ergonomic comfort.\",\"Care Instructions\":\"Wipe clean with a soft dry cloth. Avoid abrasive cleaners.\",\"Shipping\":\"White-glove doorstep delivery and inside setup included.\"}','2026-08-04 09:44:41','plastic',11,'[{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-fusion-fg_img_0_1785836666.jpg\",\"color_id\":33},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-fusion-fg_img_1_1785836666.jpg\",\"color_id\":33},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-fusion-fg_img_2_1785836667.jpg\",\"color_id\":33},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-fusion-fg_img_3_1785836668.jpg\",\"color_id\":33},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-fusion-fg_img_4_1785836668.jpg\",\"color_id\":33},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-fusion-fg_img_5_1785836669.jpg\",\"color_id\":33},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-fusion-fg_img_6_1785836670.jpg\",\"color_id\":33},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-fusion-fg_img_7_1785836671.jpg\",\"color_id\":33},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-fusion-fg_img_8_1785836672.jpg\",\"color_id\":33},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-fusion-fg_img_9_1785836673.jpg\",\"color_id\":33},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-fusion-fg_img_10_1785836673.jpg\",\"color_id\":33},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-fusion-fg_img_11_1785836674.jpg\",\"color_id\":33},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-fusion-fg_img_12_1785836675.jpg\",\"color_id\":10},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-fusion-fg_img_13_1785836676.jpg\",\"color_id\":10},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-fusion-fg_img_14_1785836676.jpg\",\"color_id\":10},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-fusion-fg_img_15_1785836677.jpg\",\"color_id\":10},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-fusion-fg_img_16_1785836678.jpg\",\"color_id\":10},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-fusion-fg_img_17_1785836678.jpg\",\"color_id\":10},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-fusion-fg_img_18_1785836679.jpg\",\"color_id\":30},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-fusion-fg_img_19_1785836680.jpg\",\"color_id\":30},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-fusion-fg_img_20_1785836680.jpg\",\"color_id\":30}]',375,1,0,31,'[31,32,30,33]','https://supremefurniture.co.in/products/supreme-fusion-01-fg'),('sup-supreme-scissor-foldable','Supreme Scissor Foldable Plastic Dining Table',4000,'tables','assets/images/uploads/supremefurniture/sup-supreme-scissor-foldable_img_0_1785912751.jpg','A futuristic design Multipurpose Blow Moulded Height adjustable Table. A splendid and sturdy product for home, office or memorable excursion. ','Brand Partner | Model: Supreme Scissor Foldable Plastic Dining Table | SKU: SUP-SUPREME-SCISSOR-FOLDABLE','{\"Material\":\"Plastic\",\"Construction\":\"Engineered for luxury durability & silent ergonomic comfort.\",\"Care Instructions\":\"Wipe clean with a soft dry cloth. Avoid abrasive cleaners.\",\"Shipping\":\"White-glove doorstep delivery and inside setup included.\"}','2026-08-05 06:52:58','plastic',11,'[{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-scissor-foldable_img_0_1785912751.jpg\",\"color_id\":28},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-scissor-foldable_img_1_1785912751.jpg\",\"color_id\":28},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-scissor-foldable_img_2_1785912752.jpg\",\"color_id\":28},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-scissor-foldable_img_3_1785912753.jpg\",\"color_id\":28},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-scissor-foldable_img_4_1785912754.jpg\",\"color_id\":28},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-scissor-foldable_img_5_1785912755.jpg\",\"color_id\":28},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-scissor-foldable_img_6_1785912755.jpg\",\"color_id\":28},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-scissor-foldable_img_7_1785912756.jpg\",\"color_id\":28},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-scissor-foldable_img_8_1785912757.jpg\",\"color_id\":28},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-scissor-foldable_img_9_1785912757.jpg\",\"color_id\":28},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-scissor-foldable_img_10_1785912758.jpg\",\"color_id\":28},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-scissor-foldable_img_11_1785912758.jpg\",\"color_id\":28},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-scissor-foldable_img_12_1785912759.jpg\",\"color_id\":10},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-scissor-foldable_img_13_1785912760.jpg\",\"color_id\":10},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-scissor-foldable_img_14_1785912760.jpg\",\"color_id\":10},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-scissor-foldable_img_15_1785912761.jpg\",\"color_id\":10},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-scissor-foldable_img_16_1785912762.jpg\",\"color_id\":10},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-scissor-foldable_img_17_1785912762.jpg\",\"color_id\":10},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-scissor-foldable_img_18_1785912763.jpg\",\"color_id\":29},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-scissor-foldable_img_19_1785912764.jpg\",\"color_id\":29},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-scissor-foldable_img_20_1785912765.jpg\",\"color_id\":29},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-scissor-foldable_img_21_1785912765.jpg\",\"color_id\":29},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-scissor-foldable_img_22_1785912766.jpg\",\"color_id\":29},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-scissor-foldable_img_23_1785912767.jpg\",\"color_id\":29},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-scissor-foldable_img_24_1785912767.jpg\",\"color_id\":29},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-scissor-foldable_img_25_1785912768.jpg\",\"color_id\":29},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-scissor-foldable_img_26_1785912768.jpg\",\"color_id\":29},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-scissor-foldable_img_27_1785912769.jpg\",\"color_id\":29},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-scissor-foldable_img_28_1785912770.jpg\",\"color_id\":29},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-scissor-foldable_img_29_1785912770.jpg\",\"color_id\":29},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-scissor-foldable_img_30_1785912771.jpg\",\"color_id\":29},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-scissor-foldable_img_31_1785912771.jpg\",\"color_id\":29},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-scissor-foldable_img_32_1785912772.jpg\",\"color_id\":29},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-scissor-foldable_img_33_1785912773.jpg\",\"color_id\":29},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-scissor-foldable_img_34_1785912774.jpg\",\"color_id\":29},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-scissor-foldable_img_35_1785912774.jpg\",\"color_id\":29},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-scissor-foldable_img_36_1785912775.jpg\",\"color_id\":29},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-scissor-foldable_img_37_1785912776.jpg\",\"color_id\":29},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-scissor-foldable_img_38_1785912777.jpg\",\"color_id\":29}]',745,645,1,29,'[29,28,16,5]','https://supremefurniture.co.in/products/scissor'),('sup-supreme-web-plastic-77','Supreme Web Plastic Without-Arm Chair',4720,'chairs','assets/images/uploads/supremefurniture/sup-supreme-web-plastic-77_img_0_1784790200.jpg','A marvel of modern design, highly creative web patterned design and bright colours making it ideal for contemporary homes & offices, call centers, cafeterias and other locations. ','Brand Partner | Model: Supreme Web Plastic Without-Arm Chair | SKU: SUP-SUPREME-WEB-PLASTIC-77','{\"Material\":\"Plastic\",\"Construction\":\"Engineered for luxury durability & silent ergonomic comfort.\",\"Care Instructions\":\"Wipe clean with a soft dry cloth. Avoid abrasive cleaners.\",\"Shipping\":\"White-glove doorstep delivery and inside setup included.\"}','2026-07-23 07:03:36','plastic',11,'[{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-web-plastic-77_img_0_1784790200.jpg\",\"color_id\":7},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-web-plastic-77_img_1_1784790200.jpg\",\"color_id\":7},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-web-plastic-77_img_2_1784790201.jpg\",\"color_id\":7},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-web-plastic-77_img_3_1784790201.jpg\",\"color_id\":7},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-web-plastic-77_img_4_1784790201.jpg\",\"color_id\":7},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-web-plastic-77_img_5_1784790201.jpg\",\"color_id\":7},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-web-plastic-77_img_6_1784790201.jpg\",\"color_id\":7},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-web-plastic-77_img_7_1784790201.jpg\",\"color_id\":7},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-web-plastic-77_img_8_1784790201.jpg\",\"color_id\":7},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-web-plastic-77_img_9_1784790202.jpg\",\"color_id\":7},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-web-plastic-77_img_10_1784790202.jpg\",\"color_id\":7},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-web-plastic-77_img_11_1784790202.jpg\",\"color_id\":7},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-web-plastic-77_img_12_1784790202.jpg\",\"color_id\":10},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-web-plastic-77_img_13_1784790202.jpg\",\"color_id\":10},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-web-plastic-77_img_14_1784790202.jpg\",\"color_id\":10},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-web-plastic-77_img_15_1784790202.jpg\",\"color_id\":10},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-web-plastic-77_img_16_1784790203.jpg\",\"color_id\":10},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-web-plastic-77_img_17_1784790203.jpg\",\"color_id\":10},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-web-plastic-77_img_18_1784790203.jpg\",\"color_id\":15},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-web-plastic-77_img_19_1784790203.jpg\",\"color_id\":15},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-web-plastic-77_img_20_1784790203.jpg\",\"color_id\":15},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-web-plastic-77_img_21_1784790203.jpg\",\"color_id\":15},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-web-plastic-77_img_22_1784790203.jpg\",\"color_id\":15},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-web-plastic-77_img_23_1784790203.jpg\",\"color_id\":15},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-web-plastic-77_img_24_1784790204.jpg\",\"color_id\":15},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-web-plastic-77_img_25_1784790204.jpg\",\"color_id\":15},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-web-plastic-77_img_26_1784790204.jpg\",\"color_id\":15},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-web-plastic-77_img_27_1784790204.jpg\",\"color_id\":15},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-web-plastic-77_img_28_1784790204.jpg\",\"color_id\":15},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-web-plastic-77_img_29_1784790204.jpg\",\"color_id\":15},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-web-plastic-77_img_30_1784790204.jpg\",\"color_id\":15},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-web-plastic-77_img_31_1784790205.jpg\",\"color_id\":15},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-web-plastic-77_img_32_1784790205.jpg\",\"color_id\":15},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-web-plastic-77_img_33_1784790205.jpg\",\"color_id\":15},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-web-plastic-77_img_34_1784790205.jpg\",\"color_id\":15},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-web-plastic-77_img_35_1784790205.jpg\",\"color_id\":15},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-web-plastic-77_img_36_1784790205.jpg\",\"color_id\":15},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-web-plastic-77_img_37_1784790205.jpg\",\"color_id\":15},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-web-plastic-77_img_38_1784790206.jpg\",\"color_id\":15},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-web-plastic-77_img_39_1784790206.jpg\",\"color_id\":15},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-web-plastic-77_img_40_1784790206.jpg\",\"color_id\":15},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-web-plastic-77_img_41_1784790206.jpg\",\"color_id\":15},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-web-plastic-77_img_42_1784790206.jpg\",\"color_id\":15},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-web-plastic-77_img_43_1784790206.jpg\",\"color_id\":15},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-web-plastic-77_img_44_1784790206.jpg\",\"color_id\":15},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-web-plastic-77_img_45_1784790207.jpg\",\"color_id\":15},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-web-plastic-77_img_46_1784790207.jpg\",\"color_id\":15},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-web-plastic-77_img_47_1784790207.jpg\",\"color_id\":15},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-web-plastic-77_img_48_1784790207.jpg\",\"color_id\":15},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-web-plastic-77_img_49_1784790207.jpg\",\"color_id\":15},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-web-plastic-77_img_50_1784790207.jpg\",\"color_id\":15},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-web-plastic-77_img_51_1784790207.jpg\",\"color_id\":15},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-web-plastic-77_img_52_1784790208.jpg\",\"color_id\":15},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-web-plastic-77_img_53_1784790208.jpg\",\"color_id\":15},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-web-plastic-77_img_54_1784790208.jpg\",\"color_id\":15},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-web-plastic-77_img_55_1784790208.jpg\",\"color_id\":15},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-web-plastic-77_img_56_1784790208.jpg\",\"color_id\":15},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-web-plastic-77_img_57_1784790208.jpg\",\"color_id\":15},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-web-plastic-77_img_58_1784790208.jpg\",\"color_id\":15},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-web-plastic-77_img_59_1784790208.jpg\",\"color_id\":15},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-web-plastic-77_img_60_1784790209.jpg\",\"color_id\":15},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-web-plastic-77_img_61_1784790209.jpg\",\"color_id\":15},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-web-plastic-77_img_62_1784790209.jpg\",\"color_id\":15},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-web-plastic-77_img_63_1784790209.jpg\",\"color_id\":15},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-web-plastic-77_img_64_1784790209.jpg\",\"color_id\":15},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-web-plastic-77_img_65_1784790209.jpg\",\"color_id\":15},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-web-plastic-77_img_66_1784790210.jpg\",\"color_id\":15},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-web-plastic-77_img_67_1784790210.jpg\",\"color_id\":15},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-web-plastic-77_img_68_1784790210.jpg\",\"color_id\":15},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-web-plastic-77_img_69_1784790210.jpg\",\"color_id\":15},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-web-plastic-77_img_70_1784790210.jpg\",\"color_id\":15},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-web-plastic-77_img_71_1784790210.jpg\",\"color_id\":15},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-web-plastic-77_img_72_1784790210.jpg\",\"color_id\":15},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-web-plastic-77_img_73_1784790211.jpg\",\"color_id\":15},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-web-plastic-77_img_74_1784790211.jpg\",\"color_id\":15},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-web-plastic-77_img_75_1784790211.jpg\",\"color_id\":15},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-web-plastic-77_img_76_1784790211.jpg\",\"color_id\":15},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-web-plastic-77_img_77_1784790211.jpg\",\"color_id\":15},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-web-plastic-77_img_78_1784790211.jpg\",\"color_id\":15},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-web-plastic-77_img_79_1784790211.jpg\",\"color_id\":15},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-web-plastic-77_img_80_1784790212.jpg\",\"color_id\":15},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-web-plastic-77_img_81_1784790212.jpg\",\"color_id\":15},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-web-plastic-77_img_82_1784790212.jpg\",\"color_id\":15},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-web-plastic-77_img_83_1784790212.jpg\",\"color_id\":15},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-web-plastic-77_img_84_1784790212.jpg\",\"color_id\":15},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-web-plastic-77_img_85_1784790212.jpg\",\"color_id\":15},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-web-plastic-77_img_86_1784790212.jpg\",\"color_id\":15},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-web-plastic-77_img_87_1784790213.jpg\",\"color_id\":15},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-web-plastic-77_img_88_1784790213.jpg\",\"color_id\":15},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-web-plastic-77_img_89_1784790213.jpg\",\"color_id\":15},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-web-plastic-77_img_90_1784790213.jpg\",\"color_id\":15},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-web-plastic-77_img_91_1784790213.jpg\",\"color_id\":15},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-web-plastic-77_img_92_1784790213.jpg\",\"color_id\":15},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-web-plastic-77_img_93_1784790213.jpg\",\"color_id\":15},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-web-plastic-77_img_94_1784790213.jpg\",\"color_id\":15},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-web-plastic-77_img_95_1784790214.jpg\",\"color_id\":15},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-web-plastic-77_img_96_1784790214.jpg\",\"color_id\":15},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-web-plastic-77_img_97_1784790214.jpg\",\"color_id\":15},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-web-plastic-77_img_98_1784790214.jpg\",\"color_id\":15},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-web-plastic-77_img_99_1784790214.jpg\",\"color_id\":15},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-web-plastic-77_img_100_1784790214.jpg\",\"color_id\":15},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-web-plastic-77_img_101_1784790214.jpg\",\"color_id\":15},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-web-plastic-77_img_102_1784790214.jpg\",\"color_id\":15},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-web-plastic-77_img_103_1784790215.jpg\",\"color_id\":15},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-web-plastic-77_img_104_1784790215.jpg\",\"color_id\":15},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-web-plastic-77_img_105_1784790215.jpg\",\"color_id\":15},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-web-plastic-77_img_106_1784790215.jpg\",\"color_id\":15},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-web-plastic-77_img_107_1784790215.jpg\",\"color_id\":15},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-web-plastic-77_img_108_1784790215.jpg\",\"color_id\":15},{\"path\":\"assets\\/images\\/uploads\\/supremefurniture\\/sup-supreme-web-plastic-77_img_109_1784790216.jpg\",\"color_id\":15}]',525,510,2,13,'[13,14,5,15,7,8,11,16]',NULL),('www-freedom-fmdr-2b','Nilkamal Freedom FMDR 2B Plastic Storage Cabinet with 2 Drawer (Weathered Brown / Biscuit)',7220,'storage','assets/images/uploads/nilkamalfurniture/www-freedom-fmdr-2b_img_0_1785913236.jpg','Nilkamal Freedom FMDR 2B Plastic Storage Cabinet with 2 Drawer - Weathered Brown/Biscuit','Brand Partner | Model: Nilkamal Freedom FMDR 2B Plastic Storage Cabinet with 2 Drawer (Weathered Brown / Biscuit) | SKU: WWW-FREEDOM-FMDR-2B','{\"Material\":\"Plastic\",\"Construction\":\"Engineered for luxury durability & silent ergonomic comfort.\",\"Care Instructions\":\"Wipe clean with a soft dry cloth. Avoid abrasive cleaners.\",\"Shipping\":\"White-glove doorstep delivery and inside setup included.\"}','2026-08-05 07:00:49','plastic',9,'[{\"path\":\"assets\\/images\\/uploads\\/nilkamalfurniture\\/www-freedom-fmdr-2b_img_0_1785913236.jpg\",\"color_id\":15},{\"path\":\"assets\\/images\\/uploads\\/nilkamalfurniture\\/www-freedom-fmdr-2b_img_1_1785913238.jpg\",\"color_id\":15},{\"path\":\"assets\\/images\\/uploads\\/nilkamalfurniture\\/www-freedom-fmdr-2b_img_2_1785913239.jpg\",\"color_id\":15},{\"path\":\"assets\\/images\\/uploads\\/nilkamalfurniture\\/www-freedom-fmdr-2b_img_3_1785913240.jpg\",\"color_id\":15},{\"path\":\"assets\\/images\\/uploads\\/nilkamalfurniture\\/www-freedom-fmdr-2b_img_4_1785913241.jpg\",\"color_id\":15},{\"path\":\"assets\\/images\\/uploads\\/nilkamalfurniture\\/www-freedom-fmdr-2b_img_5_1785913242.jpg\",\"color_id\":15},{\"path\":\"assets\\/images\\/uploads\\/nilkamalfurniture\\/www-freedom-fmdr-2b_img_6_1785913243.jpg\",\"color_id\":15},{\"path\":\"assets\\/images\\/uploads\\/nilkamalfurniture\\/www-freedom-fmdr-2b_img_7_1785913244.jpg\",\"color_id\":15}]',NULL,2,NULL,15,'[15]','https://www.nilkamalfurniture.com/products/nilkamal-freedom-wid-2-drawer-below-wbn-bst-wbn-fmdr2b-wbn-bst-wbn'),('www-freedom-large-fmsc18','Nilkamal Freedom Large 18 (FMSC18) Shoe Rack Plastic Cabinet (Grey/Charcoal Grey)',6060,'storage','assets/images/uploads/nilkamalfurniture/www-freedom-large-fmsc18_img_0_1785913179.jpg','Nilkamal Freedom Large 18 (FMSC18) Shoe Rack Plastic Cabinet (Grey/Charcoal Grey)','Brand Partner | Model: Nilkamal Freedom Large 18 (FMSC18) Shoe Rack Plastic Cabinet (Grey/Charcoal Grey) | SKU: WWW-FREEDOM-LARGE-FMSC18','{\"Material\":\"Plastic\",\"Construction\":\"Engineered for luxury durability & silent ergonomic comfort.\",\"Care Instructions\":\"Wipe clean with a soft dry cloth. Avoid abrasive cleaners.\",\"Shipping\":\"White-glove doorstep delivery and inside setup included.\"}','2026-08-05 06:59:55','plastic',9,'[{\"path\":\"assets\\/images\\/uploads\\/nilkamalfurniture\\/www-freedom-large-fmsc18_img_0_1785913179.jpg\",\"color_id\":36},{\"path\":\"assets\\/images\\/uploads\\/nilkamalfurniture\\/www-freedom-large-fmsc18_img_1_1785913181.jpg\",\"color_id\":36},{\"path\":\"assets\\/images\\/uploads\\/nilkamalfurniture\\/www-freedom-large-fmsc18_img_2_1785913181.jpg\",\"color_id\":36},{\"path\":\"assets\\/images\\/uploads\\/nilkamalfurniture\\/www-freedom-large-fmsc18_img_3_1785913182.jpg\",\"color_id\":36},{\"path\":\"assets\\/images\\/uploads\\/nilkamalfurniture\\/www-freedom-large-fmsc18_img_4_1785913183.jpg\",\"color_id\":36},{\"path\":\"assets\\/images\\/uploads\\/nilkamalfurniture\\/www-freedom-large-fmsc18_img_5_1785913184.jpg\",\"color_id\":36},{\"path\":\"assets\\/images\\/uploads\\/nilkamalfurniture\\/www-freedom-large-fmsc18_img_6_1785913185.jpg\",\"color_id\":36},{\"path\":\"assets\\/images\\/uploads\\/nilkamalfurniture\\/www-freedom-large-fmsc18_img_8_1785913192.jpg\",\"color_id\":36},{\"path\":\"assets\\/images\\/uploads\\/nilkamalfurniture\\/www-freedom-large-fmsc18_img_9_1785913193.jpg\",\"color_id\":36},{\"path\":\"assets\\/images\\/uploads\\/nilkamalfurniture\\/www-freedom-large-fmsc18_img_10_1785913194.jpg\",\"color_id\":36}]',NULL,NULL,NULL,36,'[36]','https://www.nilkamalfurniture.com/products/nilkamal-freedom-large-18-fmsc18-shoe-rack-plastic-cabinet-grey-charcoal-grey'),('www-fyrebird-marvel-gaming','Nilkamal Fyrebird Marvel Gaming Chair (Black / Red)',13590,'chairs','assets/images/uploads/nilkamalfurniture/www-fyrebird-marvel-gaming_img_0_1785913024.jpg','Nilkamal Marvel Gaming Chair (Black/Red)','Dimensions: W: 100cm x D: 240cm x H: 85cm','{\"Material\":\"Wood\",\"Construction\":\"Engineered for luxury durability & silent ergonomic comfort.\",\"Care Instructions\":\"Wipe clean with a soft dry cloth. Avoid abrasive cleaners.\",\"Shipping\":\"White-glove doorstep delivery and inside setup included.\"}','2026-08-05 06:57:26','wood',9,'[{\"path\":\"assets\\/images\\/uploads\\/nilkamalfurniture\\/www-fyrebird-marvel-gaming_img_0_1785913024.jpg\",\"color_id\":5},{\"path\":\"assets\\/images\\/uploads\\/nilkamalfurniture\\/www-fyrebird-marvel-gaming_img_1_1785913025.jpg\",\"color_id\":5},{\"path\":\"assets\\/images\\/uploads\\/nilkamalfurniture\\/www-fyrebird-marvel-gaming_img_2_1785913026.jpg\",\"color_id\":5},{\"path\":\"assets\\/images\\/uploads\\/nilkamalfurniture\\/www-fyrebird-marvel-gaming_img_3_1785913028.jpg\",\"color_id\":5},{\"path\":\"assets\\/images\\/uploads\\/nilkamalfurniture\\/www-fyrebird-marvel-gaming_img_4_1785913028.jpg\",\"color_id\":5},{\"path\":\"assets\\/images\\/uploads\\/nilkamalfurniture\\/www-fyrebird-marvel-gaming_img_5_1785913030.jpg\",\"color_id\":5},{\"path\":\"assets\\/images\\/uploads\\/nilkamalfurniture\\/www-fyrebird-marvel-gaming_img_6_1785913031.jpg\",\"color_id\":5},{\"path\":\"assets\\/images\\/uploads\\/nilkamalfurniture\\/www-fyrebird-marvel-gaming_img_7_1785913032.jpg\",\"color_id\":5},{\"path\":\"assets\\/images\\/uploads\\/nilkamalfurniture\\/www-fyrebird-marvel-gaming_img_8_1785913033.jpg\",\"color_id\":5},{\"path\":\"assets\\/images\\/uploads\\/nilkamalfurniture\\/www-fyrebird-marvel-gaming_img_9_1785913033.jpg\",\"color_id\":5},{\"path\":\"assets\\/images\\/uploads\\/nilkamalfurniture\\/www-fyrebird-marvel-gaming_img_10_1785913034.jpg\",\"color_id\":5},{\"path\":\"assets\\/images\\/uploads\\/nilkamalfurniture\\/www-fyrebird-marvel-gaming_img_11_1785913035.jpg\",\"color_id\":5},{\"path\":\"assets\\/images\\/uploads\\/nilkamalfurniture\\/www-fyrebird-marvel-gaming_img_12_1785913035.jpg\",\"color_id\":5},{\"path\":\"assets\\/images\\/uploads\\/nilkamalfurniture\\/www-fyrebird-marvel-gaming_img_13_1785913036.jpg\",\"color_id\":5},{\"path\":\"assets\\/images\\/uploads\\/nilkamalfurniture\\/www-fyrebird-marvel-gaming_img_14_1785913036.jpg\",\"color_id\":5},{\"path\":\"assets\\/images\\/uploads\\/nilkamalfurniture\\/www-fyrebird-marvel-gaming_img_15_1785913037.jpg\",\"color_id\":5},{\"path\":\"assets\\/images\\/uploads\\/nilkamalfurniture\\/www-fyrebird-marvel-gaming_img_16_1785913038.jpg\",\"color_id\":5},{\"path\":\"assets\\/images\\/uploads\\/nilkamalfurniture\\/www-fyrebird-marvel-gaming_img_17_1785913038.jpg\",\"color_id\":5},{\"path\":\"assets\\/images\\/uploads\\/nilkamalfurniture\\/www-fyrebird-marvel-gaming_img_18_1785913040.jpg\",\"color_id\":5},{\"path\":\"assets\\/images\\/uploads\\/nilkamalfurniture\\/www-fyrebird-marvel-gaming_img_19_1785913041.jpg\",\"color_id\":5},{\"path\":\"assets\\/images\\/uploads\\/nilkamalfurniture\\/www-fyrebird-marvel-gaming_img_20_1785913041.jpg\",\"color_id\":5},{\"path\":\"assets\\/images\\/uploads\\/nilkamalfurniture\\/www-fyrebird-marvel-gaming_img_21_1785913042.jpg\",\"color_id\":5},{\"path\":\"assets\\/images\\/uploads\\/nilkamalfurniture\\/www-fyrebird-marvel-gaming_img_22_1785913043.jpg\",\"color_id\":5},{\"path\":\"assets\\/images\\/uploads\\/nilkamalfurniture\\/www-fyrebird-marvel-gaming_img_23_1785913044.jpg\",\"color_id\":5},{\"path\":\"assets\\/images\\/uploads\\/nilkamalfurniture\\/www-fyrebird-marvel-gaming_img_24_1785913044.jpg\",\"color_id\":5},{\"path\":\"assets\\/images\\/uploads\\/nilkamalfurniture\\/www-fyrebird-marvel-gaming_img_25_1785913045.jpg\",\"color_id\":5}]',85,100,240,5,'[5]','https://www.nilkamalfurniture.com/products/nilkamal-marvel-gaming-chair-black-red'),('www-luxe-full-back','Nilkamal Luxe Full-Back Cushioned Plastic Chair (Bright Red & Black)',4500,'chairs','assets/images/uploads/nilkamalfurniture/www-luxe-full-back_img_0_1785912916.jpg','Nilkamal Luxe Full-Back Cushioned Plastic Chair (Bright Red & Black)','Brand Partner | Model: Nilkamal Luxe Full-Back Cushioned Plastic Chair (Bright Red & Black) | SKU: WWW-LUXE-FULL-BACK','{\"Material\":\"Plastic\",\"Construction\":\"Engineered for luxury durability & silent ergonomic comfort.\",\"Care Instructions\":\"Wipe clean with a soft dry cloth. Avoid abrasive cleaners.\",\"Shipping\":\"White-glove doorstep delivery and inside setup included.\"}','2026-08-05 06:55:25','plastic',9,'[{\"path\":\"assets\\/images\\/uploads\\/nilkamalfurniture\\/www-luxe-full-back_img_0_1785912916.jpg\",\"color_id\":13},{\"path\":\"assets\\/images\\/uploads\\/nilkamalfurniture\\/www-luxe-full-back_img_1_1785912918.jpg\",\"color_id\":13},{\"path\":\"assets\\/images\\/uploads\\/nilkamalfurniture\\/www-luxe-full-back_img_2_1785912918.jpg\",\"color_id\":13},{\"path\":\"assets\\/images\\/uploads\\/nilkamalfurniture\\/www-luxe-full-back_img_3_1785912919.jpg\",\"color_id\":13},{\"path\":\"assets\\/images\\/uploads\\/nilkamalfurniture\\/www-luxe-full-back_img_4_1785912919.jpg\",\"color_id\":13},{\"path\":\"assets\\/images\\/uploads\\/nilkamalfurniture\\/www-luxe-full-back_img_5_1785912920.jpg\",\"color_id\":13},{\"path\":\"assets\\/images\\/uploads\\/nilkamalfurniture\\/www-luxe-full-back_img_6_1785912920.jpg\",\"color_id\":13},{\"path\":\"assets\\/images\\/uploads\\/nilkamalfurniture\\/www-luxe-full-back_img_7_1785912921.jpg\",\"color_id\":5},{\"path\":\"assets\\/images\\/uploads\\/nilkamalfurniture\\/www-luxe-full-back_img_8_1785912921.jpg\",\"color_id\":5},{\"path\":\"assets\\/images\\/uploads\\/nilkamalfurniture\\/www-luxe-full-back_img_9_1785912922.jpg\",\"color_id\":5},{\"path\":\"assets\\/images\\/uploads\\/nilkamalfurniture\\/www-luxe-full-back_img_10_1785912923.jpg\",\"color_id\":5},{\"path\":\"assets\\/images\\/uploads\\/nilkamalfurniture\\/www-luxe-full-back_img_11_1785912923.jpg\",\"color_id\":5},{\"path\":\"assets\\/images\\/uploads\\/nilkamalfurniture\\/www-luxe-full-back_img_12_1785912924.jpg\",\"color_id\":5}]',NULL,2,NULL,13,'[13,5]','https://www.nilkamalfurniture.com/products/nilkamal-luxe-full-back-cushioned-plastic-chair-bright-red-black'),('www-meridian-kross-leg','Nilkamal Meridian Kross Leg Dining Table',3910,'tables','assets/images/uploads/nilkamalfurniture/www-meridian-kross-leg_img_0_1785914145.jpg','Nilkamal Meridian Kross Leg Dining Table','Dimensions: W: 2cm x D: 240cm x H: 85cm','{\"Material\":\"Wood\",\"Construction\":\"Engineered for luxury durability & silent ergonomic comfort.\",\"Care Instructions\":\"Wipe clean with a soft dry cloth. Avoid abrasive cleaners.\",\"Shipping\":\"White-glove doorstep delivery and inside setup included.\"}','2026-08-05 07:16:06','wood',9,'[{\"path\":\"assets\\/images\\/uploads\\/nilkamalfurniture\\/www-meridian-kross-leg_img_0_1785914145.jpg\",\"color_id\":39},{\"path\":\"assets\\/images\\/uploads\\/nilkamalfurniture\\/www-meridian-kross-leg_img_1_1785914147.jpg\",\"color_id\":39},{\"path\":\"assets\\/images\\/uploads\\/nilkamalfurniture\\/www-meridian-kross-leg_img_2_1785914148.jpg\",\"color_id\":39},{\"path\":\"assets\\/images\\/uploads\\/nilkamalfurniture\\/www-meridian-kross-leg_img_3_1785914148.jpg\",\"color_id\":39},{\"path\":\"assets\\/images\\/uploads\\/nilkamalfurniture\\/www-meridian-kross-leg_img_4_1785914149.jpg\",\"color_id\":39},{\"path\":\"assets\\/images\\/uploads\\/nilkamalfurniture\\/www-meridian-kross-leg_img_5_1785914150.jpg\",\"color_id\":39},{\"path\":\"assets\\/images\\/uploads\\/nilkamalfurniture\\/www-meridian-kross-leg_img_6_1785914150.jpg\",\"color_id\":39},{\"path\":\"assets\\/images\\/uploads\\/nilkamalfurniture\\/www-meridian-kross-leg_img_7_1785914151.jpg\",\"color_id\":39},{\"path\":\"assets\\/images\\/uploads\\/nilkamalfurniture\\/www-meridian-kross-leg_img_8_1785914151.jpg\",\"color_id\":39},{\"path\":\"assets\\/images\\/uploads\\/nilkamalfurniture\\/www-meridian-kross-leg_img_9_1785914152.jpg\",\"color_id\":39},{\"path\":\"assets\\/images\\/uploads\\/nilkamalfurniture\\/www-meridian-kross-leg_img_10_1785914153.jpg\",\"color_id\":39},{\"path\":\"assets\\/images\\/uploads\\/nilkamalfurniture\\/www-meridian-kross-leg_img_11_1785914154.jpg\",\"color_id\":39},{\"path\":\"assets\\/images\\/uploads\\/nilkamalfurniture\\/www-meridian-kross-leg_img_12_1785914154.jpg\",\"color_id\":10},{\"path\":\"assets\\/images\\/uploads\\/nilkamalfurniture\\/www-meridian-kross-leg_img_13_1785914155.jpg\",\"color_id\":10},{\"path\":\"assets\\/images\\/uploads\\/nilkamalfurniture\\/www-meridian-kross-leg_img_14_1785914156.jpg\",\"color_id\":10},{\"path\":\"assets\\/images\\/uploads\\/nilkamalfurniture\\/www-meridian-kross-leg_img_15_1785914156.jpg\",\"color_id\":10},{\"path\":\"assets\\/images\\/uploads\\/nilkamalfurniture\\/www-meridian-kross-leg_img_16_1785914157.jpg\",\"color_id\":10},{\"path\":\"assets\\/images\\/uploads\\/nilkamalfurniture\\/www-meridian-kross-leg_img_17_1785914158.jpg\",\"color_id\":10},{\"path\":\"assets\\/images\\/uploads\\/nilkamalfurniture\\/www-meridian-kross-leg_img_18_1785914159.jpg\",\"color_id\":43},{\"path\":\"assets\\/images\\/uploads\\/nilkamalfurniture\\/www-meridian-kross-leg_img_19_1785914160.jpg\",\"color_id\":43},{\"path\":\"assets\\/images\\/uploads\\/nilkamalfurniture\\/www-meridian-kross-leg_img_20_1785914160.jpg\",\"color_id\":43},{\"path\":\"assets\\/images\\/uploads\\/nilkamalfurniture\\/www-meridian-kross-leg_img_21_1785914161.jpg\",\"color_id\":43},{\"path\":\"assets\\/images\\/uploads\\/nilkamalfurniture\\/www-meridian-kross-leg_img_22_1785914161.jpg\",\"color_id\":43},{\"path\":\"assets\\/images\\/uploads\\/nilkamalfurniture\\/www-meridian-kross-leg_img_23_1785914163.jpg\",\"color_id\":43},{\"path\":\"assets\\/images\\/uploads\\/nilkamalfurniture\\/www-meridian-kross-leg_img_24_1785914164.jpg\",\"color_id\":43}]',85,2,240,38,'[38,39,37]','https://www.nilkamalfurniture.com/products/nilkamal-meridian-kross-leg-dining-table'),('www-mono-coffee-table','Nilkamal Mono Coffee Table (Legno Oak/Frosty White)',4490,'tables','assets/images/uploads/nilkamalfurniture/www-mono-coffee-table_img_0_1785216107.jpg','Nilkamal Mono Coffee Table (Legno Oak/Frosty White)','Brand Partner | Model: Nilkamal Mono Coffee Table (Legno Oak/Frosty White) | SKU: WWW-MONO-COFFEE-TABLE','{\"Material\":\"Wood\",\"Construction\":\"Engineered for luxury durability & silent ergonomic comfort.\",\"Care Instructions\":\"Wipe clean with a soft dry cloth. Avoid abrasive cleaners.\",\"Shipping\":\"White-glove doorstep delivery and inside setup included.\"}','2026-07-28 05:21:55','wood',9,'[{\"path\":\"assets\\/images\\/uploads\\/nilkamalfurniture\\/www-mono-coffee-table_img_0_1785216107.jpg\",\"color_id\":14},{\"path\":\"assets\\/images\\/uploads\\/nilkamalfurniture\\/www-mono-coffee-table_img_1_1785216107.jpg\",\"color_id\":14},{\"path\":\"assets\\/images\\/uploads\\/nilkamalfurniture\\/www-mono-coffee-table_img_2_1785216108.jpg\",\"color_id\":14},{\"path\":\"assets\\/images\\/uploads\\/nilkamalfurniture\\/www-mono-coffee-table_img_3_1785216109.jpg\",\"color_id\":14},{\"path\":\"assets\\/images\\/uploads\\/nilkamalfurniture\\/www-mono-coffee-table_img_4_1785216109.jpg\",\"color_id\":14},{\"path\":\"assets\\/images\\/uploads\\/nilkamalfurniture\\/www-mono-coffee-table_img_5_1785216110.jpg\",\"color_id\":14},{\"path\":\"assets\\/images\\/uploads\\/nilkamalfurniture\\/www-mono-coffee-table_img_6_1785216110.jpg\",\"color_id\":14},{\"path\":\"assets\\/images\\/uploads\\/nilkamalfurniture\\/www-mono-coffee-table_img_7_1785216111.jpg\",\"color_id\":14},{\"path\":\"assets\\/images\\/uploads\\/nilkamalfurniture\\/www-mono-coffee-table_img_8_1785216112.jpg\",\"color_id\":14},{\"path\":\"assets\\/images\\/uploads\\/nilkamalfurniture\\/www-mono-coffee-table_img_9_1785216112.jpg\",\"color_id\":14},{\"path\":\"assets\\/images\\/uploads\\/nilkamalfurniture\\/www-mono-coffee-table_img_10_1785216113.jpg\",\"color_id\":14},{\"path\":\"assets\\/images\\/uploads\\/nilkamalfurniture\\/www-mono-coffee-table_img_11_1785216114.jpg\",\"color_id\":14},{\"path\":\"assets\\/images\\/uploads\\/nilkamalfurniture\\/www-mono-coffee-table_img_12_1785216114.jpg\",\"color_id\":14}]',NULL,2,NULL,14,'[14]','https://www.nilkamalfurniture.com/products/nilkamal-mono-engineered-wood-coffee-table-legno-oak-frosty-white'),('www-mozart-door-mirror','Nilkamal Mozart 4 Door Mirror Wardrobe (Walnut)',29890,'storage','assets/images/uploads/nilkamalfurniture/www-mozart-door-mirror_img_0_1785216057.webp','Nilkamal Mozart 4 Door Mirror Wardrobe (Walnut)','Brand Partner | Model: Nilkamal Mozart 4 Door Mirror Wardrobe (Walnut) | SKU: WWW-MOZART-DOOR-MIRROR','{\"Material\":\"Wood\",\"Construction\":\"Engineered for luxury durability & silent ergonomic comfort.\",\"Care Instructions\":\"Wipe clean with a soft dry cloth. Avoid abrasive cleaners.\",\"Shipping\":\"White-glove doorstep delivery and inside setup included.\"}','2026-07-28 05:20:59','wood',9,'[{\"path\":\"assets\\/images\\/uploads\\/nilkamalfurniture\\/www-mozart-door-mirror_img_0_1785216057.webp\",\"color_id\":17},{\"path\":\"assets\\/images\\/uploads\\/nilkamalfurniture\\/www-mozart-door-mirror_img_1_1785216058.webp\",\"color_id\":17},{\"path\":\"assets\\/images\\/uploads\\/nilkamalfurniture\\/www-mozart-door-mirror_img_2_1785216058.webp\",\"color_id\":17},{\"path\":\"assets\\/images\\/uploads\\/nilkamalfurniture\\/www-mozart-door-mirror_img_3_1785216058.webp\",\"color_id\":17},{\"path\":\"assets\\/images\\/uploads\\/nilkamalfurniture\\/www-mozart-door-mirror_img_4_1785216058.webp\",\"color_id\":17},{\"path\":\"assets\\/images\\/uploads\\/nilkamalfurniture\\/www-mozart-door-mirror_img_5_1785216058.webp\",\"color_id\":17},{\"path\":\"assets\\/images\\/uploads\\/nilkamalfurniture\\/www-mozart-door-mirror_img_6_1785216059.webp\",\"color_id\":17}]',NULL,NULL,NULL,17,'[17]','https://www.nilkamalfurniture.com/products/nilkamal-mozart-4-door-mirror-wardrobe-walnut'),('www-orchid-mahjong-table','Nilkamal Orchid Mahjong Table',6400,'tables','assets/images/uploads/nilkamalfurniture/www-orchid-mahjong-table_img_0_1785914465.jpg','Nilkamal Orchid Mahjong Table','Brand Partner | Model: Nilkamal Orchid Mahjong Table | SKU: WWW-ORCHID-MAHJONG-TABLE','{\"Material\":\"Wood\",\"Construction\":\"Engineered for luxury durability & silent ergonomic comfort.\",\"Care Instructions\":\"Wipe clean with a soft dry cloth. Avoid abrasive cleaners.\",\"Shipping\":\"White-glove doorstep delivery and inside setup included.\"}','2026-08-05 07:21:27','wood',9,'[{\"path\":\"assets\\/images\\/uploads\\/nilkamalfurniture\\/www-orchid-mahjong-table_img_0_1785914465.jpg\",\"color_id\":42},{\"path\":\"assets\\/images\\/uploads\\/nilkamalfurniture\\/www-orchid-mahjong-table_img_1_1785914466.jpg\",\"color_id\":42},{\"path\":\"assets\\/images\\/uploads\\/nilkamalfurniture\\/www-orchid-mahjong-table_img_2_1785914466.jpg\",\"color_id\":42},{\"path\":\"assets\\/images\\/uploads\\/nilkamalfurniture\\/www-orchid-mahjong-table_img_3_1785914467.jpg\",\"color_id\":42},{\"path\":\"assets\\/images\\/uploads\\/nilkamalfurniture\\/www-orchid-mahjong-table_img_4_1785914468.jpg\",\"color_id\":42},{\"path\":\"assets\\/images\\/uploads\\/nilkamalfurniture\\/www-orchid-mahjong-table_img_5_1785914468.jpg\",\"color_id\":42},{\"path\":\"assets\\/images\\/uploads\\/nilkamalfurniture\\/www-orchid-mahjong-table_img_6_1785914469.jpg\",\"color_id\":42},{\"path\":\"assets\\/images\\/uploads\\/nilkamalfurniture\\/www-orchid-mahjong-table_img_7_1785914470.jpg\",\"color_id\":42},{\"path\":\"assets\\/images\\/uploads\\/nilkamalfurniture\\/www-orchid-mahjong-table_img_8_1785914471.jpg\",\"color_id\":42},{\"path\":\"assets\\/images\\/uploads\\/nilkamalfurniture\\/www-orchid-mahjong-table_img_9_1785914471.jpg\",\"color_id\":42},{\"path\":\"assets\\/images\\/uploads\\/nilkamalfurniture\\/www-orchid-mahjong-table_img_10_1785914472.jpg\",\"color_id\":42},{\"path\":\"assets\\/images\\/uploads\\/nilkamalfurniture\\/www-orchid-mahjong-table_img_11_1785914473.jpg\",\"color_id\":42},{\"path\":\"assets\\/images\\/uploads\\/nilkamalfurniture\\/www-orchid-mahjong-table_img_12_1785914473.jpg\",\"color_id\":10},{\"path\":\"assets\\/images\\/uploads\\/nilkamalfurniture\\/www-orchid-mahjong-table_img_13_1785914474.jpg\",\"color_id\":10},{\"path\":\"assets\\/images\\/uploads\\/nilkamalfurniture\\/www-orchid-mahjong-table_img_14_1785914475.jpg\",\"color_id\":10},{\"path\":\"assets\\/images\\/uploads\\/nilkamalfurniture\\/www-orchid-mahjong-table_img_15_1785914476.jpg\",\"color_id\":10},{\"path\":\"assets\\/images\\/uploads\\/nilkamalfurniture\\/www-orchid-mahjong-table_img_16_1785914476.jpg\",\"color_id\":10},{\"path\":\"assets\\/images\\/uploads\\/nilkamalfurniture\\/www-orchid-mahjong-table_img_17_1785914477.jpg\",\"color_id\":10},{\"path\":\"assets\\/images\\/uploads\\/nilkamalfurniture\\/www-orchid-mahjong-table_img_18_1785914477.jpg\",\"color_id\":43},{\"path\":\"assets\\/images\\/uploads\\/nilkamalfurniture\\/www-orchid-mahjong-table_img_19_1785914478.jpg\",\"color_id\":43},{\"path\":\"assets\\/images\\/uploads\\/nilkamalfurniture\\/www-orchid-mahjong-table_img_20_1785914479.jpg\",\"color_id\":43},{\"path\":\"assets\\/images\\/uploads\\/nilkamalfurniture\\/www-orchid-mahjong-table_img_21_1785914479.jpg\",\"color_id\":43},{\"path\":\"assets\\/images\\/uploads\\/nilkamalfurniture\\/www-orchid-mahjong-table_img_22_1785914480.jpg\",\"color_id\":43},{\"path\":\"assets\\/images\\/uploads\\/nilkamalfurniture\\/www-orchid-mahjong-table_img_23_1785914481.jpg\",\"color_id\":43},{\"path\":\"assets\\/images\\/uploads\\/nilkamalfurniture\\/www-orchid-mahjong-table_img_24_1785914481.jpg\",\"color_id\":43},{\"path\":\"assets\\/images\\/uploads\\/nilkamalfurniture\\/www-orchid-mahjong-table_img_25_1785914482.jpg\",\"color_id\":43},{\"path\":\"assets\\/images\\/uploads\\/nilkamalfurniture\\/www-orchid-mahjong-table_img_26_1785914484.jpg\",\"color_id\":43},{\"path\":\"assets\\/images\\/uploads\\/nilkamalfurniture\\/www-orchid-mahjong-table_img_27_1785914484.jpg\",\"color_id\":43},{\"path\":\"assets\\/images\\/uploads\\/nilkamalfurniture\\/www-orchid-mahjong-table_img_28_1785914485.jpg\",\"color_id\":43},{\"path\":\"assets\\/images\\/uploads\\/nilkamalfurniture\\/www-orchid-mahjong-table_img_29_1785914485.jpg\",\"color_id\":43},{\"path\":\"assets\\/images\\/uploads\\/nilkamalfurniture\\/www-orchid-mahjong-table_img_30_1785914486.jpg\",\"color_id\":43}]',NULL,NULL,NULL,42,'[42,43]','https://www.nilkamalfurniture.com/products/nilkamal-orchid-self-standing-dining-table'),('www-skelton-seater-manual','Nilkamal Skelton 3 Seater Manual Recliner (Velvet Brown)',40900,'chairs','assets/images/uploads/nilkamalfurniture/www-skelton-seater-manual_img_0_1785912851.jpg','Nilkamal Skelton 3 Seater Manual Recliner (Velvet Brown)','Brand Partner | Model: Nilkamal Skelton 3 Seater Manual Recliner (Velvet Brown) | SKU: WWW-SKELTON-SEATER-MANUAL','{\"Material\":\"Fabric\",\"Construction\":\"Engineered for luxury durability & silent ergonomic comfort.\",\"Care Instructions\":\"Wipe clean with a soft dry cloth. Avoid abrasive cleaners.\",\"Shipping\":\"White-glove doorstep delivery and inside setup included.\"}','2026-08-05 06:54:21','fabric',9,'[{\"path\":\"assets\\/images\\/uploads\\/nilkamalfurniture\\/www-skelton-seater-manual_img_0_1785912851.jpg\",\"color_id\":42},{\"path\":\"assets\\/images\\/uploads\\/nilkamalfurniture\\/www-skelton-seater-manual_img_1_1785912852.jpg\",\"color_id\":42},{\"path\":\"assets\\/images\\/uploads\\/nilkamalfurniture\\/www-skelton-seater-manual_img_2_1785912853.jpg\",\"color_id\":42},{\"path\":\"assets\\/images\\/uploads\\/nilkamalfurniture\\/www-skelton-seater-manual_img_3_1785912853.jpg\",\"color_id\":42},{\"path\":\"assets\\/images\\/uploads\\/nilkamalfurniture\\/www-skelton-seater-manual_img_4_1785912854.jpg\",\"color_id\":42},{\"path\":\"assets\\/images\\/uploads\\/nilkamalfurniture\\/www-skelton-seater-manual_img_5_1785912854.jpg\",\"color_id\":42},{\"path\":\"assets\\/images\\/uploads\\/nilkamalfurniture\\/www-skelton-seater-manual_img_6_1785912855.jpg\",\"color_id\":42},{\"path\":\"assets\\/images\\/uploads\\/nilkamalfurniture\\/www-skelton-seater-manual_img_7_1785912856.jpg\",\"color_id\":42},{\"path\":\"assets\\/images\\/uploads\\/nilkamalfurniture\\/www-skelton-seater-manual_img_8_1785912856.jpg\",\"color_id\":42},{\"path\":\"assets\\/images\\/uploads\\/nilkamalfurniture\\/www-skelton-seater-manual_img_9_1785912857.jpg\",\"color_id\":42},{\"path\":\"assets\\/images\\/uploads\\/nilkamalfurniture\\/www-skelton-seater-manual_img_10_1785912858.jpg\",\"color_id\":42},{\"path\":\"assets\\/images\\/uploads\\/nilkamalfurniture\\/www-skelton-seater-manual_img_11_1785912859.jpg\",\"color_id\":42},{\"path\":\"assets\\/images\\/uploads\\/nilkamalfurniture\\/www-skelton-seater-manual_img_12_1785912859.jpg\",\"color_id\":10},{\"path\":\"assets\\/images\\/uploads\\/nilkamalfurniture\\/www-skelton-seater-manual_img_13_1785912860.jpg\",\"color_id\":10}]',NULL,2,NULL,15,'[15]','https://www.nilkamalfurniture.com/products/nilkamal-skelton-3-seater-recliner-velvet-brown'),('www-walton-tv-unit','Nilkamal Walton TV Unit (Walnut)',11890,'tv-units','assets/images/uploads/nilkamalfurniture/www-walton-tv-unit_img_0_1785924974.jpg','Nilkamal Walton TV Unit (Walnut)','Brand Partner | Model: Nilkamal Walton TV Unit (Walnut) | SKU: WWW-WALTON-TV-UNIT','{\"Material\":\"Wood\",\"Construction\":\"Engineered for luxury durability & silent ergonomic comfort.\",\"Care Instructions\":\"Wipe clean with a soft dry cloth. Avoid abrasive cleaners.\",\"Shipping\":\"White-glove doorstep delivery and inside setup included.\"}','2026-08-05 10:16:13','wood',9,'[{\"path\":\"assets\\/images\\/uploads\\/nilkamalfurniture\\/www-walton-tv-unit_img_0_1785924974.jpg\",\"color_id\":17},{\"path\":\"assets\\/images\\/uploads\\/nilkamalfurniture\\/www-walton-tv-unit_img_1_1785924974.jpg\",\"color_id\":17},{\"path\":\"assets\\/images\\/uploads\\/nilkamalfurniture\\/www-walton-tv-unit_img_2_1785924974.jpg\",\"color_id\":17},{\"path\":\"assets\\/images\\/uploads\\/nilkamalfurniture\\/www-walton-tv-unit_img_3_1785924974.jpg\",\"color_id\":17},{\"path\":\"assets\\/images\\/uploads\\/nilkamalfurniture\\/www-walton-tv-unit_img_4_1785924974.jpg\",\"color_id\":17},{\"path\":\"assets\\/images\\/uploads\\/nilkamalfurniture\\/www-walton-tv-unit_img_5_1785924974.jpg\",\"color_id\":17},{\"path\":\"assets\\/images\\/uploads\\/nilkamalfurniture\\/www-walton-tv-unit_img_6_1785924974.jpg\",\"color_id\":17},{\"path\":\"assets\\/images\\/uploads\\/nilkamalfurniture\\/www-walton-tv-unit_img_7_1785924974.jpg\",\"color_id\":17}]',NULL,NULL,NULL,17,'[17]','https://www.nilkamalfurniture.com/products/nilkamal-walton-tv-unit-walnut-mwaln174016tvucwn');
/*!40000 ALTER TABLE `oxo_products` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `oxo_shop_images`
--

DROP TABLE IF EXISTS `oxo_shop_images`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `oxo_shop_images` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `caption` text DEFAULT NULL,
  `image_path` varchar(255) NOT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `oxo_shop_images`
--

LOCK TABLES `oxo_shop_images` WRITE;
/*!40000 ALTER TABLE `oxo_shop_images` DISABLE KEYS */;
INSERT INTO `oxo_shop_images` VALUES (1,'Shop Storefront & Facade','Corrugated Dark Cladding & Signature Orange Framing','assets/images/flagship-facade.jpg',1,1,'2026-08-06 13:50:00'),(2,'Living Room Atelier','Bespoke Modular Lounge Display','assets/images/sofa_1.png',2,1,'2026-08-06 13:50:00'),(3,'Master Joinery Studio','Artisan Hand-Finishing Station','assets/images/about-craftsman.png',3,1,'2026-08-06 13:50:00'),(4,'Architectural Dining Gallery','Honed Italian Travertine & Marble Displays','assets/images/table_2.png',4,1,'2026-08-06 13:50:00'),(5,'Lighting & Material Sanctuary','Curated Lighting & Aniline Leather Samples','assets/images/light_2.png',5,1,'2026-08-06 13:50:00'),(6,'Private Client Lounge','Consultation Suite for Custom Interior Joinery','assets/images/chair_2.png',6,1,'2026-08-06 13:50:00');
/*!40000 ALTER TABLE `oxo_shop_images` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `oxo_site_content`
--

DROP TABLE IF EXISTS `oxo_site_content`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `oxo_site_content` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `content_key` varchar(100) NOT NULL,
  `content_value` longtext DEFAULT NULL,
  `content_group` varchar(50) DEFAULT 'general',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `content_key` (`content_key`)
) ENGINE=InnoDB AUTO_INCREMENT=10797 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `oxo_site_content`
--

LOCK TABLES `oxo_site_content` WRITE;
/*!40000 ALTER TABLE `oxo_site_content` DISABLE KEYS */;
INSERT INTO `oxo_site_content` VALUES (1,'site_title','OXO — Premium Furniture Store','general','2026-08-06 04:12:33','2026-08-06 04:12:33'),(2,'site_description','Discover OXO, a premium high-end furniture store offering curated luxury sofas, accent chairs, marble dining tables, and designer lighting. Attract visual excellence.','general','2026-08-06 04:12:33','2026-08-06 04:12:33'),(3,'hero_tag','Collection 2026','hero','2026-08-06 04:12:33','2026-08-06 04:12:33'),(4,'hero_title_1','Silent Luxury','hero','2026-08-06 04:12:33','2026-08-06 04:12:33'),(5,'hero_title_2','For Modern Spaces','hero','2026-08-06 04:12:33','2026-08-06 04:12:33'),(6,'hero_desc','Explore a curated assembly of luxury furniture designed for high-end residential interiors. Sculpted shapes, premium textures, and cinematic aesthetics.','hero','2026-08-06 04:12:33','2026-08-06 04:12:33'),(7,'hero_media_path','assets/images/HERO.mp4','hero','2026-08-06 04:12:33','2026-08-06 04:12:33'),(8,'hero_btn_primary_text','Explore Catalog','hero','2026-08-06 04:12:33','2026-08-06 04:12:33'),(9,'hero_btn_primary_link','shop.php','hero','2026-08-06 04:12:33','2026-08-06 04:12:33'),(10,'hero_btn_secondary_text','Our Legacy','hero','2026-08-06 04:12:33','2026-08-06 04:12:33'),(11,'hero_btn_secondary_link','#about','hero','2026-08-06 04:12:33','2026-08-06 04:12:33'),(12,'about_home_image','assets/images/sofa_1.png','about_home','2026-08-06 04:12:33','2026-08-06 04:12:33'),(13,'about_home_stat_val','15+ Years','about_home','2026-08-06 04:12:33','2026-08-06 04:12:33'),(14,'about_home_stat_label','Master Italian Joinery','about_home','2026-08-06 04:12:33','2026-08-06 04:12:33'),(15,'about_home_tag','Our Core Philosophy','about_home','2026-08-06 04:12:33','2026-08-06 04:12:33'),(16,'about_home_title','Architecting Silent Luxury','about_home','2026-08-06 04:12:33','2026-08-06 04:12:33'),(17,'about_home_p1','At OXO, furniture is not merely functional—it is spatial sculpture. Each creation is curated to define elite residential sanctuaries. We harmonise traditional Italian joinery with progressive architectural proportions.','about_home','2026-08-06 04:12:33','2026-08-06 04:12:33'),(18,'about_home_p2','Sourcing rare Calacatta marble pedestals, top-grain aniline leathers, and kiln-dried walnut timbers, our master artisans elevate raw earth elements into tactile works of art.','about_home','2026-08-06 04:12:33','2026-08-06 04:12:33'),(19,'about_home_bento1_val','15+','about_home','2026-08-06 04:12:33','2026-08-06 04:12:33'),(20,'about_home_bento1_label','Years Legacy','about_home','2026-08-06 04:12:33','2026-08-06 04:12:33'),(21,'about_home_bento2_val','100%','about_home','2026-08-06 04:12:33','2026-08-06 04:12:33'),(22,'about_home_bento2_label','Bespoke Design','about_home','2026-08-06 04:12:33','2026-08-06 04:12:33'),(23,'about_home_bento3_val','8,000+','about_home','2026-08-06 04:12:33','2026-08-06 04:12:33'),(24,'about_home_bento3_label','Elite Residences','about_home','2026-08-06 04:12:33','2026-08-06 04:12:33'),(25,'about_page_hero_title','Crafting Timeless Elegance','about_page','2026-08-06 04:12:33','2026-08-06 04:12:33'),(26,'about_page_hero_subtitle','Since 2008, OXO has redefined luxury living through Italian design, master craftsmanship, and sustainable luxury.','about_page','2026-08-06 04:12:33','2026-08-06 04:12:33'),(27,'about_page_heritage_tag','Heritage & Craftsmanship','about_page','2026-08-06 04:12:33','2026-08-06 04:12:33'),(28,'about_page_heritage_title','Born in Milan, Crafted for the World','about_page','2026-08-06 04:12:33','2026-08-06 04:12:33'),(29,'about_page_heritage_p1','Founded in the heart of Lombardy, OXO began as an artisanal workshop dedicated to bespoke joinery and leather sculpting. Over two decades, we have evolved into a global luxury house, blending traditional techniques with cutting-edge architectural design.','about_page','2026-08-06 04:12:33','2026-08-06 04:12:33'),(30,'about_page_heritage_p2','Every sofa frame, dining table, and lighting fixture undergoes over 120 hours of hand-finishing by master craftsmen, ensuring unparalleled quality and enduring beauty.','about_page','2026-08-06 04:12:33','2026-08-06 04:12:33'),(31,'about_page_heritage_img','assets/images/about-craftsman.png','about_page','2026-08-06 04:12:33','2026-08-06 04:12:33'),(32,'about_page_showroom_tag','Flagship Sanctuary','about_page','2026-08-06 04:12:33','2026-08-06 04:12:33'),(33,'about_page_showroom_title','Experience OXO in Person','about_page','2026-08-06 04:12:33','2026-08-06 04:12:33'),(34,'about_page_showroom_p1','Step into our sanctuary of spatial architecture. Our flagship gallery offers private viewings, tactile material swatches, and dedicated interior design consultation.','about_page','2026-08-06 04:12:33','2026-08-06 04:12:33'),(35,'about_page_showroom_p2','Discover how our architectural silhouettes transform luxury residential spaces into refined artistic sanctuaries.','about_page','2026-08-06 04:12:33','2026-08-06 04:12:33'),(36,'about_page_showroom_img','assets/images/flagship-facade.jpg','about_page','2026-08-06 04:12:33','2026-08-06 04:12:33'),(37,'about_card1_icon','fa-gem','about_page','2026-08-06 04:12:33','2026-08-06 04:12:33'),(38,'about_card1_title','Bespoke Artistry','about_page','2026-08-06 04:12:33','2026-08-06 04:12:33'),(39,'about_card1_desc','Every piece is crafted to individual client specifications with rare materials.','about_page','2026-08-06 04:12:33','2026-08-06 04:12:33'),(40,'about_card2_icon','fa-leaf','about_page','2026-08-06 04:12:33','2026-08-06 04:12:33'),(41,'about_card2_title','Sustainable Luxury','about_page','2026-08-06 04:12:33','2026-08-06 04:12:33'),(42,'about_card2_desc','Responsibly sourced timber, eco-certified leathers, and zero-waste production.','about_page','2026-08-06 04:12:33','2026-08-06 04:12:33'),(43,'about_card3_icon','fa-shield-halved','about_page','2026-08-06 04:12:33','2026-08-06 04:12:33'),(44,'about_card3_title','10-Year Warranty','about_page','2026-08-06 04:12:33','2026-08-06 04:12:33'),(45,'about_card3_desc','Enduring structural integrity backed by our house guarantee of perfection.','about_page','2026-08-06 04:12:33','2026-08-06 04:12:33'),(46,'contact_tag','Bespoke Concierge','contact','2026-08-06 04:12:33','2026-08-06 04:12:33'),(47,'contact_title','Connect With OXO Private Service','contact','2026-08-06 04:12:33','2026-08-06 04:12:33'),(48,'contact_subtitle','Have questions regarding custom modular dimensions, bespoke leathers, or private showroom viewings?','contact','2026-08-06 04:12:33','2026-08-06 04:12:33'),(49,'contact_address','84 Luxury Avenue, Suite 900, Mumbai, India','contact','2026-08-06 04:12:33','2026-08-06 04:12:33'),(50,'contact_email','concierge@oxo.design','contact','2026-08-06 04:12:33','2026-08-06 04:12:33'),(51,'contact_phone','+91 (22) 8800-4400','contact','2026-08-06 04:12:33','2026-08-06 04:12:33'),(52,'contact_instagram','#','contact','2026-08-06 04:12:33','2026-08-06 04:12:33'),(53,'contact_facebook','#','contact','2026-08-06 04:12:33','2026-08-06 04:12:33'),(54,'contact_pinterest','#','contact','2026-08-06 04:12:33','2026-08-06 04:12:33'),(55,'footer_desc','Architecting spaces of silent luxury, cinematic elegance, and bespoke Italian craftsmanship. Designed to inspire elite sanctuaries.','footer','2026-08-06 04:12:33','2026-08-06 04:12:33'),(56,'footer_copyright','OXO Furniture. All rights reserved. Architected for high-end residential interiors.','footer','2026-08-06 04:12:33','2026-08-06 04:12:33'),(137,'about_home_btn_text','Read Our Full Story','about_home','2026-08-06 04:16:46','2026-08-06 04:16:46'),(138,'about_home_btn_link','about.php','about_home','2026-08-06 04:16:46','2026-08-06 04:16:46'),(168,'contact_map','https://maps.google.com','contact','2026-08-06 04:16:46','2026-08-06 04:16:46'),(450,'footer_dev_credit','Designed and Developed by Perumalil Creative','footer','2026-08-06 04:20:49','2026-08-06 04:21:30'),(451,'footer_dev_link','#https://perumalilcreative.com/','footer','2026-08-06 04:20:49','2026-08-06 04:21:55');
/*!40000 ALTER TABLE `oxo_site_content` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `oxo_users`
--

DROP TABLE IF EXISTS `oxo_users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `oxo_users`
--

LOCK TABLES `oxo_users` WRITE;
/*!40000 ALTER TABLE `oxo_users` DISABLE KEYS */;
INSERT INTO `oxo_users` VALUES (1,'Sojinmathew10testuser Oxo','sojinmathew10testuser.oxo@gmail.com40@gmail.com','$2y$10$kY4HFZJDRzq05.eTk5tgLODp6fim1BXk.9VxpnEWvTrDtwVvGOKnO',NULL,NULL,'2026-07-13 05:19:36'),(2,'SOJIN MATHEW','sojinmathew1040@gmail.com','$2y$10$G8XppuI4MowCDwB3noLvOu0timWNZ5giBCeRBPRkOuMwZ2NA.v73q',NULL,NULL,'2026-07-13 05:23:40');
/*!40000 ALTER TABLE `oxo_users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_addons`
--

DROP TABLE IF EXISTS `product_addons`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `product_addons` (
  `product_id` int(11) NOT NULL,
  `addon_product_id` int(11) NOT NULL,
  PRIMARY KEY (`product_id`,`addon_product_id`),
  KEY `addon_product_id` (`addon_product_id`),
  CONSTRAINT `product_addons_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `product_addons_ibfk_2` FOREIGN KEY (`addon_product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_addons`
--

LOCK TABLES `product_addons` WRITE;
/*!40000 ALTER TABLE `product_addons` DISABLE KEYS */;
/*!40000 ALTER TABLE `product_addons` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_attribute_values`
--

DROP TABLE IF EXISTS `product_attribute_values`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `product_attribute_values` (
  `product_id` int(11) NOT NULL,
  `attribute_id` int(11) NOT NULL,
  `value` text NOT NULL,
  PRIMARY KEY (`product_id`,`attribute_id`),
  KEY `attribute_id` (`attribute_id`),
  CONSTRAINT `product_attribute_values_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `product_attribute_values_ibfk_2` FOREIGN KEY (`attribute_id`) REFERENCES `attributes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_attribute_values`
--

LOCK TABLES `product_attribute_values` WRITE;
/*!40000 ALTER TABLE `product_attribute_values` DISABLE KEYS */;
INSERT INTO `product_attribute_values` VALUES (3,12,'Opal Glass & Brushed Brass'),(3,13,'Ø 45 cm, Cord L 200 cm'),(3,14,'Elena Rossi, 2023'),(3,15,'Murano, Italy'),(4,12,'Solid White Oak & Walnut details'),(4,13,'W 180 x D 45 x H 65 cm'),(4,14,'Kenji Tanaka'),(4,15,'Kyoto, Japan'),(5,12,'Multi-density Foam & Premium Bouclé'),(5,13,'W 280 x D 105 x H 68 cm'),(5,14,'Pierre Yovanovitch'),(5,15,'France'),(6,12,'American Walnut & Saddle Leather'),(6,13,'W 74 x D 76 x H 72 cm'),(6,14,'Studio OXO'),(6,15,'United States'),(7,12,'Honed Italian Travertine'),(7,13,'W 110 x D 90 x H 30 cm'),(7,14,'Mateo Falcone'),(7,15,'Tuscany, Italy'),(8,12,'Carbon Steel & Aluminum'),(8,13,'Reach 180 cm, Max H 210 cm'),(8,14,'Achille Castiglioni Re-edition'),(8,15,'Germany');
/*!40000 ALTER TABLE `product_attribute_values` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_variant_combinations`
--

DROP TABLE IF EXISTS `product_variant_combinations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `product_variant_combinations` (
  `product_variant_id` int(11) NOT NULL,
  `variant_option_value_id` int(11) NOT NULL,
  PRIMARY KEY (`product_variant_id`,`variant_option_value_id`),
  KEY `variant_option_value_id` (`variant_option_value_id`),
  CONSTRAINT `product_variant_combinations_ibfk_1` FOREIGN KEY (`product_variant_id`) REFERENCES `product_variants` (`id`) ON DELETE CASCADE,
  CONSTRAINT `product_variant_combinations_ibfk_2` FOREIGN KEY (`variant_option_value_id`) REFERENCES `variant_option_values` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_variant_combinations`
--

LOCK TABLES `product_variant_combinations` WRITE;
/*!40000 ALTER TABLE `product_variant_combinations` DISABLE KEYS */;
/*!40000 ALTER TABLE `product_variant_combinations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_variants`
--

DROP TABLE IF EXISTS `product_variants`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_variants`
--

LOCK TABLES `product_variants` WRITE;
/*!40000 ALTER TABLE `product_variants` DISABLE KEYS */;
/*!40000 ALTER TABLE `product_variants` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `products`
--

DROP TABLE IF EXISTS `products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `products`
--

LOCK TABLES `products` WRITE;
/*!40000 ALTER TABLE `products` DISABLE KEYS */;
INSERT INTO `products` VALUES (3,'Ocular Pendant Light',890.00,NULL,'Lighting',5,'Rossi Illuminazione','assets/images/light_1.png','An elegant spherical hanging luminaire that casts a warm, soft diffuse glow. Crafted with hand-blown sandblasted opal glass and brushed brass metal components.','Opal Glass & Brushed Brass','Ø 45 cm, Cord L 200 cm','Elena Rossi, 2023','Murano, Italy','2026-07-07 09:02:46'),(4,'Kyoto Oak Credenza',4500.00,NULL,'Sofas',6,'Tanaka Atelier','assets/images/storage_1.png','Inspired by traditional Japanese joinery, this low credenza offers sleek sliding slatted panels, hidden soft-close drawers, and raw tactile timber finishes.','Solid White Oak & Walnut details','W 180 x D 45 x H 65 cm','Kenji Tanaka','Kyoto, Japan','2026-07-07 09:02:46'),(5,'Tectonic Bouclé Sofa',6800.00,NULL,'Sofas',6,'Yovanovitch Design','assets/images/sofa_1.png','A low-profile modular sofa composed of monolithic block cushions. Configured to hug the floor, providing deep, immersive comfort with a highly premium contemporary stance.','Multi-density Foam & Premium Bouclé','W 280 x D 105 x H 68 cm','Pierre Yovanovitch','France','2026-07-07 09:02:46'),(6,'Ark Armchair',1200.00,NULL,'Chairs',1,'Studio OXO','assets/images/chair_2.png','A stark architectural silhouette constructed from heavy curved walnut plates holding a suspended full-grain black leather sling seat.','American Walnut & Saddle Leather','W 74 x D 76 x H 72 cm','Studio OXO','United States','2026-07-07 09:02:46'),(7,'Travertine Coffee Table',2100.00,NULL,'Tables',4,'Falcone Marmi','assets/images/table_2.png','An organic-shaped low coffee table sculpted entirely from premium honed Italian travertine stone, displaying beautiful natural gas pores and rich limestone sediment banding.','Honed Italian Travertine','W 110 x D 90 x H 30 cm','Mateo Falcone','Tuscany, Italy','2026-07-07 09:02:46'),(8,'Halogen Arc Floor Lamp',1150.00,NULL,'Lighting',5,'Castiglioni Studio','assets/images/light_2.png','A dramatic sweeping floor lamp designed to overhang seating or dining settings. Finished in matte carbon black with an adjustable focal shade.','Carbon Steel & Aluminum','Reach 180 cm, Max H 210 cm','Achille Castiglioni Re-edition','Germany','2026-07-07 09:02:46');
/*!40000 ALTER TABLE `products` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `variant_option_values`
--

DROP TABLE IF EXISTS `variant_option_values`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `variant_option_values` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `variant_option_id` int(11) NOT NULL,
  `value` varchar(100) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `variant_option_id` (`variant_option_id`),
  CONSTRAINT `variant_option_values_ibfk_1` FOREIGN KEY (`variant_option_id`) REFERENCES `variant_options` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `variant_option_values`
--

LOCK TABLES `variant_option_values` WRITE;
/*!40000 ALTER TABLE `variant_option_values` DISABLE KEYS */;
/*!40000 ALTER TABLE `variant_option_values` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `variant_options`
--

DROP TABLE IF EXISTS `variant_options`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `variant_options` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `variant_options_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `variant_options`
--

LOCK TABLES `variant_options` WRITE;
/*!40000 ALTER TABLE `variant_options` DISABLE KEYS */;
/*!40000 ALTER TABLE `variant_options` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping routines for database 'oxo_db'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-08-10 15:31:46