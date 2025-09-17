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
    // If current status is Shipped (or legacy Arriving/OFD), user action means confirm receipt -> Completed
    $lc = strtolower($cur);
    if ($lc === 'shipped' || $lc === 'arriving' || $lc === 'ofd' || strpos($lc, 'out for') !== false) {
      $new = 'Completed';
      $upd = mysqli_query($conn, "UPDATE orders SET status = '{$new}', status_updated_at = NOW() WHERE o_id = '{$action_order_id}' AND user_id = '{$k}'");
      if ($upd) {
        $_SESSION['success_message'] = 'Order marked as received. Thank you!';
      } else {
        $_SESSION['error_message'] = 'Failed to update order status.';
      }
    } else {
      // Otherwise attempt to cancel the order if not already completed/cancelled
      // Allow cancellation only if current status is Pending or Packing (or legacy Processing)
      $lcCur = strtolower($cur);
      if ($lcCur === 'pending' || $lcCur === 'packing' || $lcCur === 'processing') {
        // collect and validate cancel reason (optional -- required on UI)
        $cancel_reason_raw = $_POST['cancel_reason'] ?? '';
        $cancel_reason_other = trim($_POST['cancel_reason_other'] ?? '');
        $reason_options = [
          'changed_mind' => 'Changed my mind',
          'found_cheaper' => 'Found a better price',
          'wrong_item' => 'Wrong item ordered',
          'other' => 'Other'
        ];
        $cancel_reason_text = 'Unspecified';
        if (!empty($cancel_reason_raw) && array_key_exists($cancel_reason_raw, $reason_options)) {
          if ($cancel_reason_raw === 'other' && $cancel_reason_other !== '') {
            $cancel_reason_text = $cancel_reason_other;
          } else {
            $cancel_reason_text = $reason_options[$cancel_reason_raw];
          }
        }
      } else {
        $_SESSION['error_message'] = 'Order can only be cancelled while it is Pending or Packing.';
        // redirect early
        header('Location: profile.php');
        exit;
      }
      // Now perform cancellation
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
          $_SESSION['success_message'] = 'Order cancelled successfully. Stock has been restored.';
          // Save cancellation reason to user_order_cancellations
          $reason_esc = mysqli_real_escape_string($conn, mb_substr($cancel_reason_text, 0, 2000));
          $ins = mysqli_query($conn, "INSERT INTO user_order_cancellations (order_id, user_id, reason) VALUES ('{$action_order_id}', '{$k}', '{$reason_esc}')");
          if (!$ins) {
            throw new Exception('Failed to save cancellation reason.');
          }
          mysqli_commit($conn);
          // remember in session that this order was cancelled by the user
          if (!isset($_SESSION['user_cancelled_orders']) || !is_array($_SESSION['user_cancelled_orders'])) {
            $_SESSION['user_cancelled_orders'] = [];
          }
          $_SESSION['user_cancelled_orders'][] = $action_order_id;
          $_SESSION['success_message'] = 'Order cancelled successfully. Stock has been restored.';
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
  // Keep labels simple and aligned with DB enum values
  'Pending' => 'Order being processed',
  'Packing' => 'Order being pack by seller',
  // Backwards compatibility: accept old 'Processing' label if present in data
  'Processing' => 'Order being pack by seller',
  'Shipped' => 'Order is handed to courier',
  'OFD' => 'Out for delivery',
  'Arriving' => 'Arriving',
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
  include __DIR__ . '/includes/flash.php';
  ?>
  <?php
  // Get user info including address from users table
  $user_info = mysqli_query($conn, "SELECT f_name, l_name, street, zone, province, city, barangay, phone FROM users WHERE u_id='$k'");
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
  $_SESSION['success_message'] = 'Shipping address updated.';
  header('Location: profile.php');
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
            if (strcasecmp($st, 'Pending') === 0 || strcasecmp($st, 'Packing') === 0 || strcasecmp($st, 'Processing') === 0) $badgeClass = 'badge-warning';
            if (strcasecmp($st, 'Shipped') === 0 || strcasecmp($st, 'OFD') === 0 || strcasecmp($st, 'Out for delivery') === 0) $badgeClass = 'badge-info';
            if (strcasecmp($st, 'Arriving') === 0) $badgeClass = 'badge-primary';
            if (strcasecmp($st, 'Completed') === 0) $badgeClass = 'badge-success';
            if (strcasecmp($st, 'Cancelled') === 0) $badgeClass = 'badge-danger';
            // If this order was cancelled by the current user in this session, show a clearer label
            $sessionCancelled = $_SESSION['user_cancelled_orders'] ?? [];
            $orderIdLookup = (int)($row['o_id'] ?? 0);
            if (strcasecmp($st, 'Cancelled') === 0) {
              // First check order_status_history: if an admin changed the status to Cancelled, the
              // history record's changed_by should reference an admin id (>0). Prefer this as the
              // authoritative indicator that the seller/admin cancelled the order.
              $hist_q = mysqli_query($conn, "SELECT changed_by FROM order_status_history WHERE order_id = '{$orderIdLookup}' AND LOWER(new_status) = 'cancelled' ORDER BY created_at DESC LIMIT 1");
              $markedByAdmin = false;
              if ($hist_q && mysqli_num_rows($hist_q) > 0) {
                $hist_row = mysqli_fetch_assoc($hist_q);
                $changed_by = $hist_row['changed_by'] ?? null;
                if (!is_null($changed_by) && $changed_by !== '' && intval($changed_by) > 0) {
                  $markedByAdmin = true;
                  $badgeText = 'Cancelled by Seller';
                }
              }

              if (!$markedByAdmin) {
                // If there is no admin history entry, fall back to user_order_cancellations to
                // see whether the cancellation was initiated by the user (they submitted a cancel
                // reason). Note: admin code also inserts into user_order_cancellations for display
                // but the presence of an admin entry in order_status_history takes precedence.
                $uoc_q = mysqli_query($conn, "SELECT user_id FROM user_order_cancellations WHERE order_id = '{$orderIdLookup}' ORDER BY created_at DESC LIMIT 1");
                if ($uoc_q && mysqli_num_rows($uoc_q) > 0) {
                  $uoc_row = mysqli_fetch_assoc($uoc_q);
                  $uoc_user = intval($uoc_row['user_id'] ?? 0);
                  if ($uoc_user === intval($k)) {
                    $badgeText = 'Cancelled by you';
                  } else {
                    // If the cancellation row exists but the user_id is different, treat it as
                    // cancelled by the seller (defensive fallback).
                    $badgeText = 'Cancelled by Seller';
                  }
                } else {
                  // No history and no cancellation reason saved: leave the generic label
                  $badgeText = $status_label_map[$st] ?? $st;
                }
              }
            }
            echo "<span class='badge {$badgeClass}'>" . htmlspecialchars($badgeText) . "</span>";
          ?>
        </td>
              <td>
                <?php
                  // Make the tracking cell clickable and open a small order tracking page
                  $orderId = $row['o_id'] ?? null;
                  // Build a tracking number: prefer transaction_number if present, otherwise use legacy ORD-<id>
                  $tracking_no = '';
                  if (!empty($row['transaction_number'])) {
                    $tracking_no = $row['transaction_number'];
                  } elseif (!empty($orderId)) {
                    $tracking_no = 'ORD-' . $orderId;
                  }
                  // Render a Track button; JS will open the tracking overlay when clicked
                  $btnTitle = 'Track order #' . htmlspecialchars($orderId);
                  // Use inline style to ensure the button color is applied consistently
                  $btnStyle = 'background:#e7ab3c;border-color:#e7ab3c;color:#fff';
                  // Add data-transaction attribute so JS can surface the tracking number inside the overlay
                  echo '<button type="button" class="btn btn-sm track-btn" style="' . $btnStyle . '" data-order-id="' . htmlspecialchars($orderId) . '" data-transaction="' . htmlspecialchars($tracking_no) . '" title="' . $btnTitle . '">Track</button>';
                ?>
              </td>
              <td>
                <!-- Action: Cancel or Mark Received -->
                <?php
                  $curst = trim($row['status']);
                  $lcst = strtolower($curst);
                  // Show Mark Received when order is Shipped (also accept legacy OFD/Arriving)
                  if ($lcst === 'shipped' || $lcst === 'arriving' || $lcst === 'ofd' || strpos($lcst, 'out for') !== false) {
                    echo '<form method="post" style="display:inline;" onsubmit="return confirm(\'Mark this order as received?\');">';
                    echo '<input type="hidden" name="order_id" value="' . intval($row['o_id']) . '">';
                    echo '<button type="submit" name="order_action_btn" class="btn btn-sm btn-success">Mark Received</button>';
                    echo '</form>';
                  } else {
                    // show Cancel button only when status is Pending or Packing (accept legacy Processing)
                    if (strcasecmp($curst, 'Pending') === 0 || strcasecmp($curst, 'Packing') === 0 || strcasecmp($curst, 'Processing') === 0) {
                      // Cancel opens a popup fragment to collect reason
                      echo '<button type="button" class="btn btn-sm cancel-btn" data-order-id="' . intval($row['o_id']) . '" style="background:#d9534f;border-color:#d9534f;color:#fff">Cancel</button>';
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
    // store the clicked button's tracking number on the overlay so we can use it if the fragment
    // doesn't include a tracking number (server fragments may be older versions)
    var clickedTracking = btn ? (btn.getAttribute('data-transaction') || '') : '';
    overlay._clickedTracking = clickedTracking;
    overlay.style.display = 'flex';
    content.innerHTML = 'Loading...';
    fetch(href.toString(), { credentials: 'same-origin' })
      .then(function(r){ return r.text(); })
      .then(function(html){
        content.innerHTML = html;
        // attach close handler inside the content
        var closeBtn = content.querySelector('.ot-close');
        if (closeBtn) closeBtn.addEventListener('click', function(){ overlay.style.display='none'; });
        // attach copy/open-jnt handlers
        var copyBtn = content.querySelector('.copy-track');
        if (copyBtn) {
          // ensure copy button has the tracking number; prefer fragment-provided data-track,
          // otherwise fall back to the clicked button's data-transaction stored on overlay
          if (!copyBtn.getAttribute('data-track') || copyBtn.getAttribute('data-track').trim() === '') {
            if (overlay._clickedTracking && overlay._clickedTracking.trim() !== '') {
              copyBtn.setAttribute('data-track', overlay._clickedTracking);
            }
          }
          // Use Font Awesome copy icon if available, otherwise fallback to emoji
          var iconHTML = '';
          if (typeof window.FontAwesome !== 'undefined' || document.querySelector('.fa')) {
            // common FA class
            iconHTML = '<i class="fa fa-copy" aria-hidden="true"></i>';
          } else {
            // inline SVG clipboard icon (small, self-contained)
            iconHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path></svg>';
          }
          // replace button inner content with icon while preserving accessible label
          copyBtn.innerHTML = iconHTML;
          copyBtn.setAttribute('title', 'Copy tracking number');
          copyBtn.setAttribute('aria-label', 'Copy tracking number');
          copyBtn.addEventListener('click', function(){
            var t = copyBtn.getAttribute('data-track') || '';
            navigator.clipboard && navigator.clipboard.writeText(t).then(function(){
              if (typeof showFlash === 'function') showFlash('success', 'Tracking number copied to clipboard', 2000);
            }, function(){
              alert('Copy failed. Please select and copy the tracking number manually: ' + t);
            });
          });
        }
        var jntBtn = content.querySelector('.open-jnt');
        if (jntBtn) {
          if (!jntBtn.getAttribute('data-track') || jntBtn.getAttribute('data-track').trim() === '') {
            if (overlay._clickedTracking && overlay._clickedTracking.trim() !== '') {
              jntBtn.setAttribute('data-track', overlay._clickedTracking);
            }
          }
          jntBtn.addEventListener('click', function(){
            var t = jntBtn.getAttribute('data-track') || '';
            // JNT tracking URL - open in new tab
            var url = 'https://www.jtexpress.ph/track-and-trace';
            window.open(url, '_blank');
          });
        }
        // attach cancel fragment handlers if present
        var cancelBtnFrag = content.querySelector('.btn-danger');
        if (cancelBtnFrag) {
          // when cancel form posts, overlay will close on redirect; no extra wiring needed
        }
      })
      .catch(function(){ content.innerHTML = 'Failed to load.'; });
  });

  // Open cancel fragment in the same overlay when Cancel button clicked
  document.addEventListener('click', function(e){
    var cb = e.target.closest('.cancel-btn');
    if (!cb) return;
    e.preventDefault();
    var orderId = cb.getAttribute('data-order-id');
    var href = new URL('cancel_order.php', window.location.href);
    href.searchParams.set('ajax', '1');
    href.searchParams.set('order_id', orderId);
    var overlay = document.getElementById('orderTrackOverlay');
    var content = document.getElementById('orderTrackContent');
    overlay.style.display = 'flex';
    content.innerHTML = 'Loading...';
    fetch(href.toString(), { credentials: 'same-origin' })
      .then(function(r){ return r.text(); })
      .then(function(html){ content.innerHTML = html; var closeBtn = content.querySelector('.ot-close'); if (closeBtn) closeBtn.addEventListener('click', function(){ overlay.style.display='none'; }); })
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
  // Toggle 'Other' reason input visibility in cancel forms
  document.addEventListener('change', function(e){
    var el = e.target;
    if (!el || el.name !== 'cancel_reason') return;
    var form = el.closest('form');
    if (!form) return;
    // support either class name depending on fragment: .cancel-reason-other (old) or .other-input (new)
    var otherInput = form.querySelector('.cancel-reason-other') || form.querySelector('.other-input');
    if (!otherInput) return;
    if (el.value === 'other') {
      otherInput.style.display = 'inline-block';
      otherInput.setAttribute('required', 'required');
    } else {
      otherInput.style.display = 'none';
      otherInput.removeAttribute('required');
      otherInput.value = '';
    }
  });
  </script>

</body>
</html>

<?php include 'footer.php'; ?>