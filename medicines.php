<?php
/**
 * Medicines Catalog View Page
 * Displays medicine inventory with search, pagination, and role-based actions.
 */
$pageTitle = "Medicines Catalog";
include_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/config/db.php';

// Pagination settings
$items_per_page = 4;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $items_per_page;

// Search configuration
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$search_param = "%$search%";

$error_msg = "";

try {
    // 1. Get total record count for pagination calculation
    if ($search !== '') {
        $count_stmt = $pdo->prepare("
            SELECT COUNT(*) 
            FROM `medicines` m 
            LEFT JOIN `categories` c ON m.category_id = c.id 
            WHERE m.name LIKE :s1 OR m.description LIKE :s2 OR c.name LIKE :s3
        ");
        $count_stmt->bindValue(':s1', $search_param, PDO::PARAM_STR);
        $count_stmt->bindValue(':s2', $search_param, PDO::PARAM_STR);
        $count_stmt->bindValue(':s3', $search_param, PDO::PARAM_STR);
        $count_stmt->execute();
    } else {
        $count_stmt = $pdo->query("SELECT COUNT(*) FROM `medicines`");
    }
    $total_records = (int)$count_stmt->fetchColumn();
    $total_pages = ceil($total_records / $items_per_page);
    if ($total_pages < 1) $total_pages = 1;
    if ($page > $total_pages) {
        $page = $total_pages;
        $offset = ($page - 1) * $items_per_page;
    }

    // 2. Fetch medicines with details (using distinct named parameters for native PDO compatibility)
    if ($search !== '') {
        $query = "SELECT m.*, c.name AS category_name 
                  FROM `medicines` m 
                  LEFT JOIN `categories` c ON m.category_id = c.id 
                  WHERE m.name LIKE :s1 OR m.description LIKE :s2 OR c.name LIKE :s3 
                  ORDER BY m.id DESC 
                  LIMIT :limit OFFSET :offset";
        $stmt = $pdo->prepare($query);
        $stmt->bindValue(':s1', $search_param, PDO::PARAM_STR);
        $stmt->bindValue(':s2', $search_param, PDO::PARAM_STR);
        $stmt->bindValue(':s3', $search_param, PDO::PARAM_STR);
    } else {
        $query = "SELECT m.*, c.name AS category_name 
                  FROM `medicines` m 
                  LEFT JOIN `categories` c ON m.category_id = c.id 
                  ORDER BY m.id DESC 
                  LIMIT :limit OFFSET :offset";
        $stmt = $pdo->prepare($query);
    }
    
    $stmt->bindValue(':limit', $items_per_page, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $medicines = $stmt->fetchAll();
    
} catch (PDOException $e) {
    error_log("Database error in medicines: " . $e->getMessage());
    $error_msg = "Database query error: " . $e->getMessage();
}

// Success message check (passed via session from add/edit/delete actions)
$success_msg = "";
if (isset($_SESSION['medicine_success'])) {
    $success_msg = $_SESSION['medicine_success'];
    unset($_SESSION['medicine_success']);
}
?>

<div class="header-actions">
    <div class="page-title">
        <h1>Medicines Inventory</h1>
        <p>Manage and search pharmacy medicine inventory</p>
    </div>
    <?php if (isAdmin()): ?>
        <a href="medicine_add.php" class="btn btn-primary">
            + Add New Medicine
        </a>
    <?php endif; ?>
</div>

<?php if (!empty($success_msg)): ?>
    <div class="alert alert-success">
        <span><?php echo htmlspecialchars($success_msg); ?></span>
    </div>
<?php endif; ?>

<?php if (!empty($error_msg)): ?>
    <div class="alert alert-danger">
        <span><?php echo htmlspecialchars($error_msg); ?></span>
    </div>
<?php endif; ?>

<!-- Search Form Panel -->
<div class="card-premium" style="padding: 1.25rem; margin-bottom: 2rem;">
    <form action="medicines.php" method="GET" style="display: flex; gap: 15px; flex-wrap: wrap; align-items: center;">
        <div style="flex-grow: 1; min-width: 250px;">
            <input type="text" name="search" class="form-control" placeholder="Search by medicine name, category, or description..." value="<?php echo htmlspecialchars($search); ?>">
        </div>
        <div style="display: flex; gap: 10px;">
            <button type="submit" class="btn btn-primary">Search</button>
            <?php if ($search !== ''): ?>
                <a href="medicines.php" class="btn btn-secondary">Clear</a>
            <?php endif; ?>
        </div>
    </form>
    <?php if ($search !== ''): ?>
        <div style="margin-top: 0.75rem; font-size: 0.9rem; color: var(--text-secondary);">
            Found <strong><?php echo $total_records; ?></strong> result(s) matching "<strong><?php echo htmlspecialchars($search); ?></strong>":
        </div>
    <?php endif; ?>
</div>

<!-- Medicines Grid View -->
<?php if (empty($medicines)): ?>
    <div class="card-premium text-center" style="padding: 3rem;">
        <p style="color: var(--text-secondary); font-size: 1.1rem; margin-bottom: 1rem;">No medicines found in the system matching your criteria.</p>
        <?php if (isAdmin()): ?>
            <a href="medicine_add.php" class="btn btn-primary">Add a medicine now</a>
        <?php endif; ?>
    </div>
<?php else: ?>
    <div class="medicines-grid">
        <?php foreach ($medicines as $med): 
            // Determine Stock status and badges
            $stock = $med['stock_quantity'];
            $stock_badge = '<span class="badge badge-instock">In Stock (' . $stock . ')</span>';
            if ($stock === 0) {
                $stock_badge = '<span class="badge badge-outofstock">Out of Stock</span>';
            } elseif ($stock < 10) {
                $stock_badge = '<span class="badge badge-lowstock">Low Stock (' . $stock . ')</span>';
            }
            
            // Image handling (use fallback if empty or not found)
            $image_src = 'assets/img/placeholder.jpg';
            if (!empty($med['image_path'])) {
                // Support both direct path and relative path
                $test_path = __DIR__ . '/assets/uploads/' . $med['image_path'];
                if (file_exists($test_path)) {
                    $image_src = 'assets/uploads/' . $med['image_path'];
                }
            }
        ?>
            <div class="medicine-card">
                <div class="medicine-thumb">
                    <!-- Image rendering with safety features -->
                    <?php if ($image_src === 'assets/img/placeholder.jpg'): ?>
                        <div style="font-size: 3rem; color: var(--text-secondary); opacity: 0.3;">💊</div>
                    <?php else: ?>
                        <img src="<?php echo htmlspecialchars($image_src); ?>" alt="<?php echo htmlspecialchars($med['name']); ?>">
                    <?php endif; ?>
                </div>
                <div class="medicine-details">
                    <div>
                        <div style="font-size: 0.8rem; color: var(--accent-primary); font-weight: 600; margin-bottom: 0.25rem; text-transform: uppercase;">
                            <?php echo htmlspecialchars($med['category_name'] ?? 'Uncategorized'); ?>
                        </div>
                        <h3 class="medicine-title"><?php echo htmlspecialchars($med['name']); ?></h3>
                        <p class="medicine-desc"><?php echo htmlspecialchars($med['description'] ?? 'No description available.'); ?></p>
                    </div>
                    <div>
                        <div class="medicine-meta">
                            <span class="medicine-price">KSh <?php echo number_format($med['price'], 2); ?></span>
                            <?php echo $stock_badge; ?>
                        </div>
                        
                        <div class="medicine-actions">
                            <!-- Allow normal users to record sale, Admins can manage too -->
                            <a href="sales.php?add_sale=<?php echo $med['id']; ?>" class="btn btn-secondary btn-sm" style="background-color: rgba(6, 182, 212, 0.1); border-color: rgba(6, 182, 212, 0.2); color: var(--accent-primary);">
                                Sell Items
                            </a>
                            
                            <?php if (isAdmin()): ?>
                                <a href="medicine_edit.php?id=<?php echo $med['id']; ?>" class="btn btn-secondary btn-sm" title="Edit Medicine">
                                    Edit
                                </a>
                                <a href="medicine_delete.php?id=<?php echo $med['id']; ?>" class="btn btn-danger btn-sm" title="Delete Medicine" onclick="return confirm('Are you sure you want to delete this medicine? This action cannot be undone.');">
                                    Delete
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Pagination Controls -->
    <?php if ($total_pages > 1): ?>
        <div style="display: flex; flex-direction: column; align-items: center; gap: 8px; margin-top: 2rem;">
            <div style="font-size: 0.85rem; color: var(--text-secondary);">
                Showing Page <strong><?php echo $page; ?></strong> of <strong><?php echo $total_pages; ?></strong> (<?php echo $total_records; ?> total medicines)
            </div>
            <div class="pagination" style="margin-top: 0;">
                <?php if ($page > 1): ?>
                    <a href="medicines.php?page=<?php echo $page - 1; ?><?php echo $search !== '' ? '&search=' . urlencode($search) : ''; ?>" class="pagination-item" title="Previous Page">&laquo;</a>
                <?php endif; ?>
                
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <a href="medicines.php?page=<?php echo $i; ?><?php echo $search !== '' ? '&search=' . urlencode($search) : ''; ?>" class="pagination-item <?php echo $page === $i ? 'active' : ''; ?>">
                        <?php echo $i; ?>
                    </a>
                <?php endfor; ?>
                
                <?php if ($page < $total_pages): ?>
                    <a href="medicines.php?page=<?php echo $page + 1; ?><?php echo $search !== '' ? '&search=' . urlencode($search) : ''; ?>" class="pagination-item" title="Next Page">&raquo;</a>
                <?php endif; ?>
            </div>
        </div>
    <?php else: ?>
        <div style="text-align: center; font-size: 0.85rem; color: var(--text-secondary); margin-top: 2rem;">
            Showing all <?php echo $total_records; ?> medicine(s)
        </div>
    <?php endif; ?>
<?php endif; ?>

<?php include_once __DIR__ . '/includes/footer.php'; ?>
