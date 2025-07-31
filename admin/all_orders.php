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

// Query to select delivered orders along with the payment method and order date
$sql = "SELECT * FROM orders WHERE status='delivered'";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delivered Orders</title>
    <link rel="stylesheet" href="css/pending_orders.css">
</head>

<body>

<div class="container pendingbody">
    <h5>All Delivered Orders</h5>
    <table class="table">
        <thead>
            <tr>
                <th scope="col">Name</th>
                <th scope="col">Address</th>
                <th scope="col">Phone</th>
                <th scope="col">Total Product</th>
                <th scope="col">Total Price</th>
                <th scope="col">Payment Method</th>
                <th scope="col">Date</th>
                <th scope="col">Status</th>
            </tr>
        </thead>
        <tbody>
        <?php
        if (mysqli_num_rows($result) > 0) {
            // output data of each row
            while ($row = mysqli_fetch_assoc($result)) {
                ?>
                <tr>
                    <td><?php echo $row["name"]; ?></td>
                    <td><?php echo $row["address"]; ?></td>
                    <td><?php echo $row["phone"]; ?></td>
                    <td><?php echo $row["totalproduct"]; ?></td>
                    <td><?php echo $row["totalprice"]; ?></td>
                    <td><?php echo $row["payment_method"]; ?></td>
                    <td>
                        <?php
                        // Use 'created_at' column for the date
                        if (!empty($row["created_at"])) {
                            $date = new DateTime($row["created_at"]);
                            $date->setTimezone(new DateTimeZone('Asia/Manila'));
                            echo $date->format("F j, Y, g:i A");
                        } else {
                            echo "Date not available";
                        }
                        ?>
                    </td>
                    <td><?php echo $row["status"]; ?></td>
                </tr>
                <?php
            }
        } else {
            echo "<tr><td colspan='8'>No delivered orders found.</td></tr>";
        }
        ?>
        </tbody>
    </table>
</div>

</body>
</html>
