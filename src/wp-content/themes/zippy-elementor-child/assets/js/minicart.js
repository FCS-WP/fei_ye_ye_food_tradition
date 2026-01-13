jQuery(function ($) {
  // Open cart
  $(document).on("click", ".spb-cart-toggle", function (e) {
    if (
      $("body").hasClass("woocommerce-cart") ||
      $("body").hasClass("woocommerce-checkout")
    ) {
      window.location.reload();
      return;
    }

    $(".spb-cart-panel").addClass("open");
    $(".spb-cart-overlay").addClass("open");
  });

  // Close cart
  $(document).on("click", ".spb-cart-close, .spb-cart-overlay", function () {
    $(".spb-cart-panel").removeClass("open");
    $(".spb-cart-overlay").removeClass("open");
  });

  // Add on Quantity
  $(document).on("change", ".spb-addon input[type='checkbox']", function () {
    const qty_input = $(this).closest(".spb-addon").find(".spb-addon-qty");
    if ($(this).is(":checked")) {
      qty_input.show();
      if (!qty_input.val()) qty_input.val(1);
    } else {
      qty_input.hide().val("");
    }
  });
});
