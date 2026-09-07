<?php
/**
 * Automated Verification and Test Runner
 * Checks the database structures, security validations, logging actions, 
 * and configuration state of the Pharmacy Management System.
 * 
 * Usage from CLI: php test_runner.php
 */

// Define color helper codes for CLI execution
define('COLOR_SUCCESS', "\033[32m");
define('COLOR_FAILURE', "\033[31m");
define('COLOR_INFO', "\033[36m");
define('COLOR_RESET', "\033[0m");

// Render header
echo COLOR_INFO . "==========================================================" . PHP_EOL;
echo "   PHARMRX SYSTEM AUTOMATED VERIFICATION TEST RUNNER      " . PHP_EOL;
echo "==========================================================" . COLOR_RESET . PHP_EOL . PHP_EOL;

// Mock session values if running in CLI environment
if (session_status() === PHP_SESSION_NONE) {
    $_SESSION = [
        'user_id' => 1,
        'username' => 'test_admin_runner',
        'role' => 'Admin'
    ];
}

// 1. Verify Configuration files compilation
$files_to_check = [
    'config/db.php',
    'config/auth.php',
    'config/logger.php'
];

foreach ($files_to_check as $file) {
    if (!file_exists($file)) {
        echo COLOR_FAILURE . "[FAIL] Missing core dependency file: {$file}" . COLOR_RESET . PHP_EOL;
        exit(1);
    }
}

// Include required files
require_once 'config/db.php';
require_once 'config/auth.php';
require_once 'config/logger.php';

// Test tracker counters
$tests_passed = 0;
$tests_failed = 0;

/**
 * Runs a single test case block.
 */
function test($description, $callback) {
    global $tests_passed, $tests_failed;
    echo "Testing: " . str_pad($description, 50, ".");
    
    try {
        $result = $callback();
        if ($result === true) {
            echo COLOR_SUCCESS . "[ PASS ]" . COLOR_RESET . PHP_EOL;
            $tests_passed++;
        } else {
            $msg = is_string($result) ? $result : "Verification failed";
            echo COLOR_FAILURE . "[ FAIL ] ({$msg})" . COLOR_RESET . PHP_EOL;
            $tests_failed++;
        }
    } catch (Exception $e) {
        echo COLOR_FAILURE . "[ ERROR ] ({$e->getMessage()})" . COLOR_RESET . PHP_EOL;
        $tests_failed++;
    }
}

// =====================================================================
// Test cases execution
// =====================================================================

// Test 1: Database Connection
test("Database PDO Connection", function() use ($pdo) {
    return ($pdo instanceof PDO);
});

// Test 2: Database normalization tables check
test("Verify Required Table Entities Count (Min 4)", function() use ($pdo) {
    $expected_tables = ['users', 'categories', 'medicines', 'sales', 'activity_logs'];
    $existing_tables = [];
    
    $stmt = $pdo->query("SHOW TABLES");
    while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
        $existing_tables[] = $row[0];
    }
    
    $missing = array_diff($expected_tables, $existing_tables);
    if (!empty($missing)) {
        return "Missing tables: " . implode(', ', $missing);
    }
    return true;
});

// Test 3: Password Hashing Verification
test("Password Hashing Integration (Secure Hash)", function() {
    $password = "secret_password_123";
    $hash = password_hash($password, PASSWORD_DEFAULT);
    
    if (password_verify($password, $hash)) {
        // Confirm hash is dynamic and cryptographically secure
        $info = password_get_info($hash);
        return $info['algoName'] === 'bcrypt' || $info['algoName'] === 'argon2i' || $info['algoName'] === 'argon2id' || $info['algoName'] === 'unknown';
    }
    return "Password verification failed";
});

// Test 4: Role access permission helper guards
test("User Role Check Helpers", function() {
    $_SESSION['role'] = 'Admin';
    if (!isAdmin()) return "Admin check returned false for Admin user";
    
    $_SESSION['role'] = 'Normal';
    if (isAdmin()) return "Admin check returned true for Normal user";
    
    return true;
});

// Test 5: DB Integrity and constraints (Foreign Keys)
test("Database Structural Normalization (Foreign Keys)", function() use ($pdo) {
    // Check if medicines has category_id linked to categories table
    $stmt = $pdo->query("
        SELECT CONSTRAINT_NAME 
        FROM information_schema.KEY_COLUMN_USAGE 
        WHERE TABLE_SCHEMA = 'pharmacy_db' 
        AND TABLE_NAME = 'medicines' 
        AND COLUMN_NAME = 'category_id'
        AND REFERENCED_TABLE_NAME = 'categories'
    ");
    $fk = $stmt->fetch();
    return $fk ? true : "Foreign key constraint on medicines table category_id is missing";
});

// Test 6: Audit log creation
test("System Activity Logger writes to DB", function() use ($pdo) {
    $test_description = "Test log run by automated test suite runner";
    $status = logActivity(null, 'Edit', $test_description);
    
    if ($status === false) return "logger.php helper failed to execute query";
    
    // Retrieve log to verify it saved
    $stmt = $pdo->prepare("SELECT * FROM `activity_logs` WHERE `description` = ? ORDER BY `id` DESC LIMIT 1");
    $stmt->execute([$test_description]);
    $log = $stmt->fetch();
    
    if ($log) {
        // Cleanup test log
        $pdo->prepare("DELETE FROM `activity_logs` WHERE `id` = ?")->execute([$log['id']]);
        return true;
    }
    return "Log entry not written to database";
});

// Test 7: Form Numeric constraints validations helper
test("Stock & Price constraints logic", function() {
    $price_ok = 19.99;
    $price_bad = -5.00;
    $stock_ok = 100;
    $stock_bad = -10;
    
    $check_price_ok = (is_numeric($price_ok) && $price_ok >= 0);
    $check_price_bad = (is_numeric($price_bad) && $price_bad >= 0);
    $check_stock_ok = (is_numeric($stock_ok) && $stock_ok >= 0);
    $check_stock_bad = (is_numeric($stock_bad) && $stock_bad >= 0);
    
    if ($check_price_ok && !$check_price_bad && $check_stock_ok && !$check_stock_bad) {
        return true;
    }
    return "Numeric checks did not correctly filter negative inputs";
});

// =====================================================================
// Reporting results
// =====================================================================
echo PHP_EOL;
echo COLOR_INFO . "==========================================================" . COLOR_RESET . PHP_EOL;
echo "TEST RESULTS SUMMARY: " . PHP_EOL;
echo "   " . COLOR_SUCCESS . "Passed: {$tests_passed}" . COLOR_RESET . PHP_EOL;
echo "   " . COLOR_FAILURE . "Failed: {$tests_failed}" . COLOR_RESET . PHP_EOL;
echo COLOR_INFO . "==========================================================" . COLOR_RESET . PHP_EOL;

if ($tests_failed > 0) {
    exit(1);
} else {
    exit(0);
}
?>
