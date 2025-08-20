<?php
ob_start();
session_start();
include 'header.php';
include 'lib/connection.php';

// Check if user is authenticated
if (!isset($_SESSION['auth']) || $_SESSION['auth'] != 1) {
    header("location:login.php");
    exit();
}

// Order handling
// Get user's address and phone from users table
$user_address = '';
$user_phone = '';
$user_id_for_address = isset($_SESSION['userid']) ? $_SESSION['userid'] : '';
if ($user_id_for_address) {
    $user_info_query = mysqli_query($conn, "SELECT street, zone, barangay, city, province, phone FROM users WHERE id='$user_id_for_address'");
    if ($user_info_query && mysqli_num_rows($user_info_query) > 0) {
        $user_data = mysqli_fetch_assoc($user_info_query);
        
        // Build complete address from user data
        $address_parts = array();
        if (!empty($user_data['street'])) $address_parts[] = $user_data['street'];
        if (!empty($user_data['zone'])) $address_parts[] = 'Zone ' . $user_data['zone'];
        if (!empty($user_data['barangay'])) $address_parts[] = $user_data['barangay'];
        if (!empty($user_data['city'])) $address_parts[] = $user_data['city'];
        if (!empty($user_data['province'])) $address_parts[] = $user_data['province'];
        
        $user_address = implode(', ', $address_parts);
        $user_phone = $user_data['phone'];
    }
}

