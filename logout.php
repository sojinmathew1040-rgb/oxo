<?php
require_once 'includes/db.php';

// Unset all session variables
$_SESSION = array();

// Destroy the session
session_destroy();

// Redirect to the homepage
header("Location: index.php");
exit;
