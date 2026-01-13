<?php

if (!defined('ABSPATH')) exit;

/* 1. ENQUEUE CSS & JS*/
add_action('wp_enqueue_scripts', function () {

    if (!is_product()) return;
    // Flatpickr
    wp_enqueue_style(
        'flatpickr-css',
        'https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css'
    );

    wp_enqueue_script(
        'flatpickr-js',
        'https://cdn.jsdelivr.net/npm/flatpickr',
        [],
        null,
        true
    );
});

/* 2. FRONTEND BOOKING UI */
add_action('woocommerce_before_add_to_cart_button', function () {

    ob_start();

    // Query add-on products
    $addon_products = wc_get_products([
        'status'   => 'publish',
        'limit'    => -1,
        'category' => ['add-ons'],
    ]);
?>

<div class="spb-booking-box">

    <h3 class="spb-title">Store Pick Up</h3>

    <div class="spb-fixed-info">
        <p>
            <strong>Location</strong><br>
            Chinatown Complex<br>
            335 Smith St, #02-177, Singapore 050335
        </p>
        <p>
            <strong>Pick Up Time</strong><br>
            9AM – 8PM
        </p>
    </div>

    <div class="spb-section">
        <label class="spb-label">Pick Up Date</label>
        <input type="text" id="pickup_date" name="pickup_date" placeholder="Select date" required>
        <small class="spb-note">
            *All orders must be made 1 day in advance
        </small>
    </div>
    <p class="spb-walkin-note">
        *Items are still available for same day walk-in purchase
    </p>
    <?php if (!empty($addon_products)) : ?>
    <div class="spb-section">
        <label class="spb-label">Add-ons</label>

        <?php foreach ($addon_products as $addon) : ?>
        <label class="spb-addon" data-product-id="<?php echo esc_attr($addon->get_id()); ?>">
            <input type="checkbox" name="addons[]" value="<?php echo esc_attr($addon->get_id()); ?>">
            <?php echo esc_html($addon->get_name()); ?>
            <span class="spb-addon-price">
                (+<?php echo wc_price($addon->get_price()); ?>)
            </span>
            <input class="spb-addon-qty" name="addons_qty[]" type="number" min=1 max=2>
        </label>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<?php
    echo ob_get_clean();
});


/*3. VALIDATION */
add_filter('woocommerce_add_to_cart_validation', function ($passed) {
    if (empty($_POST['pickup_date'])) {
        wc_add_notice('Please select a pick up date.', 'error');
        return false;
    }
    return $passed;
});

/* 4. SAVE DATA TO CART */
add_filter('woocommerce_add_cart_item_data', function ($cart_item_data) {

    if (!empty($_POST['pickup_date'])) {
        $cart_item_data['pickup_date'] = sanitize_text_field($_POST['pickup_date']);
    }

    // if (!empty($_POST['addons'])) {
    //     $cart_item_data['addons'] = array_map('sanitize_text_field', $_POST['addons']);
    // }

    if (!empty($_POST['addons_qty']) && !empty($_POST['addons'])) {
        $raw_qty_list = array_map('sanitize_text_field', $_POST['addons_qty']);
        $raw_addons_list = array_map('sanitize_text_field', $_POST['addons']);
        $final_qty_list = array_values(array_filter($raw_qty_list));
        $final_addons = array_combine($raw_addons_list, $final_qty_list);
        $cart_item_data['addons'] = $final_addons;
    }
    return $cart_item_data;
});

add_filter(
    'woocommerce_get_cart_item_from_session',
    function ($cart_item, $values) {
        // pr($cart_item);
        if (isset($values['base_price'])) {
            $cart_item['base_price'] = $values['base_price'];
        }

        if (isset($values['addon_price_total'])) {
            $cart_item['addon_price_total'] = $values['addon_price_total'];
        }
        return $cart_item;
    },
    10,
    2
);

add_filter('woocommerce_widget_cart_item_quantity', function ($html, $cart_item, $cart_item_key) {

    if (empty($cart_item['addons'])) return $html;

    $qty = (int) $cart_item['quantity'];

    $base = (float) $cart_item['data']->get_regular_price();
    if ($cart_item['data']->is_on_sale()) {
        $base = (float) $cart_item['data']->get_sale_price();
    }

    $addon_total = 0;
    foreach ($cart_item['addons'] as $addon_id => $addon_qty) {
        $addon = wc_get_product($addon_id);
        if ($addon && $addon_qty > 0) {
            $addon_total += (float) $addon->get_price() * (int) $addon_qty;
        }
    }

    $final_price = $base + $addon_total;

    return sprintf('%d × %s', $qty, wc_price($final_price));
}, 10, 3);


