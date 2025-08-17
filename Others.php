<?php
  include 'header.php';
  include 'lib/connection.php';

  // Get category or tags from URL parameter
  $selected_category = isset($_GET['category']) ? $_GET['category'] : null;
  $selected_tags = isset($_GET['tags']) ? $_GET['tags'] : null;
  
  // Determine which parameter to use for filtering
  $filter_value = $selected_tags ? $selected_tags : $selected_category;
  
  // Filter products based on exact tags or category
  if ($filter_value) {
    // First, try to filter by exact tags match
    $sql = "SELECT * FROM product WHERE tags = '" . mysqli_real_escape_string($conn, $filter_value) . "'";
    $result = mysqli_query($conn, $sql);
    
    // If no results found with exact tags, try exact category match
    if (mysqli_num_rows($result) == 0) {
      $sql = "SELECT * FROM product WHERE category = '" . mysqli_real_escape_string($conn, $filter_value) . "'";
      $result = mysqli_query($conn, $sql);
    }
  } else {
    // Show all products if no filter specified
    $sql = "SELECT * FROM product";
    $result = mysqli_query($conn, $sql);
  }
?>
<div class="container">
    <h5>
        <?php 
        $display_category = isset($_GET['tags']) ? $_GET['tags'] : (isset($_GET['category']) ? $_GET['category'] : 'All Products');
        echo htmlspecialchars($display_category) . ' Collection';
        ?>
    </h5>

    <!-- Products Container -->
    <div class="container">
        <div class="row" id="productsContainer">
            <?php
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
                if ($filter_value) {
                    echo "<div class='col-12'><p>No products available in the '" . htmlspecialchars($filter_value) . "' category.</p></div>";
                } else {
                    echo "<div class='col-12'><p>No products available.</p></div>";
                }
            }
            ?>
        </div>
    </div>
</div>

<?php
  include 'footer.php';
?>
