<?php
/**
 * Curated Landing Page Product Preview
 */
?>
<!-- Products Section -->
<section id="products">
    <div class="container">
        <div class="products-header" style="margin-bottom: 50px;">
            <div>
                <span class="section-tag">Our Collection</span>
                <h2 class="title-medium">Curated <span class="title-serif">Creations</span></h2>
            </div>
        </div>
        
        <div class="product-grid">
            <?php 
                $count = 0;
                foreach ($PRODUCTS_DB as $pid => $p) {
                    if ($count >= 4) break;
                    $count++;
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
        
        <!-- Explore Catalog CTA Button -->
        <div class="explore-catalog-cta">
            <a href="shop.php" class="magnetic-btn secondary">
                <span class="magnetic-btn-text">Explore Full Collection &nbsp; <i class="fa-solid fa-arrow-right-long"></i></span>
            </a>
        </div>
    </div>
</section>
