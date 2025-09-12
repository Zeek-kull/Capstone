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

// Handle removal via ?remove=<order_id>
if (isset($_GET['remove'])) {
    $remove_id = intval($_GET['remove']);
    // Only allow removal for logged-in admins
    if (isset($_SESSION['admin_auth']) && $_SESSION['admin_auth'] == 1) {
        mysqli_begin_transaction($conn);
        try {
            // Before removing, attempt to restore product stock from the order (if present)
            $ord_q = mysqli_query($conn, "SELECT totalproduct FROM orders WHERE o_id = '{$remove_id}' LIMIT 1");
            if ($ord_q && mysqli_num_rows($ord_q) > 0) {
                $ord = mysqli_fetch_assoc($ord_q);
                $tp = $ord['totalproduct'] ?? '';
                $parts = array_filter(array_map('trim', explode(',', $tp)));
                foreach ($parts as $part) {
                    if (preg_match('/(\d+)\s*\((\d+)\)/', $part, $m)) {
                        $pid = intval($m[1]);
                        $qty = intval($m[2]);
                        if ($pid > 0 && $qty > 0) {
                            mysqli_query($conn, "UPDATE product SET quantity = quantity + {$qty} WHERE p_id = '{$pid}'");
                        }
                    }
                }
            }
            // Remove related history first
            $del_hist = mysqli_query($conn, "DELETE FROM order_status_history WHERE order_id = '$remove_id'");
            // Remove the order
            $del_order = mysqli_query($conn, "DELETE FROM orders WHERE o_id = '$remove_id'");
            if ($del_order) {
                mysqli_commit($conn);
                $_SESSION['success_message'] = 'Order removed successfully';
            } else {
                throw new Exception('Failed to delete order');
            }
        } catch (Exception $e) {
            mysqli_rollback($conn);
            $_SESSION['error_message'] = 'Failed to remove order';
        }
    }
    header('Location: pending_orders.php');
    exit();
}

// Get admin ID for tracking
$admin_userid = $_SESSION['admin_userid'] ?? 'admin';
$admin_query = mysqli_query($conn, "SELECT ad_id FROM admin WHERE userid = '$admin_userid'");
$admin_data = mysqli_fetch_assoc($admin_query);
$admin_id = $admin_data['ad_id'] ?? 1;

// Handle status update with process tracking
if (isset($_POST['update_update_btn'])) {
    // Sanitize inputs
    $update_value = isset($_POST['update_status']) ? mysqli_real_escape_string($conn, trim($_POST['update_status'])) : '';
    $update_id = isset($_POST['update_id']) ? intval($_POST['update_id']) : 0;
    $change_reason = mysqli_real_escape_string($conn, $_POST['change_reason'] ?? '');

    // Get current status (trim to avoid accidental whitespace mismatches)
    $current_status_query = mysqli_query($conn, "SELECT status FROM orders WHERE o_id = '{$update_id}'");
    $current_status_row = mysqli_fetch_assoc($current_status_query);
    $current_status = isset($current_status_row['status']) ? trim($current_status_row['status']) : '';
    
    // Validate status transition (case-insensitive). Keys and allowed values are lowercased.
    $valid_transitions = [
        'pending' => ['processing', 'cancelled'],
        'processing' => ['shipped', 'cancelled'],
        'shipped' => ['ofd', 'completed', 'cancelled'],
        'ofd' => ['arriving', 'completed', 'cancelled'],
        'arriving' => ['completed', 'cancelled'],
        'completed' => [],
        'cancelled' => []
    ];
    
    $curLower = strtolower($current_status);
    $updateLower = strtolower($update_value);
    if (in_array($updateLower, $valid_transitions[$curLower] ?? [])) {
        // Start transaction
        mysqli_begin_transaction($conn);
        
        try {
            // Update order status (use escaped values)
            $update_sql = "UPDATE orders SET status = '{$update_value}', status_updated_at = NOW() WHERE o_id = '{$update_id}'";
            $update_query = mysqli_query($conn, $update_sql);

            // Record in history
            $history_sql = "INSERT INTO order_status_history (order_id, old_status, new_status, changed_by, change_reason) VALUES ('{$update_id}', '" . mysqli_real_escape_string($conn, $current_status) . "', '{$update_value}', '{$admin_id}', '{$change_reason}')";
            $history_query = mysqli_query($conn, $history_sql);

            if ($update_query && $history_query) {
                // If cancelling the order, attempt to restore stock
                $toLower = strtolower($update_value);
                if ($toLower === 'cancelled' || $toLower === 'cancel') {
                    // Fetch the order's totalproduct field (format: "productId (qty), productId (qty), ...")
                    $ord_q = mysqli_query($conn, "SELECT totalproduct FROM orders WHERE o_id = '{$update_id}' LIMIT 1");
                    if ($ord_q && mysqli_num_rows($ord_q) > 0) {
                        $ord = mysqli_fetch_assoc($ord_q);
                        $tp = $ord['totalproduct'] ?? '';
                        // Parse entries like: 123 (2), 45 (1)
                        $parts = array_filter(array_map('trim', explode(',', $tp)));
                        foreach ($parts as $part) {
                            // try to extract id and qty via regex
                            if (preg_match('/(\d+)\s*\((\d+)\)/', $part, $m)) {
                                $pid = intval($m[1]);
                                $qty = intval($m[2]);
                                if ($pid > 0 && $qty > 0) {
                                    // add qty back to product stock
                                    mysqli_query($conn, "UPDATE product SET quantity = quantity + {$qty} WHERE p_id = '{$pid}'");
                                }
                            }
                        }
                    }
                }
                mysqli_commit($conn);
                $_SESSION['success_message'] = "Order status updated successfully from $current_status to $update_value";
            } else {
                // Include DB error for debugging
                $dbErr = mysqli_error($conn);
                mysqli_rollback($conn);
                $_SESSION['error_message'] = "Failed to update order status: {$dbErr}";
            }
        } catch (Exception $e) {
            mysqli_rollback($conn);
            $_SESSION['error_message'] = "Failed to update order status: " . $e->getMessage();
        }
    } else {
        $_SESSION['error_message'] = "Invalid status transition from $current_status to $update_value";
    }
    
    header('location:pending_orders.php');
    exit();
}

