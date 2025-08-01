<?php
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
        $select_cart = mysqli_query($conn, "SELECT * FROM `cart` WHERE productid = '$product_id' AND userid = '$user_id'");
        if (mysqli_num_rows($select_cart) > 0) {
            // Product already in cart, increment quantity
            $cart_row = mysqli_fetch_assoc($select_cart);
            $new_quantity = $cart_row['quantity'] + 1;
            mysqli_query($conn, "UPDATE `cart` SET quantity = '$new_quantity' WHERE id = '{$cart_row['id']}'");
            header("Location: Clothing.php");
            exit();
        } else {
            $insert_product = mysqli_query($conn, "INSERT INTO `cart`(userid, productid, name, quantity, price) VALUES('$user_id', '$product_id', '$product_name', '$product_quantity', '$product_price')");
            header("Location: Clothing.php");
            exit();
        }
    } else {
        // Redirect to login if the user is not logged in
        header("Location: login.php");
        exit();
    }
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
                    <div class="product-show-option">
                        <div class="row">
                            <div class="col-lg-7 col-md-7">
                                <div class="select-option">
                                    <select class="sorting">
                                        <option value="">Default Sorting</option>
                                    </select>
                                    <select class="p-show">
                                        <option value="">Show:</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-lg-5 col-md-5 text-right">
                                <p>Show 01- 09 Of 36 Product</p>
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
                                    <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
                                     <div class="product-item">
                                        <div class="pi-pic">
                                            <img src="img/A&M/<?php echo $row['imgname']; ?>" alt="">
                                            <div class="icon">
                                               <i class="icon_heart_alt"></i>
                                            </div>
                                             <ul>
                                                <?php if (isset($_SESSION['auth']) && $_SESSION['auth'] == 1): ?>
                                                <li class="w-icon active"><a href="#"><i class="icon_bag_alt" value="Add to Cart" name="add_to_cart"></i></a></li>
                                                 <?php endif; ?>

                                                <li class="quick-view"><a href="product.php">+ Quick View</a></li>
                                            <li class="w-icon"><a href="#"><i class="fa fa-random"></i></a></li>
                                        </ul>
                                    </div>
                                    <div class="pi-text">
                                        <div class="catagory-name">Windbreaker</div>
                                        <input type="hidden" name="product_id" value="<?php echo $row['id']; ?>">
                                        <a href="#">
                                            <h5><?php echo $row["name"] ?></h5>
                                            <input type="hidden" name="product_name" value="<?php echo $row['name']; ?>">
                                        </a>
                                        <div class="product-price">
                                            &#8369;<?php echo $row["Price"] ?>
                                            <span>500</span>
                                            <input type="hidden" name="product_price" value="<?php echo $row['Price']; ?>">
                                        </div>


                                        
                                    </div>
                                    </form>
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
    <script src="js/jquery-3.3.1.min.js"></script>
    <script src="js/bootstrap.min.js"></script>
    <script src="js/jquery-ui.min.js"></script>
    <script src="js/jquery.countdown.min.js"></script>
    <script src="js/jquery.nice-select.min.js"></script>
    <script src="js/jquery.zoom.min.js"></script>
    <script src="js/jquery.dd.min.js"></script>
    <script src="js/jquery.slicknav.js"></script>
    <script src="js/owl.carousel.min.js"></script>
    <script src="js/main.js"></script>
</body>

</html>