<?php
  include 'header.php';
  include 'lib/connection.php';

  // Query to fetch all products
  $sql = "SELECT * FROM product";
  $result = $conn->query($sql);

  // Check if user is logged in and trying to add to cart
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
        $message[] = 'Product already added to cart';
      } else {
        // Insert product into cart
        $insert_product = mysqli_query($conn, "INSERT INTO `cart`(userid, productid, name, quantity, price) VALUES('$user_id', '$product_id', '$product_name', '$product_quantity', '$product_price')");
        $message[] = 'Product added to cart successfully';
        header('Location: default.php'); // Refresh the page after adding the product
        exit();
      }
    } else {
      // Redirect to login if the user is not logged in
      header("Location: login.php");
      exit();
    }
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

    <!-- Hero Section Begin -->
    <section class="hero-section">
        <div class="hero-items owl-carousel">
            <div class="single-hero-items set-bg" data-setbg="img/hero-1.jpg">
                <div class="container">
                    <div class="row">
                        <div class="col-lg-5">
                            <span>Bag,kids</span>
                            <h1>Black friday</h1>
                            <p>Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor
                                incididunt ut labore et dolore</p>
                            <a href="#" class="primary-btn">Shop Now</a>
                        </div>
                    </div>
                    <div class="off-card">
                        <h2>Sale <span>50%</span></h2>
                    </div>
                </div>
            </div>
            <div class="single-hero-items set-bg" data-setbg="img/hero-2.jpg">
                <div class="container">
                    <div class="row">
                        <div class="col-lg-5">
                            <span>Bag,kids</span>
                            <h1>Black friday</h1>
                            <p>Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor
                                incididunt ut labore et dolore</p>
                            <a href="#" class="primary-btn">Shop Now</a>
                        </div>
                    </div>
                    <div class="off-card">
                        <h2>Sale <span>50%</span></h2>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- Hero Section End -->

    <!-- Banner Section Begin -->
<div class="banner-section spad" style="margin-top: 50px;">
    <div class="container-fluid">
        <div class="row">
            <!-- Men's -->
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="single-banner position-relative">
                    <img src="img/banner-1.jpg" class="img-fluid w-100" alt="">
                    <div class="inner-text">
                        <h4>Men’s</h4>
                    </div>
                </div>
            </div>
            <!-- Women's -->
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="single-banner position-relative">
                    <img src="img/banner-2.jpg" class="img-fluid w-100" alt="">
                    <div class="inner-text">
                        <h4>Women’s</h4>
                    </div>
                </div>
            </div>
            <!-- Kid's -->
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="single-banner position-relative">
                    <img src="img/banner-3.jpg" class="img-fluid w-100" alt="">
                    <div class="inner-text">
                        <h4>Kid’s</h4>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- Banner Section End -->



