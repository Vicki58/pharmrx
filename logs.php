<?php
/**
 * System Activity Logs View Page
 * Displays audit log table showing operator actions.
 */
$pageTitle = "System Activity Logs";
include_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/config/db.php';

// Pagination settings
$logs_per_page = 15;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $logs_per_page;

// Filtering options
$filter_activity = isset($_GET['activity']) ? trim($_GET['activity']) : '';
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';
$search_param = "%$search_query%";

try {
    // 1. Build query clauses dynamic filters
    $where_clauses = [];
    $params = [];
    
    if ($filter_activity !== '') {
        $where_clauses[] = "l.activity = :activity";
        $params[':activity'] = $filter_activity;
    }
    
    if ($search_query !== '') {
        $where_clauses[] = "l.description LIKE :search";
        $params[':search'] = $search_param;
    }
    
    $where_sql = "";
    if (!empty($where_clauses)) {
        $where_sql = "WHERE " . implode(" AND ", $where_clauses);
    }
    
    // 2. Fetch total count
    $count_query = "SELECT COUNT(*) FROM `activity_logs` l $where_sql";
    $count_stmt = $pdo->prepare($count_query);
    foreach ($params as $key => $val) {
        $count_stmt->bindValue($key, $val);
    }
    $count_stmt->execute();
    $total_logs = $count_stmt->fetchColumn();
    
    $total_pages = ceil($total_logs / $logs_per_page);
    if ($total_pages < 1) $total_pages = 1;
    if ($page > $total_pages) {
        $page = $total_pages;
        $offset = ($page - 1) * $logs_per_page;
    }
    
    // 3. Fetch logs with user details
    $select_query = "
        SELECT l.*, u.username, u.role 
        FROM `activity_logs` l 
        LEFT JOIN `users` u ON l.user_id = u.id 
        $where_sql 
        ORDER BY l.id DESC 
        LIMIT :limit OFFSET :offset
    ";
    
    $stmt = $pdo->prepare($select_query);
    foreach ($params as $key => $val) {
        $stmt->bindValue($key, $val);
    }
    $stmt->bindValue(':limit', $logs_per_page, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $logs = $stmt->fetchAll();
    
} catch (PDOException $e) {
    error_log("Logs load failure: " . $e->getMessage());
    $logs = [];
    $total_logs = 0;
    $total_pages = 1;
}
?>

<div class="header-actions">
    <div class="page-title">
        <h1>Audit Trail Logs</h1>
        <p>Activity log records for security and accountability tracking</p>
    </div>
</div>

<!-- Filters Panel -->
<div class="card-premium" style="padding: 1.25rem; margin-bottom: 2rem;">
    <form action="logs.php" method="GET" style="display: flex; gap: 15px; flex-wrap: wrap; align-items: center;">
        <div style="min-width: 200px;">
            <select name="activity" class="form-control" onchange="this.form.submit()">
                <option value="">All Activities</option>
                <option value="Login" <?php echo $filter_activity === 'Login' ? 'selected' : ''; ?>>Logins Only</option>
                <option value="Logout" <?php echo $filter_activity === 'Logout' ? 'selected' : ''; ?>>Logouts Only</option>
                <option value="Add" <?php echo $filter_activity === 'Add' ? 'selected' : ''; ?>>Add Records</option>
                <option value="Edit" <?php echo $filter_activity === 'Edit' ? 'selected' : ''; ?>>Edit Records</option>
                <option value="Delete" <?php echo $filter_activity === 'Delete' ? 'selected' : ''; ?>>Delete Records</option>
            </select>
        </div>
        <div style="flex-grow: 1; min-width: 250px;">
            <input type="text" name="search" class="form-control" placeholder="Search event logs..." value="<?php echo htmlspecialchars($search_query); ?>">
        </div>
        <div style="display: flex; gap: 10px;">
            <button type="submit" class="btn btn-primary">Filter</button>
            <?php if ($filter_activity !== '' || $search_query !== ''): ?>
                <a href="logs.php" class="btn btn-secondary">Reset</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<!-- Logs Ledger Table -->
<div class="card-premium">
    <h3 class="card-title">System Activities (Total: <?php echo $total_logs; ?>)</h3>
    <div class="table-container">
        <table class="table-premium">
            <thead>
                <tr>
                    <th>Log ID</th>
                    <th>User</th>
                    <th>Role</th>
                    <th>Activity Type</th>
                    <th>Event Description</th>
                    <th>Date</th>
                    <th>Time</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($logs)): ?>
                    <tr>
                        <td colspan="7" style="text-align: center; color: var(--text-secondary);">No matching activity log entries.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($logs as $log): 
                        // Style badges for activity types
                        $act = $log['activity'];
                        $act_badge = '<span class="badge" style="background-color:rgba(156,163,175,0.15); color:#d1d5db;">' . $act . '</span>';
                        if ($act === 'Login') {
                            $act_badge = '<span class="badge" style="background-color:rgba(52,211,153,0.15); color:#34d399;">' . $act . '</span>';
                        } elseif ($act === 'Logout') {
                            $act_badge = '<span class="badge" style="background-color:rgba(251,191,36,0.15); color:#fbbf24;">' . $act . '</span>';
                        } elseif ($act === 'Add') {
                            $act_badge = '<span class="badge" style="background-color:rgba(99,102,241,0.15); color:#818cf8;">' . $act . '</span>';
                        } elseif ($act === 'Edit') {
                            $act_badge = '<span class="badge" style="background-color:rgba(6,182,212,0.15); color:#06b6d4;">' . $act . '</span>';
                        } elseif ($act === 'Delete') {
                            $act_badge = '<span class="badge" style="background-color:rgba(239,68,68,0.15); color:#f87171;">' . $act . '</span>';
                        }
                        
                        // Parse log time into separate date and time columns
                        $timestamp = strtotime($log['log_time']);
                        $date = date('Y-m-d', $timestamp);
                        $time = date('H:i:s', $timestamp);
                    ?>
                        <tr>
                            <td>#<?php echo $log['id']; ?></td>
                            <td>
                                <strong><?php echo htmlspecialchars($log['username'] ?? '[Unknown User]'); ?></strong>
                            </td>
                            <td>
                                <span class="badge <?php echo $log['role'] === 'Admin' ? 'badge-admin' : 'badge-normal'; ?>">
                                    <?php echo htmlspecialchars($log['role'] ?? 'Guest'); ?>
                                </span>
                            </td>
                            <td><?php echo $act_badge; ?></td>
                            <td style="color: var(--text-secondary); max-width: 400px;"><?php echo htmlspecialchars($log['description']); ?></td>
                            <td><?php echo $date; ?></td>
                            <td><?php echo $time; ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination Controls -->
    <?php if ($total_pages > 1): ?>
        <div class="pagination">
            <?php if ($page > 1): ?>
                <a href="logs.php?page=<?php echo $page - 1; ?><?php echo $filter_activity !== '' ? '&activity=' . urlencode($filter_activity) : ''; ?><?php echo $search_query !== '' ? '&search=' . urlencode($search_query) : ''; ?>" class="pagination-item">&laquo;</a>
            <?php endif; ?>
            
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <a href="logs.php?page=<?php echo $i; ?><?php echo $filter_activity !== '' ? '&activity=' . urlencode($filter_activity) : ''; ?><?php echo $search_query !== '' ? '&search=' . urlencode($search_query) : ''; ?>" class="pagination-item <?php echo $page === $i ? 'active' : ''; ?>">
                    <?php echo $i; ?>
                </a>
            <?php endfor; ?>
            
            <?php if ($page < $total_pages): ?>
                <a href="logs.php?page=<?php echo $page + 1; ?><?php echo $filter_activity !== '' ? '&activity=' . urlencode($filter_activity) : ''; ?><?php echo $search_query !== '' ? '&search=' . urlencode($search_query) : ''; ?>" class="pagination-item">&raquo;</a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<?php include_once __DIR__ . '/includes/footer.php'; ?>
