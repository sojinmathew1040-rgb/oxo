// --- STATE MANAGEMENT ---
let cart = (JSON.parse(localStorage.getItem('oxo_cart')) || []).filter(item => item && item.id);
let wishlist = (JSON.parse(localStorage.getItem('oxo_wishlist')) || []).filter(item => item && item.id);

// Product Database (Loaded from window object)
const PRODUCTS_DB = window.PRODUCTS_DB;


document.addEventListener('DOMContentLoaded', () => {
    animateHeroIntro();
    initLenis();
    initThreeJS();
    initGSAPAnimations();
    initMagneticButtons();
    initShopState();
    initNavBehavior();
    checkUrlHash();
});

// --- 2. LENIS SMOOTH SCROLLING ---
let lenis;
function initLenis() {
    lenis = new Lenis({
        duration: 1.4,
        easing: (t) => Math.min(1, 1.001 - Math.pow(2, -10 * t)),
        orientation: 'vertical',
        gestureOrientation: 'vertical',
        smoothWheel: true,
        wheelMultiplier: 1,
        touchMultiplier: 2,
        infinite: false,
    });

    lenis.on('scroll', ScrollTrigger.update);

    gsap.ticker.add((time) => {
        lenis.raf(time * 1000);
    });

    gsap.ticker.lagSmoothing(0);
}

function checkUrlHash() {
    if (window.location.hash) {
        const target = document.querySelector(window.location.hash);
        if (target) {
            setTimeout(() => {
                if (lenis) {
                    lenis.scrollTo(target, { offset: -50 });
                } else {
                    target.scrollIntoView({ behavior: 'smooth' });
                }
            }, 600);
        }
    }
}

// --- 3. THREE.JS / WEBGL PARTICLE BG ---
let threeScene, threeCamera, threeRenderer, particles;
let mouseX = 0, mouseY = 0;

function initThreeJS() {
    const canvas = document.getElementById('webgl-canvas');
    if (!canvas) return;

    // Scene setup
    threeScene = new THREE.Scene();

    // Camera setup
    threeCamera = new THREE.PerspectiveCamera(75, window.innerWidth / window.innerHeight, 0.1, 1000);
    threeCamera.position.z = 5;

    // Renderer
    threeRenderer = new THREE.WebGLRenderer({ canvas: canvas, alpha: true, antialias: true });
    threeRenderer.setSize(window.innerWidth, window.innerHeight);
    threeRenderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));

    // Particle Geometry
    const particlesCount = 700;
    const posArray = new Float32Array(particlesCount * 3);
    const scaleArray = new Float32Array(particlesCount);

    for (let i = 0; i < particlesCount * 3; i += 3) {
        // Random positions inside a cuboid
        posArray[i] = (Math.random() - 0.5) * 12;     // X
        posArray[i + 1] = (Math.random() - 0.5) * 12; // Y
        posArray[i + 2] = (Math.random() - 0.5) * 8;  // Z
        
        scaleArray[i/3] = Math.random();
    }

    const particleGeometry = new THREE.BufferGeometry();
    particleGeometry.setAttribute('position', new THREE.BufferAttribute(posArray, 3));
    particleGeometry.setAttribute('scale', new THREE.BufferAttribute(scaleArray, 1));

    // Custom circle particle texture (using Canvas)
    const pCanvas = document.createElement('canvas');
    pCanvas.width = 16;
    pCanvas.height = 16;
    const ctx = pCanvas.getContext('2d');
    const grad = ctx.createRadialGradient(8, 8, 0, 8, 8, 8);
    grad.addColorStop(0, 'rgba(250, 249, 246, 1)');
    grad.addColorStop(0.5, 'rgba(250, 249, 246, 0.4)');
    grad.addColorStop(1, 'rgba(250, 249, 246, 0)');
    ctx.fillStyle = grad;
    ctx.fillRect(0, 0, 16, 16);
    
    const pTexture = new THREE.CanvasTexture(pCanvas);

    // Particle Material
    // Primary green color `#0A2E24`, accent `#C8A276` and off-white `#FAF9F6`
    // We will tint particles to a luxury warm tint
    const particleMaterial = new THREE.PointsMaterial({
        size: 0.08,
        map: pTexture,
        transparent: true,
        blending: THREE.AdditiveBlending,
        depthWrite: false,
        color: 0x8CA89E // Soft sage green tint
    });

    // Particle System
    particles = new THREE.Points(particleGeometry, particleMaterial);
    threeScene.add(particles);

    // Light
    const ambientLight = new THREE.AmbientLight(0xffffff, 0.5);
    threeScene.add(ambientLight);

    // Mouse movement tracker
    window.addEventListener('mousemove', (e) => {
        mouseX = (e.clientX / window.innerWidth) - 0.5;
        mouseY = (e.clientY / window.innerHeight) - 0.5;
    });

    // Window resize
    window.addEventListener('resize', () => {
        threeCamera.aspect = window.innerWidth / window.innerHeight;
        threeCamera.updateProjectionMatrix();
        threeRenderer.setSize(window.innerWidth, window.innerHeight);
    });

    // Render loop
    const clock = new THREE.Clock();
    
    function animateWebGL() {
        requestAnimationFrame(animateWebGL);
        
        const elapsedTime = clock.getElapsedTime();
        
        // Gentle rotation
        particles.rotation.y = elapsedTime * 0.03;
        particles.rotation.x = elapsedTime * 0.015;

        // Mouse follow effect with easing (parallax)
        const targetX = mouseX * 1.5;
        const targetY = -mouseY * 1.5;
        particles.position.x += (targetX - particles.position.x) * 0.05;
        particles.position.y += (targetY - particles.position.y) * 0.05;

        threeRenderer.render(threeScene, threeCamera);
    }
    
    animateWebGL();
}

