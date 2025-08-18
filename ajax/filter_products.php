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
        ?>
        <div class="col-lg-4 col-sm-6">
            <form method="POST" action="shop.php">
                <div class="product-item">
                    <div class="pi-pic" style="width: 100%; height: 250px;">
                        <img src="img/A&M/<?php echo $row['imgname']; ?>" alt="<?php echo $row['name']; ?>">
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
            </form>
        </div>
        <?php
    }
} else {
    echo "<div class='col-12'><p>No products found in this category.</p></div>";
}

mysqli_close($conn);
?>
