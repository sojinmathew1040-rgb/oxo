<?php
/**
 * Curated Landing Page Product Preview
 * Grouped into distinct luxury collections by category with direct shop redirect filters.
 */

// 1. Group all available creations by their category
$grouped_products = [];
foreach ($PRODUCTS_DB as $pid => $p) {
    $cat = $p['category'];
    if (!isset($grouped_products[$cat])) {
        $grouped_products[$cat] = [];
    }
    $grouped_products[$cat][] = $p;
}

// 2. Fetch categories (use DB dynamic list or fallbacks)
$categories_to_show = [];
if (isset($db) && $db) {
    try {
        $categories_to_show = $db->query("SELECT * FROM `oxo_categories` ORDER BY `name` ASC")->fetchAll();
    } catch (\Exception $e) {
        error_log("Failed to fetch categories: " . $e->getMessage());
    }
}
if (empty($categories_to_show)) {
    $categories_to_show = [
        ["slug" => "sofas", "name" => "Sofas"],
        ["slug" => "chairs", "name" => "Chairs"],
        ["slug" => "tables", "name" => "Tables"]
    ];
}

// 3. Define luxury background accent colors for each category section
$bg_colors = [
    'chairs'   => '#FAF9F6',
    'lighting' => 'rgba(200, 162, 118, 0.035)',
    'sofas'    => 'rgba(95, 173, 138, 0.03)',
    'tables'   => 'rgba(10, 46, 36, 0.02)',
    'storage'  => 'rgba(30, 40, 36, 0.015)'
];

// Helper to generate a soft, premium HSL pastel background tint for dynamic categories
if (!function_exists('get_dynamic_pastel_color')) {
    function get_dynamic_pastel_color($slug) {
        $hash = 0;
        for ($i = 0; $i < strlen($slug); $i++) {
            $hash = ord($slug[$i]) + (($hash << 5) - $hash);
        }
        $hue = abs($hash) % 360;
        return "hsl(" . $hue . ", 28%, 97%)";
    }
}
?>

