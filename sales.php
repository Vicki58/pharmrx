<?php
/**
 * Sales Ledger and Transaction Page
 * Registers transactions, updates inventory levels, and tracks revenue.
 */
$pageTitle = "Sales ledger";
include_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/logger.php';

$error_msg = "";
$success_msg = "";

// 1. Handle recording a sale
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['action']) && $_POST['action'] === 'record_sale') {
    $medicine_id = (int)($_POST['medicine_id'] ?? 0);
    $quantity = (int)($_POST['quantity'] ?? 0);
    $user_id = $_SESSION['user_id'];
    
    if ($medicine_id <= 0 || $quantity <= 0) {
        $error_msg = "Please select a valid medicine and enter a positive quantity.";
    } else {
        try {
            $pdo->beginTransaction();
            
            // Check stock and retrieve price
            $stmt = $pdo->prepare("SELECT `name`, `price`, `stock_quantity` FROM `medicines` WHERE `id` = ? FOR UPDATE");
            $stmt->execute([$medicine_id]);
            $medicine = $stmt->fetch();
            
            if (!$medicine) {
                $error_msg = "The selected medicine does not exist.";
                $pdo->rollBack();
            } elseif ($medicine['stock_quantity'] < $quantity) {
                $error_msg = "Insufficient stock. Only {$medicine['stock_quantity']} units of {$medicine['name']} are available.";
                $pdo->rollBack();
            } else {
                // Calculate pricing
                $total_price = $quantity * $medicine['price'];
                
                // Subtract stock
                $upd_stmt = $pdo->prepare("UPDATE `medicines` SET `stock_quantity` = `stock_quantity` - ? WHERE `id` = ?");
                $upd_stmt->execute([$quantity, $medicine_id]);
                
                // Insert sale record
                $ins_stmt = $pdo->prepare("INSERT INTO `sales` (`medicine_id`, `user_id`, `quantity`, `total_price`) VALUES (?, ?, ?, ?)");
                $ins_stmt->execute([$medicine_id, $user_id, $quantity, $total_price]);
                
                // Log action
                logActivity($user_id, 'Add', "Recorded sale of {$medicine['name']} (Qty: {$quantity}, Total: KSh {$total_price}).");
                
                $pdo->commit();
                $success_msg = "Sale registered successfully!";
            }
        } catch (PDOException $e) {
            $pdo->rollBack();
            error_log("Sale recording database failure: " . $e->getMessage());
            $error_msg = "A database transaction error occurred.";
        }
    }
}

