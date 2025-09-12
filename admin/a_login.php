<?php 
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// If the admin is already logged in, redirect to the home page
if (isset($_SESSION['admin_auth']) && $_SESSION['admin_auth'] == 1) {
    header("location:home.php");
    exit();
}

include "lib/connection.php";

// Check if form is submitted
if (isset($_POST['submit'])) {
    $admin_id = $_POST['email'];
    $admin_pass = $_POST['password'];

    // Server-side validation
    if (empty($admin_id) || empty($admin_pass)) {
        if (session_status() == PHP_SESSION_NONE) session_start();
        $_SESSION['error_message'] = 'Please enter both username and password.';
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit();
    } else {
        // Prepared statement to prevent SQL injection
        $loginquery = "SELECT * FROM admin WHERE userid = ? AND pass = ?";
        $stmt = $conn->prepare($loginquery);
        $stmt->bind_param('ss', $admin_id, $admin_pass);
        $stmt->execute();
        $loginres = $stmt->get_result();

        if ($loginres->num_rows > 0) {
            $_SESSION['admin_auth'] = 1;
            // store the admin userid string so other pages can lookup ad_id
            $_SESSION['admin_userid'] = $admin_id;
            // Do NOT set user session variables here
            header("location:home.php");
            exit();
        } else {
            if (session_status() == PHP_SESSION_NONE) session_start();
            $_SESSION['error_message'] = 'Invalid username or password. Please try again.';
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login</title>
    <link href="../css/bootstrap.min.css" rel="stylesheet" >
    <link rel="stylesheet" href="../css/style.css" />
    <script>
        // Client-side validation: check if both fields are filled before submission
        function validateForm() {
            var email = document.forms["loginForm"]["email"].value;
            var password = document.forms["loginForm"]["password"].value;
            if (email == "" || password == "") {
                    if (typeof showFlash === 'function') {
                        showFlash('danger', 'Both username and password are required.', 4000);
                    } else {
                        alert("Both username and password are required.");
                    }
                    return false;
                }
            return true;
        }
    </script>
</head>
<body>
<div class="container">
    <!-- hidden back-to-user-login quick-link (bottom-left) -->
    <a href="../login.php" class="secret-back-btn" aria-label="Back to user login" title="Back to user login">User</a>
    <div class="d-flex justify-content-center">
        <div class="card">
            <div class="card-header">
                <h3>Sign In</h3>
            </div>
            <div class="card-body">
                <?php include __DIR__ . '/../includes/flash.php'; ?>
                <!-- Form with client-side validation -->
                <form name="loginForm" action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post" onsubmit="return validateForm()">
                    <div class="input-group form-group">
                        <input type="text" class="form-control cp-form-control" placeholder="Admin Name" name="email" required>
                    </div>
                    <div class="input-group form-group">
                        <input type="password" class="form-control cp-form-control" placeholder="Password" name="password" required>
                    </div>
                    <div class="form-group">
                        <input type="submit" value="Login" class="btn btn-primary" name="submit">
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="../admin/js/jquery-3.6.0.min.js"></script>
<script src="../admin/js/popper.min.js"></script>
<script src="../admin/js/bootstrap.min.js"></script>

</body>
</html>
