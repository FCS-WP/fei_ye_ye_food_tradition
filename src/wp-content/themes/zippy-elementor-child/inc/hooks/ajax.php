<?php

/* Check addons on stock */

add_action('wp_ajax_get_stock_addons_product', 'get_stock_addons_product');
add_action('wp_ajax_nopriv_get_stock_addons_product', 'get_stock_addons_product');

function get_stock_addons_product()
{
    $product = wc_get_products([
        'category' => ['add-ons'],
        'status'   => 'publish',
        'limit'    => -1,
    ]);
    $final_result = [];

    foreach ($product as $item) {
        $final_result[] = [
            'product_id' => $item->get_id(),
            'is_in_stock' => $item->is_in_stock()
        ];
    }
    wp_send_json_success($final_result);
}

add_action('wp_ajax_get_variations_by_attribute', 'get_variations_by_attribute');
add_action('wp_ajax_nopriv_get_variations_by_attribute', 'get_variations_by_attribute');

function get_variations_by_attribute()
{
    $product_id = absint($_GET['product_id']);
    $attribute  = sanitize_text_field($_GET['attribute']);

    if (!$product_id || !$attribute) {
        wp_send_json_error('Invalid parameters');
    }

    $product = wc_get_product($product_id);
    if (!$product || $product->get_type() !== 'variable') {
        wp_send_json_error('Invalid product');
    }
    $variations = [];

    foreach ($product->get_children() as $variation_id) {
        $variation = wc_get_product($variation_id);
        if (!$variation) continue;

        $attrs = $variation->get_attributes();

        $variations[] = [
            'variation_id' => $variation_id,
            'attributes' => $attrs,
            'is_in_stock' => $variation->is_in_stock(),
        ];
    }
    wp_send_json(
        [
            'success' => true,
            'data'    => $variations,
        ],
        200,
        JSON_UNESCAPED_UNICODE
    );
}