// Get all orders with latest status
$sql = "SELECT o.*, u.email as user_email, DATE_FORMAT(o.created_at, '%Y-%m-%d %H:%i:%s') AS created_at_display FROM orders o 
    LEFT JOIN users u ON o.user_id = u.u_id 
    ORDER BY o.created_at DESC";
$result = $conn->query($sql);

$stats_sql = "SELECT 
    COUNT(*) as total_orders,
    SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) as pending_count,
    SUM(CASE WHEN status = 'Confirmed' THEN 1 ELSE 0 END) as confirmed_count,
    SUM(CASE WHEN status = 'Processing' THEN 1 ELSE 0 END) as processing_count,
    SUM(CASE WHEN status = 'Shipped' THEN 1 ELSE 0 END) as shipped_count,
    SUM(CASE WHEN status = 'OFD' THEN 1 ELSE 0 END) as out_for_delivery_count,
    SUM(CASE WHEN status = 'Arriving' THEN 1 ELSE 0 END) as arriving_count,
    SUM(CASE WHEN status = 'Completed' THEN 1 ELSE 0 END) as completed_count,
    SUM(CASE WHEN status = 'Cancelled' THEN 1 ELSE 0 END) as cancelled_count,
    -- Only count revenue for orders that are marked Completed (delivered)
    SUM(CASE WHEN status = 'Completed' THEN totalprice ELSE 0 END) as total_revenue
    FROM orders";
$stats_result = $conn->query($stats_sql);
$stats = $stats_result->fetch_assoc();

// Map database status values to friendly labels for display
 $status_label_map = [
    'Pending' => 'Pending',
    'Processing' => 'Processing',
    'Shipped' => 'Shipped',
    'OFD' => 'Out for delivery',
    'Arriving' => 'Arriving',
    'Completed' => 'Delivered',
    'Cancelled' => 'Cancelled'
];
?>
</head>
<body>

