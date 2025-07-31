<?php
session_start();
// If admin session, unset only admin variables and redirect to admin login
if (isset($_SESSION['admin_auth']) || isset($_SESSION['admin_id'])) {
    unset($_SESSION['admin_auth']);
    unset($_SESSION['admin_id']);
    header("Location: a_login.php");
    exit;
}
// Otherwise, perform full logout for user
session_unset(); // Clear session variables
session_destroy(); // Destroy the session
header("Location: a_login.php");
exit;
?>