if (isset($_POST['order_btn'])) {
    // Check if cart is empty
    $user_id = $_SESSION['userid'] ?? '';
    $cart_check = mysqli_query($conn, "SELECT COUNT(*) as cart_count FROM cart WHERE user_id = '$user_id'");
    $cart_data = mysqli_fetch_assoc($cart_check);
    $cart_count = $cart_data['cart_count'] ?? 0;
    
    if ($cart_count == 0) {
        echo "<script>alert('Your cart is empty. Please add items before placing an order.');</script>";
        header("location:shopping-cart.php");
        exit();
    }
    
    $userid = $_POST['user_id'] ?? '';
    $name = $_POST['user_name'] ?? '';
    $number = $_POST['number'] ?? '';
    $address = $_POST['address'] ?? '';
    $mobnumber = $_POST['mobnumber'] ?? '';
    $payment_method = $_POST['payment_method'] ?? ''; // User-selected payment method
    $status = "pending";
    $order_date = date('Y-m-d H:i:s'); // Current date and time

    $cart_query = mysqli_query($conn, "SELECT * FROM `cart` WHERE user_id='$userid'");
    $price_total = 0;
    $product_name = [];

    // Calculate total price and update stock
    if (mysqli_num_rows($cart_query) > 0) {
        while ($product_item = mysqli_fetch_assoc($cart_query)) {
            $product_name[] = $product_item['product_id'] . ' (' . $product_item['quantity'] . ')';
            $product_price = $product_item['price'] * $product_item['quantity'];
            $price_total += $product_price;

            // Update product stock
            $sql = "SELECT * FROM product WHERE p_id = '{$product_item['product_id']}'";
            $result = $conn->query($sql);
            if (mysqli_num_rows($result) > 0) {
                while ($row = mysqli_fetch_assoc($result)) {
                    if ($product_item['quantity'] <= $row['quantity']) {
                        $update_quantity = $row['quantity'] - $product_item['quantity'];
                        $update_query = mysqli_query($conn, "UPDATE `product` SET quantity = '$update_quantity' WHERE p_id = '{$row['p_id']}'");
                    } else {
                        echo "Out of stock: " . $row['name'] . " Quantity: " . $row['quantity'];
                    }
                }
            }
        }

        // Insert order if products are available
        $total_product = implode(', ', $product_name);
        // Use only the correct column 'created_at' for the order date
        $detail_query = mysqli_query($conn, "INSERT INTO `orders`(user_id, name, address, phone, mobnumber, payment_method, totalproduct, totalprice, status, created_at) 
            VALUES('$userid','$name','$address','$number','$mobnumber','$payment_method','$total_product','$price_total','$status', '$order_date')");

        // Empty cart after successful order
        $cart_query1 = mysqli_query($conn, "DELETE FROM `cart` WHERE user_id='$userid'");
        header("location:index.php");
        exit();
    }
}

// Get user's cart with product images
$id = $_SESSION['userid'] ?? '';
$sql = "SELECT cart.*, product.imgname, product.name, product.price 
        FROM cart 
        LEFT JOIN product ON cart.product_id = product.p_id 
        WHERE cart.user_id='$id'";
$result = $conn->query($sql);

// No traditional form handlers needed as we use AJAX
?>

<!DOCTYPE html>
<html lang="zxx">

<head>
    <meta charset="UTF-8">
    <meta name="description" content="Fashi Template">
    <meta name="keywords" content="Fashi, unica, creative, html">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">

    <!-- Google Font -->
    <link href="https://fonts.googleapis.com/css?family=Muli:300,400,500,600,700,800,900&display=swap" rel="stylesheet">

    <!-- Css Styles -->
    <link rel="stylesheet" href="css/bootstrap.min.css" type="text/css">
    <link rel="stylesheet" href="css/font-awesome.min.css" type="text/css">
    <link rel="stylesheet" href="css/themify-icons.css" type="text/css">
    <link rel="stylesheet" href="css/elegant-icons.css" type="text/css">
    <link rel="stylesheet" href="css/owl.carousel.min.css" type="text/css">
    <link rel="stylesheet" href="css/nice-select.css" type="text/css">
    <link rel="stylesheet" href="css/jquery-ui.min.css" type="text/css">
    <link rel="stylesheet" href="css/slicknav.min.css" type="text/css">
    <link rel="stylesheet" href="css/style.css" type="text/css">
    <link rel="stylesheet" href="css/cart-ajax.css" type="text/css">
</head>

<body>

    <!-- Breadcrumb Section Begin -->
    <div class="breacrumb-section">
        <div class="container">
            <div class="row">
                <div class="col-lg-12">
                    <div class="breadcrumb-text product-more">
                        <a href="./home.php"><i class="fa fa-home"></i> Home</a>
                        <a href="./shop.php">Shop</a>
                        <span>Shopping Cart</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Breadcrumb Section Begin -->

    <!-- Shopping Cart Section Begin -->
    <section class="shopping-cart spad">
        <div class="container">
            <div class="row">
                <div class="col-lg-12">
                    <div class="cart-table">
                        <table>
                            <thead>
                                <tr>
                                    <th>Image</th>
                                    <th class="p-name">Product Name</th>
                                    <th>Price</th>
                                    <th>Quantity</th>
                                    <th>Total</th>
                                    <th><i class="ti-close"></i></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $total = 0;
                                if (mysqli_num_rows($result) > 0) {
                                    while ($row = mysqli_fetch_assoc($result)) {
                                ?>
                                
                                <tr data-cart-id="<?php echo $row['c_id']; ?>">
                                    <td class="cart-pic first-row">
                                        <?php if (!empty($row['imgname'])): ?>
                                            <img src="img/A&M/<?php echo $row['imgname']; ?>" alt="<?php echo $row["name"]; ?>" class="cart-product-image">
                                        <?php else: ?>
                                            <img src="img/no-image.png" alt="No Image" class="cart-product-image">
                                        <?php endif; ?>
                                    </td>
                                    <td class="cart-title first-row">
                                        <h5 class="p-name"><?php echo $row["name"]; ?></h5>
                                    </td>
                                    <td class="p-price first-row">&#8369;<?php echo $row["price"] ?></td>
                                    
                                    <td>
                                        <div class="quantity-controls">
                                            <button class="quantity-btn quantity-minus" data-cart-id="<?php echo $row['c_id']; ?>" data-action="decrease">-</button>
                                            <input type="number" class="quantity-input" min="1" value="<?php echo $row['quantity']; ?>" data-cart-id="<?php echo $row['c_id']; ?>">
                                            <button class="quantity-btn quantity-plus" data-cart-id="<?php echo $row['c_id']; ?>" data-action="increase">+</button>
                                        </div>
                                    </td>
                                    <td class="total-price first-row">&#8369;<?php echo number_format($row["price"] * $row["quantity"], 2); ?></td>
                                    <?php $total += $row["price"] * $row["quantity"]; ?>
                                    <td class="close-td first-row">
                                        <a href="#" class="remove-item-btn" data-cart-id="<?php echo $row['c_id']; ?>">
                                            <i class="ti-close"></i>
                                        </a>
                                    </td>
                                </tr>
                                
                                <?php
                                    }
                                } else {
                                    echo "<tr><td colspan='4' class='text-center'>No Products in the Cart</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>

                        <div class="text-right my-4">
                            <?php
                            // Calculate total quantity (merged from 1cart.php)
                            $total_quantity = 0;
                            $result_copy = $conn->query($sql);
                            if (mysqli_num_rows($result_copy) > 0) {
                                while ($row = mysqli_fetch_assoc($result_copy)) {
                                    $total_quantity += $row['quantity'];
                                }
                            }
                            ?>
                            <h4>Total Quantity: <span class="text-primary total-quantity-display"><?php echo $total_quantity; ?></span> | Total Amount: <span class="text-danger total-amount-display">₱<?php echo number_format($total, 2); ?></span></h4>
                        </div>

                        <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post" id="orderForm" class="border p-4 rounded">
                            <input type="hidden" name="total" value="<?php echo $total ?? 0 ?>">
                            <input type="hidden" name="user_id" value="<?php echo $_SESSION['userid'] ?? '' ?>">
                            <input type="hidden" name="user_name" value="<?php echo $_SESSION['username'] ?? '' ?>">

                            <div class="form-group">
                                <div class="input-group">
                                    <input type="text" class="form-control" name="address" id="addressInput" placeholder="Shipping Address" value="<?php echo htmlspecialchars($user_address); ?>" required <?php echo empty($user_address) ? '' : 'readonly'; ?>>
                                </div>
                            </div>
                            <div class="form-group">
                                <input type="text" class="form-control" name="mobnumber" placeholder="Phone Number" pattern="[0-9]{11}" maxlength="11" value="<?php echo htmlspecialchars($user_phone); ?>" required <?php echo empty($user_phone) ? '' : 'readonly'; ?>>
                            </div>
                            <div class="form-group">
                                <select name="payment_method" id="payment_method" class="form-control" required>
                                    <option value="" disabled selected>Select Payment Method</option>
                                    <option value="COD">Cash on Delivery (COD)</option>
                                    <option value="PayPal">PayPal</option>
                                </select>
                            </div>

                            <button type="submit" name="order_btn" class="site-btn login-btn w-100" id="orderButton" disabled>Place Order</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- Shopping Cart Section End -->

    <?php include 'footer.php'; ?>

    <!-- Js Plugins -->
    <script src="js/jquery-3.6.0.min.js"></script>
    <script src="js/bootstrap.min.js"></script>
    <script src="js/jquery-ui.min.js"></script>
    <script src="js/jquery.countdown.min.js"></script>
    <script src="js/jquery.nice-select.min.js"></script>
    <script src="js/jquery.zoom.min.js"></script>
    <script src="js/jquery.dd.min.js"></script>
    <script src="js/jquery.slicknav.js"></script>
    <script src="js/owl.carousel.min.js"></script>
    <script src="js/main.js"></script>
    <script src="js/cart-ajax-final.js"></script>

    <script>
        // Check if cart has items
        var cartItems = <?php echo mysqli_num_rows($result); ?>;
        
        document.getElementById('orderForm').addEventListener('input', function () {
            var address = document.querySelector('input[name="address"]').value;
            var mobnumber = document.querySelector('input[name="mobnumber"]').value;
            var payment_method = document.querySelector('select[name="payment_method"]').value;

            // Validate phone number pattern
            var phoneValid = /^[0-9]{11}$/.test(mobnumber);

            // Check if cart has items AND form is valid
            if (cartItems > 0 && address && mobnumber && payment_method && phoneValid) {
                document.getElementById('orderButton').disabled = false;
                document.getElementById('orderButton').style.backgroundColor = '#2ecc71';  // Green
            } else {
                document.getElementById('orderButton').disabled = true;
                document.getElementById('orderButton').style.backgroundColor = '#ddd'; // Disabled gray
            }
        });
        
        // Initial check on page load
        if (cartItems == 0) {
            document.getElementById('orderButton').disabled = true;
            document.getElementById('orderButton').style.backgroundColor = '#ddd';
            document.getElementById('orderButton').textContent = 'Cart is Empty';
        }
    </script> 
</body>

</html>
