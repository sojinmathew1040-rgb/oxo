-- OXO Furniture Database Backup
-- Generated on 2026-07-21 10:35:21

SET FOREIGN_KEY_CHECKS=0;

DROP TABLE IF EXISTS `admins`;
CREATE TABLE `admins` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `admins` VALUES("1","admin","$2y$10$UFnHeM8lcXQdXaryUSQetu12gDRNWRNcF0JPbbe5U1a7wsp6gkkWm","2026-07-07 14:32:46");

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

INSERT INTO `attributes` VALUES("1","Upholstery Material","upholstery_material","select","[\"Velvet\",\"Leather\",\"Boucl\\u00e9\",\"Linen\"]");
INSERT INTO `attributes` VALUES("2","Max Weight Capacity","max_weight_capacity","number",NULL);
INSERT INTO `attributes` VALUES("3","Ergonomic Rating","ergonomic_rating","number",NULL);
INSERT INTO `attributes` VALUES("4","Bed Size","bed_size","select","[\"King\",\"Queen\",\"Twin\"]");
INSERT INTO `attributes` VALUES("5","Headboard Type","headboard_type","text",NULL);
INSERT INTO `attributes` VALUES("6","Mattress Included","mattress_included","toggle",NULL);
INSERT INTO `attributes` VALUES("7","Number of Drawers","number_of_drawers","number",NULL);
INSERT INTO `attributes` VALUES("8","Cable Management Holes","cable_management_holes","toggle",NULL);
INSERT INTO `attributes` VALUES("9","Material Type","material_type","select","[\"Oak\",\"Walnut\",\"Steel\"]");
INSERT INTO `attributes` VALUES("10","Shape","shape","text",NULL);
INSERT INTO `attributes` VALUES("11","Seating Capacity","seating_capacity","number",NULL);
INSERT INTO `attributes` VALUES("12","Material","material","text",NULL);
INSERT INTO `attributes` VALUES("13","Dimensions","dimensions","text",NULL);
INSERT INTO `attributes` VALUES("14","Designer","designer","text",NULL);
INSERT INTO `attributes` VALUES("15","Origin","origin","text",NULL);

DROP TABLE IF EXISTS `categories`;
CREATE TABLE `categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `categories` VALUES("1","Chair","chair");
INSERT INTO `categories` VALUES("2","Cot Set","cot-set");
INSERT INTO `categories` VALUES("3","Study Table","study-table");
INSERT INTO `categories` VALUES("4","Table","table");
INSERT INTO `categories` VALUES("5","Lighting","lighting");
INSERT INTO `categories` VALUES("6","Sofas","sofas");

DROP TABLE IF EXISTS `category_attributes`;
CREATE TABLE `category_attributes` (
  `category_id` int(11) NOT NULL,
  `attribute_id` int(11) NOT NULL,
  PRIMARY KEY (`category_id`,`attribute_id`),
  KEY `attribute_id` (`attribute_id`),
  CONSTRAINT `category_attributes_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE,
  CONSTRAINT `category_attributes_ibfk_2` FOREIGN KEY (`attribute_id`) REFERENCES `attributes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `category_attributes` VALUES("1","1");
INSERT INTO `category_attributes` VALUES("1","2");
INSERT INTO `category_attributes` VALUES("1","3");
INSERT INTO `category_attributes` VALUES("2","4");
INSERT INTO `category_attributes` VALUES("2","5");
INSERT INTO `category_attributes` VALUES("2","6");
INSERT INTO `category_attributes` VALUES("3","7");
INSERT INTO `category_attributes` VALUES("3","8");
INSERT INTO `category_attributes` VALUES("3","9");
INSERT INTO `category_attributes` VALUES("4","10");
INSERT INTO `category_attributes` VALUES("4","11");

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

INSERT INTO `oxo_admins` VALUES("1","admin","$2y$10$rhNISvpbHQtYKoKnsHiBA.xB2T/70bd28iEIb8OzGviUd0wvIKD/G","2026-07-09 08:43:19","8943804920");

DROP TABLE IF EXISTS `oxo_brands`;
CREATE TABLE `oxo_brands` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `logo_path` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `oxo_brands` VALUES("6","Eclipse Lighting","assets/images/logo.png","2026-07-09 09:02:16");
INSERT INTO `oxo_brands` VALUES("7","nmb","assets/images/uploads/brand_1783568282_768.png","2026-07-09 09:08:02");

DROP TABLE IF EXISTS `oxo_categories`;
CREATE TABLE `oxo_categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `slug` varchar(50) NOT NULL,
  `name` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `bg_color` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `oxo_categories` VALUES("1","sofas","Sofas","2026-07-09 09:18:24","rgba(95, 173, 138, 0.03)");
