<?php

add_action(
  'woocommerce_order_list_table_restrict_manage_orders',
  'add_hpos_pickup_date_filter_ui'
);

function add_hpos_pickup_date_filter_ui()
{

  $from = $_GET['pickup_from'] ?? '';
  $to   = $_GET['pickup_to'] ?? '';
?>
  Pickup from:
  <input
    type="date"
    name="pickup_from"
    value="<?php echo esc_attr($from); ?>"
    placeholder="Pickup from" />
  Pickup to:

  <input
    type="date"
    name="pickup_to"
    value="<?php echo esc_attr($to); ?>"
    placeholder="Pickup to" />
<?php
}


add_filter(
  'woocommerce_order_list_table_prepare_items_query_args',
  'filter_hpos_orders_by_pickup_date',
  10,
  2
);

function filter_hpos_orders_by_pickup_date($args)
{

  if (empty($_GET['pickup_from']) && empty($_GET['pickup_to'])) {
    return $args;
  }

  $from = $_GET['pickup_from'] ?? '';
  $to   = $_GET['pickup_to'] ?? '';

  $args['meta_query'] = $args['meta_query'] ?? [];

  if ($from && $to) {
    $args['meta_query'][] = [
      'key'     => '_pickup_date',
      'value'   => [$from, $to],
      'compare' => 'BETWEEN',
      'type'    => 'DATE',
    ];
  } elseif ($from) {
    $args['meta_query'][] = [
      'key'     => '_pickup_date',
      'value'   => $from,
      'compare' => '>=',
      'type'    => 'DATE',
    ];
  } elseif ($to) {
    $args['meta_query'][] = [
      'key'     => '_pickup_date',
      'value'   => $to,
      'compare' => '<=',
      'type'    => 'DATE',
    ];
  }

  return $args;
}

add_filter(
  'manage_woocommerce_page_wc-orders_columns',
  'add_pickup_date_order_column'
);

function add_pickup_date_order_column($columns)
{

  $new_columns = [];

  foreach ($columns as $key => $label) {
    $new_columns[$key] = $label;

    if ($key == 'order_date') {
      $new_columns['pickup_date'] = __('Pickup Date', 'your-textdomain');
    }
  }

  return $new_columns;
}


add_action(
  'manage_woocommerce_page_wc-orders_custom_column',
  'render_pickup_date_order_column',
  10,
  2
);

function render_pickup_date_order_column($column, $order)
{

  if ($column !== 'pickup_date') {
    return;
  }

  $pickup_date = $order->get_meta('_pickup_date');

  if (!$pickup_date) {
    echo '—';
    return;
  }

  $date = DateTime::createFromFormat('Y-m-d', $pickup_date);

  echo $date ? esc_html($date->format('d/m/Y')) : esc_html($pickup_date);
}
