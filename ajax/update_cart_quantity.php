<?php
session_start();
include '../lib/connection.php';

header('Content-Type: application/json');

// Check if user is authenticated
if (!isset($_SESSION['auth']) || $_SESSION['auth'] != 1) {
    echo json_encode(['success' => false, 'message' => 'User not authenticated']);
    exit();
}

// Validate input
if (!isset($_POST['cart_id']) || !isset($_POST['quantity'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit();
}

$cart_id = intval($_POST['cart_id']);
$quantity = max(1, intval($_POST['quantity']));
$user_id = $_SESSION['userid'] ?? '';

// Verify the cart item belongs to the current user
$verify_query = mysqli_query($conn, "SELECT c_id FROM cart WHERE c_id = '$cart_id' AND user_id = '$user_id'");
if (mysqli_num_rows($verify_query) == 0) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Update quantity
$update_query = mysqli_query($conn, "UPDATE cart SET quantity = '$quantity' WHERE c_id = '$cart_id'");
if (!$update_query) {
    echo json_encode(['success' => false, 'message' => 'Database error']);
    exit();
}

// Get updated cart information
$cart_info = mysqli_query($conn, "SELECT c.quantity, p.price, p.name 
                                  FROM cart c 
                                  JOIN product p ON c.product_id = p.p_id 
                                  WHERE c.c_id = '$cart_id'");
$cart_data = mysqli_fetch_assoc($cart_info);

// Calculate new totals
$subtotal = $cart_data['price'] * $cart_data['quantity'];

// Get new cart totals
$total_query = mysqli_query($conn, "SELECT SUM(c.quantity * p.price) as total_amount, SUM(c.quantity) as total_quantity
                                   FROM cart c 
                                   JOIN product p ON c.product_id = p.p_id 
                                   WHERE c.user_id = '$user_id'");
$totals = mysqli_fetch_assoc($total_query);

echo json_encode([
    'success' => true,
    'message' => 'Quantity updated successfully',
    'subtotal' => number_format($subtotal, 2),
    'total_amount' => number_format($totals['total_amount'], 2),
    'total_quantity' => $totals['total_quantity'],
    'quantity' => $quantity
]);

exit();
?>
