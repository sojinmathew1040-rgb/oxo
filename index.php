<?php
/**
 * OXO Premium Furniture Store
 * Main Landing Page Router
 */

// 1. Layout Header & CDNs
require_once __DIR__ . '/includes/header.php';

?>

<!-- Scroll Container for Lenis -->
<main id="scroll-container">

    <!-- 3. Hero Section (Background Video) -->
    <?php require_once __DIR__ . '/components/hero.php'; ?>

    <!-- Partner Brands Infinite Marquee -->
    <?php require_once __DIR__ . '/components/brands-marquee.php'; ?>

    <!-- 4. Product Grid Catalog Preview -->
    <?php require_once __DIR__ . '/components/products-landing.php'; ?>

    <!-- 5. About / Philosophy Parallax Section -->
    <?php require_once __DIR__ . '/components/about.php'; ?>

    <!-- 6. Interactive Contact Forms -->
    <?php require_once __DIR__ . '/components/contact.php'; ?>

</main>

<!-- Drawers Overlays -->
<div class="drawer-overlay" id="drawer-overlay"></div>

<!-- 7. Shopping Cart slide-out panel -->
<?php require_once __DIR__ . '/components/cart.php'; ?>

<!-- 8. Wishlist slide-out panel -->
<?php require_once __DIR__ . '/components/wishlist.php'; ?>

<!-- 9. Product Quick View modal popup -->
<?php require_once __DIR__ . '/components/product-detail.php'; ?>

<?php
// 10. Layout Footer & Core Scripts
require_once __DIR__ . '/includes/footer.php';
?>
