<?php
ob_start();
session_start();
include 'header.php';
include 'lib/connection.php';

// Require user to be logged in
if (!isset($_SESSION['auth']) || $_SESSION['auth'] != 1) {
    header('Location: login.php');
    exit;
}

$userId = intval($_SESSION['userid']);

// Handle POST update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_address_btn'])) {
    $street = trim($_POST['street'] ?? '');
    $zone = trim($_POST['zone'] ?? '');
    $barangay = trim($_POST['barangay'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $province = trim($_POST['province'] ?? '');
    $phone = trim($_POST['phone'] ?? '');

    // Basic validation
    $errors = [];
    if ($phone === '') {
        $errors[] = 'Phone number is required.';
    }
    if (empty($street) && empty($zone) && empty($barangay) && empty($city) && empty($province)) {
        $errors[] = 'Please provide at least part of your address.';
    }

    if (count($errors) === 0) {
        $streetEsc = mysqli_real_escape_string($conn, $street);
        $zoneEsc = mysqli_real_escape_string($conn, $zone);
        $barangayEsc = mysqli_real_escape_string($conn, $barangay);
        $cityEsc = mysqli_real_escape_string($conn, $city);
        $provinceEsc = mysqli_real_escape_string($conn, $province);
        $phoneEsc = mysqli_real_escape_string($conn, $phone);

        $sql = "UPDATE users SET street = '{$streetEsc}', zone = '{$zoneEsc}', barangay = '{$barangayEsc}', city = '{$cityEsc}', province = '{$provinceEsc}', phone = '{$phoneEsc}' WHERE u_id = '{$userId}'";
        $res = mysqli_query($conn, $sql);
        if ($res) {
            $_SESSION['success_message'] = 'Shipping address updated successfully.';
            header('Location: profile.php');
            exit;
        } else {
            $_SESSION['error_message'] = 'Failed to update address. Please try again.';
        }
    } else {
        // Join errors and show via session so includes/flash.php can display
        $_SESSION['error_message'] = implode(' ', $errors);
    }
    // After handling POST, redirect to avoid form resubmission (if not redirected already)
    header('Location: edit_shipping_address.php');
    exit;
}

// Fetch existing user address
$user_q = mysqli_query($conn, "SELECT f_name, l_name, street, zone, barangay, city, province, phone FROM users WHERE u_id = '{$userId}' LIMIT 1");
$user = mysqli_fetch_assoc($user_q) ?: [];

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Edit Shipping Address</title>
  <link rel="stylesheet" href="css/css.css" type="text/css">
  <link rel="stylesheet" href="css/bootstrap.min.css" type="text/css">
</head>
<body>

<div class="container mt-4">
  <h4>Edit Shipping Address</h4>
  <?php include __DIR__ . '/includes/flash.php'; ?>
  <form method="post" class="border p-3 rounded" novalidate>
    <div class="form-row">
      <div class="form-group">
        <label for="street">Street / House No.</label>
        <input type="text" name="street" id="street" class="form-control" value="<?php echo htmlspecialchars($user['street'] ?? ''); ?>">
      </div>
      <div class="form-group">
        <label for="zone">Zone / Subdivision</label>
        <input type="text" name="zone" id="zone" class="form-control" value="<?php echo htmlspecialchars($user['zone'] ?? ''); ?>">
      </div>
      <div class="form-group">
        <label for="barangay">Barangay</label>
        <input type="text" name="barangay" id="barangay" class="form-control" value="<?php echo htmlspecialchars($user['barangay'] ?? ''); ?>">
      </div>
      <div class="form-group">
        <label for="city">City / Municipality</label>
        <input type="text" name="city" id="city" class="form-control" value="<?php echo htmlspecialchars($user['city'] ?? ''); ?>">
      </div>
      <div class="form-group">
        <label for="province">Province</label>
        <input type="text" name="province" id="province" class="form-control" value="<?php echo htmlspecialchars($user['province'] ?? ''); ?>">
      </div>
      <div class="form-group">
        <label for="phone">Phone Number</label>
        <input type="text" name="phone" id="phone" class="form-control" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" required>
      </div>
    </div>
    <div class="mt-3">
      <button type="submit" name="update_address_btn" class="btn btn-primary">Save Address</button>
      <a href="profile.php" class="btn btn-secondary">Cancel</a>
    </div>
  </form>
</div>

<?php include 'footer.php'; ?>
</body>
</html>
