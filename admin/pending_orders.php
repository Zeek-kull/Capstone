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

    $change_reason = isset($_POST['change_reason']) ? trim($_POST['change_reason']) : '';
    $transaction_number = isset($_POST['transaction_number']) ? trim($_POST['transaction_number']) : '';

    // Server-side: require a transaction number when marking as Shipped
    if (strtolower($update_value) === 'shipped') {
        if ($transaction_number === '') {
            $_SESSION['error_message'] = "Transaction number is required when marking an order as Shipped.";
            header('location:pending_orders.php');
            exit();
        }
    }

    // Server-side: require a reason when cancelling an order
    if (strtolower($update_value) === 'cancelled' || strtolower($update_value) === 'cancel') {
        if ($change_reason === '') {
            $_SESSION['error_message'] = "Cancellation reason is required when cancelling an order.";
            header('location:pending_orders.php');
            exit();
        }
    }

    // Get current status (trim to avoid accidental whitespace mismatches)
    $current_status_query = mysqli_query($conn, "SELECT status FROM orders WHERE o_id = '{$update_id}'");
    $current_status_row = mysqli_fetch_assoc($current_status_query);
    $current_status = isset($current_status_row['status']) ? trim($current_status_row['status']) : '';
    
    // Validate status transition (case-insensitive). Keys and allowed values are lowercased.
    // Keep transitions limited to the enum defined in the DB: Pending, Packing, Shipped, Completed, Cancelled
    // Support legacy 'processing' values for backwards compatibility when present
    $valid_transitions = [
        'pending' => ['packing', 'processing', 'cancelled'],
        'processing' => ['shipped', 'cancelled'],
        'packing' => ['shipped', 'cancelled'],
        'shipped' => ['cancelled'],
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
            // If the orders table has a 'transaction_number' column and transaction provided, include it
            $update_fields = ["status = '{$update_value}'", "status_updated_at = NOW()"];
            if ($transaction_number !== '') {
                // Check whether the column exists to avoid SQL errors on schemas without this column
                $col_check = mysqli_query($conn, "SHOW COLUMNS FROM orders LIKE 'transaction_number'");
                if ($col_check && mysqli_num_rows($col_check) > 0) {
                    $tx_esc = mysqli_real_escape_string($conn, $transaction_number);
                    $update_fields[] = "transaction_number = '{$tx_esc}'";
                }
            }
            $update_sql = "UPDATE orders SET " . implode(', ', $update_fields) . " WHERE o_id = '{$update_id}'";
            $update_query = mysqli_query($conn, $update_sql);

            // Record in history. If available, include transaction_number in history as well.
            $history_fields = ['order_id', 'old_status', 'new_status', 'changed_by', 'change_reason'];
            $history_values = [
                "'{$update_id}'",
                "'" . mysqli_real_escape_string($conn, $current_status) . "'",
                "'{$update_value}'",
                "'{$admin_id}'",
                "'" . mysqli_real_escape_string($conn, $change_reason) . "'"
            ];

            // If a transaction number was provided, include it in the history table too (if the column exists)
            $tx_col_exists = false;
            if ($transaction_number !== '') {
                $col_check = mysqli_query($conn, "SHOW COLUMNS FROM order_status_history LIKE 'transaction_number'");
                if ($col_check && mysqli_num_rows($col_check) > 0) {
                    $tx_col_exists = true;
                    $history_fields[] = 'transaction_number';
                    $history_values[] = "'" . mysqli_real_escape_string($conn, $transaction_number) . "'";
                }
            }

            $history_sql = "INSERT INTO order_status_history (" . implode(', ', $history_fields) . ") VALUES (" . implode(', ', $history_values) . ")";
            $history_query = mysqli_query($conn, $history_sql);

            // If orders table lacks transaction_number column but we received a transaction number, set an info message for admin
            if ($transaction_number !== '') {
                $orders_tx_check = mysqli_query($conn, "SHOW COLUMNS FROM orders LIKE 'transaction_number'");
                if (!($orders_tx_check && mysqli_num_rows($orders_tx_check) > 0)) {
                    $_SESSION['error_message'] = "Note: transaction number received but orders table doesn't have a 'transaction_number' column. Transaction saved only in history (if supported).";
                }
            }

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
                    // Also insert a cancellation reason row so users can see it in their tracking popup
                    // Determine the order's user_id
                    $order_user_q = mysqli_query($conn, "SELECT user_id FROM orders WHERE o_id = '{$update_id}' LIMIT 1");
                    if ($order_user_q && mysqli_num_rows($order_user_q) > 0) {
                        $order_user = mysqli_fetch_assoc($order_user_q);
                        $order_user_id = intval($order_user['user_id'] ?? 0);
                        $reason_esc = mysqli_real_escape_string($conn, $change_reason);
                        // Insert into user_order_cancellations for display in user UI
                        if ($order_user_id > 0) {
                            mysqli_query($conn, "INSERT INTO user_order_cancellations (order_id, user_id, reason) VALUES ('{$update_id}', '{$order_user_id}', '{$reason_esc}')");
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
    SUM(CASE WHEN status = 'Packing' THEN 1 ELSE 0 END) as processing_count,
    SUM(CASE WHEN status = 'Shipped' THEN 1 ELSE 0 END) as shipped_count,
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
    // Primary DB label is Packing; show friendly 'Packing' but accept 'Processing' rows as well
    'Packing' => 'Packing',
    'Processing' => 'Packing',
    'Shipped' => 'Shipped', 
    // Completed in DB represents delivered orders
    'Completed' => 'Delivered',
    'Cancelled' => 'Cancelled'
];
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
    <?php
    // Display flash messages (set earlier during operations)
    if (isset($_SESSION['success_message']) && !empty($_SESSION['success_message'])) {
        echo '<div class="alert alert-success" role="alert" id="adminFlashSuccess">' . htmlspecialchars($_SESSION['success_message']) . '</div>';
        unset($_SESSION['success_message']);
    }
    if (isset($_SESSION['error_message']) && !empty($_SESSION['error_message'])) {
        echo '<div class="alert alert-danger" role="alert" id="adminFlashError">' . htmlspecialchars($_SESSION['error_message']) . '</div>';
        unset($_SESSION['error_message']);
    }
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
            <option value="Packing">Packing</option>
            <option value="Shipped">Shipped</option>
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
            <div class="stat-value" style="color: var(--info-color);"><?php echo $stats['processing_count']; ?></div>
            <div class="stat-label">Packing</div>
        </div>
        <div class="stat-card">
            <div class="stat-value" style="color: var(--info-color);"><?php echo $stats['shipped_count']; ?></div>
            <div class="stat-label">Shipped</div>
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
                    $valid_next_statuses = ['Packing', 'Cancelled'];
                    break;
                case 'Processing':
                case 'Packing':
                    $valid_next_statuses = ['Shipped', 'Cancelled'];
                    break;
                case 'Shipped':
                    // Do not allow admin to mark orders as Completed/Delivered via this UI
                    // Only allow cancellation from Shipped here (other completion flows handled elsewhere)
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
                        
                        <!-- Hidden final change reason submitted to server -->
                        <input type="hidden" name="change_reason" id="change_reason_<?php echo $row['o_id']; ?>" value="">
                        <!-- Transaction number input (hidden unless Shipped) -->
                        <div class="transaction-group" style="display:none; margin-left:0.5rem;">
                            <label for="transaction_number_<?php echo $row['o_id']; ?>" style="margin-right:0.5rem; font-weight:600;">Transaction #</label>
                            <input type="text" name="transaction_number" id="transaction_number_<?php echo $row['o_id']; ?>" class="transaction-number-input" placeholder="Enter transaction number" style="padding:0.25rem 0.5rem;">
                        </div>
                        <!-- Visible cancel reason options (hidden unless Cancelling) -->
                        <div class="cancel-reason-group" style="display:none; flex:1; gap:0.5rem; align-items:center;">
                            <label style="margin:0 0.5rem 0 0; font-weight:600;">Cancel reason:</label>
                            <label style="margin-right:0.5rem;"><input type="radio" name="cancel_reason" value="changed_mind"> Changed my mind</label>
                            <label style="margin-right:0.5rem;"><input type="radio" name="cancel_reason" value="found_cheaper"> Found a better price</label>
                            <label style="margin-right:0.5rem;"><input type="radio" name="cancel_reason" value="wrong_item"> Wrong item ordered</label>
                            <label style="margin-right:0.5rem;"><input type="radio" name="cancel_reason" value="other"> Other</label>
                            <input type="text" name="cancel_reason_other" class="cancel-reason-other other-input" placeholder="Other reason" style="display:none; margin-left:0.5rem;">
                        </div>
                        
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
    alert('Status history for order #' + orderId + ' would be displayed here');
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

<script>
    // Client-side: require reason when admin attempts to set status to Cancelled
    document.addEventListener('DOMContentLoaded', function(){
        // Attach change listener to all select elements in order-action forms
        document.querySelectorAll('.status-form select[name="update_status"]').forEach(function(sel){
            sel.addEventListener('change', function(e){
                var form = sel.closest('form');
                if(!form) return;
                var cancelGroup = form.querySelector('.cancel-reason-group');
                var txGroup = form.querySelector('.transaction-group');
                var txInput = form.querySelector('.transaction-number-input');
                var reasonHidden = form.querySelector('input[name="change_reason"]');
                if(!cancelGroup || !reasonHidden) return;
                var val = (sel.value || '').toLowerCase();
                if(val === 'cancelled' || val === 'cancel'){
                    // Show cancel reason group and require selection
                    cancelGroup.style.display = 'flex';
                    var radios = cancelGroup.querySelectorAll('input[type="radio"][name="cancel_reason"]');
                    radios.forEach(function(r){ r.required = true; });
                    // Hide transaction input when cancelling
                    if(txGroup){ txGroup.style.display = 'none'; }
                    if(txInput){ txInput.removeAttribute('required'); txInput.value = ''; }
                } else {
                    // Hide cancel group and clear values
                    cancelGroup.style.display = 'none';
                    var radios = cancelGroup.querySelectorAll('input[type="radio"][name="cancel_reason"]');
                    radios.forEach(function(r){ r.required = false; r.checked = false; });
                    var other = cancelGroup.querySelector('.cancel-reason-other');
                    if(other){ other.style.display = 'none'; other.value = ''; other.removeAttribute('required'); }
                    if(reasonHidden) reasonHidden.value = '';

                    // If marking as shipped, show transaction input and require it
                    if(val === 'shipped'){
                        if(txGroup) txGroup.style.display = 'inline-block';
                        if(txInput) txInput.setAttribute('required','required');
                    } else {
                        if(txGroup) txGroup.style.display = 'none';
                        if(txInput){ txInput.removeAttribute('required'); txInput.value = ''; }
                    }
                }
            });
        });

        // Toggle Other input visibility
        document.querySelectorAll('.cancel-reason-group input[name="cancel_reason"]').forEach(function(radio){
            radio.addEventListener('change', function(e){
                var group = radio.closest('.cancel-reason-group');
                if(!group) return;
                var otherInput = group.querySelector('.cancel-reason-other');
                if(!otherInput) return;
                if(radio.value === 'other'){
                    otherInput.style.display = 'inline-block';
                    otherInput.setAttribute('required','required');
                } else {
                    otherInput.style.display = 'none';
                    otherInput.removeAttribute('required');
                    otherInput.value = '';
                }
            });
        });

        // Prevent submit if required reason missing, and assemble final change_reason
        document.querySelectorAll('.status-form').forEach(function(f){
            f.addEventListener('submit', function(e){
                var sel = f.querySelector('select[name="update_status"]');
                var changeReasonHidden = f.querySelector('input[name="change_reason"]');
                if(sel && changeReasonHidden){
                    var val = (sel.value || '').toLowerCase();
                    if((val === 'cancelled' || val === 'cancel')){
                        var group = f.querySelector('.cancel-reason-group');
                        if(!group){
                            e.preventDefault();
                            alert('Please provide a reason for cancelling the order.');
                            return false;
                        }
                        var selected = group.querySelector('input[name="cancel_reason"]:checked');
                        var finalReason = '';
                        if(selected){
                            if(selected.value === 'other'){
                                var otherVal = (group.querySelector('.cancel-reason-other') || {value:''}).value.trim();
                                if(otherVal === ''){
                                    e.preventDefault();
                                    alert('Please provide the other reason for cancellation.');
                                    (group.querySelector('.cancel-reason-other') || {}).focus && (group.querySelector('.cancel-reason-other') || {}).focus();
                                    return false;
                                }
                                finalReason = otherVal;
                            } else {
                                // Map option to human-friendly label similar to profile.php
                                var map = {
                                    'changed_mind': 'Changed my mind',
                                    'found_cheaper': 'Found a better price',
                                    'wrong_item': 'Wrong item ordered'
                                };
                                finalReason = map[selected.value] || selected.value;
                            }
                        } else {
                            e.preventDefault();
                            alert('Please select a cancellation reason.');
                            return false;
                        }
                        changeReasonHidden.value = finalReason.substring(0,2000);
                    }

                    // If marking as shipped, ensure transaction number is provided
                    if(val === 'shipped'){
                        var txInput = f.querySelector('.transaction-number-input');
                        if(!txInput || !txInput.value.trim()){
                            e.preventDefault();
                            alert('Please provide a transaction number when marking an order as Shipped.');
                            txInput && txInput.focus && txInput.focus();
                            return false;
                        }
                        txInput.value = txInput.value.trim().substring(0,255);
                    }
                }
            });
        });
    });
</script>

</body>
</html>