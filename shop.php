<?php
/**
 * OXO Premium Furniture Store
 * Dedicated Catalog (Shop) Page
 */

// 1. Layout Header & CDNs
require_once __DIR__ . '/includes/header.php';

// Fetch dynamic filter groups from database
$categories = [];
$materials = [];
$colors_map = [];
$brands = [];
if (isset($db) && $db) {
    try {
        $categories = $db->query("SELECT * FROM `oxo_categories` ORDER BY `name` ASC")->fetchAll();
        $materials = $db->query("SELECT * FROM `oxo_materials` ORDER BY `name` ASC")->fetchAll();
        $brands = $db->query("SELECT * FROM `oxo_brands` ORDER BY `name` ASC")->fetchAll();
        
        $colors_all = $db->query("SELECT * FROM `oxo_colors`")->fetchAll();
        foreach ($colors_all as $c) {
            $colors_map[(int)$c['id']] = $c;
        }
    } catch (\Exception $e) {
        error_log("Failed to load filters in shop.php: " . $e->getMessage());
    }
}

// Robust fallback values if MySQL is offline
if (empty($categories)) {
    $categories = [
        ["slug" => "sofas", "name" => "Sofas"],
        ["slug" => "chairs", "name" => "Chairs"],
        ["slug" => "tables", "name" => "Tables"],
        ["slug" => "lighting", "name" => "Lighting"],
        ["slug" => "storage", "name" => "Storage"]
    ];
}
if (empty($materials)) {
    $materials = [
        ["slug" => "wood", "name" => "Solid Wood"],
        ["slug" => "metal", "name" => "Brushed Metal"],
        ["slug" => "glass", "name" => "Tempered Glass"],
        ["slug" => "fabric", "name" => "Organic Fabric"],
        ["slug" => "plastic", "name" => "Recycled Plastic"]
    ];
}
?>

