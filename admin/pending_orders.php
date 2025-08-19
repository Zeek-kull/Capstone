<?php
include 'header.php';
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (isset($_SESSION['admin_auth'])) {
    if ($_SESSION['admin_auth'] != 1) {
        header("location:a_login.php");
    }
} else {
    header("location:a_login.php");
}

include 'lib/connection.php';

// Get admin ID for tracking
$admin_userid = $_SESSION['admin_userid'] ?? 'admin';
$admin_query = mysqli_query($conn, "SELECT id FROM admin WHERE userid = '$admin_userid'");
$admin_data = mysqli_fetch_assoc($admin_query);
$admin_id = $admin_data['id'] ?? 1;

// Handle status update with process tracking
if (isset($_POST['update_update_btn'])) {
    $update_value = $_POST['update_status'];
    $update_id = $_POST['update_id'];
    $change_reason = mysqli_real_escape_string($conn, $_POST['change_reason'] ?? '');
    
    // Get current status
    $current_status_query = mysqli_query($conn, "SELECT status FROM orders WHERE o_id = '$update_id'");
    $current_status = mysqli_fetch_assoc($current_status_query)['status'];
    
    // Validate status transition
    $valid_transitions = [
        'Pending' => ['Processing', 'Cancelled'],
        'Processing' => ['Shipped', 'Cancelled'],
        'Shipped' => ['Completed', 'Cancelled'],
        'Completed' => [],
        'Cancelled' => []
    ];
    
    if (in_array($update_value, $valid_transitions[$current_status] ?? [])) {
        // Start transaction
        mysqli_begin_transaction($conn);
        
        try {
            // Update order status
            $update_query = mysqli_query($conn, "UPDATE orders SET status = '$update_value', status_updated_at = NOW() WHERE o_id = '$update_id'");
            
            // Record in history
            $history_query = mysqli_query($conn, "INSERT INTO order_status_history (order_id, old_status, new_status, changed_by, change_reason) VALUES ('$update_id', '$current_status', '$update_value', '$admin_id', '$change_reason')");
            
            if ($update_query && $history_query) {
                mysqli_commit($conn);
                $_SESSION['success_message'] = "Order status updated successfully from $current_status to $update_value";
            } else {
                throw new Exception("Database error");
            }
        } catch (Exception $e) {
            mysqli_rollback($conn);
            $_SESSION['error_message'] = "Failed to update order status";
        }
    } else {
        $_SESSION['error_message'] = "Invalid status transition from $current_status to $update_value";
    }
    
    header('location:pending_orders.php');
    exit();
}

// Get all orders with latest status
$sql = "SELECT o.*, u.email as user_email FROM orders o 
        LEFT JOIN users u ON o.user_id = u.id 
        ORDER BY o.created_at DESC";
$result = $conn->query($sql);

// Get order statistics
$stats_sql = "SELECT 
    COUNT(*) as total_orders,
    SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) as pending_count,
    SUM(CASE WHEN status = 'Processing' THEN 1 ELSE 0 END) as processing_count,
    SUM(CASE WHEN status = 'Shipped' THEN 1 ELSE 0 END) as shipped_count,
    SUM(CASE WHEN status = 'Completed' THEN 1 ELSE 0 END) as completed_count,
    SUM(CASE WHEN status = 'Cancelled' THEN 1 ELSE 0 END) as cancelled_count,
    SUM(totalprice) as total_revenue
    FROM orders";
$stats_result = $conn->query($stats_sql);
$stats = $stats_result->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Management</title>
    <link rel="stylesheet" href="css/pending_orders.css">
</head>
<body>

