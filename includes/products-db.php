<?php
/**
 * Centralized Product Database for OXO Furniture
 */

require_once __DIR__ . '/db.php';

$PRODUCTS_DB = [];
$db = get_db_connection();

if ($db) {
    try {
        $stmt = $db->query("SELECT * FROM `oxo_products` ORDER BY `created_at` DESC");
        // Fetch Nilkamal brand ID for fallback mapping
        $nk_brand_id = null;
        try {
            $nk_stmt = $db->query("SELECT `id` FROM `oxo_brands` WHERE LOWER(`name`) LIKE '%nilkamal%' LIMIT 1");
            $nk_brand_id = $nk_stmt->fetchColumn();
        } catch (\Exception $e) {}

        $db_products = $stmt->fetchAll();
        foreach ($db_products as $p) {
            // Parse dimensions from specs as fallback if defaults or missing
            $h = isset($p['height_cm']) ? (int)$p['height_cm'] : 85;
            $w = isset($p['width_cm']) ? (int)$p['width_cm'] : 100;
            $l = isset($p['length_cm']) ? (int)$p['length_cm'] : 240;
            
            if ($h === 85 && $w === 100 && $l === 240) {
                if (preg_match('/W:\s*(\d+)cm/i', $p['specs'], $m)) {
                    $w = (int)$m[1];
                }
                if (preg_match('/(?:D|L):\s*(\d+)cm/i', $p['specs'], $m)) {
                    $l = (int)$m[1];
                }
                if (preg_match('/H:\s*(\d+)cm/i', $p['specs'], $m)) {
                    $h = (int)$m[1];
                }
                if (preg_match('/Dia:\s*(\d+)cm/i', $p['specs'], $m)) {
                    $w = (int)$m[1];
                    $l = (int)$m[1];
                }
            }

            // Auto-heal missing brand_id in-memory
            $b_id = isset($p['brand_id']) && $p['brand_id'] !== '' && $p['brand_id'] !== null ? (int)$p['brand_id'] : null;

            // Auto-heal misclassified TV Unit category in-memory
            $cat_val = $p['category'];
            if (($cat_val === 'chairs' || empty($cat_val)) && (preg_match('/tv\s*unit|tv\s*stand|tv\s*cabinet|media\s*unit/i', $p['title'] . ' ' . $p['id']))) {
                $cat_val = 'tv-units';
            }

            $PRODUCTS_DB[$p['id']] = [
                "id" => $p['id'],
                "title" => $p['title'],
                "price" => (int)$p['price'],
                "category" => $cat_val,
                "image" => $p['image'],
                "description" => $p['description'],
                "specs" => $p['specs'],
                "details" => json_decode($p['details'], true),
                "material_slug" => isset($p['material_slug']) ? $p['material_slug'] : 'wood',
                "brand_id" => $b_id,
                "gallery" => isset($p['gallery']) ? $p['gallery'] : '',
                "height_cm" => $h,
                "width_cm" => $w,
                "length_cm" => $l,
                "color_id" => isset($p['color_id']) && $p['color_id'] !== '' ? (int)$p['color_id'] : null,
                "color_ids" => isset($p['color_ids']) ? $p['color_ids'] : null
            ];
        }
    } catch (\Exception $e) {
        error_log("Failed to load products from database: " . $e->getMessage());
    }
}

