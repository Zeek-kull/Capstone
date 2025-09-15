<?php
include 'lib/connection.php';
// set_lensid.php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['lensid'])) {
    $lensid = trim($_POST['lensid']);
    $envPath = __DIR__ . '/snap-camerakit-demo/.env';
    if (file_exists($envPath)) {
        $envLines = file($envPath, FILE_IGNORE_NEW_LINES);
        $found = false;
        foreach ($envLines as $i => $line) {
            if (strpos($line, 'VITE_LENS_ID=') === 0) {
                $envLines[$i] = 'VITE_LENS_ID=' . $lensid;
                $found = true;
                break;
            }
        }
        if (!$found) {
            $envLines[] = 'VITE_LENS_ID=' . $lensid;
        }
        file_put_contents($envPath, implode("\r\n", $envLines) . "\r\n");
    }
    // Redirect to AR try-on page (use redirect param if provided)
    $redirect = isset($_POST['redirect']) ? $_POST['redirect'] : 'snap-camerakit-demo/index.html';
    header('Location: ' . $redirect);
    exit();
}
header('Location: product.php');
exit();
