<?php
add_filter('woocommerce_get_stock_html', '__return_empty_string', 10, 2);