INSERT INTO `oxo_categories` VALUES("2","chairs","Chairs","2026-07-09 09:18:24","#F2FDE7");
INSERT INTO `oxo_categories` VALUES("3","tables","Tables","2026-07-09 09:18:24","rgba(10, 46, 36, 0.02)");
INSERT INTO `oxo_categories` VALUES("4","lighting","Lighting","2026-07-09 09:18:24","rgba(200, 162, 118, 0.035)");
INSERT INTO `oxo_categories` VALUES("5","storage","Storage","2026-07-09 09:18:24","rgba(30, 40, 36, 0.015)");

DROP TABLE IF EXISTS `oxo_colors`;
CREATE TABLE `oxo_colors` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `hex` varchar(7) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `oxo_colors` VALUES("1","Charcoal Black","#1a1a1a","2026-07-09 11:43:22");
INSERT INTO `oxo_colors` VALUES("2","Off-White Linen","#faf9f6","2026-07-09 11:43:22");
INSERT INTO `oxo_colors` VALUES("3","Gold Sand","#bf8f54","2026-07-09 11:43:22");
INSERT INTO `oxo_colors` VALUES("4","Smoked Oak","#4a3b32","2026-07-09 11:43:22");

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

INSERT INTO `oxo_consultations` VALUES("1","SOJIN MATHEW","sojinmathew1040@gmail.com","General Contact","hello","Addressed","2026-07-13 11:35:29","9946020724");
INSERT INTO `oxo_consultations` VALUES("2","SOJIN MATHEW","sojinmathew1040@gmail.com","General Contact","hi","Addressed","2026-07-13 11:39:34","9946020724");
INSERT INTO `oxo_consultations` VALUES("3","SOJIN MATHEW","sojinmathew1040@gmail.com","test","is it avalable","Addressed","2026-07-13 15:18:04","8943804920");
INSERT INTO `oxo_consultations` VALUES("4","SOJIN MATHEW","sojinmathew1040@gmail.com","test","hi","Addressed","2026-07-21 09:48:18","9946020724");
INSERT INTO `oxo_consultations` VALUES("5","Test User","testuser@example.com","Nirvana Modular Sofa","Need a custom length","Addressed","2026-07-21 09:54:06","9999999999");

DROP TABLE IF EXISTS `oxo_materials`;
CREATE TABLE `oxo_materials` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `slug` varchar(50) NOT NULL,
  `name` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `oxo_materials` VALUES("1","wood","Solid Wood","2026-07-09 09:18:24");
