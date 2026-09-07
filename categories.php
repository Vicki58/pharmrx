<?php
/**
 * Category Management Page
 * Allows viewing categories and restricted creation/modification options for admins.
 */
$pageTitle = "Medicine Categories";
include_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/logger.php';

$error_msg = "";
$success_msg = "";

// Handle Category Submissions (Admins Only)
if ($_SERVER["REQUEST_METHOD"] === "POST" && isAdmin()) {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add') {
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        
        if (empty($name)) {
            $error_msg = "Category name is required.";
        } else {
            try {
                // Check duplicate
                $chk = $pdo->prepare("SELECT `id` FROM `categories` WHERE `name` = ?");
                $chk->execute([$name]);
                if ($chk->fetch()) {
                    $error_msg = "Category '{$name}' already exists.";
                } else {
                    $ins = $pdo->prepare("INSERT INTO `categories` (`name`, `description`) VALUES (?, ?)");
                    $ins->execute([$name, $description]);
                    
                    logActivity($_SESSION['user_id'], 'Add', "Created category '{$name}'.");
                    $success_msg = "Category '{$name}' added successfully!";
                }
            } catch (PDOException $e) {
                error_log("Category insert error: " . $e->getMessage());
                $error_msg = "A database error occurred.";
            }
        }
    } elseif ($action === 'edit') {
        $id = (int)($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        
        if ($id <= 0 || empty($name)) {
            $error_msg = "Invalid edit submission.";
        } else {
            try {
                // Check duplicate excluding self
                $chk = $pdo->prepare("SELECT `id` FROM `categories` WHERE `name` = ? AND `id` != ?");
                $chk->execute([$name, $id]);
                if ($chk->fetch()) {
                    $error_msg = "Another category named '{$name}' already exists.";
                } else {
                    $upd = $pdo->prepare("UPDATE `categories` SET `name` = ?, `description` = ? WHERE `id` = ?");
                    $upd->execute([$name, $description, $id]);
                    
                    logActivity($_SESSION['user_id'], 'Edit', "Updated category '{$name}' (ID: {$id}).");
                    $success_msg = "Category updated successfully!";
                }
            } catch (PDOException $e) {
                error_log("Category update error: " . $e->getMessage());
                $error_msg = "A database error occurred.";
            }
        }
    }
}

// Handle Category Deletion (Admins Only)
if (isset($_GET['delete']) && isAdmin()) {
    $del_id = (int)$_GET['delete'];
    if ($del_id > 0) {
        try {
            // Find category details first for logging
            $info_stmt = $pdo->prepare("SELECT `name` FROM `categories` WHERE `id` = ? LIMIT 1");
            $info_stmt->execute([$del_id]);
            $cat = $info_stmt->fetch();
            
            if ($cat) {
                $del = $pdo->prepare("DELETE FROM `categories` WHERE `id` = ?");
                $del->execute([$del_id]);
                
                logActivity($_SESSION['user_id'], 'Delete', "Deleted category '{$cat['name']}' (ID: {$del_id}).");
                $success_msg = "Category '{$cat['name']}' deleted successfully.";
            }
        } catch (PDOException $e) {
            error_log("Category delete error: " . $e->getMessage());
            $error_msg = "Cannot delete category. Ensure no medicines are currently assigned to it.";
        }
    }
}