<div class="pendingbody">
    <!-- Page Header -->
    <div class="page-header">
        <div>
            <h1 class="page-title">Order Management</h1>
            <p class="page-subtitle">Manage and track all customer orders</p>
        </div>
    </div>

    <!-- Controls Section -->
    <div class="controls-section">
        <div class="search-box">
            <input type="text" id="searchInput" placeholder="Search orders...">
        </div>
        <div class="filter-group">
            <label for="statusFilter">Status:</label>
            <select id="statusFilter">
                <option value="">All Status</option>
                <option value="Pending">Pending</option>
                <option value="Processing">Processing</option>
                <option value="Shipped">Shipped</option>
                <option value="Completed">Completed</option>
                <option value="Cancelled">Cancelled</option>
            </select>
        </div>
        <div class="filter-group">
            <label for="sortBy">Sort by:</label>
            <select id="sortBy">
                <option value="newest">Newest First</option>
                <option value="oldest">Oldest First</option>
                <option value="price-high">Price: High to Low</option>
                <option value="price-low">Price: Low to High</option>
            </select>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-value" style="color: var(--primary-color);"><?php echo $stats['total_orders']; ?></div>
            <div class="stat-label">Total Orders</div>
        </div>
        <div class="stat-card">
            <div class="stat-value" style="color: var(--warning-color);"><?php echo $stats['pending_count']; ?></div>
            <div class="stat-label">Pending</div>
        </div>
        <div class="stat-card">
            <div class="stat-value" style="color: var(--info-color);"><?php echo $stats['processing_count']; ?></div>
            <div class="stat-label">Processing</div>
        </div>
        <div class="stat-card">
            <div class="stat-value" style="color: var(--success-color);"><?php echo $stats['completed_count']; ?></div>
            <div class="stat-label">Completed</div>
        </div>
        <div class="stat-card">
            <div class="stat-value" style="color: var(--danger-color);"><?php echo $stats['cancelled_count']; ?></div>
            <div class="stat-label">Cancelled</div>
        </div>
        <div class="stat-card">
            <div class="stat-value" style="color: var(--success-color);">₱<?php echo number_format($stats['total_revenue'], 2); ?></div>
            <div class="stat-label">Total Revenue</div>
        </div>
    </div>

    <!-- Orders Container -->
    <div class="orders-container" id="ordersContainer">
    <?php
    if (mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            // Get status history count
            $history_count_query = mysqli_query($conn, "SELECT COUNT(*) as count FROM order_status_history WHERE order_id = '{$row['o_id']}'");
            $history_count = mysqli_fetch_assoc($history_count_query)['count'];
            
            // Get valid next statuses based on current status
            $current_status = $row['status'];
            $valid_next_statuses = [];
            switch($current_status) {
                case 'Pending':
                    $valid_next_statuses = ['Processing', 'Cancelled'];
                    break;
                case 'Processing':
                    $valid_next_statuses = ['Shipped', 'Cancelled'];
                    break;
                case 'Shipped':
                    $valid_next_statuses = ['Completed'];
                    break;
                case 'Completed':
                    $valid_next_statuses = [];
                    break;
                case 'Cancelled':
                    $valid_next_statuses = [];
                    break;
            }
            
            // Parse products
            $products = explode(',', $row["totalproduct"]);
            $product_details = [];
            foreach ($products as $prod) {
                if (preg_match('/^(.+)\((\d+)\)$/', trim($prod), $matches)) {
                    $product_details[] = [
                        'name' => trim($matches[1]),
                        'quantity' => (int)$matches[2]
                    ];
                }
            }
            ?>
            <div class="order-card" data-status="<?php echo strtolower($row['status']); ?>" data-price="<?php echo $row['totalprice']; ?>" data-date="<?php echo strtotime($row['created_at']); ?>">
                <div class="order-header">
                    <div class="order-info">
                        <div class="order-number">Order #<?php echo $row['o_id']; ?></div>
                        <div class="order-date">
                            <?php 
                            if (!empty($row["created_at"])) {
                                $date = new DateTime($row["created_at"]);
                                $date->setTimezone(new DateTimeZone('Asia/Manila'));
                                echo $date->format("F j, Y, g:i A");
                            } else {
                                echo "N/A";
                            }
                            ?>
                        </div>
                    </div>
                    <div class="order-status">
                        <span class="status-badge status-<?php echo strtolower($row['status']); ?>">
                            <?php 
                            $display_status = $row['status'];
                            if ($display_status == 'Completed') {
                                $display_status = 'Delivered';
                            }
                            echo htmlspecialchars($display_status); 
                            ?>
                        </span>
                        <?php if ($history_count > 0): ?>
                            <a href="#" onclick="showStatusHistory(<?php echo $row['o_id']; ?>)" class="btn btn-sm btn-outline">
                                View History (<?php echo $history_count; ?>)
                            </a>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="order-body">
                    <div class="customer-info">
                        <h4>Customer Information</h4>
                        <div class="customer-details">
                            <div class="detail-item">
                                <span class="detail-label">Name</span>
                                <span class="detail-value"><?php echo htmlspecialchars($row["name"]); ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Email</span>
                                <span class="detail-value"><?php echo htmlspecialchars($row["user_email"]); ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Phone</span>
                                <span class="detail-value">
                                    <?php
                                    $phone = $row["phone"];
                                    if (preg_match('/^[0-9]+$/', $phone) && strlen($phone) >= 7 && strlen($phone) <= 15) {
                                        echo htmlspecialchars($phone);
                                    } elseif (!empty($row["mobnumber"])) {
                                        echo htmlspecialchars($row["mobnumber"]);
                                    } else {
                                        echo "N/A";
                                    }
                                    ?>
                                </span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Address</span>
                                <span class="detail-value"><?php echo htmlspecialchars($row["address"]); ?></span>
                            </div>
                        </div>
                    </div>

                    <div class="order-summary">
                        <h4>Order Summary</h4>
                        <div class="products-list">
                            <?php foreach ($product_details as $product): ?>
                                <div class="product-item">
                                    <span class="product-name"><?php echo htmlspecialchars($product['name']); ?></span>
                                    <span class="product-quantity">Qty: <?php echo $product['quantity']; ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="total-price">
                            Total: ₱<?php echo number_format($row["totalprice"], 2); ?>
                        </div>
                    </div>
                </div>

                <div class="order-actions">
                    <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post" class="status-form" style="display: contents;">
                        <input type="hidden" name="update_id" value="<?php echo $row['o_id']; ?>">
                        
                        <select name="update_status" class="btn btn-sm btn-outline" required>
                            <option value="" disabled selected>Update Status</option>
                            <?php foreach ($valid_next_statuses as $status): ?>
                                <option value="<?php echo htmlspecialchars($status); ?>"><?php echo htmlspecialchars($status); ?></option>
                            <?php endforeach; ?>
                        </select>
                        
                        <input type="text" name="change_reason" class="btn btn-sm btn-outline" placeholder="Reason (optional)" style="flex: 1;">
                        
                        <button type="submit" name="update_update_btn" class="btn btn-sm btn-primary">
                            Update
                        </button>
                    </form>
                    
                    <a href="pending_orders.php?remove=<?php echo urlencode($row['o_id']); ?>" 
                       class="btn btn-sm btn-danger" 
                       onclick="return confirm('Are you sure you want to remove this order?')">
                        Remove
                    </a>
                </div>
            </div>
            <?php
        }
    } else {
        echo '<div class="order-card"><div class="order-body"><p class="text-center">No orders found</p></div></div>';
    }
    ?>
    </div>