<!-- Collections Preview Section -->
<div id="collections-container">
    
    <!-- SECTION 0: Curated All Products (At the Top) -->
    <section id="collection-all" class="collection-landing-section" style="padding: 90px 0; background: #ffffff;">
        <div class="container">
            <div class="products-header" style="margin-bottom: 45px; display: flex; justify-content: space-between; align-items: center; width: 100%; gap: 15px;">
                <div style="max-width: 70%;">
                    <span class="section-tag" style="text-transform: uppercase; font-size: 0.72rem; letter-spacing: 2px; font-weight: 700; color: var(--color-accent); display: block; margin-bottom: 8px;">
                        Our Collection
                    </span>
                    <h2 class="title-medium" style="margin: 0; font-family: var(--font-title); font-size: 2rem; color: var(--color-primary); font-weight: 700;">
                        Curated <span class="title-serif">Creations</span>
                    </h2>
                </div>
                <div class="explore-catalog-cta" style="margin: 0 !important; width: auto !important; display: inline-flex !important; justify-content: flex-end !important; flex-shrink: 0;">
                    <a href="shop.php" class="magnetic-btn secondary static-btn" style="border-radius: 30px; padding: 10px 20px; font-size: 0.78rem; border-color: rgba(10, 46, 36, 0.15); display: inline-flex; align-items: center; gap: 8px; background: #ffffff;">
                        <span class="magnetic-btn-text">Explore Full Collection &nbsp; <i class="fa-solid fa-arrow-right-long"></i></span>
                    </a>
                </div>
            </div>
            
            <div class="product-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 30px; width: 100%;">
                <?php 
                $all_count = 0;
                foreach ($PRODUCTS_DB as $p) {
                    if ($all_count >= 4) break;
                    $all_count++;
                ?>
                    <div class="product-card" data-category="<?php echo htmlspecialchars($p['category']); ?>" data-id="<?php echo htmlspecialchars($p['id']); ?>">
                        <div class="product-image-container">
                            <img src="<?php echo htmlspecialchars($p['image']); ?>" alt="<?php echo htmlspecialchars($p['title']); ?>" loading="lazy">
                            <div class="product-actions">
                                <button class="product-action-btn" data-action="quick-view" data-id="<?php echo htmlspecialchars($p['id']); ?>" aria-label="Quick View">
                                    <i class="fa-regular fa-eye"></i>
                                </button>
                            </div>
                        </div>
                        <div class="product-info">
                            <span class="product-category"><?php echo htmlspecialchars(ucfirst($p['category'])); ?></span>
                            <h3 class="product-title">
                                <a href="product.php?id=<?php echo htmlspecialchars($p['id']); ?>" style="color: inherit; text-decoration: none;">
                                    <?php echo htmlspecialchars($p['title']); ?>
                                </a>
                            </h3>
                            <span class="product-price"><?php echo format_inr($p['price']); ?></span>
                        </div>
                    </div>
                <?php } ?>
            </div>
        </div>
    </section>

    <!-- Category Sections -->
    <?php 
    foreach ($categories_to_show as $cat): 
        $cat_slug = $cat['slug'];
        $cat_name = $cat['name'];
        $cat_products = isset($grouped_products[$cat_slug]) ? $grouped_products[$cat_slug] : [];
        if (empty($cat_products)) continue; // skip if no products in this category
        
        // Limit to 4 creations per category section on landing page
        $cat_products_limit = array_slice($cat_products, 0, 4);
        
        // Pick corresponding background color from DB, static list, or generate dynamically
        if (!empty($cat['bg_color'])) {
            $section_bg = $cat['bg_color'];
        } elseif (isset($bg_colors[$cat_slug])) {
            $section_bg = $bg_colors[$cat_slug];
        } else {
            $section_bg = get_dynamic_pastel_color($cat_slug);
        }
    ?>
        <section id="collection-<?php echo htmlspecialchars($cat_slug); ?>" class="collection-landing-section" style="padding: 90px 0; background: <?php echo $section_bg; ?>; border-top: 1px solid rgba(10, 46, 36, 0.05);">
            <div class="container">
                
                <!-- Section Header with top-right action button -->
                <div class="products-header" style="margin-bottom: 45px; display: flex; justify-content: space-between; align-items: center; width: 100%; gap: 15px;">
                    <div style="max-width: 70%;">
                        <span class="section-tag" style="text-transform: uppercase; font-size: 0.72rem; letter-spacing: 2px; font-weight: 700; color: var(--color-accent); display: block; margin-bottom: 8px;">
                            <?php echo htmlspecialchars($cat_name); ?> Collection
                        </span>
                        <h2 class="title-medium" style="margin: 0; font-family: var(--font-title); font-size: 2rem; color: var(--color-primary); font-weight: 700;">
                            Curated <span class="title-serif"><?php echo htmlspecialchars($cat_name); ?></span>
                        </h2>
                    </div>
                    <div class="explore-catalog-cta" style="margin: 0 !important; width: auto !important; display: inline-flex !important; justify-content: flex-end !important; flex-shrink: 0;">
                        <a href="shop.php?category=<?php echo urlencode($cat_slug); ?>" class="magnetic-btn secondary static-btn" style="border-radius: 30px; padding: 10px 20px; font-size: 0.78rem; border-color: rgba(10, 46, 36, 0.15); display: inline-flex; align-items: center; gap: 8px; background: #ffffff;">
                            <span class="magnetic-btn-text">View All <?php echo htmlspecialchars($cat_name); ?> &nbsp; <i class="fa-solid fa-arrow-right-long"></i></span>
                        </a>
                    </div>
                </div>
                
                <!-- Curated Category Products Grid -->
                <div class="product-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 30px; width: 100%;">
                    <?php foreach ($cat_products_limit as $p): ?>
                        <div class="product-card" data-category="<?php echo htmlspecialchars($p['category']); ?>" data-id="<?php echo htmlspecialchars($p['id']); ?>">
                            <div class="product-image-container">
                                <img src="<?php echo htmlspecialchars($p['image']); ?>" alt="<?php echo htmlspecialchars($p['title']); ?>" loading="lazy">
                                <div class="product-actions">
                                    <button class="product-action-btn" data-action="quick-view" data-id="<?php echo htmlspecialchars($p['id']); ?>" aria-label="Quick View">
                                        <i class="fa-regular fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="product-info">
                                <span class="product-category"><?php echo htmlspecialchars(ucfirst($p['category'])); ?></span>
                                <h3 class="product-title">
                                    <a href="product.php?id=<?php echo htmlspecialchars($p['id']); ?>" style="color: inherit; text-decoration: none;">
                                        <?php echo htmlspecialchars($p['title']); ?>
                                    </a>
                                </h3>
                                <span class="product-price"><?php echo format_inr($p['price']); ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
            </div>
        </section>
    <?php endforeach; ?>
</div>
