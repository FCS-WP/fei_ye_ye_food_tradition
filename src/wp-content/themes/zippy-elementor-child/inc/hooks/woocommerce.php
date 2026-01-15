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
    if (isset($fields['billing']['billing_phone'])) {
        $fields['billing']['billing_phone']['required'] = true;
        $fields['billing']['billing_phone']['label'] = __('Phone');
    }
    return $fields;
});


add_action("woocommerce_checkout_before_customer_details", "parse_pickup_form");
function parse_pickup_form()
{
?>
<h3 class="spb-title">Store</h3>

<div class="spb-fixed-info">
    <p>
        <strong>Location</strong><br>
        Chinatown Complex<br>
        335 Smith St, #02-177, Singapore 050335
    </p>
    <p>
        <strong>Pick up time</strong><br>
        9AM – 8PM
    </p>
</div>
<div class="spb-section">
    <h3 class="spb-label">Pick up date</h3>
    <input type="text" id="pickup_date" name="pickup_date" placeholder="Select date" required>
    <small class="spb-note">
        *All Orders must be made at least 1 day in advance
    </small>
</div>
<?php
}

add_filter('woocommerce_cart_item_price', 'show_base_price_of_product', 10, 3);
function show_base_price_of_product($price_html, $cart_item, $cart_item_key)
{
    // pr($cart_item);
    if (!isset($cart_item['data'])) {
        return $price_html;
    }
    $base_price = $cart_item['data']->get_regular_price();
    return wc_price($base_price);
}