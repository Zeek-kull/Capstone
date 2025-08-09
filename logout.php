<?php
session_start();
// Only unset user session variables
unset($_SESSION['auth']);
unset($_SESSION['userid']);
unset($_SESSION['username']);
header("Location: login.php");
exit;
?>