// Load current categories
try {
    $stmt = $pdo->query("SELECT c.*, COUNT(m.id) AS medicine_count 
                         FROM `categories` c 
                         LEFT JOIN `medicines` m ON c.id = m.category_id 
                         GROUP BY c.id 
                         ORDER BY c.name ASC");
    $categories = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Category fetch error: " . $e->getMessage());
    $categories = [];
}
?>

<div class="header-actions">
    <div class="page-title">
        <h1>Medicine Categories</h1>
        <p>Manage groupings for medicine classification</p>
    </div>
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

<div class="dashboard-grid">
    <!-- Left panel: Categories List -->
    <div class="card-premium">
        <h3 class="card-title">All Categories</h3>
        <div class="table-container">
            <table class="table-premium">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Description</th>
                        <th style="text-align: center;">Medicines Count</th>
                        <?php if (isAdmin()): ?>
                            <th style="text-align: right;">Actions</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($categories)): ?>
                        <tr>
                            <td colspan="<?php echo isAdmin() ? '4' : '3'; ?>" style="text-align: center; color: var(--text-secondary);">No categories found.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($categories as $cat): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($cat['name']); ?></strong></td>
                                <td style="color: var(--text-secondary); max-width: 250px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                    <?php echo htmlspecialchars($cat['description'] ?? 'No description'); ?>
                                </td>
                                <td style="text-align: center;">
                                    <span class="badge" style="background-color: rgba(6, 182, 212, 0.1); color: var(--accent-primary);">
                                        <?php echo $cat['medicine_count']; ?> items
                                    </span>
                                </td>
                                <?php if (isAdmin()): ?>
                                    <td style="text-align: right;">
                                        <button type="button" class="btn btn-secondary btn-sm" onclick="populateEditForm(<?php echo $cat['id']; ?>, '<?php echo addslashes($cat['name']); ?>', '<?php echo addslashes($cat['description'] ?? ''); ?>')">
                                            Edit
                                        </button>
                                        <a href="categories.php?delete=<?php echo $cat['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this category?');">
                                            Delete
                                        </a>
                                    </td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Right panel: Add/Edit Forms (Visible depending on Admin role) -->
    <div>
        <?php if (isAdmin()): ?>
            <!-- Add Category Card -->
            <div class="card-premium" id="add-card">
                <h3 class="card-title">Add Category</h3>
                <form action="categories.php" method="POST" class="validated-form">
                    <input type="hidden" name="action" value="add">
                    <div class="form-group">
                        <label for="add_name">Category Name *:</label>
                        <input type="text" name="name" id="add_name" class="form-control" placeholder="e.g. Vitamins" required>
                    </div>
                    <div class="form-group">
                        <label for="add_desc">Description:</label>
                        <textarea name="description" id="add_desc" class="form-control" rows="3" placeholder="Category notes..."></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary" style="width: 100%;">Create Category</button>
                </form>
            </div>
            
            <!-- Edit Category Card (hidden until triggered) -->
            <div class="card-premium" id="edit-card" style="display: none; border-color: var(--accent-secondary);">
                <h3 class="card-title" style="color: var(--accent-secondary);">Edit Category</h3>
                <form action="categories.php" method="POST" class="validated-form">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="id" id="edit_id" value="">
                    <div class="form-group">
                        <label for="edit_name">Category Name *:</label>
                        <input type="text" name="name" id="edit_name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_desc">Description:</label>
                        <textarea name="description" id="edit_desc" class="form-control" rows="3"></textarea>
                    </div>
                    <div style="display: flex; gap: 10px;">
                        <button type="submit" class="btn btn-primary" style="flex-grow: 1;">Save Changes</button>
                        <button type="button" class="btn btn-secondary" onclick="cancelEdit()">Cancel</button>
                    </div>
                </form>
            </div>
            
            <script>
                function populateEditForm(id, name, desc) {
                    document.getElementById('add-card').style.display = 'none';
                    const editCard = document.getElementById('edit-card');
                    editCard.style.display = 'block';
                    
                    document.getElementById('edit_id').value = id;
                    document.getElementById('edit_name').value = name;
                    document.getElementById('edit_desc').value = desc;
                    
                    // Smooth scroll to edit card on small screens
                    editCard.scrollIntoView({ behavior: 'smooth' });
                }
                
                function cancelEdit() {
                    document.getElementById('edit-card').style.display = 'none';
                    document.getElementById('add-card').style.display = 'block';
                }
            </script>
        <?php else: ?>
            <div class="card-premium text-center" style="padding: 2rem;">
                <h3 style="color: var(--text-secondary); margin-bottom: 0.5rem;">Access Notice</h3>
                <p style="font-size: 0.9rem; color: var(--text-secondary);">Normal staff user roles have <strong>Read-Only</strong> permissions for system categories. Only Administrators can add, edit, or remove categories.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include_once __DIR__ . '/includes/footer.php'; ?>
