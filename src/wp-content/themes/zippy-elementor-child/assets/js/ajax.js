jQuery(function ($) {
  /* ========================
   * Cached selectors
   * ======================== */
  const $variationForm = $(".variations_form");
  if (!$variationForm.length) return;

  const $variationSelects = $variationForm.find("select");
  const productId = $variationForm.data("product_id");
  const attrName = $variationSelects.first().attr("id");

  /* ========================
   * Helpers
   * ======================== */
  const addLoading = () => {
    $(".elementor-add-to-cart").addClass("c_loader");
    $variationSelects.prop("disabled", true);
  };

  const hideLoading = () => {
    $(".elementor-add-to-cart").removeClass("c_loader");
    $variationSelects.prop("disabled", false);
  };

  const disableOutOfStockAddons = (addons) => {
    addons.forEach(({ product_id, is_in_stock }) => {
      if (is_in_stock) return;

      const $addon = $(`.spb-addon[data-product-id="${product_id}"]`);
      $addon.addClass("is-out-of-stock").find("input").prop("disabled", true);
    });
  };

  const disableUnavailableVariations = (variations, filterAttr) => {
    variations.forEach(({ is_in_stock, attributes }) => {
      if (is_in_stock) return;

      $.each(attributes, (key, value) => {
        if (key !== filterAttr) return;

        $variationForm
          .find(`select#${key} option[value="${value}"]`)
          .prop("disabled", true);
      });
    });
  };

  /* ========================
   * Init
   * ======================== */
  addLoading();

  /* ========================
   * AJAX – Add-ons stock
   * ======================== */
  $.post("/wp-admin/admin-ajax.php", {
    action: "get_stock_addons_product",
  }).done((res) => {
    if (!res?.success) return;
    disableOutOfStockAddons(res.data);
  });

  /* ========================
   * AJAX – Variations by attribute
   * ======================== */
  $.ajax({
    url: "/wp-admin/admin-ajax.php",
    type: "GET",
    data: {
      action: "get_variations_by_attribute",
      product_id: productId,
      attribute: attrName,
    },
  })
    .done((res) => {
      if (!res?.success) return console.log(res.data);
      disableUnavailableVariations(res.data, attrName);
    })
    .fail((err) => console.error(err))
    .always(hideLoading);
});