// Fallback to static array if database query is empty or failed
if (empty($PRODUCTS_DB)) {
    $PRODUCTS_DB = [
        "sofa-1" => [
            "id" => "sofa-1",
            "title" => "Nirvana Modular Sofa",
            "price" => 185000,
            "category" => "sofas",
            "image" => "assets/images/sofa_1.png",
            "description" => "A plush, deep-seated modular sofa wrapped in premium performance linen. Experience ultimate luxury, tailored scale, and custom modular configurations.",
            "specs" => "Dimensions: W: 240cm x D: 100cm x H: 85cm | Frame: Kiln-dried hardwood | Fill: High-density foam & down feathers",
            "details" => [
                "Material" => "Performance textured linen (80% polyester, 20% linen), stain-resistant and durable.",
                "Construction" => "Double-doweled kiln-dried birch wood frame with pocket coil spring suspension.",
                "Care Instructions" => "Professional upholstery cleaning recommended. Vacuum weekly using a soft brush attachment.",
                "Shipping" => "Delivered in 3 modular sections. Free white-glove inside delivery & assembly within 7-10 business days."
            ]
        ],
        "chair-1" => [
            "id" => "chair-1",
            "title" => "Aurelia Accent Chair",
            "price" => 85000,
            "category" => "chairs",
            "image" => "assets/images/chair_1.png",
            "description" => "A sculptural masterpiece featuring a curved silhouette and cozy boucle upholstery, anchored securely by solid smoked oak wood legs.",
            "specs" => "Dimensions: W: 85cm x D: 80cm x H: 75cm | Fabric: Premium Boucle | Legs: Solid Smoked Oak",
            "details" => [
                "Material" => "Luxury heavyweight boucle yarn (75% acrylic, 25% wool) with soft tactile texture.",
                "Construction" => "Contoured steel inner frame padded with multi-density ergonomic foam.",
                "Care Instructions" => "Blot spills immediately with a clean, dry white cloth. Do not rub.",
                "Shipping" => "Fully assembled. Ships in custom reinforced wooden crate. Delivered in 3-5 business days."
            ]
        ],
        "chair-2" => [
            "id" => "chair-2",
            "title" => "Vesper Lounge Chair",
            "price" => 95000,
            "category" => "chairs",
            "image" => "assets/images/chair_2.png",
            "description" => "A sleek combination of hand-woven premium saddle leather and a matte black powder-coated steel frame. A perfect minimalist statement.",
            "specs" => "Dimensions: W: 70cm x D: 75cm x H: 80cm | Material: Top-grain Italian Leather | Frame: Alloy Steel",
            "details" => [
                "Material" => "4mm thick vegetable-tanned, top-grain Italian saddle leather strap weave.",
                "Construction" => "TIG-welded seamless steel pipe frame with satin-black powder-coated finish.",
                "Care Instructions" => "Treat with high-quality leather conditioner twice a year. Keep away from direct sunlight.",
                "Shipping" => "Fully assembled. Free threshold delivery. Ships in 4-6 business days."
            ]
        ],
        "table-1" => [
            "id" => "table-1",
            "title" => "Zephyr Dining Table",
            "price" => 240000,
            "category" => "tables",
            "image" => "assets/images/table_1.png",
            "description" => "A monolithic dining table crafted from solid Calacatta marble. Featuring soft chamfered edges and cylindrical fluted pedestals.",
            "specs" => "Dimensions: L: 200cm x W: 100cm x H: 75cm | Top: Italian Calacatta Marble | Base: Marble fluting",
            "details" => [
                "Material" => "Genuine Italian Calacatta Oro marble top with honed matte finish, pre-sealed.",
                "Construction" => "Fibre-reinforced concrete structural inner core clad in natural fluted marble tiles.",
                "Care Instructions" => "Always use coasters. Wipe with warm water and neutral pH stone soap. Avoid acidic cleaners.",
                "Shipping" => "Extremely heavy (280kg). Ships in 2 crates. Requires white-glove setup (included) in 12-15 business days."
            ]
        ],
        "table-2" => [
            "id" => "table-2",
            "title" => "Helios Coffee Table",
            "price" => 120000,
            "category" => "tables",
            "image" => "assets/images/table_2.png",
            "description" => "A circular, low-profile coffee table combining a dark green travertine top with brushed brass metal accent trim.",
            "specs" => "Dimensions: Dia: 90cm x H: 38cm | Stone: Green Travertine | Detailing: Solid Brushed Brass",
            "details" => [
                "Material" => "Iranian Forest Green travertine with natural cavities left unfilled for organic textures.",
                "Construction" => "Solid travertine base with thick solid brass inlay ring detail.",
                "Care Instructions" => "Clean with dry microfiber cloth. Promptly clean liquids (especially wine/coffee) to prevent staining.",
                "Shipping" => "Crated delivery. Requires basic assembly (attaching top to base). Ships in 5-7 business days."
            ]
        ],
        "light-1" => [
            "id" => "light-1",
            "title" => "Eclipse Pendant Lamp",
            "price" => 45000,
            "category" => "lighting",
            "image" => "assets/images/light_1.png",
            "description" => "A floating light sculpture utilizing integrated LED panels and brushed brass discs to cast soft, ambient indirect glows.",
            "specs" => "Dimensions: Dia: 60cm | Cord length: 150cm (adjustable) | Source: Integrated LED 3000K | Finish: Brass",
            "details" => [
                "Material" => "Solid spun brass shade plates, clear diffuser, and steel canopy matching wire.",
                "Illumination" => "24W integrated warm LED ring (1800 lumens, 3000K soft warm white), dimmable.",
                "Care Instructions" => "Dust with a dry static feather duster. Always switch off electricity before cleaning.",
                "Shipping" => "Ships with mounting brackets, ceiling anchor, and driver. Delivered in 2-4 business days."
            ]
        ],
        "light-2" => [
            "id" => "light-2",
            "title" => "Solstice Floor Lamp",
            "price" => 65000,
            "category" => "lighting",
            "image" => "assets/images/light_2.png",
            "description" => "An elegant floor lamp crafted from a solid green marble base and hand-blown frosted glass sphere diffuser.",
            "specs" => "Dimensions: H: 165cm x Dia: 35cm | Base: Verde Guatemala Marble | Glass: Mouth-blown opal glass",
            "details" => [
                "Material" => "Verde Guatemala marble base (honed), solid brass frame stem, hand-blown acid-etched glass globe.",
                "Illumination" => "E27 bulb socket (10W warm dimmable LED bulb included). Foot switch on cloth-wrapped cord.",
                "Care Instructions" => "Wipe globe with damp glass cleaner cloth. Clean base with stone sealer friendly wipes.",
                "Shipping" => "Delivered in two packages (globe + base assembly). Ships in 3-5 business days."
            ]
        ],
        "storage-1" => [
            "id" => "storage-1",
            "title" => "Krypton Sideboard",
            "price" => 170000,
            "category" => "storage",
            "image" => "assets/images/storage_1.png",
            "description" => "A premium walnut cabinet featuring three soft-close cabinet doors with three-dimensional ridged detailing.",
            "specs" => "Dimensions: W: 180cm x D: 45cm x H: 75cm | Material: American Walnut | Hardware: Blum soft-close hinges",
            "details" => [
                "Material" => "Selected FSC American Walnut solid edge banding and premium natural walnut veneer faces.",
                "Construction" => "Three internal height-adjustable shelves, wire grommet access, and adjustable leveling feet.",
                "Care Instructions" => "Wipe with furniture polish or damp cloth following natural wood grain patterns.",
                "Shipping" => "Fully assembled except for feet alignment. White glove room-of-choice placement included. 7-10 days."
            ]
        ]
    ];
}