<!----------------------------------------------------------------------------------------------------------------------------------------------------- -->



    <!-- Women Banner Section Begin -->
    <?php
    // Helper: resolve a product image filename to an existing web path (prefer thumbnails when asked)
    function resolve_product_image_index($filename, $preferThumb = true) {
        $filename = trim($filename);
        if ($filename === '') return 'img/hero-1.jpg';
        $candidates = [];
        if ($preferThumb) {
            $candidates = [
                ['fs' => __DIR__ . '/admin/uploaded_products/thumbs/' . $filename, 'web' => 'admin/uploaded_products/thumbs/' . $filename],
                ['fs' => __DIR__ . '/admin/uploaded_products/' . $filename, 'web' => 'admin/uploaded_products/' . $filename],
                ['fs' => __DIR__ . '/img/A&M/thumbs/' . $filename, 'web' => 'img/A&M/thumbs/' . $filename],
                ['fs' => __DIR__ . '/img/A&M/' . $filename, 'web' => 'img/A&M/' . $filename],
            ];
        } else {
            $candidates = [
                ['fs' => __DIR__ . '/admin/uploaded_products/' . $filename, 'web' => 'admin/uploaded_products/' . $filename],
                ['fs' => __DIR__ . '/admin/uploaded_products/thumbs/' . $filename, 'web' => 'admin/uploaded_products/thumbs/' . $filename],
                ['fs' => __DIR__ . '/img/A&M/' . $filename, 'web' => 'img/A&M/' . $filename],
                ['fs' => __DIR__ . '/img/A&M/thumbs/' . $filename, 'web' => 'img/A&M/thumbs/' . $filename],
            ];
        }
        foreach ($candidates as $c) {
            if (file_exists($c['fs'])) return $c['web'];
        }
        return 'img/hero-1.jpg';
    }
    ?>
    <!-- Banner styles moved to css/style.css -->

    <section class="women-banner spad">
        <div class="container-fluid">
            <div class="row">
                <div class="col-lg-3">
                    <div class="product-large set-bg" data-setbg="img/products/women-large.jpg">
                        <h2>Women’s</h2>
                        <a href="#">Discover More</a>
                    </div>
                </div>
                <div class="col-lg-8 offset-lg-1">
                    <div class="filter-control">
                    </div>
                    <div class="product-slider owl-carousel">
                        <?php
                        // Fetch products with 'Women' tag
                        $women_sql = "SELECT * FROM product WHERE tags = 'Women'";
                        $women_result = $conn->query($women_sql);
                        if ($women_result && $women_result->num_rows > 0):
                            while ($row = $women_result->fetch_assoc()):
                                // Robust image resolver (prefer thumb, then original, then fallback)
                                $img = 'img/hero-1.jpg';
                                if (!empty($row['imgname'])) {
                                    $parts = explode(',', $row['imgname']);
                                    foreach ($parts as $p) {
                                        $p = trim($p);
                                        if ($p !== '') {
                                            $img = resolve_product_image_index($p, true);
                                            break;
                                        }
                                    }
                                }
                                $isOutOfStock = isset($row['quantity']) && $row['quantity'] <= 0;
                        ?>
                        <div class="product-item <?php echo $isOutOfStock ? 'out-of-stock' : ''; ?>">
                            <div class="pi-pic">
                                <img src="<?php echo htmlspecialchars($img); ?>" alt="<?php echo htmlspecialchars($row['name']); ?>">
                                <?php if ($isOutOfStock): ?><div class="out-of-stock-badge">OUT OF STOCK</div><?php endif; ?>
                                <?php if ($row['sale'] ?? false): ?><div class="sale">Sale</div><?php endif; ?>
                                <ul>
                                    <li class="quick-view"><a href="product.php?id=<?php echo $row['p_id']; ?>">+ Quick View</a></li>
                                </ul>
                            </div>
                            <div class="pi-text">
                                <div class="category-name"><?php echo htmlspecialchars($row['category']); ?></div>
                                <a href="product.php?id=<?php echo $row['p_id']; ?>">
                                    <h5><?php echo htmlspecialchars($row['name']); ?></h5>
                                </a>
                                <div class="product-price">
                                    &#8369;<?php echo number_format((float)$row['price'], 2); ?>
                                    <?php if (!empty($row['old_price'])): ?><span>&#8369;<?php echo number_format((float)$row['old_price'], 2); ?></span><?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <?php endwhile; else: ?>
                        <div class="product-item">
                            <div class="pi-pic">
                                <img src="img/hero-1.jpg" alt="No products">
                            </div>
                            <div class="pi-text">
                                <div class="category-name">No products</div>
                                <h5>No women's products found.</h5>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- Women Banner Section End -->

    <!-- Deal Of The Week Section Begin-->
    <section class="deal-of-week set-bg spad" data-setbg="img/time-bg.jpg">
        <div class="container">
            <div class="col-lg-6 text-center">
                <div class="section-title">
                    <h2>Deal Of The Week</h2>
                    <p>Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed<br /> do ipsum dolor sit amet,
                        consectetur adipisicing elit </p>
                    <div class="product-price">
                        $35.00
                        <span>/ HanBag</span>
                    </div>
                </div>
                <div class="countdown-timer" id="countdown">
                    <div class="cd-item">
                        <span>56</span>
                        <p>Days</p>
                    </div>
                    <div class="cd-item">
                        <span>12</span>
                        <p>Hrs</p>
                    </div>
                    <div class="cd-item">
                        <span>40</span>
                        <p>Mins</p>
                    </div>
                    <div class="cd-item">
                        <span>52</span>
                        <p>Secs</p>
                    </div>
                </div>
                <a href="#" class="primary-btn">Shop Now</a>
            </div>
        </div>
    </section>
    <!-- Deal Of The Week Section End -->

    <!-- Man Banner Section Begin -->
    <section class="man-banner spad">
        <div class="container-fluid">
            <div class="row">
                <div class="col-lg-8">
                    <div class="filter-control">
                    </div>
                    <div class="product-slider owl-carousel">
                        <?php
                        // Fetch products with 'Women' tag to show on man-banner as requested
                        $man_sql = "SELECT * FROM product WHERE tags = 'Men'";
                        $man_result = $conn->query($man_sql);
                        if ($man_result && $man_result->num_rows > 0):
                            while ($row = $man_result->fetch_assoc()):
                                $img = 'img/hero-1.jpg';
                                if (!empty($row['imgname'])) {
                                    $parts = explode(',', $row['imgname']);
                                    foreach ($parts as $p) {
                                        $p = trim($p);
                                        if ($p !== '') {
                                            $img = resolve_product_image_index($p, true);
                                            break;
                                        }
                                    }
                                }
                                $isOutOfStock = isset($row['quantity']) && $row['quantity'] <= 0;
                        ?>
                        <div class="product-item <?php echo $isOutOfStock ? 'out-of-stock' : ''; ?>">
                            <div class="pi-pic">
                                <img src="<?php echo htmlspecialchars($img); ?>" alt="<?php echo htmlspecialchars($row['name']); ?>">
                                <?php if ($isOutOfStock): ?><div class="out-of-stock-badge">OUT OF STOCK</div><?php endif; ?>
                                <?php if ($row['sale'] ?? false): ?><div class="sale">Sale</div><?php endif; ?>
                                <ul>
                                    <li class="quick-view"><a href="product.php?id=<?php echo $row['p_id']; ?>">+ Quick View</a></li>
                                </ul>
                            </div> 
                            <div class="pi-text">
                                <div class="category-name"><?php echo htmlspecialchars($row['category']); ?></div>
                                <a href="product.php?id=<?php echo $row['p_id']; ?>">
                                    <h5><?php echo htmlspecialchars($row['name']); ?></h5>
                                </a>
                                <div class="product-price">
                                    &#8369;<?php echo number_format((float)$row['price'], 2); ?>
                                    <?php if (!empty($row['old_price'])): ?><span>&#8369;<?php echo number_format((float)$row['old_price'], 2); ?></span><?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <?php
                            endwhile;
                        else:
                        ?>
                        <div class="product-item">
                            <div class="pi-pic">
                                <img src="img/hero-1.jpg" alt="No products">
                            </div>
                            <div class="pi-text">
                                <div class="category-name">No products</div>
                                <h5>No products found.</h5>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="col-lg-3 offset-lg-1">
                    <div class="product-large set-bg m-large" data-setbg="img/products/man-large.jpg">
                        <h2>Men’s</h2>
                        <a href="#">Discover More</a>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- Man Banner Section End -->

    <!-- Instagram Section Begin -->
    <div class="instagram-photo">
        <div class="insta-item set-bg" data-setbg="img/insta-1.jpg">
            <div class="inside-text">
                <i class="ti-instagram"></i>
                <h5><a href="#">A & M Clothing</a></h5>
            </div>
        </div>
        <div class="insta-item set-bg" data-setbg="img/insta-2.jpg">
            <div class="inside-text">
                <i class="ti-instagram"></i>
                <h5><a href="#">A & M Clothing</a></h5>
            </div>
        </div>
        <div class="insta-item set-bg" data-setbg="img/insta-3.jpg">
            <div class="inside-text">
                <i class="ti-instagram"></i>
                <h5><a href="#">A & M Clothing</a></h5>
            </div>
        </div>
        <div class="insta-item set-bg" data-setbg="img/insta-4.jpg">
            <div class="inside-text">
                <i class="ti-instagram"></i>
                <h5><a href="#">A & M Clothing</a></h5>
            </div>
        </div>
        <div class="insta-item set-bg" data-setbg="img/insta-5.jpg">
            <div class="inside-text">
                <i class="ti-instagram"></i>
                <h5><a href="#">A & M Clothing</a></h5>
            </div>
        </div>
        <div class="insta-item set-bg" data-setbg="img/insta-6.jpg">
            <div class="inside-text">
                <i class="ti-instagram"></i>
                <h5><a href="#">A & M Clothing</a></h5>
            </div>
        </div>
    </div>
    <!-- Instagram Section End -->
    <br> <br>
    <!-- Latest Blog Section removed -->
   
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
    <script src="js/main.bundle.js"></script>
</body>

</html>