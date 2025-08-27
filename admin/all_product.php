<?php
SESSION_START();

if(isset($_SESSION['admin_auth']))
{
   if($_SESSION['admin_auth']!=1)
   {
       header("location:a_login.php");
   }
}
else
{
   header("location:a_login.php");
}
 include 'header.php';
 include 'lib/connection.php';

  $sql = "SELECT * FROM product";
  $result = $conn -> query ($sql);

 if(isset($_POST['update_update_btn'])){
  $name = $_POST['update_name'];
  $category = $_POST['update_category'];
  $tag = $_POST['update_tag'];
  $quantity = $_POST['update_quantity'];
  $price = $_POST['update_Price'];
  $update_id = $_POST['update_id'];
  $update_quantity_query = mysqli_query($conn, "UPDATE `product` SET quantity = '$quantity' , name='$name' , category='$category' , tags='$tag' , price='$price'  WHERE p_id = '$update_id'");
  if($update_quantity_query){
     header('location:all_product.php');
  };
};

 if(isset($_GET['remove'])){
  $remove_id = $_GET['remove'];
  mysqli_query($conn, "DELETE FROM `product` WHERE p_id = '$remove_id'");
  header('location:all_product.php');
};

// Get product stats
$total_products = mysqli_num_rows($result);
$low_stock_count = 0;
$total_value = 0;

if ($total_products > 0) {
    mysqli_data_seek($result, 0);
    while($row = mysqli_fetch_assoc($result)) {
        if ($row['quantity'] < 10) {
            $low_stock_count++;
        }
        $total_value += ($row['price'] * $row['quantity']);
    }
    mysqli_data_seek($result, 0);
}

