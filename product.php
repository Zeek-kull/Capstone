<?php
ob_start();
session_start();
include 'header.php';
include 'lib/connection.php';

// Validate product_id parameter
if (!isset($_GET['id']) || empty($_GET['id'])) {
    if (session_status() == PHP_SESSION_NONE) session_start();
    $_SESSION['error_message'] = 'Invalid product ID!';
    header('Location: default.php');
    exit();
}

$product_id = intval($_GET['id']);

// Debugging: Output the product ID (after validation, as HTML comment)
echo "<!-- Debug: Product ID: " . htmlspecialchars($product_id) . " -->";

// Fetch product details
$sql = "SELECT * FROM product WHERE p_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $product_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $product = $result->fetch_assoc();
} else {
    if (session_status() == PHP_SESSION_NONE) session_start();
    $_SESSION['error_message'] = 'Product not found!';
    header('Location: default.php');
    exit();
}

// Helper: resolve a product image filename to an existing web path (prefer thumbnails when asked)
function resolve_product_image($filename, $preferThumb = true) {
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

// Build images array from CSV stored in imgname
$images = [];
if (!empty($product['imgname'])) {
    $parts = explode(',', $product['imgname']);
    foreach ($parts as $p) {
        $p = trim($p);
        if ($p !== '') $images[] = $p;
    }
}
if (empty($images)) $images[] = 'hero-1.jpg';
$main_img = resolve_product_image($images[0], false);

  // Handle add to cart from product detail page
  if (isset($_POST['add_to_cart'])) {
    if (isset($_SESSION['auth']) && $_SESSION['auth'] == 1) { 
      $user_id = $_SESSION['userid'];
      $product_name = $_POST['product_name'];
      $product_price = $_POST['product_price'];
      $product_id = $_POST['product_id'];
      $product_quantity = $_POST['quantity'];

      // Check if the product is already in the cart
      $select_cart = mysqli_query($conn, "SELECT * FROM `cart` WHERE product_id = '$product_id' AND user_id = '$user_id'");
            if (mysqli_num_rows($select_cart) > 0) {
        // Product exists, update quantity by adding new quantity
        $cart_item = mysqli_fetch_assoc($select_cart);
        $new_quantity = $cart_item['quantity'] + $product_quantity;
        
        // Update the existing cart item
                $update_query = mysqli_query($conn, "UPDATE `cart` SET quantity = '$new_quantity' WHERE product_id = '$product_id' AND user_id = '$user_id'");
                // set flash message
                $_SESSION['success_message'] = 'Product quantity updated in cart';
      } else {
        // Product doesn't exist, insert new cart item
        $insert_product = mysqli_query($conn, "INSERT INTO `cart`(user_id, product_id, quantity, price) VALUES('$user_id', '$product_id', '$product_quantity', '$product_price')");
                $_SESSION['success_message'] = 'Product added to cart successfully';
      }
      header('Location: product.php?id=' . $product_id);
      exit();
    } else {
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
                        <span>Detail</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Breadcrumb Section Begin -->

    <?php include __DIR__ . '/includes/flash.php'; ?>

    <!-- Product Shop Section Begin -->
    <section class="product-shop spad page-details">
        <div class="container">
            <div class="row">
                <div class="col-lg-12">
                    <div class="row">
                        <div class="col-lg-6">
                                <div class="product-pic-zoom" style="position: relative;">
                                <?php if ($product['quantity'] <= 0): ?>
                                    <div class="out-of-stock-badge">Out of Stock</div>
                                <?php endif; ?>
                                <img id="main-product-image" src="<?php echo $main_img; ?>" class="product-big-img img-fluid <?php echo $product['quantity'] <= 0 ? 'out-of-stock-img' : ''; ?>" alt="<?php echo htmlspecialchars($product['name'], ENT_QUOTES); ?>">
                                <div class="zoom-icon">
                                    <i class="fa fa-search-plus"></i>
                                </div>
                            </div>
                            <div class="product-thumbs">
                                <div id="thumbs-track" class="product-thumbs-track ps-slider owl-carousel">
                                    <?php foreach ($images as $img):
                                        $thumb = resolve_product_image($img, true);
                                        $full = resolve_product_image($img, false);
                                    ?>
                                    <div class="pt" data-full="<?php echo $full; ?>" data-thumb="<?php echo $thumb; ?>" data-imgbigurl="<?php echo $full; ?>">
                                        <img src="<?php echo $thumb; ?>" alt="">
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="product-details">

                               <form action="set_lensid.php" method="post" style="display:inline;">
                                   <input type="hidden" name="lensid" value="<?php echo htmlspecialchars($product['lens_id']); ?>">
                                   <input type="hidden" name="redirect" value="snap-camerakit-demo/index.html">
                                   <button type="submit" class="site-btn login-btn" aria-label="AR Try-On" title="AR Try-On">AR</button>
                               </form>

                                <div class="pd-title">
                                    <h3><?php echo $product['name']; ?></h3>
                                </div>
                                <div class="pd-desc">
                                    <h4>&#8369;<?php echo number_format((float)$product['price'], 2); ?></h4>
                                    
                                    <div class="availability-status mb-3">
                                        <?php if ($product['quantity'] <= 0): ?>
                                            <span class="text-danger font-weight-bold">Out of Stock</span>
                                        <?php else: ?>
                                            <span class="text-success font-weight-bold"><?php echo $product['quantity']; ?> in stock</span>
                                        <?php endif; ?>
                                    </div>

                                    <form action="<?php echo $_SERVER['PHP_SELF'] . '?id=' . $product_id; ?>" method="post">
                                        <input type="hidden" name="product_id" value="<?php echo $product['p_id']; ?>">
                                        <input type="hidden" name="product_name" value="<?php echo $product['name']; ?>">
                                        <input type="hidden" name="product_price" value="<?php echo $product['price']; ?>">


                                        <!-- Size selection removed per requirements -->
        
                                        <div class="form-group">
                                            <label for="quantity">Quantity:</label>
                                            <input type="number" class="form-control" id="quantity" name="quantity" value="1" min="1" max="<?php echo $product['quantity'] > 0 ? $product['quantity'] : 1; ?>" <?php echo $product['quantity'] <= 0 ? 'disabled' : ''; ?>>
                                        </div>
        
                                        <?php if (isset($_SESSION['auth']) && $_SESSION['auth'] == 1): ?>
                                            <?php if ($product['quantity'] <= 0): ?>
                                                <button type="button" class="site-btn login-btn" disabled>Out of Stock</button>
                                            <?php else: ?>
                                                <button type="submit" class="site-btn login-btn" name="add_to_cart">Add to Cart</button>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <a href="login.php" class="site-btn login-btn">Login to Add to Cart</a>
                                        <?php endif; ?>
                                    </form>
                                </div>
                                
                                
                                            
                               
                                <div class="pd-share">
                                    <div class="pd-social">
                                        <div class="pd-share">
                                            <div class="pd-social">
                                                <a href="#"><i class="ti-facebook"></i></a>
                                                <a href="#"><i class="ti-twitter-alt"></i></a>
                                                <a href="#"><i class="ti-linkedin"></i></a>
                                            </div>
                                        </div>
                    </div>
                    <div class="product-tab">
                        <div class="tab-item">
                            <ul class="nav" role="tablist">
                                <li>
                                            <li>
                                                <a class="active" data-toggle="tab" href="#tab-1" role="tab">DESCRIPTION</a>
                                            </li>
                        </div>
                        <div class="tab-item-content">
                            <div class="tab-content">
                                <div class="tab-pane fade-in active" id="tab-1" role="tabpanel">
                                            <div class="tab-pane fade-in active" id="tab-1" role="tabpanel">
                                                <div class="product-content">
                                                    <div class="row">
                                                        <div class="col-12">
                                                            <?php echo nl2br(htmlspecialchars($product['description'] ?? 'No description available.')); ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- Product Shop Section End -->

    <!-- Related Products Section End -->
    <div class="related-products spad">
        <div class="container mt-5">
            <div class="row">
                <div class="col-lg-12">
                    <div class="section-title">
                        <h2>Related Products</h2>
                    </div>
                </div>
            </div>
            <div class="row g-4">
    <?php
    $related_sql = "SELECT * FROM product WHERE p_id != ? ORDER BY RAND() LIMIT 4"; // Use 4 for perfect alignment
    $stmt = $conn->prepare($related_sql);
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $related_result = $stmt->get_result();

    while ($related = $related_result->fetch_assoc()) {
    ?>
        <div class="col-lg-3 col-md-6 col-sm-6 d-flex">
            <div class="product-item flex-fill d-flex flex-column <?php echo $related['quantity'] <= 0 ? 'out-of-stock' : ''; ?>">
                <div class="pi-pic" style="height: 250px; display: flex; justify-content: center; align-items: center; overflow: hidden; position: relative;">
                    <?php if ($related['quantity'] <= 0): ?>
                        <div class="out-of-stock-badge">Out of Stock</div>
                    <?php endif; ?>
                    <?php
                        // resolve related product image (use first from CSV)
                        $rel_images = [];
                        if (!empty($related['imgname'])) {
                            $rel_images = array_filter(array_map('trim', explode(',', $related['imgname'])));
                        }
                        $rel_first = !empty($rel_images) ? $rel_images[0] : '';
                        $rel_img_src = resolve_product_image($rel_first, true);
                    ?>
                    <img src="<?php echo $rel_img_src; ?>" alt="<?php echo htmlspecialchars($related['name'], ENT_QUOTES); ?>" style="max-height: 100%; max-width: 100%; object-fit: cover;">
                    <ul>
                        <li style="width: 75%;">
                            <a href="product.php?id=<?php echo $related['p_id']; ?>" class="product-link">+ Quick View</a>
                        </li>
                    </ul>
                </div>
                <div class="pi-text flex-grow-1 d-flex flex-column">
                        <div class="category-name"><?php echo htmlspecialchars($related['category'] ?? ''); ?></div>
                        <a href="product.php?id=<?php echo $related['p_id']; ?>">
                            <h5><?php echo htmlspecialchars($related['name']); ?></h5>
                        </a>
                        <div class="product-price mb-2">
                            &#8369;<?php echo number_format((float)$related['price'], 2); ?>
                        </div>
                        <!-- Buttons removed from related-product tiles: Add to Cart and Out of Stock button intentionally omitted -->
                    </div>
            </div>
        </div>
    <?php } ?>
</div>

        </div>
    </div>
    <!-- Related Products Section End -->

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
    <script>
    // Wire thumbnail clicks to update main image and reinit zoom
    $(document).ready(function(){
        $('#thumbs-track .pt').on('click', function(){
            var full = $(this).attr('data-full');
            if (full) {
                $('#main-product-image').attr('src', full);
                // reinit zoom helper if available
                if (window.initProductZoom) window.initProductZoom();
            }
        });
        // ensure zoom initialized on page load
        if (window.initProductZoom) window.initProductZoom();
    });
    </script>

    
</body>

</html>