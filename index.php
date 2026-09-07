<?php
/**
 * Dashboard Panel Home Page
 * Renders inventory statistics, revenue summaries, and previews recent updates.
 */
$pageTitle = "Dashboard";
include_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/config/db.php';

// Initialize stats variables
$stat_total_medicines = 0;
$stat_out_of_stock = 0;
$stat_total_sales_revenue = 0.00;
$stat_total_users = 0;
$recent_sales = [];
$recent_logs = [];
$low_stock_alerts = [];

try {
    // 1. Calculate General Statistics
    $stmt_meds = $pdo->query("SELECT COUNT(*) FROM `medicines`");
    $stat_total_medicines = $stmt_meds->fetchColumn();
    
    $stmt_stock = $pdo->query("SELECT COUNT(*) FROM `medicines` WHERE `stock_quantity` = 0");
    $stat_out_of_stock = $stmt_stock->fetchColumn();
    
    $stmt_rev = $pdo->query("SELECT SUM(`total_price`) FROM `sales`");
    $stat_total_sales_revenue = $stmt_rev->fetchColumn() ?: 0.00;
    
    $stmt_usr = $pdo->query("SELECT COUNT(*) FROM `users`");
    $stat_total_users = $stmt_usr->fetchColumn();
    
    // 2. Fetch Low Stock Medicines (< 10 items remaining)
    $stmt_low = $pdo->query("SELECT `id`, `name`, `stock_quantity` FROM `medicines` WHERE `stock_quantity` < 10 ORDER BY `stock_quantity` ASC");
    $low_stock_alerts = $stmt_low->fetchAll();
    
    // 3. Fetch Recent Sales (limit 5)
    $stmt_rsales = $pdo->query("
        SELECT s.*, m.name AS medicine_name 
        FROM `sales` s 
        LEFT JOIN `medicines` m ON s.medicine_id = m.id 
        ORDER BY s.id DESC LIMIT 5
    ");
    $recent_sales = $stmt_rsales->fetchAll();
    
    // 4. Fetch Recent Logs (limit 5)
    $stmt_rlogs = $pdo->query("
        SELECT l.*, u.username 
        FROM `activity_logs` l 
        LEFT JOIN `users` u ON l.user_id = u.id 
        ORDER BY l.id DESC LIMIT 5
    ");
    $recent_logs = $stmt_rlogs->fetchAll();
    
} catch (PDOException $e) {
    error_log("Dashboard query execution error: " . $e->getMessage());
}
?>

<div class="header-actions">
    <div class="page-title">
        <h1>Dashboard Panel</h1>
        <p>Welcome back, <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong>! Here is today's pharmacy status.</p>
    </div>
</div>

<!-- Low Stock Warning Banner -->
<?php if (!empty($low_stock_alerts)): ?>
    <div class="alert alert-danger" style="margin-bottom: 2rem;">
        <div style="flex-grow: 1;">
            <strong>Warning: Low Stock Items Detected!</strong>
            <ul style="margin-top: 0.5rem; padding-left: 1.25rem; font-size: 0.85rem;">
                <?php foreach ($low_stock_alerts as $alert_item): ?>
                    <li>
                        <?php echo htmlspecialchars($alert_item['name']); ?> has only 
                        <strong><?php echo $alert_item['stock_quantity']; ?></strong> units left in stock.
                        <?php if (isAdmin()): ?>
                            <a href="medicine_edit.php?id=<?php echo $alert_item['id']; ?>" style="color: white; margin-left: 10px; text-decoration: underline;">Restock</a>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
<?php endif; ?>

<!-- Analytics Grid Cards -->
<div class="grid-stats">
    <!-- Stat Item 1: Medicines -->
    <div class="stat-card">
        <div>
            <div class="stat-desc">Total Medicines</div>
            <div class="stat-val"><?php echo $stat_total_medicines; ?></div>
        </div>
        <div class="stat-icon">💊</div>
    </div>
    
    <!-- Stat Item 2: Out of Stock -->
    <div class="stat-card">
        <div>
            <div class="stat-desc">Out of Stock</div>
            <div class="stat-val" style="<?php echo $stat_out_of_stock > 0 ? 'color: var(--danger);' : ''; ?>">
                <?php echo $stat_out_of_stock; ?>
            </div>
        </div>
        <div class="stat-icon" style="<?php echo $stat_out_of_stock > 0 ? 'color: var(--danger); background: rgba(239,68,68,0.1);' : ''; ?>">⚠️</div>
    </div>
    
    <!-- Stat Item 3: Revenue -->
    <div class="stat-card">
        <div>
            <div class="stat-desc">Total Revenue</div>
            <div class="stat-val" style="color: var(--success);">KSh <?php echo number_format($stat_total_sales_revenue, 2); ?></div>
        </div>
        <div class="stat-icon" style="color: var(--success); background: rgba(16,185,129,0.1);">💵</div>
    </div>
    
    <!-- Stat Item 4: Registered Operators -->
    <div class="stat-card">
        <div>
            <div class="stat-desc">Operators</div>
            <div class="stat-val"><?php echo $stat_total_users; ?></div>
        </div>
        <div class="stat-icon" style="color: var(--accent-secondary); background: rgba(99,102,241,0.1);">👤</div>
    </div>
</div>

<!-- Dashboard split view grids -->
<div class="dashboard-grid">
    <!-- Left Split Column: Recent Sales -->
    <div class="card-premium">
        <h3 class="card-title">
            <span>Recent Sales Transactions</span>
            <a href="sales.php" class="btn btn-secondary btn-sm">View All</a>
        </h3>
        
        <div class="table-container">
            <table class="table-premium">
                <thead>
                    <tr>
                        <th>Medicine</th>
                        <th style="text-align: center;">Qty</th>
                        <th>Total</th>
                        <th>Timestamp</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($recent_sales)): ?>
                        <tr>
                            <td colspan="4" style="text-align: center; color: var(--text-secondary);">No sales recorded yet.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($recent_sales as $sale): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($sale['medicine_name'] ?? '[Deleted Medicine]'); ?></strong></td>
                                <td style="text-align: center;"><?php echo $sale['quantity']; ?></td>
                                <td style="color: var(--accent-primary); font-weight: 600;">KSh <?php echo number_format($sale['total_price'], 2); ?></td>
                                <td style="font-size: 0.8rem; color: var(--text-secondary);"><?php echo date('M d, H:i', strtotime($sale['sale_date'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Right Split Column: Recent Logs -->
    <div class="card-premium">
        <h3 class="card-title">
            <span>Recent Operator Activities</span>
            <a href="logs.php" class="btn btn-secondary btn-sm">View All</a>
        </h3>
        
        <ul style="list-style: none; padding: 0;">
            <?php if (empty($recent_logs)): ?>
                <li style="color: var(--text-secondary); text-align: center; padding: 2rem 0;">No activities logged.</li>
            <?php else: ?>
                <?php foreach ($recent_logs as $log): 
                    $act = $log['activity'];
                    $act_color = 'var(--text-secondary)';
                    if ($act === 'Login') $act_color = 'var(--success)';
                    elseif ($act === 'Delete') $act_color = 'var(--danger)';
                    elseif ($act === 'Add') $act_color = 'var(--accent-primary)';
                ?>
                    <li style="border-bottom: 1px solid var(--border-color); padding: 0.75rem 0; font-size: 0.9rem;">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 0.25rem;">
                            <span style="font-weight: 600;"><?php echo htmlspecialchars($log['username'] ?? 'System'); ?></span>
                            <span style="color: <?php echo $act_color; ?>; font-size: 0.75rem; font-weight: 700; text-transform: uppercase;">
                                <?php echo $act; ?>
                            </span>
                        </div>
                        <p style="color: var(--text-secondary); font-size: 0.85rem; margin-bottom: 0.25rem;">
                            <?php echo htmlspecialchars($log['description']); ?>
                        </p>
                        <small style="color: rgba(255,255,255,0.25); font-size: 0.75rem;">
                            <?php echo date('Y-m-d H:i:s', strtotime($log['log_time'])); ?>
                        </small>
                    </li>
                <?php endforeach; ?>
            <?php endif; ?>
        </ul>
    </div>
</div>

<?php include_once __DIR__ . '/includes/footer.php'; ?>
