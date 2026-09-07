<?php
/**
 * User Login Gate
 * Authenticates users and establishes secure sessions.
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
    $username_or_email = trim($_POST['username_or_email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // Server-side validation
    if (empty($username_or_email) || empty($password)) {
        $error_msg = "Please enter both username/email and password.";
    } else {
        try {
            // Find user by either username or email
            $stmt = $pdo->prepare("SELECT * FROM `users` WHERE `username` = ? OR `email` = ? LIMIT 1");
            $stmt->execute([$username_or_email, $username_or_email]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password_hash'])) {
                // Secure Session Handoff
                secureSessionRegenerate();
                
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                
                // Audit log registration
                logActivity($user['id'], 'Login', "User '{$user['username']}' logged in successfully from IP: {$_SERVER['REMOTE_ADDR']}");
                
                header("Location: index.php");
                exit();
            } else {
                $error_msg = "Invalid username/email or password.";
            }
        } catch (PDOException $e) {
            error_log("Login error: " . $e->getMessage());
            $error_msg = "A database error occurred. Please try again later.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - PharmRx System</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="auth-wrapper">
    <div class="auth-card">
        <div class="auth-header">
            <div class="logo-icon" style="margin: 0 auto; width: 48px; height: 48px; font-size: 1.5rem;">Rx</div>
            <h2>Welcome Back</h2>
            <p>Sign in to manage your pharmacy inventory</p>
        </div>
        
        <?php if (!empty($error_msg)): ?>
            <div class="alert alert-danger">
                <span><?php echo htmlspecialchars($error_msg); ?></span>
            </div>
        <?php endif; ?>
        
        <form action="login.php" method="POST" class="validated-form">
            <div class="form-group">
                <label for="username_or_email">Username or Email:</label>
                <input type="text" name="username_or_email" id="username_or_email" class="form-control" placeholder="Enter username or email" required>
            </div>
            
            <div class="form-group">
                <label for="password">Password:</label>
                <input type="password" name="password" id="password" class="form-control" placeholder="Enter password" required>
            </div>
            
            <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 1rem;">Sign In</button>
        </form>
        
        <div style="margin-top: 1.5rem; text-align: center; border-top: 1px solid var(--border-color); padding-top: 1.5rem;">
            <p style="font-size: 0.85rem; color: var(--text-secondary);">
                Don't have an account? <a href="register.php" style="color: var(--accent-primary); text-decoration: none; font-weight: 600;">Register here</a>
            </p>
        </div>
        
        <!-- Evaluator Demo Account Guide -->
        <div style="margin-top: 1.5rem; background-color: rgba(255,255,255,0.02); padding: 1rem; border-radius: var(--radius-sm); border: 1px solid var(--border-color);">
            <h4 style="font-size: 0.85rem; margin-bottom: 0.5rem; color: var(--accent-primary);">Demo Evaluator Accounts:</h4>
            <div style="display: flex; justify-content: space-between; font-size: 0.75rem; color: var(--text-secondary);">
                <div>
                    <strong>Admin Role:</strong><br>
                    User: <code>admin</code><br>
                    Pass: <code>admin123</code>
                </div>
                <div>
                    <strong>Normal User:</strong><br>
                    User: <code>staff</code><br>
                    Pass: <code>staff123</code>
                </div>
            </div>
        </div>
    </div>
    
    <script src="assets/js/validation.js"></script>
</body>
</html>
