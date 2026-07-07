<?php
require_once __DIR__ . '/products-db.php';

$current_script = basename($_SERVER['SCRIPT_NAME']);
$is_product_page = ($current_script === 'product.php');
$is_shop_page = ($current_script === 'shop.php');
$current_product = null;

if ($is_product_page && isset($_GET['id']) && isset($PRODUCTS_DB[$_GET['id']])) {
    $current_product = $PRODUCTS_DB[$_GET['id']];
}

$page_title = "OXO — Premium Furniture Store";
$page_desc = "Discover OXO, a premium high-end furniture store offering curated luxury sofas, accent chairs, marble dining tables, and designer lighting. Attract visual excellence.";

if ($is_product_page && $current_product) {
    $page_title = $current_product['title'] . " — OXO Premium";
    $page_desc = $current_product['description'];
} elseif ($is_shop_page) {
    $page_title = "The Catalog — OXO Premium Furniture";
    $page_desc = "Browse the complete collection of high-end modular sofas, accent chairs, marble dining tables, and designer lighting fixtures at OXO.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    
    <!-- Meta Descriptions for SEO -->
    <meta name="description" content="<?php echo htmlspecialchars($page_desc); ?>">
    <meta name="keywords" content="luxury furniture, designer chairs, modular sofas, premium dining tables, light fixtures, OXO furniture">
    <meta name="author" content="OXO Design Team">
    
    <!-- FontAwesome Vector Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Custom Style Sheets -->
    <link rel="stylesheet" href="assets/css/style.css">
    
    <!-- Favicon Link -->
    <link rel="icon" type="image/png" href="assets/images/logo.png">
    
    <!-- Inject Products Database for JavaScript -->
    <script>
        window.PRODUCTS_DB = <?php echo json_encode($PRODUCTS_DB); ?>;
    </script>
</head>
<body>

    <!-- WebGL background container -->
    <canvas id="webgl-canvas"></canvas>

    <!-- Navigation Header -->
    <header class="site-header">
        <div class="header-container">
            <div class="logo-wrapper">
                <a href="<?php echo ($is_product_page || $is_shop_page) ? 'index.php' : '#hero'; ?>" class="logo-image-link">
                    <img src="assets/images/logo.png" alt="OXO Premium Furniture" class="header-logo-img">
                </a>
            </div>
            
            <nav class="nav-menu" id="nav-menu">
                <a href="<?php echo ($is_product_page || $is_shop_page) ? 'index.php#hero' : '#hero'; ?>" class="nav-link <?php echo ($current_script === 'index.php') ? 'active' : ''; ?>">Home</a>
                <a href="shop.php" class="nav-link <?php echo ($current_script === 'shop.php') ? 'active' : ''; ?>">Products</a>
                <a href="<?php echo ($is_product_page || $is_shop_page) ? 'index.php#about' : '#about'; ?>" class="nav-link">About</a>
                <a href="<?php echo ($is_product_page || $is_shop_page) ? 'index.php#contact' : '#contact'; ?>" class="nav-link">Contact</a>
            </nav>
            
            <div class="header-actions">

                <button class="header-btn magnetic" id="wishlist-toggle" aria-label="Open Wishlist">
                    <span class="magnetic-btn-text"><i class="fa-regular fa-heart"></i></span>
                    <span class="badge-count" id="wishlist-badge" style="display: none;">0</span>
                </button>
                
                <button class="header-btn magnetic" id="cart-toggle" aria-label="Open Cart">
                    <span class="magnetic-btn-text"><i class="fa-solid fa-bag-shopping"></i></span>
                    <span class="badge-count" id="cart-badge" style="display: none;">0</span>
                </button>
                
                <button class="menu-toggle" id="menu-toggle" aria-label="Toggle Menu">
                    <i class="fa-solid fa-bars-staggered"></i>
                </button>
            </div>
        </div>
    </header>
