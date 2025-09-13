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
  // Normalize updated product name
  $name_raw = isset($_POST['update_name']) ? trim($_POST['update_name']) : '';
  $name_clean = preg_replace('/\s+/', ' ', $name_raw);
  if (function_exists('mb_convert_case')) {
    $name = mb_convert_case($name_clean, MB_CASE_TITLE, "UTF-8");
  } else {
    $name = ucwords(strtolower($name_clean));
  }
  $category = $_POST['update_category'];
  $tag = $_POST['update_tag'];
  // Sanitize and enforce description length
  $maxDesc = 300; // reasonable limit for a shirt description
  $description_raw = $_POST['update_description'] ?? '';
  $description = mb_substr(trim($description_raw), 0, $maxDesc);
  $quantity = $_POST['update_quantity'];
  $price = $_POST['update_Price'];
  $update_id = $_POST['update_id'];
  $update_quantity_query = mysqli_query($conn, "UPDATE `product` SET quantity = '$quantity' , name='$name' , category='$category' , tags='$tag' , description='$description' , price='$price'  WHERE p_id = '$update_id'");
  if($update_quantity_query){
    // set flash message and redirect
    if (session_status() == PHP_SESSION_NONE) session_start();
    $_SESSION['success_message'] = 'Product updated successfully.';
    header('location:all_product.php');
    exit();
  } else {
    if (session_status() == PHP_SESSION_NONE) session_start();
    $_SESSION['error_message'] = 'Failed to update product.';
  }
};

 if(isset($_GET['remove'])){
  $remove_id = $_GET['remove'];
  $del = mysqli_query($conn, "DELETE FROM `product` WHERE p_id = '$remove_id'");
  if (session_status() == PHP_SESSION_NONE) session_start();
  if($del){
    $_SESSION['success_message'] = 'Product deleted successfully.';
  } else {
    $_SESSION['error_message'] = 'Failed to delete product.';
  }
  header('location:all_product.php');
  exit();
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
  <?php include __DIR__ . '/../includes/flash.php'; ?>
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
  <select id="categoryFilter" class="form-select cp-form-control-sm" aria-label="Filter by category">
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
  <select id="stockFilter" class="form-select cp-form-control-sm" aria-label="Filter by stock status">
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
    <?php
    // resolve image: handle CSV imgname and prefer thumbs/ folders
    $img_field = $row['imgname'] ?? '';
    $first_img = '';
    if ($img_field !== '') {
      $parts = array_filter(array_map('trim', explode(',', $img_field)));
      if (!empty($parts)) $first_img = $parts[0];
    }
    // Default web path from admin folder (go up to root for shared images)
    $img_src = '../img/hero-1.jpg';
    if ($first_img) {
      // Filesystem checks (absolute paths)
      $thumbU_fs = __DIR__ . '/uploaded_products/thumbs/' . $first_img;
      $origU_fs  = __DIR__ . '/uploaded_products/' . $first_img;
      $thumbA_fs = __DIR__ . '/../img/A&M/thumbs/' . $first_img;
      $origA_fs  = __DIR__ . '/../img/A&M/' . $first_img;

      if (file_exists($thumbU_fs)) {
        // Web path relative to admin folder
        $img_src = 'uploaded_products/thumbs/' . $first_img;
      } elseif (file_exists($origU_fs)) {
        $img_src = 'uploaded_products/' . $first_img;
      } elseif (file_exists($thumbA_fs)) {
        $img_src = '../img/A&M/thumbs/' . $first_img;
      } elseif (file_exists($origA_fs)) {
        $img_src = '../img/A&M/' . $first_img;
      }
    }
    ?>
    <img src="<?php echo htmlspecialchars($img_src); ?>" alt="<?php echo htmlspecialchars($row['name']); ?>" class="product-image" style="cursor: pointer;">
      
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
            <input type="text" name="update_name" id="name_<?php echo $row['p_id']; ?>" value="<?php echo htmlspecialchars($row['name']); ?>" class=" cp-form-control" required>
          </div>

          <div class="form-group">
            <label for="description_<?php echo $row['p_id']; ?>">Description</label>
            <textarea name="update_description" id="description_<?php echo $row['p_id']; ?>" class="cp-form-control" rows="3"><?php echo htmlspecialchars($row['description']); ?></textarea>
            <div class="small text-muted mt-1">Remaining: <span class="desc-remaining" data-max="300" id="desc_remaining_<?php echo $row['p_id']; ?>">300</span> characters</div>
          </div>

          <div class="form-group">
            <label for="category_select_<?php echo $row['p_id']; ?>">Category</label>
            <select name="update_category_select" id="category_select_<?php echo $row['p_id']; ?>" class="cp-form-control form-select" aria-label="Select category for product <?php echo htmlspecialchars($row['p_id']); ?>">
              <?php if(!empty($categories)){ foreach($categories as $cat){ ?>
                <option value="<?php echo htmlspecialchars($cat); ?>" <?php if($row['category'] == $cat) echo 'selected'; ?>><?php echo htmlspecialchars($cat); ?></option>
              <?php } } ?>
              <option value="new">Add new category...</option>
            </select>
            <input type="text" id="new_category_<?php echo $row['p_id']; ?>" class="cp-form-control mt-2" placeholder="Enter new category" style="display:none;">
            <!-- Hidden field that the server expects (update_category) will be kept in sync by JS -->
            <input type="hidden" name="update_category" id="update_category_hidden_<?php echo $row['p_id']; ?>" value="<?php echo htmlspecialchars($row['category']); ?>">
          </div>

          <div class="form-group">
            <label for="tag_<?php echo $row['p_id']; ?>">Tag</label>
            <select name="update_tag" id="tag_<?php echo $row['p_id']; ?>" class="cp-form-control form-select" aria-label="Select tag for product <?php echo htmlspecialchars($row['p_id']); ?>">
              <option value="Men" <?php if($row['tags'] == "Men") echo "selected"; ?>>Men</option>
              <option value="Women" <?php if($row['tags'] == "Women") echo "selected"; ?>>Women</option>
              <option value="Kids" <?php if($row['tags'] == "Kids") echo "selected"; ?>>Kid's</option>
            </select>
          </div>

          <div class="form-group">
            <label for="quantity_<?php echo $row['p_id']; ?>">Quantity</label>
            <input type="number" name="update_quantity" id="quantity_<?php echo $row['p_id']; ?>" value="<?php echo $row['quantity']; ?>" class=" cp-form-control" min="0" required>
          </div>

          <div class="form-group">
            <label for="price_<?php echo $row['p_id']; ?>">Price</label>
            <input type="number" name="update_Price" id="price_<?php echo $row['p_id']; ?>" value="<?php echo $row['price']; ?>" class=" cp-form-control" step="0.01" min="0" required>
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
<script>
// Per-form category select handling: show new category input and sync hidden field
document.addEventListener('DOMContentLoaded', function(){
  document.querySelectorAll('form.product-form').forEach(function(form){
    var pid = form.querySelector('input[name="update_id"]').value;
    var sel = document.getElementById('category_select_' + pid);
    var newInput = document.getElementById('new_category_' + pid);
    var hidden = document.getElementById('update_category_hidden_' + pid);

    if(!sel || !hidden) return;

    function updateHidden(){
      if(sel.value === 'new'){
        newInput.style.display = 'block';
        newInput.required = true;
        hidden.value = newInput.value.trim();
      } else {
        newInput.style.display = 'none';
        newInput.required = false;
        hidden.value = sel.value;
      }
    }

    // when new input changes, update hidden
    if(newInput){
      newInput.addEventListener('input', function(){
        if(sel.value === 'new') hidden.value = newInput.value.trim();
      });
    }

    sel.addEventListener('change', updateHidden);
    // initialize
    updateHidden();

    // ensure hidden field is updated before form submit (defensive)
    form.addEventListener('submit', function(){
      if(sel.value === 'new' && newInput) {
        hidden.value = newInput.value.trim();
      } else {
        hidden.value = sel.value;
      }
    });
  });
});
</script>
<script>
// Description character counters (max 300)
document.addEventListener('DOMContentLoaded', function(){
  const max = 300;
  document.querySelectorAll('textarea[name="update_description"]').forEach(function(txt){
    const pid = txt.id.split('_').pop();
    const counter = document.getElementById('desc_remaining_' + pid);
    function update(){
      let val = txt.value || '';
      if (val.length > max) {
        txt.value = val.substring(0, max);
        val = txt.value;
      }
      if(counter) counter.textContent = Math.max(0, max - val.length);
    }
    txt.addEventListener('input', update);
    // init
    update();
  });
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
  const closeBtn = modal ? modal.querySelector('.cp-modal-close') : null;
  // Ensure the close button isn't focusable while modal is hidden
  if (closeBtn) closeBtn.tabIndex = -1;

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
  // expose modal to assistive tech and allow focus
  modal.setAttribute('aria-hidden','false');
  if ('inert' in modal) modal.inert = false;
  if (closeBtn) closeBtn.tabIndex = 0;
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
  if (closeBtn) {
    closeBtn.addEventListener('click', function(){
      closeModal();
    });
  }

  modal.addEventListener('click', function(e){ if(e.target === modal){ closeModal(); } });

  // helper to close modal programmatically (used by Escape handler)
  function closeModal(){
  // restore form and focus back to the previously focused element first
  restoreForm();
  // then mark modal inert/hidden and make sure its close button cannot receive focus
  if ('inert' in modal) modal.inert = true;
  if (closeBtn) closeBtn.tabIndex = -1;
  modal.classList.remove('show');
  modal.setAttribute('aria-hidden','true');
  }
});
</script>
</body>
</html>