<?php
session_start();

// Vercel session configuration
ini_set('session.save_path', '/tmp');

// Store user info for logging
$username = $_SESSION['user'] ?? 'unknown';

// Unset all session variables
$_SESSION = array();

// Delete the session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destroy the session
session_destroy();

// Clear any cached credentials
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

// Redirect to login page
header("Location: /api/login.php");
exit();
?>
