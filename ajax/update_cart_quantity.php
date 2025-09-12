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
$requested_qty = intval($_POST['quantity']);
$user_id = $_SESSION['userid'] ?? '';

// Verify the cart item belongs to the current user and fetch product stock/price
$sql = "SELECT c.c_id, c.quantity AS cart_quantity, c.product_id, p.quantity AS prod_stock, p.price
        FROM cart c
        JOIN product p ON c.product_id = p.p_id
        WHERE c.c_id = '$cart_id' AND c.user_id = '$user_id' LIMIT 1";
$res = mysqli_query($conn, $sql);
if (!$res || mysqli_num_rows($res) == 0) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access or item not found']);
    exit();
}

$row = mysqli_fetch_assoc($res);
$prod_stock = isset($row['prod_stock']) ? intval($row['prod_stock']) : 0;
$price = isset($row['price']) ? floatval($row['price']) : 0.0;

// Decide final quantity
if ($prod_stock <= 0) {
    // Product out of stock - remove cart item
    $del = mysqli_query($conn, "DELETE FROM cart WHERE c_id = '$cart_id'");
    // Recalculate totals after removal
    $total_query = mysqli_query($conn, "SELECT COALESCE(SUM(c.quantity * p.price),0) as total_amount, COALESCE(SUM(c.quantity),0) as total_quantity
                                       FROM cart c JOIN product p ON c.product_id = p.p_id WHERE c.user_id = '$user_id'");
    $totals = mysqli_fetch_assoc($total_query);

    echo json_encode([
        'success' => true,
        'message' => 'Product is out of stock and was removed from your cart',
        'quantity' => 0,
        'subtotal' => number_format(0, 2),
        'total_amount' => number_format($totals['total_amount'], 2),
        'total_quantity' => intval($totals['total_quantity'])
    ]);
    exit();
}

$final_qty = max(1, $requested_qty);
if ($final_qty > $prod_stock) $final_qty = $prod_stock;

// Update the cart with the clamped quantity
$update_sql = "UPDATE cart SET quantity = '$final_qty' WHERE c_id = '$cart_id' AND user_id = '$user_id'";
if (!mysqli_query($conn, $update_sql)) {
    echo json_encode(['success' => false, 'message' => 'Database error while updating quantity']);
    exit();
}

// Calculate subtotal for this item
$subtotal = $price * $final_qty;

// Get new cart totals (authoritative)
$total_query = mysqli_query($conn, "SELECT COALESCE(SUM(c.quantity * p.price),0) as total_amount, COALESCE(SUM(c.quantity),0) as total_quantity
                                   FROM cart c JOIN product p ON c.product_id = p.p_id WHERE c.user_id = '$user_id'");
$totals = mysqli_fetch_assoc($total_query);

echo json_encode([
    'success' => true,
    'message' => 'Quantity updated successfully',
    'quantity' => intval($final_qty),
    'subtotal' => number_format($subtotal, 2),
    'total_amount' => number_format($totals['total_amount'], 2),
    'total_quantity' => intval($totals['total_quantity']),
    'stock' => $prod_stock
]);

exit();
?>
