<?php
/**
 * System Activity Logging helper
 * Records security and administrative events to the database.
 */

require_once __DIR__ . '/db.php';

/**
 * Inserts an activity log entry into the database.
 * 
 * @param int|null $userId ID of the user performing the action (defaults to current session user if null)
 * @param string $activity Type of activity ('Login', 'Logout', 'Add', 'Edit', 'Delete')
 * @param string $description Detailed description of the action
 * @return bool
 */
function logActivity($userId, $activity, $description) {
    global $pdo;
    
    // Default to the current logged-in user if null is passed
    if ($userId === null && isset($_SESSION['user_id'])) {
        $userId = $_SESSION['user_id'];
    }
    
    // Validate activity type against ENUM constraints
    $allowed_activities = ['Login', 'Logout', 'Add', 'Edit', 'Delete'];
    if (!in_array($activity, $allowed_activities)) {
        error_log("Invalid activity log type attempted: " . $activity);
        return false;
    }
    
    try {
        $stmt = $pdo->prepare("INSERT INTO `activity_logs` (`user_id`, `activity`, `description`) VALUES (?, ?, ?)");
        return $stmt->execute([$userId, $activity, $description]);
    } catch (PDOException $e) {
        // Log database failure to system logs but do not crash the application
        error_log("Activity Logging Failure: " . $e->getMessage());
        return false;
    }
}
?>
