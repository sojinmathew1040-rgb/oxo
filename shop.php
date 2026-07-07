<?php
/**
 * OXO Premium Furniture Store
 * Dedicated Catalog (Shop) Page
 */

// 1. Layout Header & CDNs
require_once __DIR__ . '/includes/header.php';
?>

<!-- Scroll Container for Lenis -->
<main id="scroll-container">

    <!-- Catalog Section -->
    <section class="shop-catalog-section" id="products">
        <div class="container">
            <div class="shop-catalog-header">
                <div>
                    <span class="section-tag">Explore All</span>
                    <h1 class="title-medium">The <span class="title-serif">Catalog</span></h1>
                    <p class="shop-subtitle">Discover curated luxury creations, crafted for silent elegance and premium spaces.</p>
                </div>
                
                <div class="category-filters">
                    <button class="filter-btn active" data-filter="all">All</button>
                    <button class="filter-btn" data-filter="sofas">Sofas</button>
                    <button class="filter-btn" data-filter="chairs">Chairs</button>
                    <button class="filter-btn" data-filter="tables">Tables</button>
                    <button class="filter-btn" data-filter="lighting">Lighting</button>
                    <button class="filter-btn" data-filter="storage">Storage</button>
                </div>
            </div>
            
            <div class="product-grid">
                <?php 
                    foreach ($PRODUCTS_DB as $pid => $p) {
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
                            <span class="product-price"><?php echo format_inr($p['price']); ?></span>
                        </div>
                    </div>
                <?php } ?>
            </div>
        </div>
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

<?php
// Layout Footer & Core Scripts
require_once __DIR__ . '/includes/footer.php';
?>