// --- 4. GSAP & SCROLLTRIGGER ANIMATIONS ---
function initGSAPAnimations() {
    gsap.registerPlugin(ScrollTrigger);

    // Set initial hidden states for text reveal
    document.querySelectorAll('.split-text').forEach(el => {
        // Split Type kinetic typography
        const split = new SplitType(el, { types: 'lines, words, chars' });
        
        // Setup initial wrapper styling
        if (split.chars) {
            gsap.set(split.chars, { 
                yPercent: 100,
                rotate: 5
            });

            // Reveal trigger on scroll
            gsap.to(split.chars, {
                scrollTrigger: {
                    trigger: el,
                    start: "top 85%",
                    toggleActions: "play none none none"
                },
                yPercent: 0,
                rotate: 0,
                duration: 1.2,
                stagger: 0.015,
                ease: "power4.out"
            });
        }
    });

    // Card fade/scale reveals
    gsap.utils.toArray('.product-card').forEach((card, idx) => {
        gsap.from(card, {
            scrollTrigger: {
                trigger: card,
                start: "top 90%",
                toggleActions: "play none none none"
            },
            opacity: 0,
            y: 50,
            scale: 0.95,
            duration: 1.0,
            delay: (idx % 4) * 0.1, // Stagger in grid row
            ease: "power3.out"
        });
    });



    // Smooth section background color fades (optional elegant touch)
    gsap.to('body', {
        scrollTrigger: {
            trigger: '#about',
            start: "top 50%",
            end: "bottom 50%",
            scrub: true
        },
        // We let CSS manage base variables, but if we need direct styling shifts we can add them here
    });
}

function animateHeroIntro() {
    // Splits titles inside hero line-by-line
    const titleLines = document.querySelectorAll('.hero-heading .hero-title-line');
    if (titleLines.length === 0) return;

    const splitLines = [];
    titleLines.forEach(line => {
        splitLines.push(new SplitType(line, { types: 'words, chars' }));
    });

    const tl = gsap.timeline();

    // 1. Header slides in
    tl.from('.site-header', {
        y: -100,
        opacity: 0,
        duration: 1.2,
        ease: "power4.out"
    });

    // 2. Letters slide/rotate up
    const allChars = [];
    splitLines.forEach(split => {
        if (split.chars) {
            allChars.push(...split.chars);
        }
    });

    if (allChars.length > 0) {
        tl.fromTo(allChars, 
            { yPercent: 115, rotate: 2 },
            { yPercent: 0, rotate: 0, duration: 1.3, stagger: 0.015, ease: "power4.out" },
            "-=0.8"
        );
    }

    // 3. Subtitles, CTA, indicators reveal
    tl.from('.hero-desc', {
        opacity: 0,
        y: 30,
        duration: 1.0,
        ease: "power3.out"
    }, "-=1.0");

    tl.from('.hero-actions-row .magnetic-btn', {
        opacity: 0,
        scale: 0.9,
        y: 20,
        duration: 1.0,
        stagger: 0.15,
        ease: "power3.out"
    }, "-=0.8");

    tl.from('.scroll-indicator', {
        opacity: 0,
        y: -20,
        duration: 1.0,
        ease: "power3.out"
    }, "-=0.8");
}

