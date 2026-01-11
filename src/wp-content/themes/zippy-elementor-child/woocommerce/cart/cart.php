<?php

/**
 * Cart Page
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/cart/cart.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://docs.woocommerce.com/document/template-structure/
 * @package WooCommerce/Templates
 * @version 3.8.0
 */

defined('ABSPATH') || exit;

do_action('woocommerce_before_cart'); ?>
<div class="zippy-cart pivoo-cart row">
    <div class="col-12 col-md-8">
        <form class="woocommerce-cart-form" action="<?php echo esc_url(wc_get_cart_url()); ?>" method="post">
            <?php do_action('woocommerce_before_cart_table'); ?>
            <div class="custom-table cart woocommerce-cart-form__contents">
                <?php do_action('woocommerce_before_cart_contents'); ?>
                <div class="cth header-table-cart row gy-3 align-items-center mb-4 d-none d-xl-flex">
                    <div class="ctc text-center product-thumbnail col-2">Product Image</div>
                    <div class="ctc product-name col-6">Product Name</div>
                    <div class="ctc text-center product-quantity col-2">Quantity</div>
                    <div class="ctc text-center product-subtotal col-2">Subtotal</div>
                </div>
                <?php
                foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
                    $_product = apply_filters('woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key);
                    $product_id = apply_filters('woocommerce_cart_item_product_id', $cart_item['product_id'], $cart_item, $cart_item_key);

                    if ($_product && $_product->exists() && $cart_item['quantity'] > 0 && apply_filters('woocommerce_cart_item_visible', true, $cart_item, $cart_item_key)) {
                        $product_permalink = apply_filters('woocommerce_cart_item_permalink', $_product->is_visible() ? $_product->get_permalink($cart_item) : '', $cart_item, $cart_item_key);
                ?>

                        <div class="ctr body-table-cart row gy-3 gy-md-0 align-items-center py-3 border-bottom cart_item">
                            <div class="ctc product-thumbnail col-2">
                                <?php
                                $thumbnail = apply_filters('woocommerce_cart_item_thumbnail', $_product->get_image(), $cart_item, $cart_item_key);

                                if (!$product_permalink) {
                                    echo maybe_unserialize($thumbnail); // PHPCS: XSS ok.
                                } else {
                                    printf('<a href="%s">%s</a>', esc_url($product_permalink), $thumbnail); // PHPCS: XSS ok.
                                }
                                ?>
                            </div>
                            <div class="ctc product-name col-6">
                                <?php
                                if (!$product_permalink) {
                                    echo wp_kses_post(apply_filters(
                                        'woocommerce_cart_item_name',
                                        $_product->get_name(),
                                        $cart_item,
                                        $cart_item_key
                                    ) . '&nbsp;');
                                } else {
                                    echo wp_kses_post(apply_filters(
                                        'woocommerce_cart_item_name',
                                        sprintf('<a class="name fw-semibold fs-1x" href="%s">%s</a>', esc_url($product_permalink), esc_html($_product->get_name())),
                                        $cart_item,
                                        $cart_item_key
                                    ));
                                }

                                do_action('woocommerce_after_cart_item_name', $cart_item, $cart_item_key);

                                // Meta data.
                                echo wc_get_formatted_cart_item_data($cart_item); // PHPCS: XSS ok.

                                // Backorder notification.
                                if ($_product->backorders_require_notification() && $_product->is_on_backorder($cart_item['quantity'])) {
                                    echo wp_filter_kses(apply_filters('woocommerce_cart_item_backorder_notification', '<p class="backorder_notification">' . esc_html__('Available on backorder', 'emerce') . '</p>', $product_id));
                                }
                                ?>
                                <ul class="m-0" style="list-style-type: disclosure-closed;">
                                    <?php
                                    if (isset($cart_item['folder_link'])) {
                                        echo '<li class="small mb-0"><a class="custom-view-folder fw-bold" href="' . $cart_item['folder_link'] . '" target="_blank">View Photobook Folder</a></li>';
                                    }
                                    if (isset($cart_item['note']) && $cart_item['note'] !== '') {
                                        echo '<li class="small mb-0"> <strong>Note: </strong>' . $cart_item['note'] . '</li>';
                                    }
                                    if (isset($cart_item['country'])) {
                                        echo '<li class="small mb-0"><span class="custom-metadata">Photo ID country: <strong> ' . $cart_item['country'] . ' </strong> </span></li>';
                                    }
                                    if (isset($cart_item['photo_id_url'])) {
                                        echo '<li class="small mb-0"><a class="custom-view-folder fw-bold" href="' . $cart_item['photo_id_url'] . '" target="_blank">View Photo ID</a></li>';
                                    }
                                    ?>
                                </ul>

                                <div class="ctc product-price">
                                    <?php
                                    echo '<span class="d-md-none">Price: </span>';
                                    echo apply_filters('woocommerce_cart_item_price', WC()->cart->get_product_price($_product), $cart_item, $cart_item_key); // PHPCS: XSS ok.
                                    ?>
                                </div>
                            </div>


                            <div class="ctc product-quantity col-2 text-start text-xl-center" data-title="<?php esc_attr_e('Quantity', 'emerce'); ?>">
                                <?php
                                if ($_product->is_sold_individually()) {
                                    $product_quantity = sprintf('1 <input type="hidden" name="cart[%s][qty]" value="1" />', $cart_item_key);
                                } else {
                                    $product_quantity = woocommerce_quantity_input(
                                        array(
                                            'input_name' => "cart[{$cart_item_key}][qty]",
                                            'input_value' => $cart_item['quantity'],
                                            'max_value' => $_product->get_max_purchase_quantity(),
                                            'min_value' => '0',
                                            'product_name' => $_product->get_name(),
                                        ),
                                        $_product,
                                        false
                                    );
                                }

                                echo apply_filters('woocommerce_cart_item_quantity', $product_quantity, $cart_item_key, $cart_item); // PHPCS: XSS ok.
                                ?>
                            </div>

                            <div class="ctc product-subtotal col-2 text-end text-xl-center">
                                <?php
                                echo '<span class="d-md-none">Subtotal: </span>';
                                echo apply_filters('woocommerce_cart_item_subtotal', WC()->cart->get_product_subtotal($_product, $cart_item['quantity']), $cart_item, $cart_item_key); // PHPCS: XSS ok.
                                ?>

                                <p class="product-remove-cart justify-content-xl-center justify-content-end">
                                    <?php
                                    echo apply_filters( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                                        'woocommerce_cart_item_remove_link',
                                        sprintf(
                                            '<a href="%s" class="remove" aria-label="%s" data-product_id="%s" data-product_sku="%s"><i class="zil zi-cross"></i> %s</a>',
                                            esc_url(wc_get_cart_remove_url($cart_item_key)),
                                            esc_html__('Remove this item', 'emerce'),
                                            esc_attr($product_id),
                                            esc_attr($_product->get_sku()),
                                            esc_html__('Remove', 'emerce')
                                        ),
                                        $cart_item_key
                                    );
                                    ?>
                                </p>
                            </div>

                        </div>
                <?php
                    }
                }
                ?>
                <!-- end foreach -->

                <div class="ctr row footer-table-cart">
                    <div class="col-12 actions">
                        <button type="submit" class="button my-3 d-none" name="update_cart"
                            value="<?php esc_attr_e('Update cart', 'emerce'); ?>"><?php esc_html_e('Update cart', 'emerce'); ?></button>

                        <?php do_action('woocommerce_cart_actions'); ?>

                        <?php wp_nonce_field('woocommerce-cart', 'woocommerce-cart-nonce'); ?>
                    </div>
                </div>


                <?php do_action('woocommerce_after_cart_contents'); ?>
            </div>
            <?php do_action('woocommerce_after_cart_table'); ?>
        </form>

        <?php if (wc_coupons_enabled()) { ?>
            <form class="checkout_coupon" method="post">
                <div class="coupon emerce-woo-coupon">
                    <h3 class="widget-title"><?php esc_html_e('Coupon', 'emerce'); ?></h3>
                    <div class="xpc-coupon-flex">
                        <input type="text" name="coupon_code" class="input-text" id="coupon_code" value=""
                            placeholder="<?php esc_attr_e('Coupon code', 'emerce'); ?>" />
                        <input type="submit" class="is-form expand" name="apply_coupon"
                            value="<?php esc_attr_e('Apply coupon', 'emerce'); ?>" />
                    </div>

                    <?php do_action('woocommerce_cart_coupon'); ?>

                </div>
            </form>
        <?php } ?>
    </div>
    <?php do_action('woocommerce_before_cart_collaterals'); ?>

    <div class="cart-collaterals col-12 col-md-4">
        <?php
        /**
         * Cart collaterals hook.
         *
         * @hooked woocommerce_cross_sell_display
         * @hooked woocommerce_cart_totals - 10
         */
        do_action('woocommerce_cart_collaterals');
        ?>


    </div>
</div>
<?php do_action('woocommerce_after_cart'); ?>