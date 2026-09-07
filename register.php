<?php
/**
 * User Registration Gate
 * Registers new system operators.
 */
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/logger.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header("Location: index.php");
    exit();
}

$error_msg = "";
$success_msg = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? 'Normal';
    
    // Server-side validation
    if (empty($username) || empty($email) || empty($password)) {
        $error_msg = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_msg = "Please enter a valid email address.";
    } elseif (!in_array($role, ['Admin', 'Normal'])) {
        $error_msg = "Invalid role selection.";
    } else {
        try {
            // Check for duplicate username or email
            $dup_stmt = $pdo->prepare("SELECT `username`, `email` FROM `users` WHERE `username` = ? OR `email` = ?");
            $dup_stmt->execute([$username, $email]);
            $duplicate = $dup_stmt->fetch();
            
            if ($duplicate) {
                if ($duplicate['username'] === $username) {
                    $error_msg = "Username already exists. Please choose another.";
                } else {
                    $error_msg = "Email address is already registered.";
                }
            } else {
                // Securely hash password
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                
                // Insert new user
                $ins_stmt = $pdo->prepare("INSERT INTO `users` (`username`, `email`, `password_hash`, `role`) VALUES (?, ?, ?, ?)");
                $ins_stmt->execute([$username, $email, $password_hash, $role]);
                
                $new_id = $pdo->lastInsertId();
                
                // Log action
                logActivity($new_id, 'Login', "User Account '{$username}' ({$role}) registered successfully.");
                
                $success_msg = "Registration successful! You can now log in.";
            }
        } catch (PDOException $e) {
            error_log("Registration error: " . $e->getMessage());
            $error_msg = "A database error occurred. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - PharmRx System</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="auth-wrapper">
    <div class="auth-card">
        <div class="auth-header">
            <div class="logo-icon" style="margin: 0 auto; width: 48px; height: 48px; font-size: 1.5rem;">Rx</div>
            <h2>Create Account</h2>
            <p>Register a new operator profile</p>
        </div>
        
        <?php if (!empty($error_msg)): ?>
            <div class="alert alert-danger">
                <span><?php echo htmlspecialchars($error_msg); ?></span>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($success_msg)): ?>
            <div class="alert alert-success">
                <span><?php echo htmlspecialchars($success_msg); ?></span>
            </div>
        <?php endif; ?>
        
        <form action="register.php" method="POST" class="validated-form">
            <div class="form-group">
                <label for="username">Username:</label>
                <input type="text" name="username" id="username" class="form-control" placeholder="Choose a username" required>
            </div>
            
            <div class="form-group">
                <label for="email">Email Address:</label>
                <input type="email" name="email" id="email" class="form-control" placeholder="Enter email address" required>
            </div>
            
            <div class="form-group">
                <label for="password">Password:</label>
                <input type="password" name="password" id="password" class="form-control" placeholder="Choose a password" required>
            </div>
            
            <div class="form-group">
                <label for="role">User Role:</label>
                <select name="role" id="role" class="form-control" required>
                    <option value="Normal">Normal User (View-only medicine permissions)</option>
                    <option value="Admin">Administrator (Add, Edit, Delete permissions)</option>
                </select>
            </div>
            
            <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 1rem;">Register Account</button>
        </form>
        
        <div style="margin-top: 1.5rem; text-align: center; border-top: 1px solid var(--border-color); padding-top: 1.5rem;">
            <p style="font-size: 0.85rem; color: var(--text-secondary);">
                Already have an account? <a href="login.php" style="color: var(--accent-primary); text-decoration: none; font-weight: 600;">Sign in here</a>
            </p>
        </div>
    </div>
    
    <script src="assets/js/validation.js"></script>
</body>
</html>
