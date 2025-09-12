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
    $user_info_query = mysqli_query($conn, "SELECT street, zone, barangay, city, province, phone FROM users WHERE u_id='$user_id_for_address'");
    if ($user_info_query && mysqli_num_rows($user_info_query) > 0) {
        $user_data = mysqli_fetch_assoc($user_info_query);
        
                // Build complete address from user data (profile.php style)
                $address_main = [];
                if (!empty($user_data['street'])) $address_main[] = $user_data['street'];
                if (!empty($user_data['zone'])) $address_main[] = $user_data['zone'];
                if (!empty($user_data['barangay'])) $address_main[] = $user_data['barangay'];
                $address_first = implode(' ', $address_main);
                $address_second = [];
                if (!empty($user_data['city'])) $address_second[] = $user_data['city'];
                if (!empty($user_data['province'])) $address_second[] = $user_data['province'];
                $user_address = $address_first;
                if (!empty($address_second)) {
                    $user_address .= ', ' . implode(', ', $address_second);
                }
                $user_phone = $user_data['phone'];
    }
}

if (isset($_POST['order_btn'])) {
    // Expect selected_items[] containing cart IDs
    $selected = $_POST['selected_items'] ?? [];
    if (!is_array($selected) || count($selected) == 0) {
        if (session_status() == PHP_SESSION_NONE) session_start();
        $_SESSION['error_message'] = 'Please select at least one item to checkout.';
        header("location:shopping-cart.php");
        exit();
    }

    $userid = $_POST['user_id'] ?? '';
    $name = $_POST['user_name'] ?? '';
    $number = $_POST['number'] ?? '';
    $address = $_POST['address'] ?? '';
    $payment_method = $_POST['payment_method'] ?? ''; // User-selected payment method
    $status = "pending";
    $order_date = date('Y-m-d H:i:s'); // Current date and time

    // Build a safe list of integer cart IDs
    $ids = array_map('intval', $selected);
    if (count($ids) == 0) {
        if (session_status() == PHP_SESSION_NONE) session_start();
        $_SESSION['error_message'] = 'Invalid selection.';
        header("location:shopping-cart.php");
        exit();
    }

    $ids_list = implode(',', $ids);
    // Fetch only selected cart rows (with product details)
    $cart_query = mysqli_query($conn, "SELECT cart.*, product.p_id, product.name, product.price, product.quantity AS prod_stock FROM cart LEFT JOIN product ON cart.product_id = product.p_id WHERE cart.c_id IN ($ids_list) AND cart.user_id='$userid'");

    $price_total = 0;
    $product_name = [];

    if (mysqli_num_rows($cart_query) > 0) {
        while ($product_item = mysqli_fetch_assoc($cart_query)) {
            $product_name[] = $product_item['product_id'] . ' (' . $product_item['quantity'] . ')';
            $product_price = $product_item['price'] * $product_item['quantity'];
            $price_total += $product_price;

            // Update product stock if available
            if ($product_item['quantity'] <= $product_item['prod_stock']) {
                $update_quantity = $product_item['prod_stock'] - $product_item['quantity'];
                $update_query = mysqli_query($conn, "UPDATE `product` SET quantity = '$update_quantity' WHERE p_id = '{$product_item['p_id']}'");
            } else {
                echo "Out of stock: " . htmlspecialchars($product_item['name']) . " Quantity: " . intval($product_item['prod_stock']);
                header("location:shopping-cart.php");
                exit();
            }
        }

        // Insert order
        $total_product = implode(', ', $product_name);
        $detail_query = mysqli_query($conn, "INSERT INTO `orders`(user_id, name, address, phone, payment_method, totalproduct, totalprice, status, created_at) 
            VALUES('$userid','$name','$address','$number','$payment_method','$total_product','$price_total','$status', '$order_date')");

        // Delete only selected cart rows
        $cart_query1 = mysqli_query($conn, "DELETE FROM `cart` WHERE c_id IN ($ids_list) AND user_id='$userid'");
        // Set flash to display on redirect
        $_SESSION['success_message'] = 'Order placed successfully';
        header("location:index.php");
        exit();
    } else {
        if (session_status() == PHP_SESSION_NONE) session_start();
        $_SESSION['error_message'] = 'No selectable items found.';
        header("location:shopping-cart.php");
        exit();
    }
}

$id = $_SESSION['userid'] ?? '';
$sql = "SELECT cart.*, product.imgname, product.name, product.price, product.quantity AS prod_stock 
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

    <!-- Local Muli Font -->
    <link rel="stylesheet" href="css/css.css" type="text/css">

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

    <?php include __DIR__ . '/includes/flash.php'; ?>

    <!-- Shopping Cart Section Begin -->
    <section class="shopping-cart spad">
        <div class="container">
            <div class="row">
                <div class="col-lg-12">
                    <div class="cart-table">
                        <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post" id="orderForm" class="border p-4 rounded">
                        <table>
                            <thead>
                                <tr>
                                    <th><input type="checkbox" id="selectAll" aria-label="Select all items"></th>
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
                                    <td class="first-row">
                                        <input type="checkbox" class="cart-select" name="selected_items[]" value="<?php echo $row['c_id']; ?>" aria-label="Select <?php echo htmlspecialchars($row['name']); ?>" data-price="<?php echo htmlspecialchars($row['price']); ?>">
                                    </td>
                                    <td class="cart-pic first-row">
                                        <?php
                                        // Resolve imgname which may contain multiple filenames separated by commas.
                                        $cart_img_src = 'img/no-image.png';
                                        if (!empty($row['imgname'])) {
                                            $names = array_filter(array_map('trim', explode(',', $row['imgname'])));
                                            if (count($names) > 0) {
                                                // Prefer thumbnail locations, then originals (legacy paths and new upload dir)
                                                $first = $names[0];
                                                $candidates = [
                                                    'img/A&M/thumbs/' . $first,
                                                    'admin/uploaded_products/thumbs/' . $first,
                                                    'img/A&M/' . $first,
                                                    'admin/uploaded_products/' . $first,
                                                ];
                                                foreach ($candidates as $p) {
                                                    if (file_exists($p)) {
                                                        $cart_img_src = $p;
                                                        break;
                                                    }
                                                }
                                            }
                                        }
                                        ?>
                                        <img src="<?php echo htmlspecialchars($cart_img_src); ?>" alt="<?php echo htmlspecialchars($row['name']); ?>" class="cart-product-image">
                                    </td>
                                    <td class="cart-title first-row">
                                        <h5 class="p-name"><?php echo $row["name"]; ?></h5>
                                    </td>
                                    <td class="p-price first-row">&#8369;<?php echo number_format((float)$row["price"], 2); ?></td>
                                    
                                    <td>
                                        <div class="quantity-controls">
                                            <button type="button" class="quantity-btn quantity-minus" data-cart-id="<?php echo $row['c_id']; ?>" data-action="decrease">-</button>
                                            <?php
                                            $cartQty = (int)$row['quantity'];
                                            $stock = isset($row['prod_stock']) ? (int)$row['prod_stock'] : 0;
                                            if ($stock > 0 && $cartQty > $stock) {
                                                // clamp displayed quantity to available stock for UI, but keep original value for server handling
                                                $displayQty = $stock;
                                                $overStock = true;
                                            } else {
                                                $displayQty = $cartQty > 0 ? $cartQty : 1;
                                                $overStock = false;
                                            }
                                            ?>
                                            <input type="number" class="quantity-input" min="1" max="<?php echo $stock > 0 ? $stock : 99999; ?>" value="<?php echo $displayQty; ?>" data-cart-id="<?php echo $row['c_id']; ?>" data-actual-qty="<?php echo $cartQty; ?>" data-stock="<?php echo $stock; ?>">
                                            <button type="button" class="quantity-btn quantity-plus" data-cart-id="<?php echo $row['c_id']; ?>" data-action="increase">+</button>
                                            <?php if ($overStock): ?>
                                                <div class="small text-danger mt-1">Only <?php echo $stock; ?> in stock — quantity was reduced for checkout.</div>
                                            <?php endif; ?>
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
                                    echo "<tr><td colspan='7' class='text-center'>No Products in the Cart</td></tr>";
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
                            <h5>Selected: <span class="text-primary selected-quantity">0</span> items | Amount: <span class="text-danger selected-amount">₱0.00</span></h5>
                        </div>

                        <!-- form inputs moved inside the wrapping form above -->
                        <input type="hidden" name="total" value="<?php echo $total ?? 0 ?>">
                        <input type="hidden" name="user_id" value="<?php echo $_SESSION['userid'] ?? '' ?>">
                        <input type="hidden" name="user_name" value="<?php echo $_SESSION['username'] ?? '' ?>">

                        <div class="form-group">
                            <label for="addressInput" class="mb-1">Shipping Address</label>
                            <div class="input-group">
                                <input type="text" class="form-control" name="address" id="addressInput"  value="<?php echo htmlspecialchars($user_address); ?>" required <?php echo empty($user_address) ? '' : 'readonly'; ?>>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="phoneInput" class="mb-1">Phone Number</label>
                            <input type="text" class="form-control" name="number" id="phoneInput" value="<?php echo htmlspecialchars($user_phone); ?>" required readonly>
                        </div>
                        <div class="form-group">
                            <label for="payment_method" class="mb-1">Payment Method</label>
                            <select name="payment_method" id="payment_method" class="form-control" required aria-label="Payment Method">
                                <option value="COD">Cash on Delivery (COD)</option>
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
        // Check if cart has items (total rows)
        var cartTotalRows = <?php echo mysqli_num_rows($result); ?>;

        var orderForm = document.getElementById('orderForm');
        var orderButton = document.getElementById('orderButton');

        function updateOrderButtonStyle() {
            if (!orderButton) return;
            if (orderButton.disabled) {
                orderButton.style.backgroundColor = '#ddd';
                orderButton.style.borderColor = '#ccc';
                orderButton.style.color = '#333'
            } else {
                orderButton.style.backgroundColor = '#2ecc71';
                orderButton.style.borderColor = '';
                orderButton.style.color = '';
            }
        }

        function canEnableOrder() {
            var address = document.querySelector('input[name="address"]').value;
            var payment_method = document.querySelector('select[name="payment_method"]').value;
            var anyChecked = !!document.querySelector('.cart-select:checked');
            return anyChecked && address && payment_method;
        }

        // Compute selected totals and update UI
        function computeSelectedTotals() {
            var checked = Array.from(document.querySelectorAll('.cart-select:checked'));
            var totalQty = 0;
            var totalAmt = 0;
            checked.forEach(function(chk) {
                var cartRow = chk.closest('tr');
                var qtyInput = cartRow.querySelector('.quantity-input');
                var qty = qtyInput ? parseInt(qtyInput.value) || 0 : 0;
                var priceText = cartRow.querySelector('.p-price') ? cartRow.querySelector('.p-price').textContent : '';
                // priceText may contain currency symbol; better to use data-price attribute on checkbox
                var price = parseFloat(chk.getAttribute('data-price')) || 0;
                totalQty += qty;
                totalAmt += qty * price;
            });
            document.querySelector('.selected-quantity').textContent = numberWithCommas(totalQty);
            document.querySelector('.selected-amount').textContent = '₱' + formatCurrency(totalAmt);
        }

    // Formatting helpers
    function numberWithCommas(x) {
        if (x === null || x === undefined) return '0';
        var parts = x.toString().split('.');
        parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ',');
        return parts.join('.');
    }

    function formatCurrency(n) {
        var num = Number(n) || 0;
        return numberWithCommas(num.toFixed(2));
    }

    // expose for other scripts (ajax handlers) to call
    window.computeSelectedTotals = computeSelectedTotals;
    window.formatCurrency = formatCurrency;
    window.numberWithCommas = numberWithCommas;

    // Quantity controls: enforce stock limits and update totals
    function enforceQuantityInput(input) {
        var stock = parseInt(input.getAttribute('data-stock')) || 0;
        var val = parseInt(input.value) || 0;
        if (val < 1) {
            input.value = 1;
            val = 1;
        }
        if (stock > 0 && val > stock) {
            // clamp to stock
            input.value = stock;
            if (typeof showFlash === 'function') {
                showFlash('warning', 'Quantity adjusted to available stock: ' + stock, 3000);
            }
            val = stock;
        }
        // Update compute totals
        computeSelectedTotals();
        orderButton.disabled = !canEnableOrder();
        updateOrderButtonStyle();
        // Dispatch a custom event so AJAX handlers can update server-side cart with final quantity
        try {
            var ev = new Event('cartQtyFinal');
            input.dispatchEvent(ev);
        } catch(e){}
        // Optionally, send AJAX update to server to update cart quantity (handled by cart-ajax-final.js normally)
    }

    // Note: plus/minus buttons are handled by delegated jQuery handlers in js/cart-ajax-final.js.
    // We keep input enforcement (below) so clamping occurs on manual change before the delegated handlers run.

    // Hook up manual input enforcement
    document.querySelectorAll('.quantity-input').forEach(function(inp){
        inp.addEventListener('change', function(){ enforceQuantityInput(inp); });
        inp.addEventListener('input', function(){
            // allow typing but prevent extremely large numbers by checking and clamping on input
            var stock = parseInt(inp.getAttribute('data-stock')) || 0;
            var val = parseInt(inp.value) || 0;
            if (stock > 0 && val > stock) {
                // don't immediately clobber while typing, but show small hint if it becomes too large
                inp.classList.add('border-danger');
            } else {
                inp.classList.remove('border-danger');
            }
        });
    });

        // Select all handling and keyboard accessibility
        var selectAllCheckbox = document.getElementById('selectAll');
        if (selectAllCheckbox) {
            selectAllCheckbox.addEventListener('change', function () {
                var all = document.querySelectorAll('.cart-select');
                all.forEach(function(c){ c.checked = selectAllCheckbox.checked; });
                computeSelectedTotals();
                orderButton.disabled = !canEnableOrder();
                updateOrderButtonStyle();
            });
            // keyboard support for Space/Enter on the checkbox wrapper
            selectAllCheckbox.addEventListener('keydown', function(e){
                if (e.key === ' ' || e.key === 'Enter') {
                    e.preventDefault();
                    selectAllCheckbox.checked = !selectAllCheckbox.checked;
                    var ev = new Event('change');
                    selectAllCheckbox.dispatchEvent(ev);
                }
            });
        }


        // Listen for changes on the form and on checkbox selection
        if (orderForm) {
            orderForm.addEventListener('change', function (e) {
                // if checkboxes, toggles
                orderButton.disabled = !canEnableOrder();
                updateOrderButtonStyle();
                computeSelectedTotals();
            });

            orderForm.addEventListener('input', function () {
                orderButton.disabled = !canEnableOrder();
                updateOrderButtonStyle();
                computeSelectedTotals();
            });
        }

        // Initial check on page load
        if (cartTotalRows == 0) {
            if (orderButton) {
                orderButton.disabled = true;
                orderButton.textContent = 'Cart is Empty';
            }
        }
        // ensure style reflects initial disabled state
        orderButton.disabled = !canEnableOrder();
        updateOrderButtonStyle();
    // compute initial selected totals
    computeSelectedTotals();
    </script> 
</body>

</html>
