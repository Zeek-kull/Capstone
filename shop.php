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
            $insert_product = mysqli_query($conn, "INSERT INTO `cart`(user_id, product_id, quantity, price) VALUES('$user_id', '$product_id', '$product_quantity', '$product_price')");
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

    <!-- Local Muli Font -->
    <link rel="stylesheet" href="css/css.css" type="text/css">
    <!-- CSS Styles -->
    <link rel="stylesheet" href="css/bootstrap.min.css" type="text/css">
    <link rel="stylesheet" href="css/font-awesome.min.css" type="text/css">
    <link rel="stylesheet" href="css/themify-icons.css" type="text/css">
    <link rel="stylesheet" href="css/elegant-icons.css" type="text/css">
    <link rel="stylesheet" href="css/owl.carousel.min.css" type="text/css">
    <link rel="stylesheet" href="css/nice-select.css" type="text/css">
    <link rel="stylesheet" href="css/jquery-ui.min.css" type="text/css">
    <link rel="stylesheet" href="css/slicknav.min.css" type="text/css">
    <link rel="stylesheet" href="css/style.css" type="text/css">
    <link rel="stylesheet" href="css/out-of-stock.css" type="text/css">
    <!-- Quick view styles moved to css/style.css -->
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
                        <ul class="filter-catagories">
                            <?php foreach ($categories as $category): 
                                // skip unwanted sidebar categories (case-insensitive)
                                $catNorm = strtolower(trim($category));
                                if (in_array($catNorm, ['med','top'])) continue;
                            ?>
                                <li>
                                    <a href="#" class="category-link" data-category="<?php echo htmlspecialchars($category); ?>">
                                        <?php echo htmlspecialchars($category); ?>
                                    </a>
                                </li>
                            <?php endforeach; ?>
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
                    
                    <!-- Size filter removed -->
                </div>
                <div class="col-lg-9 order-1 order-lg-2">
                        <!-- Filter Dropdown -->
                    <div class="filter-section mb-4">
                        <div class="row">
                            <div class="col-md-4">
                                <label for="categoryFilter" class="form-label">Category:</label>
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
                        <div class="row" id="productsContainer">
                            <?php
                            if (mysqli_num_rows($result) > 0) {
                            // Loop through products
                            while ($row = mysqli_fetch_assoc($result)) {
                                $isOutOfStock = isset($row['quantity']) && $row['quantity'] <= 0;
                             ?>
                            
                                <div class="col-lg-4 col-sm-6">
                                    <form method="POST" action="">
                                        <div class="product-item <?php echo $isOutOfStock ? 'out-of-stock' : ''; ?>">
                                            <div class="pi-pic" style="width: 100%; height: 250px; position: relative;">
                                                <?php
                                                // resolve image when imgname can be a comma-separated list
                                                $img_field = $row['imgname'] ?? '';
                                                $first_img = '';
                                                if ($img_field !== '') {
                                                    if (strpos($img_field, ',') !== false) {
                                                        $parts = explode(',', $img_field);
                                                        $first_img = trim($parts[0]);
                                                    } else {
                                                        $first_img = trim($img_field);
                                                    }
                                                }
                                                $img_src = '';
                                                if ($first_img) {
                                                    // prefer thumb_ variants when available
                                                    $thumbA = __DIR__ . '/img/A&M/thumbs/' . $first_img;
                                                    $origA = __DIR__ . '/img/A&M/' . $first_img;
                                                    $thumbU = __DIR__ . '/admin/uploaded_products/thumbs/' . $first_img;
                                                    $origU = __DIR__ . '/admin/uploaded_products/' . $first_img;
                                                    if (file_exists($thumbA)) {
                                                        $img_src = 'img/A&M/thumbs/' . $first_img;
                                                    } elseif (file_exists($origA)) {
                                                        $img_src = 'img/A&M/' . $first_img;
                                                    } elseif (file_exists($thumbU)) {
                                                        $img_src = 'admin/uploaded_products/thumbs/' . $first_img;
                                                    } elseif (file_exists($origU)) {
                                                        $img_src = 'admin/uploaded_products/' . $first_img;
                                                    } else {
                                                        $img_src = 'img/hero-1.jpg';
                                                    }
                                                } else {
                                                    $img_src = 'img/hero-1.jpg';
                                                }
                                                ?>
                                                <img src="<?php echo $img_src; ?>" alt="<?php echo htmlspecialchars($row['name']); ?>" <?php echo $isOutOfStock ? 'style="opacity: 0.5;"' : ''; ?>>
                                                <?php if ($isOutOfStock): ?>
                                                    <div class="out-of-stock-badge">OUT OF STOCK</div>
                                                <?php endif; ?>
                                                 <ul>
                                                    <li style="width:75%;"><a href="product.php?id=<?php echo $row['p_id']; ?>" class="product-link">+ Quick View</a></li>
                                                </ul>
                                            </div>
                                            <div class="pi-text">
                                                <div class="category-name"><?php echo htmlspecialchars($row['category'] ?? ''); ?></div>
                                                
                                                <a href="product.php?id=<?php echo $row['p_id']; ?>">
                                                    <h5><?php echo htmlspecialchars($row['name']); ?></h5>
                                                </a>
                                                <div class="product-price">
                                                    &#8369;<?php echo number_format((float)$row["price"], 2); ?>                                            
                                                </div>
                                                    <div>
                                                    <?php if (!isset($_SESSION['auth']) || $_SESSION['auth'] != 1): ?>
                                                        <a href="login.php" class="site-btn login-btn w-100">Login to Add to Cart</a>
                                                    <?php endif; ?>
                                                </div>
                                                <input type="hidden" name="product_id" value="<?php echo $row['p_id']; ?>">
                                                <input type="hidden" name="product_name" value="<?php echo $row['name']; ?>">
                                                <input type="hidden" name="product_price" value="<?php echo $row['price']; ?>">
                                            </div>
                                        </div>
                                    </form>
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

    <!-- JS Plugins -->
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
                url: 'ajax/filter_products.php',
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



