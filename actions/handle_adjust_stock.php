<?php
require_once __DIR__ . '/../settings/session.php';
require_once __DIR__ . '/../settings/auth.php';
require_once __DIR__ . '/../settings/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../inventory.php");
    exit();
}

$productId     = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
$actionType    = trim($_POST['action_type'] ?? 'add'); // 'add' or 'subtract'
$quantity      = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 0;
$referenceNote = trim($_POST['reference_note'] ?? '');
$adminId       = $_SESSION['user_id'] ?? null;

$errors = [];

if ($productId <= 0) {
    $errors[] = "Please select a valid footwear model.";
}

if ($quantity <= 0) {
    $errors[] = "Quantity must be at least 1 pair.";
}

// Fetch current product stock
$currentStock = 0;
$productName  = '';
$stmt = mysqli_prepare($conn, "SELECT id, name, stock_quantity, low_stock_threshold FROM products WHERE id = ?");
if ($stmt) {
    mysqli_stmt_bind_param($stmt, "i", $productId);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    if ($row = mysqli_fetch_assoc($res)) {
        $currentStock      = (int)$row['stock_quantity'];
        $productName       = $row['name'];
        $lowStockThreshold = (int)$row['low_stock_threshold'];
    } else {
        $errors[] = "Selected footwear model does not exist.";
    }
    mysqli_stmt_close($stmt);
} else {
    $errors[] = "Database error verifying product.";
}

if (!empty($errors)) {
    $_SESSION['errors'] = $errors;
    header("Location: ../inventory.php");
    exit();
}

// Calculate new stock
if ($actionType === 'subtract') {
    if ($quantity > $currentStock) {
        $_SESSION['errors'] = ["Cannot deduct {$quantity} pairs because current stock is only {$currentStock} pairs."];
        header("Location: ../inventory.php");
        exit();
    }
    $newStock       = $currentStock - $quantity;
    $quantityChange = -$quantity;
    $changeType     = 'Damaged / Wastage';
} else {
    $newStock       = $currentStock + $quantity;
    $quantityChange = $quantity;
    $changeType     = 'Restock';
}

// Determine new product status
$newStatus = 'Active';
if ($newStock <= 0) {
    $newStatus = 'Out of Stock';
}

// Update products table
$updateStmt = mysqli_prepare($conn, "UPDATE products SET stock_quantity = ?, status = ? WHERE id = ?");
if ($updateStmt) {
    mysqli_stmt_bind_param($updateStmt, "isi", $newStock, $newStatus, $productId);
    mysqli_stmt_execute($updateStmt);
    mysqli_stmt_close($updateStmt);
}

// Log in inventory_logs table
$logStmt = mysqli_prepare($conn, "INSERT INTO inventory_logs (product_id, change_type, quantity_change, previous_stock, new_stock, reference_note, recorded_by_admin_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
if ($logStmt) {
    mysqli_stmt_bind_param($logStmt, "isiissi", $productId, $changeType, $quantityChange, $currentStock, $newStock, $referenceNote, $adminId);
    mysqli_stmt_execute($logStmt);
    mysqli_stmt_close($logStmt);
}

$actionLabel = ($actionType === 'add') ? "added to" : "deducted from";
$_SESSION['success'] = "Inventory updated! {$quantity} pairs {$actionLabel} \"{$productName}\". New stock: {$newStock} pairs.";
header("Location: ../inventory.php");
exit();

