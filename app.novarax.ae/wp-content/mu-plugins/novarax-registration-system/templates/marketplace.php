
<?php
// Get available modules
global $wpdb;
$modules = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}novarax_modules WHERE status = 'active' ORDER BY id ASC");

// Check if user has selected modules (in session or cart)
$cart_items = WC()->cart->get_cart();
?>

<div class="novarax-marketplace-wrapper">
    <div class="novarax-marketplace-container">
        
        <!-- Header Section -->
        <div class="marketplace-header">
            <div class="header-content">
                <h1>Build Your Perfect Workspace</h1>
                <p>Choose the modules you need. Start with a free trial, upgrade anytime.</p>
            </div>
            
            <!-- Cart Preview -->
            <div class="cart-preview" id="cart-preview">
                <button class="cart-button" id="cart-toggle">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/>
                        <path d="M1 1h4l2.68 13.39a2 2 0 002 1.61h9.72a2 2 0 002-1.61L23 6H6"/>
                    </svg>
                    <span class="cart-count"><?php echo count($cart_items); ?></span>
                </button>
                
                <div class="cart-dropdown" id="cart-dropdown" style="display: none;">
                    <div class="cart-header">
                        <h3>Your Selected Apps</h3>
                        <span class="cart-close" id="cart-close">×</span>
                    </div>
                    <div class="cart-items" id="cart-items-list">
                        <?php if (empty($cart_items)): ?>
                            <p class="empty-cart">Your cart is empty. Add some apps!</p>
                        <?php else: ?>
                            <?php foreach ($cart_items as $item): ?>
                                <div class="cart-item">
                                    <span><?php echo esc_html($item['data']->get_name()); ?></span>
                                    <button class="remove-item" data-key="<?php echo $item['key']; ?>">×</button>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    <div class="cart-footer">
                        <a href="<?php echo wc_get_cart_url(); ?>" class="btn-secondary">View Cart</a>
                        <a href="<?php echo wc_get_checkout_url(); ?>" class="btn-primary">Checkout</a>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Modules Grid -->
        <div class="modules-grid">
            <?php foreach ($modules as $module): ?>
                <?php
                $product = wc_get_product($module->product_id);
                if (!$product) continue;
                
                $in_cart = false;
                foreach ($cart_items as $item) {
                    if ($item['product_id'] == $module->product_id) {
                        $in_cart = true;
                        break;
                    }
                }
                ?>
                
                <div class="module-card" data-module-id="<?php echo $module->id; ?>">
                    <div class="module-icon">
                        <?php if ($module->icon_url): ?>
                            <img src="<?php echo esc_url($module->icon_url); ?>" alt="<?php echo esc_attr($module->module_name); ?>">
                        <?php else: ?>
                            <svg width="48" height="48" viewBox="0 0 48 48" fill="none">
                                <rect width="48" height="48" rx="12" fill="url(#gradient-<?php echo $module->id; ?>)"/>
                                <text x="24" y="32" text-anchor="middle" fill="white" font-size="20" font-weight="bold">
                                    <?php echo strtoupper(substr($module->module_name, 0, 2)); ?>
                                </text>
                                <defs>
                                    <linearGradient id="gradient-<?php echo $module->id; ?>" x1="0" y1="0" x2="48" y2="48">
                                        <stop offset="0%" stop-color="#3ECFAB"/>
                                        <stop offset="100%" stop-color="#1E88E5"/>
                                    </linearGradient>
                                </defs>
                            </svg>
                        <?php endif; ?>
                    </div>
                    
                    <div class="module-content">
                        <h3><?php echo esc_html($module->module_name); ?></h3>
                        <p class="module-description"><?php echo esc_html($module->description); ?></p>
                        
                        <div class="module-features">
                            <?php
                            $features = array(
                                'Unlimited users',
                                'Cloud storage',
                                'Mobile app',
                                '24/7 support'
                            );
                            foreach (array_slice($features, 0, 3) as $feature):
                            ?>
                                <span class="feature-tag">
                                    <svg width="12" height="12" viewBox="0 0 12 12" fill="currentColor">
                                        <path d="M10.293 3.293a1 1 0 011.414 1.414l-5 5a1 1 0 01-1.414 0l-2-2a1 1 0 011.414-1.414L6 7.586l4.293-4.293z"/>
                                    </svg>
                                    <?php echo esc_html($feature); ?>
                                </span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <div class="module-footer">
                        <div class="module-price">
                            <span class="price-amount"><?php echo $product->get_price_html(); ?></span>
                            <span class="price-period">/month</span>
                        </div>
                        
                        <?php if ($in_cart): ?>
                            <button class="btn-added" disabled>
                                <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor">
                                    <path d="M13.854 3.646a.5.5 0 010 .708l-7 7a.5.5 0 01-.708 0l-3.5-3.5a.5.5 0 11.708-.708L6.5 10.293l6.646-6.647a.5.5 0 01.708 0z"/>
                                </svg>
                                Added to Cart
                            </button>
                        <?php else: ?>
                            <button class="btn-add-to-cart" data-product-id="<?php echo $module->product_id; ?>">
                                Add to Cart
                            </button>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Optional: Badge for popular/recommended -->
                    <?php if ($module->id == 1): // Featured module ?>
                        <div class="module-badge">Most Popular</div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Empty State -->
        <?php if (empty($modules)): ?>
            <div class="empty-state">
                <svg width="120" height="120" viewBox="0 0 120 120" fill="none">
                    <circle cx="60" cy="60" r="60" fill="#1A1A1A"/>
                    <path d="M40 60h40M60 40v40" stroke="#3ECFAB" stroke-width="4" stroke-linecap="round"/>
                </svg>
                <h2>No Apps Available Yet</h2>
                <p>We're preparing amazing apps for you. Check back soon!</p>
            </div>
        <?php endif; ?>
        
    </div>
