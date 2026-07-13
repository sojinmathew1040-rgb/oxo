<?php
/**
 * OXO Premium Furniture Store
 * Standalone Product Details Page
 */

// 1. Load Header, DB and Page configurations
require_once __DIR__ . '/includes/header.php';

// Fetch the product ID
$product_id = isset($_GET['id']) ? trim($_GET['id']) : '';
$product = null;

if (!empty($product_id) && isset($PRODUCTS_DB[$product_id])) {
    $product = $PRODUCTS_DB[$product_id];
}

// Function to render scale graph SVG dynamically
function render_scale_graph($h, $w, $l) {
    // Normalise maximum size to 135px for canvas presentation
    $max_dim = max($h, $w, $l);
    if ($max_dim <= 0) $max_dim = 1;
    $scale = 135 / $max_dim;
    
    $sw_x = $w * $scale * 0.866;
    $sw_y = $w * $scale * 0.5;
    
    $sl_x = $l * $scale * 0.866;
    $sl_y = $l * $scale * 0.5;
    
    $sh_y = $h * $scale;
    
    // Base and top coordinates
    $p_bf = [200, 210];
    $p_bl = [200 - $sw_x, 210 + $sw_y];
    $p_br = [200 + $sl_x, 210 + $sl_y];
    $p_bb = [200 - $sw_x + $sl_x, 210 + $sw_y + $sl_y];
    
    $p_tf = [200, 210 - $sh_y];
    $p_tl = [200 - $sw_x, 210 + $sw_y - $sh_y];
    $p_tr = [200 + $sl_x, 210 + $sl_y - $sh_y];
    $p_tb = [200 - $sw_x + $sl_x, 210 + $sw_y + $sl_y - $sh_y];
    
    // Shift computation to center the drawing in the box (centered at y=215)
    $xs = [$p_bf[0], $p_bl[0], $p_br[0], $p_bb[0], $p_tf[0], $p_tl[0], $p_tr[0], $p_tb[0]];
    $ys = [$p_bf[1], $p_bl[1], $p_br[1], $p_bb[1], $p_tf[1], $p_tl[1], $p_tr[1], $p_tb[1]];
    
    $min_x = min($xs);
    $max_x = max($xs);
    $min_y = min($ys);
    $max_y = max($ys);
    
    $offset_x = 200 - ($min_x + $max_x) / 2;
    $offset_y = 215 - ($min_y + $max_y) / 2;
    
    $shift = function($p) use ($offset_x, $offset_y) {
        return [$p[0] + $offset_x, $p[1] + $offset_y];
    };
    
    $bf = $shift($p_bf);
    $bl = $shift($p_bl);
    $br = $shift($p_br);
    $bb = $shift($p_bb);
    $tf = $shift($p_tf);
    $tl = $shift($p_tl);
    $tr = $shift($p_tr);
    $tb = $shift($p_tb);

    $offset = 35; 
    $w_dx = $offset * 0.866; 
    $w_dy = $offset * 0.5;

    $bl_off = [$bl[0] - $w_dx, $bl[1] + $w_dy];
    $bf_off = [$bf[0] - $w_dx, $bf[1] + $w_dy];
    
    $bf_off2 = [$bf[0] + $w_dx, $bf[1] + $w_dy];
    $br_off = [$br[0] + $w_dx, $br[1] + $w_dy];

    $bl_off3 = [$bl[0] - 45, $bl[1]];
    $tl_off = [$tl[0] - 45, $tl[1]];

    $w_cx = ($bl_off[0] + $bf_off[0]) / 2;
    $w_cy = ($bl_off[1] + $bf_off[1]) / 2;

    $l_cx = ($bf_off2[0] + $br_off[0]) / 2;
    $l_cy = ($bf_off2[1] + $br_off[1]) / 2;

    $h_cx = ($bl_off3[0] + $tl_off[0]) / 2;
    $h_cy = ($bl_off3[1] + $tl_off[1]) / 2;
    
    ob_start();
    ?>
    <svg viewBox="0 0 400 460" width="100%" height="100%" xmlns="http://www.w3.org/2000/svg" style="background: transparent;">
        <defs>
            <marker id="arrow" viewBox="0 0 10 10" refX="5" refY="5" markerWidth="6" markerHeight="6" orient="auto-start-reverse">
                <path d="M 0 1.5 L 10 5 L 0 8.5 z" fill="#bf8f54" />
            </marker>
        </defs>

        <!-- Faint glassmorphic base footprint -->
        <polygon points="<?php echo "{$bl[0]},{$bl[1]} {$bf[0]},{$bf[1]} {$br[0]},{$br[1]} {$bb[0]},{$bb[1]}"; ?>" fill="var(--color-accent)" opacity="0.04" />
        
        <!-- Outer contours (Thin white-gold wireframe) -->
        <g stroke="var(--color-accent)" stroke-width="1.0" stroke-linejoin="round" fill="none" opacity="0.85">
            <!-- Front visible edges -->
            <line x1="<?php echo $bl[0]; ?>" y1="<?php echo $bl[1]; ?>" x2="<?php echo $bf[0]; ?>" y2="<?php echo $bf[1]; ?>" />
            <line x1="<?php echo $bf[0]; ?>" y1="<?php echo $bf[1]; ?>" x2="<?php echo $br[0]; ?>" y2="<?php echo $br[1]; ?>" />
            
            <!-- Hidden base edges -->
            <line x1="<?php echo $br[0]; ?>" y1="<?php echo $br[1]; ?>" x2="<?php echo $bb[0]; ?>" y2="<?php echo $bb[1]; ?>" stroke-dasharray="2,3" opacity="0.4" />
            <line x1="<?php echo $bb[0]; ?>" y1="<?php echo $bb[1]; ?>" x2="<?php echo $bl[0]; ?>" y2="<?php echo $bl[1]; ?>" stroke-dasharray="2,3" opacity="0.4" />
            
            <!-- Top edges -->
            <line x1="<?php echo $tl[0]; ?>" y1="<?php echo $tl[1]; ?>" x2="<?php echo $tf[0]; ?>" y2="<?php echo $tf[1]; ?>" />
            <line x1="<?php echo $tf[0]; ?>" y1="<?php echo $tf[1]; ?>" x2="<?php echo $tr[0]; ?>" y2="<?php echo $tr[1]; ?>" />
            <line x1="<?php echo $tr[0]; ?>" y1="<?php echo $tr[1]; ?>" x2="<?php echo $tb[0]; ?>" y2="<?php echo $tb[1]; ?>" stroke-dasharray="2,3" opacity="0.6" />
            <line x1="<?php echo $tb[0]; ?>" y1="<?php echo $tb[1]; ?>" x2="<?php echo $tl[0]; ?>" y2="<?php echo $tl[1]; ?>" stroke-dasharray="2,3" opacity="0.6" />
            
            <!-- Vertical pillars -->
            <line x1="<?php echo $bl[0]; ?>" y1="<?php echo $bl[1]; ?>" x2="<?php echo $tl[0]; ?>" y2="<?php echo $tl[1]; ?>" />
            <line x1="<?php echo $bf[0]; ?>" y1="<?php echo $bf[1]; ?>" x2="<?php echo $tf[0]; ?>" y2="<?php echo $tf[1]; ?>" />
            <line x1="<?php echo $br[0]; ?>" y1="<?php echo $br[1]; ?>" x2="<?php echo $tr[0]; ?>" y2="<?php echo $tr[1]; ?>" />
            <line x1="<?php echo $bb[0]; ?>" y1="<?php echo $bb[1]; ?>" x2="<?php echo $tb[0]; ?>" y2="<?php echo $tb[1]; ?>" stroke-dasharray="2,3" opacity="0.4" />
        </g>
        
        <!-- Annotations lines with dynamic arrowheads -->
        <g stroke="var(--color-accent)" stroke-width="0.8" fill="none" opacity="0.75">
            <!-- Width line offset -->
            <line x1="<?php echo $bl_off[0]; ?>" y1="<?php echo $bl_off[1]; ?>" x2="<?php echo $bf_off[0]; ?>" y2="<?php echo $bf_off[1]; ?>" marker-start="url(#arrow)" marker-end="url(#arrow)" />
            <line x1="<?php echo $bl[0]; ?>" y1="<?php echo $bl[1]; ?>" x2="<?php echo $bl_off[0]; ?>" y2="<?php echo $bl_off[1]; ?>" stroke-dasharray="1,2" />
            <line x1="<?php echo $bf[0]; ?>" y1="<?php echo $bf[1]; ?>" x2="<?php echo $bf_off[0]; ?>" y2="<?php echo $bf_off[1]; ?>" stroke-dasharray="1,2" />
            
            <!-- Length/Depth line offset -->
            <line x1="<?php echo $bf_off2[0]; ?>" y1="<?php echo $bf_off2[1]; ?>" x2="<?php echo $br_off[0]; ?>" y2="<?php echo $br_off[1]; ?>" marker-start="url(#arrow)" marker-end="url(#arrow)" />
            <line x1="<?php echo $bf[0]; ?>" y1="<?php echo $bf[1]; ?>" x2="<?php echo $bf_off2[0]; ?>" y2="<?php echo $bf_off2[1]; ?>" stroke-dasharray="1,2" />
            <line x1="<?php echo $br[0]; ?>" y1="<?php echo $br[1]; ?>" x2="<?php echo $br_off[0]; ?>" y2="<?php echo $br_off[1]; ?>" stroke-dasharray="1,2" />
            
            <!-- Height line offset -->
            <line x1="<?php echo $bl_off3[0]; ?>" y1="<?php echo $bl_off3[1]; ?>" x2="<?php echo $tl_off[0]; ?>" y2="<?php echo $tl_off[1]; ?>" marker-start="url(#arrow)" marker-end="url(#arrow)" />
            <line x1="<?php echo $bl[0]; ?>" y1="<?php echo $bl[1]; ?>" x2="<?php echo $bl_off3[0]; ?>" y2="<?php echo $bl_off3[1]; ?>" stroke-dasharray="1,2" />
            <line x1="<?php echo $tl[0]; ?>" y1="<?php echo $tl[1]; ?>" x2="<?php echo $tl_off[0]; ?>" y2="<?php echo $tl_off[1]; ?>" stroke-dasharray="1,2" />
        </g>
        
        <!-- Text labels with background pills -->
        <g font-family="sans-serif" font-size="7.5" font-weight="700" text-anchor="middle">
            <!-- Width -->
            <?php 
            $w_text = "W: {$w} cm";
            $w_w = strlen($w_text) * 5.2 + 10;
            ?>
            <rect x="<?php echo $w_cx - $w_w/2; ?>" y="<?php echo $w_cy - 7; ?>" width="<?php echo $w_w; ?>" height="14" rx="3" fill="#111111" stroke="var(--color-accent)" stroke-width="0.8" />
            <text x="<?php echo $w_cx; ?>" y="<?php echo $w_cy + 3; ?>" fill="#FAF9F6"><?php echo htmlspecialchars($w_text); ?></text>

            <!-- Length -->
            <?php 
            $l_text = "L: {$l} cm";
            $l_w = strlen($l_text) * 5.2 + 10;
            ?>
            <rect x="<?php echo $l_cx - $l_w/2; ?>" y="<?php echo $l_cy - 7; ?>" width="<?php echo $l_w; ?>" height="14" rx="3" fill="#111111" stroke="var(--color-accent)" stroke-width="0.8" />
            <text x="<?php echo $l_cx; ?>" y="<?php echo $l_cy + 3; ?>" fill="#FAF9F6"><?php echo htmlspecialchars($l_text); ?></text>

            <!-- Height -->
            <?php 
            $h_text = "H: {$h} cm";
            $h_w = strlen($h_text) * 5.2 + 10;
            ?>
            <rect x="<?php echo $h_cx - $h_w/2; ?>" y="<?php echo $h_cy - 7; ?>" width="<?php echo $h_w; ?>" height="14" rx="3" fill="#111111" stroke="var(--color-accent)" stroke-width="0.8" />
            <text x="<?php echo $h_cx; ?>" y="<?php echo $h_cy + 3; ?>" fill="#FAF9F6"><?php echo htmlspecialchars($h_text); ?></text>
        </g>
        
        <!-- Visual Node markers -->
        <circle cx="<?php echo $bf[0]; ?>" cy="<?php echo $bf[1]; ?>" r="2" fill="#FAF9F6" stroke="var(--color-accent)" stroke-width="0.8" />
        <circle cx="<?php echo $bl[0]; ?>" cy="<?php echo $bl[1]; ?>" r="2" fill="#FAF9F6" stroke="var(--color-accent)" stroke-width="0.8" />
        <circle cx="<?php echo $br[0]; ?>" cy="<?php echo $br[1]; ?>" r="2" fill="#FAF9F6" stroke="var(--color-accent)" stroke-width="0.8" />
        <circle cx="<?php echo $tf[0]; ?>" cy="<?php echo $tf[1]; ?>" r="2" fill="#FAF9F6" stroke="var(--color-accent)" stroke-width="0.8" />
        <circle cx="<?php echo $tl[0]; ?>" cy="<?php echo $tl[1]; ?>" r="2" fill="#FAF9F6" stroke="var(--color-accent)" stroke-width="0.8" />
        <circle cx="<?php echo $tr[0]; ?>" cy="<?php echo $tr[1]; ?>" r="2" fill="#FAF9F6" stroke="var(--color-accent)" stroke-width="0.8" />
    </svg>
    <?php
    return ob_get_clean();
}
?>

