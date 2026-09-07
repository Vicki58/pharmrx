<?php
/**
 * Create Medicine Record Page
 * Restricted to Administrators. Handles file uploads and data validation.
 */
$pageTitle = "Add Medicine";
include_once __DIR__ . '/includes/header.php';
requireAdmin(); // Enforce Administrator privilege
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/logger.php';

$error_msg = "";
$success_msg = "";

// Fetch categories for the form selector
try {
    $cat_stmt = $pdo->query("SELECT * FROM `categories` ORDER BY `name` ASC");
    $categories = $cat_stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Categories fetch error: " . $e->getMessage());
    $categories = [];
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
            // Check for duplicate medicine name
            $dup_stmt = $pdo->prepare("SELECT `id` FROM `medicines` WHERE `name` = ?");
            $dup_stmt->execute([$name]);
            if ($dup_stmt->fetch()) {
                $error_msg = "A medicine with this name already exists in the inventory.";
            } else {
                $image_filename = null;
                
                // File upload validation
                if (isset($_FILES['medicine_image']) && $_FILES['medicine_image']['error'] !== UPLOAD_ERR_NO_FILE) {
                    $file = $_FILES['medicine_image'];
                    
                    if ($file['error'] !== UPLOAD_ERR_OK) {
                        $error_msg = "Error during file upload code: " . $file['error'];
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
                            // Ensure upload directory exists
                            $upload_dir = __DIR__ . '/assets/uploads/';
                            if (!is_dir($upload_dir)) {
                                mkdir($upload_dir, 0755, true);
                            }
                            
                            // Generate safe unique filename
                            $image_filename = uniqid('med_', true) . '.' . $file_ext;
                            $destination = $upload_dir . $image_filename;
                            
                            if (!move_uploaded_file($file['tmp_name'], $destination)) {
                                $error_msg = "Failed to save uploaded image. Check folder write permissions.";
                            }
                        }
                    }
                }
                
                // Proceed to DB Insert if no upload errors occurred
                if (empty($error_msg)) {
                    $insert_stmt = $pdo->prepare("
                        INSERT INTO `medicines` (`category_id`, `name`, `description`, `price`, `stock_quantity`, `image_path`) 
                        VALUES (?, ?, ?, ?, ?, ?)
                    ");
                    $insert_stmt->execute([
                        $category_id,
                        $name,
                        $description,
                        floatval($price),
                        intval($stock),
                        $image_filename
                    ]);
                    
                    // Audit log
                    logActivity($_SESSION['user_id'], 'Add', "Added new medicine '{$name}' with price KSh {$price} and stock {$stock}.");
                    
                    $_SESSION['medicine_success'] = "Medicine '{$name}' added successfully!";
                    echo "<script>window.location.href='medicines.php';</script>";
                    exit();
                }
            }
        } catch (PDOException $e) {
            error_log("Add medicine database error: " . $e->getMessage());
            $error_msg = "A database error occurred while creating the record.";
        }
    }
}
?>

<div class="header-actions">
    <div class="page-title">
        <h1>Add New Medicine</h1>
        <p>Register a new product in the database</p>
    </div>
    <a href="medicines.php" class="btn btn-secondary">
        &larr; Back to Catalog
    </a>
</div>

<div class="auth-card" style="max-width: 700px; margin: 0 auto; background-color: var(--bg-secondary); border: 1px solid var(--border-color);">
    <h3 style="margin-bottom: 1.5rem; color: var(--accent-primary); border-bottom: 1px solid var(--border-color); padding-bottom: 0.5rem;">Medicine Information Form</h3>
    
    <?php if (!empty($error_msg)): ?>
        <div class="alert alert-danger">
            <span><?php echo htmlspecialchars($error_msg); ?></span>
        </div>
    <?php endif; ?>
    
    <form action="medicine_add.php" method="POST" enctype="multipart/form-data" class="validated-form">
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
            <div>
                <div class="form-group">
                    <label for="name">Medicine Name *:</label>
                    <input type="text" name="name" id="name" class="form-control" placeholder="e.g. Aspirin 100mg" required>
                </div>
                
                <div class="form-group">
                    <label for="category_id">Category:</label>
                    <select name="category_id" id="category_id" class="form-control">
                        <option value="">Select Category...</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                    <div class="form-group">
                        <label for="price">Price (KES) *:</label>
                        <input type="number" step="0.01" min="0.00" name="price" id="price" class="form-control" placeholder="0.00" required>
                    </div>
                    <div class="form-group">
                        <label for="stock_quantity">Stock Qty *:</label>
                        <input type="number" min="0" name="stock_quantity" id="stock_quantity" class="form-control" placeholder="0" required>
                    </div>
                </div>
            </div>
            
            <div>
                <div class="form-group">
                    <label for="description">Description:</label>
                    <textarea name="description" id="description" rows="4" class="form-control" placeholder="Optional description details..."></textarea>
                </div>
                
                <div class="form-group">
                    <label for="medicine_image">Medicine Image (Max 2MB):</label>
                    <div class="file-upload-wrapper">
                        <input type="file" name="medicine_image" id="medicine_image" class="form-control" accept="image/*" style="opacity:0; position:absolute; z-index:-1;">
                        <label for="medicine_image" class="file-upload-preview" style="cursor:pointer;">
                            <span>📁 Click to browse image</span>
                            <span style="font-size:0.75rem; color:var(--text-secondary);">Allowed: JPG, PNG, WEBP</span>
                        </label>
                    </div>
                </div>
            </div>
        </div>
        
        <div style="display: flex; gap: 10px; margin-top: 1.5rem; border-top: 1px solid var(--border-color); padding-top: 1.5rem;">
            <button type="submit" class="btn btn-primary">Add Medicine</button>
            <a href="medicines.php" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>

<?php include_once __DIR__ . '/includes/footer.php'; ?>