// 2. Handle deleting/canceling a sale (Admins Only)
if (isset($_GET['delete_sale']) && isAdmin()) {
    $sale_id = (int)$_GET['delete_sale'];
    if ($sale_id > 0) {
        try {
            $pdo->beginTransaction();
            
            // Get sale details first to restore stock
            $stmt = $pdo->prepare("
                SELECT s.*, m.name AS medicine_name 
                FROM `sales` s 
                LEFT JOIN `medicines` m ON s.medicine_id = m.id 
                WHERE s.id = ? FOR UPDATE
            ");
            $stmt->execute([$sale_id]);
            $sale = $stmt->fetch();
            
            if ($sale) {
                // Restore stock quantity if medicine still exists in inventory
                if ($sale['medicine_id'] !== null) {
                    $restore_stmt = $pdo->prepare("UPDATE `medicines` SET `stock_quantity` = `stock_quantity` + ? WHERE `id` = ?");
                    $restore_stmt->execute([$sale['quantity'], $sale['medicine_id']]);
                }
                
                // Delete sale
                $del_stmt = $pdo->prepare("DELETE FROM `sales` WHERE `id` = ?");
                $del_stmt->execute([$sale_id]);
                
                // Log deletion action
                logActivity($_SESSION['user_id'], 'Delete', "Canceled sale ID: {$sale_id} for '{$sale['medicine_name']}' (Qty: {$sale['quantity']}). Restored stock.");
                
                $pdo->commit();
                $success_msg = "Sale canceled and stock restored.";
            } else {
                $pdo->rollBack();
                $error_msg = "Sale record not found.";
            }
        } catch (PDOException $e) {
            $pdo->rollBack();
            error_log("Sale cancellation error: " . $e->getMessage());
            $error_msg = "A database error occurred during cancellation.";
        }
    }
}

// 3. Pagination & Filtering for sales ledger
$sales_per_page = 8;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $sales_per_page;

try {
    // Total count query
    $count_stmt = $pdo->query("SELECT COUNT(*) FROM `sales`");
    $total_sales = $count_stmt->fetchColumn();
    $total_pages = ceil($total_sales / $sales_per_page);
    if ($total_pages < 1) $total_pages = 1;
    if ($page > $total_pages) {
        $page = $total_pages;
        $offset = ($page - 1) * $sales_per_page;
    }
    
    // Fetch detailed sales with relations
    $query = "SELECT s.*, m.name AS medicine_name, u.username AS seller_name 
              FROM `sales` s 
              LEFT JOIN `medicines` m ON s.medicine_id = m.id 
              LEFT JOIN `users` u ON s.user_id = u.id 
              ORDER BY s.id DESC 
              LIMIT :limit OFFSET :offset";
    $stmt = $pdo->prepare($query);
    $stmt->bindValue(':limit', $sales_per_page, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $sales = $stmt->fetchAll();
    
    // Fetch all active medicines for the sale form dropdown
    $meds_stmt = $pdo->query("SELECT `id`, `name`, `price`, `stock_quantity` FROM `medicines` WHERE `stock_quantity` > 0 ORDER BY `name` ASC");
    $active_medicines = $meds_stmt->fetchAll();
    
} catch (PDOException $e) {
    error_log("Sales data load error: " . $e->getMessage());
    $sales = [];
    $active_medicines = [];
}

// Check for direct-sale shortcuts from catalog link
$preselected_medicine_id = isset($_GET['add_sale']) ? (int)$_GET['add_sale'] : 0;
?>

<div class="header-actions">
    <div class="page-title">
        <h1>Sales & Transactions</h1>
        <p>Record transactions and review transaction history</p>
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
    <!-- Left Panel: Transaction Ledger -->
    <div class="card-premium">
        <h3 class="card-title">Transaction Ledger</h3>
        <div class="table-container">
            <table class="table-premium">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Medicine</th>
                        <th>Sold By</th>
                        <th style="text-align: center;">Qty</th>
                        <th>Total Price</th>
                        <th>Date & Time</th>
                        <?php if (isAdmin()): ?>
                            <th style="text-align: right;">Action</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($sales)): ?>
                        <tr>
                            <td colspan="<?php echo isAdmin() ? '7' : '6'; ?>" style="text-align: center; color: var(--text-secondary);">No sales transactions logged.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($sales as $sale): ?>
                            <tr>
                                <td>#<?php echo $sale['id']; ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($sale['medicine_name'] ?? '[Deleted Medicine]'); ?></strong>
                                </td>
                                <td><?php echo htmlspecialchars($sale['seller_name'] ?? '[Deleted User]'); ?></td>
                                <td style="text-align: center;"><?php echo $sale['quantity']; ?></td>
                                <td style="color: var(--accent-primary); font-weight: 600;">KSh <?php echo number_format($sale['total_price'], 2); ?></td>
                                <td style="font-size: 0.85rem; color: var(--text-secondary);"><?php echo $sale['sale_date']; ?></td>
                                <?php if (isAdmin()): ?>
                                    <td style="text-align: right;">
                                        <a href="sales.php?delete_sale=<?php echo $sale['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Cancel this sale? This will restore the stock and log the event.');">
                                            Cancel Sale
                                        </a>
                                    </td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Pagination links -->
        <?php if ($total_pages > 1): ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="sales.php?page=<?php echo $page - 1; ?>" class="pagination-item">&laquo;</a>
                <?php endif; ?>
                
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <a href="sales.php?page=<?php echo $i; ?>" class="pagination-item <?php echo $page === $i ? 'active' : ''; ?>">
                        <?php echo $i; ?>
                    </a>
                <?php endfor; ?>
                
                <?php if ($page < $total_pages): ?>
                    <a href="sales.php?page=<?php echo $page + 1; ?>" class="pagination-item">&raquo;</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Right Panel: Record New Sale Form -->
    <div>
        <div class="card-premium" style="position: sticky; top: 20px;">
            <h3 class="card-title">Record New Sale</h3>
            <form action="sales.php" method="POST" class="validated-form">
                <input type="hidden" name="action" value="record_sale">
                
                <div class="form-group">
                    <label for="medicine_id">Select Medicine:</label>
                    <select name="medicine_id" id="medicine_id" class="form-control" required onchange="updatePriceCalculator()">
                        <option value="" data-price="0" data-stock="0">Choose item...</option>
                        <?php foreach ($active_medicines as $med): ?>
                            <option value="<?php echo $med['id']; ?>" 
                                    data-price="<?php echo $med['price']; ?>" 
                                    data-stock="<?php echo $med['stock_quantity']; ?>"
                                    <?php echo $preselected_medicine_id === $med['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($med['name']); ?> (Qty: <?php echo $med['stock_quantity']; ?> left)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="quantity">Quantity to Sell *:</label>
                    <input type="number" min="1" name="quantity" id="quantity" class="form-control" value="1" required oninput="updatePriceCalculator()">
                </div>
                
                <div style="background-color: rgba(255,255,255,0.02); padding: 1.25rem; border-radius: var(--radius-sm); border: 1px solid var(--border-color); margin-bottom: 1.5rem;">
                    <div style="display: flex; justify-content: space-between; font-size: 0.9rem; margin-bottom: 0.5rem; color: var(--text-secondary);">
                        <span>Unit Price:</span>
                        <span id="calc-unit-price">KSh 0.00</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; font-size: 1.1rem; font-weight: 700;">
                        <span>Total Price:</span>
                        <span id="calc-total" style="color: var(--accent-primary);">KSh 0.00</span>
                    </div>
                    <div id="calc-stock-warning" style="color: var(--danger); font-size: 0.8rem; margin-top: 0.5rem; text-align: center; display: none;"></div>
                </div>
                
                <button type="submit" class="btn btn-primary" style="width: 100%;">Complete Transaction</button>
            </form>
        </div>
    </div>
</div>

<script>
    // Live client side total price calculator
    function updatePriceCalculator() {
        const select = document.getElementById("medicine_id");
        const selectedOption = select.options[select.selectedIndex];
        
        const price = parseFloat(selectedOption.getAttribute("data-price")) || 0;
        const stock = parseInt(selectedOption.getAttribute("data-stock")) || 0;
        const quantityInput = document.getElementById("quantity");
        const quantity = parseInt(quantityInput.value) || 0;
        
        // Show unit price
        document.getElementById("calc-unit-price").innerText = "KSh " + price.toFixed(2);
        
        // Calculate total
        const total = price * quantity;
        document.getElementById("calc-total").innerText = "KSh " + total.toFixed(2);
        
        // Check stock levels client side
        const warning = document.getElementById("calc-stock-warning");
        if (quantity > stock) {
            warning.innerText = "Error: Entered quantity exceeds available stock (" + stock + " items remaining).";
            warning.style.display = "block";
        } else {
            warning.style.display = "none";
        }
    }
    
    // Initialize calculator on page load (e.g. if loaded via preselected medicine)
    document.addEventListener("DOMContentLoaded", function() {
        updatePriceCalculator();
    });
</script>

<?php include_once __DIR__ . '/includes/footer.php'; ?>
