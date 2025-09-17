<?php

// admin/lib/connection.php - improved database connection for admin area
// Mirrors project's lib/connection.php: supports .env via phpdotenv, lib/db_config.php, mysqli exceptions, utf8mb4, and error logging.

// Try to load project Composer autoload (optional) so phpdotenv is available if installed
$projectRoot = dirname(__DIR__, 2); // admin/lib -> project root
$autoload = $projectRoot . '/vendor/autoload.php';
if (file_exists($autoload)) {
	require_once $autoload;

	if (class_exists('Dotenv\\Dotenv') || class_exists('\Dotenv\\Dotenv')) {
		try {
			// Use fully-qualified name to be safe in different environments
			$dotenvClass = class_exists('Dotenv\\Dotenv') ? 'Dotenv\\Dotenv' : '\\Dotenv\\Dotenv';
			$dotenv = $dotenvClass::createImmutable($projectRoot);
			$dotenv->safeLoad();
		} catch (Exception $e) {
			// ignore dotenv errors; fall back to other methods
		}
	}
}

// Next, try to load lib/db_config.php if present (simple no-dependency approach)
$dbConfigFile = $projectRoot . '/lib/db_config.php';
if (file_exists($dbConfigFile)) {
	$cfg = include $dbConfigFile;
	if (is_array($cfg)) {
		foreach ($cfg as $k => $v) {
			if (getenv($k) === false) {
				putenv("$k=$v");
				$_ENV[$k] = $v;
				$_SERVER[$k] = $v;
			}
		}
	}
}

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
		$conn = new mysqli($host, $user, $pass, $db, $port ?? 3306, $socket);
	} else {
		$conn = new mysqli($host, $user, $pass, $db);
	}

	// Ensure consistent UTF-8 handling
	$conn->set_charset('utf8mb4');

} catch (Exception $e) {
	$logMessage = sprintf("[%s] Admin DB connection error: %s in %s on line %s\n", date('c'), $e->getMessage(), $e->getFile(), $e->getLine());
	error_log($logMessage, 3, $projectRoot . '/lib/db_errors.log');

	if (php_sapi_name() === 'cli') {
		fwrite(STDERR, "Admin database connection failed. Check lib/db_errors.log for details.\n");
	}

	exit('Database connection failed. Please check the application logs.');
}

?>