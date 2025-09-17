<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

include "lib/connection.php";

// Get cart count only if user is logged in
$total = 0;
if (isset($_SESSION['userid'])) {
    $id = $_SESSION['userid'];
    $cart_result = $conn->query("SELECT * FROM cart WHERE user_id='" . intval($id) . "'");
    if ($cart_result && mysqli_num_rows($cart_result) > 0) {
        $total = mysqli_num_rows($cart_result);
    }
}

// Fetch distinct tags from the product table for dynamic navigation
$tags_sql = "SELECT DISTINCT tags FROM product WHERE tags != '' AND tags IS NOT NULL ORDER BY tags";
$tags_result = mysqli_query($conn, $tags_sql);
?>
<!DOCTYPE html>
<html lang="zxx">

<head>
    <meta charset="UTF-8">
    <meta name="description" content="A & M Closet">
    <meta name="keywords" content="A & M Closet, clothing, closet, html">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">

    <!-- Favicon: compute base path so this works under a subdirectory (e.g., /Capstone) -->
    <?php
    // Determine base path for assets relative to document root
    $scriptDir = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
    $basePath = $scriptDir === '' ? '' : $scriptDir;
    // prefer favicon at the site root (e.g., /Capstone/favicon.ico)
    $icoPath = $basePath . '/favicon.ico';
    $pngPath = $basePath . '/img/favicon.png';
    ?>
    <link rel="icon" href="<?php echo htmlspecialchars($icoPath); ?>" type="image/x-icon">
    <link rel="shortcut icon" href="<?php echo htmlspecialchars($icoPath); ?>" type="image/x-icon">
    <link rel="icon" href="<?php echo htmlspecialchars($pngPath); ?>" type="image/png">

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
    <link rel="stylesheet" href="css/out-of-stock.css" type="text/css">
    <!-- Styles moved to css/style.css -->
</head>

<body>
    <!-- Header Section Begin -->
    <?php if (empty($hideHeader)): ?>
    <header class="header-section">
        <div class="header-top"></div>
        <div class="container">
            <div class="inner-header">
                <div class="row">
                    <div class="col-lg-2 col-md-2">
                        <div class="logo">
                            <a href="./default.php">
                                <img src="img/amLogoo.png" alt="">
                            </a>
                        </div>
                    </div>
                    <div class="col-lg-7 col-md-2">
                        <form action="search.php" method="post">
                            <div class="advanced-search">
                                <button type="button" class="category-btn">All Categories</button>
                                <div class="input-group">
                                    <input id="header-search-input" type="search" placeholder="What do you need?" aria-label="Search" name="name">
                                    <button type="submit"><i class="ti-search"></i></button>
                                </div>
                            </div>
                        </form>
                    </div>
                    <?php if (isset($_SESSION['userid'])): ?>
                    <div class="col-lg-3 text-right col-md-3">
                        <ul class="nav-right">
                            <li class="cart-icon">
                                <a href="shopping-cart.php">
                                    <i class="icon_bag_alt"></i>
                                    <span><?php echo $total; ?></span>
                                </a>
                            </li>
                            <?php if (isset($_SESSION['auth']) && $_SESSION['auth'] == 1): ?>
                            <li class="user-icon">
                                <a href="profile.php">
                                    <i class="fa fa-user" style="text-decoration:none;color:black;"></i>
                                </a>
                            </li>
                            <li><a class="btn btn-outline-success btn-sm ml-2" href="logout.php">Logout</a></li>
                        </ul>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="ml-3">
                    <?php else: ?>
                        <a class="btn btn-outline-primary btn-sm" href="login.php" id="header-login-btn">Login</a>
                        <a class="btn btn-outline-success btn-sm ml-2" href="register.php" id="header-register-btn">Signup</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="nav-item">
            <div class="container">
                <nav class="nav-menu mobile-menu">
                    <?php
                        // Determine the current page and tag to mark active nav items
                        $current_page = basename($_SERVER['SCRIPT_NAME']);
                        $current_tag = isset($_GET['tags']) ? $_GET['tags'] : '';
                    ?>
                    <ul>
                        <li<?php echo in_array($current_page, ['default.php','home.php']) ? ' class="active"' : ''; ?>><a href="./default.php">Home</a></li>
                        <li<?php echo $current_page === 'shop.php' ? ' class="active"' : ''; ?>><a href="./shop.php">Shop</a></li>
                        <?php
                        // Add dynamic tags to navigation. Normalize 'Kid' => 'Kids' and order
                        if ($tags_result && mysqli_num_rows($tags_result) > 0) {
                            $tags = [];
                            while ($tag_row = mysqli_fetch_assoc($tags_result)) {
                                $orig = $tag_row['tags'];
                                // Normalize display name (Kid -> Kids)
                                $display = ($orig === 'Kid') ? 'Kids' : $orig;
                                $tags[] = ['orig' => $orig, 'display' => $display];
                            }

                            // Preferred ordering: Kids, Women, Men
                            $preferred = ['Kids', 'Women', 'Men'];
                            $seenDisplays = [];

                            // First, output preferred tags in order if present
                            foreach ($preferred as $p) {
                                foreach ($tags as $t) {
                                    if (!in_array($t['display'], $seenDisplays, true) && strcasecmp($t['display'], $p) === 0) {
                                        $isActive = ($current_page === 'Others.php' && $current_tag === $t['orig']);
                                        echo '<li' . ($isActive ? ' class="active"' : '') . '><a href="Others.php?tags=' . urlencode($t['orig']) . '">' . htmlspecialchars($t['display']) . '</a></li>';
                                        $seenDisplays[] = $t['display'];
                                    }
                                }
                            }

                            // Collect remaining tags (not already shown)
                            $remaining = [];
                            foreach ($tags as $t) {
                                if (!in_array($t['display'], $seenDisplays, true)) {
                                    $remaining[$t['display']] = $t['orig'];
                                }
                            }

                            // Sort remaining by display name and output
                            if (!empty($remaining)) {
                                ksort($remaining, SORT_NATURAL | SORT_FLAG_CASE);
                                foreach ($remaining as $display => $orig) {
                                    // avoid duplicates if display already output
                                    if (in_array($display, $seenDisplays, true)) continue;
                                    $isActive = ($current_page === 'Others.php' && $current_tag === $orig);
                                    echo '<li' . ($isActive ? ' class="active"' : '') . '><a href="Others.php?tags=' . urlencode($orig) . '">' . htmlspecialchars($display) . '</a></li>';
                                    $seenDisplays[] = $display;
                                }
                            }
                        }
                        ?>
                        <li<?php echo $current_page === 'contact.php' ? ' class="active"' : ''; ?>><a href="./contact.php">Contact</a></li>
                        <li<?php echo $current_page === 'faq.php' ? ' class="active"' : ''; ?>><a href="./faq.php">Faq</a></li>
                    </ul>
                </nav>
                <div id="mobile-menu-wrap"></div>
            </div>
        </div>
    </header>
   <?php endif; ?>
    <!-- Header End -->