<!-- Scroll Container for Lenis -->
<main id="scroll-container">

    <!-- Catalog Section -->
    <section class="shop-catalog-section" id="products">
        <div class="container">
            
            <style>
                .shop-layout {
                    display: flex;
                    gap: 40px;
                    align-items: start;
                    margin-top: 40px;
                }
                .shop-sidebar-filters {
                    flex: 0 0 280px;
                    width: 280px;
                    position: sticky;
                    top: 100px;
                    background: var(--color-bg-panel);
                    border: 1px solid var(--color-panel-border);
                    border-radius: 16px;
                    padding: 25px;
                    box-shadow: 0 4px 20px rgba(0,0,0,0.02);
                }
                .shop-sidebar-filters h4 {
                    font-size: 1.05rem;
                    font-weight: 700;
                    margin: 0;
                }
                .filter-group {
                    border-bottom: 1px solid var(--color-panel-border);
                    padding-bottom: 20px;
                    margin-bottom: 20px;
                }
                .filter-group:last-child {
                    border-bottom: none;
                    padding-bottom: 0;
                    margin-bottom: 0;
                }
                .filter-group h5 {
                    font-size: 0.72rem;
                    font-weight: 800;
                    text-transform: uppercase;
                    color: var(--color-gray);
                    letter-spacing: 1px;
                    margin-bottom: 12px;
                    margin-top: 0;
                }
                .filter-radio-label {
                    display: flex;
                    align-items: center;
                    gap: 10px;
                    font-size: 0.82rem;
                    font-weight: 600;
                    color: var(--color-primary);
                    cursor: pointer;
                    user-select: none;
                    margin-bottom: 8px;
                    transition: color 0.2s ease;
                }
                .filter-radio-label:hover {
                    color: var(--color-accent);
                }
                .filter-radio-label input[type="radio"] {
                    accent-color: var(--color-accent);
                    width: 15px;
                    height: 15px;
                    cursor: pointer;
                    margin: 0;
                }
                .sidebar-color-btn {
                    width: 28px;
                    height: 28px;
                    border-radius: 50%;
                    border: 1.5px solid transparent;
                    background: transparent;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    cursor: pointer;
                    transition: all 0.2s ease;
                    outline: none;
                    padding: 0;
                }
                .sidebar-color-btn:hover {
                    transform: scale(1.1);
                }
                .sidebar-color-btn.active {
                    box-shadow: 0 0 0 1px rgba(255,255,255,1);
                }
                
                @media (max-width: 992px) {
                    .shop-layout {
                        flex-direction: column !important;
                        gap: 30px !important;
                    }
                    .shop-sidebar-filters {
                        width: 100% !important;
                        flex: none !important;
                        position: relative !important;
                        top: 0 !important;
                    }
                }
            </style>

            <div class="shop-catalog-header" style="border-bottom: 1px solid rgba(10, 46, 36, 0.05); padding-bottom: 25px;">
                <span class="section-tag">Explore All</span>
                <h1 class="title-medium" style="margin: 5px 0 10px;">The <span class="title-serif">Catalog</span></h1>
                <p class="shop-subtitle">Discover curated luxury creations, crafted for silent elegance and premium spaces.</p>
            </div>

            <div class="shop-layout">
                <!-- Sidebar Filters -->
                <aside class="shop-sidebar-filters">
                    <!-- Header -->
                    <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--color-panel-border); padding-bottom: 15px; margin-bottom: 20px;">
                        <h4 style="font-family: var(--font-title); color: var(--color-primary); display: flex; align-items: center; gap: 8px;">
                            <i class="fa-solid fa-sliders" style="color: var(--color-accent); font-size: 0.95rem;"></i> Filter Option
                        </h4>
                        <button id="btn-clear-filters" style="font-size: 0.68rem; font-weight: 800; color: var(--color-accent); background: none; border: none; cursor: pointer; text-transform: uppercase; letter-spacing: 0.8px;">Clear All</button>
                    </div>

                    <!-- Category Filter -->
                    <div class="filter-group">
                        <h5>Category</h5>
                        <div style="display: flex; flex-direction: column;">
                            <label class="filter-radio-label">
                                <input type="radio" name="shop_category" value="all" checked>
                                <span>All Categories</span>
                            </label>
                            <?php foreach ($categories as $cat): ?>
                                <label class="filter-radio-label">
                                    <input type="radio" name="shop_category" value="<?php echo htmlspecialchars($cat['slug']); ?>">
                                    <span><?php echo htmlspecialchars($cat['name']); ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Material Filter -->
                    <div class="filter-group">
                        <h5>Material</h5>
                        <div style="display: flex; flex-direction: column;">
                            <label class="filter-radio-label">
                                <input type="radio" name="shop_material" value="all" checked>
                                <span>All Materials</span>
                            </label>
                            <?php foreach ($materials as $mat): ?>
                                <label class="filter-radio-label">
                                    <input type="radio" name="shop_material" value="<?php echo htmlspecialchars($mat['slug']); ?>">
                                    <span><?php echo htmlspecialchars($mat['name']); ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Color Finishes Filter -->
                    <div class="filter-group">
                        <h5>Finish / Color</h5>
                        <div style="display: flex; gap: 10px; flex-wrap: wrap; align-items: center; min-height: 32px;">
                            <button type="button" class="sidebar-color-btn active" data-color-id="all" title="All Colors" 
                                    style="border-color: var(--color-accent);">
                                <span style="width: 18px; height: 18px; border-radius: 50%; background: conic-gradient(red, yellow, lime, aqua, blue, magenta, red); display: inline-block;"></span>
                            </button>
                            <?php foreach ($colors_map as $cid => $cdata): ?>
                                <button type="button" class="sidebar-color-btn" data-color-id="<?php echo $cid; ?>" title="<?php echo htmlspecialchars($cdata['name']); ?>" data-hex="<?php echo htmlspecialchars($cdata['hex']); ?>">
                                    <span style="width: 18px; height: 18px; border-radius: 50%; background-color: <?php echo htmlspecialchars($cdata['hex']); ?>; display: inline-block; box-shadow: inset 0 0 0 1px rgba(0,0,0,0.08); <?php if (strtolower($cdata['hex']) === '#ffffff') echo 'border: 1px solid var(--color-panel-border);'; ?>"></span>
                                </button>
                            <?php endforeach; ?>
                        </div>
                        <div id="sidebar-color-label" style="font-size: 0.68rem; color: var(--color-gray); margin-top: 8px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;">All Colors</div>
                    </div>

                    <!-- Brand Filter -->
                    <?php if (!empty($brands)): ?>
                        <div class="filter-group">
                            <h5>Brand Partner</h5>
                            <div style="display: flex; flex-direction: column;">
                                <label class="filter-radio-label">
                                    <input type="radio" name="shop_brand" value="all" checked>
                                    <span>All Brands</span>
                                </label>
                                <?php foreach ($brands as $b): ?>
                                    <label class="filter-radio-label">
                                        <input type="radio" name="shop_brand" value="<?php echo htmlspecialchars($b['id']); ?>">
                                        <span><?php echo htmlspecialchars($b['name']); ?></span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Price Range Filter -->
                    <div class="filter-group" style="border-bottom: none; padding-bottom: 0; margin-bottom: 0;">
                        <h5>Price Bracket (Max)</h5>
                        <div style="display: flex; flex-direction: column; gap: 8px;">
                            <input type="range" id="price-range" min="5000" max="600000" step="5000" value="600000" style="width: 100%; accent-color: var(--color-accent); cursor: pointer;">
                            <div style="display: flex; justify-content: space-between; font-size: 0.8rem; font-weight: 700; color: var(--color-primary); font-family: var(--font-numeric);">
                                <span>₹5,000</span>
                                <span id="price-val" style="color: var(--color-accent);">₹6,00,000</span>
                            </div>
                        </div>
                    </div>
                </aside>

                <!-- Grid Section -->
                <div style="flex: 1;">
                    <div class="product-grid" style="margin-top: 0; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));">
                <?php 
                    foreach ($PRODUCTS_DB as $pid => $p) {
                        $p_mat = isset($p['material_slug']) ? $p['material_slug'] : 'wood';
                        
                        $gallery_images = [];
                        if (!empty($p['gallery'])) {
                            $decoded = json_decode($p['gallery'], true);
                            if (is_array($decoded)) {
                                foreach ($decoded as $item) {
                                    if (is_array($item)) {
                                        $gallery_images[] = [
                                            'path' => $item['path'],
                                            'color_id' => isset($item['color_id']) && $item['color_id'] !== '' ? (int)$item['color_id'] : null
                                        ];
                                    } else {
                                        $gallery_images[] = [
                                            'path' => $item,
                                            'color_id' => null
                                        ];
                                    }
                                }
                            }
                        }
                        
                        $card_color_ids = [];
                        if (!empty($p['color_id'])) {
                            $card_color_ids[] = (int)$p['color_id'];
                        }
                        foreach ($gallery_images as $gimg) {
                            if (!empty($gimg['color_id'])) {
                                $card_color_ids[] = (int)$gimg['color_id'];
                            }
                        }
                        $card_color_ids = array_unique($card_color_ids);
                        
                        // Parse list of color ids from the new JSON fieldcolor_ids if present
                        if (isset($p['color_ids']) && !empty($p['color_ids'])) {
                            $decoded_ids = json_decode($p['color_ids'], true);
                            if (is_array($decoded_ids)) {
                                foreach ($decoded_ids as $cid) {
                                    $card_color_ids[] = (int)$cid;
                                }
                                $card_color_ids = array_unique($card_color_ids);
                            }
                        }
                ?>
                    <div class="product-card" 
                         data-category="<?php echo htmlspecialchars($p['category']); ?>" 
                         data-material="<?php echo htmlspecialchars($p_mat); ?>" 
                         data-price="<?php echo (int)$p['price']; ?>"
                         data-brand="<?php echo htmlspecialchars((string)$p['brand_id']); ?>"
                         data-colors="<?php echo implode(',', $card_color_ids); ?>"
                         data-id="<?php echo htmlspecialchars($p['id']); ?>">
                        <div class="product-image-container">
                            <img src="<?php echo htmlspecialchars($p['image']); ?>" alt="<?php echo htmlspecialchars($p['title']); ?>" loading="lazy">
                            <div class="product-actions">
                                <button class="product-action-btn" data-action="quick-view" data-id="<?php echo htmlspecialchars($p['id']); ?>" aria-label="Quick View">
                                    <i class="fa-regular fa-eye"></i>
                                </button>
                                <button class="product-action-btn" data-action="add-to-wishlist" data-id="<?php echo htmlspecialchars($p['id']); ?>" aria-label="Add to Wishlist">
                                    <i class="fa-regular fa-heart"></i>
                                </button>
                                <button class="product-action-btn" data-action="add-to-cart" data-id="<?php echo htmlspecialchars($p['id']); ?>" aria-label="Add to Cart">
                                    <i class="fa-solid fa-cart-shopping"></i>
                                </button>
                            </div>
                        </div>
                        <div class="product-info">
                            <span class="product-category"><?php echo htmlspecialchars(ucfirst($p['category'])); ?></span>
                            <h3 class="product-title"><a href="product.php?id=<?php echo htmlspecialchars($p['id']); ?>" style="color: inherit; text-decoration: none;"><?php echo htmlspecialchars($p['title']); ?></a></h3>
                            <span class="product-price"><?php echo format_inr($p['price']); ?></span>
                            
                            <!-- Card Color Swatches (Premium Hover/Click Swap) -->
                            <?php 
                            if (!empty($card_color_ids)): ?>
                                <div class="product-card-colors" style="display: flex; gap: 8px; margin-top: 12px; align-items: center; min-height: 18px;">
                                    <?php foreach ($card_color_ids as $cid): 
                                        if (isset($colors_map[$cid])): 
                                            $cdata = $colors_map[$cid];
                                            $associated_img = $p['image'];
                                            if ((int)$p['color_id'] === $cid) {
                                                $associated_img = $p['image'];
                                            } else {
                                                foreach ($gallery_images as $gimg) {
                                                    if ($gimg['color_id'] === $cid) {
                                                        $associated_img = $gimg['path'];
                                                        break;
                                                    }
                                                }
                                            }
                                    ?>
                                        <span class="card-color-dot" 
                                              data-color-id="<?php echo $cid; ?>" 
                                              data-image="<?php echo htmlspecialchars($associated_img); ?>" 
                                              title="<?php echo htmlspecialchars($cdata['name']); ?>" 
                                              style="display: inline-block; width: 12px; height: 12px; border-radius: 50%; background-color: <?php echo htmlspecialchars($cdata['hex']); ?>; cursor: pointer; border: 1px solid rgba(0,0,0,0.1); box-shadow: inset 0 0 0 1px rgba(255,255,255,0.2); transition: all 0.2s ease;">
                                        </span>
                                    <?php 
                                        endif;
                                    endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php } ?>
                </div> <!-- Closes flex: 1 wrapper -->
            </div> <!-- Closes shop-layout -->
        </div> <!-- Closes container -->
    </section>

