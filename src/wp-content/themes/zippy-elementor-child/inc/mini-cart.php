<?php

/**
 * Shortcode: [spb_cart_icon]
 * Icon + badge + slide mini cart panel
 */
add_shortcode('spb_cart_icon', function () {

    if (! function_exists('WC')) {
        return '';
    }

    ob_start();
?>
    <div class="spb-cart-wrapper">

        <!-- Cart Icon -->
        <button class="spb-cart-toggle" type="button">
            <span class="spb-cart-icon"><img width="24" height="24" src="/wp-content/uploads/2026/01/bag-shopping-solid-full.svg" alt="mini-cart"></span>
            <span class="spb-cart-badge">
                <?php echo WC()->cart->get_cart_contents_count(); ?>
            </span>
        </button>

        <!-- Overlay -->
        <div class="spb-cart-overlay"></div>

        <!-- Slide Panel -->
        <div class="spb-cart-panel">
            <div class="spb-cart-header">
            <h3>Your Cart</h3>
            <button class="spb-cart-close">&times;</button>
            </div>

            <div class="spb-cart-content">
                <?php woocommerce_mini_cart(); ?>
            </div>
        </div>

    </div>
<?php
    return ob_get_clean();
});


//
add_filter('woocommerce_add_to_cart_fragments', function ($fragments) {

    // Mini cart content
    ob_start();
    woocommerce_mini_cart();
    $fragments['.spb-cart-content'] = ob_get_clean();

    // Cart badge
    ob_start();
    echo WC()->cart->get_cart_contents_count();
    $fragments['.spb-cart-badge'] = ob_get_clean();

    return $fragments;
});
