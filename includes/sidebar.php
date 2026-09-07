<?php
/**
 * Shared Sidebar Navigation Template
 * Renders user details and active page navigation links based on user status.
 */

$current_page = basename($_SERVER['PHP_SELF']);
$username = isset($_SESSION['username']) ? $_SESSION['username'] : 'Guest';
$role = isset($_SESSION['role']) ? $_SESSION['role'] : 'Normal';
?>
<aside class="app-sidebar">
    <div class="sidebar-logo">
        <div class="logo-icon">Rx</div>
        <div class="logo-text">PharmRx</div>
    </div>
    
    <div class="sidebar-user">
        <div class="user-avatar">
            <?php echo htmlspecialchars(substr($username, 0, 2)); ?>
        </div>
        <div class="user-info">
            <div class="user-name" title="<?php echo htmlspecialchars($username); ?>">
                <?php echo htmlspecialchars($username); ?>
            </div>
            <div class="user-role">
                <?php echo htmlspecialchars($role); ?>
            </div>
        </div>
    </div>
    
    <ul class="sidebar-menu">
        <li class="<?php echo $current_page === 'index.php' ? 'active' : ''; ?>">
            <a href="index.php">
                Dashboard
            </a>
        </li>
        <li class="<?php echo in_array($current_page, ['medicines.php', 'medicine_add.php', 'medicine_edit.php']) ? 'active' : ''; ?>">
            <a href="medicines.php">
                Medicines Catalog
            </a>
        </li>
        <li class="<?php echo $current_page === 'categories.php' ? 'active' : ''; ?>">
            <a href="categories.php">
                Categories
            </a>
        </li>
        <li class="<?php echo $current_page === 'sales.php' ? 'active' : ''; ?>">
            <a href="sales.php">
                Sales Records
            </a>
        </li>
        <li class="<?php echo $current_page === 'logs.php' ? 'active' : ''; ?>">
            <a href="logs.php">
                System Activity Logs
            </a>
        </li>
        <li class="<?php echo $current_page === 'change_password.php' ? 'active' : ''; ?>">
            <a href="change_password.php">
                Change Password
            </a>
        </li>
    </ul>
    
    <div class="sidebar-footer">
        <a href="logout.php" class="btn btn-secondary btn-sm" style="width: 100%; display: flex; justify-content: center;">
            Log Out
        </a>
    </div>
</aside>