<div class="pendingbody">
    <?php
    // include flash partial for admin messages
    include __DIR__ . '/../includes/flash.php';
    ?>
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
            <select id="statusFilter" aria-label="Filter orders by status">
            <option value="">All Status</option>
            <option value="Pending">Pending</option>
            <option value="Confirmed">Confirmed</option>
            <option value="Processing">Processing</option>
            <option value="Shipped">Shipped</option>
            <option value="OFD">Out for delivery</option>
            <option value="Arriving">Arriving</option>
            <option value="Completed">Completed</option>
            <option value="Cancelled">Cancelled</option>
            </select>
        </div>
        <div class="filter-group">
            <label for="sortBy">Sort by:</label>
            <select id="sortBy" aria-label="Sort orders">
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
            <div class="stat-value" style="color: var(--info-color);"><?php echo $stats['confirmed_count']; ?></div>
            <div class="stat-label">Confirmed</div>
        </div>
        <div class="stat-card">
            <div class="stat-value" style="color: var(--info-color);"><?php echo $stats['processing_count']; ?></div>
            <div class="stat-label">Processing</div>
        </div>
        <div class="stat-card">
            <div class="stat-value" style="color: var(--info-color);"><?php echo $stats['shipped_count']; ?></div>
            <div class="stat-label">Shipped</div>
        </div>
        <div class="stat-card">
            <div class="stat-value" style="color: var(--info-color);"><?php echo $stats['out_for_delivery_count']; ?></div>
            <div class="stat-label">Out for delivery</div>
        </div>
        <div class="stat-card">
            <div class="stat-value" style="color: var(--info-color);"><?php echo $stats['arriving_count']; ?></div>
            <div class="stat-label">Arriving</div>
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
                    // DB stores "Out for delivery" as 'OFD'
                    $valid_next_statuses = ['OFD', 'Cancelled'];
                    break;
                case 'OFD':
                    $valid_next_statuses = ['Arriving', 'Cancelled'];
                    break;
                case 'Arriving':
                    $valid_next_statuses = ['Cancelled'];
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
            <div class="order-card" data-status="<?php echo strtolower($row['status']); ?>" data-price="<?php echo $row['totalprice']; ?>" data-date="<?php echo strtotime($row['created_at_display'] ?? $row['created_at']); ?>">
                <div class="order-header">
                    <div class="order-info">
                        <div class="order-number">Order #<?php echo $row['o_id']; ?></div>
                        <div class="order-date">
                            <?php 
                            // Prefer formatted alias from SQL to avoid microseconds in UI, fall back to raw column
                            $created_for_display = $row['created_at_display'] ?? $row['created_at'];
                            if (!empty($created_for_display)) {
                                $date = new DateTime($created_for_display);
                                $date->setTimezone(new DateTimeZone('Asia/Manila'));
                                echo $date->format("F j, Y, g:i A");
                            } else {
                                echo "N/A";
                            }
                            ?>
                        </div>
                    </div>
                    <div class="order-status">
                        <?php
                        // Render friendly label for status
                        $display_status = $status_label_map[$row['status']] ?? $status_label_map[strtolower($row['status'])] ?? $row['status'];
                        $status_class = 'status-' . preg_replace('/[^a-z0-9]+/', '-', strtolower($row['status']));
                        ?>
                        <span class="status-badge <?php echo $status_class; ?>">
                            <?php echo htmlspecialchars($display_status); ?>
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
                        
                        <select name="update_status" class="btn btn-sm btn-outline" required aria-label="<?php echo 'Update status for order #' . htmlspecialchars($row['o_id']); ?>">
                            <option value="" disabled selected>Update Status</option>
                            <?php foreach ($valid_next_statuses as $status): ?>
                                <?php $label = $status_label_map[$status] ?? $status; ?>
                                <option value="<?php echo htmlspecialchars($status); ?>"><?php echo htmlspecialchars($label); ?></option>
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
    // statusFilter contains DB enum values (e.g. 'OFD') or empty string
    const statusFilter = (e.target.value || '').toLowerCase();
    const orders = document.querySelectorAll('.order-card');
    
    orders.forEach(order => {
    const orderStatus = order.getAttribute('data-status') || '';
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
    if (typeof showFlash === 'function') {
        showFlash('info', 'Status history for order #' + orderId + ' would be displayed here');
    } else {
        console.log('Status history for order #' + orderId + ' would be displayed here');
    }
}
</script>

<script>
// Auto-hide flash messages after 6 seconds
document.addEventListener('DOMContentLoaded', function(){
    var s = document.getElementById('adminFlashSuccess');
    var e = document.getElementById('adminFlashError');
    [s,e].forEach(function(el){
        if(!el) return;
        el.tabIndex = -1;
        el.focus();
        setTimeout(function(){
            try{ el.style.transition = 'opacity 400ms'; el.style.opacity = 0; setTimeout(function(){ el.remove(); }, 450); }catch(err){}
        }, 6000);
    });
});
</script>

</body>
</html>
