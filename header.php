<?php 
  if (session_status() == PHP_SESSION_NONE) {
      session_start();
  }

  include "lib/connection.php";

  // Get cart count only if user is logged in
  $total = 0;
  if (isset($_SESSION['userid'])) {
      $id = $_SESSION['userid'];
      $cart_result = $conn->query("SELECT * FROM cart WHERE user_id='$id'");
      if ($cart_result && mysqli_num_rows($cart_result) > 0) {
          $total = mysqli_num_rows($cart_result);
      }
  }

  // Fetch distinct tags from the product table for dynamic navigation
  $tags_sql = "SELECT DISTINCT tags FROM product WHERE tags != '' AND tags IS NOT NULL ORDER BY tags";
  $tags_result = mysqli_query($conn, $tags_sql);
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
</head>

<body>
    

   <!-- Header Section Begin -->
   <?php if (empty($hideHeader)): ?>
    <header class="header-section">
        <div class="header-top"></div>
        <div class="container">
            <div class="inner-header">
                <div class="row">
                    <div class="col-lg-2 col-md-2">
                        <div class="logo">
                            <a href="./index.php">
                                <img src="img/amLogoo.png" alt="">
                            </a>
                        </div>
                    </div>
                    <div class="col-lg-7   col-md-2">
                        <form  action="search.php" method="post">
                        <div class="advanced-search">
                            <button type="button" class="category-btn">All Categories</button>
                            <div class="input-group">
                                <input type="search" placeholder="What do you need?" aria-label="Search" name="name">
                                <button type="submit"><i class="ti-search"></i></button>
                            </div>
                        </div>
                        </form>
                    </div>
                    <?php if (isset($_SESSION['userid'])): ?>
                    <div class="col-lg-3 text-right col-md-3">
                        <ul class="nav-right">
                            <li class="heart-icon">
                                <a href="#">
                                    <i class="icon_heart_alt"></i>
                                    <span>1</span>
                                </a>
                            </li>
                            <li class="cart-icon">
                                <a href="shopping-cart.php">
                                    <i class="icon_bag_alt"></i>
                                    <span><?php echo $total; ?></span>
                                </a>
                            </li>
                            <?php if (isset($_SESSION['auth']) && $_SESSION['auth'] == 1): ?>
                            <li class="user-icon">
                                <a href="profile.php">
                                    <i class="fa fa-user" style="text-decoration:none;color:black; "></i> 
                                </a>
                            </li>
                            <li><a class="btn btn-outline-success btn-sm ml-2" href="logout.php">Logout</a></li>
                        </ul>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="ml-3">
                    <?php else: ?>
                        <a class="btn btn-outline-primary btn-sm" href="login.php" id="header-login-btn">Login</a>
                        <a class="btn btn-outline-success btn-sm ml-2" href="register.php" id="header-register-btn">Signup</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="nav-item">
            <div class="container">
                <nav class="nav-menu mobile-menu">
                    <ul>
                        <li class="active"><a href="./index.php">Home</a></li>
                        <li><a href="./shop.php">Shop</a></li>
                        <?php
                        // Add dynamic tags from 1header.php
                        if ($tags_result && mysqli_num_rows($tags_result) > 0) {
                            while ($tag_row = mysqli_fetch_assoc($tags_result)) {
                                $tag = $tag_row['tags'];
                                // Handle display name transformation (Kid -> Kids)
                                $display_name = ($tag == 'Kid') ? 'Kids' : $tag;
                                echo '<li><a href="shop.php?tags=' . urlencode($tag) . '">' . htmlspecialchars($display_name) . '</a></li>';
                            }
                        }
                        ?>
                        <li><a href="./blog.php">Blog</a></li>
                        <li><a href="./contact.php">Contact</a></li>
                        <li><a href="./faq.php">Faq</a></li>
                    </ul>
                </nav>
                <div id="mobile-menu-wrap"></div>
            </div>
        </div>
    </header>
   <?php endif; ?>
    <!-- Header End -->

<script>
    // Only run if orderForm and orderButton exist
    document.addEventListener('DOMContentLoaded', function () {
        var orderForm = document.getElementById('orderForm');
        var orderButton = document.getElementById('orderButton');
        var cartItems = <?php echo json_encode($total); ?>;
        if (orderForm && orderButton) {
            orderForm.addEventListener('input', function () {
                var address = document.querySelector('input[name="address"]')?.value;
                // mobnumber JS reference removed
                var payment_method = document.querySelector('select[name="payment_method"]')?.value;
                // phoneValid for mobnumber removed
                if (cartItems > 0 && address && payment_method) {
                    orderButton.disabled = false;
                    orderButton.style.backgroundColor = '#2ecc71';
                } else {
                    orderButton.disabled = true;
                    orderButton.style.backgroundColor = '#ddd';
                }
            });
            // Initial check on page load
            if (cartItems == 0) {
                orderButton.disabled = true;
                orderButton.style.backgroundColor = '#ddd';
                orderButton.textContent = 'Cart is Empty';
            }
        }

        // Focus email input on login page when Login button is clicked
        var loginBtn = document.getElementById('header-login-btn');
        if (loginBtn) {
            loginBtn.addEventListener('click', function() {
                localStorage.setItem('focusEmailOnLogin', '1');
            });
        }
    });
</script>
</body>  

</html>