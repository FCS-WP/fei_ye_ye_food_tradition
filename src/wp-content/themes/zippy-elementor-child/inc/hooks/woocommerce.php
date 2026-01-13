<?php
add_filter('woocommerce_get_stock_html', '__return_empty_string', 10, 2);

// Limit 10 yusheng food 
add_filter(
    'woocommerce_add_to_cart_validation',
    function ($passed, $product_id, $qty) {

        $cart = WC()->cart;
        if (!$cart) return $passed;

        $total_qty = 0;

        foreach ($cart->get_cart() as $item) {
            $total_qty += (int) $item['quantity'];
        }

        if ($total_qty + $qty > 10) {
            wc_add_notice(
                __('You can only buy up to 10 Yusheng food per order.', 'woocommerce'),
                'error'
            );
            return false;
        }

        return $passed;
    },
    10,
    3
);

add_filter(
    'woocommerce_update_cart_validation',
    function ($passed, $cart_item_key, $values, $new_qty) {

        if (!isset($_POST['cart']) || !is_array($_POST['cart'])) {
            return $passed;
        }

        $total_qty = 0;

        foreach ($_POST['cart'] as $posted_item) {
            if (!isset($posted_item['qty'])) continue;
            $total_qty += (int) $posted_item['qty'];
        }

        if ($total_qty > 10) {
            wc_add_notice(
                __('You can only buy up to 10 Yusheng food per order.', 'woocommerce'),
                'error'
            );
            return false;
        }

        return $passed;
    },
    10,
    4
);

// Add information before payment
add_action('woocommerce_review_order_before_payment', function () {
?>
<div class="custom-checkout-warning">
    <strong>Terms and conditions</strong>
    <ul>
        <li>No cancellation of order after payment </li>
        <li>No change of date of collection </li>
        <li>No refund for non collection of order. </li>
    </ul>
</div>
<?php
});

// Simplize the billing detail
add_filter('woocommerce_checkout_fields', function ($fields) {

    $allowed = [
        'billing_first_name',
        'billing_last_name',
        'billing_phone',
        'billing_email',
    ];

    foreach ($fields['billing'] as $key => $field) {
        if (!in_array($key, $allowed, true)) {
            unset($fields['billing'][$key]);
        }
    }

    return $fields;
});