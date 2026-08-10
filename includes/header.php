<?php
require_once __DIR__ . '/products-db.php';
require_once __DIR__ . '/db.php';

$current_script = basename($_SERVER['SCRIPT_NAME']);
$is_product_page = ($current_script === 'product.php');
$is_shop_page = ($current_script === 'shop.php');
$current_product = null;

if ($is_product_page && isset($_GET['id']) && isset($PRODUCTS_DB[$_GET['id']])) {
    $current_product = $PRODUCTS_DB[$_GET['id']];
}

$page_title = get_site_content('site_title', 'OXO — Premium Furniture Store');
$page_desc = get_site_content('site_description', 'Discover OXO, a premium high-end furniture store offering curated luxury sofas, accent chairs, marble dining tables, and designer lighting. Attract visual excellence.');

if ($is_product_page && $current_product) {
    $page_title = $current_product['title'] . " — OXO Premium";
    $page_desc = $current_product['description'];
} elseif ($is_shop_page) {
    $page_title = "The Catalog — " . get_site_content('site_title', 'OXO Premium Furniture');
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
                <div class="nav-menu-inner">
                    <div class="nav-links-stack">
                        <a href="<?php echo ($current_script !== 'index.php') ? 'index.php#hero' : '#hero'; ?>" class="nav-link">
                            <span class="nav-num">01</span>
                            <span class="nav-text">Home</span>
                            <i class="fa-solid fa-arrow-right-long nav-arrow"></i>
                        </a>
                        <a href="<?php echo ($current_script !== 'index.php') ? 'index.php#collections-container' : '#collections-container'; ?>" class="nav-link <?php echo ($current_script === 'shop.php') ? 'active' : ''; ?>">
                            <span class="nav-num">02</span>
                            <span class="nav-text">Products</span>
                            <i class="fa-solid fa-arrow-right-long nav-arrow"></i>
                        </a>
                        <a href="<?php echo ($current_script === 'index.php') ? '#about' : (($current_script === 'about.php') ? '#philosophy' : 'about.php#philosophy'); ?>" class="nav-link <?php echo ($current_script === 'about.php') ? 'active' : ''; ?>">
                            <span class="nav-num">03</span>
                            <span class="nav-text">About</span>
                            <i class="fa-solid fa-arrow-right-long nav-arrow"></i>
                        </a>
                        <a href="<?php echo ($current_script !== 'index.php') ? 'index.php#contact' : '#contact'; ?>" class="nav-link">
                            <span class="nav-num">04</span>
                            <span class="nav-text">Contact</span>
                            <i class="fa-solid fa-arrow-right-long nav-arrow"></i>
                        </a>
                    </div>

                    <div class="mobile-nav-footer">
                        <div class="mobile-nav-tagline">OXO Furniture Studio</div>
                        <p class="mobile-nav-sub">Curated luxury & visual excellence</p>
                        <?php 
                        $admin_wa = get_admin_whatsapp();
                        if (!empty($admin_wa)):
                            $clean_wa = preg_replace('/[^0-9]/', '', $admin_wa);
                            if (strlen($clean_wa) === 10) $clean_wa = '91' . $clean_wa;
                        ?>
                            <a href="https://wa.me/<?php echo $clean_wa; ?>" target="_blank" rel="noopener" class="mobile-nav-wa-btn">
                                <i class="fa-brands fa-whatsapp"></i> Chat on WhatsApp
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </nav>
            
            <div class="header-actions">
                <button class="menu-toggle" id="menu-toggle" aria-label="Toggle Menu">
                    <i class="fa-solid fa-bars-staggered"></i>
                </button>
            </div>
        </div>
    </header>
