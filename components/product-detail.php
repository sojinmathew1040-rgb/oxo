<!-- Product Quick View Modal -->
<div class="modal-overlay" id="modal-overlay">
    <div class="modal-container">
        <button class="modal-close" id="modal-close" aria-label="Close Modal"><i class="fa-solid fa-xmark"></i></button>
        
        <div class="modal-grid">
            <div class="modal-visual">
                <img src="" alt="Product Showcase" id="modal-product-img">
            </div>
            
            <div class="modal-info">
                <span class="modal-category">Category</span>
                <h3 class="modal-title">Product Title</h3>
                <span class="modal-price">₹0</span>
                
                <p class="modal-desc">
                    Product description details go here.
                </p>
                
                <div class="modal-details">
                    <span style="font-weight: 600; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.05em; color: var(--color-accent-green);">Specifications</span>
                    <p style="opacity: 0.8; margin-top: 5px; line-height: 1.5;"></p>
                </div>
                
                <div class="modal-actions">
                    <button class="magnetic-btn" style="flex-grow: 1; text-align: center; padding: 14px 28px;" data-action="add-to-cart" data-id="">
                        <span class="magnetic-btn-text">Add to Cart</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
