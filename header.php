   <?php 


  if (session_status() == PHP_SESSION_NONE) {
      session_start();
  }

  include "lib/connection.php";

  // Initialize variables
  $id = null; 
  $result = null;

  // Check if 'userid' exists in the session (i.e., the user is logged in)
  if (isset($_SESSION['userid'])) {
      $id = $_SESSION['userid'];
      
      // Run query only if $id is set
      $sql = "SELECT * FROM cart WHERE userid='$id'";
      $result = $conn->query($sql);
  }

  // Calculate total items in the cart
  $total = 0;
  if ($result && mysqli_num_rows($result) > 0) {
      while ($row = mysqli_fetch_assoc($result)) {
          $total++;
      }
  }

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
    

   <!-- Header Section Begin -->
    <header class="header-section">
        <div class="header-top">
        </div>
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
                                <a class="btn btn-outline-primary btn-sm" href="login.php">Login</a>
                                <a class="btn btn-outline-success btn-sm ml-2" href="Register.php">Signup</a>
                                <?php endif; ?>
                            </div>
            </div>
        </div>
        <div class="nav-item">
            <div class="container">
                <div class="nav-depart">
                    <div class="depart-btn">
                        <i class="ti-menu"></i>
                        <span>All departments</span>
                        <ul class="depart-hover">
                            <li class="active"><a href="#">Women’s Clothing</a></li>
                            <li><a href="./shop.php">Men’s Clothing</a></li>
                            <li><a href="./shop.php">Underwear</a></li>
                            <li><a href="./shop.php">Kid's Clothing</a></li>
                            <li><a href="./shop.php">Brand Fashion</a></li>
                            <li><a href="./shop.php">Accessories/Shoes</a></li>
                            <li><a href="./shop.php">Luxury Brands</a></li>
                            <li><a href="./shop.php">Brand Outdoor Apparel</a></li>
                        </ul>
                    </div>
                </div>
                <nav class="nav-menu mobile-menu">
                    <ul>
                        <li class="active"><a href="./index.php">Home</a></li>
                        <li><a href="./shop.php">Shop</a></li>
                        <li><a href="#">Collection</a>
                            <ul class="dropdown">
                                <li><a href="./shop.php">Men's</a></li>
                                <li><a href="./shop.php">Women's</a></li>
                                <li><a href="./shop.php">Kid's</a></li>
                            </ul>
                        </li>
                        <li><a href="./blog.php">Blog</a></li>
                        <li><a href="./contact.php">Contact</a></li>
                        <li><a href="#">Pages</a>
                            <ul class="dropdown">
                                <li><a href="./blog-details.php">Blog Details</a></li>
                                <li><a href="./faq.php">Faq</a></li>
                            </ul>
                        </li>
                    </ul>
                </nav>
                <div id="mobile-menu-wrap"></div>
            </div>
        </div>
    </header>
    <!-- Header End -->

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