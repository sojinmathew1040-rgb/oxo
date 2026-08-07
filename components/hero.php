<?php
require_once __DIR__ . '/../includes/db.php';
$hero_tag = get_site_content('hero_tag', 'Collection 2026');
$hero_t1 = get_site_content('hero_title_1', 'Silent Luxury');
$hero_t2 = get_site_content('hero_title_2', 'For Modern Spaces');
$hero_desc = get_site_content('hero_desc', 'Explore a curated assembly of luxury furniture designed for high-end residential interiors. Sculpted shapes, premium textures, and cinematic aesthetics.');
$hero_media = get_site_content('hero_media_path', 'assets/images/HERO.mp4');
$hero_btn1_txt = get_site_content('hero_btn_primary_text', 'Explore Catalog');
$hero_btn1_link = get_site_content('hero_btn_primary_link', 'shop.php');
$hero_btn2_txt = get_site_content('hero_btn_secondary_text', 'Our Legacy');
$hero_btn2_link = get_site_content('hero_btn_secondary_link', '#about');

$is_video = (bool)preg_match('/\.(mp4|webm|ogg)$/i', $hero_media);
?>
<!-- Hero Section with Dynamic Background Video or Image -->
<section id="hero">
    <div class="hero-bg-video">
        <?php if ($is_video): ?>
            <video autoplay loop muted playsinline aria-hidden="true">
                <source src="<?php echo htmlspecialchars($hero_media); ?>" type="video/mp4">
                Your browser does not support the video tag.
            </video>
        <?php else: ?>
            <img src="<?php echo htmlspecialchars($hero_media); ?>" alt="Hero Background" style="width: 100%; height: 100%; object-fit: cover;">
        <?php endif; ?>
    </div>
    
    <div class="hero-overlay"></div>
    
    <div class="hero-content">
        <div class="hero-heading">
            <span class="section-tag" style="color: var(--color-secondary); margin-bottom: 10px;"><?php echo htmlspecialchars($hero_tag); ?></span>
            <h1 class="title-large">
                <span class="hero-title-line"><?php echo htmlspecialchars($hero_t1); ?></span>
                <span class="hero-title-line"><?php echo htmlspecialchars($hero_t2); ?></span>
            </h1>
        </div>
        
        <p class="hero-desc">
            <?php echo htmlspecialchars($hero_desc); ?>
        </p>
        
        <div class="hero-actions-row">
            <a href="<?php echo htmlspecialchars($hero_btn1_link); ?>" class="magnetic-btn">
                <span class="magnetic-btn-text"><?php echo htmlspecialchars($hero_btn1_txt); ?></span>
            </a>
            <?php if (strpos($hero_btn2_link, '#') === 0): ?>
                <button class="magnetic-btn secondary" data-action="scroll-to" href="<?php echo htmlspecialchars($hero_btn2_link); ?>">
                    <span class="magnetic-btn-text"><?php echo htmlspecialchars($hero_btn2_txt); ?></span>
                </button>
            <?php else: ?>
                <a href="<?php echo htmlspecialchars($hero_btn2_link); ?>" class="magnetic-btn secondary" style="text-decoration: none;">
                    <span class="magnetic-btn-text"><?php echo htmlspecialchars($hero_btn2_txt); ?></span>
                </a>
            <?php endif; ?>
        </div>
    </div>
</section>
