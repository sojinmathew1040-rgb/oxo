<!-- Contact & Bespoke Concierge Section -->
<?php
require_once __DIR__ . '/../includes/db.php';
$c_tag = get_site_content('contact_tag', 'Bespoke Concierge');
$c_title = get_site_content('contact_title', 'Connect With OXO Private Service');
$c_sub = get_site_content('contact_subtitle', 'Have questions regarding custom modular dimensions, bespoke leathers, or private showroom viewings?');
$c_addr = get_site_content('contact_address', '84 Luxury Avenue, Suite 900, Mumbai, India');
$c_email = get_site_content('contact_email', 'concierge@oxo.design');
$c_phone = get_site_content('contact_phone', '+91 (22) 8800-4400');
$c_insta = get_site_content('contact_instagram', '#');
$c_fb = get_site_content('contact_facebook', '#');
$c_map = get_site_content('contact_map', 'https://maps.google.com');
?>
<section id="contact" style="padding: 100px 0; background: #FFFFFF;">
    <div class="container">
        
        <div class="contact-glass-deck">
            <div class="contact-glass-grid">
                
                <!-- Left Panel: Info & Concierge Details -->
                <div style="display: flex; flex-direction: column; gap: 30px;">
                    <div>
                        <span class="oxo-badge oxo-badge-accent" style="margin-bottom: 14px;">
                            <i class="fa-solid fa-headset" style="font-size: 0.65rem;"></i> <?php echo htmlspecialchars($c_tag); ?>
                        </span>
                        <h2 style="font-family: var(--font-title); font-size: clamp(1.8rem, 5vw, 2.8rem); font-weight: 700; line-height: 1.15; color: #FFFFFF; margin: 10px 0 0 0;">
                            <?php echo nl2br(htmlspecialchars($c_title)); ?>
                        </h2>
                        <p style="color: rgba(255, 255, 255, 0.7); font-size: 0.95rem; line-height: 1.7; margin-top: 15px;">
                            <?php echo htmlspecialchars($c_sub); ?>
                        </p>
                    </div>

                    <div style="display: flex; flex-direction: column; gap: 20px;">
                        <div style="display: flex; align-items: flex-start; gap: 16px;">
                            <div style="width: 44px; height: 44px; border-radius: 50%; background: rgba(200, 162, 118, 0.15); color: var(--color-accent); display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                <i class="fa-solid fa-location-dot"></i>
                            </div>
                            <div>
                                <span style="font-size: 0.7rem; text-transform: uppercase; letter-spacing: 1px; color: var(--color-accent); font-weight: 700; display: block;">Flagship Showroom</span>
                                <a href="<?php echo htmlspecialchars($c_map); ?>" target="_blank" rel="noopener" style="font-size: 0.95rem; color: #FFFFFF; font-weight: 500; text-decoration: underline; text-underline-offset: 3px;" title="Open in Google Maps"><?php echo htmlspecialchars($c_addr); ?></a>
                            </div>
                        </div>

                        <div style="display: flex; align-items: flex-start; gap: 16px;">
                            <div style="width: 44px; height: 44px; border-radius: 50%; background: rgba(200, 162, 118, 0.15); color: var(--color-accent); display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                <i class="fa-solid fa-envelope"></i>
                            </div>
                            <div>
                                <span style="font-size: 0.7rem; text-transform: uppercase; letter-spacing: 1px; color: var(--color-accent); font-weight: 700; display: block;">Private Inquiries</span>
                                <span style="font-size: 0.95rem; color: #FFFFFF; font-weight: 500;"><?php echo htmlspecialchars($c_email); ?></span>
                            </div>
                        </div>

                        <div style="display: flex; align-items: flex-start; gap: 16px;">
                            <div style="width: 44px; height: 44px; border-radius: 50%; background: rgba(200, 162, 118, 0.15); color: var(--color-accent); display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                <i class="fa-brands fa-whatsapp"></i>
                            </div>
                            <div>
                                <span style="font-size: 0.7rem; text-transform: uppercase; letter-spacing: 1px; color: var(--color-accent); font-weight: 700; display: block;">WhatsApp Concierge</span>
                                <span style="font-size: 0.95rem; color: #FFFFFF; font-weight: 500;"><?php echo htmlspecialchars($c_phone); ?></span>
                            </div>
                        </div>
                    </div>

                    <div style="display: flex; gap: 12px;">
                        <a href="<?php echo htmlspecialchars($c_insta); ?>" aria-label="Instagram" style="width: 40px; height: 40px; border-radius: 50%; background: rgba(255,255,255,0.08); color: #FFFFFF; display: flex; align-items: center; justify-content: center; transition: all 0.3s ease;"><i class="fa-brands fa-instagram"></i></a>
                        <a href="<?php echo htmlspecialchars($c_fb); ?>" aria-label="Facebook" style="width: 40px; height: 40px; border-radius: 50%; background: rgba(255,255,255,0.08); color: #FFFFFF; display: flex; align-items: center; justify-content: center; transition: all 0.3s ease;"><i class="fa-brands fa-facebook-f"></i></a>
                        <a href="<?php echo htmlspecialchars($c_map); ?>" target="_blank" rel="noopener" aria-label="Google Maps Location" title="Open Google Maps Location" style="width: 40px; height: 40px; border-radius: 50%; background: rgba(255,255,255,0.08); color: #FFFFFF; display: flex; align-items: center; justify-content: center; transition: all 0.3s ease;"><i class="fa-solid fa-map-location-dot"></i></a>
                    </div>
                </div>

                <!-- Right Panel: Interactive Form -->
                <div style="background: rgba(255, 255, 255, 0.04); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 24px; padding: 40px;">
                    <form class="contact-form" id="main-contact-form" style="display: flex; flex-direction: column; gap: 20px;">
                        <input type="hidden" name="product_title" value="General Contact">
                        
                        <div>
                            <label style="display: block; font-size: 0.72rem; text-transform: uppercase; letter-spacing: 1px; color: var(--color-accent); font-weight: 700; margin-bottom: 8px;">Full Name</label>
                            <input type="text" id="contact-name" name="name" class="concierge-input" placeholder="e.g. Lord Alexander Wright" required autocomplete="name">
                        </div>
                        
                        <div>
                            <label style="display: block; font-size: 0.72rem; text-transform: uppercase; letter-spacing: 1px; color: var(--color-accent); font-weight: 700; margin-bottom: 8px;">Email Address</label>
                            <input type="email" id="contact-email" name="email" class="concierge-input" placeholder="e.g. alexander@residence.com" required autocomplete="email">
                        </div>
                        
                        <div>
                            <label style="display: block; font-size: 0.72rem; text-transform: uppercase; letter-spacing: 1px; color: var(--color-accent); font-weight: 700; margin-bottom: 8px;">WhatsApp / Phone</label>
                            <input type="tel" id="contact-whatsapp" name="whatsapp" class="concierge-input" placeholder="+91 98765 43210" required>
                        </div>
                        
                        <div>
                            <label style="display: block; font-size: 0.72rem; text-transform: uppercase; letter-spacing: 1px; color: var(--color-accent); font-weight: 700; margin-bottom: 8px;">Bespoke Message</label>
                            <textarea id="contact-message" name="message" class="concierge-input" rows="4" placeholder="Detail your project dimensions, leather swatches, or timeline requirements..." required></textarea>
                        </div>
                        
                        <button type="submit" style="width: 100%; padding: 16px; border-radius: 40px; background: var(--color-accent); color: var(--color-primary); border: none; font-weight: 700; font-size: 0.85rem; letter-spacing: 1px; text-transform: uppercase; cursor: pointer; transition: all 0.3s ease; display: flex; align-items: center; justify-content: center; gap: 10px; margin-top: 10px;">
                            <span class="magnetic-btn-text">Submit Request &nbsp; <i class="fa-solid fa-paper-plane"></i></span>
                        </button>
                    </form>
                </div>

            </div>
        </div>

    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const contactForm = document.getElementById('main-contact-form');
    if (contactForm) {
        contactForm.addEventListener('submit', (e) => {
            e.preventDefault();
            
            const submitBtn = contactForm.querySelector('button[type="submit"]');
            const btnText = submitBtn ? submitBtn.querySelector('.magnetic-btn-text') : null;
            const originalText = btnText ? btnText.innerHTML : 'Submit Request';
            
            if (submitBtn && btnText) {
                submitBtn.disabled = true;
                btnText.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Transmitting...';
            }
            
            const formData = new FormData(contactForm);
            
            fetch('submit-inquiry.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Thank you for connecting with OXO. Our Senior Concierge will reach out via Email/WhatsApp within 2 hours.');
                    contactForm.reset();
                } else {
                    alert(data.error || 'Failed to send message.');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred. Please try again.');
            })
            .finally(() => {
                if (submitBtn && btnText) {
                    submitBtn.disabled = false;
                    btnText.innerHTML = originalText;
                }
            });
        });
    }
});
</script>