// Fetch distinct categories for dynamic filter
$categories = array();
$catSql = "SELECT DISTINCT category FROM product WHERE category IS NOT NULL AND TRIM(category) != '' ORDER BY category";
$catResult = $conn->query($catSql);
if($catResult){
  while($crow = mysqli_fetch_assoc($catResult)){
    $categories[] = $crow['category'];
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Management - Admin Panel</title>
  <link rel="stylesheet" href="css/all_products.css">
  <!-- Modal styles moved to admin/css/all_products.css -->
</head>
<body>

<div class="products-body">
  <!-- Page Header -->
  <div class="page-header">
    <div>
      <h1 class="page-title">Product Management</h1>
      <p class="page-subtitle">Manage your product inventory with ease</p>
    </div>
  </div>

  <!-- Statistics Cards -->
  <div class="stats-grid">
    <div class="stat-card">
      <div class="stat-value"><?php echo $total_products; ?></div>
      <div class="stat-label">Total Products</div>
    </div>
    <div class="stat-card">
      <div class="stat-value"><?php echo $low_stock_count; ?></div>
      <div class="stat-label">Low Stock Items</div>
    </div>
    <div class="stat-card">
      <div class="stat-value">₱<?php echo number_format($total_value, 2); ?></div>
      <div class="stat-label">Total Inventory Value</div>
    </div>
  </div>

  <!-- Controls Section -->
  <div class="controls-section">
    <div class="search-box">
      <input type="text" placeholder="Search products..." id="searchInput">
    </div>
    <div class="filter-group">
      <label for="categoryFilter">Category:</label>
  <select id="categoryFilter" class="form-select cp-form-control-sm">
        <option value="">All Categories</option>
        <?php
        if(!empty($categories)){
          foreach($categories as $cat){
            echo '<option value="'.htmlspecialchars($cat).'">'.htmlspecialchars($cat)."</option>";
          }
        }
        ?>
      </select>
    </div>
    <div class="filter-group">
      <label for="stockFilter">Stock:</label>
  <select id="stockFilter" class="form-select cp-form-control-sm">
        <option value="">All Stock</option>
        <option value="low">Low Stock (<10)</option>
        <option value="in">In Stock</option>
        <option value="out">Out of Stock</option>
      </select>
    </div>
  </div>

  <!-- Products Grid -->
  <div class="products-grid" id="productsGrid">
    <?php
    if (mysqli_num_rows($result) > 0) {
      while($row = mysqli_fetch_assoc($result)) {
        $stock_status = $row['quantity'] > 0 ? ($row['quantity'] < 10 ? 'low' : 'in') : 'out';
    ?>
  <div class="product-card" data-pid="<?php echo $row['p_id']; ?>" data-category="<?php echo htmlspecialchars($row['category']); ?>" data-stock="<?php echo $stock_status; ?>" data-name="<?php echo htmlspecialchars(strtolower($row['name'])); ?>">
      <img src="product_img/<?php echo htmlspecialchars($row['imgname']); ?>" alt="<?php echo htmlspecialchars($row['name']); ?>" class="product-image" style="cursor: pointer;">
      
      <div class="product-content">
        <div class="product-header">
          <h3 class="product-title"><?php echo htmlspecialchars($row['name']); ?></h3>
          <span class="product-category"><?php echo htmlspecialchars($row['category']); ?></span>
        </div>

        <div class="product-details">
          <div class="detail-row">
            <span class="detail-label">Tag</span>
            <span class="detail-value"><?php echo htmlspecialchars($row['tags']); ?></span>
          </div>
          <div class="detail-row">
            <span class="detail-label">Stock</span>
            <span class="detail-value <?php echo $stock_status === 'low' ? 'text-warning' : ($stock_status === 'out' ? 'text-danger' : 'text-success'); ?>">
              <?php echo $row['quantity']; ?> units
            </span>
          </div>
          <div class="detail-row">
            <span class="detail-label">Price</span>
            <span class="price-tag">₱<?php echo number_format($row['price'], 2); ?></span>
          </div>
        </div>

        <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post" class="product-form">
          <input type="hidden" name="update_id" value="<?php echo $row['p_id']; ?>">
          
          <div class="form-group">
            <label for="name_<?php echo $row['p_id']; ?>">Product Name</label>
            <input type="text" name="update_name" id="name_<?php echo $row['p_id']; ?>" value="<?php echo htmlspecialchars($row['name']); ?>" class="form-control cp-form-control" required>
          </div>

          <div class="form-group">
            <label for="category_<?php echo $row['p_id']; ?>">Category</label>
            <input type="text" name="update_category" id="category_<?php echo $row['p_id']; ?>" value="<?php echo htmlspecialchars($row['category']); ?>" class="form-control cp-form-control" required>
          </div>

          <div class="form-group">
            <label for="tag_<?php echo $row['p_id']; ?>">Tag</label>
            <select name="update_tag" id="tag_<?php echo $row['p_id']; ?>" class="cp-form-control form-select">
              <option value="">-- Select Tag --</option>
              <option value="Men" <?php if($row['tags'] == "Men") echo "selected"; ?>>Men</option>
              <option value="Women" <?php if($row['tags'] == "Women") echo "selected"; ?>>Women</option>
              <option value="Kids" <?php if($row['tags'] == "Kids") echo "selected"; ?>>Kid's</option>
            </select>
          </div>

          <div class="form-group">
            <label for="quantity_<?php echo $row['p_id']; ?>">Quantity</label>
            <input type="number" name="update_quantity" id="quantity_<?php echo $row['p_id']; ?>" value="<?php echo $row['quantity']; ?>" class="form-control cp-form-control" min="0" required>
          </div>

          <div class="form-group">
            <label for="price_<?php echo $row['p_id']; ?>">Price</label>
            <input type="number" name="update_Price" id="price_<?php echo $row['p_id']; ?>" value="<?php echo $row['price']; ?>" class="form-control cp-form-control" step="0.01" min="0" required>
          </div>

          <div class="product-actions">
            <button type="submit" name="update_update_btn" class="btn btn-primary btn-full">
              💾 Update Product
            </button>
            <a href="all_product.php?remove=<?php echo $row['p_id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this product?')">
              🗑️ Delete
            </a>
          </div>
        </form>
      </div>
    </div>
    <?php 
      }
    } else {
    ?>
    <div class="empty-state">
      <div class="empty-icon">📦</div>
      <h3 class="empty-title">No Products Found</h3>
      <p class="empty-description">Get started by adding your first product to the inventory.</p>
      <a href="add_product.php" class="btn btn-primary">Add Product</a>
    </div>
    <?php } ?>
  </div>
</div>

<script>
// Simple search and filter functionality
document.addEventListener('DOMContentLoaded', function() {
  const searchInput = document.getElementById('searchInput');
  const categoryFilter = document.getElementById('categoryFilter');
  const stockFilter = document.getElementById('stockFilter');
  const productCards = document.querySelectorAll('.product-card');

  function filterProducts() {
    const searchTerm = searchInput.value.toLowerCase();
    const categoryValue = categoryFilter.value;
    const stockValue = stockFilter.value;

    productCards.forEach(card => {
      const productName = card.getAttribute('data-name');
      const productCategory = card.getAttribute('data-category');
      const productStock = card.getAttribute('data-stock');

      const matchesSearch = productName.includes(searchTerm);
      const matchesCategory = !categoryValue || productCategory === categoryValue;
      const matchesStock = !stockValue || productStock === stockValue;

      if (matchesSearch && matchesCategory && matchesStock) {
        card.style.display = 'block';
      } else {
        card.style.display = 'none';
      }
    });
  }

  searchInput.addEventListener('input', filterProducts);
  categoryFilter.addEventListener('change', filterProducts);
  stockFilter.addEventListener('change', filterProducts);
});
</script>
    
<!-- Custom modal for product form (namespaced to avoid Bootstrap conflicts) -->
<div id="cpProductModal" aria-hidden="true">
  <div class="cp-modal-content" role="dialog" aria-modal="true">
    <button class="cp-modal-close" aria-label="Close">&times;</button>
    <div id="cpModalFormContainer"></div>
  </div>
</div>

<script>
// Modal logic: move the original form into modal when card clicked to preserve file inputs
document.addEventListener('DOMContentLoaded', function(){
  // use custom, namespaced modal elements so Bootstrap's JS doesn't target them
  const modal = document.getElementById('cpProductModal');
  const modalContainer = document.getElementById('cpModalFormContainer');
  let activePlaceholder = null;
  let previousActiveElement = null;
  const focusableSelector = 'a[href], area[href], input:not([disabled]):not([type="hidden"]), select:not([disabled]), textarea:not([disabled]), button:not([disabled]), [tabindex]:not([tabindex="-1"])';
  let boundKeydown = null;

  function openModalWithForm(form, pid){
    // create a placeholder where the form currently is so we can restore it later
    const placeholder = document.createElement('div');
    placeholder.className = 'form-placeholder';
    placeholder.dataset.pid = pid;
    form.parentNode.insertBefore(placeholder, form);
    // move the form into modal
    modalContainer.innerHTML = '';
    modalContainer.appendChild(form);
    modal.classList.add('show');
    modal.setAttribute('aria-hidden','false');
    activePlaceholder = placeholder;
    // store previously focused element to restore on close
    previousActiveElement = document.activeElement;

    // focus management: focus first focusable element inside modal
    const focusable = modal.querySelectorAll(focusableSelector);
    if(focusable.length){
      // prefer first input inside the moved form, else the modal close button
      let first = modal.querySelector('input, select, textarea, button');
      if(!first) first = focusable[0];
      first.focus();
    } else {
      // fallback to close button
      const closeBtn = modal.querySelector('.cp-modal-close');
      if(closeBtn) closeBtn.focus();
    }

    // attach keydown handler for Escape and Tab focus trap
    boundKeydown = function(e){
      // Close on Escape
      if(e.key === 'Escape' || e.key === 'Esc'){
        e.preventDefault();
        closeModal();
        return;
      }

      // Focus trap on Tab
      if(e.key === 'Tab'){
        const nodes = Array.from(modal.querySelectorAll(focusableSelector));
        if(nodes.length === 0) return;
        const firstNode = nodes[0];
        const lastNode = nodes[nodes.length - 1];
        if(e.shiftKey){ // backward
          if(document.activeElement === firstNode){
            e.preventDefault();
            lastNode.focus();
          }
        } else { // forward
          if(document.activeElement === lastNode){
            e.preventDefault();
            firstNode.focus();
          }
        }
      }
    };
    document.addEventListener('keydown', boundKeydown);
  }

  function restoreForm(){
    if(!activePlaceholder) return;
    const pid = activePlaceholder.dataset.pid;
    const formInModal = modalContainer.querySelector('form.product-form');
    if(formInModal){
      activePlaceholder.parentNode.insertBefore(formInModal, activePlaceholder);
    }
    activePlaceholder.remove();
    activePlaceholder = null;
    modalContainer.innerHTML = '';
    // remove keydown listener
    if(boundKeydown) {
      document.removeEventListener('keydown', boundKeydown);
      boundKeydown = null;
    }
    // restore focus to previously active element
    if(previousActiveElement && typeof previousActiveElement.focus === 'function'){
      try{ previousActiveElement.focus(); }catch(err){}
    }
    previousActiveElement = null;
  }

  document.querySelectorAll('.product-card').forEach(card => {
    card.addEventListener('click', function(e){
      // ignore clicks on inner action buttons/controls (they use .product-actions)
      if(e.target.closest('.product-actions')) return;
      // if modal already open, restore any moved form first
      if(modal.classList.contains('show')) restoreForm();
      const pid = this.getAttribute('data-pid');
      const form = this.querySelector('form.product-form');
      if(!form) return;
      openModalWithForm(form, pid);
    });
  });

  // close modal handlers
  modal.querySelector('.cp-modal-close').addEventListener('click', function(){
    restoreForm();
    modal.classList.remove('show');
    modal.setAttribute('aria-hidden','true');
  });

  modal.addEventListener('click', function(e){ if(e.target === modal){ restoreForm(); modal.classList.remove('show'); modal.setAttribute('aria-hidden','true'); } });

  // helper to close modal programmatically (used by Escape handler)
  function closeModal(){
    restoreForm();
    modal.classList.remove('show');
    modal.setAttribute('aria-hidden','true');
  }
});
</script>
</body>
</html>