</div>

<style>
/* Marketplace Styles */
.novarax-marketplace-wrapper {
    min-height: 100vh;
    background: #0A0A0A;
    color: #FFFFFF;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    padding: 40px 20px;
}

.novarax-marketplace-container {
    max-width: 1400px;
    margin: 0 auto;
}

/* Header */
.marketplace-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 60px;
    gap: 20px;
}

.header-content h1 {
    font-size: 48px;
    font-weight: 700;
    margin-bottom: 12px;
    background: linear-gradient(135deg, #FFFFFF 0%, #A0A0A0 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
}

.header-content p {
    font-size: 18px;
    color: #A0A0A0;
}

/* Cart Preview */
.cart-preview {
    position: relative;
}

.cart-button {
    position: relative;
    background: #1A1A1A;
    border: 1px solid #2A2A2A;
    border-radius: 12px;
    padding: 12px 16px;
    color: #FFFFFF;
    cursor: pointer;
    transition: all 0.2s;
}

.cart-button:hover {
    background: #2A2A2A;
    border-color: #3ECFAB;
}

.cart-count {
    position: absolute;
    top: -8px;
    right: -8px;
    background: #3ECFAB;
    color: #0A0A0A;
    font-size: 12px;
    font-weight: 700;
    min-width: 20px;
    height: 20px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 0 6px;
}

.cart-dropdown {
    position: absolute;
    top: calc(100% + 12px);
    right: 0;
    background: #1A1A1A;
    border: 1px solid #2A2A2A;
    border-radius: 12px;
    width: 320px;
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.5);
    z-index: 1000;
}

.cart-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px;
    border-bottom: 1px solid #2A2A2A;
}

.cart-header h3 {
    font-size: 16px;
    font-weight: 600;
    margin: 0;
}

.cart-close {
    font-size: 24px;
    color: #A0A0A0;
    cursor: pointer;
    line-height: 1;
}

.cart-close:hover {
    color: #FFFFFF;
}

.cart-items {
    max-height: 300px;
    overflow-y: auto;
    padding: 20px;
}

.empty-cart {
    text-align: center;
    color: #707070;
    padding: 20px 0;
}

.cart-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px;
    background: #0A0A0A;
    border-radius: 8px;
    margin-bottom: 8px;
}

.remove-item {
    background: none;
    border: none;
    color: #A0A0A0;
    font-size: 20px;
    cursor: pointer;
    padding: 0 8px;
}

.remove-item:hover {
    color: #FF4444;
}

.cart-footer {
    display: flex;
    gap: 12px;
    padding: 20px;
    border-top: 1px solid #2A2A2A;
}

/* Modules Grid */
.modules-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
    gap: 24px;
}

.module-card {
    background: linear-gradient(135deg, #1A1A1A 0%, #0F0F0F 100%);
    border: 1px solid #2A2A2A;
    border-radius: 16px;
    padding: 24px;
    display: flex;
    flex-direction: column;
    gap: 20px;
    transition: all 0.3s;
    position: relative;
    overflow: hidden;
}

.module-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: radial-gradient(circle at top right, rgba(62, 207, 171, 0.05), transparent);
    opacity: 0;
    transition: opacity 0.3s;
}

.module-card:hover::before {
    opacity: 1;
}