if (!function_exists('format_inr')) {
    function format_inr($amount) {
        $amount = (int)$amount;
        $negative = $amount < 0 ? '-' : '';
        $amount = abs($amount);
        
        $num = (string)$amount;
        
        if (strlen($num) > 3) {
            $last_three = substr($num, -3);
            $remaining = substr($num, 0, -3);
            $remaining = preg_replace("/\B(?=(\d{2})+(?!\d))/", ",", $remaining);
            $result = $remaining . ',' . $last_three;
        } else {
            $result = $num;
        }
        
        return '₹' . $negative . $result;
    }
}

// Ensure all products in PRODUCTS_DB have parsed dimensions (for fallbacks and default DB records)
foreach ($PRODUCTS_DB as $id => &$product) {
    if (!isset($product['height_cm']) || !isset($product['width_cm']) || !isset($product['length_cm']) || ($product['height_cm'] === 85 && $product['width_cm'] === 100 && $product['length_cm'] === 240)) {
        $h = isset($product['height_cm']) ? (int)$product['height_cm'] : 85;
        $w = isset($product['width_cm']) ? (int)$product['width_cm'] : 100;
        $l = isset($product['length_cm']) ? (int)$product['length_cm'] : 240;
        
        if (isset($product['specs']) && !empty($product['specs'])) {
            if (preg_match('/W:\s*(\d+)cm/i', $product['specs'], $m)) {
                $w = (int)$m[1];
            }
            if (preg_match('/(?:D|L):\s*(\d+)cm/i', $product['specs'], $m)) {
                $l = (int)$m[1];
            }
            if (preg_match('/H:\s*(\d+)cm/i', $product['specs'], $m)) {
                $h = (int)$m[1];
            }
            if (preg_match('/Dia:\s*(\d+)cm/i', $product['specs'], $m)) {
                $w = (int)$m[1];
                $l = (int)$m[1];
            }
        }
        
        $product['height_cm'] = $h;
        $product['width_cm'] = $w;
        $product['length_cm'] = $l;
    }
}
unset($product);
?>

