    <!-- Footer Section -->
    <?php
    require_once __DIR__ . '/db.php';
    $f_desc = get_site_content('footer_desc', 'Architecting spaces of silent luxury, cinematic elegance, and bespoke Italian craftsmanship. Designed to inspire elite sanctuaries.');
    $f_copy = get_site_content('footer_copyright', 'OXO Furniture. All rights reserved.');
    $f_dev_credit = get_site_content('footer_dev_credit', 'Designed and Developed by peru');
    $f_dev_link = get_site_content('footer_dev_link', '#');
    $f_fb = get_site_content('contact_facebook', '#');
    $f_insta = get_site_content('contact_instagram', '#');
    $f_map = get_site_content('contact_map', 'https://maps.google.com');
    ?>
    <footer class="site-footer-luxury">
        <div class="container">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 60px; margin-bottom: 60px; align-items: start;">
                
                <!-- Col 1: Brand & Manifesto -->
                <div style="display: flex; flex-direction: column; gap: 20px;">
                    <a href="index.php" style="display: inline-block;">
                        <img src="assets/images/logo.png" alt="OXO Luxury Furniture" style="height: 50px; width: auto; filter: brightness(0) invert(1);">
                    </a>
                    <p style="font-size: 0.9rem; line-height: 1.7; color: rgba(255, 255, 255, 0.65); margin: 0; max-width: 380px;">
                        <?php echo htmlspecialchars($f_desc); ?>
                    </p>
                    <div style="display: flex; gap: 12px; margin-top: 6px;">
                        <a href="<?php echo htmlspecialchars($f_fb); ?>" aria-label="Facebook" style="width: 38px; height: 38px; border-radius: 50%; background: rgba(255,255,255,0.06); color: #FFFFFF; display: flex; align-items: center; justify-content: center; transition: all 0.3s ease;"><i class="fa-brands fa-facebook-f" style="font-size: 0.85rem;"></i></a>
                        <a href="<?php echo htmlspecialchars($f_insta); ?>" aria-label="Instagram" style="width: 38px; height: 38px; border-radius: 50%; background: rgba(255,255,255,0.06); color: #FFFFFF; display: flex; align-items: center; justify-content: center; transition: all 0.3s ease;"><i class="fa-brands fa-instagram" style="font-size: 0.85rem;"></i></a>
                        <a href="<?php echo htmlspecialchars($f_map); ?>" target="_blank" rel="noopener" aria-label="Google Maps Location" title="Open Google Maps Location" style="width: 38px; height: 38px; border-radius: 50%; background: rgba(255,255,255,0.06); color: #FFFFFF; display: flex; align-items: center; justify-content: center; transition: all 0.3s ease;"><i class="fa-solid fa-map-location-dot" style="font-size: 0.85rem;"></i></a>
                    </div>
                </div>
                
                <!-- Col 2: Newsletter -->
                <div>
                    <h4 style="font-family: var(--font-title); font-size: 0.82rem; text-transform: uppercase; letter-spacing: 1.5px; color: var(--color-accent); font-weight: 700; margin-bottom: 20px;">Private Journal</h4>
                    <p style="font-size: 0.85rem; color: rgba(255, 255, 255, 0.65); line-height: 1.6; margin-bottom: 16px; max-width: 360px;">
                        Subscribe to receive early access to private trunk shows and editorial lookbooks.
                    </p>
                    <div style="display: flex; gap: 8px; max-width: 400px;">
                        <input type="email" placeholder="Enter your email" style="flex: 1; background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.15); border-radius: 30px; padding: 12px 18px; color: #FFFFFF; font-size: 0.82rem;">
                        <button aria-label="Subscribe" style="width: 44px; height: 44px; border-radius: 50%; background: var(--color-accent); color: var(--color-primary); border: none; cursor: pointer; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                            <i class="fa-solid fa-arrow-right"></i>
                        </button>
                    </div>
                </div>

            </div>
            
            <div style="padding-top: 30px; border-top: 1px solid rgba(255, 255, 255, 0.08); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px; font-size: 0.78rem; color: rgba(255, 255, 255, 0.45);">
                <p>&copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars($f_copy); ?><?php if (!empty($f_dev_credit)): ?> &nbsp;|&nbsp; <a href="<?php echo htmlspecialchars($f_dev_link); ?>" target="_blank" rel="noopener" style="color: var(--color-accent); text-decoration: none; font-weight: 600; transition: all 0.3s ease; display: inline-flex; align-items: center; gap: 4px;"><?php echo htmlspecialchars($f_dev_credit); ?> <i class="fa-solid fa-arrow-up-right-from-square" style="font-size: 0.65rem;"></i></a><?php endif; ?></p>
                <div style="display: flex; gap: 18px; font-size: 1.2rem; color: rgba(255, 255, 255, 0.35);">
                    <i class="fa-brands fa-cc-visa"></i>
                    <i class="fa-brands fa-cc-mastercard"></i>
                    <i class="fa-brands fa-cc-amex"></i>
                    <i class="fa-brands fa-cc-apple-pay"></i>
                </div>
            </div>
        </div>
    </footer>
    <!-- Floating Back to Top Button (Bottom Left) -->
    <button id="btn-back-to-top" aria-label="Scroll to top" style="
        position: fixed;
        bottom: 30px;
        left: 30px;
        width: 50px;
        height: 50px;
        border-radius: 50%;
        background-color: var(--color-primary);
        color: #ffffff;
        border: 1px solid var(--color-accent);
        font-size: 1.15rem;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 99999;
        box-shadow: 0 6px 20px rgba(10, 46, 36, 0.3);
        opacity: 0;
        pointer-events: none;
        transform: translateY(15px);
        transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
    ">
        <i class="fa-solid fa-arrow-up"></i>
    </button>

    <style>
    #btn-back-to-top:hover {
        background-color: var(--color-accent) !important;
        color: var(--color-primary) !important;
        transform: translateY(-4px) scale(1.05) !important;
        box-shadow: 0 10px 25px rgba(200, 162, 118, 0.45) !important;
    }
    #btn-back-to-top:active {
        transform: translateY(-2px) scale(0.98) !important;
    }
    #btn-back-to-top.show {
        opacity: 1 !important;
        pointer-events: auto !important;
        transform: translateY(0) !important;
    }
    </style>

    <script>
    document.addEventListener('DOMContentLoaded', () => {
        const topBtn = document.getElementById('btn-back-to-top');
        if (topBtn) {
            window.addEventListener('scroll', () => {
                if (window.scrollY > 300) {
                    topBtn.classList.add('show');
                } else {
                    topBtn.classList.remove('show');
                }
            });
            
            topBtn.addEventListener('click', (e) => {
                e.preventDefault();
                if (window.lenis) {
                    window.lenis.scrollTo(0);
                } else {
                    window.scrollTo({
                        top: 0,
                        behavior: 'smooth'
                    });
                }
            });
        }
    });
    </script>

    <!-- Import Libraries CDNs -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/ScrollTrigger.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@studio-freight/lenis@1.0.39/dist/lenis.min.js"></script>
    <script src="https://unpkg.com/split-type"></script>
    
    <!-- Main Script -->
    <script src="assets/js/main.js"></script>
</body>
</html>