.module-card:hover {
    border-color: #3ECFAB;
    transform: translateY(-4px);
    box-shadow: 0 20px 40px rgba(62, 207, 171, 0.1);
}

.module-icon {
    width: 64px;
    height: 64px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.module-icon img {
    width: 100%;
    height: 100%;
    object-fit: contain;
}

.module-content h3 {
    font-size: 24px;
    font-weight: 700;
    margin: 0 0 8px 0;
}

.module-description {
    color: #A0A0A0;
    font-size: 14px;
    line-height: 1.6;
    margin: 0;
}

.module-features {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}

.feature-tag {
    display: flex;
    align-items: center;
    gap: 4px;
    font-size: 12px;
    color: #3ECFAB;
    background: rgba(62, 207, 171, 0.1);
    padding: 4px 10px;
    border-radius: 20px;
}

.module-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: auto;
}

.module-price {
    display: flex;
    flex-direction: column;
}

.price-amount {
    font-size: 28px;
    font-weight: 700;
    color: #FFFFFF;
}

.price-period {
    font-size: 14px;
    color: #707070;
}

.btn-add-to-cart,
.btn-added {
    background: #3ECFAB;
    color: #0A0A0A;
    border: none;
    border-radius: 8px;
    padding: 10px 20px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
}

.btn-add-to-cart:hover {
    background: #2FB596;
    transform: translateY(-1px);
}

.btn-added {
    background: #2A2A2A;
    color: #3ECFAB;
    cursor: default;
    display: flex;
    align-items: center;
    gap: 6px;
}

.module-badge {
    position: absolute;
    top: 20px;
    right: -32px;
    background: #3ECFAB;
    color: #0A0A0A;
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    padding: 4px 36px;
    transform: rotate(45deg);
}

/* Buttons */
.btn-primary, .btn-secondary {
    flex: 1;
    padding: 10px 16px;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 600;
    text-align: center;
    text-decoration: none;
    transition: all 0.2s;
}

.btn-primary {
    background: #3ECFAB;
    color: #0A0A0A;
}

.btn-primary:hover {
    background: #2FB596;
}

.btn-secondary {
    background: transparent;
    border: 1px solid #2A2A2A;
    color: #FFFFFF;
}

.btn-secondary:hover {
    border-color: #3ECFAB;
    color: #3ECFAB;
}

/* Empty State */
.empty-state {
    text-align: center;
    padding: 100px 20px;
}

.empty-state h2 {
    font-size: 32px;
    margin: 24px 0 12px;
}

.empty-state p {
    color: #A0A0A0;
    font-size: 16px;
}

@media (max-width: 768px) {
    .marketplace-header {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .header-content h1 {
        font-size: 32px;
    }
    
    .modules-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    // Cart toggle
    $('#cart-toggle').on('click', function() {
        $('#cart-dropdown').fadeToggle(200);
    });
    
    $('#cart-close').on('click', function() {
        $('#cart-dropdown').fadeOut(200);
    });
    
    // Close cart when clicking outside
    $(document).on('click', function(e) {
        if (!$(e.target).closest('.cart-preview').length) {
            $('#cart-dropdown').fadeOut(200);
        }
    });
    
    // Add to cart
    $('.btn-add-to-cart').on('click', function() {
        const btn = $(this);
        const productId = btn.data('product-id');
        const card = btn.closest('.module-card');
        
        btn.prop('disabled', true).text('Adding...');
        
        $.ajax({
            url: '<?php echo admin_url('admin-ajax.php'); ?>',
            type: 'POST',
            data: {
                action: 'novarax_add_to_cart',
                product_id: productId,
                nonce: '<?php echo wp_create_nonce('novarax_marketplace'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    // Update button
                    btn.replaceWith('<button class="btn-added" disabled><svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor"><path d="M13.854 3.646a.5.5 0 010 .708l-7 7a.5.5 0 01-.708 0l-3.5-3.5a.5.5 0 11.708-.708L6.5 10.293l6.646-6.647a.5.5 0 01.708 0z"/></svg>Added to Cart</button>');
                    
                    // Update cart count
                    $('.cart-count').text(response.data.cart_count);
                    
                    // Animation
                    card.css('transform', 'scale(1.02)');
                    setTimeout(() => card.css('transform', ''), 200);
                } else {
                    btn.prop('disabled', false).text('Add to Cart');
                    alert(response.data.message || 'Failed to add to cart');
                }
            },
            error: function() {
                btn.prop('disabled', false).text('Add to Cart');
                alert('An error occurred. Please try again.');
            }
        });
    });
});
</script>