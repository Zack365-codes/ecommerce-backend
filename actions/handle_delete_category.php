<?php
require_once __DIR__ . "/../settings/session.php";
require_once __DIR__ . "/../settings/auth.php";
require_once __DIR__ . "/../settings/db.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../categories.php");
    exit;
}

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

if ($id <= 0) {
    $_SESSION['errors'] = ["Invalid category selected."];
    header("Location: ../categories.php");
    exit;
}

// Check if any products are using this category
$checkStmt = mysqli_prepare($conn, "SELECT COUNT(*) FROM products WHERE category_id = ?");
if ($checkStmt) {
    mysqli_stmt_bind_param($checkStmt, 'i', $id);
    mysqli_stmt_execute($checkStmt);
    mysqli_stmt_bind_result($checkStmt, $productCount);
    mysqli_stmt_fetch($checkStmt);
    mysqli_stmt_close($checkStmt);

    if ($productCount > 0) {
        $_SESSION['errors'] = ["Cannot delete category because it is currently linked to $productCount product(s)."];
        header("Location: ../categories.php");
        exit;
    }
}

// Delete category
$delStmt = mysqli_prepare($conn, "DELETE FROM categories WHERE id = ?");
if ($delStmt) {
    mysqli_stmt_bind_param($delStmt, 'i', $id);
    if (mysqli_stmt_execute($delStmt)) {
        $_SESSION['success'] = "Category deleted successfully.";
    } else {
        $_SESSION['errors'] = ["Failed to delete category: " . mysqli_error($conn)];
    }
    mysqli_stmt_close($delStmt);
}

header("Location: ../categories.php");
exit;

