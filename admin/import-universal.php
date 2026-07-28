<?php
require_once __DIR__ . '/auth.php';
require_admin_login();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/generate-docs.php';

// Auto-cleanup redundant temporary importer files & fake SEO banner products
$redundant_files = [
    __DIR__ . '/../import_single_cli.php',
    __DIR__ . '/../generate-docs.php',
    __DIR__ . '/import-nilkamal.php',
    __DIR__ . '/import-nilkamal-bulk.php',
    __DIR__ . '/clean.php',
    __DIR__ . '/test.php',
    __DIR__ . '/../scratch/do_cleanup.php',
    __DIR__ . '/../scratch/generate-docs.php'
];
foreach ($redundant_files as $rf) {
    if (file_exists($rf)) {
        @unlink($rf);
    }
}

$db = get_db_connection();
if ($db) {
    // Delete any fake product created from homepage SEO banner text
    $db->exec("DELETE FROM `oxo_products` WHERE `title` LIKE '%KERALA%WOODEN%' OR `title` LIKE '%TEAK WOOD FURNITURE IN KERALA%' OR `title` LIKE '%BUYBACK%'");

    // Ensure default materials exist in oxo_materials table
    $db->exec("CREATE TABLE IF NOT EXISTS `oxo_materials` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `slug` VARCHAR(50) NOT NULL UNIQUE,
        `name` VARCHAR(100) NOT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB;");
    
    $mats = [
        ['wood', 'Wood'],
        ['metal', 'Metal'],
        ['leather', 'Leather'],
        ['fabric', 'Fabric'],
        ['plastic', 'Plastic'],
        ['glass', 'Glass'],
        ['marble', 'Marble']
    ];
    $stmt_m = $db->prepare("INSERT IGNORE INTO `oxo_materials` (`slug`, `name`) VALUES (?, ?)");
    foreach ($mats as $m) {
        $stmt_m->execute([$m[0], $m[1]]);
    }
}

$message = '';
$message_type = '';
$extracted_data = null;
$gallery_extraction = null;
$imported_product = null;
$batch_imported_count = 0;

// Helper: Fetch URL content using cURL
function fetch_web_page($url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36');
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 25);
    $html = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_code === 200 && $html) {
        return $html;
    }
    return null;
}

// Helper: Download image locally
function download_universal_image($img_url, $brand_slug, $product_slug, $index) {
    if (empty($img_url)) return null;

    $upload_dir = __DIR__ . '/../assets/images/uploads/' . $brand_slug . '/';
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    $clean_url = strtok($img_url, '?');
    $ext = pathinfo($clean_url, PATHINFO_EXTENSION);
    if (!$ext || strlen($ext) > 4) {
        $ext = 'jpg';
    }

    $filename = $product_slug . '_img_' . $index . '_' . time() . '.' . $ext;
    $target_filepath = $upload_dir . $filename;
    $relative_path = 'assets/images/uploads/' . $brand_slug . '/' . $filename;

    $ch = curl_init($clean_url);
    $fp = fopen($target_filepath, 'wb');
    curl_setopt($ch, CURLOPT_FILE, $fp);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0');
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 25);
    curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    fclose($fp);

    if ($http_code === 200 && file_exists($target_filepath) && filesize($target_filepath) > 0) {
        compress_and_save_image($target_filepath, 78);
        return $relative_path;
    }
    return null;
}

// Compress and optimize image file to lightweight KB size format for fast live loading
function compress_and_save_image($source_filepath, $quality = 78) {
    if (!file_exists($source_filepath) || filesize($source_filepath) === 0) return;

    $info = @getimagesize($source_filepath);
    if (!$info) return;

    $mime = $info['mime'];
    $width = $info[0];
    $height = $info[1];

    $max_dim = 1200;

    if ($width > $max_dim || $height > $max_dim) {
        $ratio = min($max_dim / $width, $max_dim / $height);
        $new_width = (int)round($width * $ratio);
        $new_height = (int)round($height * $ratio);
    } else {
        $new_width = $width;
        $new_height = $height;
    }

    $image = null;
    switch ($mime) {
        case 'image/jpeg':
        case 'image/jpg':
            if (function_exists('imagecreatefromjpeg')) {
                $image = @imagecreatefromjpeg($source_filepath);
            }
            break;
        case 'image/png':
            if (function_exists('imagecreatefrompng')) {
                $image = @imagecreatefrompng($source_filepath);
            }
            break;
        case 'image/webp':
            if (function_exists('imagecreatefromwebp')) {
                $image = @imagecreatefromwebp($source_filepath);
            }
            break;
    }

    if ($image) {
        $canvas = imagecreatetruecolor($new_width, $new_height);
        $bg = imagecolorallocate($canvas, 255, 255, 255);
        imagefill($canvas, 0, 0, $bg);

        imagecopyresampled($canvas, $image, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
        
        // Save optimized JPEG to compress file size to small KB format
        @imagejpeg($canvas, $source_filepath, $quality);

        imagedestroy($image);
        imagedestroy($canvas);
    }
}

// Helper: Get or create Color ID in oxo_colors
function get_or_create_color_id($pdo, $color_name) {
    static $color_cache = [];
    $name_trimmed = trim($color_name);
    if (empty($name_trimmed)) return null;

    $normalized = strtolower($name_trimmed);
    if (isset($color_cache[$normalized])) {
        return $color_cache[$normalized];
    }

    $hex_map = [
        'red' => '#E74C3C',
        'pink' => '#E84393',
        'green' => '#2ECC71',
        'yellow' => '#F1C40F',
        'blue' => '#3498DB',
        'navy' => '#1B1464',
        'black' => '#1A1A1A',
        'white' => '#FAF9F6',
        'grey' => '#95A5A6',
        'gray' => '#95A5A6',
        'orange' => '#E67E22',
        'purple' => '#8E44AD',
        'violet' => '#9B59B6',
        'brown' => '#5C4033',
        'walnut' => '#4A3B32',
        'oak' => '#8B5A2B',
        'teak' => '#A0522D',
        'beige' => '#F5F5DC',
        'cream' => '#FFFDD0',
        'gold' => '#BF8F54',
        'silver' => '#BDC3C7'
    ];

    $hex = '#333333';
    foreach ($hex_map as $key => $h) {
        if (strpos($normalized, $key) !== false) {
            $hex = $h;
            break;
        }
    }

    try {
        $stmt = $pdo->prepare("SELECT `id` FROM `oxo_colors` WHERE LOWER(`name`) = ? LIMIT 1");
        $stmt->execute([$normalized]);
        $existing_id = $stmt->fetchColumn();

        if ($existing_id) {
            $color_cache[$normalized] = (int)$existing_id;
            return (int)$existing_id;
        }

        $stmt = $pdo->prepare("INSERT INTO `oxo_colors` (`name`, `hex`) VALUES (?, ?)");
        $stmt->execute([ucwords($name_trimmed), $hex]);
        $new_id = (int)$pdo->lastInsertId();
        $color_cache[$normalized] = $new_id;
        return $new_id;
    } catch (\Exception $e) {
        return null;
    }
}

// Brand Detection & Deduplication Engine
function get_or_create_brand($db, $url) {
    if (!$db || empty($url)) return null;

    $host = parse_url($url, PHP_URL_HOST);
    if (!$host) $host = $url;
    $host = preg_replace('/^www\./', '', strtolower($host));
    
    $domain_brand_map = [
        'indroyal.com' => 'Indroyal',
        'applecart.co.in' => 'Applecart',
        'supremefurniture.co.in' => 'Supreme Furniture',
        'pepsindia.com' => 'Peps India',
        'mmfoam.com' => 'M.M. Foam',
        'evergreenchair.com' => 'Evergreen Chair',
        'nilkamalfurniture.com' => 'Nilkamal Furniture'
    ];

    $brand_name = isset($domain_brand_map[$host]) ? $domain_brand_map[$host] : ucwords(explode('.', $host)[0]);
    $brand_slug = preg_replace('/[^a-z0-9]/', '', strtolower($brand_name));

    try {
        $stmt = $db->prepare("SELECT `id` FROM `oxo_brands` WHERE LOWER(`name`) = ? OR LOWER(`name`) LIKE ? LIMIT 1");
        $stmt->execute([strtolower($brand_name), '%' . strtolower($brand_name) . '%']);
        $existing_id = $stmt->fetchColumn();

        if ($existing_id) {
            return (int)$existing_id;
        }

        $logo_path = 'assets/images/uploads/brands/' . $brand_slug . '_logo.png';
        $brand_dir = __DIR__ . '/../assets/images/uploads/brands/';
        if (!file_exists($brand_dir)) {
            mkdir($brand_dir, 0777, true);
        }

        if (!file_exists(__DIR__ . '/../' . $logo_path)) {
            $favicon_url = 'https://www.google.com/s2/favicons?domain=' . $host . '&sz=128';
            $ch = curl_init($favicon_url);
            $fp = fopen(__DIR__ . '/../' . $logo_path, 'wb');
            curl_setopt($ch, CURLOPT_FILE, $fp);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 15);
            curl_exec($ch);
            curl_close($ch);
            fclose($fp);
        }

        $stmt = $db->prepare("INSERT INTO `oxo_brands` (`name`, `logo_path`) VALUES (?, ?)");
        $stmt->execute([$brand_name, $logo_path]);
        return (int)$db->lastInsertId();
    } catch (\Exception $e) {
        error_log("Failed to get/create brand: " . $e->getMessage());
        return null;
    }
}

