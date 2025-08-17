$(document).ready(function() {
    // Handle quantity update via AJAX
    $('.quantity-update-btn').on('click', function(e) {
        e.preventDefault();
        
        var $btn = $(this);
        var $row = $btn.closest('tr');
        var cartId = $btn.data('cart-id');
        var $quantityInput = $row.find('.quantity-input');
        var newQuantity = parseInt($quantityInput.val());
        
        // Validate quantity
        if (newQuantity < 1) {
            alert('Quantity must be at least 1');
            return;
        }
        
        // Disable button and show loading
        $btn.prop('disabled', true).text('Updating...');
        
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
                    $('.total-amount').text(response.total_amount);
                    $('.total-quantity').text(response.total_quantity);
                    
                    // Update quantity input with validated value
                    $quantityInput.val(response.quantity);
                    
                    // Show success message briefly
                    showNotification('Quantity updated successfully', 'success');
                } else {
                    showNotification(response.message || 'Error updating quantity', 'error');
                }
            },
            error: function() {
                showNotification('Error connecting to server', 'error');
            },
            complete: function() {
                // Re-enable button
                $btn.prop('disabled', false).text('Update');
            }
        });
    });
    
    // Handle quantity increment/decrement buttons
    $('.quantity-btn').on('click', function() {
        var $btn = $(this);
        var $row = $btn.closest('tr');
        var $quantityInput = $row.find('.quantity-input');
        var currentQuantity = parseInt($quantityInput.val());
        
        if ($btn.hasClass('quantity-plus')) {
            $quantityInput.val(currentQuantity + 1);
        } else if ($btn.hasClass('quantity-minus')) {
            if (currentQuantity > 1) {
                $quantityInput.val(currentQuantity - 1);
            }
        }
        
        // Trigger update
        $row.find('.quantity-update-btn').click();
    });
    
    // Handle quantity input change
    $('.quantity-input').on('change', function() {
        var $input = $(this);
        var $row = $input.closest('tr');
        var value = parseInt($input.val());
        
        if (value < 1) {
            $input.val(1);
        }
        
        // Trigger update
        $row.find('.quantity-update-btn').click();
    });
    
    // Handle remove item via AJAX
    $('.remove-item-btn').on('click', function(e) {
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
                        // Remove row from table
                        $row.fadeOut(300, function() {
                            $(this).remove();
                            
                            // Update cart totals
                            $('.total-amount').text(response.total_amount);
                            $('.total-quantity').text(response.total_quantity);
                            
                            // Check if cart is empty
                            if (response.cart_empty) {
                                location.reload(); // Reload to show empty cart message
                            }
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
    
    // Notification function
    function showNotification(message, type) {
        var $notification = $('<div class="cart-notification ' + type + '">' + message + '</div>');
        $('body').append($notification);
        
        setTimeout(function() {
            $notification.fadeOut(300, function() {
                $(this).remove();
            });
        }, 3000);
    }
});