<script>
    // Only run if orderForm and orderButton exist
    document.addEventListener('DOMContentLoaded', function () {
        var orderForm = document.getElementById('orderForm');
        var orderButton = document.getElementById('orderButton');
        var cartItems = <?php echo json_encode($total); ?>;
        if (orderForm && orderButton) {
            orderForm.addEventListener('input', function () {
                var address = document.querySelector('input[name="address"]')?.value;
                // mobnumber JS reference removed
                var payment_method = document.querySelector('select[name="payment_method"]')?.value;
                // phoneValid for mobnumber removed
                if (cartItems > 0 && address && payment_method) {
                    orderButton.disabled = false;
                    orderButton.style.backgroundColor = '#2ecc71';
                } else {
                    orderButton.disabled = true;
                    orderButton.style.backgroundColor = '#ddd';
                }
            });
            // Initial check on page load
            if (cartItems == 0) {
                orderButton.disabled = true;
                orderButton.style.backgroundColor = '#ddd';
                orderButton.textContent = 'Cart is Empty';
            }
        }

        // Focus email input on login page when Login button is clicked
        var loginBtn = document.getElementById('header-login-btn');
        if (loginBtn) {
            loginBtn.addEventListener('click', function() {
                localStorage.setItem('focusEmailOnLogin', '1');
            });
        }

        // Focus search when All Categories button is clicked
        var catBtn = document.querySelector('.category-btn');
        var searchInput = document.getElementById('header-search-input');
        if (catBtn && searchInput) {
            catBtn.addEventListener('click', function() {
                searchInput.focus();
            });
        }

        // Small UX: show clicked feedback on cart/user icons (temporary class)
        function addIconClickFeedback(selector) {
            var li = document.querySelector(selector);
            if (!li) return;
            var anchor = li.querySelector('a');
            // show feedback on mousedown (desktop) and keyboard activation
            li.addEventListener('mousedown', function() {
                li.classList.add('icon-clicked');
                setTimeout(function() { li.classList.remove('icon-clicked'); }, 800);
            });
            if (anchor) {
                anchor.addEventListener('keydown', function(e) {
                    if (e.key === 'Enter' || e.key === ' ') {
                        li.classList.add('icon-clicked');
                        setTimeout(function() { li.classList.remove('icon-clicked'); }, 800);
                    }
                });
            }
        }
        addIconClickFeedback('.nav-right .cart-icon');
        addIconClickFeedback('.nav-right .user-icon');
    });
</script>
</body>

</html>