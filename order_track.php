<?php
session_start();
include 'lib/connection.php';

// Require authentication
if (!isset($_SESSION['auth']) || $_SESSION['auth'] != 1) {
    header('Location: login.php');
    exit;
}

$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
$user_id = $_SESSION['userid'];

// Verify ownership: the order must belong to the current user
$order_q = $conn->query("SELECT * FROM orders WHERE o_id = '$order_id' AND user_id = '$user_id' LIMIT 1");
if (!$order_q || $order_q->num_rows == 0) {
    echo "<p class='text-center'>Order not found or you don't have permission to view it.</p>";
    exit;
}
$order = $order_q->fetch_assoc();

// Fetch status history
$hist_q = $conn->query("SELECT * FROM order_status_history WHERE order_id = '$order_id' ORDER BY created_at ASC");

// Build a richer tracking fragment inspired by try.html (status badge, kv, stepper, timeline)
$events = [];
if ($hist_q && $hist_q->num_rows > 0) {
    while ($h = $hist_q->fetch_assoc()) {
        // Normalize timestamp
        $ts = '';
        if (!empty($h['created_at'])) {
            try {
                $dt = new DateTime($h['created_at']);
                $dt->setTimezone(new DateTimeZone('Asia/Manila'));
                $ts = $dt->format('Y-m-d H:i:s');
            } catch (Exception $e) {
                $ts = preg_replace('/\.[0-9]+$/', '', $h['created_at']);
            }
        }
        $events[] = [
            'time' => $ts,
            'status' => $h['new_status'],
            'note' => $h['change_reason'] ?? '',
            'location' => $h['changed_by'] ?? ''
        ];
    }
}

// If no events, include the order creation as the initial event
if (empty($events)) {
    $created = '';
    if (!empty($order['created_at'])) {
        try {
            $dt = new DateTime($order['created_at']);
            $dt->setTimezone(new DateTimeZone('Asia/Manila'));
            $created = $dt->format('Y-m-d H:i:s');
        } catch (Exception $e) {
            $created = preg_replace('/\.[0-9]+$/', '', $order['created_at']);
        }
    }
    $events[] = ['time' => $created, 'status' => $order['status'], 'note' => '', 'location' => ''];
}

// Steps definition aligned to DB enum: Packing -> Shipped -> Completed
$steps = ['Packing', 'Shipped', 'Delivered'];
$status_to_index = [
    // 'pending' and 'placed' do not map to a visible step (no active step shown)
    'pending' => -1,
    'placed' => -1,
    'packing' => 0,
    'processing' => 0,
    'packed' => 0,
    // Map shipped and common legacy variants to step 1
    'shipped' => 1,
    'in transit' => 1,
    'out for delivery' => 1,
    'outfordelivery' => 1,
    'ofd' => 1,
    'arriving' => 1,
    'arrive' => 1,
    // Completed/delivered map to final step
    'completed' => 2,
    'delivered' => 2
];

$curStatus = strtolower(trim($order['status'] ?? ''));
$currentIndex = -1; // -1 means no active step (used for Pending / Placed)
foreach ($status_to_index as $k => $v) {
    if (strpos($curStatus, $k) !== false) {
        $currentIndex = $v;
        break;
    }
}
// If order is cancelled or contains cancel, mark as issue
$isIssue = (strpos($curStatus, 'cancel') !== false) || (strpos($curStatus, 'fail') !== false) || (strpos($curStatus, 'return') !== false);

// Friendly labels for known status codes (DB may store 'OFD')
$status_label_map = [
    'pending' => 'Pending',
    'placed' => 'Placed',
    'processing' => 'Packing',
    'packing' => 'Order being pack',
    'packed' => 'Packed',
    'shipped' => 'Handed to courier',
    'in transit' => 'Shipped',
    'out for delivery' => 'Shipped',
    'outfordelivery' => 'Shipped',
    'ofd' => 'Shipped',
    'arriving' => 'Shipped',
    'arrive' => 'Shipped',
    'completed' => 'Delivered',
    'delivered' => 'Delivered',
    'confirmed' => 'Packing',
    'cancelled' => 'Cancelled'
];

// Build HTML fragment using a heredoc for clarity
$events_html = '';
// sort events newest first
usort($events, function($a,$b){
    return strtotime($b['time']) <=> strtotime($a['time']);
});
foreach ($events as $ev) {
    $timeStr = htmlspecialchars($ev['time']);
    $rawStatus = strtolower(trim($ev['status']));
    $title = htmlspecialchars($status_label_map[$rawStatus] ?? $ev['status']);
    $meta = htmlspecialchars($ev['location']);
    $note = htmlspecialchars($ev['note']);
    $events_html .= "<div class=\"event\">\n";
    // removed visual timeline bar/pin per request
    $events_html .= "  <div class=\"event-content\">\n";
    $events_html .= "    <div class=\"event-title\">$title</div>\n";
    $events_html .= "    <div class=\"event-meta\">$timeStr" . (!empty($meta) ? ' • ' . $meta : '') . "</div>\n";
    if (!empty($note)) { $events_html .= "    <div class=\"event-note\">$note</div>\n"; }
    $events_html .= "  </div>\n";
    $events_html .= "</div>\n";
}

$steps_html = '';
foreach ($steps as $i => $label) {
    $active = ($i <= $currentIndex) ? 'true' : 'false';
    $steps_html .= "<div class=\"step\" data-active=\"$active\">";
    $steps_html .= "<div class=\"dot\" aria-hidden=\"true\"></div>";
    $steps_html .= "<div class=\"label\">" . htmlspecialchars($label) . "</div>";
    $steps_html .= "</div>\n";
}