INSERT INTO `oxo_materials` VALUES("2","metal","Brushed Metal","2026-07-09 09:18:24");
INSERT INTO `oxo_materials` VALUES("3","glass","Tempered Glass","2026-07-09 09:18:24");
INSERT INTO `oxo_materials` VALUES("4","fabric","Organic Fabric","2026-07-09 09:18:24");
INSERT INTO `oxo_materials` VALUES("5","plastic","Recycled Plastic","2026-07-09 09:18:24");

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
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `oxo_products` VALUES("chair-1","Aurelia Accent Chair","85000","chairs","assets/images/chair_1.png","A sculptural masterpiece featuring a curved silhouette and cozy boucle upholstery, anchored securely by solid smoked oak wood legs.","Dimensions: W: 85cm x D: 80cm x H: 75cm | Fabric: Premium Boucle | Legs: Solid Smoked Oak","{\"Material\":\"Luxury heavyweight boucle yarn (75% acrylic, 25% wool) with soft tactile texture.\",\"Construction\":\"Contoured steel inner frame padded with multi-density ergonomic foam.\",\"Care Instructions\":\"Blot spills immediately with a clean, dry white cloth. Do not rub.\",\"Shipping\":\"Fully assembled. Ships in custom reinforced wooden crate. Delivered in 3-5 business days.\"}","2026-07-09 08:44:00","wood",NULL,NULL,"85","100","240",NULL,NULL);
INSERT INTO `oxo_products` VALUES("chair-2","Vesper Lounge Chair","95000","chairs","assets/images/chair_2.png","A sleek combination of hand-woven premium saddle leather and a matte black powder-coated steel frame. A perfect minimalist statement.","Dimensions: W: 70cm x D: 75cm x H: 80cm | Material: Top-grain Italian Leather | Frame: Alloy Steel","{\"Material\":\"4mm thick vegetable-tanned, top-grain Italian saddle leather strap weave.\",\"Construction\":\"TIG-welded seamless steel pipe frame with satin-black powder-coated finish.\",\"Care Instructions\":\"Treat with high-quality leather conditioner twice a year. Keep away from direct sunlight.\",\"Shipping\":\"Fully assembled. Free threshold delivery. Ships in 4-6 business days.\"}","2026-07-09 08:44:00","wood",NULL,NULL,"85","100","240",NULL,NULL);
INSERT INTO `oxo_products` VALUES("light-2","Solstice Floor Lamp","65000","lighting","assets/images/light_2.png","An elegant floor lamp crafted from a solid green marble base and hand-blown frosted glass sphere diffuser.","Dimensions: H: 165cm x Dia: 35cm | Base: Verde Guatemala Marble | Glass: Mouth-blown opal glass","{\"Material\":\"Verde Guatemala marble base (honed), solid brass frame stem, hand-blown acid-etched glass globe.\",\"Illumination\":\"E27 bulb socket (10W warm dimmable LED bulb included). Foot switch on cloth-wrapped cord.\",\"Care Instructions\":\"Wipe globe with damp glass cleaner cloth. Clean base with stone sealer friendly wipes.\",\"Shipping\":\"Delivered in two packages (globe + base assembly). Ships in 3-5 business days.\"}","2026-07-09 08:44:00","wood",NULL,NULL,"85","100","240",NULL,NULL);
INSERT INTO `oxo_products` VALUES("sofa-1","Nirvana Modular Sofa","185000","sofas","assets/images/uploads/sofa-1_asset_0_1783571329.jpg","A plush, deep-seated modular sofa wrapped in premium performance linen. Experience ultimate luxury, tailored scale, and custom modular configurations.","Dimensions: W: 100cm x D: 240cm x H: 85cm","{\"Material\":\"Performance textured linen (80% polyester, 20% linen), stain-resistant and durable.\",\"Construction\":\"Double-doweled kiln-dried birch wood frame with pocket coil spring suspension.\",\"Care Instructions\":\"Professional upholstery cleaning recommended. Vacuum weekly using a soft brush attachment.\",\"Shipping\":\"Delivered in 3 modular sections. Free white-glove inside delivery & assembly within 7-10 business days.\"}","2026-07-09 08:44:00","wood","6","[\"assets\\/images\\/sofa_1.png\"]","85","100","240",NULL,NULL);
INSERT INTO `oxo_products` VALUES("table-1","Zephyr Dining Table","240000","tables","assets/images/table_1.png","A monolithic dining table crafted from solid Calacatta marble. Featuring soft chamfered edges and cylindrical fluted pedestals.","Dimensions: L: 200cm x W: 100cm x H: 75cm | Top: Italian Calacatta Marble | Base: Marble fluting","{\"Material\":\"Genuine Italian Calacatta Oro marble top with honed matte finish, pre-sealed.\",\"Construction\":\"Fibre-reinforced concrete structural inner core clad in natural fluted marble tiles.\",\"Care Instructions\":\"Always use coasters. Wipe with warm water and neutral pH stone soap. Avoid acidic cleaners.\",\"Shipping\":\"Extremely heavy (280kg). Ships in 2 crates. Requires white-glove setup (included) in 12-15 business days.\"}","2026-07-09 08:44:00","wood",NULL,NULL,"85","100","240",NULL,NULL);
INSERT INTO `oxo_products` VALUES("table-2","Helios Coffee Table","120000","tables","assets/images/table_2.png","A circular, low-profile coffee table combining a dark green travertine top with brushed brass metal accent trim.","Dimensions: Dia: 90cm x H: 38cm | Stone: Green Travertine | Detailing: Solid Brushed Brass","{\"Material\":\"Iranian Forest Green travertine with natural cavities left unfilled for organic textures.\",\"Construction\":\"Solid travertine base with thick solid brass inlay ring detail.\",\"Care Instructions\":\"Clean with dry microfiber cloth. Promptly clean liquids (especially wine\\/coffee) to prevent staining.\",\"Shipping\":\"Crated delivery. Requires basic assembly (attaching top to base). Ships in 5-7 business days.\"}","2026-07-09 08:44:00","wood",NULL,NULL,"85","100","240",NULL,NULL);
INSERT INTO `oxo_products` VALUES("test","test","15626","lighting","assets/images/uploads/test_asset_0_1783589735.webp","Contrary to popular belief, Lorem Ipsum is not simply random text. It has roots in a piece of classical Latin literature from 45 BC, making it over 2000 years old. Richard McClintock, a Latin professor at Hampden-Sydney College in Virginia, looked up one of the more obscure Latin words, consectetur, from a Lorem Ipsum passage, and going through the cites of the word in classical literature, discovered the undoubtable source. Lorem Ipsum comes from sections 1.10.32 and 1.10.33 of \"de Finibus Bonorum et Malorum\" (The Extremes of Good and Evil) by Cicero, written in 45 BC. This book is a treatise on the theory of ethics, very popular during the Renaissance. The first line of Lorem Ipsum, \"Lorem ipsum dolor sit amet..\", comes from a line in section 1.10.32.","Dimensions: W: 100cm x D: 240cm x H: 85cm","{\"Material\":\"\",\"Construction\":\"\",\"Care Instructions\":\"\",\"Shipping\":\"\"}","2026-07-09 15:05:35","fabric","6","[{\"path\":\"assets\\/images\\/uploads\\/test_asset_1_1783589735.webp\",\"color_id\":3},{\"path\":\"assets\\/images\\/uploads\\/test_asset_2_1783589735.webp\",\"color_id\":3},{\"path\":\"assets\\/images\\/uploads\\/test_asset_3_1783589735.webp\",\"color_id\":1},{\"path\":\"assets\\/images\\/uploads\\/test_asset_4_1783589735.webp\",\"color_id\":1},{\"path\":\"assets\\/images\\/uploads\\/test_asset_5_1783589735.webp\",\"color_id\":1}]","85","100","240","3","[1,3]");

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

