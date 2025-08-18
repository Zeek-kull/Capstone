$(document).ready(function() {
    // Handle quantity update via AJAX
    function updateQuantity(cartId, newQuantity, $row) {
        // Validate quantity
        if (newQuantity < 1) {
            showNotification('Quantity must be at least 1', 'error');
            return;
        }
        
        // Show loading state
        $row.find('.quantity-input').prop('disabled', true);
        
        // Send AJAX request
        $.ajax({
            url: 'ajax/update_cart_quantity.php',
            type: 'POST',
            data: {
                cart_id: cartId,
                quantity: newQuantity
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    // Update subtotal for this item
                    $row.find('.total-price').text('₱' + response.subtotal);
                    
                    // Update cart totals
                    $('.total-amount-display').text('₱' + response.total_amount);
                    $('.total-quantity-display').text(response.total_quantity);
                    
                    // Update quantity input with validated value
                    $row.find('.quantity-input').val(response.quantity);
                    
                    // Show success message
                    showNotification('Quantity updated successfully', 'success');
                } else {
                    showNotification(response.message || 'Error updating quantity', 'error');
                }
            },
            error: function() {
                showNotification('Error connecting to server', 'error');
            },
            complete: function() {
                // Re-enable input
                $row.find('.quantity-input').prop('disabled', false);
            }
        });
    }
    
    // Handle quantity increment/decrement buttons
    $(document).on('click', '.quantity-btn', function() {
        var $btn = $(this);
        var $row = $btn.closest('tr');
        var $quantityInput = $row.find('.quantity-input');
        var currentQuantity = parseInt($quantityInput.val());
        var cartId = $btn.data('cart-id');
        
        if ($btn.hasClass('quantity-plus')) {
            var newQuantity = currentQuantity + 1;
            $quantityInput.val(newQuantity);
            updateQuantity(cartId, newQuantity, $row);
        } else if ($btn.hasClass('quantity-minus')) {
            if (currentQuantity > 1) {
                var newQuantity = currentQuantity - 1;
                $quantityInput.val(newQuantity);
                updateQuantity(cartId, newQuantity, $row);
            }
        }
    });
    
    // Handle quantity input change
    $(document).on('change', '.quantity-input', function() {
        var $input = $(this);
        var $row = $input.closest('tr');
        var value = parseInt($input.val());
        var cartId = $input.data('cart-id');
        
        if (value < 1) {
            $input.val(1);
            value = 1;
        }
        
        updateQuantity(cartId, value, $row);
    });
    
    // Handle remove item via AJAX
    $(document).on('click', '.remove-item-btn', function(e) {
        e.preventDefault();
        
        var $btn = $(this);
        var cartId = $btn.data('cart-id');
        var $row = $btn.closest('tr');
        
        if (confirm('Are you sure you want to remove this item?')) {
            // Send AJAX request to remove item
            $.ajax({
                url: 'ajax/remove_cart_item.php',
                type: 'POST',
                data: {
                    cart_id: cartId
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        // Remove row from table with animation
                        $row.fadeOut(300, function() {
                            $(this).remove();
                            
                            // Update cart totals
                            $('.total-amount-display').text('₱' + response.total_amount);
                            $('.total-quantity-display').text(response.total_quantity);
                            
                            // Check if cart is empty
                            if (response.cart_empty) {
                                location.reload(); // Reload to show empty cart message
                            }
                            
                            // Update cart count for order button
                            updateOrderButtonState();
                        });
                        
                        showNotification('Item removed successfully', 'success');
                    } else {
                        showNotification(response.message || 'Error removing item', 'error');
                    }
                },
                error: function() {
                    showNotification('Error connecting to server', 'error');
                }
            });
        }
    });
    
    // Update order button state based on cart items
    function updateOrderButtonState() {
        var cartItems = $('tbody tr[data-cart-id]').length;
        
        if (cartItems === 0) {
            $('#orderButton').prop('disabled', true)
                .css('background-color', '#ddd')
                .text('Cart is Empty');
        } else {
            // Check if form is valid
            var address = $('input[name="address"]').val();
            var mobnumber = $('input[name="mobnumber"]').val();
            var payment_method = $('select[name="payment_method"]').val();
            var phoneValid = /^[0-9]{11}$/.test(mobnumber);
            
            if (address && mobnumber && payment_method && phoneValid) {
                $('#orderButton').prop('disabled', false)
                    .css('background-color', '#2ecc71');
            } else {
                $('#orderButton').prop('disabled', true)
                    .css('background-color', '#ddd');
            }
        }
    }
    
    // Notification function
    function showNotification(message, type) {
        var $notification = $('<div class="cart-notification ' + type + '">' + message + '</div>');
        $('body').append($notification);
        
        // Position notification
        $notification.css({
            position: 'fixed',
            top: '20px',
            right: '20px',
            padding: '15px 20px',
            borderRadius: '4px',
            color: '#fff',
            fontSize: '14px',
            zIndex: 9999,
            boxShadow: '0 2px 10px rgba(0,0,0,0.1)'
        });
        
        // Set background color based on type
        if (type === 'success') {
            $notification.css('background-color', '#28a745');
        } else if (type === 'error') {
            $notification.css('background-color', '#dc3545');
        }
        
        // Animate in
        $notification.hide().fadeIn(300);
        
        // Remove after 3 seconds
        setTimeout(function() {
            $notification.fadeOut(300, function() {
                $(this).remove();
            });
        }, 3000);
    }
    
    // Add CSS for loading states
    var style = $('<style>');
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
    $('head').append(style);
});
