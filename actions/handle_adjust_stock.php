<?php
require_once __DIR__ . '/../settings/session.php';
require_once __DIR__ . '/../settings/auth.php';
require_once __DIR__ . '/../settings/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../inventory.php");
    exit();
}

$productId     = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
$adjustMode    = trim($_POST['adjust_mode'] ?? 'quick'); // 'quick' or 'direct_variants'
$referenceNote = trim($_POST['reference_note'] ?? '');
$adminId       = $_SESSION['user_id'] ?? null;

$errors = [];

if ($productId <= 0) {
    $_SESSION['errors'] = ["Please select a valid footwear model."];
    header("Location: ../inventory.php");
    exit();
}

// Fetch current product details
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

// -------------------------------------------------------------
// MODE 1: Direct Variant Stocks Edit (By Size)
// -------------------------------------------------------------
if ($adjustMode === 'direct_variants' && !empty($_POST['variant_stocks']) && is_array($_POST['variant_stocks'])) {
    $anyChanged = false;
    foreach ($_POST['variant_stocks'] as $varId => $newQty) {
        $varId  = (int)$varId;
        $newQty = max(0, (int)$newQty);

        // Fetch current variant record
        $vRes = mysqli_query($conn, "SELECT id, size_eu, color_name, stock_quantity FROM product_variants WHERE id = $varId AND product_id = $productId");
        if ($vRes && $vRow = mysqli_fetch_assoc($vRes)) {
            $oldQty = (int)$vRow['stock_quantity'];
            $diff   = $newQty - $oldQty;

            if ($diff !== 0) {
                $anyChanged = true;
                $varStatus  = ($newQty > 5) ? 'In Stock' : (($newQty > 0) ? 'Low Stock' : 'Out of Stock');
                $changeType = ($diff > 0) ? 'Restock' : 'Manual Adjustment';
                $noteText   = !empty($referenceNote) ? $referenceNote : "Direct size adjustment (EU {$vRow['size_eu']})";

                // Update variant
                $upVar = mysqli_prepare($conn, "UPDATE product_variants SET stock_quantity = ?, status = ? WHERE id = ?");
                if ($upVar) {
                    mysqli_stmt_bind_param($upVar, "isi", $newQty, $varStatus, $varId);
                    mysqli_stmt_execute($upVar);
                    mysqli_stmt_close($upVar);
                }

                // Log change
                $logStmt = mysqli_prepare($conn, "INSERT INTO inventory_logs (product_id, variant_id, change_type, quantity_change, previous_stock, new_stock, reference_note, recorded_by_admin_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                if ($logStmt) {
                    mysqli_stmt_bind_param($logStmt, "iisiissi", $productId, $varId, $changeType, $diff, $oldQty, $newQty, $noteText, $adminId);
                    mysqli_stmt_execute($logStmt);
                    mysqli_stmt_close($logStmt);
                }
            }
        }
    }

    // Recalculate total product stock from variants
    $sumRes = mysqli_query($conn, "SELECT COALESCE(SUM(stock_quantity), 0) AS total FROM product_variants WHERE product_id = $productId");
    $newTotalStock = ($sumRes && $sRow = mysqli_fetch_assoc($sumRes)) ? (int)$sRow['total'] : $currentStock;
    $newProdStatus = ($newTotalStock > 0) ? 'Active' : 'Out of Stock';

    $upProd = mysqli_prepare($conn, "UPDATE products SET stock_quantity = ?, status = ? WHERE id = ?");
    if ($upProd) {
        mysqli_stmt_bind_param($upProd, "isi", $newTotalStock, $newProdStatus, $productId);
        mysqli_stmt_execute($upProd);
        mysqli_stmt_close($upProd);
    }

    $_SESSION['success'] = "Variant sizes stock updated for \"{$productName}\"! New total stock: {$newTotalStock} pairs.";
    header("Location: ../inventory.php");
    exit();
}

// -------------------------------------------------------------
// MODE 2: Quick Adjustment (+ / -) for Variant or Overall Stock
// -------------------------------------------------------------
$variantId  = isset($_POST['variant_id']) ? (int)$_POST['variant_id'] : 0;
$actionType = trim($_POST['action_type'] ?? 'add'); // 'add' or 'subtract'
$quantity   = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 0;

if ($quantity <= 0) {
    $_SESSION['errors'] = ["Quantity must be at least 1 pair."];
    header("Location: ../inventory.php");
    exit();
}

