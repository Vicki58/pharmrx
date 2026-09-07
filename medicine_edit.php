<?php
/**
 * Edit/Update Medicine Record Page
 * Restricted to Administrators. Supports image replacement and validation.
 */
$pageTitle = "Edit Medicine";
include_once __DIR__ . '/includes/header.php';
requireAdmin(); // Enforce Administrator privilege
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/logger.php';

$error_msg = "";
$success_msg = "";
$med_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($med_id <= 0) {
    echo "<script>window.location.href='medicines.php';</script>";
    exit();
}

// Fetch current medicine details
try {
    $stmt = $pdo->prepare("SELECT * FROM `medicines` WHERE `id` = ? LIMIT 1");
    $stmt->execute([$med_id]);
    $medicine = $stmt->fetch();
    
    if (!$medicine) {
        $_SESSION['medicine_success'] = "The requested medicine record could not be found.";
        echo "<script>window.location.href='medicines.php';</script>";
        exit();
    }
    
    // Fetch categories
    $cat_stmt = $pdo->query("SELECT * FROM `categories` ORDER BY `name` ASC");
    $categories = $cat_stmt->fetchAll();
    
} catch (PDOException $e) {
    error_log("Database error in edit view initialization: " . $e->getMessage());
    die("Database access error.");
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = trim($_POST['name'] ?? '');
    $category_id = $_POST['category_id'] !== '' ? (int)$_POST['category_id'] : null;
    $description = trim($_POST['description'] ?? '');
    $price = $_POST['price'] ?? '';
    $stock = $_POST['stock_quantity'] ?? '';
    
    // Server-side validation
    if (empty($name) || $price === '' || $stock === '') {
        $error_msg = "Please fill in all required fields.";
    } elseif (!is_numeric($price) || floatval($price) < 0) {
        $error_msg = "Price must be a positive number.";
    } elseif (!is_numeric($stock) || intval($stock) < 0) {
        $error_msg = "Stock quantity must be a positive integer.";
    } else {
        try {
            // Check for duplicate medicine name (excluding self)
            $dup_stmt = $pdo->prepare("SELECT `id` FROM `medicines` WHERE `name` = ? AND `id` != ?");
            $dup_stmt->execute([$name, $med_id]);
            if ($dup_stmt->fetch()) {
                $error_msg = "A medicine with this name already exists in the inventory.";
            } else {
                $image_filename = $medicine['image_path']; // Keep existing by default
                
                // File upload validation
                if (isset($_FILES['medicine_image']) && $_FILES['medicine_image']['error'] !== UPLOAD_ERR_NO_FILE) {
                    $file = $_FILES['medicine_image'];
                    
                    if ($file['error'] !== UPLOAD_ERR_OK) {
                        $error_msg = "Error during file upload: " . $file['error'];
                    } else {
                        // Check extension and mime type
                        $allowed_extensions = ['jpg', 'jpeg', 'png', 'webp'];
                        $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                        
                        $allowed_mimes = ['image/jpeg', 'image/png', 'image/webp', 'image/pjpeg', 'image/x-png'];
                        $file_mime = $file['type'];
                        
                        $max_size = 2 * 1024 * 1024; // 2MB
                        
                        if (!in_array($file_ext, $allowed_extensions) || !in_array($file_mime, $allowed_mimes)) {
                            $error_msg = "Invalid file type. Only JPG, PNG, and WEBP images are allowed.";
                        } elseif ($file['size'] > $max_size) {
                            $error_msg = "File is too large. Maximum size is 2MB.";
                        } else {
                            $upload_dir = __DIR__ . '/assets/uploads/';
                            if (!is_dir($upload_dir)) {
                                mkdir($upload_dir, 0755, true);
                            }
                            
                            // Generate safe unique filename
                            $new_image_filename = uniqid('med_', true) . '.' . $file_ext;
                            $destination = $upload_dir . $new_image_filename;
                            
                            if (move_uploaded_file($file['tmp_name'], $destination)) {
                                // Delete old image file if it exists
                                if (!empty($medicine['image_path'])) {
                                    $old_file_path = $upload_dir . $medicine['image_path'];
                                    if (file_exists($old_file_path)) {
                                        unlink($old_file_path);
                                    }
                                }
                                $image_filename = $new_image_filename;
                            } else {
                                $error_msg = "Failed to save uploaded image.";
                            }
                        }
                    }
                }
                
                // Update DB Record if no errors occurred
                if (empty($error_msg)) {
                    $update_stmt = $pdo->prepare("
                        UPDATE `medicines` 
                        SET `category_id` = ?, `name` = ?, `description` = ?, `price` = ?, `stock_quantity` = ?, `image_path` = ? 
                        WHERE `id` = ?
                    ");
                    $update_stmt->execute([
                        $category_id,
                        $name,
                        $description,
                        floatval($price),
                        intval($stock),
                        $image_filename,
                        $med_id
                    ]);
                    
                    // Audit log registration
                    logActivity($_SESSION['user_id'], 'Edit', "Updated medicine '{$name}' (ID: {$med_id}) details.");
                    
                    $_SESSION['medicine_success'] = "Medicine '{$name}' updated successfully!";
                    echo "<script>window.location.href='medicines.php';</script>";
                    exit();
                }
            }
        } catch (PDOException $e) {
            error_log("Update medicine database error: " . $e->getMessage());
            $error_msg = "A database error occurred while updating the record.";
        }
    }
}
?>

<div class="header-actions">
    <div class="page-title">
        <h1>Edit Medicine</h1>
        <p>Modify inventory details for product ID: <?php echo $med_id; ?></p>
    </div>
    <a href="medicines.php" class="btn btn-secondary">
        &larr; Back to Catalog
    </a>
</div>

<div class="auth-card" style="max-width: 700px; margin: 0 auto; background-color: var(--bg-secondary); border: 1px solid var(--border-color);">
    <h3 style="margin-bottom: 1.5rem; color: var(--accent-primary); border-bottom: 1px solid var(--border-color); padding-bottom: 0.5rem;">Update Medicine Information Form</h3>
    
    <?php if (!empty($error_msg)): ?>
        <div class="alert alert-danger">
            <span><?php echo htmlspecialchars($error_msg); ?></span>
        </div>
    <?php endif; ?>
    
    <form action="medicine_edit.php?id=<?php echo $med_id; ?>" method="POST" enctype="multipart/form-data" class="validated-form">
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
            <div>
                <div class="form-group">
                    <label for="name">Medicine Name *:</label>
                    <input type="text" name="name" id="name" class="form-control" placeholder="e.g. Aspirin 100mg" value="<?php echo htmlspecialchars($medicine['name']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="category_id">Category:</label>
                    <select name="category_id" id="category_id" class="form-control">
                        <option value="">Select Category...</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat['id']; ?>" <?php echo $medicine['category_id'] == $cat['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cat['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                    <div class="form-group">
                        <label for="price">Price (KES) *:</label>
                        <input type="number" step="0.01" min="0.00" name="price" id="price" class="form-control" placeholder="0.00" value="<?php echo htmlspecialchars($medicine['price']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="stock_quantity">Stock Qty *:</label>
                        <input type="number" min="0" name="stock_quantity" id="stock_quantity" class="form-control" placeholder="0" value="<?php echo htmlspecialchars($medicine['stock_quantity']); ?>" required>
                    </div>
                </div>
            </div>
            
            <div>
                <div class="form-group">
                    <label for="description">Description:</label>
                    <textarea name="description" id="description" rows="4" class="form-control" placeholder="Optional description details..."><?php echo htmlspecialchars($medicine['description'] ?? ''); ?></textarea>
                </div>
                
                <div class="form-group">
                    <label for="medicine_image">Replace Image (Max 2MB):</label>
                    <div class="file-upload-wrapper">
                        <input type="file" name="medicine_image" id="medicine_image" class="form-control" accept="image/*" style="opacity:0; position:absolute; z-index:-1;">
                        <label for="medicine_image" class="file-upload-preview" style="cursor:pointer; height: 120px;">
                            <?php if (!empty($medicine['image_path']) && file_exists(__DIR__ . '/assets/uploads/' . $medicine['image_path'])): ?>
                                <img src="assets/uploads/<?php echo htmlspecialchars($medicine['image_path']); ?>" style="max-height: 50px; border-radius: 4px;">
                                <span style="font-size:0.75rem; color:var(--text-secondary);">Click to replace current image</span>
                            <?php else: ?>
                                <span>📁 Click to browse image</span>
                                <span style="font-size:0.75rem; color:var(--text-secondary);">Allowed: JPG, PNG, WEBP</span>
                            <?php endif; ?>
                        </label>
                    </div>
                </div>
            </div>
        </div>
        
        <div style="display: flex; gap: 10px; margin-top: 1.5rem; border-top: 1px solid var(--border-color); padding-top: 1.5rem;">
            <button type="submit" class="btn btn-primary">Save Changes</button>
            <a href="medicines.php" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>

<?php include_once __DIR__ . '/includes/footer.php'; ?>
