<?php

// lib/connection.php - improved database connection
// Behavior:
// - Reads DB credentials from environment variables if available (for security/config flexibility)
// - Enables mysqli exceptions for clearer error handling
// - Sets connection charset to utf8mb4
// - Logs connection errors to a file instead of echoing them
// - Exposes $conn for backward compatibility with existing code

// Default values (fall back to these when env vars are not set)
$host = getenv('DB_HOST') !== false ? getenv('DB_HOST') : 'localhost';
$user = getenv('DB_USER') !== false ? getenv('DB_USER') : 'root';
$pass = getenv('DB_PASS') !== false ? getenv('DB_PASS') : '';
$db   = getenv('DB_NAME') !== false ? getenv('DB_NAME') : 'cse411project';

// Optional: port and socket
$port = getenv('DB_PORT') !== false ? (int)getenv('DB_PORT') : null;
$socket = getenv('DB_SOCKET') !== false ? getenv('DB_SOCKET') : null;

// Enable mysqli exceptions so errors can be caught with try/catch
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$conn = null;
try {
	if ($port !== null || $socket !== null) {
		// Use the procedural constructor signature that accepts port and socket
		$conn = new mysqli($host, $user, $pass, $db, $port ?? 3306, $socket);
	} else {
		$conn = new mysqli($host, $user, $pass, $db);
	}

	// Ensure consistent UTF-8 handling
	$conn->set_charset('utf8mb4');

	// Connection successful - do not echo in production
	// Uncomment for quick local debug: // error_log('DB connected: ' . $host);

} catch (Exception $e) {
	// Log the error to a file (make sure writable by PHP)
	$logMessage = sprintf("[%s] DB connection error: %s in %s on line %s\n", date('c'), $e->getMessage(), $e->getFile(), $e->getLine());
	error_log($logMessage, 3, __DIR__ . '/db_errors.log');

	// For CLI or debugging, you may want to show the error. In web production, avoid revealing details.
	if (php_sapi_name() === 'cli') {
		fwrite(STDERR, "Database connection failed. Check lib/db_errors.log for details.\n");
	}

	// Stop execution to prevent further errors when DB is required
	// If your app can continue without DB, change this to set $conn = null and handle checks elsewhere.
	exit('Database connection failed. Please check the application logs.');
}

?>