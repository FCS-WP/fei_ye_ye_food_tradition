import "./spb-flatpickr";
import "./minicart";

$(document).ready(function ($) {
$('form.variations_form').on('found_variation', function (event, variation) {

    // variation.price_html = HTML giá chuẩn WooCommerce
    $(this)
      .closest('.product')
      .find('p.price')
      .html(variation.price_html);

  });
});
