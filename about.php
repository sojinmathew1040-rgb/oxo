<?php
/**
 * OXO Premium Furniture Store
 * Dedicated About Us Page
 */

// 1. Load Header Layout
require_once __DIR__ . '/includes/header.php';

// Copy generated assets for About page if not already present in the workspace
$generated_craftsman = 'C:\\Users\\sojin\\.gemini\\antigravity-ide\\brain\\de2a8e5a-b9fd-4554-a7fb-416cecf0e467\\craftsman_polishing_wood_1784621148970.png';
$dest_craftsman = __DIR__ . '/assets/images/about-craftsman.png';
if (file_exists($generated_craftsman) && !file_exists($dest_craftsman)) {
    @copy($generated_craftsman, $dest_craftsman);
}

// User-supplied showroom facade photograph
$user_facade = 'C:\\Users\\sojin\\.gemini\\antigravity-ide\\brain\\de2a8e5a-b9fd-4554-a7fb-416cecf0e467\\media__1784621937810.jpg';
$dest_facade = __DIR__ . '/assets/images/flagship-facade.jpg';
if (file_exists($user_facade)) {
    @copy($user_facade, $dest_facade);
}
?>

<main id="scroll-container">
    
    <!-- CSS Custom Styles for About Us Page -->
    <style>
        .about-hero-section {
            padding: 160px 0 100px;
            background: radial-gradient(circle at 50% 30%, rgba(181, 148, 96, 0.08), transparent 70%);
            text-align: center;
            border-bottom: 1px solid var(--color-panel-border);
        }
        .about-hero-container {
            max-width: 900px;
            margin: 0 auto;
            padding: 0 20px;
        }
        .about-title {
            font-size: 3.5rem;
            line-height: 1.1;
            margin-bottom: 25px;
            font-weight: 300;
            color: var(--color-primary);
        }
        .about-subtitle {
            font-size: 1.15rem;
            line-height: 1.8;
            color: var(--color-gray);
            max-width: 750px;
            margin: 0 auto 35px;
        }
        
        .about-grid-section {
            padding: 100px 0;
            border-bottom: 1px solid var(--color-panel-border);
        }
        .about-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 60px;
            align-items: center;
        }
        .about-content-card {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        .about-content-card h2 {
            font-size: 2.2rem;
            font-weight: 300;
            line-height: 1.2;
            color: var(--color-primary);
        }
        .about-content-card p {
            font-size: 0.98rem;
            line-height: 1.75;
            color: #4A564E;
            margin: 0;
        }
        .about-image-wrapper {
            position: relative;
            border-radius: 20px;
            overflow: hidden;
            border: 1px solid var(--color-panel-border);
            box-shadow: 0 15px 40px rgba(0,0,0,0.05);
            aspect-ratio: 16/9;
            background: linear-gradient(135deg, #18221D, #0C1511);
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .about-image-wrapper img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.6s cubic-bezier(0.25, 1, 0.5, 1);
        }
        .about-image-wrapper img:hover {
            transform: scale(1.06);
        }

        .philosophy-section {
            padding: 100px 0;
            background: rgba(181, 148, 96, 0.02);
            border-bottom: 1px solid var(--color-panel-border);
        }
        .philosophy-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 30px;
            margin-top: 50px;
        }
        .philosophy-card {
            background: var(--color-bg-panel);
            border: 1px solid var(--color-panel-border);
            border-radius: 16px;
            padding: 40px 30px;
            transition: all 0.35s ease;
        }
        .philosophy-card:hover {
            transform: translateY(-5px);
            border-color: var(--color-accent);
            box-shadow: 0 10px 30px rgba(181, 148, 96, 0.05);
        }
        .philosophy-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            background: rgba(181, 148, 96, 0.08);
            color: var(--color-accent);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3rem;
            margin-bottom: 25px;
        }
        .philosophy-card h3 {
            font-family: var(--font-title);
            font-size: 1.15rem;
            color: var(--color-primary);
            margin-bottom: 15px;
            font-weight: 700;
        }
        .philosophy-card p {
            font-size: 0.88rem;
            line-height: 1.6;
            color: var(--color-gray);
            margin: 0;
        }

        .process-section {
            padding: 100px 0;
            border-bottom: 1px solid var(--color-panel-border);
        }
        .process-flow {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 30px;
            margin-top: 50px;
        }
        .process-step {
            position: relative;
        }
        .step-number {
            font-family: var(--font-numeric);
            font-size: 3rem;
            font-weight: 200;
            color: var(--color-accent);
            opacity: 0.3;
            line-height: 1;
            margin-bottom: 15px;
        }
        .process-step h4 {
            font-family: var(--font-title);
            font-size: 1.05rem;
            color: var(--color-primary);
            margin-bottom: 10px;
            font-weight: 700;
        }
        .process-step p {
            font-size: 0.85rem;
            line-height: 1.6;
            color: var(--color-gray);
            margin: 0;
        }

        .about-cta-section {
            padding: 120px 0;
            text-align: center;
            background: radial-gradient(circle at 50% 80%, rgba(181, 148, 96, 0.06), transparent 60%);
        }

        @keyframes pulse-icon {
            0%, 100% { transform: scale(1); opacity: 0.25; }
            50% { transform: scale(1.15); opacity: 0.4; }
        }

        @media (max-width: 992px) {
            .about-grid {
                grid-template-columns: 1fr;
                gap: 40px;
            }
            .philosophy-grid {
                grid-template-columns: 1fr;
            }
            .process-flow {
                grid-template-columns: repeat(2, 1fr);
                gap: 40px;
            }
            .about-title {
                font-size: 2.5rem;
            }
        }
        @media (max-width: 576px) {
            .process-flow {
                grid-template-columns: 1fr;
            }
        }

        /* --- FLAGSHIP EXPERIENCE STYLES --- */
        .flagship-section {
            padding: 100px 0;
            background: #0C1511; /* Dark aesthetic from the corrugated black facade */
            color: #FFFFFF;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            position: relative;
        }
        .flagship-section::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: #E25822; /* Vivid orange accent from storefront border */
        }
        .flagship-section .section-tag {
            color: var(--color-accent);
        }
        .flagship-section h2 {
            font-size: 2.4rem;
            font-weight: 300;
            line-height: 1.2;
            color: #FFFFFF;
            margin-bottom: 25px;
        }
        .flagship-grid {
            display: grid;
            grid-template-columns: 1.1fr 0.9fr;
            gap: 60px;
            align-items: center;
        }
        .flagship-desc {
            font-size: 0.98rem;
            line-height: 1.8;
            color: #A5B6AC;
        }
        .flagship-desc p {
            margin-bottom: 25px;
        }
        .flagship-features {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            margin-top: 30px;
        }
        .feature-card {
            background: rgba(255, 255, 255, 0.02);
            border: 1px solid rgba(255, 255, 255, 0.06);
            border-radius: 12px;
            padding: 24px;
            transition: all 0.3s cubic-bezier(0.25, 1, 0.5, 1);
        }
        .feature-card:hover {
            border-color: #E25822; /* Store design orange */
            background: rgba(255, 255, 255, 0.04);
            transform: translateY(-4px);
        }
        .feature-card-icon {
            font-size: 1.4rem;
            color: #E25822;
            margin-bottom: 12px;
            display: inline-block;
        }
        .feature-card h4 {
            font-family: var(--font-title);
            font-size: 0.95rem;
            color: #FFFFFF;
            margin-bottom: 8px;
            font-weight: 700;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }
        .feature-card p {
            color: #8E9C94;
            margin: 0;
        }
        .flagship-image-card img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.8s cubic-bezier(0.16, 1, 0.3, 1);
        }
        .flagship-image-card:hover img {
            transform: scale(1.05);
        }

        /* --- DEPARTMENTS SECTION STYLES --- */
        .departments-section {
            padding: 100px 0;
            border-bottom: 1px solid var(--color-panel-border);
            background: var(--color-secondary);
        }
        .departments-intro {
            max-width: 800px;
            margin: 0 auto 55px;
            text-align: center;
        }
        .departments-intro h2 {
            margin-bottom: 15px;
            color: var(--color-primary);
        }
        .departments-grid {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 20px;
        }
        .dept-card {
            background: var(--color-white);
            border: 1px solid var(--color-panel-border);
            border-radius: 16px;
            padding: 35px 20px;
            text-align: center;
            transition: all 0.4s cubic-bezier(0.25, 1, 0.5, 1);
            position: relative;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .dept-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: #E25822; /* Vivid orange border matching store outline */
            transform: scaleX(0);
            transition: transform 0.35s ease;
            transform-origin: left;
        }
        .dept-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 16px 35px rgba(226, 88, 34, 0.06);
            border-color: rgba(226, 88, 34, 0.25);
        }
        .dept-card:hover::before {
            transform: scaleX(1);
        }
        .dept-icon {
            width: 54px;
            height: 54px;
            border-radius: 50%;
            background: rgba(226, 88, 34, 0.06);
            color: #E25822;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3rem;
            margin-bottom: 20px;
            transition: all 0.3s ease;
        }
        .dept-card:hover .dept-icon {
            background: #E25822;
            color: #FFFFFF;
        }
        .dept-card h3 {
            font-family: var(--font-title);
            font-size: 1.05rem;
            color: var(--color-primary);
            margin-bottom: 12px;
            font-weight: 700;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }
        .dept-card p {
            font-size: 0.8rem;
            line-height: 1.6;
            color: var(--color-gray);
            margin: 0;
        }

        /* --- FLAGSHIP RESPONSIVENESS --- */
        @media (max-width: 1200px) {
            .departments-grid {
                grid-template-columns: repeat(3, 1fr);
            }
        }
        @media (max-width: 992px) {
            .flagship-grid {
                grid-template-columns: 1fr;
                gap: 40px;
            }
        }
        @media (max-width: 768px) {
            .departments-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            .flagship-features {
                grid-template-columns: 1fr;
            }
        }
        @media (max-width: 480px) {
            .departments-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <!-- 1. Hero Welcome Header -->
    <section class="about-hero-section">
        <div class="about-hero-container">
            <span class="section-tag">Our Heritage</span>
            <h1 class="about-title">Crafting Silent <span class="title-serif">Elegance</span></h1>
            <p class="about-subtitle">OXO is a bespoke design atelier born from a commitment to uncompromised luxury. We carve furniture masterpieces designed to attract visual excellence in elite spaces.</p>
            <div style="display: flex; gap: 20px; justify-content: center; align-items: center; flex-wrap: wrap;">
                <div style="border-right: 1px solid var(--color-panel-border); padding-right: 25px; margin-right: 5px;">
                    <span style="font-family: var(--font-numeric); font-size: 2.2rem; font-weight: 300; color: var(--color-primary); display: block;">15+</span>
                    <span style="font-size: 0.72rem; text-transform: uppercase; letter-spacing: 1px; color: var(--color-gray); font-weight: 700;">Years Legacy</span>
                </div>
                <div style="border-right: 1px solid var(--color-panel-border); padding-right: 25px; margin-right: 5px;">
                    <span style="font-family: var(--font-numeric); font-size: 2.2rem; font-weight: 300; color: var(--color-primary); display: block;">100%</span>
                    <span style="font-size: 0.72rem; text-transform: uppercase; letter-spacing: 1px; color: var(--color-gray); font-weight: 700;">Bespoke Design</span>
                </div>
                <div>
                    <span style="font-family: var(--font-numeric); font-size: 2.2rem; font-weight: 300; color: var(--color-primary); display: block;">8,000+</span>
                    <span style="font-size: 0.72rem; text-transform: uppercase; letter-spacing: 1px; color: var(--color-gray); font-weight: 700;">Elite Homes</span>
                </div>
            </div>
        </div>
    </section>

    <!-- 2. Detail Story Grid -->
    <section class="about-grid-section">
        <div class="container">
            <div class="about-grid">
                
                <div class="about-content-card">
                    <span class="section-tag">Founding Story</span>
                    <h2>The Pursuit of <span class="title-serif">Perfection</span></h2>
                    <p>Founded with a desire to replace mass-production aesthetics with crafted distinction, OXO bridges modern architectural design with centuries-old artisanal craft.</p>
                    <p>Every sofa frame we carve, every marble slab we inspect, and every brass contour we shape is chosen to create furniture that doesn't just fill a space, but commands a quiet presence. Our designs emphasize clean silhouettes, premium natural materials, and uncompromised structural longevity.</p>
                    <p>We work exclusively with certified premium foresters, Italian marble quarries, and master metalworkers to bring custom bespoke layouts directly to elite homeowners, builders, and architects.</p>
                </div>

                <div class="about-image-wrapper">
                    <img src="assets/images/flagship-facade.jpg" alt="OXO Flagship Showroom Facade">
                </div>

            </div>
        </div>
    </section>

    <!-- 2.1 Flagship Experience Section -->
    <section class="flagship-section">
        <div class="container">
            <div class="flagship-grid">
                <div class="flagship-content">
                    <span class="section-tag">Showroom Experience</span>
                    <h2>OXO The Gallery: <br><span class="title-serif" style="color: var(--color-accent);">An Architectural Vision</span></h2>
                    <div class="flagship-desc">
                        <p>Our flagship showroom is more than just a store—it is a physical expression of our brand's bold design philosophy. Designed to represent the intersection of industrial strength and warm luxurious space, the landmark building commands a striking presence.</p>
                        <p>Featuring an iconic, vertically ribbed matte charcoal facade encased in a signature vivid orange-red border trim, the structure stands out as a piece of architectural art. Large, double-height panoramic glass displays allow natural light to cascade onto our curated furniture arrangements across multiple expansive floors.</p>
                        <p>With its welcoming entrance and proud regional identity (locally represented by the Malayalam script <strong>ഓക്സോ ഫർണിച്ചർ</strong>), OXO The Gallery offers a sensory walkthrough of elite luxury interiors.</p>
                    </div>
                </div>

                <div class="flagship-features">
                    <!-- Feature 1 -->
                    <div class="feature-card">
                        <span class="feature-card-icon"><i class="fa-solid fa-building"></i></span>
                        <h4>Ribbed Metal Facade</h4>
                        <p>Bold architectural corrugated dark cladding reflecting modern industrial design.</p>
                    </div>
                    <!-- Feature 2 -->
                    <div class="feature-card">
                        <span class="feature-card-icon"><i class="fa-solid fa-border-all"></i></span>
                        <h4>Vibrant Outline</h4>
                        <p>Signature orange-red architectural framing that adds energy and warm distinction.</p>
                    </div>
                    <!-- Feature 3 -->
                    <div class="feature-card">
                        <span class="feature-card-icon"><i class="fa-solid fa-window-maximize"></i></span>
                        <h4>Panoramic Glass</h4>
                        <p>Double-story glass panels displaying beautifully lit lifestyle settings.</p>
                    </div>
                    <!-- Feature 4 -->
                    <div class="feature-card">
                        <span class="feature-card-icon"><i class="fa-solid fa-location-dot"></i></span>
                        <h4>Design Hub</h4>
                        <p>A landmark experience center located in Kerala, inspiring regional design standards.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- 2.2 Curated Departments Section -->
    <section class="departments-section">
        <div class="container">
            <div class="departments-intro">
                <span class="section-tag">Our Collections</span>
                <h2 class="title-medium">Tailored for <span class="title-serif">Every Space</span></h2>
                <p style="color: var(--color-gray); font-size: 0.95rem; line-height: 1.7; margin: 0;">
                    Following the clear vision marked on our gallery's facade, OXO offers bespoke furniture meticulously crafted for five core segments of modern, elite living:
                </p>
            </div>

            <div class="departments-grid">
                <!-- Bedroom -->
                <div class="dept-card">
                    <div class="dept-icon"><i class="fa-solid fa-bed"></i></div>
                    <h3>Bedroom</h3>
                    <p>Sanctuaries of comfort. Hand-tufted headboards, low-profile platforms, floating bedside tables, and modular wardrobes.</p>
                </div>

                <!-- Kitchen -->
                <div class="dept-card">
                    <div class="dept-icon"><i class="fa-solid fa-kitchen-set"></i></div>
                    <h3>Kitchen</h3>
                    <p>Sleek culinary design. High-performance modular cabinetry, custom stone countertops, and smart integrated features.</p>
                </div>

                <!-- Living -->
                <div class="dept-card">
                    <div class="dept-icon"><i class="fa-solid fa-couch"></i></div>
                    <h3>Living</h3>
                    <p>Bespoke social spaces. Iconic modular sofas in rich textured fabrics, accent lounge chairs, and statement coffee tables.</p>
                </div>

                <!-- Dining -->
                <div class="dept-card">
                    <div class="dept-icon"><i class="fa-solid fa-chair"></i></div>
                    <h3>Dining</h3>
                    <p>Sculptural dining rooms. Solid walnut timber and Calacatta marble tables paired with ergonomic, hand-stitched seating.</p>
                </div>

                <!-- Office -->
                <div class="dept-card">
                    <div class="dept-icon"><i class="fa-solid fa-briefcase"></i></div>
                    <h3>Office</h3>
                    <p>Refined work environments. Executive timber desks, ergonomic chairs, and sophisticated library bookshelves.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- 3. Core Philosophy Section -->
    <section class="philosophy-section" id="philosophy">
        <div class="container">
            <div style="text-align: center;">
                <span class="section-tag">Guiding Values</span>
                <h2 class="title-medium">Our Core <span class="title-serif">Philosophy</span></h2>
            </div>
            
            <div class="philosophy-grid">
                <!-- Value 1 -->
                <div class="philosophy-card">
                    <div class="philosophy-icon">
                        <i class="fa-solid fa-compass-drafting"></i>
                    </div>
                    <h3>Silent Elegance</h3>
                    <p>We believe true luxury is quiet. Our pieces focus on balanced proportions, subtle contours, and seamless joints that complement instead of cluttering your interior.</p>
                </div>

                <!-- Value 2 -->
                <div class="philosophy-card">
                    <div class="philosophy-icon">
                        <i class="fa-solid fa-gem"></i>
                    </div>
                    <h3>Curated Materials</h3>
                    <p>From kiln-dried solid teak to premium Calacatta marble and brushed anodized metal, only the finest natural raw materials are hand-selected for our creations.</p>
                </div>

                <!-- Value 3 -->
                <div class="philosophy-card">
                    <div class="philosophy-icon">
                        <i class="fa-solid fa-hand-holding-heart"></i>
                    </div>
                    <h3>Artisanal Integrity</h3>
                    <p>Every piece is handcrafted by master carpenters and stonemasons who carry generational expertise. We build furniture to last lifetimes.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- 4. Crafting Process flow -->
    <section class="process-section">
        <div class="container">
            <div style="text-align: center;">
                <span class="section-tag">Behind the Scenes</span>
                <h2 class="title-medium">The Design <span class="title-serif">Process</span></h2>
            </div>
            
            <div class="process-flow">
                <!-- Step 1 -->
                <div class="process-step">
                    <div class="step-number">01</div>
                    <h4>Curation</h4>
                    <p>Selecting custom premium logs, marble deposits, and high-performance textiles.</p>
                </div>

                <!-- Step 2 -->
                <div class="process-step">
                    <div class="step-number">02</div>
                    <h4>Crafting</h4>
                    <p>Hand-carving structural framework, shaping stone profiles, and polishing brass details.</p>
                </div>

                <!-- Step 3 -->
                <div class="process-step">
                    <div class="step-number">03</div>
                    <h4>Upholstery</h4>
                    <p>Precise layering with high-density premium foam and hand-stitched leather or fabric.</p>
                </div>

                <!-- Step 4 -->
                <div class="process-step">
                    <div class="step-number">04</div>
                    <h4>Curation & Delivery</h4>
                    <p>Rigorous quality checks followed by white-glove assembly inside your home.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- 5. Call to Action Banner -->
    <section class="about-cta-section">
        <div class="container">
            <span class="section-tag">Take the Next Step</span>
            <h2 class="title-medium" style="margin-bottom: 30px;">Discover the <span class="title-serif">OXO Collection</span></h2>
            <a href="shop.php" class="magnetic-btn" style="display: inline-flex; align-items: center; justify-content: center; text-decoration: none;">
                <span class="magnetic-btn-text">Explore Catalog</span>
            </a>
        </div>
    </section>

    <script>
    document.addEventListener('DOMContentLoaded', () => {
        if (typeof gsap !== 'undefined') {
            // Hero animation (runs immediately on load)
            gsap.from('.about-hero-section > *', {
                opacity: 0,
                y: 30,
                duration: 1.2,
                stagger: 0.15,
                ease: "power4.out"
            });
        }
    });
    </script>
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
// Load Footer Layout
require_once __DIR__ . '/includes/footer.php';
?>
