<?php
if (session_status() == PHP_SESSION_NONE) {
	session_start();
}
// Check admin session, not user session
if (!isset($_SESSION['admin_auth']) || $_SESSION['admin_auth'] != 1) {
	header("Location: a_login.php");
	exit;
}
include 'lib/connection.php';
$sql = "SELECT * FROM orders where status='pending'";
$result = $conn -> query ($sql);
?>

<!DOCTYPE html>
<html>
<head>
	<title>Admin</title>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<!--css link-->
	<link
	  rel="stylesheet" href="../css/bootstrap.min.css"
	/>
	<link rel="stylesheet" href="css/style.css">
	<!-- <link rel="stylesheet" href="css/media.css"> -->
</head>
<body>
	<section class="header" id="header">
		<i class="fas fa-bars fixed" onclick="openside()"></i>
		<div class="line-fixed">Admin Panel</div>
		<?php
		$c=0;
		  if (mysqli_num_rows($result) > 0) {
			// output data of each row
			while($row = mysqli_fetch_assoc($result)) {
				$c=$c+1;
			}
		}
			  ?>
		<span>(New Orders)</span>
		<span style="    border-radius: 20px;
	
	background-color: red;
	color: white;
	padding: 5px;"><?php echo $c ;?></span>
		<a href="a_logout.php">(logout)</a>
	</section>

	<div class="sidenav" onmouseleave="closeside()" id="sidenav"  >
		<ul class="navbar-nav">
		   <li class="nav-item">
				<a class="nav-link d" href="home.php">Dashboard</a>
			</li>
			<li class="nav-item">
				<a class="nav-link po" href="pending_orders.php">Order Status</a>
			</li>
			<li class="nav-item">
				<a class="nav-link ap" href="add_product.php">Add Product</a>
			</li>
			<li class="nav-item">
				<a class="nav-link vp" href="all_product.php">All Product</a>
			</li>
			
			<li class="nav-item">
				<a class="nav-link ao" href="all_orders.php">Delivered Order</a>
			</li>
			<li class="nav-item">
				<a class="nav-link u" href="users.php">Users</a>
			</li>
			
		</ul>
	</div>
	<?php

?>
<!--js link-->
<script src="../js/jquery-3.6.0.min.js"></script>
<script src="../js/popper.min.js"></script>
<script src="../js/bootstrap.min.js"></script>
<script src="js/script.js"></script>
<script src="../js/3b83a3096d.js"></script>

</body>
</html>