// Dimension Extractor Helper (Parses Height, Width, Length from page text)
function parse_product_dimensions($text) {
    if (empty($text)) return ['height' => null, 'width' => null, 'length' => null];

    $clean = strtolower(strip_tags(html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8')));
    $height = null;
    $width = null;
    $length = null;

    if (preg_match('/(?:height|h|ht)\s*[:=\-]?\s*(\d+(?:\.\d+)?)\s*(?:cm|mm|inches|in|\"|m)/i', $clean, $m)) {
        $height = (int)round((float)$m[1]);
    }
    if (preg_match('/(?:width|w|wt)\s*[:=\-]?\s*(\d+(?:\.\d+)?)\s*(?:cm|mm|inches|in|\"|m)/i', $clean, $m)) {
        $width = (int)round((float)$m[1]);
    }
    if (preg_match('/(?:length|depth|d|l|len)\s*[:=\-]?\s*(\d+(?:\.\d+)?)\s*(?:cm|mm|inches|in|\"|m)/i', $clean, $m)) {
        $length = (int)round((float)$m[1]);
    }

    if (!$height || !$width || !$length) {
        if (preg_match('/(\d+(?:\.\d+)?)\s*(?:cm)?\s*[x×*]\s*(\d+(?:\.\d+)?)\s*(?:cm)?\s*[x×*]\s*(\d+(?:\.\d+)?)\s*cm/i', $clean, $m)) {
            $length = (int)round((float)$m[1]);
            $width = (int)round((float)$m[2]);
            $height = (int)round((float)$m[3]);
        }
    }

    return [
        'height' => $height,
        'width' => $width,
        'length' => $length
    ];
}

// Category Auto-Creator helper
function ensure_category_exists($db, $slug_or_name) {
    if (!$db || empty($slug_or_name)) return 'chairs';
    $slug = preg_replace('/[^a-z0-9\-]/', '', strtolower(trim($slug_or_name)));
    if (empty($slug)) $slug = 'chairs';
    $name = ucwords(str_replace('-', ' ', $slug_or_name));
    try {
        $stmt = $db->prepare("SELECT `slug` FROM `oxo_categories` WHERE `slug` = ? OR LOWER(`name`) = ? LIMIT 1");
        $stmt->execute([$slug, strtolower($name)]);
        $existing = $stmt->fetchColumn();
        if ($existing) return $existing;

        $stmt = $db->prepare("INSERT INTO `oxo_categories` (`slug`, `name`, `bg_color`) VALUES (?, ?, 'rgba(95, 173, 138, 0.03)')");
        $stmt->execute([$slug, $name]);
        return $slug;
    } catch (\Exception $e) {
        return $slug;
    }
}

// Material Auto-Creator helper
function ensure_material_exists($db, $slug_or_name) {
    if (!$db || empty($slug_or_name)) return 'wood';
    $slug = preg_replace('/[^a-z0-9\-]/', '', strtolower(trim($slug_or_name)));
    if (empty($slug)) $slug = 'wood';
    $name = ucwords(str_replace('-', ' ', $slug_or_name));
    try {
        $stmt = $db->prepare("SELECT `slug` FROM `oxo_materials` WHERE `slug` = ? OR LOWER(`name`) = ? LIMIT 1");
        $stmt->execute([$slug, strtolower($name)]);
        $existing = $stmt->fetchColumn();
        if ($existing) return $existing;

        $stmt = $db->prepare("INSERT INTO `oxo_materials` (`slug`, `name`) VALUES (?, ?)");
        $stmt->execute([$slug, $name]);
        return $slug;
    } catch (\Exception $e) {
        return $slug;
    }
}

// Category Auto-Mapper
function map_universal_category($product_type, $title, $body_text) {
    $text = strtolower($product_type . ' ' . $title . ' ' . $body_text);
    
    if (strpos($text, 'bed') !== false || strpos($text, 'mattress') !== false || strpos($text, 'cot') !== false || strpos($text, 'pillow') !== false) {
        return 'beds';
    }
    if (strpos($text, 'chair') !== false || strpos($text, 'recliner') !== false || strpos($text, 'stool') !== false || strpos($text, 'bench') !== false) {
        return 'chairs';
    }
    if (strpos($text, 'sofa') !== false || strpos($text, 'couch') !== false || strpos($text, 'settee') !== false || strpos($text, 'lounger') !== false) {
        return 'sofas';
    }
    if (strpos($text, 'table') !== false || strpos($text, 'desk') !== false) {
        return 'tables';
    }
    if (strpos($text, 'lamp') !== false || strpos($text, 'light') !== false) {
        return 'lighting';
    }
    if (strpos($text, 'cabinet') !== false || strpos($text, 'wardrobe') !== false || strpos($text, 'storage') !== false || strpos($text, 'rack') !== false || strpos($text, 'shelf') !== false || strpos($text, 'almirah') !== false) {
        return 'storage';
    }
    
    return 'chairs';
}

// Material Auto-Mapper
function map_universal_material($title, $body_text) {
    $text = strtolower($title . ' ' . $body_text);

    if (strpos($text, 'plastic') !== false || strpos($text, 'polypropylene') !== false) {
        return 'plastic';
    }
    if (strpos($text, 'leather') !== false || strpos($text, 'leatherette') !== false) {
        return 'leather';
    }
    if (strpos($text, 'fabric') !== false || strpos($text, 'velvet') !== false || strpos($text, 'cotton') !== false || strpos($text, 'linen') !== false) {
        return 'fabric';
    }
    if (strpos($text, 'metal') !== false || strpos($text, 'steel') !== false || strpos($text, 'iron') !== false || strpos($text, 'aluminum') !== false) {
        return 'metal';
    }
    if (strpos($text, 'glass') !== false) {
        return 'glass';
    }
    if (strpos($text, 'marble') !== false) {
        return 'marble';
    }
    
    return 'wood';
}

// Extract images from Category/Gallery showcase pages
function extract_gallery_page_items($html, $raw_url) {
    $scheme = parse_url($raw_url, PHP_URL_SCHEME) ?: 'https';
    $host = parse_url($raw_url, PHP_URL_HOST);
    $base_domain = $scheme . '://' . $host;
    $path = parse_url($raw_url, PHP_URL_PATH) ?: '';

    $category_slug = map_universal_category($path, '', $html);
    $found_images = [];

    if (preg_match_all('/<img[^>]+(?:src|data-src|data-lazy-src|srcset)=["\']([^"\']+)["\']/is', $html, $matches)) {
        foreach ($matches[1] as $src) {
            $src = explode(' ', trim($src))[0];
            $src = strtok($src, '?');

            // Skip non-product icons, logos, avatars, and promo banners
            if (preg_match('/(logo|icon|avatar|favicon|pixel|sprite|loader|arrow|chevron|cart|svg|banner|buyback|hero|slide|promo|offer|discount)/i', $src)) {
                continue;
            }

            if (strpos($src, '//') === 0) {
                $src = $scheme . ':' . $src;
            } else if (strpos($src, '/') === 0) {
                $src = $base_domain . $src;
            } else if (strpos($src, 'http') !== 0) {
                $src = $base_domain . '/' . $src;
            }

            $ext = strtolower(pathinfo(parse_url($src, PHP_URL_PATH), PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'avif'])) {
                $found_images[] = $src;
            }
        }
    }

    $unique_images = array_values(array_unique($found_images));
    return [
        'category' => $category_slug,
        'images' => $unique_images
    ];
}

// Short & Clean Product ID Generator Helper (Short, readable IDs like nk-ludo-table)
function generate_short_product_id($title_or_handle, $brand_name = '', $pdo = null) {
    $clean = preg_replace('/[^a-z0-9\s\-]/', '', strtolower($title_or_handle));
    $words = array_filter(explode('-', str_replace(' ', '-', $clean)));
    
    $stop_words = ['with', 'set', 'for', 'and', 'the', 'in', 'of', 'by', 'nk', 'nilkamal', 'furniture', 'product', 'item'];
    $filtered = [];
    foreach ($words as $w) {
        if (!in_array($w, $stop_words) && strlen($w) > 1 && !is_numeric($w)) {
            $filtered[] = $w;
        }
    }
    if (empty($filtered)) {
        $filtered = array_slice($words, 0, 3);
    } else {
        $filtered = array_slice($filtered, 0, 3);
    }
    
    $prefix = 'nk';
    if (!empty($brand_name)) {
        $clean_b = preg_replace('/[^a-z0-9]/', '', strtolower($brand_name));
        if (!empty($clean_b)) {
            $prefix = substr($clean_b, 0, 3);
        }
    }
    
    $short_slug = implode('-', $filtered);
    if (empty($short_slug)) {
        $short_slug = 'item-' . rand(100, 999);
    }
    
    $id = $prefix . '-' . $short_slug;
    if (strlen($id) > 28) {
        $id = substr($id, 0, 28);
        $id = rtrim($id, '-');
    }
    
    if ($pdo) {
        try {
            $check_stmt = $pdo->prepare("SELECT COUNT(*) FROM `oxo_products` WHERE `id` = ?");
            $check_stmt->execute([$id]);
            if ($check_stmt->fetchColumn() > 0) {
                $id .= '-' . rand(10, 99);
            }
        } catch (\Exception $e) {}
    }

    return $id;
}

// --- AJAX BULK CATALOG SYNC ENDPOINT ---
if (isset($_GET['api']) && $_GET['api'] === 'bulk_batch') {
    header('Content-Type: application/json');
    $domain_input = trim($_GET['domain'] ?? 'nilkamalfurniture.com');
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = 20;

    $clean_domain = preg_replace('/^https?:\/\//', '', rtrim($domain_input, '/'));
    $brand_id = get_or_create_brand($db, 'https://' . $clean_domain);
    $brand_slug = preg_replace('/[^a-z0-9]/', '', strtolower($clean_domain));

    $url = "https://{$clean_domain}/products.json?limit={$limit}&page={$page}";
    $data_str = fetch_web_page($url);
    $data = $data_str ? json_decode($data_str, true) : null;

    if (!$data || empty($data['products'])) {
        echo json_encode(['status' => 'complete', 'imported_count' => 0, 'message' => 'No more products to import for this brand catalog.']);
        exit;
    }

    $imported_in_batch = 0;
    $imported_titles = [];

    foreach ($data['products'] as $p) {
        $product_id = generate_short_product_id($p['handle'] ?: $p['title'], $clean_domain, $db);
        $title = $p['title'];
        $raw_price = isset($p['variants'][0]['price']) ? (float)$p['variants'][0]['price'] : 0;
        $price = (int)round($raw_price);

        $raw_desc = $p['body_html'] ?? '';
        $clean_desc = html_entity_decode($raw_desc, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $clean_desc = trim(preg_replace('/\s+/', ' ', strip_tags($clean_desc)));
        if (strlen($clean_desc) > 800) {
            $clean_desc = substr($clean_desc, 0, 797) . '...';
        }
        if (empty($clean_desc)) {
            $clean_desc = $title . ' from Brand Catalog Collection.';
        }

        $category = map_universal_category($p['product_type'] ?? '', $title, $raw_desc);
        $material = map_universal_material($title, $raw_desc);
        $specs = "Brand Partner | Model: " . $title . " | SKU: " . ($p['variants'][0]['sku'] ?? 'PROD-' . $p['id']);

        $details = [
            "Material" => ucfirst($material),
            "Construction" => "Engineered for high durability and ergonomic support.",
            "Care Instructions" => "Wipe clean with a soft dry cloth. Avoid harsh chemicals.",
            "Shipping" => "Delivered directly to doorstep with assembly."
        ];
        $details_json = json_encode($details);

        // Color & Variant Processing
        $variant_color_map = [];
        $color_ids_set = [];
        $primary_color_id = null;

        $options = $p['options'] ?? [];
        $color_option_index = null;
        foreach ($options as $opt_idx => $opt) {
            $opt_name = strtolower($opt['name'] ?? '');
            if (strpos($opt_name, 'color') !== false || strpos($opt_name, 'colour') !== false || strpos($opt_name, 'finish') !== false) {
                $color_option_index = 'option' . ($opt_idx + 1);
                break;
            }
        }

        $variants = $p['variants'] ?? [];
        foreach ($variants as $v) {
            $c_name = '';
            if ($color_option_index && isset($v[$color_option_index])) {
                $c_name = $v[$color_option_index];
            } else {
                $v_title = $v['title'] ?? '';
                foreach (['Red', 'Pink', 'Green', 'Yellow', 'Blue', 'Black', 'White', 'Grey', 'Orange', 'Purple', 'Brown'] as $known_c) {
                    if (stripos($v_title, $known_c) !== false) {
                        $c_name = $known_c;
                        break;
                    }
                }
            }

            if ($c_name) {
                $cid = get_or_create_color_id($db, $c_name);
                if ($cid) {
                    $variant_color_map[$v['id']] = $cid;
                    $color_ids_set[$cid] = true;
                    if ($primary_color_id === null) $primary_color_id = $cid;
                }
            }
        }

        $images = $p['images'] ?? [];
        $local_main_image = 'assets/images/chair_1.png';
        $gallery_items = [];

        if (!empty($images)) {
            foreach ($images as $idx => $img_obj) {
                $img_src = is_array($img_obj) ? ($img_obj['src'] ?? '') : $img_obj;
                $img_var_ids = is_array($img_obj) ? ($img_obj['variant_ids'] ?? []) : [];

                $assigned_color_id = null;
                if (!empty($img_var_ids)) {
                    foreach ($img_var_ids as $vid) {
                        if (isset($variant_color_map[$vid])) {
                            $assigned_color_id = $variant_color_map[$vid];
                            break;
                        }
                    }
                }

                if (!$assigned_color_id) {
                    $img_name_lower = strtolower($img_src);
                    foreach (['red', 'pink', 'green', 'yellow', 'blue', 'black', 'white', 'grey', 'orange', 'purple', 'brown'] as $known_c) {
                        if (strpos($img_name_lower, $known_c) !== false) {
                            $assigned_color_id = get_or_create_color_id($db, ucfirst($known_c));
                            if ($assigned_color_id) $color_ids_set[$assigned_color_id] = true;
                            break;
                        }
                    }
                }

                if ($img_src) {
                    $saved_path = download_universal_image($img_src, $brand_slug, $p['handle'] ?: 'product', $idx);
                    if ($saved_path) {
                        if ($idx === 0) $local_main_image = $saved_path;
                        $gallery_items[] = [
                            'path' => $saved_path,
                            'color_id' => $assigned_color_id
                        ];
                    }
                }
            }
        }

        $color_ids_array = array_keys($color_ids_set);
        $color_ids_json = !empty($color_ids_array) ? json_encode($color_ids_array) : null;
        $gallery_json = !empty($gallery_items) ? json_encode($gallery_items) : null;

        // DB Upsert
        $check_stmt = $db->prepare("SELECT COUNT(*) FROM `oxo_products` WHERE `id` = ?");
        $check_stmt->execute([$product_id]);
        $exists = $check_stmt->fetchColumn() > 0;

        if ($exists) {
            $stmt = $db->prepare("UPDATE `oxo_products` SET 
                `title` = ?, `price` = ?, `category` = ?, `image` = ?, `description` = ?, `specs` = ?, `details` = ?, `gallery` = ?, `color_id` = ?, `color_ids` = ?, `brand_id` = ?, `material_slug` = ?
                WHERE `id` = ?");
            $stmt->execute([$title, $price, $category, $local_main_image, $clean_desc, $specs, $details_json, $gallery_json, $primary_color_id, $color_ids_json, $brand_id, $material, $product_id]);
        } else {
            $stmt = $db->prepare("INSERT INTO `oxo_products` 
                (`id`, `title`, `price`, `category`, `image`, `description`, `specs`, `details`, `gallery`, `material_slug`, `height_cm`, `width_cm`, `length_cm`, `color_id`, `color_ids`, `brand_id`) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 85, 100, 240, ?, ?, ?)");
            $stmt->execute([$product_id, $title, $price, $category, $local_main_image, $clean_desc, $specs, $details_json, $gallery_json, $material, $primary_color_id, $color_ids_json, $brand_id]);
        }

        $imported_in_batch++;
        $imported_titles[] = $title;
    }

    auto_sync_documentation();

    echo json_encode([
        'status' => 'success',
        'page' => $page,
        'imported_count' => $imported_in_batch,
        'titles' => $imported_titles
    ]);
    exit;
}

// BATCH CONFIRM & SAVE GALLERY PRODUCTS
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'batch_confirm_gallery_save') {
    $batch_items = isset($_POST['batch']) ? $_POST['batch'] : [];
    $brand_id = !empty($_POST['brand_id']) ? (int)$_POST['brand_id'] : null;

    $brand_slug = 'universal';
    if ($brand_id && $db) {
        $stmt = $db->prepare("SELECT `name` FROM `oxo_brands` WHERE `id` = ?");
        $stmt->execute([$brand_id]);
        $b_name = $stmt->fetchColumn();
        if ($b_name) {
            $brand_slug = preg_replace('/[^a-z0-9]/', '', strtolower($b_name));
        }
    }

    foreach ($batch_items as $idx => $item) {
        if (!isset($item['selected']) || $item['selected'] !== '1') continue;

        $title = trim($item['title'] ?? "{$brand_slug} Creation #" . ($idx + 1));
        $price = (int)($item['price'] ?? 0);
        $category = ensure_category_exists($db, trim($item['category'] ?? 'chairs'));
        $material = ensure_material_exists($db, trim($item['material_slug'] ?? 'wood'));
        $img_url = trim($item['image_url'] ?? '');
        $description = trim($item['description'] ?? "Premium {$title} from brand catalog collection.");

        $product_slug = preg_replace('/[^a-z0-9\-]/', '', strtolower($title)) . '-' . time() . '-' . $idx;
        $product_id = generate_short_product_id($title, $brand_slug, $db);

        $saved_image = download_universal_image($img_url, $brand_slug, $product_slug, 0);
        if (!$saved_image) {
            $saved_image = 'assets/images/chair_1.png';
        }

        // Color detection
        $assigned_color_id = null;
        foreach (['red', 'pink', 'green', 'yellow', 'blue', 'black', 'white', 'grey', 'orange', 'purple', 'brown'] as $known_c) {
            if (stripos($title, $known_c) !== false || stripos($img_url, $known_c) !== false) {
                $assigned_color_id = get_or_create_color_id($db, ucfirst($known_c));
                break;
            }
        }

        $specs = "Brand Partner | Model: {$title} | SKU: " . strtoupper($product_slug);
        $details = [
            "Material" => ucfirst($material),
            "Construction" => "Engineered for long-lasting luxury comfort.",
            "Care Instructions" => "Wipe clean with a soft damp cloth.",
            "Shipping" => "White-glove doorstep delivery included."
        ];
        $details_json = json_encode($details);

        $stmt = $db->prepare("INSERT INTO `oxo_products` 
            (`id`, `title`, `price`, `category`, `image`, `description`, `specs`, `details`, `gallery`, `material_slug`, `height_cm`, `width_cm`, `length_cm`, `color_id`, `color_ids`, `brand_id`) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, null, ?, 85, 100, 240, ?, null, ?)");
        $stmt->execute([$product_id, $title, $price, $category, $saved_image, $description, $specs, $details_json, $material, $assigned_color_id, $brand_id]);
        $batch_imported_count++;
    }

    auto_sync_documentation();

    $message = "Successfully imported {$batch_imported_count} products from gallery page into your catalog!";
    $message_type = 'success';
}

// STAGE 1: Extract Data from Single or Gallery URL
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'extract_url') {
    $raw_url = trim($_POST['product_url'] ?? '');
    
    if (empty($raw_url) || !filter_var($raw_url, FILTER_VALIDATE_URL)) {
        $message = "Please enter a valid product or category page URL.";
        $message_type = 'danger';
    } else {
        $host = parse_url($raw_url, PHP_URL_HOST);
        $path = parse_url($raw_url, PHP_URL_PATH) ?: '/';
        $path_clean = trim($path, '/');
        
        $brand_id = get_or_create_brand($db, $raw_url);
        $html = fetch_web_page($raw_url);

        $is_homepage_or_catalog = empty($path_clean) || strtolower($path_clean) === 'index.php' || strtolower($path_clean) === 'index.html';

        if ($is_homepage_or_catalog || strpos($raw_url, '/products') === false) {
            if ($html) {
                $gallery_res = extract_gallery_page_items($html, $raw_url);
                if (!empty($gallery_res['images'])) {
                    $gallery_extraction = [
                        'url' => $raw_url,
                        'brand_id' => $brand_id,
                        'category' => $gallery_res['category'],
                        'images' => $gallery_res['images']
                    ];
                }
            }
        }

        if (empty($gallery_extraction)) {
            $path_parts = explode('/', $path_clean);
            $handle = end($path_parts) ?: 'product';
            $product_id = 'prod-' . preg_replace('/[^a-z0-9\-]/', '', strtolower($handle));

            $title = '';
            $price = 0;
            $description = '';
            $images = [];
            $raw_category = '';

            // TIER 1: Shopify JSON API
            $shopify_json_url = '';
            if (strpos($raw_url, '/products/') !== false) {
                $base_url = strtok($raw_url, '?');
                $shopify_json_url = $base_url . '.json';
            }
            
            $shopify_data = $shopify_json_url ? fetch_web_page($shopify_json_url) : null;
            $json_arr = $shopify_data ? json_decode($shopify_data, true) : null;

            if ($json_arr && isset($json_arr['product'])) {
                $p = $json_arr['product'];
                $title = $p['title'] ?? '';
                $price = isset($p['variants'][0]['price']) ? (int)round((float)$p['variants'][0]['price']) : 0;
                $raw_desc = $p['body_html'] ?? '';
                $description = trim(preg_replace('/\s+/', ' ', strip_tags(html_entity_decode($raw_desc, ENT_QUOTES | ENT_HTML5, 'UTF-8'))));
                $raw_category = $p['product_type'] ?? '';
                
                if (!empty($p['images'])) {
                    foreach ($p['images'] as $img) {
                        $images[] = is_array($img) ? ($img['src'] ?? '') : $img;
                    }
                }
            }

            // TIER 2: HTML JSON-LD MICRODATA
            if ((empty($title) || empty($images)) && $html) {
                if (preg_match_all('/<script[^>]+type=["\']application\/ld\+json["\'][^>]*>(.*?)<\/script>/is', $html, $matches)) {
                    foreach ($matches[1] as $json_ld_str) {
                        $ld_data = json_decode($json_ld_str, true);
                        if (!$ld_data) continue;
                        
                        $nodes = isset($ld_data['@graph']) ? $ld_data['@graph'] : [$ld_data];
                        foreach ($nodes as $node) {
                            if (isset($node['@type']) && ($node['@type'] === 'Product' || $node['@type'] === 'IndividualProduct')) {
                                if (empty($title) && isset($node['name'])) $title = $node['name'];
                                if (empty($description) && isset($node['description'])) $description = strip_tags($node['description']);
                                if (empty($price) && isset($node['offers'])) {
                                    $offers = is_array($node['offers']) && isset($node['offers'][0]) ? $node['offers'][0] : $node['offers'];
                                    if (isset($offers['price'])) $price = (int)round((float)$offers['price']);
                                }
                                if (isset($node['image'])) {
                                    $ld_imgs = is_array($node['image']) ? $node['image'] : [$node['image']];
                                    foreach ($ld_imgs as $limg) {
                                        $img_src = is_array($limg) ? ($limg['url'] ?? '') : $limg;
                                        if (!empty($img_src)) $images[] = $img_src;
                                    }
                                }
                            }
                        }
                    }
                }

                // TIER 3: OPENGRAPH META TAGS
                if (empty($title) && preg_match('/<meta[^>]+property=["\']og:title["\'][^>]+content=["\'](.*?)["\']/i', $html, $m)) {
                    $title = html_entity_decode($m[1], ENT_QUOTES, 'UTF-8');
                }
                if (empty($description) && preg_match('/<meta[^>]+property=["\']og:description["\'][^>]+content=["\'](.*?)["\']/i', $html, $m)) {
                    $description = html_entity_decode($m[1], ENT_QUOTES, 'UTF-8');
                }
                if (empty($price) && preg_match('/<meta[^>]+property=["\']product:price:amount["\'][^>]+content=["\'](.*?)["\']/i', $html, $m)) {
                    $price = (int)round((float)$m[1]);
                }
                if (empty($images) && preg_match_all('/<meta[^>]+property=["\']og:image["\'][^>]+content=["\'](.*?)["\']/i', $html, $m)) {
                    $images = array_unique($m[1]);
                }

                // TIER 4: HTML DOM SCRAPER FALLBACK
                if (empty($title) && preg_match('/<h1[^>]*>(.*?)<\/h1>/is', $html, $m)) {
                    $title = trim(strip_tags($m[1]));
                }
                if (empty($title) && preg_match('/<title[^>]*>(.*?)<\/title>/is', $html, $m)) {
                    $title = trim(explode('-', strip_tags($m[1]))[0]);
                }
                if (empty($price) && preg_match('/(?:₹|Rs\.?|INR)\s*([\d,]+)/i', $html, $m)) {
                    $price = (int)str_replace(',', '', $m[1]);
                }
            }

            $category_slug = map_universal_category($raw_category, $title, $description);
            $material_slug = map_universal_material($title, $description);
            $product_id = generate_short_product_id(!empty($title) ? $title : $handle, $host, $db);
            $parsed_dims = parse_product_dimensions($description . ' ' . $html);

            $extracted_data = [
                'url' => $raw_url,
                'id' => $product_id,
                'title' => $title ?: 'Imported Product',
                'price' => $price,
                'category' => $category_slug,
                'material' => $material_slug,
                'brand_id' => $brand_id,
                'height_cm' => $parsed_dims['height'],
                'width_cm' => $parsed_dims['width'],
                'length_cm' => $parsed_dims['length'],
                'description' => $description ?: 'High quality furniture creation crafted for long-lasting comfort.',
                'images' => array_values(array_unique(array_filter($images)))
            ];
        }
    }
}

// STAGE 2: Confirm & Save Single Product to Database
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'confirm_save') {
    $product_id = trim($_POST['product_id'] ?? '');
    $title = trim($_POST['title'] ?? '');
    $price = (int)($_POST['price'] ?? 0);
    $category = ensure_category_exists($db, trim($_POST['category'] ?? 'chairs'));
    $material = ensure_material_exists($db, trim($_POST['material_slug'] ?? 'wood'));
    $brand_id = !empty($_POST['brand_id']) ? (int)$_POST['brand_id'] : null;
    $description = trim($_POST['description'] ?? '');
    $source_url = trim($_POST['source_url'] ?? '');
    $raw_images = isset($_POST['images']) ? $_POST['images'] : [];

    $brand_slug = 'universal';
    if ($brand_id && $db) {
        $stmt = $db->prepare("SELECT `name` FROM `oxo_brands` WHERE `id` = ?");
        $stmt->execute([$brand_id]);
        $b_name = $stmt->fetchColumn();
        if ($b_name) {
            $brand_slug = preg_replace('/[^a-z0-9]/', '', strtolower($b_name));
        }
    }

    $product_slug = preg_replace('/[^a-z0-9\-]/', '', strtolower($product_id ?: $title));

    $height_cm = (isset($_POST['height_cm']) && $_POST['height_cm'] !== '') ? (int)$_POST['height_cm'] : null;
    $width_cm = (isset($_POST['width_cm']) && $_POST['width_cm'] !== '') ? (int)$_POST['width_cm'] : null;
    $length_cm = (isset($_POST['length_cm']) && $_POST['length_cm'] !== '') ? (int)$_POST['length_cm'] : null;

    $detail_construction = !empty($_POST['detail_construction']) ? trim($_POST['detail_construction']) : "Engineered for luxury durability & silent ergonomic comfort.";
    $detail_care = !empty($_POST['detail_care']) ? trim($_POST['detail_care']) : "Wipe clean with a soft dry cloth. Avoid abrasive cleaners.";
    $detail_shipping = !empty($_POST['detail_shipping']) ? trim($_POST['detail_shipping']) : "White-glove doorstep delivery and inside setup included.";

    $specs = "Brand Partner | Model: " . $title . " | SKU: " . strtoupper($product_slug);
    $details = [
        "Material" => ucfirst($material),
        "Construction" => $detail_construction,
        "Care Instructions" => $detail_care,
        "Shipping" => $detail_shipping
    ];
    $details_json = json_encode($details);

    $gallery_items = [];
    $local_main_image = 'assets/images/chair_1.png';
    $color_ids_set = [];
    $primary_color_id = null;

    if (!empty($raw_images)) {
        foreach ($raw_images as $idx => $img_url) {
            $assigned_color_id = null;
            $img_name_lower = strtolower($img_url);
            foreach (['red', 'pink', 'green', 'yellow', 'blue', 'black', 'white', 'grey', 'orange', 'purple', 'brown', 'walnut', 'oak', 'teak', 'beige'] as $known_c) {
                if (strpos($img_name_lower, $known_c) !== false || stripos($title, $known_c) !== false) {
                    $assigned_color_id = get_or_create_color_id($db, ucfirst($known_c));
                    if ($assigned_color_id) {
                        $color_ids_set[$assigned_color_id] = true;
                        if ($primary_color_id === null) $primary_color_id = $assigned_color_id;
                    }
                    break;
                }
            }

            $saved_path = download_universal_image($img_url, $brand_slug, $product_slug, $idx);
            if ($saved_path) {
                if ($idx === 0) $local_main_image = $saved_path;
                $gallery_items[] = [
                    'path' => $saved_path,
                    'color_id' => $assigned_color_id
                ];
            }
        }
    }

    $color_ids_array = array_keys($color_ids_set);
    $color_ids_json = !empty($color_ids_array) ? json_encode($color_ids_array) : null;
    $gallery_json = !empty($gallery_items) ? json_encode($gallery_items) : null;

    $check_stmt = $db->prepare("SELECT COUNT(*) FROM `oxo_products` WHERE `id` = ?");
    $check_stmt->execute([$product_id]);
    $exists = $check_stmt->fetchColumn() > 0;

    if ($exists) {
        $stmt = $db->prepare("UPDATE `oxo_products` SET 
            `title` = ?, `price` = ?, `category` = ?, `image` = ?, `description` = ?, `specs` = ?, `details` = ?, `gallery` = ?, `color_id` = ?, `color_ids` = ?, `brand_id` = ?, `material_slug` = ?, `height_cm` = ?, `width_cm` = ?, `length_cm` = ?, `source_url` = ?
            WHERE `id` = ?");
        $stmt->execute([$title, $price, $category, $local_main_image, $description, $specs, $details_json, $gallery_json, $primary_color_id, $color_ids_json, $brand_id, $material, $height_cm, $width_cm, $length_cm, $source_url, $product_id]);
        $message = "Product '{$title}' successfully updated in catalog!";
    } else {
        $stmt = $db->prepare("INSERT INTO `oxo_products` 
            (`id`, `title`, `price`, `category`, `image`, `description`, `specs`, `details`, `gallery`, `material_slug`, `height_cm`, `width_cm`, `length_cm`, `color_id`, `color_ids`, `brand_id`, `source_url`) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$product_id, $title, $price, $category, $local_main_image, $description, $specs, $details_json, $gallery_json, $material, $height_cm, $width_cm, $length_cm, $primary_color_id, $color_ids_json, $brand_id, $source_url]);
        $message = "Product '{$title}' successfully imported and added to catalog!";
    }

    $message_type = 'success';
    auto_sync_documentation();
    
    $fetch_stmt = $db->prepare("SELECT * FROM `oxo_products` WHERE `id` = ?");
    $fetch_stmt->execute([$product_id]);
    $imported_product = $fetch_stmt->fetch();
    if ($imported_product && !empty($imported_product['gallery'])) {
        $imported_product['gallery_arr'] = json_decode($imported_product['gallery'], true);
    }
}

