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
});
