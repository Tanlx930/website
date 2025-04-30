<?php
// Start the session
session_start();

// Unset all session variables
$_SESSION = [];

// Destroy the session
session_destroy();

// Clear the username cookie if it exists
if (isset($_COOKIE['username'])) {
    setcookie('username', '', time() - 3600, "/"); // Set the cookie expiration time to the past
}

// Redirect to the login page
header("Location: login.php");
exit();
?>
