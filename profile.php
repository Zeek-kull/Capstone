<?php
ob_start();
session_start();
include 'header.php';

// Redirect to login if not authenticated
if (!isset($_SESSION['auth']) || $_SESSION['auth'] != 1) {
  header("location:login.php");
  exit;
}

include 'lib/connection.php';
$k = $_SESSION['userid'];
// Handle user-initiated order action: cancel or mark received
if (isset($_POST['order_action_btn'])) {
  $action_order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
  // ensure order belongs to user
  $o_q = mysqli_query($conn, "SELECT status, totalproduct FROM orders WHERE o_id = '{$action_order_id}' AND user_id = '{$k}' LIMIT 1");
  if ($o_q && mysqli_num_rows($o_q) > 0) {
    $o = mysqli_fetch_assoc($o_q);
    $cur = trim($o['status']);
    // If current status is Arriving, user action means confirm receipt -> Completed
  if (strtolower($cur) === 'arriving') {
      $new = 'Completed';
      $upd = mysqli_query($conn, "UPDATE orders SET status = '{$new}', status_updated_at = NOW() WHERE o_id = '{$action_order_id}' AND user_id = '{$k}'");
      if ($upd) {
        $_SESSION['success_message'] = '';
  } else {
        $_SESSION['error_message'] = 'Failed to update order status.';
      }
    } else {
      // Otherwise attempt to cancel the order if not already completed/cancelled
      if (strtolower($cur) === 'completed' || strtolower($cur) === 'cancelled') {
        $_SESSION['error_message'] = 'Order cannot be cancelled.';
      } else {
        $new = 'Cancelled';
        mysqli_begin_transaction($conn);
  try {
          $upd = mysqli_query($conn, "UPDATE orders SET status = '{$new}', status_updated_at = NOW() WHERE o_id = '{$action_order_id}' AND user_id = '{$k}'");
          // restore stock based on totalproduct string
          $tp = $o['totalproduct'] ?? '';
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
          mysqli_commit($conn);
          // remember in session that this order was cancelled by the user
          if (!isset($_SESSION['user_cancelled_orders']) || !is_array($_SESSION['user_cancelled_orders'])) {
            $_SESSION['user_cancelled_orders'] = [];
          }
          $_SESSION['user_cancelled_orders'][] = $action_order_id;
          $_SESSION['success_message'] = '';
        } catch (Exception $e) {
          mysqli_rollback($conn);
          $_SESSION['error_message'] = 'Failed to cancel order.';
        }
      }
    }
  } else {
    $_SESSION['error_message'] = 'Order not found or permission denied.';
  }
  // Redirect to avoid reposts
  header('Location: profile.php');
  exit;
}
$sql = "SELECT *, DATE_FORMAT(created_at, '%Y-%m-%d %H:%i:%s') AS created_at_display FROM orders WHERE user_id='$k' ORDER BY created_at DESC";
$result = $conn->query($sql);
// Map DB status values to friendly labels (DB stores 'OFD' for Out for delivery)
$status_label_map = [
  'Pending' => 'Order placed',
  'Processing' => 'Order is being processed',
  'Shipped' => 'Order shipped',
  'OFD' => 'Out for delivery',
  'Arriving' => 'Arriving',
  'Confirmed' => 'Order confirmed',
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
  <title>My Orders</title>
  <link rel="stylesheet" href="css/css.css" type="text/css">
</head>
<body>

<div class="container pendingbody">
  <?php
  // Show user flash messages (if any)
  if (isset($_SESSION['success_message']) && !empty($_SESSION['success_message'])) {
    echo '<div class="alert alert-success" role="alert">' . htmlspecialchars($_SESSION['success_message']) . '</div>';
    unset($_SESSION['success_message']);
  }
  if (isset($_SESSION['error_message']) && !empty($_SESSION['error_message'])) {
    echo '<div class="alert alert-danger" role="alert">' . htmlspecialchars($_SESSION['error_message']) . '</div>';
    unset($_SESSION['error_message']);
  }
  ?>
  <?php
  // Get user info including address from users table
  $user_info = mysqli_query($conn, "SELECT f_name, l_name, street, zone, province, city, barangay, phone FROM users WHERE id='$k'");
  $user_row = mysqli_fetch_assoc($user_info);
  $user_name = $user_row['f_name'] ?? ($_SESSION['username'] ?? '');
  $user_lname = $user_row['l_name'] ?? '';
  // Build complete address from user data
  $user_address = '';
  if ($user_row) {
    $address_main = [];
    if (!empty($user_row['street'])) $address_main[] = $user_row['street'];
    if (!empty($user_row['zone'])) $address_main[] = $user_row['zone'];
    if (!empty($user_row['barangay'])) $address_main[] = $user_row['barangay'];
    $address_first = implode(' ', $address_main);
    $address_second = [];
    if (!empty($user_row['city'])) $address_second[] = $user_row['city'];
    if (!empty($user_row['province'])) $address_second[] = $user_row['province'];
    $user_address = $address_first;
    if (!empty($address_second)) {
      $user_address .= ', ' . implode(', ', $address_second);
    }
  }
  ?>
  <div class="mb-3">
    <h4>Name: <?php echo htmlspecialchars($user_name . ' ' . $user_lname); ?></h4>
    <h5>Phone: <?php echo htmlspecialchars($user_row['phone'] ?? 'N/A'); ?></h5>
    <h5>Address: <?php echo htmlspecialchars($user_address); ?></h5>
  </div>
  <h5>My Orders</h5>
  <table class="table">
    <thead>
      <tr>
        <th scope="col">Date</th>
        <th scope="col">Name</th>
        <th scope="col">Address</th>
        <th scope="col">Phone</th>
        <th scope="col">Total Product</th>
        <th scope="col">Total Price</th>
        <th scope="col">Payment Method</th>
        <th scope="col">Status</th>
        <th scope="col">Track</th>
        <th scope="col">Action</th>
      </tr>
    </thead>
    <tbody>
    <?php
  // Handle shipping address update
  if (isset($_POST['update_address_btn'])) {
    $update_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
    $new_address = $_POST['new_address'] ?? '';
    $update_query = mysqli_query($conn, "UPDATE `orders` SET address = '" . mysqli_real_escape_string($conn, $new_address) . "' WHERE o_id = '{$update_id}' AND user_id = '{$k}'");
    if ($update_query) {
      echo "<script>window.location.href='profile.php';</script>";
      exit();
    }
  }

    if (mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            ?>
            <tr>
              <td>
                <?php 
                // Prefer formatted alias from SQL to avoid microseconds in UI
                $created_for_display = $row['created_at_display'] ?? $row['created_at'];
                if (!empty($created_for_display)) {
                  $date = new DateTime($created_for_display);
                  $date->setTimezone(new DateTimeZone('Asia/Manila'));
                  echo $date->format("F j, Y, g:i A"); 
                } else {
                  echo "N/A";
                }
                ?>
              </td>
              <td><?php echo htmlspecialchars($row["name"]); ?></td>
              <td>
                <?php
                  $order_address = $row["address"];
                  echo htmlspecialchars($order_address);
                ?>
              </td>
              <td>
                <?php
                  $order_phone = $row["phone"];
                  if (!empty($order_phone) && preg_match('/^[0-9]+$/', $order_phone) && strlen($order_phone) >= 7 && strlen($order_phone) <= 15) {
                    echo htmlspecialchars($order_phone);
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
              <td><?php echo htmlspecialchars($row["payment_method"]); ?></td>
        <td>
          <?php
            $st = trim($row["status"]);
            $badgeText = $status_label_map[$st] ?? $st;
            $badgeClass = 'badge-info';
            if (strcasecmp($st, 'Pending') === 0 || strcasecmp($st, 'Processing') === 0) $badgeClass = 'badge-warning';
            if (strcasecmp($st, 'Shipped') === 0 || strcasecmp($st, 'OFD') === 0 || strcasecmp($st, 'Out for delivery') === 0) $badgeClass = 'badge-info';
            if (strcasecmp($st, 'Arriving') === 0) $badgeClass = 'badge-primary';
            if (strcasecmp($st, 'Completed') === 0) $badgeClass = 'badge-success';
            if (strcasecmp($st, 'Cancelled') === 0) $badgeClass = 'badge-danger';
            // If this order was cancelled by the current user in this session, show a clearer label
            $sessionCancelled = $_SESSION['user_cancelled_orders'] ?? [];
            $orderIdLookup = (int)($row['o_id'] ?? $row['id'] ?? 0);
            if (strcasecmp($st, 'Cancelled') === 0) {
              if (in_array($orderIdLookup, $sessionCancelled, true)) {
                $badgeText = 'Cancelled by you';
              } else {
                // Check order_status_history to see if the latest Cancelled entry was made by an admin
                $hist_q = mysqli_query($conn, "SELECT changed_by FROM order_status_history WHERE order_id = '{$orderIdLookup}' AND LOWER(new_status) = 'cancelled' ORDER BY created_at DESC LIMIT 1");
                if ($hist_q && mysqli_num_rows($hist_q) > 0) {
                  $hist_row = mysqli_fetch_assoc($hist_q);
                  $changed_by = $hist_row['changed_by'] ?? null;
                  if (!is_null($changed_by) && $changed_by !== '' && intval($changed_by) > 0) {
                    // presence of changed_by (admin id) implies admin performed the cancellation
                    $badgeText = 'Cancelled by Seller';
                  }
                }
              }
            }
            echo "<span class='badge {$badgeClass}'>" . htmlspecialchars($badgeText) . "</span>";
          ?>
        </td>
              <td>
                <?php
                  // Make the tracking cell clickable and open a small order tracking page
                  $orderId = $row['id'] ?? $row['o_id'] ?? null;
                  // Render a Track button; JS will open the tracking overlay when clicked
                  $btnTitle = 'Track order #' . htmlspecialchars($orderId);
                  // Use inline style to ensure the button color is applied consistently
                  $btnStyle = 'background:#e7ab3c;border-color:#e7ab3c;color:#fff';
                  echo '<button type="button" class="btn btn-sm track-btn" style="' . $btnStyle . '" data-order-id="' . htmlspecialchars($orderId) . '" title="' . $btnTitle . '">Track</button>';
                ?>
              </td>
              <td>
                <!-- Action: Cancel or Mark Received -->
                <?php
                  $curst = trim($row['status']);
                  if (strtolower($curst) === 'arriving') {
                    // show Mark as Received
                    echo '<form method="post" style="display:inline;" onsubmit="return confirm(\'Mark this order as received?\');">';
                    echo '<input type="hidden" name="order_id" value="' . intval($row['o_id']) . '">';
                    echo '<button type="submit" name="order_action_btn" class="btn btn-sm btn-success">Mark Received</button>';
                    echo '</form>';
                  } else {
                    // show Cancel button if not arriving/completed/cancelled
                    if (!in_array(strtolower($curst), ['completed','cancelled'])) {
                      echo '<form method="post" style="display:inline;" onsubmit="return confirm(\'Are you sure you want to cancel this order?\');">';
                      echo '<input type="hidden" name="order_id" value="' . intval($row['o_id']) . '">';
                      echo '<button type="submit" name="order_action_btn" class="btn btn-sm btn-danger">Cancel</button>';
                      echo '</form>';
                    }
                  }
                ?>
              </td>
            </tr>
            <?php
        }
    } else {
  echo "<tr><td colspan='10' class='text-center'>No orders found</td></tr>";
    }
    ?>
    </tbody>
  </table>
</div>
    
  <!-- Modal overlay for order tracking -->
  <div id="orderTrackOverlay" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.45); z-index:99999; align-items:center; justify-content:center;">
    <div id="orderTrackCard" style="background:#fff; max-width:720px; width:95%; border-radius:8px; padding:18px; box-shadow:0 8px 24px rgba(0,0,0,0.3); position:relative;">
      <button id="orderTrackClose" style="position:absolute; right:20px; top:16px; background:transparent; border:none; font-size:18px;">&times;</button>
      <div id="orderTrackContent">Loading...</div>
    </div>
  </div>

  <script>
  // Open tracking popup and fetch fragment via AJAX
  document.addEventListener('click', function(e) {
    // handle old anchor links and new track buttons
    var anchor = e.target.closest('a[href*="order_track.php"]');
    var btn = e.target.closest('.track-btn');
    if (!anchor && !btn) return;
    e.preventDefault();
    var orderId = btn ? btn.getAttribute('data-order-id') : null;
    var href;
    if (anchor) {
      href = new URL(anchor.href, window.location.href);
      href.searchParams.set('ajax', '1');
    } else {
      href = new URL('order_track.php', window.location.href);
      href.searchParams.set('order_id', orderId);
      href.searchParams.set('ajax', '1');
    }
    var overlay = document.getElementById('orderTrackOverlay');
    var content = document.getElementById('orderTrackContent');
    overlay.style.display = 'flex';
    content.innerHTML = 'Loading...';
    fetch(href.toString(), { credentials: 'same-origin' })
      .then(function(r){ return r.text(); })
      .then(function(html){
        content.innerHTML = html;
        // attach close handler inside the content
        var closeBtn = content.querySelector('.ot-close');
        if (closeBtn) closeBtn.addEventListener('click', function(){ overlay.style.display='none'; });
      })
      .catch(function(){ content.innerHTML = 'Failed to load.'; });
  });

  // Close overlay handlers
  document.getElementById('orderTrackClose').addEventListener('click', function(){
    document.getElementById('orderTrackOverlay').style.display = 'none';
  });
  document.getElementById('orderTrackOverlay').addEventListener('click', function(e){
    if (e.target === this) this.style.display = 'none';
  });
  // Close overlay when Escape is pressed
  document.addEventListener('keydown', function(e){
    if (e.key === 'Escape' || e.key === 'Esc') {
      var overlay = document.getElementById('orderTrackOverlay');
      if (overlay && overlay.style.display && overlay.style.display !== 'none') {
        overlay.style.display = 'none';
      }
    }
  });
  </script>

</body>
</html>

<?php include 'footer.php'; ?>