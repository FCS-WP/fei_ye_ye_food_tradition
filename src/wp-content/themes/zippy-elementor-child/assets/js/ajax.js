$(document).ready(function ($) {
  // Define Variable
  const variation_form = $(".variations_form");

  // Event
  let attr_name = $(".variations_form select").attr("id"),
    product_id = variation_form.data("product_id");

  /* Ajax for checking stock for add ons products */
  $.ajax({
    url: "/wp-admin/admin-ajax.php",
    type: "POST",
    data: {
      action: "get_stock_addons_product",
    },
    success: function (res) {
      if (!res.success) return;
      res.data.forEach(function (item) {
        if (!item.is_in_stock) {
          const addon = $(
            '.spb-addon[data-product-id="' + item.product_id + '"]'
          );
          addon.find("input").prop("disabled", true);
          addon.addClass("is-out-of-stock");
        }
      });
    },
  });

  $.ajax({
    url: "/wp-admin/admin-ajax.php",
    type: "GET",
    data: {
      action: "get_variations_by_attribute",
      product_id: product_id,
      attribute: attr_name,
    },

    success: function (response) {
      if (response.success) {
        getAvailableOptions(response.data, attr_name);
      } else {
        console.log(response.data);
      }
      hideLoading();
    },
    error: function (error) {
      console.log(error);
      hideLoading();
    },
  });

  // Function
  function getAvailableOptions(variations, filter) {
    variations.forEach((item) => {
      if (!item.is_in_stock) {
        $.each(item.attributes, function (key, value) {
          if (key == filter) {
            $(`.variations_form select#${key} option[value="${value}"]`).attr(
              "disabled",
              "disabled"
            );
          }
        });
      }
    });
  }
  // Loading
  function addLoading() {
    $(".elementor-add-to-cart").addClass("c_loader");
    variation_form.find("select").prop("disabled", true);
  }

  function hideLoading() {
    $(".elementor-add-to-cart").removeClass("c_loader");
    variation_form.find("select").prop("disabled", false);
  }
});

// $(`.variations_form select option`).removeAttr("disabled");
