    <!-- Footer Section -->
    <footer class="site-footer">
        <div class="container">
            <div class="footer-top">
                <div class="footer-brand">
                    <span class="footer-logo">OXO</span>
                    <p class="footer-tagline">Crafting spaces of silent luxury, cinematic elegance, and premium comfort. Designed to wow, built to last.</p>
                    <div class="social-links" style="margin-top: 10px;">
                        <a href="#" class="social-link" aria-label="Facebook"><i class="fa-brands fa-facebook-f"></i></a>
                        <a href="#" class="social-link" aria-label="Instagram"><i class="fa-brands fa-instagram"></i></a>
                        <a href="#" class="social-link" aria-label="Pinterest"><i class="fa-brands fa-pinterest-p"></i></a>
                        <a href="#" class="social-link" aria-label="Twitter"><i class="fa-brands fa-x-twitter"></i></a>
                    </div>
                </div>
                
                <div class="footer-col">
                    <span class="footer-col-title">Collections</span>
                    <div class="footer-links">
                        <a href="shop.php?filter=sofas" class="footer-link">Sofas & Lounges</a>
                        <a href="shop.php?filter=chairs" class="footer-link">Accent Chairs</a>
                        <a href="shop.php?filter=tables" class="footer-link">Dining Tables</a>
                        <a href="shop.php?filter=lighting" class="footer-link">Architectural Lighting</a>
                        <a href="shop.php?filter=storage" class="footer-link">Walnut Sideboards</a>
                    </div>
                </div>
                
                <div class="footer-col">
                    <span class="footer-col-title">Information</span>
                    <div class="footer-links">
                        <a href="#about" class="footer-link">Our Philosophy</a>
                        <a href="#about" class="footer-link">Sourcing & Craftsmanship</a>
                        <a href="#" class="footer-link">Sustainability Pledge</a>
                        <a href="#" class="footer-link">Shipping & Returns</a>
                        <a href="#" class="footer-link">Privacy Policy</a>
                    </div>
                </div>
                
                <div class="footer-col">
                    <span class="footer-col-title">Newsletter</span>
                    <div class="footer-newsletter">
                        <p class="footer-tagline" style="font-size: 0.85rem; max-width: 100%;">Subscribe to receive notifications about new collections and editorial releases.</p>
                        <div class="newsletter-input-group">
                            <input type="email" placeholder="Your email address" class="newsletter-input" aria-label="Email address">
                            <button class="newsletter-submit" aria-label="Subscribe"><i class="fa-solid fa-arrow-right"></i></button>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> OXO Furniture. All rights reserved. Designed for elite spaces.</p>
                <div class="footer-payments">
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
