<?php
ob_start();
session_start();
include 'lib/connection.php';

// Basic auth check
if (!isset($_SESSION['auth']) || $_SESSION['auth'] != 1) {
  header('Location: login.php');
  exit;
}

$k = $_SESSION['userid'];
// Allow GET requests for AJAX fragment (ajax=1) or POST for performing cancel
$is_ajax_get = ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['ajax']) && $_GET['ajax'] == '1');
if (!($is_ajax_get || $_SERVER['REQUEST_METHOD'] === 'POST')) {
  header('Location: profile.php');
  exit;
}

$action_order_id = isset($_REQUEST['order_id']) ? intval($_REQUEST['order_id']) : 0;
// If reason supplied it will be in POST; otherwise we'll render a small reason form
$cancel_reason_raw = $_POST['cancel_reason'] ?? '';
$cancel_reason_other = trim($_POST['cancel_reason_other'] ?? '');

// validate order belongs to user
$o_q = mysqli_query($conn, "SELECT status, totalproduct FROM orders WHERE o_id = '{$action_order_id}' AND user_id = '{$k}' LIMIT 1");
if (!($o_q && mysqli_num_rows($o_q) > 0)) {
  $_SESSION['error_message'] = 'Order not found or permission denied.';
  header('Location: profile.php');
  exit;
}

$o = mysqli_fetch_assoc($o_q);
$cur = trim($o['status']);
$lcCur = strtolower($cur);
if (!($lcCur === 'pending' || $lcCur === 'packing' || $lcCur === 'processing')) {
  $_SESSION['error_message'] = 'Order can only be cancelled while it is Pending or Packing.';
  header('Location: profile.php');
  exit;
}

$reason_options = [
  'changed_mind' => 'Changed my mind',
  'found_cheaper' => 'Found a better price',
  'wrong_item' => 'Wrong item ordered',
  'other' => 'Other'
];

// If no cancel reason was submitted yet, render a small page/fragment that asks for it
if (empty($cancel_reason_raw)) {
  // If this is an AJAX fragment request, return only the inner form fragment
  if ($is_ajax_get) {
    ?>
    <div class="ot-track-wrap">
      <div class="ot-panel">
        <h3>Cancel Order #<?php echo intval($action_order_id); ?></h3>
        <p>Please tell us why you are cancelling this order (required):</p>
        <form method="post" action="cancel_order.php">
          <input type="hidden" name="order_id" value="<?php echo intval($action_order_id); ?>">
          <div style="margin:8px 0;"><label><input type="radio" name="cancel_reason" value="changed_mind" required> Changed my mind</label></div>
          <div style="margin:8px 0;"><label><input type="radio" name="cancel_reason" value="found_cheaper"> Found a better price</label></div>
          <div style="margin:8px 0;"><label><input type="radio" name="cancel_reason" value="wrong_item"> Wrong item ordered</label></div>
          <div style="margin:8px 0;"><label><input type="radio" name="cancel_reason" value="other"> Other</label>
            <input type="text" name="cancel_reason_other" class="other-input" placeholder="Please specify" style="display:none; margin-left:8px;">
          </div>
          <div style="margin-top:12px;"><button type="submit" class="btn btn-danger">Confirm Cancel</button> <a href="profile.php" class="btn">Back</a></div>
        </form>
      </div>
    </div>
    <script>
      // close handler for the overlay
      (function(){
        var close = document.querySelector('.ot-close');
        if (close) close.addEventListener('click', function(){ var overlay = document.getElementById('orderTrackOverlay'); if (overlay) overlay.style.display='none'; });
        document.addEventListener('change', function(e){
          var el = e.target;
          if (!el || el.name !== 'cancel_reason') return;
          var form = el.closest('form'); if (!form) return; var other = form.querySelector('.other-input'); if (!other) return;
          if (el.value === 'other') { other.style.display='inline-block'; other.setAttribute('required','required'); } else { other.style.display='none'; other.removeAttribute('required'); other.value=''; }
        });
      })();
    </script>
    <?php
    exit;
  }

  // non-AJAX fallback: render full page form
  ?>
  <!doctype html>
  <html>
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Cancel Order</title>
    <link rel="stylesheet" href="css/css.css" type="text/css">
    <style> .reason-row { margin:8px 0; } .other-input{ display:none; margin-left:8px; } </style>
  </head>
  <body>
  <div class="container pendingbody">
    <h3>Cancel Order #<?php echo intval($action_order_id); ?></h3>
    <p>Please tell us why you are cancelling this order (required):</p>
    <form method="post" action="cancel_order.php">
      <input type="hidden" name="order_id" value="<?php echo intval($action_order_id); ?>">
      <div class="reason-row"><label><input type="radio" name="cancel_reason" value="changed_mind" required> Changed my mind</label></div>
      <div class="reason-row"><label><input type="radio" name="cancel_reason" value="found_cheaper"> Found a better price</label></div>
      <div class="reason-row"><label><input type="radio" name="cancel_reason" value="wrong_item"> Wrong item ordered</label></div>
      <div class="reason-row"><label><input type="radio" name="cancel_reason" value="other"> Other</label> <input type="text" name="cancel_reason_other" class="other-input" placeholder="Please specify"></div>
      <div style="margin-top:12px;"><button type="submit" class="btn btn-danger">Confirm Cancel</button> <a href="profile.php" class="btn">Back</a></div>
    </form>
  </div>
  <script>
    document.addEventListener('change', function(e){
      var el = e.target;
      if (!el || el.name !== 'cancel_reason') return;
      var form = el.closest('form');
      if (!form) return;
      var other = form.querySelector('.other-input');
      if (!other) return;
      if (el.value === 'other') { other.style.display='inline-block'; other.setAttribute('required','required'); }
      else { other.style.display='none'; other.removeAttribute('required'); other.value=''; }
    });
  </script>
  </body>
  </html>
  <?php
  exit;
}

// Normalize the submitted reason now
$cancel_reason_text = 'Unspecified';
if (!empty($cancel_reason_raw) && array_key_exists($cancel_reason_raw, $reason_options)) {
  if ($cancel_reason_raw === 'other' && $cancel_reason_other !== '') {
    $cancel_reason_text = $cancel_reason_other;
  } else {
    $cancel_reason_text = $reason_options[$cancel_reason_raw];
  }
}

// perform cancellation inside transaction
if (strtolower($cur) === 'completed' || strtolower($cur) === 'cancelled') {
  $_SESSION['error_message'] = 'Order cannot be cancelled.';
  header('Location: profile.php');
  exit;
}

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
  // save cancellation reason
  $reason_esc = mysqli_real_escape_string($conn, mb_substr($cancel_reason_text, 0, 2000));
  $ins = mysqli_query($conn, "INSERT INTO user_order_cancellations (order_id, user_id, reason) VALUES ('{$action_order_id}', '{$k}', '{$reason_esc}')");
  if (!$ins) throw new Exception('Failed to save cancellation reason.');

  mysqli_commit($conn);
  if (!isset($_SESSION['user_cancelled_orders']) || !is_array($_SESSION['user_cancelled_orders'])) {
    $_SESSION['user_cancelled_orders'] = [];
  }
  $_SESSION['user_cancelled_orders'][] = $action_order_id;
  $_SESSION['success_message'] = 'Order cancelled successfully. Stock has been restored.';
} catch (Exception $e) {
  mysqli_rollback($conn);
  $_SESSION['error_message'] = 'Failed to cancel order.';
}

header('Location: profile.php');
exit;

?>