/* 7.5. ADD ADD-ON PRICE TO CART ITEM (FIXED) */
add_action('woocommerce_before_calculate_totals', function ($cart) {
    if (is_admin() && !defined('DOING_AJAX')) return;
    $final = 0;
    foreach ($cart->get_cart() as $cart_item) {

        if (empty($cart_item['addons'])) continue;

        // Save base price once
        if (!isset($cart_item['base_price'])) {
            $cart_item['base_price'] = $cart_item['data']->get_regular_price();
        }

        $addon_total = 0;

        foreach ($cart_item['addons'] as $addon_id => $addon_qty) {
            $addon = wc_get_product($addon_id);
            if ($addon) {
                $addon_total += (float) $addon->get_price() * (int)$addon_qty;
            }
        }

        $final_price = $cart_item['base_price'] + $addon_total;
        $final = $final + $final_price;
        // SET PRICE
        $cart_item['data']->set_price($final_price);

        // SAVE FINAL PRICE FOR MINI CART
        $cart_item['addon_price_total'] = $addon_total;
    }
});

add_filter(
    'woocommerce_cart_item_subtotal',
    function ($subtotal, $cart_item, $cart_item_key) {

        if (!empty($cart_item['addon_price_total'])) {
            return wc_price(
                $cart_item['data']->get_price() * $cart_item['quantity']
            );
        }
        return $subtotal;
    },
    10,
    3
);



/* 5. DISPLAY CART / CHECKOUT */
add_filter('woocommerce_get_item_data', function ($item_data, $cart_item) {

    if (!empty($cart_item['pickup_date'])) {
        $item_data[] = [
            'name'  => 'Pick Up Date',
            'value' => esc_html($cart_item['pickup_date'])
        ];
    }

    if (!empty($cart_item['addons'])) {

        foreach ($cart_item['addons'] as $addon_id => $addon_qty) {

            $addon = wc_get_product($addon_id);
            if (!$addon && $addon_qty > 0) continue;

            $item_data[] = [
                'name'  => 'Add-on',
                'value' => $addon->get_name() . ' (' . wc_price($addon->get_price()) . ')' . ' - ' . intval($addon_qty)
            ];
        }
    }

    return $item_data;
}, 10, 2);


/* 6. SAVE TO ORDER (ADMIN) */
add_action('woocommerce_checkout_create_order_line_item', function ($item, $cart_item_key, $values) {

    if (!empty($values['pickup_date'])) {
        $item->add_meta_data('Pick Up Date', $values['pickup_date'], true);
    }

    if (!empty($values['addons']) && is_array($values['addons'])) {

        $addons = [];

        foreach ($values['addons'] as $addon_id => $addon_qty) {
            $addon = wc_get_product($addon_id);
            if ($addon && $addon_qty > 0) {
                $addons[] = $addon->get_name() . ' (' . wc_price($addon->get_price()) . ')' . ' x ' . intval($addon_qty);
            }
        }

        if (!empty($addons)) {
            $item->add_meta_data('Add-ons', implode(', ', $addons), true);
        }
    }
}, 10, 3);


/* 7. THANK YOU PAGE */
add_action('woocommerce_thankyou', function ($order_id) {

    if (!$order_id) return;

    $order = wc_get_order($order_id);

    echo '<h3>Pick Up Information</h3>';

    foreach ($order->get_items() as $item) {
        echo '<p><strong>Pick Up Date:</strong> ' . esc_html($item->get_meta('Pick Up Date')) . '</p>';
        echo '<p><strong>Add-ons:</strong> ' . esc_html($item->get_meta('Add-ons')) . '</p>';
    }
}, 20);

/* DEBUG */
// add_action('woocommerce_before_cart', 'wp_kama_woocommerce_before_cart_action');
// function wp_kama_woocommerce_before_cart_action()
// {
//     $cart = WC()->cart->get_cart();
//     pr($cart);
//     die;
// }