if ($variantId > 0) {
    // Single Variant Specific Adjustment
    $vRes = mysqli_query($conn, "SELECT id, size_eu, color_name, stock_quantity FROM product_variants WHERE id = $variantId AND product_id = $productId");
    if (!$vRes || !($vRow = mysqli_fetch_assoc($vRes))) {
        $_SESSION['errors'] = ["Selected size variant does not belong to this footwear model."];
        header("Location: ../inventory.php");
        exit();
    }

    $oldVarStock = (int)$vRow['stock_quantity'];

    if ($actionType === 'subtract') {
        if ($quantity > $oldVarStock) {
            $_SESSION['errors'] = ["Cannot deduct {$quantity} pairs from Size EU {$vRow['size_eu']} because current stock is only {$oldVarStock} pairs."];
            header("Location: ../inventory.php");
            exit();
        }
        $newVarStock    = $oldVarStock - $quantity;
        $quantityChange = -$quantity;
        $changeType     = 'Damaged / Wastage';
    } else {
        $newVarStock    = $oldVarStock + $quantity;
        $quantityChange = $quantity;
        $changeType     = 'Restock';
    }

    $varStatus = ($newVarStock > 5) ? 'In Stock' : (($newVarStock > 0) ? 'Low Stock' : 'Out of Stock');

    // Update variant record
    $upVar = mysqli_prepare($conn, "UPDATE product_variants SET stock_quantity = ?, status = ? WHERE id = ?");
    if ($upVar) {
        mysqli_stmt_bind_param($upVar, "isi", $newVarStock, $varStatus, $variantId);
        mysqli_stmt_execute($upVar);
        mysqli_stmt_close($upVar);
    }

    // Recalculate total product stock
    $sumRes = mysqli_query($conn, "SELECT COALESCE(SUM(stock_quantity), 0) AS total FROM product_variants WHERE product_id = $productId");
    $newTotalStock = ($sumRes && $sRow = mysqli_fetch_assoc($sumRes)) ? (int)$sRow['total'] : $currentStock;
    $newProdStatus = ($newTotalStock > 0) ? 'Active' : 'Out of Stock';

    $upProd = mysqli_prepare($conn, "UPDATE products SET stock_quantity = ?, status = ? WHERE id = ?");
    if ($upProd) {
        mysqli_stmt_bind_param($upProd, "isi", $newTotalStock, $newProdStatus, $productId);
        mysqli_stmt_execute($upProd);
        mysqli_stmt_close($upProd);
    }

    // Insert log
    $noteText = !empty($referenceNote) ? $referenceNote : "Stock adjustment on Size EU {$vRow['size_eu']}";
    $logStmt  = mysqli_prepare($conn, "INSERT INTO inventory_logs (product_id, variant_id, change_type, quantity_change, previous_stock, new_stock, reference_note, recorded_by_admin_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    if ($logStmt) {
        mysqli_stmt_bind_param($logStmt, "iisiissi", $productId, $variantId, $changeType, $quantityChange, $oldVarStock, $newVarStock, $noteText, $adminId);
        mysqli_stmt_execute($logStmt);
        mysqli_stmt_close($logStmt);
    }

    $actionWord = ($actionType === 'add') ? "added to" : "deducted from";
    $_SESSION['success'] = "{$quantity} pairs {$actionWord} Size EU {$vRow['size_eu']} of \"{$productName}\"! New size stock: {$newVarStock} pairs (Total model stock: {$newTotalStock} pairs).";
    header("Location: ../inventory.php");
    exit();

} else {
    // General Product Adjustment (No specific size selected)
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

    $newStatus = ($newStock <= 0) ? 'Out of Stock' : 'Active';

    // Update products table
    $updateStmt = mysqli_prepare($conn, "UPDATE products SET stock_quantity = ?, status = ? WHERE id = ?");
    if ($updateStmt) {
        mysqli_stmt_bind_param($updateStmt, "isi", $newStock, $newStatus, $productId);
        mysqli_stmt_execute($updateStmt);
        mysqli_stmt_close($updateStmt);
    }

    // Log in inventory_logs table with variant_id = null
    $varNull = null;
    $logStmt = mysqli_prepare($conn, "INSERT INTO inventory_logs (product_id, variant_id, change_type, quantity_change, previous_stock, new_stock, reference_note, recorded_by_admin_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    if ($logStmt) {
        mysqli_stmt_bind_param($logStmt, "iisiissi", $productId, $varNull, $changeType, $quantityChange, $currentStock, $newStock, $referenceNote, $adminId);
        mysqli_stmt_execute($logStmt);
        mysqli_stmt_close($logStmt);
    }

    $actionLabel = ($actionType === 'add') ? "added to" : "deducted from";
    $_SESSION['success'] = "Inventory updated! {$quantity} pairs {$actionLabel} \"{$productName}\". New stock: {$newStock} pairs.";
    header("Location: ../inventory.php");
    exit();
}
