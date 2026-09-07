<?php
/**
 * Database Connection Configuration
 * Uses PDO for secure SQL execution via prepared statements.
 */

define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') !== false ? getenv('DB_PASS') : '');
define('DB_NAME', getenv('DB_NAME') ?: 'pharmacy_db');
define('DB_PORT', getenv('DB_PORT') ?: '3306');

try {
    // Construct DSN
    $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    
    // Set PDO options for security and error handling
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Enable exception handling
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Fetch associative arrays
        PDO::ATTR_EMULATE_PREPARES   => false,                  // Disable emulation to enforce actual prepared statements in MySQL
    ];
    
    // Establish connection
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    
} catch (PDOException $e) {
    // Fail gracefully with a generic message for security, preventing database detail exposure
    error_log("Database connection failure: " . $e->getMessage());
    die("A secure database connection could not be established. Please ensure the database server is running and configured correctly.");
}
?>
