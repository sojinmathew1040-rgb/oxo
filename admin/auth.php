<?php
/**
 * Authentication Helper for OXO Admin Panel
 * Handles secure session starts and page access verification.
 */

if (session_status() === PHP_SESSION_NONE) {
    // Enable secure session cookie settings
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    
    session_start();
}

/**
 * Require admin authentication to view the page.
 * If not logged in, redirects to the login screen.
 */
function require_admin_login() {
    if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
        header("Location: login.php");
        exit;
    }
}

/**
 * Redirects the user to the admin dashboard if they are already logged in.
 * Typically used on the login page.
 */
function redirect_if_logged_in() {
    if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
        header("Location: index.php");
        exit;
    }
}
