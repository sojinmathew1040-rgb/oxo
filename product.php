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
            <?php else: ?>
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
                        <div class="gallery-main-container" id="gallery-zoom-box">
                            <img src="<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['title']); ?>" id="gallery-main-img" class="zoom-image">
                            <div class="zoom-indicator"><i class="fa-solid fa-magnifying-glass-plus"></i> Hover to zoom</div>
                        </div>
                        
                        <div class="gallery-thumbnails">
                            <button class="thumbnail-btn active" data-view="full" aria-label="View Full Design">
                                <img src="<?php echo htmlspecialchars($product['image']); ?>" alt="Full Design View">
                                <span class="thumb-label">Studio</span>
                            </button>
                            <button class="thumbnail-btn" data-view="detail" aria-label="View Textile Detail">
                                <img src="<?php echo htmlspecialchars($product['image']); ?>" alt="Textile Detail View" class="thumb-detail-render">
                                <span class="thumb-label">Detail</span>
                            </button>
                            <button class="thumbnail-btn" data-view="shadow" aria-label="View High-Contrast Shadow">
                                <img src="<?php echo htmlspecialchars($product['image']); ?>" alt="High-Contrast Shadow View" class="thumb-shadow-render">
                                <span class="thumb-label">Contour</span>
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
                        </div>

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
                <input type="text" placeholder="Your Full Name" required class="consult-input">
            </div>
            <div class="form-input-group">
                <input type="email" placeholder="Your Email Address" required class="consult-input">
            </div>
            <div class="form-input-group">
                <textarea placeholder="Tell us about your space or questions about <?php echo htmlspecialchars($product['title'] ?? ''); ?>..." required class="consult-input" rows="4"></textarea>
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

    // Gallery Zoom Effect
    const zoomBox = document.getElementById('gallery-zoom-box');
    const zoomImg = document.getElementById('gallery-main-img');
    
    if (zoomBox && zoomImg) {
        zoomBox.addEventListener('mousemove', (e) => {
            const { left, top, width, height } = zoomBox.getBoundingClientRect();
            const x = ((e.clientX - left) / width) * 100;
            const y = ((e.clientY - top) / height) * 100;
            
            zoomImg.style.transformOrigin = `${x}% ${y}%`;
            zoomImg.style.transform = 'scale(1.5)';
        });
        
        zoomBox.addEventListener('mouseleave', () => {
            zoomImg.style.transform = 'scale(1)';
            zoomImg.style.transformOrigin = 'center center';
        });
    }

    // Gallery View Switcher
    const thumbBtns = document.querySelectorAll('.thumbnail-btn');
    if (thumbBtns.length > 0 && zoomImg) {
        thumbBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                thumbBtns.forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                
                const view = btn.getAttribute('data-view');
                
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
            });
        });
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
                consultForm.style.display = 'none';
                if (consultSuccess) {
                    consultSuccess.classList.add('active');
                }
            });
        }
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