$db_categories = $db ? $db->query("SELECT * FROM `oxo_categories` ORDER BY `name` ASC")->fetchAll() : [];
$db_materials = $db ? $db->query("SELECT * FROM `oxo_materials` ORDER BY `name` ASC")->fetchAll() : [];
$db_brands = $db ? $db->query("SELECT * FROM `oxo_brands` ORDER BY `name` ASC")->fetchAll() : [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Universal Brand Product Importer - OXO Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        body { background-color: #f4f6f8; font-family: 'Segoe UI', system-ui, -apple-system, sans-serif; }
        .card { border: none; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
        .btn-custom { background: #0a2e24; color: #fff; border-radius: 8px; padding: 12px 28px; font-weight: 600; }
        .btn-custom:hover { background: #134637; color: #fff; }
        .product-img-preview { width: 100%; height: 260px; object-fit: cover; border-radius: 8px; }
        .gallery-thumb { width: 70px; height: 70px; object-fit: cover; border-radius: 6px; margin-right: 8px; border: 1px solid #ddd; }
        .extracted-img-thumb { width: 90px; height: 90px; object-fit: cover; border-radius: 6px; border: 2px solid #e2e8f0; }
        .batch-card-img { width: 100%; height: 160px; object-fit: cover; border-radius: 8px 8px 0 0; }
        .missing-highlight { border: 2px solid #e74c3c !important; background-color: #fdf2f2 !important; }
        .log-box { height: 250px; overflow-y: auto; background: #1e1e1e; color: #00ff66; font-family: monospace; font-size: 13px; padding: 15px; border-radius: 8px; }
        .nav-pills .nav-link { color: #0a2e24; font-weight: 600; border-radius: 8px; padding: 10px 20px; }
        .nav-pills .nav-link.active { background-color: #0a2e24; color: #fff; }
    </style>
</head>
<body>

<div class="container py-5">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h2 class="fw-bold mb-1" style="color: #0a2e24;">Universal Brand Product Importer</h2>
            <p class="text-muted mb-0">Import products from Indroyal, Applecart, Supreme, Peps India, MM Foam, Evergreen Chair, Nilkamal & any brand site.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="sync-prices.php?action=sync_all" class="btn btn-sm btn-outline-primary" style="border-radius: 8px;">
                <i class="bi bi-arrow-repeat me-1"></i> Sync Live Prices
            </a>
            <a href="index.php" class="btn btn-outline-secondary btn-sm" style="border-radius: 8px;"><i class="bi bi-arrow-left me-1"></i> Back to Dashboard</a>
        </div>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-<?= $message_type ?> alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i> <?= htmlspecialchars($message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Navigation Tabs -->
    <ul class="nav nav-pills mb-4" id="importer-tabs" role="tablist">
        <li class="nav-item">
            <button class="nav-link active" id="tab-single-btn" data-bs-toggle="pill" data-bs-target="#tab-single">
                <i class="bi bi-link-45deg me-1"></i> Single / Gallery Page Importer
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link" id="tab-bulk-btn" data-bs-toggle="pill" data-bs-target="#tab-bulk">
                <i class="bi bi-layers me-1"></i> Bulk Brand Catalog Sync
            </button>
        </li>
    </ul>

    <div class="tab-content" id="importer-tab-content">
        
        <!-- TAB 1: Single or Gallery Page Importer -->
        <div class="tab-pane fade show active" id="tab-single">
            <div class="card p-4 mb-4">
                <h5 class="fw-bold mb-3"><i class="bi bi-link-45deg text-primary me-2"></i>Paste Product Page or Category Gallery Link</h5>
                <form action="import-universal.php" method="POST">
                    <input type="hidden" name="action" value="extract_url">
                    
                    <div class="input-group mb-2">
                        <span class="input-group-text bg-white"><i class="bi bi-globe"></i></span>
                        <input type="url" class="form-control form-control-lg" name="product_url" 
                               placeholder="e.g. https://applecart.co.in/ or https://applecart.co.in/bedroom or https://pepsindia.com/..." 
                               value="<?= htmlspecialchars($_POST['product_url'] ?? '') ?>" required>
                        <button type="submit" class="btn btn-custom">
                            <i class="bi bi-search me-1"></i> Extract Product Data
                        </button>
                    </div>
                    <div class="form-text text-muted">Supports single product pages & showcase gallery pages (like Applecart). Auto-detects brand without duplicates.</div>
                </form>
            </div>

            <!-- STAGE 2A: CATEGORY / GALLERY SHOWCASE BATCH REVIEW GRID -->
            <?php if ($gallery_extraction): ?>
                <div class="card p-4 mb-4 border-success">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <h5 class="fw-bold text-success mb-1"><i class="bi bi-images me-2"></i>Category Showcase Page Detected!</h5>
                            <p class="text-muted small mb-0">Found <?= count($gallery_extraction['images']) ?> product photos on page. Review & set prices below to import all items into your catalog.</p>
                        </div>
                        <span class="badge bg-success">Gallery Extraction Mode</span>
                    </div>

                    <form action="import-universal.php" method="POST">
                        <input type="hidden" name="action" value="batch_confirm_gallery_save">
                        <input type="hidden" name="brand_id" value="<?= htmlspecialchars($gallery_extraction['brand_id']) ?>">

                        <div class="row g-3 mb-4">
                            <?php foreach ($gallery_extraction['images'] as $g_idx => $g_img): ?>
                                <div class="col-md-4 col-lg-3">
                                    <div class="card h-100 border shadow-sm">
                                        <img src="<?= htmlspecialchars($g_img) ?>" class="batch-card-img" alt="Gallery Item">
                                        <div class="p-3">
                                            <div class="form-check mb-2">
                                                <input class="form-check-input" type="checkbox" name="batch[<?= $g_idx ?>][selected]" value="1" id="batch_chk_<?= $g_idx ?>" checked>
                                                <label class="form-check-label fw-bold small" for="batch_chk_<?= $g_idx ?>">
                                                    Import Item #<?= $g_idx + 1 ?>
                                                </label>
                                            </div>

                                            <input type="hidden" name="batch[<?= $g_idx ?>][image_url]" value="<?= htmlspecialchars($g_img) ?>">

                                            <div class="mb-2">
                                                <label class="form-label small fw-semibold mb-1">Title</label>
                                                <input type="text" class="form-control form-control-sm" name="batch[<?= $g_idx ?>][title]" 
                                                       value="Creation Item #<?= $g_idx + 1 ?>" required>
                                            </div>

                                            <div class="mb-2">
                                                <label class="form-label small fw-semibold mb-1">Price (₹) <span class="text-danger">*</span></label>
                                                <input type="number" class="form-control form-control-sm missing-highlight" name="batch[<?= $g_idx ?>][price]" 
                                                       placeholder="e.g. 18500" required>
                                            </div>

                                            <div class="mb-2">
                                                <label class="form-label small fw-semibold mb-1">Category</label>
                                                <select class="form-select form-select-sm" name="batch[<?= $g_idx ?>][category]">
                                                    <?php foreach ($db_categories as $cat): ?>
                                                        <option value="<?= htmlspecialchars($cat['slug']) ?>" <?= $gallery_extraction['category'] === $cat['slug'] ? 'selected' : '' ?>>
                                                            <?= htmlspecialchars($cat['name']) ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>

                                            <div>
                                                <label class="form-label small fw-semibold mb-1">Material</label>
                                                <select class="form-select form-select-sm" name="batch[<?= $g_idx ?>][material_slug]">
                                                    <?php foreach ($db_materials as $mat): ?>
                                                        <option value="<?= htmlspecialchars($mat['slug']) ?>" <?= $mat['slug'] === 'wood' ? 'selected' : '' ?>>
                                                            <?= htmlspecialchars($mat['name']) ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <button type="submit" class="btn btn-success btn-lg w-100 fw-bold">
                            <i class="bi bi-cloud-arrow-down-fill me-2"></i> Batch Import All Selected Products to Catalog
                        </button>
                    </form>
                </div>
            <?php endif; ?>

            <!-- STAGE 2B: SINGLE PRODUCT REVIEW FORM -->
            <?php if ($extracted_data): ?>
                <div class="card p-4 mb-4 border-primary">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="fw-bold text-primary mb-0"><i class="bi bi-pencil-square me-2"></i>Review & Complete Product Details</h5>
                        <span class="badge bg-info text-dark">Data Extracted Successfully</span>
                    </div>

                    <?php if ($extracted_data['price'] === 0): ?>
                        <div class="alert alert-warning py-2 mb-3 small">
                            <i class="bi bi-exclamation-triangle-fill me-1"></i> No price was found on the brand page. Please type the <strong>Price (₹)</strong> below before saving.
                        </div>
                    <?php endif; ?>

                    <form action="import-universal.php" method="POST">
                        <input type="hidden" name="action" value="confirm_save">
                        <input type="hidden" name="source_url" value="<?= htmlspecialchars($extracted_data['url']) ?>">
                        <input type="hidden" name="product_id" value="<?= htmlspecialchars($extracted_data['id']) ?>">

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Product Title</label>
                                <input type="text" class="form-control" name="title" value="<?= htmlspecialchars($extracted_data['title']) ?>" required>
                            </div>

                            <div class="col-md-3">
                                <label class="form-label fw-bold">Price (₹) <span class="text-danger">*</span></label>
                                <input type="number" class="form-control <?= $extracted_data['price'] === 0 ? 'missing-highlight' : '' ?>" 
                                       name="price" value="<?= $extracted_data['price'] ?>" placeholder="e.g. 24990" required>
                            </div>

                            <div class="col-md-3">
                                <label class="form-label fw-bold">Category</label>
                                <select class="form-select" name="category" required>
                                    <?php foreach ($db_categories as $cat): ?>
                                        <option value="<?= htmlspecialchars($cat['slug']) ?>" <?= $extracted_data['category'] === $cat['slug'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($cat['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Material</label>
                                <select class="form-select" name="material_slug" required>
                                    <?php foreach ($db_materials as $mat): ?>
                                        <option value="<?= htmlspecialchars($mat['slug']) ?>" <?= $extracted_data['material'] === $mat['slug'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($mat['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label fw-bold">Brand Partner (Auto-Deduplicated)</label>
                                <select class="form-select" name="brand_id">
                                    <?php foreach ($db_brands as $b): ?>
                                        <option value="<?= $b['id'] ?>" <?= (int)$extracted_data['brand_id'] === (int)$b['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($b['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label fw-bold">Extracted Image URLs (<?= count($extracted_data['images']) ?> photos found)</label>
                                <div class="d-flex gap-2 overflow-x-auto pb-2">
                                    <?php foreach ($extracted_data['images'] as $img_url): ?>
                                        <div class="position-relative">
                                            <img src="<?= htmlspecialchars($img_url) ?>" class="extracted-img-thumb" alt="Extracted Photo">
                                            <input type="hidden" name="images[]" value="<?= htmlspecialchars($img_url) ?>">
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-bold">Product Description</label>
                            <textarea class="form-control" name="description" rows="3" required><?= htmlspecialchars($extracted_data['description']) ?></textarea>
                        </div>

                        <!-- DIMENSIONS SECTION -->
                        <div class="card bg-light border-0 p-3 mb-3">
                            <h6 class="fw-bold text-dark mb-2"><i class="bi bi-ruler me-1"></i>Product Dimensions & Scale Graph (Parsed or Custom)</h6>
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label small fw-semibold">Height (cm)</label>
                                    <input type="number" class="form-control form-control-sm" name="height_cm" value="<?= htmlspecialchars($extracted_data['height_cm'] ?? '') ?>" placeholder="e.g. 85">
                                    <div class="form-text small">Leave empty if no size available.</div>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-semibold">Width (cm)</label>
                                    <input type="number" class="form-control form-control-sm" name="width_cm" value="<?= htmlspecialchars($extracted_data['width_cm'] ?? '') ?>" placeholder="e.g. 100">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-semibold">Length / Depth (cm)</label>
                                    <input type="number" class="form-control form-control-sm" name="length_cm" value="<?= htmlspecialchars($extracted_data['length_cm'] ?? '') ?>" placeholder="e.g. 240">
                                </div>
                            </div>
                        </div>

                        <!-- BESPOKE CUSTOM DETAILS SECTION -->
                        <div class="card bg-light border-0 p-3 mb-3">
                            <h6 class="fw-bold text-dark mb-2"><i class="bi bi-file-earmark-text me-1"></i>Bespoke Custom Details & Shipping Specifications</h6>
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label small fw-semibold">Construction Details</label>
                                    <input type="text" class="form-control form-control-sm" name="detail_construction" value="Engineered for luxury durability & silent ergonomic comfort.">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-semibold">Care Instructions</label>
                                    <input type="text" class="form-control form-control-sm" name="detail_care" value="Wipe clean with a soft dry cloth. Avoid abrasive cleaners.">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-semibold">Shipping Specifications</label>
                                    <input type="text" class="form-control form-control-sm" name="detail_shipping" value="White-glove doorstep delivery and inside setup included.">
                                </div>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-success btn-lg w-100 fw-bold">
                            <i class="bi bi-check-circle me-2"></i> Save & Add Product to OXO Catalog
                        </button>
                    </form>
                </div>
            <?php endif; ?>

            <!-- RESULT DISPLAY -->
            <?php if ($imported_product): ?>
                <div class="card p-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="badge bg-success">Import Complete</span>
                        <small class="text-muted">ID: <?= htmlspecialchars($imported_product['id']) ?></small>
                    </div>

                    <div class="row">
                        <div class="col-md-4">
                            <img src="../<?= htmlspecialchars($imported_product['image']) ?>" class="product-img-preview mb-3" alt="Product Image">
                        </div>
                        <div class="col-md-8">
                            <h4 class="fw-bold text-dark mb-2"><?= htmlspecialchars($imported_product['title']) ?></h4>
                            <div class="fs-4 fw-bold text-success mb-2">₹<?= number_format($imported_product['price']) ?></div>
                            <p class="text-muted small mb-3"><?= htmlspecialchars($imported_product['description']) ?></p>

                            <?php if (!empty($imported_product['gallery_arr'])): ?>
                                <h6 class="fw-bold mb-2">Downloaded Gallery (<?= count($imported_product['gallery_arr']) ?> photos):</h6>
                                <div class="d-flex flex-wrap mb-3">
                                    <?php foreach ($imported_product['gallery_arr'] as $g_item): 
                                        $g_path = is_array($g_item) ? $g_item['path'] : $g_item;
                                    ?>
                                        <img src="../<?= htmlspecialchars($g_path) ?>" class="gallery-thumb" alt="Gallery Photo">
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>

                            <div class="d-flex gap-2">
                                <a href="../product.php?id=<?= urlencode($imported_product['id']) ?>" target="_blank" class="btn btn-outline-primary flex-fill fw-semibold">
                                    <i class="bi bi-eye me-1"></i> View Live on Site
                                </a>
                                <a href="product-editor.php?id=<?= urlencode($imported_product['id']) ?>" class="btn btn-outline-dark flex-fill fw-semibold">
                                    <i class="bi bi-pencil me-1"></i> Edit in Admin Console
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- TAB 2: Bulk Brand Catalog Sync -->
        <div class="tab-pane fade" id="tab-bulk">
            <div class="card p-4">
                <h5 class="fw-bold mb-2"><i class="bi bi-layers text-primary me-2"></i>Automated Bulk Brand Catalog Sync</h5>
                <p class="text-muted small mb-4">Sync an entire brand catalog page-by-page. Enter any brand domain (e.g., <code>nilkamalfurniture.com</code>, <code>applecart.co.in</code>, <code>supremefurniture.co.in</code>).</p>

                <div class="row g-3 align-items-center mb-3">
                    <div class="col-md-8">
                        <label class="form-label fw-bold">Brand Store Domain</label>
                        <input type="text" id="bulk-domain-input" class="form-control" value="nilkamalfurniture.com" placeholder="e.g. nilkamalfurniture.com or applecart.co.in">
                    </div>
                    <div class="col-md-4 d-flex gap-2 align-self-end">
                        <button id="start-bulk-btn" class="btn btn-custom flex-fill" onclick="startBulkImport()">
                            <i class="bi bi-play-fill me-1"></i> Start Bulk Sync
                        </button>
                        <button id="stop-bulk-btn" class="btn btn-danger" onclick="stopBulkImport()" disabled>
                            <i class="bi bi-stop-fill me-1"></i> Stop
                        </button>
                    </div>
                </div>

                <div class="progress mb-3" style="height: 25px;">
                    <div id="import-progress-bar" class="progress-bar progress-bar-striped progress-bar-animated bg-success" role="progressbar" style="width: 0%;">0 Products</div>
                </div>

                <h6 class="fw-bold mb-2">Live Progress Logs:</h6>
                <div id="log-box" class="log-box">
                    Ready. Enter domain and click "Start Bulk Sync" to begin...
                </div>
            </div>
        </div>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
let currentPage = 1;
let totalImported = 0;
let isRunning = false;

function log(msg) {
    const box = document.getElementById('log-box');
    const time = new Date().toLocaleTimeString();
    box.innerHTML += `[${time}] ${msg}\n`;
    box.scrollTop = box.scrollHeight;
}

async function startBulkImport() {
    const domain = document.getElementById('bulk-domain-input').value.trim() || 'nilkamalfurniture.com';
    isRunning = true;
    document.getElementById('start-bulk-btn').disabled = true;
    document.getElementById('stop-bulk-btn').disabled = false;
    log(`Starting bulk import for brand domain: ${domain}...`);

    while (isRunning) {
        log(`Fetching Page ${currentPage} for ${domain}...`);
        try {
            const response = await fetch(`import-universal.php?api=bulk_batch&domain=${encodeURIComponent(domain)}&page=${currentPage}`);
            const res = await response.json();

            if (res.status === 'complete' || res.imported_count === 0) {
                log(`Finished! All products, variant color swatches, and photos for ${domain} have been imported.`);
                break;
            }

            totalImported += res.imported_count;
            log(`Successfully imported ${res.imported_count} products from page ${currentPage}:`);
            res.titles.forEach(t => log(`   - ${t}`));

            const pb = document.getElementById('import-progress-bar');
            pb.style.width = '100%';
            pb.innerText = `${totalImported} Products Imported`;

            currentPage++;
        } catch (e) {
            log(`Error fetching page ${currentPage}: ${e.message}`);
            break;
        }
    }

    isRunning = false;
    document.getElementById('start-bulk-btn').disabled = false;
    document.getElementById('stop-bulk-btn').disabled = true;
}

function stopBulkImport() {
    isRunning = false;
    log("Bulk import stopped by user.");
    document.getElementById('start-bulk-btn').disabled = false;
    document.getElementById('stop-bulk-btn').disabled = true;
}
</script>
</body>
</html>
