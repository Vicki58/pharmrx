<?php
/**
 * Delete Medicine Action Handler
 * Restricted to Administrators. Cleans up files from disk.
 */
require_once __DIR__ . '/config/auth.php';
requireAdmin(); // Enforce administrator permission check
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/logger.php';

$med_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($med_id > 0) {
    try {
        // Fetch current image path to clean up from storage
        $stmt = $pdo->prepare("SELECT `name`, `image_path` FROM `medicines` WHERE `id` = ? LIMIT 1");
        $stmt->execute([$med_id]);
        $medicine = $stmt->fetch();
        
        if ($medicine) {
            // Delete image file if it exists on disk
            if (!empty($medicine['image_path'])) {
                $file_path = __DIR__ . '/assets/uploads/' . $medicine['image_path'];
                if (file_exists($file_path)) {
                    unlink($file_path);
                }
            }
            
            // Delete database record (dependent sales will set their medicine_id to NULL due to ON DELETE SET NULL constraint)
            $del_stmt = $pdo->prepare("DELETE FROM `medicines` WHERE `id` = ?");
            $del_stmt->execute([$med_id]);
            
            // Register Delete action log
            logActivity($_SESSION['user_id'], 'Delete', "Deleted medicine '{$medicine['name']}' (ID: {$med_id}).");
            
            $_SESSION['medicine_success'] = "Medicine '{$medicine['name']}' deleted successfully.";
        }
    } catch (PDOException $e) {
        error_log("Delete database error: " . $e->getMessage());
        $_SESSION['medicine_success'] = "Failed to delete medicine record due to a database integrity issue.";
    }
}

// Redirect back to main medicines listing page
header("Location: medicines.php");
exit();
?>
