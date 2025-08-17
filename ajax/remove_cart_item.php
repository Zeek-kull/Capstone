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
if (!isset($_POST['cart_id'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit();
}

$cart_id = intval($_POST['cart_id']);
$user_id = $_SESSION['userid'] ?? '';

// Verify the cart item belongs to the current user
$verify_query = mysqli_query($conn, "SELECT c_id FROM cart WHERE c_id = '$cart_id' AND user_id = '$user_id'");
if (mysqli_num_rows($verify_query) == 0) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Remove item from cart
$delete_query = mysqli_query($conn, "DELETE FROM cart WHERE c_id = '$cart_id'");
if (!$delete_query) {
    echo json_encode(['success' => false, 'message' => 'Database error']);
    exit();
}

// Get new cart totals
$total_query = mysqli_query($conn, "SELECT SUM(c.quantity * p.price) as total_amount, SUM(c.quantity) as total_quantity
                                   FROM cart c 
                                   JOIN product p ON c.product_id = p.p_id 
                                   WHERE c.user_id = '$user_id'");
$totals = mysqli_fetch_assoc($total_query);

// Check if cart is empty
$cart_count_query = mysqli_query($conn, "SELECT COUNT(*) as count FROM cart WHERE user_id = '$user_id'");
$cart_count = mysqli_fetch_assoc($cart_count_query);

echo json_encode([
    'success' => true,
    'message' => 'Item removed successfully',
    'total_amount' => number_format($totals['total_amount'] ?? 0, 2),
    'total_quantity' => $totals['total_quantity'] ?? 0,
    'cart_empty' => ($cart_count['count'] == 0)
]);

exit();
?>