// --- 5. MAGNETIC BUTTONS ---
function initMagneticButtons() {
    // Disabled magnetic movement effect globally to keep all buttons fixed/static
    return;

    magneticBtns.forEach(btn => {
        const text = btn.querySelector('.magnetic-btn-text');

        btn.addEventListener('mousemove', (e) => {
            const rect = btn.getBoundingClientRect();
            const x = e.clientX - rect.left - rect.width / 2;
            const y = e.clientY - rect.top - rect.height / 2;

            // Move the button itself slightly
            gsap.to(btn, {
                x: x * 0.35,
                y: y * 0.35,
                duration: 0.3,
                ease: "power2.out"
            });

            // Move the inner text more for enhanced depth
            if (text) {
                gsap.to(text, {
                    x: x * 0.5,
                    y: y * 0.5,
                    duration: 0.3,
                    ease: "power2.out"
                });
            }
        });

        btn.addEventListener('mouseleave', () => {
            // Spring back
            gsap.to(btn, {
                x: 0,
                y: 0,
                duration: 0.6,
                ease: "elastic.out(1, 0.4)"
            });

            if (text) {
                gsap.to(text, {
                    x: 0,
                    y: 0,
                    duration: 0.6,
                    ease: "elastic.out(1, 0.4)"
                });
            }
        });
    });
}

// --- 6. SHOPPING STATE, CART & WISHLIST ---
function initShopState() {
    updateCounters();
    renderCart();
    renderWishlist();

    // Drawer Toggles
    const cartToggle = document.getElementById('cart-toggle');
    const wishlistToggle = document.getElementById('wishlist-toggle');
    const cartDrawer = document.getElementById('cart-drawer');
    const wishlistDrawer = document.getElementById('wishlist-drawer');
    const drawerOverlay = document.getElementById('drawer-overlay');
    const closeBtns = document.querySelectorAll('.drawer-close');

    function openDrawer(drawer) {
        if (!drawer) return;
        if (drawerOverlay) drawerOverlay.classList.add('active');
        drawer.classList.add('active');
        if (lenis) lenis.stop(); // Stop scroll when drawer is open
    }

    function closeAllDrawers() {
        if (drawerOverlay) drawerOverlay.classList.remove('active');
        if (cartDrawer) cartDrawer.classList.remove('active');
        if (wishlistDrawer) wishlistDrawer.classList.remove('active');
        if (lenis) lenis.start(); // Resume scroll
    }

    if (cartToggle) cartToggle.addEventListener('click', () => openDrawer(cartDrawer));
    if (wishlistToggle) wishlistToggle.addEventListener('click', () => openDrawer(wishlistDrawer));
    if (drawerOverlay) drawerOverlay.addEventListener('click', closeAllDrawers);
    closeBtns.forEach(btn => btn.addEventListener('click', closeAllDrawers));

    // Handle Quick View Modal
    const modalOverlay = document.getElementById('modal-overlay');
    const modalClose = document.getElementById('modal-close');

    function openModal() {
        if (modalOverlay) {
            modalOverlay.classList.add('active');
            if (lenis) lenis.stop();
        }
    }

    function closeModal() {
        if (modalOverlay) {
            modalOverlay.classList.remove('active');
            if (lenis) lenis.start();
        }
    }

    if (modalClose) modalClose.addEventListener('click', closeModal);
    if (modalOverlay) {
        modalOverlay.addEventListener('click', (e) => {
            if (e.target === modalOverlay) closeModal();
        });
    }

    // Global event listeners for Add to Cart, Add to Wishlist, Quick View
    document.addEventListener('click', (e) => {
        const btn = e.target.closest('[data-action]');
        if (btn) {
            const action = btn.getAttribute('data-action');
            const pid = btn.getAttribute('data-id');

            if (action === 'add-to-cart') {
                const qtyInput = document.getElementById('detail-qty-val');
                const qty = qtyInput ? parseInt(qtyInput.value) || 1 : 1;
                addToCart(pid, qty);
                openDrawer(cartDrawer);
            } else if (action === 'add-to-wishlist') {
                toggleWishlist(pid);
            } else if (action === 'quick-view') {
                setupQuickView(pid);
                openModal();
            }
            return;
        }

        const card = e.target.closest('.product-card');
        if (card) {
            const pid = card.getAttribute('data-id');
            if (pid) {
                window.location.href = `product.php?id=${pid}`;
            }
        }
    });

    // Category & Material Filtering with GSAP Card Shifts
    let activeCategory = 'all';
    let activeMaterial = 'all';

    const filterBtns = document.querySelectorAll('.filter-btn');
    const materialBtns = document.querySelectorAll('.material-filter-btn');
    const productCards = document.querySelectorAll('.product-card');

    function applyCatalogFilters() {
        const tl = gsap.timeline();
        
        // Fade out current cards
        tl.to(productCards, {
            opacity: 0,
            scale: 0.9,
            y: 15,
            duration: 0.3,
            stagger: 0.03,
            ease: "power2.in",
            onComplete: () => {
                // Hide/Show based on both active filters
                productCards.forEach(card => {
                    const cat = card.getAttribute('data-category');
                    const mat = card.getAttribute('data-material') || '';
                    
                    const catMatches = (activeCategory === 'all' || cat === activeCategory);
                    const matMatches = (activeMaterial === 'all' || mat === activeMaterial);
                    
                    if (catMatches && matMatches) {
                        card.style.display = 'flex';
                    } else {
                        card.style.display = 'none';
                    }
                });
                
                // Trigger scroll refresh to recalibrate positions
                ScrollTrigger.refresh();
            }
        });

        // Fade in matching cards
        tl.to(productCards, {
            opacity: (i, target) => {
                const cat = target.getAttribute('data-category');
                const mat = target.getAttribute('data-material') || '';
                const matches = (activeCategory === 'all' || cat === activeCategory) && (activeMaterial === 'all' || mat === activeMaterial);
                return matches ? 1 : 0;
            },
            scale: (i, target) => {
                const cat = target.getAttribute('data-category');
                const mat = target.getAttribute('data-material') || '';
                const matches = (activeCategory === 'all' || cat === activeCategory) && (activeMaterial === 'all' || mat === activeMaterial);
                return matches ? 1 : 0.9;
            },
            y: (i, target) => {
                const cat = target.getAttribute('data-category');
                const mat = target.getAttribute('data-material') || '';
                const matches = (activeCategory === 'all' || cat === activeCategory) && (activeMaterial === 'all' || mat === activeMaterial);
                return matches ? 0 : 15;
            },
            duration: 0.5,
            stagger: 0.03,
            ease: "power3.out"
        });
    }

    if (filterBtns.length > 0) {
        filterBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                activeCategory = btn.getAttribute('data-filter') || 'all';
                filterBtns.forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                applyCatalogFilters();
            });
        });
    }

    if (materialBtns.length > 0) {
        materialBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                activeMaterial = btn.getAttribute('data-material') || 'all';
                materialBtns.forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                applyCatalogFilters();
            });
        });
    }

    // Parse URL parameters to auto-select category filter on load
    const urlParams = new URLSearchParams(window.location.search);
    const filterParam = urlParams.get('filter');
    if (filterParam && filterBtns.length > 0) {
        const targetBtn = document.querySelector(`.filter-btn[data-filter="${filterParam}"]`);
        if (targetBtn) {
            setTimeout(() => {
                targetBtn.click();
            }, 100);
        }
    }
}

