<?php
include '../lib/connection.php';

// Get the selected category from AJAX request
$selectedCategory = isset($_POST['category']) ? $_POST['category'] : '';

// Build query based on selected category
if (!empty($selectedCategory)) {
    $sql = "SELECT * FROM product WHERE category = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "s", $selectedCategory);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
} else {
    $sql = "SELECT * FROM product";
    $result = mysqli_query($conn, $sql);
}

// Generate HTML for filtered products
if (mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        $isOutOfStock = isset($row['quantity']) && $row['quantity'] <= 0;
        ?>
        <div class="col-lg-4 col-sm-6">
            <form method="POST" action="shop.php">
                <div class="product-item <?php echo $isOutOfStock ? 'out-of-stock' : ''; ?>">
                    <div class="pi-pic" style="width: 100%; height: 250px; position: relative;">
                        <?php
                        // Resolve possible CSV imgname into a usable path (prefer thumbnails)
                        $product_img = 'img/hero-1.jpg';
                        if (!empty($row['imgname'])) {
                            $names = array_filter(array_map('trim', explode(',', $row['imgname'])));
                            if (count($names) > 0) {
                                $first = $names[0];
                                // candidate list with filesystem path (fs) and web path (web)
                                $candidates = [
                                    ['fs' => __DIR__ . '/../img/A&M/thumbs/' . $first, 'web' => 'img/A&M/thumbs/' . $first],
                                    ['fs' => __DIR__ . '/../admin/uploaded_products/thumbs/' . $first, 'web' => 'admin/uploaded_products/thumbs/' . $first],
                                    ['fs' => __DIR__ . '/../img/A&M/' . $first, 'web' => 'img/A&M/' . $first],
                                    ['fs' => __DIR__ . '/../admin/uploaded_products/' . $first, 'web' => 'admin/uploaded_products/' . $first],
                                ];
                                foreach ($candidates as $c) {
                                    if (file_exists($c['fs'])) {
                                        $product_img = $c['web'];
                                        break;
                                    }
                                }
                            }
                        }
                        ?>
                        <img src="<?php echo htmlspecialchars($product_img); ?>" alt="<?php echo htmlspecialchars($row['name']); ?>" <?php echo $isOutOfStock ? 'style="opacity: 0.5;"' : ''; ?>>
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
                        <div class="category-name"><?php echo htmlspecialchars($row['category'] ?? ''); ?></div>
                        <a href="product.php?id=<?php echo $row['p_id']; ?>">
                            <h5><?php echo htmlspecialchars($row["name"]) ?></h5>
                        </a>
                        <div class="product-price">
                            &#8369;<?php echo number_format((float)$row["price"], 2); ?>
                        </div>
                        <div>
                            <?php if ($isOutOfStock): ?>
                                <button type="button" class="site-btn login-btn w-100" disabled style="background-color: #ccc; cursor: not-allowed;">Out of Stock</button>
                            <?php elseif (!isset($_SESSION['auth']) || $_SESSION['auth'] != 1): ?>
                                <a href="login.php" class="site-btn login-btn w-100">Login to Add to Cart</a>
                            <?php endif; ?>
                        </div>
                        <input type="hidden" name="product_id" value="<?php echo $row['p_id']; ?>">
                        <input type="hidden" name="product_name" value="<?php echo $row['name']; ?>">
                        <input type="hidden" name="product_price" value="<?php echo htmlspecialchars($row['price']); ?>">
                    </div>
                </div>
            </form>
        </div>
        <?php
    }
} else {
    echo "<div class='col-12'><p>No products found in this category.</p></div>";
}

mysqli_close($conn);
?>
