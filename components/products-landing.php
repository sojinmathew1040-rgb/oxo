<?php
/**
 * OXO Premium Furniture Store
 * Ultra-Luxury Landing Page Curated Collection Showcase
 * Stacked Category Sections Layout
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
        ["slug" => "sofas", "name" => "Modular Sofas"],
        ["slug" => "chairs", "name" => "Accent Chairs"],
        ["slug" => "tables", "name" => "Marble Tables"],
        ["slug" => "lighting", "name" => "Designer Lighting"]
    ];
}

// Background tints for category sections
$bg_colors = [
    'chairs'   => '#FAF9F6',
    'lighting' => 'rgba(200, 162, 118, 0.035)',
    'sofas'    => 'rgba(95, 173, 138, 0.03)',
    'tables'   => 'rgba(10, 46, 36, 0.02)',
    'storage'  => 'rgba(30, 40, 36, 0.015)'
];
?>

<!-- Collections Stacked Container -->
<div id="collections-container">

    <!-- SECTION 0: Curated Latest Creations (All Categories Overview) -->
    <section id="collection-all" class="collection-landing-section" style="padding: 100px 0; background: #ffffff;">
        <div class="container">
            <div class="products-header" style="margin-bottom: 45px; display: flex; justify-content: space-between; align-items: flex-end; width: 100%; gap: 20px; flex-wrap: wrap;">
                <div>
                    <span class="oxo-badge oxo-badge-accent" style="margin-bottom: 10px;">
                        <i class="fa-solid fa-sparkles" style="font-size: 0.65rem;"></i> Latest Release
                    </span>
                    <h2 style="margin: 0; font-family: var(--font-title); font-size: clamp(1.8rem, 5vw, 2.6rem); color: var(--color-primary); font-weight: 700; line-height: 1.15;">
                        Curated Creations
                    </h2>
                </div>
                <div>
                    <a href="shop.php" class="magnetic-btn" style="padding: 12px 26px; border-radius: 30px; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; border: 1px solid rgba(10, 46, 36, 0.15); color: var(--color-primary); font-weight: 700; font-size: 0.78rem; text-transform: uppercase; letter-spacing: 0.8px; background: #ffffff;">
                        <span class="magnetic-btn-text">Explore Full Collection &nbsp; <i class="fa-solid fa-arrow-right-long" style="color: var(--color-accent);"></i></span>
                    </a>
                </div>
            </div>

            <!-- Product Cards Grid -->
            <div class="product-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(285px, 1fr)); gap: 30px; width: 100%;">
                <?php 
                $all_count = 0;
                foreach ($PRODUCTS_DB as $p):
                    if ($all_count >= 4) break;
                    $all_count++;
                ?>
                    <div class="product-card" data-category="<?php echo htmlspecialchars($p['category']); ?>" data-id="<?php echo htmlspecialchars($p['id']); ?>">
                        <div class="product-image-container">
                            <img src="<?php echo htmlspecialchars($p['image']); ?>" alt="<?php echo htmlspecialchars($p['title']); ?>" loading="lazy" decoding="async">
                            
                            <div style="position: absolute; top: 14px; left: 14px;">
                                <span class="oxo-badge" style="background: rgba(255,255,255,0.92); backdrop-filter: blur(8px); font-size: 0.72rem; padding: 6px 12px; border-radius: 20px; font-weight: 700;">
                                    <?php echo htmlspecialchars(ucfirst($p['category'])); ?>
                                </span>
                            </div>

                            <div class="product-actions" style="position: absolute; bottom: 14px; right: 14px;">
                                <button class="product-action-btn" data-action="quick-view" data-id="<?php echo htmlspecialchars($p['id']); ?>" aria-label="Quick View" style="width: 46px; height: 46px; border-radius: 50%; background: #ffffff; color: var(--color-primary); border: none; box-shadow: 0 6px 20px rgba(0,0,0,0.15); cursor: pointer; display: flex; align-items: center; justify-content: center; transition: all 0.3s ease;">
                                    <i class="fa-regular fa-eye" style="font-size: 1.05rem;"></i>
                                </button>
                            </div>
                        </div>
                        
                        <div class="product-info">
                            <h3 class="product-title" style="margin: 6px 0 4px; font-size: 1.2rem; font-weight: 700; line-height: 1.3;">
                                <a href="product.php?id=<?php echo htmlspecialchars($p['id']); ?>" style="color: inherit; text-decoration: none;">
                                    <?php echo htmlspecialchars($p['title']); ?>
                                </a>
                            </h3>
                            <p style="font-size: 0.88rem; color: var(--color-gray); margin: 4px 0 0; line-height: 1.5; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                                <?php echo htmlspecialchars($p['description']); ?>
                            </p>
                        </div>

                        <div class="product-card-footer">
                            <span class="oxo-price-tag" style="font-size: 1.25rem; font-weight: 800;"><?php echo format_inr($p['price']); ?></span>
                            <a href="product.php?id=<?php echo htmlspecialchars($p['id']); ?>" style="display: inline-flex; align-items: center; gap: 8px; font-size: 0.84rem; font-weight: 700; color: var(--color-primary); text-transform: uppercase; letter-spacing: 0.8px;">
                                View Details <i class="fa-solid fa-arrow-right-long" style="font-size: 0.78rem; color: var(--color-accent);"></i>
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Stacked Category Sections -->
    <?php 
    foreach ($categories_to_show as $cat): 
        $cat_slug = $cat['slug'];
        $cat_name = $cat['name'];
        $cat_products = isset($grouped_products[$cat_slug]) ? $grouped_products[$cat_slug] : [];
        if (empty($cat_products)) continue;
        
        $cat_products_limit = array_slice($cat_products, 0, 4);
        $section_bg = isset($bg_colors[$cat_slug]) ? $bg_colors[$cat_slug] : '#FAF9F6';
    ?>
        <section id="collection-<?php echo htmlspecialchars($cat_slug); ?>" class="collection-landing-section" style="padding: 100px 0; background: <?php echo $section_bg; ?>; border-top: 1px solid rgba(10, 46, 36, 0.06);">
            <div class="container">
                
                <!-- Section Header -->
                <div class="products-header" style="margin-bottom: 45px; display: flex; justify-content: space-between; align-items: flex-end; width: 100%; gap: 20px; flex-wrap: wrap;">
                    <div>
                        <span class="oxo-badge" style="margin-bottom: 10px; background: rgba(10, 46, 36, 0.06);">
                            <?php echo htmlspecialchars($cat_name); ?>
                        </span>
                        <h2 style="margin: 0; font-family: var(--font-title); font-size: clamp(1.6rem, 4.5vw, 2.4rem); color: var(--color-primary); font-weight: 700; line-height: 1.15;">
                            Curated <?php echo htmlspecialchars($cat_name); ?>
                        </h2>
                    </div>
                    <div>
                        <a href="shop.php?category=<?php echo urlencode($cat_slug); ?>" class="magnetic-btn" style="padding: 12px 24px; border-radius: 30px; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; border: 1px solid rgba(10, 46, 36, 0.15); color: var(--color-primary); font-weight: 700; font-size: 0.78rem; text-transform: uppercase; letter-spacing: 0.8px; background: #ffffff;">
                            <span class="magnetic-btn-text">View All <?php echo htmlspecialchars($cat_name); ?> &nbsp; <i class="fa-solid fa-arrow-right-long" style="color: var(--color-accent);"></i></span>
                        </a>
                    </div>
                </div>

                <!-- 4 Product Grid for Category -->
                <div class="product-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(285px, 1fr)); gap: 30px; width: 100%;">
                    <?php foreach ($cat_products_limit as $p): ?>
                        <div class="product-card" data-category="<?php echo htmlspecialchars($p['category']); ?>" data-id="<?php echo htmlspecialchars($p['id']); ?>">
                            <div class="product-image-container">
                                <img src="<?php echo htmlspecialchars($p['image']); ?>" alt="<?php echo htmlspecialchars($p['title']); ?>" loading="lazy" decoding="async">
                                
                                <div style="position: absolute; top: 14px; left: 14px;">
                                    <span class="oxo-badge" style="background: rgba(255,255,255,0.92); backdrop-filter: blur(8px); font-size: 0.72rem; padding: 6px 12px; border-radius: 20px; font-weight: 700;">
                                        <?php echo htmlspecialchars(ucfirst($p['category'])); ?>
                                    </span>
                                </div>

                                <div class="product-actions" style="position: absolute; bottom: 14px; right: 14px;">
                                    <button class="product-action-btn" data-action="quick-view" data-id="<?php echo htmlspecialchars($p['id']); ?>" aria-label="Quick View" style="width: 46px; height: 46px; border-radius: 50%; background: #ffffff; color: var(--color-primary); border: none; box-shadow: 0 6px 20px rgba(0,0,0,0.15); cursor: pointer; display: flex; align-items: center; justify-content: center; transition: all 0.3s ease;">
                                        <i class="fa-regular fa-eye" style="font-size: 1.05rem;"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <div class="product-info">
                                <h3 class="product-title" style="margin: 6px 0 4px; font-size: 1.2rem; font-weight: 700; line-height: 1.3;">
                                    <a href="product.php?id=<?php echo htmlspecialchars($p['id']); ?>" style="color: inherit; text-decoration: none;">
                                        <?php echo htmlspecialchars($p['title']); ?>
                                    </a>
                                </h3>
                                <p style="font-size: 0.88rem; color: var(--color-gray); margin: 4px 0 0; line-height: 1.5; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                                    <?php echo htmlspecialchars($p['description']); ?>
                                </p>
                            </div>

                            <div class="product-card-footer">
                                <span class="oxo-price-tag" style="font-size: 1.25rem; font-weight: 800;"><?php echo format_inr($p['price']); ?></span>
                                <a href="product.php?id=<?php echo htmlspecialchars($p['id']); ?>" style="display: inline-flex; align-items: center; gap: 8px; font-size: 0.84rem; font-weight: 700; color: var(--color-primary); text-transform: uppercase; letter-spacing: 0.8px;">
                                    View Details <i class="fa-solid fa-arrow-right-long" style="font-size: 0.78rem; color: var(--color-accent);"></i>
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

            </div>
        </section>
    <?php endforeach; ?>

</div>