// Add to Cart Logic
function addToCart(pid, qtyToAdd = 1) {
    const product = PRODUCTS_DB[pid];
    if (!product) return;

    const existing = cart.find(item => item.id === pid);
    if (existing) {
        existing.qty += qtyToAdd;
    } else {
        cart.push({
            id: pid,
            title: product.title,
            price: product.price,
            image: product.image,
            qty: qtyToAdd
        });
    }

    localStorage.setItem('oxo_cart', JSON.stringify(cart));
    updateCounters();
    renderCart();
    
    // Play micro-animation on badge
    const badge = document.getElementById('cart-badge');
    if (badge) {
        gsap.fromTo(badge, { scale: 0.6 }, { scale: 1, duration: 0.5, ease: "elastic.out(1, 0.3)" });
    }
}

// Modify Quantity in Cart
function modifyCartQty(pid, delta) {
    const item = cart.find(i => i.id === pid);
    if (!item) return;

    item.qty += delta;
    if (item.qty <= 0) {
        cart = cart.filter(i => i.id !== pid);
    }

    localStorage.setItem('oxo_cart', JSON.stringify(cart));
    updateCounters();
    renderCart();
}

// Remove from Cart
function removeFromCart(pid) {
    cart = cart.filter(item => item.id !== pid);
    localStorage.setItem('oxo_cart', JSON.stringify(cart));
    updateCounters();
    renderCart();
}

