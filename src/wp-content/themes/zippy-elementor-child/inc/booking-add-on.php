<?php

if (!defined('ABSPATH')) exit;

/* 1. ENQUEUE CSS & JS*/
add_action('wp_enqueue_scripts', function () {

    if (!is_checkout()) return;
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
        'limit'    => -1,
        'category' => ['add-ons'],
    ]);
?>

    <div class="spb-booking-box">

        <p class="spb-walkin-note">
            *Items are still available for same day walk-in purchase.</br>
            *Limited to a max of 2 quantities for each add-ons per Yusheng set.
        </p>
        <?php if (!empty($addon_products)) : ?>
            <div class="spb-section">
                <label class="spb-label">Add-ons</label>

                <?php foreach ($addon_products as $addon) : ?>
                    <label class="spb-addon" data-product-id="<?php echo esc_attr($addon->get_id()); ?>">
                        <?php
                        $image_id  = $addon->get_image_id();
                        $image_url = wp_get_attachment_url($image_id);
                        ?>
                        <img src="<?php echo esc_url($image_url); ?>" alt="<?php echo esc_attr($addon->get_name()); ?>">
                        <input type="checkbox" name="addons[]" value="<?php echo esc_attr($addon->get_id()); ?>">
                        <?php
                        echo '<span class ="single-addon">';
                        echo '<span class="addon-cn">' . esc_html(get_field('product_china_name', $addon->get_id())) . '</span>';
                        echo '<span class="addon-desc">' . esc_html($addon->get_short_description()) . '</span>';
                        echo '</span>';
                        ?>
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
add_action('woocommerce_checkout_process', function () {
    if (empty($_POST['pickup_date'])) {
        wc_add_notice('Please select a pick up date.', 'error');
    }
});

/* 4. SAVE DATA TO CART */
add_filter('woocommerce_add_cart_item_data', function ($cart_item_data) {

    if (!empty($_POST['pickup_date'])) {
        $cart_item_data['pickup_date'] = sanitize_text_field($_POST['pickup_date']);
    }

    if (!empty($_POST['addons_qty']) && !empty($_POST['addons'])) {

        $addons     = array_map('sanitize_text_field', $_POST['addons']);
        $quantities = array_map('sanitize_text_field', $_POST['addons_qty']);

        $final_addons = [];

        foreach ($addons as $index => $addon_name) {
            if (
                isset($quantities[$index]) &&
                $quantities[$index] !== '' &&
                (int) $quantities[$index] > 0
            ) {
                $final_addons[$addon_name] = (int) $quantities[$index];
            }
        }

        if (!empty($final_addons)) {
            $cart_item_data['addons'] = $final_addons;
        }
    }

    return $cart_item_data;
});


add_filter(
    'woocommerce_get_cart_item_from_session',
    function ($cart_item, $values) {
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

    $qty = $cart_item['quantity'];
    return '<span class="mini-cart-qty">× ' . $qty . '</span>';
}, 10, 3);


/* 7.5. ADD ADD-ON PRICE TO CART ITEM (FIXED) */
add_action('woocommerce_before_calculate_totals', function ($cart) {
    if (is_admin() && !defined('DOING_AJAX')) return;
    $final = 0;
    foreach ($cart->get_cart() as $cart_item) {
        if (empty($cart_item['addons'])) continue;
        $qty     = max(1, (int) $cart_item['quantity']);
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
        $final_price = $cart_item['base_price'] + ($addon_total / $qty);
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

    if (!empty($cart_item['addons'])) {

        foreach ($cart_item['addons'] as $addon_id => $addon_qty) {

            $addon = wc_get_product($addon_id);
            if (!$addon && $addon_qty > 0) continue;

            $item_data[] = [
                'name'  => 'Add-on',
                'value' => $addon->get_name() . ' (' . wc_price($addon->get_price()) . ')' . ' x ' . intval($addon_qty)
            ];
        }
    }

    return $item_data;
}, 10, 2);


/* 6. SAVE TO ORDER (ADMIN) */
add_action('woocommerce_checkout_create_order_line_item', function ($item, $cart_item_key, $values) {

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

/* 6.1 SAVE PICKUP DATE TO ORDER AND SHOW (ADMIN) */
add_action('woocommerce_checkout_create_order', 'save_pickup_date_to_order_meta', 20, 2);

function save_pickup_date_to_order_meta($order, $data)
{

    if (empty($_POST['pickup_date'])) {
        return;
    }

    $raw_date = sanitize_text_field($_POST['pickup_date']);

    $date = DateTime::createFromFormat('d/m/Y', $raw_date);

    if ($date) {
        $order->update_meta_data(
            '_pickup_date',
            $date->format('Y-m-d')
        );
    }
}

add_action("woocommerce_admin_order_data_after_billing_address", 'display_pickup_date_in_order');
function display_pickup_date_in_order($order)
{
    $pickup_date = $order->get_meta('_pickup_date');
    if (empty($pickup_date)) return;

?>
    <div class="order_data_column">
        <p>
            <strong>Pick Up Date:</strong><br>
            <?php echo esc_html(date('d/m/Y', strtotime($pickup_date)));
            ?>
        </p>
    </div>
    <p style="color:#102870;font-weight:500">Pre-order - Delivery starts from February</p>
<?php

}
/* 7. THANK YOU PAGE */
add_action('woocommerce_thankyou', function ($order_id) {

    if (!$order_id) return;

    $order = wc_get_order($order_id);
    if (!$order) return;

    $order_number = $order->get_order_number();
    $pickup_date  = $order->get_meta('_pickup_date');

    echo '<h3>Pick Up Information</h3>';

    echo '<p><strong>Location:</strong><br>
        Chinatown Complex 335 Smith St, #02-177, Singapore 050335
    </p>';

    echo '<p><strong>Available Time:</strong><br>
        9AM – 8PM
    </p>';

    if ($pickup_date) {
        echo '<p><strong>Pick Up Date:</strong><br>' . date('d/m/Y', strtotime($pickup_date)) . '</p>';
    }
    echo '<hr style="margin:16px 0;">';

    echo '<p><strong>Order Number:</strong> #' . esc_html($order_number) . '</p>';

    echo '<p style="margin-top:12px;">
        Thank you for your order! We look forward to seeing you.
    </p>';
}, 20);

/* 8. SHOW IN EMAIL */
add_action(
    'woocommerce_email_customer_details',
    'show_pickup_date_under_billing_in_email',
    20,
    4
);
function show_pickup_date_under_billing_in_email($order, $sent_to_admin, $plain_text, $email)
{
    $pickup_date = $order->get_meta('_pickup_date');

    if (empty($pickup_date)) return;

    if ($plain_text) return;
?>
    <table cellspacing="0" cellpadding="0" style="width:100%; margin-top:12px;">
        <tr>
            <td style="padding:0; vertical-align:top;">
                <h3 style="margin:0 0 6px;">Location</h3>
                Chinatown Complex 335 Smith St, #02-177, Singapore 050335
            </td>
            <td style="padding:0; vertical-align:top;">
                <h3 style="margin:0 0 6px;">Pick Up Date</h3>

                <?php date('d/m/Y', strtotime($pickup_date)); ?>
            </td>
        </tr>
    </table>

<?php
}
