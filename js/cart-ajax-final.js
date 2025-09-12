$(document).ready(function () {
  // Handle quantity update via AJAX
  function updateQuantity(cartId, newQuantity, $row) {
    // Validate quantity
    if (newQuantity < 1) {
      showNotification("Quantity must be at least 1", "error");
      return;
    }

    // Show loading state
    $row.find(".quantity-input").prop("disabled", true);

    // Send AJAX request
    $.ajax({
      url: "ajax/update_cart_quantity.php",
      type: "POST",
      data: {
        cart_id: cartId,
        quantity: newQuantity,
      },
      dataType: "json",
      success: function (response) {
        if (response.success) {
          // Update cart totals (authoritative)
          if (typeof response.total_amount !== 'undefined') {
            $(".total-amount-display").text("₱" + response.total_amount);
          }
          if (typeof response.total_quantity !== 'undefined') {
            $(".total-quantity-display").text(response.total_quantity);
          }

          // If server removed the item because stock is 0
          if (parseInt(response.quantity) === 0) {
            // Remove row from table with animation
            $row.fadeOut(300, function () {
              $(this).remove();

              // Update order button state and totals handlers
              updateOrderButtonState();
              if (typeof window.computeSelectedTotals === 'function') {
                try { window.computeSelectedTotals(); } catch (e) { }
              }
              if ($('#orderForm').length) { $('#orderForm').trigger('change'); }

              // If cart is empty, reload to show empty cart message
              var cartItems = $("tbody tr[data-cart-id]").length;
              if (cartItems === 0) {
                location.reload();
              }
            });

            if (typeof showFlash === 'function') showFlash('danger', response.message || 'Item removed: product is out of stock', 5000);

          } else {
            // Update quantity input with validated value and update row subtotal
            $row.find('.quantity-input').val(response.quantity).data('stock', response.stock);
            if (typeof response.subtotal !== 'undefined') {
              $row.find('.total-price').text('₱' + response.subtotal);
            }

            // If server clamped the quantity down (requested larger than stock), show a warning
            if (newQuantity > parseInt(response.quantity) && typeof response.stock !== 'undefined') {
              if (typeof showFlash === 'function') showFlash('warning', 'Requested quantity reduced to available stock: ' + response.stock, 3500);
            }

            // Update computed totals/state
            if (typeof window.computeSelectedTotals === 'function') {
              try { window.computeSelectedTotals(); } catch (e) { /* ignore */ }
            }
            if ($('#orderForm').length) { $('#orderForm').trigger('change'); }
          }

        } else {
          // server signaled failure — show as a toast
          if (typeof showFlash === 'function') {
            showFlash('danger', response.message || 'Error updating quantity', 4000);
          } else {
            showNotification(response.message || 'Error updating quantity', 'error');
          }
        }
      },
      error: function () {
        if (typeof showFlash === 'function') showFlash('danger', 'Error connecting to server', 4000);
        else showNotification('Error connecting to server', 'error');
      },
      complete: function () {
        // Re-enable input
        $row.find('.quantity-input').prop('disabled', false);
      },
    });
  }

  // Handle quantity increment/decrement buttons
  $(document).on("click", ".quantity-btn", function () {
    var $btn = $(this);
    var $row = $btn.closest("tr");
    var $quantityInput = $row.find(".quantity-input");
    var currentQuantity = parseInt($quantityInput.val());
    var cartId = $btn.data("cart-id");
    var stock = parseInt($quantityInput.data('stock')) || 0;
    if ($btn.hasClass("quantity-plus")) {
      var newQuantity = currentQuantity + 1;
      if (stock > 0 && newQuantity > stock) {
        // clamp and warn
        newQuantity = stock;
        $quantityInput.val(newQuantity);
        if (typeof showFlash === 'function') showFlash('warning', 'Cannot increase beyond available stock (' + stock + ')', 2500);
        // if already at stock, don't send duplicate update
        if (currentQuantity >= newQuantity) return;
      } else {
        $quantityInput.val(newQuantity);
      }
      updateQuantity(cartId, newQuantity, $row);
    } else if ($btn.hasClass("quantity-minus")) {
      if (currentQuantity > 1) {
        var newQuantity = currentQuantity - 1;
        $quantityInput.val(newQuantity);
        updateQuantity(cartId, newQuantity, $row);
      }
    }
  });

  // Handle quantity input change
  $(document).on("change", ".quantity-input", function () {
    var $input = $(this);
    var $row = $input.closest("tr");
    var value = parseInt($input.val());
    var cartId = $input.data("cart-id");
    var stock = parseInt($input.data('stock')) || 0;
    if (value < 1 || isNaN(value)) {
      value = 1;
    }
    if (stock > 0 && value > stock) {
      value = stock;
      $input.val(value);
      if (typeof showFlash === 'function') showFlash('warning', 'Quantity adjusted to available stock: ' + stock, 3000);
    }
    updateQuantity(cartId, value, $row);
  });

  // Listen for custom cartQtyFinal event dispatched from vanilla JS when clamping is applied
  $(document).on('cartQtyFinal', '.quantity-input', function (e) {
    var $input = $(this);
    var $row = $input.closest('tr');
    var value = parseInt($input.val()) || 1;
    var cartId = $input.data('cart-id');
    updateQuantity(cartId, value, $row);
  });

  // Handle remove item via AJAX
  $(document).on("click", ".remove-item-btn", function (e) {
    e.preventDefault();

    var $btn = $(this);
    var cartId = $btn.data("cart-id");
    var $row = $btn.closest("tr");

    if (confirm("Are you sure you want to remove this item?")) {
      // Send AJAX request to remove item
      $.ajax({
        url: "ajax/remove_cart_item.php",
        type: "POST",
        data: {
          cart_id: cartId,
        },
        dataType: "json",
        success: function (response) {
          if (response.success) {
            // Remove row from table with animation
            $row.fadeOut(300, function () {
              $(this).remove();

              // Update cart totals
              $(".total-amount-display").text("₱" + response.total_amount);
              $(".total-quantity-display").text(response.total_quantity);

              // Check if cart is empty
              if (response.cart_empty) {
                location.reload(); // Reload to show empty cart message
              }

              // Update cart count for order button
                updateOrderButtonState();
                // Recompute selected totals and update order form handlers
                if (typeof window.computeSelectedTotals === 'function') {
                  try { window.computeSelectedTotals(); } catch (e) { }
                }
                if ($('#orderForm').length) { $('#orderForm').trigger('change'); }
            });

            showNotification("Item removed successfully", "success");
          } else {
            showNotification(
              response.message || "Error removing item",
              "error"
            );
          }
        },
        error: function () {
          showNotification("Error connecting to server", "error");
        },
      });
    }
  });

  // Update order button state based on cart items
  function updateOrderButtonState() {
    var cartItems = $("tbody tr[data-cart-id]").length;

    if (cartItems === 0) {
      $("#orderButton")
        .prop("disabled", true)
        .css("background-color", "#ddd")
        .text("Cart is Empty");
    } else {
      // Check if form is valid
      var address = $('input[name="address"]').val();
      var mobnumber = $('input[name="mobnumber"]').val();
      var payment_method = $('select[name="payment_method"]').val();
      var phoneValid = /^[0-9]{11}$/.test(mobnumber);

      if (address && mobnumber && payment_method && phoneValid) {
        $("#orderButton")
          .prop("disabled", false)
          .css("background-color", "#2ecc71");
      } else {
        $("#orderButton")
          .prop("disabled", true)
          .css("background-color", "#ddd");
      }
    }
  }

  // Notification function
  function showNotification(message, type) {
    var $notification = $(
      '<div class="cart-notification ' + type + '">' + message + "</div>"
    );
    $("body").append($notification);

    // Position notification
    $notification.css({
      position: "fixed",
      top: "20px",
      right: "20px",
      padding: "15px 20px",
      borderRadius: "4px",
      color: "#fff",
      fontSize: "14px",
      zIndex: 9999,
      boxShadow: "0 2px 10px rgba(0,0,0,0.1)",
    });

    // Set background color based on type
    if (type === "success") {
      $notification.css("background-color", "#28a745");
    } else if (type === "error") {
      $notification.css("background-color", "#dc3545");
    }

    // Animate in
    $notification.hide().fadeIn(300);

    // Remove after 3 seconds
    setTimeout(function () {
      $notification.fadeOut(300, function () {
        $(this).remove();
      });
    }, 3000);
  }

  // Add CSS for loading states
  var style = $("<style>");
  style.text(`
        .quantity-input:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        
        .quantity-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        .cart-notification {
            animation: slideIn 0.3s ease-out;
        }
        
        @keyframes slideIn {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
    `);
  $("head").append(style);
});