// compute progress percent; if currentIndex is -1, treat as 0%
if ($currentIndex < 0) {
    $pct = 0;
} else {
    $pct = ($currentIndex / max(1, count($steps)-1)) * 100;
}

$lastIndex = count($steps) - 1;
$badge_variant = $isIssue ? 'issue' : (($currentIndex >= $lastIndex) ? '' : 'moving');

 $tracking_no = htmlspecialchars('ORD-' . $order_id);
 $courier = htmlspecialchars($order['courier'] ?? ($order['shipping_courier'] ?? 'N/A'));
 $recipient = htmlspecialchars($order['name'] ?? ($order['recip_name'] ?? 'N/A'));
 $destination = htmlspecialchars($order['address'] ?? 'N/A');

 // sanitized status and rounded progress
 $status_text = htmlspecialchars($status_label_map[strtolower(trim($order['status'] ?? ''))] ?? $order['status'] ?? '');
 $pct_f = round($pct);

// If the order is cancelled, try to fetch the latest cancellation reason
$cancel_reason_html = '';
if (strpos($curStatus, 'cancel') !== false) {
    // First check order_status_history for an admin cancellation (changed_by > 0)
    $hist_cancel_q = $conn->query("SELECT change_reason, changed_by, created_at FROM order_status_history WHERE order_id = '$order_id' AND LOWER(new_status) = 'cancelled' ORDER BY created_at DESC LIMIT 1");
    if ($hist_cancel_q && $hist_cancel_q->num_rows > 0) {
        $hc = $hist_cancel_q->fetch_assoc();
        $changed_by = intval($hc['changed_by'] ?? 0);
        $hc_reason = trim($hc['change_reason'] ?? '');
        if ($changed_by > 0 && $hc_reason !== '') {
            // Admin-provided cancel reason takes precedence
            $cancel_reason_html = htmlspecialchars($hc_reason);
        }
    }
    // If no admin cancel reason found, fall back to user-provided cancellation reason
    if (empty($cancel_reason_html)) {
        $cr_q = $conn->query("SELECT reason, created_at FROM user_order_cancellations WHERE order_id = '$order_id' ORDER BY created_at DESC LIMIT 1");
        if ($cr_q && $cr_q->num_rows > 0) {
            $cr = $cr_q->fetch_assoc();
            $cancel_reason_html = htmlspecialchars(trim($cr['reason'] ?? ''));
        }
    }
}

// Build a small fragment to display the cancel reason beneath the status badge
$cancel_html_fragment = '';
if (!empty($cancel_reason_html)) {
    $cancel_html_fragment = "<div class=\"cancel-reason\" style=\"margin-top:6px;color:#c0392b;font-weight:600;\">Reason: {$cancel_reason_html}</div>";
}

// Show tracking controls only when order has shipped (step index >= 1)
$show_tracking = ($currentIndex >= 1);
$tracking_block = '';
if ($show_tracking) {
    // show copy button inline next to the tracking number
    $tracking_block = "<div class=\"kv-row\"><div class=\"kv-label\">Tracking no.</div><div class=\"kv-value\">{$tracking_no} <button type=\"button\" class=\"btn btn-sm btn-outline-secondary copy-track\" data-track=\"{$tracking_no}\" style=\"margin-left:8px;\">Copy</button></div></div>";
    // open jnt button placed below Destination in its own row
    $open_jnt_row = "<div class=\"kv-row\"><div class=\"kv-label\"></div><div class=\"kv-value\"><button type=\"button\" class=\"btn btn-sm btn-primary open-jnt\" data-track=\"{$tracking_no}\">Open JNT</button></div></div>";
} else {
    $open_jnt_row = '';
}

 $card = <<<HTML
<link rel="stylesheet" href="css/order_track.css">
<div class="ot-track-wrap">
  <div class="ot-panel">
                <div class="ot-header">
            <div>
                                <div class="status-badge" data-variant="{$badge_variant}">{$status_text}</div>
                                {$cancel_html_fragment}
                        </div>
            <div>
                    {$tracking_block}
                <div class="kv-row"><div class="kv-label">Recipient</div><div class="kv-value">{$recipient}</div></div>
                <div class="kv-row"><div class="kv-label">Destination</div><div class="kv-value">{$destination}</div></div>
                    {$open_jnt_row}
            </div>
        </div>

        <div class="stepper" aria-label="Delivery progress">
            <div class="steps">{$steps_html}</div>
            <div class="progressbar" aria-hidden="true">
                <div style="width: {$pct_f}%;"></div>
            </div>
        </div>

        <div class="timeline" id="otTimeline">
            {$events_html}
        </div>

    <div class="ot-close-btn"></div>
    </div>
</div>
HTML;

// If requested via AJAX, return only the fragment
if (isset($_GET['ajax']) && $_GET['ajax'] == '1') {
    echo $card;
    exit;
}

// Otherwise render full page (fallback)
?><!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Track Order #<?php echo htmlspecialchars($order_id); ?></title>
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link rel="stylesheet" href="css/order_track.css">
</head>
<body>
<div class="container pendingbody order-track-card-wrapper">
    <?php echo $card; ?>
    <div class="mt-3">
        <a href="profile.php" class="btn btn-secondary">Back to Orders</a>
    </div>
</div>
</body>
</html>
