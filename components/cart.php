<!-- Shopping Cart Slide-over -->
<div class="drawer" id="cart-drawer">
    <div class="drawer-header">
        <span class="drawer-title">Your Cart</span>
        <button class="drawer-close" aria-label="Close Cart"><i class="fa-solid fa-xmark"></i></button>
    </div>
    
    <div class="drawer-body" id="cart-items-container">
        <!-- Dynmically loaded cart items go here (see assets/js/main.js) -->
        <div class="drawer-empty">
            <i class="fa-solid fa-bag-shopping"></i>
            <p>Your shopping cart is empty.</p>
        </div>
    </div>
    
    <div class="drawer-footer">
        <div class="drawer-summary">
            <span>Subtotal</span>
            <span class="drawer-summary-price" id="cart-subtotal">₹0</span>
        </div>
        
        <p style="font-size: 0.75rem; color: var(--color-gray); margin-bottom: 5px;">
            Shipping, duties, and taxes are calculated at checkout.
        </p>
        
        <button class="magnetic-btn" style="width: 100%; text-align: center;" onclick="alert('Proceeding to checkout with secure gateway...');">
            <span class="magnetic-btn-text">Proceed to Checkout</span>
        </button>
    </div>
</div>