// Wishlist Logic
function toggleWishlist(pid) {
    const product = PRODUCTS_DB[pid];
    if (!product) return;

    const idx = wishlist.findIndex(item => item.id === pid);
    
    // Select wishlist icon trigger
    const triggers = document.querySelectorAll(`[data-id="${pid}"][data-action="add-to-wishlist"]`);

    if (idx !== -1) {
        wishlist.splice(idx, 1);
        triggers.forEach(t => t.querySelector('i').className = 'fa-regular fa-heart');
    } else {
        wishlist.push({
            id: pid,
            title: product.title,
            price: product.price,
            image: product.image
        });
        triggers.forEach(t => t.querySelector('i').className = 'fa-solid fa-heart text-accent');
        
        // Wishlist counter animation
        const badge = document.getElementById('wishlist-badge');
        if (badge) {
            gsap.fromTo(badge, { scale: 0.6 }, { scale: 1, duration: 0.5, ease: "elastic.out(1, 0.3)" });
        }
    }

    localStorage.setItem('oxo_wishlist', JSON.stringify(wishlist));
    updateCounters();
    renderWishlist();
}

// Setup Quick View Modal Content
function setupQuickView(pid) {
    const product = PRODUCTS_DB[pid];
    if (!product) return;

    const modal = document.getElementById('modal-overlay');
    if (!modal) return;

    modal.querySelector('.modal-category').textContent = product.category;
    modal.querySelector('.modal-title').textContent = product.title;
    modal.querySelector('.modal-price').textContent = formatCurrency(product.price);
    modal.querySelector('.modal-desc').textContent = product.description;
    modal.querySelector('.modal-details').textContent = product.specs;
    modal.querySelector('.modal-visual img').src = product.image;
    
    // Setup action button in modal
    const viewDetailsBtn = modal.querySelector('#modal-view-details');
    if (viewDetailsBtn) {
        viewDetailsBtn.setAttribute('href', `product.php?id=${pid}`);
    }
}

// Render Cart HTML
function renderCart() {
    const cartBody = document.getElementById('cart-items-container');
    const summaryPrice = document.getElementById('cart-subtotal');
    
    if (!cartBody) return;

    if (cart.length === 0) {
        cartBody.innerHTML = `
            <div class="drawer-empty">
                <i class="fa-solid fa-bag-shopping"></i>
                <p>Your shopping cart is empty.</p>
            </div>
        `;
        if (summaryPrice) summaryPrice.textContent = formatCurrency(0);
        return;
    }

    let html = '';
    let total = 0;
    
    cart.forEach(item => {
        const itemTotal = item.price * item.qty;
        total += itemTotal;
        
        html += `
            <div class="drawer-item" data-cart-id="${item.id}">
                <div class="drawer-item-img">
                    <img src="${item.image}" alt="${item.title}">
                </div>
                <div class="drawer-item-info">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 10px;">
                        <span class="drawer-item-title">${item.title}</span>
                        <button class="drawer-item-remove" onclick="removeFromCart('${item.id}')"><i class="fa-regular fa-trash-can"></i></button>
                    </div>
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 10px;">
                        <div class="drawer-item-qty">
                            <span class="qty-btn" onclick="modifyCartQty('${item.id}', -1)">-</span>
                            <span class="qty-val">${item.qty}</span>
                            <span class="qty-btn" onclick="modifyCartQty('${item.id}', 1)">+</span>
                        </div>
                        <span class="drawer-item-price">${formatCurrency(itemTotal)}</span>
                    </div>
                </div>
            </div>
        `;
    });

    cartBody.innerHTML = html;
    if (summaryPrice) summaryPrice.textContent = formatCurrency(total);
}

// Render Wishlist HTML
function renderWishlist() {
    const wishBody = document.getElementById('wishlist-items-container');
    if (!wishBody) return;

    if (wishlist.length === 0) {
        wishBody.innerHTML = `
            <div class="drawer-empty">
                <i class="fa-solid fa-heart"></i>
                <p>Your wishlist is empty.</p>
            </div>
        `;
        return;
    }

    let html = '';
    
    wishlist.forEach(item => {
        html += `
            <div class="drawer-item">
                <div class="drawer-item-img">
                    <img src="${item.image}" alt="${item.title}">
                </div>
                <div class="drawer-item-info">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 10px;">
                        <span class="drawer-item-title">${item.title}</span>
                        <button class="drawer-item-remove" onclick="toggleWishlist('${item.id}')"><i class="fa-solid fa-heart text-accent"></i></button>
                    </div>
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 10px;">
                        <span class="drawer-item-price">${formatCurrency(item.price)}</span>
                        <button class="magnetic-btn secondary" style="padding: 8px 16px; font-size: 0.75rem;" data-action="add-to-cart" data-id="${item.id}">
                            <span class="magnetic-btn-text">Add to Cart</span>
                        </button>
                    </div>
                </div>
            </div>
        `;
    });

    wishBody.innerHTML = html;
    initMagneticButtons(); // re-initialize magnetic classes on new elements
}