<main id="scroll-container">
    <section class="single-product-section">
        <div class="container">
            <?php if (!$product): ?>
                <div class="product-error-container">
                    <span class="section-tag">Error</span>
                    <h2 class="title-medium">Product <span class="title-serif">Not Found</span></h2>
                    <p>The premium creation you are looking for does not exist or has been archived.</p>
                    <a href="shop.php" class="magnetic-btn back-btn" style="margin-top: 30px;">
                        <span class="magnetic-btn-text"><i class="fa-solid fa-arrow-left"></i> Return to Catalog</span>
                    </a>
                </div>
            <?php else: 
                $gallery_images = [];
                if (!empty($product['gallery'])) {
                    $decoded = json_decode($product['gallery'], true);
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
                
                // Fetch associated colors
                $associated_color_ids = [];
                if (isset($product['color_ids']) && !empty($product['color_ids'])) {
                    $decoded = json_decode($product['color_ids'], true);
                    if (is_array($decoded)) {
                        $associated_color_ids = array_map('intval', $decoded);
                    }
                }
                if (empty($associated_color_ids)) {
                    if (!empty($product['color_id'])) {
                        $associated_color_ids[] = (int)$product['color_id'];
                    }
                    foreach ($gallery_images as $gimg) {
                        if (!empty($gimg['color_id'])) {
                            $associated_color_ids[] = (int)$gimg['color_id'];
                        }
                    }
                    $associated_color_ids = array_unique($associated_color_ids);
                }
                
                $associated_colors = [];
                if (!empty($associated_color_ids) && $db) {
                    $placeholders = implode(',', array_fill(0, count($associated_color_ids), '?'));
                    try {
                        $c_stmt = $db->prepare("SELECT * FROM `oxo_colors` WHERE `id` IN ($placeholders)");
                        $c_stmt->execute($associated_color_ids);
                        $associated_colors = $c_stmt->fetchAll();
                    } catch (\Exception $e) {
                        error_log("Failed to load associated colors: " . $e->getMessage());
                    }
                }
                ?>
                <!-- Breadcrumbs -->
                <div class="breadcrumbs">
                    <a href="index.php">Home</a>
                    <span class="breadcrumb-separator"><i class="fa-solid fa-chevron-right"></i></span>
                    <a href="shop.php">Collections</a>
                    <span class="breadcrumb-separator"><i class="fa-solid fa-chevron-right"></i></span>
                    <span class="breadcrumb-current"><?php echo htmlspecialchars($product['title']); ?></span>
                </div>

                <div class="single-product-grid">
                    <!-- Left: Premium Zoom-capable Image Gallery -->
                    <div class="product-gallery">
                        <div class="gallery-main-container" id="gallery-zoom-box" style="position: relative;">
                            <img src="<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['title']); ?>" id="gallery-main-img" class="zoom-image" style="transition: opacity 0.3s ease;">
                            
                            <!-- Dimensions Scale Graph container -->
                            <div id="gallery-scale-container" style="display: none; position: absolute; top: 0; left: 0; width: 100%; height: 100%; align-items: center; justify-content: center; background: #faf9f6; border-radius: 8px; pointer-events: none; z-index: 5; border: 1px solid rgba(191, 143, 84, 0.15);">
                                <?php 
                                $h_val = isset($product['height_cm']) ? (int)$product['height_cm'] : 85;
                                $w_val = isset($product['width_cm']) ? (int)$product['width_cm'] : 100;
                                $l_val = isset($product['length_cm']) ? (int)$product['length_cm'] : 240;
                                echo render_scale_graph($h_val, $w_val, $l_val);
                                ?>
                            </div>
                            <div class="zoom-indicator" id="gallery-zoom-indicator"><i class="fa-solid fa-magnifying-glass-plus"></i> Hover to zoom</div>
                        </div>
                        
                        <div class="gallery-thumbnails">
                            <!-- Main Studio image -->
                            <button class="thumbnail-btn active" data-view="full" data-src="<?php echo htmlspecialchars($product['image']); ?>" data-color="<?php echo !empty($product['color_id']) ? $product['color_id'] : ''; ?>" aria-label="View Full Design">
                                <img src="<?php echo htmlspecialchars($product['image']); ?>" alt="Full Design View">
                                <span class="thumb-label">Studio</span>
                            </button>

                            <!-- Dynamic Gallery Images -->
                            <?php 
                            if (!empty($gallery_images)):
                                $img_idx = 1;
                                foreach ($gallery_images as $gimg): 
                                    $gpath = $gimg['path'];
                                    $gcolor = $gimg['color_id'];
                                    if (!empty($gpath)): ?>
                                        <button class="thumbnail-btn" data-view="gallery" data-src="<?php echo htmlspecialchars($gpath); ?>" data-color="<?php echo htmlspecialchars((string)$gcolor); ?>" aria-label="View Gallery <?php echo $img_idx; ?>">
                                            <img src="<?php echo htmlspecialchars($gpath); ?>" alt="Gallery View <?php echo $img_idx; ?>">
                                            <span class="thumb-label">View <?php echo $img_idx; ?></span>
                                        </button>
                                    <?php 
                                    $img_idx++;
                                    endif;
                                endforeach;
                            endif; 
                            ?>



                            <!-- Dimensions blueprint -->
                            <button class="thumbnail-btn" data-view="scale" aria-label="View Dimensions Scale Graph">
                                <div style="display: flex; flex-direction: column; align-items: center; justify-content: center; height: 100%; background: var(--color-bg-panel); border-radius: 4px; border: 1px solid var(--color-panel-border); font-size: 1.1rem; color: var(--color-accent);">
                                    <i class="fa-solid fa-ruler-combined"></i>
                                </div>
                                <span class="thumb-label">Scale Graph</span>
                            </button>
                        </div>
                    </div>

                    <!-- Right: Info Panel -->
                    <div class="product-detail-panel">
                        <div class="product-detail-meta-row">
                            <span class="product-detail-category"><?php echo htmlspecialchars($product['category']); ?></span>
                            <div class="product-rating-box">
                                <span class="rating-stars">
                                    <i class="fa-solid fa-star"></i>
                                    <i class="fa-solid fa-star"></i>
                                    <i class="fa-solid fa-star"></i>
                                    <i class="fa-solid fa-star"></i>
                                    <i class="fa-solid fa-star"></i>
                                </span>
                                <span class="rating-val">4.9</span>
                                <span class="rating-count">(42 Reviews)</span>
                            </div>
                        </div>
                        
                        <h1 class="product-detail-title title-medium"><?php echo htmlspecialchars($product['title']); ?></h1>
                        
                        <span class="product-detail-price">
                            <?php echo format_inr($product['price']); ?>
                        </span>
                        
                        <p class="product-detail-description"><?php echo htmlspecialchars($product['description']); ?></p>

                        <!-- Specifications Summary -->
                        <div class="specs-summary-box">
                            <span class="specs-label">Key Specifications</span>
                            <p class="specs-text"><?php echo htmlspecialchars($product['specs']); ?></p>
                            <?php if (!empty($product['color_id']) && $db): 
                                try {
                                    $c_stmt = $db->prepare("SELECT * FROM `oxo_colors` WHERE `id` = ?");
                                    $c_stmt->execute([$product['color_id']]);
                                    $p_color = $c_stmt->fetch();
                                    if ($p_color):
                                ?>
                                    <div style="display: flex; align-items: center; gap: 8px; margin-top: 10px; font-size: 0.88rem; color: var(--color-gray);">
                                        <span>Default Finish:</span>
                                        <span style="display: inline-block; width: 14px; height: 14px; border-radius: 50%; background-color: <?php echo htmlspecialchars($p_color['hex']); ?>; border: 1px solid var(--color-panel-border); vertical-align: middle; box-shadow: 0 1px 3px rgba(0,0,0,0.1);"></span>
                                        <span style="font-weight: 600; color: var(--color-primary);"><?php echo htmlspecialchars($p_color['name']); ?></span>
                                    </div>
                                <?php 
                                    endif;
                                } catch (\Exception $e) {
                                    error_log("Failed to load color metadata in storefront details: " . $e->getMessage());
                                }
                            endif; ?>
                        </div>

                        <?php if (!empty($associated_colors)): 
                            $first_color_name = isset($associated_colors[0]) ? $associated_colors[0]['name'] : '';
                        ?>
                        <!-- Color Finish Swatches Selection (Screenshot Style) -->
                        <div class="color-selection-container" style="margin: 25px 0; border-top: 1px solid var(--color-panel-border); border-bottom: 1px solid var(--color-panel-border); padding: 15px 0;">
                            <div style="font-family: var(--font-title); font-size: 0.82rem; font-weight: 700; color: var(--color-gray); letter-spacing: 0.8px; margin-bottom: 12px; text-transform: uppercase;">
                                COLOR: <span id="active-color-name" style="color: var(--color-primary); font-weight: 800;"><?php echo htmlspecialchars(strtoupper($first_color_name)); ?></span>
                            </div>
                            
                            <div style="display: flex; gap: 14px; align-items: center; flex-wrap: wrap;">
                                <?php foreach ($associated_colors as $acolor_idx => $acolor): 
                                    $is_first = ($acolor_idx === 0);
                                ?>
                                    <button type="button" class="color-swatch-btn <?php echo $is_first ? 'active' : ''; ?>" data-color-id="<?php echo $acolor['id']; ?>" title="<?php echo htmlspecialchars($acolor['name']); ?>" data-hex="<?php echo htmlspecialchars($acolor['hex']); ?>"
                                            style="width: 32px; height: 32px; border-radius: 50%; border: 1.5px solid <?php echo $is_first ? htmlspecialchars($acolor['hex']) : 'transparent'; ?>; background: transparent; display: flex; align-items: center; justify-content: center; padding: 0; cursor: pointer; transition: all 0.25s ease; outline: none;">
                                        <span style="width: 20px; height: 20px; border-radius: 50%; background-color: <?php echo htmlspecialchars($acolor['hex']); ?>; display: inline-block; box-shadow: inset 0 0 0 1px rgba(0,0,0,0.08); <?php if (strtolower($acolor['hex']) === '#ffffff') echo 'border: 1px solid var(--color-panel-border);'; ?>"></span>
                                    </button>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- Action Block -->
                        <div class="product-detail-action-block">
                            <div class="detail-qty-wrapper">
                                <span class="action-label">Quantity</span>
                                <div class="detail-qty-selector">
                                    <button type="button" class="qty-selector-btn" id="detail-qty-dec"><i class="fa-solid fa-minus"></i></button>
                                    <input type="text" id="detail-qty-val" value="1" readonly>
                                    <button type="button" class="qty-selector-btn" id="detail-qty-inc"><i class="fa-solid fa-plus"></i></button>
                                </div>
                            </div>

                            <div class="detail-actions-row">
                                <button class="magnetic-btn primary-action-btn" data-action="add-to-cart" data-id="<?php echo htmlspecialchars($product['id']); ?>">
                                    <span class="magnetic-btn-text">Add to Cart</span>
                                </button>
                                
                                <button class="wishlist-action-btn" data-action="add-to-wishlist" data-id="<?php echo htmlspecialchars($product['id']); ?>" aria-label="Add to Wishlist">
                                    <i class="fa-regular fa-heart"></i>
                                </button>
                                
                                <button class="concierge-action-btn magnetic" id="open-consultation-btn" aria-label="Speak to a Specialist">
                                    <span class="magnetic-btn-text"><i class="fa-regular fa-comments"></i> Inquire</span>
                                </button>
                            </div>
                        </div>

                        <!-- Luxury Trust Badges -->
                        <div class="product-trust-badges">
                            <div class="trust-badge-item">
                                <i class="fa-solid fa-truck-ramp-box"></i>
                                <div class="trust-text-box">
                                    <span class="trust-title">White-Glove Delivery</span>
                                    <span class="trust-desc">Free inside setup & assembly</span>
                                </div>
                            </div>
                            <div class="trust-badge-item">
                                <i class="fa-solid fa-award"></i>
                                <div class="trust-text-box">
                                    <span class="trust-title">10-Year Warranty</span>
                                    <span class="trust-desc">Guaranteed heirloom construction</span>
                                </div>
                            </div>
                            <div class="trust-badge-item">
                                <i class="fa-solid fa-leaf"></i>
                                <div class="trust-text-box">
                                    <span class="trust-title">Sustainably Crafted</span>
                                    <span class="trust-desc">FSC Certified natural hardwoods</span>
                                </div>
                            </div>
                            <div class="trust-badge-item">
                                <i class="fa-solid fa-shield-halved"></i>
                                <div class="trust-text-box">
                                    <span class="trust-title">Insured Transit</span>
                                    <span class="trust-desc">100% damage protection cover</span>
                                </div>
                            </div>
                        </div>

                        <!-- Accordion for detailed information -->
                        <div class="detail-accordions">
                            <?php if (isset($product['details']) && is_array($product['details'])): ?>
                                <?php $index = 0; foreach ($product['details'] as $title => $content): $index++; ?>
                                    <div class="accordion-item <?php echo $index === 1 ? 'active' : ''; ?>">
                                        <button class="accordion-header" aria-expanded="<?php echo $index === 1 ? 'true' : 'false'; ?>">
                                            <span><?php echo htmlspecialchars($title); ?></span>
                                            <span class="accordion-icon"><i class="fa-solid fa-plus"></i></span>
                                        </button>
                                        <div class="accordion-content">
                                            <p><?php echo htmlspecialchars($content); ?></p>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Related Products Section -->
                <div class="related-products-section">
                    <h3 class="related-title title-medium">Related <span class="title-serif">Creations</span></h3>
                    <div class="product-grid">
                        <?php 
                            // Select up to 3 related products
                            $related_count = 0;
                            foreach ($PRODUCTS_DB as $pid => $p) {
                                if ($pid === $product['id']) continue;
                                if ($related_count >= 3) break;
                                
                                // Prioritize same category first
                                if ($p['category'] === $product['category'] || $related_count < 2) {
                                    $related_count++;
                                    ?>
                                    <div class="product-card" data-category="<?php echo htmlspecialchars($p['category']); ?>" data-id="<?php echo htmlspecialchars($p['id']); ?>">
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
                                            <h3 class="product-title"><?php echo htmlspecialchars($p['title']); ?></h3>
                                            <span class="product-price">
                                                <?php echo format_inr($p['price']); ?>
                                            </span>
                                        </div>
                                    </div>
                                    <?php
                                }
                            }
                        ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </section>
</main>

<!-- Concierge Consultation Modal -->
<div class="consultation-modal-overlay" id="consultation-modal">
    <div class="consultation-modal-container">
        <button class="consultation-modal-close" id="consultation-close" aria-label="Close Modal"><i class="fa-solid fa-xmark"></i></button>
        <span class="section-tag" style="margin-bottom: 10px;">Bespoke Service</span>
        <h3 class="title-medium" style="font-size: 2rem; margin-bottom: 10px;">Design <span class="title-serif">Consultation</span></h3>
        <p style="opacity: 0.8; font-size: 0.95rem; margin-bottom: 25px; line-height: 1.5;">
            Inquire about this creation, request material fabric swatches, or plan a curated space with our interior designers.
        </p>
        
        <form class="consultation-form" id="consultation-form">
            <input type="hidden" name="product_title" value="<?php echo htmlspecialchars($product['title'] ?? ''); ?>">
            <div class="form-input-group">
                <input type="text" name="name" placeholder="Your Full Name" required class="consult-input">
            </div>
            <div class="form-input-group">
                <input type="email" name="email" placeholder="Your Email Address" required class="consult-input">
            </div>
            <div class="form-input-group">
                <input type="tel" name="whatsapp" placeholder="WhatsApp / Mobile Number" required class="consult-input">
            </div>
            <div class="form-input-group">
                <textarea name="message" placeholder="Tell us about your space or questions about <?php echo htmlspecialchars($product['title'] ?? ''); ?>..." required class="consult-input" rows="4"></textarea>
            </div>
            <button type="submit" class="magnetic-btn form-submit-btn" style="width: 100%; padding: 14px 28px; text-align: center; border-radius: 4px; background: var(--color-primary); color: var(--color-white); font-weight: 600;">
                <span class="magnetic-btn-text">Submit Request</span>
            </button>
        </form>
        
        <div class="consultation-success-message" id="consultation-success">
            <i class="fa-solid fa-circle-check success-icon"></i>
            <h4>Request Received</h4>
            <p>Our bespoke design consultants will reach out to you within 2 business hours via email. Thank you for choosing OXO.</p>
        </div>
    </div>
</div>

<!-- Fullscreen Luxury Lightbox Modal -->
<div id="gallery-lightbox" class="lightbox-modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(10, 46, 36, 0.97); backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px); z-index: 10000; align-items: center; justify-content: center; opacity: 0; transition: opacity 0.35s cubic-bezier(0.25, 1, 0.5, 1);">
    <button id="lightbox-close" style="position: absolute; top: 30px; right: 40px; background: none; border: none; font-size: 2.2rem; color: var(--color-accent); cursor: pointer; transition: transform 0.3s; z-index: 10002;" aria-label="Close Gallery"><i class="fa-solid fa-xmark"></i></button>
    
    <button id="lightbox-prev" style="position: absolute; left: 40px; background: none; border: none; font-size: 2.5rem; color: var(--color-white); opacity: 0.6; cursor: pointer; transition: all 0.3s; z-index: 10002;" aria-label="Previous image"><i class="fa-solid fa-chevron-left"></i></button>
    <button id="lightbox-next" style="position: absolute; right: 40px; background: none; border: none; font-size: 2.5rem; color: var(--color-white); opacity: 0.6; cursor: pointer; transition: all 0.3s; z-index: 10002;" aria-label="Next image"><i class="fa-solid fa-chevron-right"></i></button>
    
    <div style="max-width: 80%; max-height: 80%; display: flex; flex-direction: column; align-items: center; justify-content: center; z-index: 10001;">
        <div style="overflow: hidden; border-radius: 8px; display: flex; align-items: center; justify-content: center;">
            <img id="lightbox-img" src="" alt="Expanded View" style="max-width: 100%; max-height: 70vh; object-fit: contain; border-radius: 8px; box-shadow: 0 30px 60px rgba(0,0,0,0.45); transition: transform 0.3s ease;">
        </div>
        <div id="lightbox-caption" style="color: var(--color-accent); font-family: var(--font-title); font-size: 1.15rem; margin-top: 25px; font-weight: 700; letter-spacing: 0.5px; text-transform: uppercase;"></div>
    </div>
</div>

<!-- Extra scripts for product details functionality (Accordion & Zoom) -->
<script>
document.addEventListener('DOMContentLoaded', () => {
    // Quantity Increment & Decrement
    const qtyVal = document.getElementById('detail-qty-val');
    const qtyInc = document.getElementById('detail-qty-inc');
    const qtyDec = document.getElementById('detail-qty-dec');
    
    if (qtyVal && qtyInc && qtyDec) {
        qtyInc.addEventListener('click', () => {
            let val = parseInt(qtyVal.value) || 1;
            qtyVal.value = val + 1;
        });
        
        qtyDec.addEventListener('click', () => {
            let val = parseInt(qtyVal.value) || 1;
            if (val > 1) {
                qtyVal.value = val - 1;
            }
        });
    }

    // Accordion Toggle Behavior
    const accordionHeaders = document.querySelectorAll('.accordion-header');
    accordionHeaders.forEach(header => {
        header.addEventListener('click', () => {
            const item = header.parentElement;
            const isActive = item.classList.contains('active');
            
            // Close all items
            document.querySelectorAll('.accordion-item').forEach(i => {
                i.classList.remove('active');
                i.querySelector('.accordion-header').setAttribute('aria-expanded', 'false');
            });
            
            // Toggle clicked item
            if (!isActive) {
                item.classList.add('active');
                header.setAttribute('aria-expanded', 'true');
            }
        });
    });

    // Gallery Zoom Effect (Magnifier calculates active view state)
    const zoomBox = document.getElementById('gallery-zoom-box');
    const zoomImg = document.getElementById('gallery-main-img');
    
    if (zoomBox && zoomImg) {
        zoomBox.addEventListener('mousemove', (e) => {
            const activeBtn = document.querySelector('.thumbnail-btn.active');
            const view = activeBtn ? activeBtn.getAttribute('data-view') : '';
            
            // Only perform image zoom if we're not in the Scale Graph blueprint tab
            if (view === 'scale') return;
            
            const { left, top, width, height } = zoomBox.getBoundingClientRect();
            const x = ((e.clientX - left) / width) * 100;
            const y = ((e.clientY - top) / height) * 100;
            
            zoomImg.style.transformOrigin = `${x}% ${y}%`;
            
            let scaleFactor = 1.5;
            if (view === 'detail') {
                scaleFactor = 2.2; // Deep detail zoom
            }
            zoomImg.style.transform = `scale(${scaleFactor})`;
        });
        
        zoomBox.addEventListener('mouseleave', () => {
            const activeBtn = document.querySelector('.thumbnail-btn.active');
            const view = activeBtn ? activeBtn.getAttribute('data-view') : '';
            
            zoomImg.style.transformOrigin = 'center center';
            if (view === 'detail') {
                zoomImg.style.transform = 'scale(1.4)';
            } else {
                zoomImg.style.transform = 'scale(1)';
            }
        });
    }

    // Gallery View Switcher
    const thumbBtns = document.querySelectorAll('.thumbnail-btn');
    const scaleContainer = document.getElementById('gallery-scale-container');
    const zoomIndicator = document.getElementById('gallery-zoom-indicator');
    
    if (thumbBtns.length > 0 && zoomImg) {
        thumbBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                thumbBtns.forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                
                const view = btn.getAttribute('data-view');
                const src = btn.getAttribute('data-src');
                
                // Hide/show the scale graph container vs normal zoomImg
                if (view === 'scale') {
                    zoomImg.style.display = 'none';
                    if (zoomIndicator) zoomIndicator.style.display = 'none';
                    if (scaleContainer) {
                        scaleContainer.style.display = 'flex';
                        if (typeof gsap !== 'undefined') {
                            gsap.fromTo(scaleContainer, { opacity: 0 }, { opacity: 1, duration: 0.5, ease: "power2.out" });
                        }
                    }
                } else {
                    if (scaleContainer) scaleContainer.style.display = 'none';
                    zoomImg.style.display = 'block';
                    if (zoomIndicator) zoomIndicator.style.display = 'block';
                    
                    if (src) {
                        zoomImg.src = src;
                    }
                    
                    // Apply specific classes to main image for CSS effect
                    zoomImg.className = 'zoom-image';
                    if (view === 'detail') {
                        zoomImg.classList.add('view-detail');
                    } else if (view === 'shadow') {
                        zoomImg.classList.add('view-shadow');
                    }
                    
                    // Smooth fade transition using GSAP
                    if (typeof gsap !== 'undefined') {
                        gsap.fromTo(zoomImg, { opacity: 0.4 }, { opacity: 1, duration: 0.5, ease: "power2.out" });
                    }
                }
            });
        });
    }

    // Color Swatch Switcher Filter
    const swatchBtns = document.querySelectorAll('.color-swatch-btn');
    const activeColorText = document.getElementById('active-color-name');
    
    if (swatchBtns.length > 0 && thumbBtns.length > 0) {
        swatchBtns.forEach(swatch => {
            swatch.addEventListener('click', () => {
                // Update active state class on swatches
                swatchBtns.forEach(s => {
                    s.classList.remove('active');
                    s.style.borderColor = 'transparent';
                });
                swatch.classList.add('active');
                const hex = swatch.getAttribute('data-hex') || 'var(--color-accent)';
                swatch.style.borderColor = hex;
                
                const colorId = swatch.getAttribute('data-color-id');
                const colorName = swatch.getAttribute('title');
                if (activeColorText) {
                    activeColorText.textContent = colorId === 'all' ? 'All Finishes' : colorName;
                }
                
                // Filter thumbnails
                let firstVisibleThumb = null;
                thumbBtns.forEach(btn => {
                    const btnColor = btn.getAttribute('data-color');
                    const btnView = btn.getAttribute('data-view');
                    
                    if (colorId === 'all') {
                        btn.style.display = 'flex';
                        if (!firstVisibleThumb && btnView !== 'scale') firstVisibleThumb = btn;
                    } else {
                        // Keep scale graph thumbnail visible, filter others by color
                        if (btnView === 'scale') {
                            btn.style.display = 'flex';
                        } else if (btnColor == colorId) {
                            btn.style.display = 'flex';
                            if (!firstVisibleThumb) firstVisibleThumb = btn;
                        } else {
                            btn.style.display = 'none';
                        }
                    }
                });
                
                // Auto click the first visible thumbnail to display it
                if (firstVisibleThumb) {
                    firstVisibleThumb.click();
                }
            });
        });
        
        // Auto-filter by the initially selected color swatch on page load
        const activeSwatch = document.querySelector('.color-swatch-btn.active');
        if (activeSwatch) {
            activeSwatch.click();
        }
    }

    // Consultation Modal Behavior
    const consultBtn = document.getElementById('open-consultation-btn');
    const consultModal = document.getElementById('consultation-modal');
    const consultClose = document.getElementById('consultation-close');
    const consultForm = document.getElementById('consultation-form');
    const consultSuccess = document.getElementById('consultation-success');
    
    if (consultBtn && consultModal && consultClose) {
        consultBtn.addEventListener('click', () => {
            consultModal.classList.add('active');
            if (window.lenis) window.lenis.stop();
        });
        
        const closeConsult = () => {
            consultModal.classList.remove('active');
            if (window.lenis) window.lenis.start();
            setTimeout(() => {
                if (consultForm && consultSuccess) {
                    consultForm.style.display = 'block';
                    consultSuccess.classList.remove('active');
                    consultForm.reset();
                }
            }, 500);
        };
        
        consultClose.addEventListener('click', closeConsult);
        consultModal.addEventListener('click', (e) => {
            if (e.target === consultModal) closeConsult();
        });
        
        if (consultForm) {
            consultForm.addEventListener('submit', (e) => {
                e.preventDefault();
                
                const formData = new FormData(consultForm);
                const submitBtn = consultForm.querySelector('.form-submit-btn');
                const btnText = submitBtn ? submitBtn.querySelector('.magnetic-btn-text') : null;
                
                if (submitBtn && btnText) {
                    submitBtn.disabled = true;
                    btnText.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Submitting...';
                }
                
                fetch('submit-inquiry.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        consultForm.style.display = 'none';
                        if (consultSuccess) {
                            consultSuccess.classList.add('active');
                        }
                    } else {
                        alert(data.error || 'Failed to submit inquiry.');
                        if (submitBtn && btnText) {
                            submitBtn.disabled = false;
                            btnText.innerText = 'Submit Request';
                        }
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    // Fallback to UX success
                    consultForm.style.display = 'none';
                    if (consultSuccess) {
                        consultSuccess.classList.add('active');
                    }
                });
            });
        }
    }

    // Lightbox Gallery Controller
    const lightbox = document.getElementById('gallery-lightbox');
    const lightboxImg = document.getElementById('lightbox-img');
    const lightboxCaption = document.getElementById('lightbox-caption');
    const lightboxClose = document.getElementById('lightbox-close');
    const lightboxPrev = document.getElementById('lightbox-prev');
    const lightboxNext = document.getElementById('lightbox-next');
    
    let lightboxSources = [];
    let currentLightboxIdx = 0;
    
    const validThumbnails = Array.from(document.querySelectorAll('.thumbnail-btn')).filter(btn => btn.getAttribute('data-view') !== 'scale');
    
    if (zoomBox && lightbox && lightboxImg) {
        zoomBox.addEventListener('click', () => {
            const currentActiveBtn = document.querySelector('.thumbnail-btn.active');
            if (currentActiveBtn && currentActiveBtn.getAttribute('data-view') === 'scale') {
                return; // Do not open lightbox on scale graph SVG
            }
            
            lightboxSources = validThumbnails.map(btn => ({
                src: btn.getAttribute('data-src') || (btn.querySelector('img') ? btn.querySelector('img').src : ''),
                label: btn.querySelector('.thumb-label') ? btn.querySelector('.thumb-label').textContent : '',
                view: btn.getAttribute('data-view')
            }));
            
            const currentSrc = zoomImg.src;
            const currentView = currentActiveBtn ? currentActiveBtn.getAttribute('data-view') : '';
            
            currentLightboxIdx = lightboxSources.findIndex(item => {
                if (currentView === 'detail' || currentView === 'shadow') {
                    return item.view === currentView;
                }
                return item.src === currentSrc;
            });
            
            if (currentLightboxIdx === -1) currentLightboxIdx = 0;
            
            openLightbox();
        });
    }
    
    function openLightbox() {
        if (!lightboxSources[currentLightboxIdx]) return;
        
        const item = lightboxSources[currentLightboxIdx];
        lightboxImg.src = item.src;
        
        // Apply special filters for detail/shadow view inside lightbox
        lightboxImg.className = '';
        if (item.view === 'detail') {
            lightboxImg.style.transform = 'scale(1.4)';
            lightboxImg.style.filter = '';
        } else if (item.view === 'shadow') {
            lightboxImg.style.transform = '';
            lightboxImg.style.filter = 'contrast(1.4) brightness(0.85) saturate(0.65)';
        } else {
            lightboxImg.style.transform = '';
            lightboxImg.style.filter = '';
        }
        
        if (lightboxCaption) {
            lightboxCaption.textContent = `<?php echo htmlspecialchars($product['title'] ?? ''); ?> — ${item.label}`;
        }
        
        lightbox.style.display = 'flex';
        setTimeout(() => {
            lightbox.style.opacity = '1';
        }, 10);
        
        if (window.lenis) window.lenis.stop();
    }
    
    function closeLightbox() {
        if (lightbox) {
            lightbox.style.opacity = '0';
            setTimeout(() => {
                lightbox.style.display = 'none';
            }, 400);
        }
        if (window.lenis) window.lenis.start();
    }
    
    if (lightboxClose) lightboxClose.addEventListener('click', closeLightbox);
    if (lightboxPrev) lightboxPrev.addEventListener('click', () => {
        currentLightboxIdx = (currentLightboxIdx - 1 + lightboxSources.length) % lightboxSources.length;
        openLightbox();
    });
    if (lightboxNext) lightboxNext.addEventListener('click', () => {
        currentLightboxIdx = (currentLightboxIdx + 1) % lightboxSources.length;
        openLightbox();
    });
    if (lightbox) {
        lightbox.addEventListener('click', (e) => {
            if (e.target === lightbox || e.target.id === 'gallery-lightbox') {
                closeLightbox();
            }
        });
    }

    // GSAP load stagger animations
    if (typeof gsap !== 'undefined') {
        const tl = gsap.timeline({ defaults: { ease: "power4.out" } });
        tl.from('.breadcrumbs', { opacity: 0, y: -15, duration: 1.2 })
          .from('.product-gallery', { opacity: 0, x: -30, duration: 1.4 }, "-=0.9")
          .from('.product-detail-panel > *', { 
              opacity: 0, 
              y: 20, 
              duration: 1.2, 
              stagger: 0.08 
          }, "-=1.1")
          .from('.related-products-section', { opacity: 0, y: 40, duration: 1.4 }, "-=1.0");
    }
});
</script>

<?php
// Load Footer
require_once __DIR__ . '/includes/footer.php';
?>
