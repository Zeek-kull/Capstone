    <?php
    include 'header.php';
    include 'lib/connection.php';

    // Get category or tags from URL parameter
    $selected_category = isset($_GET['category']) ? $_GET['category'] : null;
    $selected_tags = isset($_GET['tags']) ? $_GET['tags'] : null;

    // Determine which parameter to use for filtering
    $filter_value = $selected_tags ? $selected_tags : $selected_category;

    // Filter products based on tags or category (case-insensitive)
    if ($filter_value) {
        $fv = mysqli_real_escape_string($conn, $filter_value);
        $fv_lower = strtolower($fv);

        // 1) Exact tag match, case-insensitive
        $sql = "SELECT * FROM product WHERE LOWER(tags) = '" . $fv_lower . "'";
        $result = mysqli_query($conn, $sql);

        // 2) If no exact tag match, try tags containing the term (for comma-separated tags)
        if ($result && mysqli_num_rows($result) == 0) {
            $sql = "SELECT * FROM product WHERE LOWER(tags) LIKE '%" . $fv_lower . "%'";
            $result = mysqli_query($conn, $sql);
        }

        // 3) If still no results, try category exact match (case-insensitive)
        if ($result && mysqli_num_rows($result) == 0) {
            $sql = "SELECT * FROM product WHERE LOWER(category) = '" . $fv_lower . "'";
            $result = mysqli_query($conn, $sql);
        }
    } else {
        // Show all products if no filter specified
        $sql = "SELECT * FROM product";
        $result = mysqli_query($conn, $sql);
    }

    // Get all distinct categories for the filter dropdown (same as shop.php)
    $category_sql = "SELECT DISTINCT category FROM product ORDER BY category";
    $category_result = mysqli_query($conn, $category_sql);
    $categories = [];
    while ($rowC = mysqli_fetch_assoc($category_result)) {
        $categories[] = $rowC['category'];
    }
    
        // Determine breadcrumb label: prefer tags, then category, normalize 'Kid' => 'Kids'
        $breadcrumb_label = 'All Products';
        if ($filter_value) {
            // Use selected tag when present, otherwise category
            $raw_label = $selected_tags ? $selected_tags : $selected_category;
            $breadcrumb_label = ($raw_label === 'Kid') ? 'Kids' : $raw_label;
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
        <!-- header.php already includes styles -->
    </head>

    <body>

        <!-- Breadcrumb Section Begin -->
        <div class="breacrumb-section">
            <div class="container">
                <div class="row">
                    <div class="col-lg-12">
                        <div class="breadcrumb-text">
                            <a href="index.php"><i class="fa fa-home"></i> Home</a>

                                <span><?php echo htmlspecialchars($breadcrumb_label); ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Breadcrumb Section End -->

        <!-- Product Shop Section Begin -->
        <section class="product-shop spad">
            <div class="container">
                <div class="row">
                    <!-- Sidebar removed for this view -->
                    <div class="col-lg-12 order-1 order-lg-2">
                            <!-- Filter Dropdown removed for this view -->

                        <div class="product-list">
                            <div class="row" id="productsContainer">
                                <?php
                                if ($result && mysqli_num_rows($result) > 0) {
                                    while ($row = mysqli_fetch_assoc($result)) {
                                        $isOutOfStock = isset($row['quantity']) && $row['quantity'] <= 0;
                                ?>
                                    <div class="col-lg-4 col-sm-6">
                                        <form method="POST" action="">
                                            <div class="product-item <?php echo $isOutOfStock ? 'out-of-stock' : ''; ?>">
                                                <div class="pi-pic" style="width: 100%; height: 250px; position: relative;">
                                                            <?php
                                                            // resolve image (handle CSV imgname) and prefer thumbs/
                                                            $img_field = $row['imgname'] ?? '';
                                                            $first_img = '';
                                                            if ($img_field !== '') {
                                                                $parts = array_filter(array_map('trim', explode(',', $img_field)));
                                                                if (!empty($parts)) $first_img = $parts[0];
                                                            }
                                                            $img_src = 'img/hero-1.jpg';
                                                            if ($first_img) {
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
                                                                }
                                                            }
                                                            ?>
                                                            <img src="<?php echo htmlspecialchars($img_src); ?>" alt="<?php echo htmlspecialchars($row['name']); ?>" <?php echo $isOutOfStock ? 'style="opacity: 0.5;"' : ''; ?>>
                                                    <?php if ($isOutOfStock): ?>
                                                        <div class="out-of-stock-badge">OUT OF STOCK</div>
                                                    <?php endif; ?>
                                                    <div class="icon">
                                                       <i class="icon_heart_alt"></i>
                                                    </div>
                                                     <ul>
                                                        <li style="width:75%;"><a href="product.php?id=<?php echo $row['p_id']; ?>" class="product-link">+ Quick View</a></li>
                                                    </ul>
                                                </div>
                                                <div class="pi-text">
                                                    <div class="category-name"></div>
                                                
                                                    <a href="#">
                                                        <h5><?php echo htmlspecialchars($row["name"]) ?></h5>
                                                    </a> 
                                                    <div class="product-price">
                                                        &#8369;<?php echo number_format((float)$row["price"], 2); ?>                                            
                                                    </div>
                                                    <div>
                                                        <?php if ($isOutOfStock): ?>
                                                            <button type="button" class="site-btn login-btn w-100" disabled style="background-color: #ccc; cursor: not-allowed;">Out of Stock</button>
                                                        <?php elseif (isset($_SESSION['auth']) && $_SESSION['auth'] == 1): ?>
                                                            <button type="submit" class="site-btn login-btn w-100" name="add_to_cart">Add to Cart</button>
                                                        <?php else: ?>
                                                            <a href="login.php" class="site-btn login-btn w-100">Login to Add to Cart</a>
                                                        <?php endif; ?>
                                                    </div>
                                                    <input type="hidden" name="product_id" value="<?php echo $row['p_id']; ?>">
                                                    <input type="hidden" name="product_name" value="<?php echo htmlspecialchars($row['name']); ?>">
                                                    <input type="hidden" name="product_price" value="<?php echo htmlspecialchars($row['price']); ?>">
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                <?php
                                    }
                                } else {
                                    echo "<div class='col-12'><p>No products available.</p></div>";
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
    // No category filter on this page — JavaScript handler removed.
        </script>


    </body>

    </html>
