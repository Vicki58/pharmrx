<?php
/**
 * Change Password Page
 * Allows authenticated users to safely update their credentials.
 */
$pageTitle = "Change Password";
include_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/logger.php';

$error_msg = "";
$success_msg = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Server-side validation
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error_msg = "All fields are required.";
    } elseif ($new_password !== $confirm_password) {
        $error_msg = "New passwords do not match.";
    } elseif (strlen($new_password) < 6) {
        $error_msg = "New password must be at least 6 characters long.";
    } else {
        try {
            $user_id = $_SESSION['user_id'];
            
            // Retrieve current hashed password
            $stmt = $pdo->prepare("SELECT `password_hash`, `username` FROM `users` WHERE `id` = ? LIMIT 1");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($current_password, $user['password_hash'])) {
                // Update password with new hash
                $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
                $update_stmt = $pdo->prepare("UPDATE `users` SET `password_hash` = ? WHERE `id` = ?");
                $update_stmt->execute([$new_hash, $user_id]);
                
                // Regenerate session to protect session token integrity
                secureSessionRegenerate();
                
                // Log password change
                logActivity($user_id, 'Edit', "User '{$user['username']}' updated their account password.");
                
                $success_msg = "Password changed successfully!";
            } else {
                $error_msg = "Current password is incorrect.";
            }
        } catch (PDOException $e) {
            error_log("Password update error: " . $e->getMessage());
            $error_msg = "A database error occurred. Please try again.";
        }
    }
}
?>

<div class="header-actions">
    <div class="page-title">
        <h1>Account Settings</h1>
        <p>Change your account access credentials</p>
    </div>
</div>

<div class="auth-card" style="max-width: 550px; margin: 0 auto; background-color: var(--bg-secondary); border: 1px solid var(--border-color);">
    <h3 style="margin-bottom: 1.5rem; color: var(--accent-primary); border-bottom: 1px solid var(--border-color); padding-bottom: 0.5rem;">Update Password</h3>
    
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
    
    <form action="change_password.php" method="POST" class="validated-form">
        <div class="form-group">
            <label for="current_password">Current Password:</label>
            <input type="password" name="current_password" id="current_password" class="form-control" placeholder="Enter current password" required>
        </div>
        
        <div class="form-group">
            <label for="new_password">New Password:</label>
            <input type="password" name="new_password" id="new_password" class="form-control" placeholder="Enter new password" required>
        </div>
        
        <div class="form-group">
            <label for="confirm_password">Confirm New Password:</label>
            <input type="password" name="confirm_password" id="confirm_password" class="form-control" placeholder="Re-enter new password" required>
        </div>
        
        <div style="display: flex; gap: 10px; margin-top: 1.5rem;">
            <button type="submit" class="btn btn-primary">Update Password</button>
            <a href="index.php" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>

<?php include_once __DIR__ . '/includes/footer.php'; ?>
