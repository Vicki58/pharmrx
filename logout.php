<?php
/**
 * User Logout Page
 * Destroys sessions and registers activity logs.
 */
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/logger.php';

if (isLoggedIn()) {
    $user_id = $_SESSION['user_id'];
    $username = $_SESSION['username'];
    
    // Log Logout Action before destroying session
    logActivity($user_id, 'Logout', "User '{$username}' logged out successfully.");
    
    // Unset all session variables
    $_SESSION = [];
    
    // Destroy the session cookie if set
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    // Destroy the session
    session_destroy();
}

// Redirect to login page
header("Location: login.php");
exit();
?>
