<?php
require_once __DIR__ . '/../includes/db.php';
$ab_img = get_site_content('about_home_image', 'assets/images/sofa_1.png');
$ab_stat_val = get_site_content('about_home_stat_val', '15+ Years');
$ab_stat_lbl = get_site_content('about_home_stat_label', 'Master Italian Joinery');
$ab_tag = get_site_content('about_home_tag', 'Our Core Philosophy');
$ab_title = get_site_content('about_home_title', 'Architecting Silent Luxury');
$ab_p1 = get_site_content('about_home_p1', 'At OXO, furniture is not merely functional—it is spatial sculpture. Each creation is curated to define elite residential sanctuaries. We harmonise traditional Italian joinery with progressive architectural proportions.');
$ab_p2 = get_site_content('about_home_p2', 'Sourcing rare Calacatta marble pedestals, top-grain aniline leathers, and kiln-dried walnut timbers, our master artisans elevate raw earth elements into tactile works of art.');
$ab_b1_val = get_site_content('about_home_bento1_val', '15+');
$ab_b1_lbl = get_site_content('about_home_bento1_label', 'Years Legacy');
$ab_b2_val = get_site_content('about_home_bento2_val', '100%');
$ab_b2_lbl = get_site_content('about_home_bento2_label', 'Bespoke Design');
$ab_b3_val = get_site_content('about_home_bento3_val', '8,000+');
$ab_b3_lbl = get_site_content('about_home_bento3_label', 'Elite Residences');
$ab_btn_txt = get_site_content('about_home_btn_text', 'Read Our Full Story');
$ab_btn_link = get_site_content('about_home_btn_link', 'about.php');
?>
<!-- About / Philosophy Section -->
<section id="about" style="padding: 120px 0; background: linear-gradient(180deg, var(--color-secondary) 0%, #FFFFFF 100%); position: relative;">
    <div class="container">
        <div class="about-grid-luxury">
            
            <!-- Left Panel: Interactive Visual Card with Overlay Stat Badge -->
            <div class="about-visual-card">
                <img src="<?php echo htmlspecialchars($ab_img); ?>" alt="OXO Luxury Philosophy" loading="lazy">
                
                <div class="about-stat-badge-overlay">
                    <div style="width: 44px; height: 44px; border-radius: 50%; background: var(--color-primary); color: #FFFFFF; display: flex; align-items: center; justify-content: center;">
                        <i class="fa-solid fa-award" style="font-size: 1.1rem; color: var(--color-accent);"></i>
                    </div>
                    <div>
                        <div style="font-family: var(--font-numeric); font-weight: 700; font-size: 1.25rem; color: var(--color-primary); line-height: 1;"><?php echo htmlspecialchars($ab_stat_val); ?></div>
                        <div style="font-size: 0.72rem; color: var(--color-gray); font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; margin-top: 4px;"><?php echo htmlspecialchars($ab_stat_lbl); ?></div>
                    </div>
                </div>
            </div>
            
            <!-- Right Panel: Editorial Story Content -->
            <div style="display: flex; flex-direction: column; gap: 24px;">
                <div>
                    <span class="oxo-badge oxo-badge-accent" style="margin-bottom: 12px;">
                        <i class="fa-solid fa-compass" style="font-size: 0.65rem;"></i> <?php echo htmlspecialchars($ab_tag); ?>
                    </span>
                    <h2 style="font-family: var(--font-title); font-size: 2.8rem; color: var(--color-primary); font-weight: 700; line-height: 1.15; margin-top: 10px;">
                        <?php echo nl2br(htmlspecialchars($ab_title)); ?>
                    </h2>
                </div>
                
                <p style="font-size: 1.05rem; line-height: 1.8; color: #3A4740; margin: 0;">
                    <?php echo nl2br(htmlspecialchars($ab_p1)); ?>
                </p>
                
                <p style="font-size: 0.95rem; line-height: 1.7; color: var(--color-gray); margin: 0;">
                    <?php echo nl2br(htmlspecialchars($ab_p2)); ?>
                </p>
                
                <!-- Floating Bento Stats Grid -->
                <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin-top: 10px;">
                    <div class="oxo-glass-card" style="padding: 18px 14px; text-align: center;">
                        <span style="font-family: var(--font-numeric); font-size: 1.5rem; font-weight: 700; color: var(--color-primary); display: block;"><?php echo htmlspecialchars($ab_b1_val); ?></span>
                        <span style="font-size: 0.7rem; font-weight: 600; color: var(--color-gray); text-transform: uppercase; letter-spacing: 0.5px;"><?php echo htmlspecialchars($ab_b1_lbl); ?></span>
                    </div>
                    <div class="oxo-glass-card" style="padding: 18px 14px; text-align: center;">
                        <span style="font-family: var(--font-numeric); font-size: 1.5rem; font-weight: 700; color: var(--color-accent); display: block;"><?php echo htmlspecialchars($ab_b2_val); ?></span>
                        <span style="font-size: 0.7rem; font-weight: 600; color: var(--color-gray); text-transform: uppercase; letter-spacing: 0.5px;"><?php echo htmlspecialchars($ab_b2_lbl); ?></span>
                    </div>
                    <div class="oxo-glass-card" style="padding: 18px 14px; text-align: center;">
                        <span style="font-family: var(--font-numeric); font-size: 1.5rem; font-weight: 700; color: var(--color-primary); display: block;"><?php echo htmlspecialchars($ab_b3_val); ?></span>
                        <span style="font-size: 0.7rem; font-weight: 600; color: var(--color-gray); text-transform: uppercase; letter-spacing: 0.5px;"><?php echo htmlspecialchars($ab_b3_lbl); ?></span>
                    </div>
                </div>
                
                <div style="margin-top: 10px;">
                    <a href="<?php echo htmlspecialchars($ab_btn_link); ?>" class="magnetic-btn" style="padding: 12px 28px; border-radius: 30px; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; border: 1px solid var(--color-accent); font-weight: 600; font-size: 0.82rem; text-transform: uppercase; letter-spacing: 0.5px;">
                        <span class="magnetic-btn-text"><?php echo htmlspecialchars($ab_btn_txt); ?> &nbsp; <i class="fa-solid fa-arrow-right-long" style="color: var(--color-accent);"></i></span>
                    </a>
                </div>
            </div>

        </div>
    </div>
</section>
