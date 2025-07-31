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
$sql = "SELECT * FROM orders";
$result = $conn->query($sql);

if (isset($_POST['update_update_btn'])) {
    $update_value = $_POST['update_status'];
    $update_id = $_POST['update_id'];
    $update_query = mysqli_query($conn, "UPDATE `orders` SET status = '$update_value' WHERE id = '$update_id'");
    if ($update_query) {
        header('location:pending_orders.php');
    }
}

if (isset($_GET['remove'])) {
    $remove_id = $_GET['remove'];
    mysqli_query($conn, "DELETE FROM `orders` WHERE id = '$remove_id'");
    header('location:pending_orders.php');
}
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
                <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
                  <input type="hidden" name="update_id" value="<?php echo $row['id']; ?>">
                  <select name="update_status" class="form-control">
                    <option selected disabled><?php echo $row['status']; ?></option>
                    <?php
                    // Add all status options except the current one
                    $statuses = ["Pending", "Confirmed", "Cancel", "Delivered"];
                    foreach ($statuses as $status) {
                        if ($status !== $row['status']) {
                            echo "<option value=\"$status\">$status</option>";
                        }
                    }
                    ?>
                  </select>
                  <input type="submit" value="Update" name="update_update_btn" class="btn btn-primary btn-sm">
                </form>
              </td>
              <td><a href="pending_orders.php?remove=<?php echo $row['id']; ?>" class="btn btn-danger btn-sm">Remove</a></td>
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
