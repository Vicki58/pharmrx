<?php
/**
 * Authentication and Session Management Configuration
 * Implements session security measures and role checking.
 */

// Start session if not already active, with security flags
if (session_status() === PHP_SESSION_NONE && php_sapi_name() !== 'cli') {
    if (!headers_sent()) {
        ini_set('session.cookie_httponly', 1); // Prevent Javascript access to session cookie
        ini_set('session.use_only_cookies', 1); // Prevent session ID passing via URL
        
        // If using HTTPS, set secure cookie flag (optional but good practice)
        if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
            ini_set('session.cookie_secure', 1);
        }
        
        session_start();
    }
}

/**
 * Checks if a user is currently logged in.
 * @return bool
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['username']);
}

/**
 * Redirects the user to the login page if they are not authenticated.
 */
function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: login.php");
        exit();
    }
}

/**
 * Checks if the logged-in user has the Administrator role.
 * @return bool
 */
function isAdmin() {
    return isLoggedIn() && isset($_SESSION['role']) && $_SESSION['role'] === 'Admin';
}

/**
 * Enforces Administrator role restriction, otherwise redirects or displays unauthorized error.
 */
function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        // Log unauthorized attempt if logging helper is loaded (optional fallback)
        header("HTTP/1.1 403 Forbidden");
        echo "<!DOCTYPE html>
        <html>
        <head>
            <title>Access Denied</title>
            <style>
                body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #0f172a; color: #f1f5f9; display: flex; align-items: center; justify-content: center; height: 100vh; margin: 0; }
                .card { background: #1e293b; padding: 2rem; border-radius: 12px; text-align: center; max-width: 400px; box-shadow: 0 4px 20px rgba(0,0,0,0.3); border: 1px solid #334155; }
                h1 { color: #f43f5e; margin-top: 0; }
                a { color: #38bdf8; text-decoration: none; font-weight: bold; }
                a:hover { text-decoration: underline; }
            </style>
        </head>
        <body>
            <div class='card'>
                <h1>Access Denied</h1>
                <p>You do not have the required permissions to view this page. This area is restricted to Administrators only.</p>
                <p><a href='index.php'>Return to Dashboard</a></p>
            </div>
        </body>
        </html>";
        exit();
    }
}

/**
 * Regenerates the session ID to mitigate session fixation attacks.
 * Should be called upon login and password change.
 */
function secureSessionRegenerate() {
    session_regenerate_id(true);
}
?>
