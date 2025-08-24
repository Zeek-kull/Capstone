<?php
session_start();
unset($_SESSION['auth'], $_SESSION['userid'], $_SESSION['username']);
header("Location: login.php");
exit;
?>
