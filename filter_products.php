<?php
include 'lib/connection.php';

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
        <div class="col-md-3 col-sm-6 col-6 product-item" data-category="<?php echo htmlspecialchars($row['category']); ?>">
            <div class="product-item">
                <a href="product_detail.php?id=<?php echo $row['p_id']; ?>" class="product-link">
                    <div class="product-image">
                        <img src="admin/product_img/<?php echo $row['imgname']; ?>" alt="<?php echo $row['name']; ?>">
                    </div>
                    <div class="product-info">
                        <h6><?php echo $row["name"]; ?></h6>
                        <span class="price"><?php echo number_format($row["price"], 2); ?></span>
                        <small class="text-muted d-block"><?php echo $row["category"]; ?></small>
                    </div>
                </a>
            </div>
        </div>
        <?php
    }
} else {
    echo "<div class='col-12'><p>No products found in this category.</p></div>";
}

mysqli_close($conn);
?>