// Update counters in headers
function updateCounters() {
    const cartBadge = document.getElementById('cart-badge');
    const wishlistBadge = document.getElementById('wishlist-badge');

    const cartTotalQty = cart.reduce((acc, curr) => acc + curr.qty, 0);
    const wishTotalCount = wishlist.length;

    if (cartBadge) {
        cartBadge.textContent = cartTotalQty;
        cartBadge.style.display = cartTotalQty > 0 ? 'flex' : 'none';
    }

    if (wishlistBadge) {
        wishlistBadge.textContent = wishTotalCount;
        wishlistBadge.style.display = wishTotalCount > 0 ? 'flex' : 'none';
    }

    // Sync heart classes on original grid
    document.querySelectorAll('[data-action="add-to-wishlist"]').forEach(btn => {
        const pid = btn.getAttribute('data-id');
        const icon = btn.querySelector('i');
        if (wishlist.some(item => item.id === pid)) {
            icon.className = 'fa-solid fa-heart text-accent';
        } else {
            icon.className = 'fa-regular fa-heart';
        }
    });
}

// Helpers
function formatCurrency(val) {
    return new Intl.NumberFormat('en-IN', {
        style: 'currency',
        currency: 'INR',
        maximumFractionDigits: 0
    }).format(val);
}

// --- 7. NAVIGATION BEHAVIOR & HAMBURGER ---
function initNavBehavior() {
    const header = document.querySelector('.site-header');
    const menuToggle = document.getElementById('menu-toggle');
    const navMenu = document.getElementById('nav-menu');
    const navLinks = document.querySelectorAll('.nav-link');
    
    let lastScrollY = window.scrollY;

    // Header hiding on scroll
    window.addEventListener('scroll', () => {
        const currentScrollY = window.scrollY;

        if (currentScrollY > 100) {
            header.classList.add('header-scrolled');
        } else {
            header.classList.remove('header-scrolled');
        }

        if (currentScrollY > lastScrollY && currentScrollY > 300) {
            // Scroll down
            header.classList.add('header-hidden');
        } else {
            // Scroll up
            header.classList.remove('header-hidden');
        }
        
        lastScrollY = currentScrollY;

        // Dynamic Active states for sections
        const scrollPos = currentScrollY + 200;
        document.querySelectorAll('section').forEach(sec => {
            const top = sec.offsetTop;
            const height = sec.offsetHeight;
            const id = sec.getAttribute('id');
            
            if (scrollPos >= top && scrollPos < top + height) {
                navLinks.forEach(link => {
                    link.classList.remove('active');
                    if (link.getAttribute('href') === `#${id}`) {
                        link.classList.add('active');
                    }
                });
            }
        });
    });

    // Mobile Hamburger
    if (menuToggle && navMenu) {
        menuToggle.addEventListener('click', () => {
            navMenu.classList.toggle('active');
            
            const icon = menuToggle.querySelector('i');
            if (navMenu.classList.contains('active')) {
                icon.className = 'fa-solid fa-xmark';
                if (lenis) lenis.stop();
            } else {
                icon.className = 'fa-solid fa-bars-staggered';
                if (lenis) lenis.start();
            }
        });
    }

    // Close mobile nav on link click
    navLinks.forEach(link => {
        link.addEventListener('click', (e) => {
            const href = link.getAttribute('href');
            if (href.startsWith('index.php') || href.startsWith('about.php')) {
                if (navMenu && navMenu.classList.contains('active')) {
                    navMenu.classList.remove('active');
                    if (menuToggle) menuToggle.querySelector('i').className = 'fa-solid fa-bars-staggered';
                }
                return; // Let standard link navigation happen
            }

            e.preventDefault();
            const targetSec = document.querySelector(href);

            if (navMenu && navMenu.classList.contains('active')) {
                navMenu.classList.remove('active');
                if (menuToggle) menuToggle.querySelector('i').className = 'fa-solid fa-bars-staggered';
            }

            if (targetSec && lenis) {
                lenis.scrollTo(targetSec, { offset: -50 });
            }
        });
    });
}
