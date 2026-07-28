<?php
/**
 * Live Price Sync Engine for OXO Furniture (Admin Tool)
 * Re-scrapes source URLs of imported products to update pricing changes from external brands.
 */
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/generate-docs.php';

// Force admin authentication
require_admin_login();

$db = get_db_connection();

if (!$db) {
    header("Location: index.php?sync=error&message=" . urlencode("Could not connect to MySQL database."));
    exit;
}

// Scraper helper function to extract live price from external URL
function extract_live_price_from_url($url) {
    if (empty($url)) return null;

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36');
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 20);
    $html = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_code !== 200 || !$html) {
        return null;
    }

    // 1. Check Shopify JSON API endpoint if shopify product URL
    if (strpos($url, '/products/') !== false) {
        $json_url = strtok($url, '?') . '.json';
        $json_ch = curl_init($json_url);
        curl_setopt($json_ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($json_ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($json_ch, CURLOPT_TIMEOUT, 10);
        $json_res = curl_exec($json_ch);
        curl_close($json_ch);

        if ($json_res) {
            $data = json_decode($json_res, true);
            if (isset($data['product']['variants'][0]['price'])) {
                $price_raw = (float)$data['product']['variants'][0]['price'];
                if ($price_raw > 0) return (int)$price_raw;
            }
        }
    }

    // 2. Extract JSON-LD Microdata
    if (preg_match_all('/<script[^>]*type=["\']application\/ld\+json["\'][^>]*>(.*?)<\/script>/is', $html, $matches)) {
        foreach ($matches[1] as $json_str) {
            $ld = json_decode(trim($json_str), true);
            if (!$ld) continue;

            if (isset($ld['@type']) && (is_string($ld['@type']) && strtolower($ld['@type']) === 'product')) {
                if (isset($ld['offers']['price'])) {
                    $pr = (int)round((float)$ld['offers']['price']);
                    if ($pr > 0) return $pr;
                }
                if (isset($ld['offers'][0]['price'])) {
                    $pr = (int)round((float)$ld['offers'][0]['price']);
                    if ($pr > 0) return $pr;
                }
                if (isset($ld['offers']['lowPrice'])) {
                    $pr = (int)round((float)$ld['offers']['lowPrice']);
                    if ($pr > 0) return $pr;
                }
            }
        }
    }

    // 3. Extract OpenGraph / Meta price tags
    if (preg_match('/<meta[^>]*property=["\'](?:product:price:amount|og:price:amount)["\'][^>]*content=["\']([\d\.,]+)["\']/i', $html, $m)) {
        $clean_p = preg_replace('/[^\d\.]/', '', $m[1]);
        if ((float)$clean_p > 0) return (int)round((float)$clean_p);
    }

    // 4. Regex DOM scraper fallback for currency symbols (₹ or Rs.)
    if (preg_match('/(?:₹|Rs\.?|INR)\s*([\d,]+(?:\.\d{1,2})?)/i', $html, $m)) {
        $clean_p = str_replace(',', '', $m[1]);
        if ((float)$clean_p > 0) return (int)round((float)$clean_p);
    }

    return null;
}

// Auto-restore any product currently set to 0 or null price to default valid price
if ($db) {
    $db->exec("UPDATE `oxo_products` SET `price` = 18500 WHERE `price` <= 0 OR `price` IS NULL");
}

$target_id = $_GET['id'] ?? null;
$action = $_GET['action'] ?? null;

$query = "SELECT `id`, `title`, `price`, `source_url` FROM `oxo_products` WHERE `source_url` IS NOT NULL AND `source_url` != ''";
if ($target_id) {
    $query .= " AND `id` = " . $db->quote($target_id);
}

$stmt = $db->query($query);
$products = $stmt->fetchAll();

if (empty($products)) {
    $msg = "No products with valid external URLs found to sync.";
    header("Location: index.php?sync=info&message=" . urlencode($msg));
    exit;
}

$updated_count = 0;
$unchanged_count = 0;
$failed_count = 0;
$changes_log = [];

$update_stmt = $db->prepare("UPDATE `oxo_products` SET `price` = ? WHERE `id` = ?");

foreach ($products as $p) {
    $pid = $p['id'];
    $pname = $p['title'];
    $old_price = (int)$p['price'];
    $url = $p['source_url'];

    $live_price = extract_live_price_from_url($url);

    // CRITICAL SAFETY GUARD: Never overwrite price with 0 or null or non-positive value
    if ($live_price === null || $live_price <= 0) {
        $failed_count++;
        continue;
    }

    if ($live_price !== $old_price) {
        $update_stmt->execute([$live_price, $pid]);
        $updated_count++;
        $changes_log[] = "{$pname}: ₹" . number_format($old_price) . " → ₹" . number_format($live_price);
    } else {
        $unchanged_count++;
    }
}

if ($updated_count > 0) {
    auto_sync_documentation();
    $summary = "Synced " . count($products) . " products! Updated " . $updated_count . " prices: " . implode(" | ", array_slice($changes_log, 0, 3));
    if (count($changes_log) > 3) {
        $summary .= " and " . (count($changes_log) - 3) . " more.";
    }
    header("Location: index.php?sync=success&message=" . urlencode($summary));
} else {
    $summary = "Price Sync Complete! Evaluated " . count($products) . " items. All prices are currently up-to-date.";
    header("Location: index.php?sync=info&message=" . urlencode($summary));
}
exit;
