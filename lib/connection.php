<?php 

$host = "localhost";
$user = "root";
$pass = "";
$db   ="cse411project";

$db_host = getenv('DB_HOST') ?: '127.0.0.1';
$db_port = getenv('DB_PORT') ?: '3306';
$db_name = getenv('DB_NAME') ?: 'capstone';
$db_user = getenv('DB_USER') ?: 'root';
$db_pass = getenv('DB_PASS') ?: '';

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name, (int)$db_port);
if ($conn->connect_errno) {
    error_log("DB connect failed: " . $conn->connect_error);
    die('Database connection error.');
}
$conn->set_charset('utf8mb4');

if ($conn -> connect_error) 
{
	die($conn -> error);
}
else
{
	// echo "database connected";
}

?>