</div>

<script>
// Search functionality
document.getElementById('searchInput').addEventListener('input', function(e) {
    const searchTerm = e.target.value.toLowerCase();
    const orders = document.querySelectorAll('.order-card');
    
    orders.forEach(order => {
        const text = order.textContent.toLowerCase();
        order.style.display = text.includes(searchTerm) ? '' : 'none';
    });
});

// Status filter
document.getElementById('statusFilter').addEventListener('change', function(e) {
    const statusFilter = e.target.value.toLowerCase();
    const orders = document.querySelectorAll('.order-card');
    
    orders.forEach(order => {
        const orderStatus = order.getAttribute('data-status');
        if (!statusFilter || orderStatus === statusFilter) {
            order.style.display = '';
        } else {
            order.style.display = 'none';
        }
    });
});

// Sort functionality
document.getElementById('sortBy').addEventListener('change', function(e) {
    const sortBy = e.target.value;
    const container = document.getElementById('ordersContainer');
    const orders = Array.from(container.children);
    
    orders.sort((a, b) => {
        switch(sortBy) {
            case 'newest':
                return parseInt(b.getAttribute('data-date')) - parseInt(a.getAttribute('data-date'));
            case 'oldest':
                return parseInt(a.getAttribute('data-date')) - parseInt(b.getAttribute('data-date'));
            case 'price-high':
                return parseFloat(b.getAttribute('data-price')) - parseFloat(a.getAttribute('data-price'));
            case 'price-low':
                return parseFloat(a.getAttribute('data-price')) - parseFloat(b.getAttribute('data-price'));
            default:
                return 0;
        }
    });
    
    orders.forEach(order => container.appendChild(order));
});

// Show status history
function showStatusHistory(orderId) {
    // This would typically open a modal with status history
    alert('Status history for order #' + orderId + ' would be displayed here');
}
</script>

</body>
</html>
