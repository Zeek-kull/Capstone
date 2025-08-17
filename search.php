<?php
ob_start();
session_start();
include 'header.php';
include 'lib/connection.php';

// Fix undefined array key warning and prevent SQL injection
$name = isset($_POST['name']) ? trim($_POST['name']) : '';
$name = mysqli_real_escape_string($conn, $name);

// Initialize result
$result = null;

if (!empty($name)) {
    // Use prepared statement to prevent SQL injection
    $sql = "SELECT * FROM product WHERE name LIKE ? OR catagory LIKE ?";
    $stmt = $conn->prepare($sql);
    $search_param = "%{$name}%";
    $stmt->bind_param("ss", $search_param, $search_param);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    // Return empty result for empty search
    $result = $conn->query("SELECT * FROM product LIMIT 0");
}

// Handle add to cart functionality
if (isset($_POST['add_to_cart'])) {
    if (!isset($_SESSION['auth']) || $_SESSION['auth'] != 1) {
        header("Location: login.php");
        exit();
    }
    
    $user_id = (int)$_POST['user_id'];
    $product_name = mysqli_real_escape_string($conn, $_POST['product_name']);
    $product_price = (float)$_POST['product_price'];
    $product_id = (int)$_POST['product_id'];
    $product_quantity = 1;

    // Check if product already in cart
    $select_cart = $conn->prepare("SELECT * FROM `cart` WHERE productid = ? AND userid = ?");
    $select_cart->bind_param("ii", $product_id, $user_id);
    $select_cart->execute();
    $cart_result = $select_cart->get_result();

    if ($cart_result->num_rows > 0) {
        echo "Product already added to cart";
    } else {
        $insert_product = $conn->prepare("INSERT INTO `cart`(userid, productid, name, quantity, price) VALUES(?, ?, ?, ?, ?)");
        $insert_product->bind_param("iisid", $user_id, $product_id, $product_name, $product_quantity, $product_price);
        
        if ($insert_product->execute()) {
            echo "Product added to cart successfully";
        } else {
            echo "Error adding product to cart";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Results</title>
    <link rel="stylesheet" href="admin/css/pending_orders.css">
</head>
<body>
    <div class="container pendingbody">
        <h5>Search Result</h5>
        <div class="container">
            <div class="row">
                <?php
                if ($result && $result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        ?>
                        <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post">
                            <div class="col-md-3 col-sm-6 col-6">
                                <div class="product-item">
                                    <div class="pi-pic" style="width: 100%; height: 250px;">
                                        <img src="img/A&M/<?php echo htmlspecialchars($row['imgname']); ?>" alt="<?php echo htmlspecialchars($row['name']); ?>">
                                        <div class="icon">
                                            <i class="icon_heart_alt"></i>
                                        </div>
                                        <ul>
                                            <li style="width:75%;">
                                                <a href="product.php?id=<?php echo (int)$row['p_id']; ?>" class="product-link">+ Quick View</a>
                                            </li>
                                        </ul>
                                    </div>
                                    <div class="pi-text">
                                        <div class="catagory-name">Windbreaker</div>
                                        <a href="#">
                                            <h5><?php echo htmlspecialchars($row["name"]); ?></h5>
                                        </a>
                                        <div class="product-price">
                                            &#8369;<?php echo number_format($row["Price"], 2); ?>
                                            <span>500</span>
                                        </div>
                                        <div>
                                            <?php if (isset($_SESSION['auth']) && $_SESSION['auth'] == 1): ?>
                                                <button type="submit" class="site-btn login-btn w-100" name="add_to_cart">Add to Cart</button>
                                            <?php else: ?>
                                                <a href="login.php" class="site-btn login-btn w-100">Login to Add to Cart</a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <input type="hidden" name="user_id" value="<?php echo (int)$_SESSION['userid']; ?>">
                                    <input type="hidden" name="product_id" value="<?php echo (int)$row['id']; ?>">
                                    <input type="hidden" name="product_name" value="<?php echo htmlspecialchars($row['name']); ?>">
                                    <input type="hidden" name="product_price" value="<?php echo (float)$row['Price']; ?>">
                                </div>
                            </div>
                        </form>
                        <?php
                    }
                } else {
                    echo "<div class='col-12'><p class='text-center'>No products found matching your search.</p></div>";
                }
                ?>
            </div>
        </div>
    </div>
</body>
</html>
<?php
ob_end_flush();
?>
