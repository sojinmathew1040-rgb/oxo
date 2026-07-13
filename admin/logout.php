<?php
/**
 * Sign Out Handler for OXO Admin Panel
 */

require_once __DIR__ . '/auth.php';

// Clear session variables
$_SESSION = [];

// Destroy the session
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}
session_destroy();

// Redirect back to login page
header("Location: login.php");
exit;
