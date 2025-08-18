<?php
ob_start();
session_start();
  include 'header.php';
  include 'lib/connection.php'; // Make sure this file includes your database connection logic.

// Handle Add to Cart
if (isset($_POST['add_to_cart'])) {
    if (isset($_SESSION['auth']) && $_SESSION['auth'] == 1) {
        $user_id = $_SESSION['userid'];
        $product_name = $_POST['product_name'];
        $product_price = $_POST['product_price'];
        $product_id = $_POST['product_id'];
        $product_quantity = 1;

        // Check if the product is already in the cart
        $select_cart = mysqli_query($conn, "SELECT * FROM `cart` WHERE product_id = '$product_id' AND user_id = '$user_id'");
        if (mysqli_num_rows($select_cart) > 0) {
            // Product already in cart, increment quantity
            $cart_row = mysqli_fetch_assoc($select_cart);
            $new_quantity = $cart_row['quantity'] + 1;
            mysqli_query($conn, "UPDATE `cart` SET quantity = '$new_quantity' WHERE c_id = '{$cart_row['c_id']}'");

            header("Location: shop.php");
            exit();
        } else {
            $insert_product = mysqli_query($conn, "INSERT INTO `cart`(user_id, product_id, quantity, price) VALUES('$user_id', '$product_id', '$product_name', '$product_quantity', '$product_price')");
            header("Location: shop.php");
            exit();
        }
    } else {
        // Redirect to login if the user is not logged in
        header("Location: login.php");
        exit();
    }
}

// Get all distinct categories for the filter dropdown
  $category_sql = "SELECT DISTINCT category FROM product ORDER BY category";
  $category_result = mysqli_query($conn, $category_sql);
  $categories = [];
  while ($row = mysqli_fetch_assoc($category_result)) {
    $categories[] = $row['category'];
  }

  // Query to fetch products from the database
  $sql = "SELECT * FROM product"; // Make sure this query is correct
  $result = mysqli_query($conn, $sql); // Execute the query and store the result

  if (!$result) {
    // If the query fails, output an error and exit
    die('Query failed: ' . mysqli_error($conn));
  }
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
</head>



<body>
    

    <!-- Breadcrumb Section Begin -->
    <div class="breacrumb-section">
        <div class="container">
            <div class="row">
                <div class="col-lg-12">
                    <div class="breadcrumb-text">
                        <a href="index.php"><i class="fa fa-home"></i> Home</a>

                        <span>Shop</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Breadcrumb Section Begin -->

    <!-- Product Shop Section Begin -->
    <section class="product-shop spad">
        <div class="container">
            <div class="row">
                <div class="col-lg-3 col-md-6 col-sm-8 order-2 order-lg-1 produts-sidebar-filter">
                    <div class="filter-widget">
                        <h4 class="fw-title">Categories</h4>
                        <ul class="filter-catagories">
                            <li><a href="#">Men</a></li>
                            <li><a href="#">Women</a></li>
                            <li><a href="#">Kids</a></li>
                        </ul>
                    </div>
                    
                    <div class="filter-widget">
                        <h4 class="fw-title">Price</h4>
                        <div class="filter-range-wrap">
                            <div class="range-slider">
                                <div class="price-input">
                                    <input type="text" id="minamount">
                                    <input type="text" id="maxamount">
                                </div>
                            </div>
                            <div class="price-range ui-slider ui-corner-all ui-slider-horizontal ui-widget ui-widget-content"
                                data-min="33" data-max="98">
                                <div class="ui-slider-range ui-corner-all ui-widget-header"></div>
                                <span tabindex="0" class="ui-slider-handle ui-corner-all ui-state-default"></span>
                                <span tabindex="0" class="ui-slider-handle ui-corner-all ui-state-default"></span>
                            </div>
                        </div>
                        <a href="#" class="filter-btn">Filter</a>
                    </div>
                    
                    <div class="filter-widget">
                        <h4 class="fw-title">Size</h4>
                        <div class="fw-size-choose">
                            <div class="sc-item">
                                <input type="radio" id="s-size">
                                <label for="s-size">s</label>
                            </div>
                            <div class="sc-item">
                                <input type="radio" id="m-size">
                                <label for="m-size">m</label>
                            </div>
                            <div class="sc-item">
                                <input type="radio" id="l-size">
                                <label for="l-size">l</label>
                            </div>
                            <div class="sc-item">
                                <input type="radio" id="xs-size">
                                <label for="xs-size">xs</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-9 order-1 order-lg-2">
                        <!-- Filter Dropdown -->
                    <div class="filter-section mb-4">
                        <div class="row" id="productsContainer">
                            <div class="col-md-4">
                                <label for="categoryFilter" class="form-label">Filter by Category:</label>
                                <select id="categoryFilter" class="form-select">
                                    <option value="">All Categories</option>
                                        <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo htmlspecialchars($category); ?>">
                                        <?php echo htmlspecialchars($category); ?>
                                    </option>
                                         <?php endforeach; ?>
                                </select>
                            </div>
                         </div>
                    </div>

<!----------------------------------------------------------- PRODUCT ITEM ------------------------------>        



      <!---------------------------------------------------------------------------------------------------------------------------- -->

                    <div class="product-list">
                        <div class="row">
                            <?php
                            if (mysqli_num_rows($result) > 0) {
                            // Loop through products
                            while ($row = mysqli_fetch_assoc($result)) {
                             ?>
                            
                                <div class="col-lg-4 col-sm-6">
                                    <div class="product-item">
                                        <div class="pi-pic" style="width: 100%; height: 250px;">
                                            <img src="img/A&M/<?php echo $row['imgname']; ?>" alt="">
                                            <div class="icon">
                                               <i class="icon_heart_alt"></i>
                                            </div>
                                             <ul>

                                                <li style="width:75%;"><a href="product.php?id=<?php echo $row['p_id']; ?>" class="product-link">+ Quick View</a></li>
                                        </ul>
                                    </div>
                                    <div class="pi-text">
                                        <div class="catagory-name"></div>
                                        
                                        <a href="#">
                                            <h5><?php echo $row["name"] ?></h5>
                                        </a> 
                                        <div class="product-price">
                                            &#8369;<?php echo $row["price"] ?>
                                            <span>500</span>                                            
                                        </div>
                                        <div>
                                            <?php if (isset($_SESSION['auth']) && $_SESSION['auth'] == 1): ?>
                                                <button type="submit" class="site-btn login-btn w-100" name="add_to_cart">Add to Cart</button>
                                            <?php else: ?>
                                                <a href="login.php" class="site-btn login-btn w-100">Login to Add to Cart</a>
                                            <?php endif; ?>
                                        </div>
                                            <input type="hidden" name="product_id" value="<?php echo $row['p_id']; ?>">
                                            <input type="hidden" name="product_name" value="<?php echo $row['name']; ?>">
                                            <input type="hidden" name="product_price" value="<?php echo $row['price']; ?>">

                                    </div>
                                </div>
                            </div>
                            
                            <?php
                            }
                            } else {
                           echo "No products available.";
                          }
                            ?>
                        </div>
                    </div>
                    
    </section>
    <!-- Product Shop Section End -->

    

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

    <script>
$(document).ready(function() {
    $('#categoryFilter').change(function() {
        var selectedCategory = $(this).val();
        
        $.ajax({
            url: 'filter_products.php',
            type: 'POST',
            data: { category: selectedCategory },
            success: function(response) {
                $('#productsContainer').html(response);
            },
            error: function(xhr, status, error) {
                console.log('AJAX Error:', error);
                alert('Error loading products. Please try again.');
            }
        });
    });
});
</script>


</body>

</html>



