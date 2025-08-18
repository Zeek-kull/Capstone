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

// Handle order removal
if (isset($_GET['remove'])) {
    $remove_id = $_GET['remove'];
    mysqli_query($conn, "DELETE FROM `orders` WHERE o_id = '$remove_id'");
    $_SESSION['success_message'] = "Order removed successfully";
    header('location:pending_orders.php');
    exit();
}

// Get all orders with latest status
$sql = "SELECT o.*, u.email as user_email FROM orders o 
        LEFT JOIN users u ON o.user_id = u.id 
        ORDER BY o.created_at DESC";
$result = $conn->query($sql);
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

<div class="container pendingbody">
  <h5>Order Management</h5>
  <table class="table">
    <thead>
      <tr>
        <th scope="col">Date</th>
        <th scope="col">Name</th>
        <th scope="col">Address</th>
        <th scope="col">Phone</th>
        <th scope="col">Total Product</th>
        <th scope="col">Total Price</th>
        <th scope="col">Status</th>
        <th scope="col">Action</th>
      </tr>
    </thead>
    <tbody>
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
            ?>
            <tr>
              <td>
                <?php 
                // Adjust order date with timezone using 'created_at' column
                if (!empty($row["created_at"])) {
                  $date = new DateTime($row["created_at"]);
                  $date->setTimezone(new DateTimeZone('Asia/Manila'));
                  echo $date->format("F j, Y, g:i A");
                } else {
                  echo "N/A";
                }
                ?>
              </td>
              <td><?php echo htmlspecialchars($row["name"]); ?></td>
              <td><?php echo htmlspecialchars($row["address"]); ?></td>
              <td>
                <?php
                  // If phone is valid, show it; else show mobnumber if available
                  $phone = $row["phone"];
                  if (preg_match('/^[0-9]+$/', $phone) && strlen($phone) >= 7 && strlen($phone) <= 15) {
                    echo htmlspecialchars($phone);
                  } elseif (!empty($row["mobnumber"])) {
                    echo htmlspecialchars($row["mobnumber"]);
                  } else {
                    echo "N/A";
                  }
                ?>
              </td>
              <td>
                <?php
                  // Sum all product quantities for this order
                  $products = explode(',', $row["totalproduct"]);
                  $total_quantity = 0;
                  foreach ($products as $prod) {
                    if (preg_match('/\((\d+)\)/', $prod, $matches)) {
                      $total_quantity += (int)$matches[1];
                    }
                  }
                  echo htmlspecialchars($total_quantity);
                ?>
              </td>
              <td><?php echo "₱" . number_format($row["totalprice"], 2); ?></td>
              <td>
                <span class="badge badge-<?php 
                  switch($row['status']) {
                    case 'Pending': echo 'warning'; break;
                    case 'Processing': echo 'info'; break;
                    case 'Shipped': echo 'primary'; break;
                    case 'Completed': echo 'success'; break;
                    case 'Cancelled': echo 'danger'; break;
                    default: echo 'secondary';
                  }
                ?>">
                  <?php echo htmlspecialchars($row['status']); ?>
                </span>
                <?php if ($history_count > 0): ?>
                  <br><small class="text-muted">
                    <a href="#" onclick="showStatusHistory(<?php echo $row['o_id']; ?>)">
                      View history (<?php echo $history_count; ?>)
                    </a>
                  </small>
                <?php endif; ?>
              </td>
              <td>
                <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post" class="status-form">
                  <input type="hidden" name="update_id" value="<?php echo $row['o_id']; ?>">
                  <div class="form-group">
                    <select name="update_status" class="form-control form-control-sm" required>
                      <option value="" disabled selected>Change status...</option>
                      <?php
                      foreach ($valid_next_statuses as $status) {
                          echo "<option value=\"" . htmlspecialchars($status) . "\">" . htmlspecialchars($status) . "</option>";
                      }
                      ?>
                    </select>
                  </div>
                  <div class="form-group">
                    <input type="text" name="change_reason" class="form-control form-control-sm" 
                           placeholder="Reason for change (optional)">
                  </div>
                  <button type="submit" name="update_update_btn" class="btn btn-primary btn-sm btn-block">
                    Update Status
                  </button>
                </form>
                <a href="pending_orders.php?remove=<?php echo urlencode($row['o_id']); ?>" 
                   class="btn btn-danger btn-sm btn-block mt-1" 
                   onclick="return confirm('Are you sure you want to remove this order?')">
                  Remove
                </a>
              </td>
            </tr>
            <?php
        }
    } else {
        echo "<tr><td colspan='8' class='text-center'>No orders found</td></tr>";
    }
    ?>
    </tbody>
  </table>
</div>
</body>
</html>