</main>

<!-- Drawers Overlays -->
<div class="drawer-overlay" id="drawer-overlay"></div>

<!-- Shopping Cart slide-out panel -->
<?php require_once __DIR__ . '/components/cart.php'; ?>

<!-- Wishlist slide-out panel -->
<?php require_once __DIR__ . '/components/wishlist.php'; ?>

<!-- Product Quick View modal popup -->
<?php require_once __DIR__ . '/components/product-detail.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', () => {
    // 1. Hover/Click Color Swatches on individual product cards
    const dots = document.querySelectorAll('.card-color-dot');
    dots.forEach(dot => {
        const updateImage = () => {
            const card = dot.closest('.product-card');
            const img = card ? card.querySelector('.product-image-container img') : null;
            const newSrc = dot.getAttribute('data-image');
            if (img && newSrc) {
                img.src = newSrc;
            }
            
            // Add visual active state border to active dot
            const parent = dot.parentElement;
            if (parent) {
                parent.querySelectorAll('.card-color-dot').forEach(d => {
                    d.style.transform = 'scale(1)';
                    d.style.boxShadow = 'inset 0 0 0 1px rgba(255,255,255,0.2)';
                    d.style.borderColor = 'rgba(0,0,0,0.1)';
                });
            }
            dot.style.transform = 'scale(1.35)';
            dot.style.boxShadow = '0 0 0 2px var(--color-accent)';
            dot.style.borderColor = 'transparent';
        };
        
        dot.addEventListener('mouseenter', updateImage);
        dot.addEventListener('click', (e) => {
            e.stopPropagation();
            updateImage();
        });
    });

    // 2. Sidebar Live Multi-Criteria Filtering
    const getCategory = () => {
        const selected = document.querySelector('input[name="shop_category"]:checked');
        return selected ? selected.value : 'all';
    };

    const getMaterial = () => {
        const selected = document.querySelector('input[name="shop_material"]:checked');
        return selected ? selected.value : 'all';
    };

    const getBrand = () => {
        const selected = document.querySelector('input[name="shop_brand"]:checked');
        return selected ? selected.value : 'all';
    };

    const getColor = () => {
        const activeColor = document.querySelector('.sidebar-color-btn.active');
        return activeColor ? activeColor.getAttribute('data-color-id') : 'all';
    };

    const getMaxPrice = () => {
        const range = document.getElementById('price-range');
        return range ? parseInt(range.value) : 600000;
    };

    function runShopFilter() {
        const cat = getCategory();
        const mat = getMaterial();
        const brand = getBrand();
        const color = getColor();
        const maxPrice = getMaxPrice();

        const productCards = document.querySelectorAll('.product-card');
        
        // Use GSAP animation timeline if available
        if (typeof gsap !== 'undefined') {
            gsap.to(productCards, {
                opacity: 0,
                scale: 0.95,
                duration: 0.25,
                ease: "power2.in",
                onComplete: () => {
                    productCards.forEach(card => {
                        const cardCat = card.getAttribute('data-category');
                        const cardMat = card.getAttribute('data-material') || '';
                        const cardPrice = parseInt(card.getAttribute('data-price')) || 0;
                        const cardBrand = card.getAttribute('data-brand') || '';
                        const cardColors = (card.getAttribute('data-colors') || '').split(',');

                        const catMatches = (cat === 'all' || cardCat === cat);
                        const matMatches = (mat === 'all' || cardMat === mat);
                        const priceMatches = (cardPrice <= maxPrice);
                        const brandMatches = (brand === 'all' || cardBrand === brand);
                        const colorMatches = (color === 'all' || cardColors.includes(color));

                        if (catMatches && matMatches && priceMatches && brandMatches && colorMatches) {
                            card.style.display = 'flex';
                        } else {
                            card.style.display = 'none';
                        }
                    });

                    const visibleCards = Array.from(productCards).filter(c => c.style.display === 'flex');
                    if (visibleCards.length > 0) {
                        gsap.to(visibleCards, {
                            opacity: 1,
                            scale: 1,
                            duration: 0.4,
                            stagger: 0.02,
                            ease: "power3.out"
                        });
                    }
                    if (typeof ScrollTrigger !== 'undefined') {
                        ScrollTrigger.refresh();
                    }
                }
            });
        } else {
            // Native fallback
            productCards.forEach(card => {
                const cardCat = card.getAttribute('data-category');
                const cardMat = card.getAttribute('data-material') || '';
                const cardPrice = parseInt(card.getAttribute('data-price')) || 0;
                const cardBrand = card.getAttribute('data-brand') || '';
                const cardColors = (card.getAttribute('data-colors') || '').split(',');

                const catMatches = (cat === 'all' || cardCat === cat);
                const matMatches = (mat === 'all' || cardMat === mat);
                const priceMatches = (cardPrice <= maxPrice);
                const brandMatches = (brand === 'all' || cardBrand === brand);
                const colorMatches = (color === 'all' || cardColors.includes(color));

                if (catMatches && matMatches && priceMatches && brandMatches && colorMatches) {
                    card.style.display = 'flex';
                } else {
                    card.style.display = 'none';
                }
            });
        }
    }

    // Bind listeners
    document.querySelectorAll('input[name="shop_category"]').forEach(r => r.addEventListener('change', runShopFilter));
    document.querySelectorAll('input[name="shop_material"]').forEach(r => r.addEventListener('change', runShopFilter));
    document.querySelectorAll('input[name="shop_brand"]').forEach(r => r.addEventListener('change', runShopFilter));

    const priceRange = document.getElementById('price-range');
    const priceVal = document.getElementById('price-val');
    if (priceRange) {
        priceRange.addEventListener('input', () => {
            const val = parseInt(priceRange.value);
            if (priceVal) {
                priceVal.textContent = '₹' + val.toLocaleString('en-IN');
            }
            runShopFilter();
        });
    }

    const sidebarColorBtns = document.querySelectorAll('.sidebar-color-btn');
    const sidebarColorLabel = document.getElementById('sidebar-color-label');
    sidebarColorBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            sidebarColorBtns.forEach(b => {
                b.classList.remove('active');
                b.style.borderColor = 'transparent';
            });
            btn.classList.add('active');
            const hex = btn.getAttribute('data-hex') || 'var(--color-accent)';
            btn.style.borderColor = hex;
            
            if (sidebarColorLabel) {
                sidebarColorLabel.textContent = btn.getAttribute('title') || 'All Colors';
            }
            runShopFilter();
        });
    });

    const btnClearAll = document.getElementById('btn-clear-filters');
    if (btnClearAll) {
        btnClearAll.addEventListener('click', () => {
            const catAll = document.querySelector('input[name="shop_category"][value="all"]');
            if (catAll) catAll.checked = true;
            
            const matAll = document.querySelector('input[name="shop_material"][value="all"]');
            if (matAll) matAll.checked = true;
            
            const brandAll = document.querySelector('input[name="shop_brand"][value="all"]');
            if (brandAll) brandAll.checked = true;
            
            if (priceRange) {
                priceRange.value = 600000;
                if (priceVal) priceVal.textContent = '₹6,00,000';
            }
            
            sidebarColorBtns.forEach(b => {
                b.classList.remove('active');
                b.style.borderColor = 'transparent';
            });
            const defaultColorBtn = document.querySelector('.sidebar-color-btn[data-color-id="all"]');
            if (defaultColorBtn) {
                defaultColorBtn.classList.add('active');
                defaultColorBtn.style.borderColor = 'var(--color-accent)';
            }
            if (sidebarColorLabel) {
                sidebarColorLabel.textContent = 'All Colors';
            }
            runShopFilter();
        });
    }
});
</script>

<?php
// Layout Footer & Core Scripts
require_once __DIR__ . '/includes/footer.php';
?>