INSERT INTO `oxo_users` VALUES("1","Sojinmathew10testuser Oxo","sojinmathew10testuser.oxo@gmail.com40@gmail.com","$2y$10$kY4HFZJDRzq05.eTk5tgLODp6fim1BXk.9VxpnEWvTrDtwVvGOKnO",NULL,NULL,"2026-07-13 10:49:36");
INSERT INTO `oxo_users` VALUES("2","SOJIN MATHEW","sojinmathew1040@gmail.com","$2y$10$G8XppuI4MowCDwB3noLvOu0timWNZ5giBCeRBPRkOuMwZ2NA.v73q",NULL,NULL,"2026-07-13 10:53:40");

DROP TABLE IF EXISTS `product_addons`;
CREATE TABLE `product_addons` (
  `product_id` int(11) NOT NULL,
  `addon_product_id` int(11) NOT NULL,
  PRIMARY KEY (`product_id`,`addon_product_id`),
  KEY `addon_product_id` (`addon_product_id`),
  CONSTRAINT `product_addons_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `product_addons_ibfk_2` FOREIGN KEY (`addon_product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


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

INSERT INTO `product_attribute_values` VALUES("3","12","Opal Glass & Brushed Brass");
INSERT INTO `product_attribute_values` VALUES("3","13","Ø 45 cm, Cord L 200 cm");
INSERT INTO `product_attribute_values` VALUES("3","14","Elena Rossi, 2023");
INSERT INTO `product_attribute_values` VALUES("3","15","Murano, Italy");
INSERT INTO `product_attribute_values` VALUES("4","12","Solid White Oak & Walnut details");
INSERT INTO `product_attribute_values` VALUES("4","13","W 180 x D 45 x H 65 cm");
INSERT INTO `product_attribute_values` VALUES("4","14","Kenji Tanaka");
INSERT INTO `product_attribute_values` VALUES("4","15","Kyoto, Japan");
INSERT INTO `product_attribute_values` VALUES("5","12","Multi-density Foam & Premium Bouclé");
INSERT INTO `product_attribute_values` VALUES("5","13","W 280 x D 105 x H 68 cm");
INSERT INTO `product_attribute_values` VALUES("5","14","Pierre Yovanovitch");
INSERT INTO `product_attribute_values` VALUES("5","15","France");
INSERT INTO `product_attribute_values` VALUES("6","12","American Walnut & Saddle Leather");
INSERT INTO `product_attribute_values` VALUES("6","13","W 74 x D 76 x H 72 cm");
INSERT INTO `product_attribute_values` VALUES("6","14","Studio OXO");
INSERT INTO `product_attribute_values` VALUES("6","15","United States");
INSERT INTO `product_attribute_values` VALUES("7","12","Honed Italian Travertine");
INSERT INTO `product_attribute_values` VALUES("7","13","W 110 x D 90 x H 30 cm");
INSERT INTO `product_attribute_values` VALUES("7","14","Mateo Falcone");
INSERT INTO `product_attribute_values` VALUES("7","15","Tuscany, Italy");
INSERT INTO `product_attribute_values` VALUES("8","12","Carbon Steel & Aluminum");
INSERT INTO `product_attribute_values` VALUES("8","13","Reach 180 cm, Max H 210 cm");
INSERT INTO `product_attribute_values` VALUES("8","14","Achille Castiglioni Re-edition");
INSERT INTO `product_attribute_values` VALUES("8","15","Germany");

DROP TABLE IF EXISTS `product_variant_combinations`;
CREATE TABLE `product_variant_combinations` (
  `product_variant_id` int(11) NOT NULL,
  `variant_option_value_id` int(11) NOT NULL,
  PRIMARY KEY (`product_variant_id`,`variant_option_value_id`),
  KEY `variant_option_value_id` (`variant_option_value_id`),
  CONSTRAINT `product_variant_combinations_ibfk_1` FOREIGN KEY (`product_variant_id`) REFERENCES `product_variants` (`id`) ON DELETE CASCADE,
  CONSTRAINT `product_variant_combinations_ibfk_2` FOREIGN KEY (`variant_option_value_id`) REFERENCES `variant_option_values` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


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

INSERT INTO `products` VALUES("3","Ocular Pendant Light","890.00",NULL,"Lighting","5","Rossi Illuminazione","assets/images/light_1.png","An elegant spherical hanging luminaire that casts a warm, soft diffuse glow. Crafted with hand-blown sandblasted opal glass and brushed brass metal components.","Opal Glass & Brushed Brass","Ø 45 cm, Cord L 200 cm","Elena Rossi, 2023","Murano, Italy","2026-07-07 14:32:46");
INSERT INTO `products` VALUES("4","Kyoto Oak Credenza","4500.00",NULL,"Sofas","6","Tanaka Atelier","assets/images/storage_1.png","Inspired by traditional Japanese joinery, this low credenza offers sleek sliding slatted panels, hidden soft-close drawers, and raw tactile timber finishes.","Solid White Oak & Walnut details","W 180 x D 45 x H 65 cm","Kenji Tanaka","Kyoto, Japan","2026-07-07 14:32:46");
INSERT INTO `products` VALUES("5","Tectonic Bouclé Sofa","6800.00",NULL,"Sofas","6","Yovanovitch Design","assets/images/sofa_1.png","A low-profile modular sofa composed of monolithic block cushions. Configured to hug the floor, providing deep, immersive comfort with a highly premium contemporary stance.","Multi-density Foam & Premium Bouclé","W 280 x D 105 x H 68 cm","Pierre Yovanovitch","France","2026-07-07 14:32:46");
INSERT INTO `products` VALUES("6","Ark Armchair","1200.00",NULL,"Chairs","1","Studio OXO","assets/images/chair_2.png","A stark architectural silhouette constructed from heavy curved walnut plates holding a suspended full-grain black leather sling seat.","American Walnut & Saddle Leather","W 74 x D 76 x H 72 cm","Studio OXO","United States","2026-07-07 14:32:46");
INSERT INTO `products` VALUES("7","Travertine Coffee Table","2100.00",NULL,"Tables","4","Falcone Marmi","assets/images/table_2.png","An organic-shaped low coffee table sculpted entirely from premium honed Italian travertine stone, displaying beautiful natural gas pores and rich limestone sediment banding.","Honed Italian Travertine","W 110 x D 90 x H 30 cm","Mateo Falcone","Tuscany, Italy","2026-07-07 14:32:46");
INSERT INTO `products` VALUES("8","Halogen Arc Floor Lamp","1150.00",NULL,"Lighting","5","Castiglioni Studio","assets/images/light_2.png","A dramatic sweeping floor lamp designed to overhang seating or dining settings. Finished in matte carbon black with an adjustable focal shade.","Carbon Steel & Aluminum","Reach 180 cm, Max H 210 cm","Achille Castiglioni Re-edition","Germany","2026-07-07 14:32:46");

DROP TABLE IF EXISTS `variant_option_values`;
CREATE TABLE `variant_option_values` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `variant_option_id` int(11) NOT NULL,
  `value` varchar(100) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `variant_option_id` (`variant_option_id`),
  CONSTRAINT `variant_option_values_ibfk_1` FOREIGN KEY (`variant_option_id`) REFERENCES `variant_options` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


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
