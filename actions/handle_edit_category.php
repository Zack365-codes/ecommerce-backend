<?php
require_once __DIR__ . "/../settings/session.php";
require_once __DIR__ . "/../settings/auth.php";
require_once __DIR__ . "/../settings/db.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../categories.php");
    exit;
}

$id   = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$name = trim($_POST['name'] ?? '');
$errors = [];

// Validation
if ($id <= 0) {
    $errors[] = "Invalid category selected.";
}
if ($name === '') {
    $errors[] = "Category name is required.";
} elseif (mb_strlen($name) > 100) {
    $errors[] = "Category name cannot exceed 100 characters.";
}

if (!empty($errors)) {
    $_SESSION['errors'] = $errors;
    header("Location: ../categories.php");
    exit;
}

// Check category exists
$chk = mysqli_prepare($conn, "SELECT id FROM categories WHERE id = ?");
if ($chk) {
    mysqli_stmt_bind_param($chk, 'i', $id);
    mysqli_stmt_execute($chk);
    mysqli_stmt_store_result($chk);
    if (mysqli_stmt_num_rows($chk) === 0) {
        mysqli_stmt_close($chk);
        $_SESSION['errors'] = ["Category not found."];
        header("Location: ../categories.php");
        exit;
    }
    mysqli_stmt_close($chk);
}

// Prevent duplicate category name on other categories
$dupStmt = mysqli_prepare($conn, "SELECT id FROM categories WHERE LOWER(TRIM(name)) = LOWER(?) AND id != ?");
if ($dupStmt) {
    mysqli_stmt_bind_param($dupStmt, 'si', $name, $id);
    mysqli_stmt_execute($dupStmt);
    mysqli_stmt_store_result($dupStmt);

    if (mysqli_stmt_num_rows($dupStmt) > 0) {
        mysqli_stmt_close($dupStmt);
        $_SESSION['errors'] = ["A category named '" . htmlspecialchars($name) . "' already exists."];
        header("Location: ../categories.php");
        exit;
    }
    mysqli_stmt_close($dupStmt);
}

// Update category
$updateStmt = mysqli_prepare($conn, "UPDATE categories SET name = ? WHERE id = ?");
if ($updateStmt) {
    mysqli_stmt_bind_param($updateStmt, 'si', $name, $id);
    if (mysqli_stmt_execute($updateStmt)) {
        $_SESSION['success'] = "Category '" . htmlspecialchars($name) . "' has been updated successfully.";
    } else {
        $_SESSION['errors'] = ["Failed to update category: " . mysqli_error($conn)];
    }
    mysqli_stmt_close($updateStmt);
} else {
    $_SESSION['errors'] = ["Database error: " . mysqli_error($conn)];
}

header("Location: ../categories.php");
exit;

