<!-- Contact Section -->
<section id="contact">
    <div class="container">
        <div class="contact-grid">
            <div class="contact-info-panel">
                <div class="contact-heading-group">
                    <span class="section-tag">Get in Touch</span>
                    <h2 class="title-medium">Connect <br>With <span class="title-serif">OXO</span></h2>
                    <p style="color: var(--color-gray); font-size: 0.95rem; margin-top: 15px; max-width: 320px;">
                        Have questions about configurations, dimensions, or custom leather selections? Our concierge is here to assist.
                    </p>
                </div>
                
                <div class="contact-details">
                    <div class="contact-detail-item">
                        <span class="contact-detail-label">Showroom Location</span>
                        <span class="contact-detail-value">84 luxury avenue, suite 900, mumbai, india</span>
                    </div>
                    
                    <div class="contact-detail-item">
                        <span class="contact-detail-label">Inquiries & Custom Orders</span>
                        <span class="contact-detail-value">concierge@oxo.design</span>
                    </div>
                    
                    <div class="contact-detail-item">
                        <span class="contact-detail-label">Phone Concierge</span>
                        <span class="contact-detail-value">+91 (22) 8800-4400</span>
                    </div>
                </div>
                
                <div class="social-links">
                    <a href="#" class="social-link" aria-label="Facebook"><i class="fa-brands fa-facebook-f"></i></a>
                    <a href="#" class="social-link" aria-label="Instagram"><i class="fa-brands fa-instagram"></i></a>
                    <a href="#" class="social-link" aria-label="Pinterest"><i class="fa-brands fa-pinterest-p"></i></a>
                </div>
            </div>
            
            <div class="contact-form-wrapper">
                <form class="contact-form" id="main-contact-form">
                    <input type="hidden" name="product_title" value="General Contact">
                    
                    <div class="form-group">
                        <input type="text" id="contact-name" name="name" class="form-input" placeholder=" " required autocomplete="name">
                        <label for="contact-name" class="form-label">Full Name</label>
                    </div>
                    
                    <div class="form-group">
                        <input type="email" id="contact-email" name="email" class="form-input" placeholder=" " required autocomplete="email">
                        <label for="contact-email" class="form-label">Email Address</label>
                    </div>
                    
                    <div class="form-group">
                        <input type="tel" id="contact-whatsapp" name="whatsapp" class="form-input" placeholder=" " required>
                        <label for="contact-whatsapp" class="form-label">WhatsApp Number</label>
                    </div>
                    
                    <div class="form-group">
                        <textarea id="contact-message" name="message" class="form-input" placeholder=" " required></textarea>
                        <label for="contact-message" class="form-label">Message details</label>
                    </div>
                    
                    <div style="margin-top: 10px;">
                        <button type="submit" class="magnetic-btn">
                            <span class="magnetic-btn-text">Send Message</span>
                        </button>
                    </div>
                </form>
            </div>
            
            <script>
            document.addEventListener('DOMContentLoaded', () => {
                const contactForm = document.getElementById('main-contact-form');
                if (contactForm) {
                    contactForm.addEventListener('submit', (e) => {
                        e.preventDefault();
                        
                        const submitBtn = contactForm.querySelector('button[type="submit"]');
                        const btnText = submitBtn ? submitBtn.querySelector('.magnetic-btn-text') : null;
                        const originalText = btnText ? btnText.innerHTML : 'Send Message';
                        
                        if (submitBtn && btnText) {
                            submitBtn.disabled = true;
                            btnText.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Sending...';
                        }
                        
                        const formData = new FormData(contactForm);
                        
                        fetch('submit-inquiry.php', {
                            method: 'POST',
                            body: formData
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                alert('Thank you for contacting OXO. Our concierge will reach out via Email or WhatsApp shortly.');
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
        </div>
    </div>
</section>
