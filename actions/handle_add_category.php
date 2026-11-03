<?php
require_once __DIR__ . "/../settings/session.php";
require_once __DIR__ . "/../settings/auth.php";
require_once __DIR__ . "/../settings/db.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../categories.php");
    exit;
}

$name = trim($_POST['name'] ?? '');
$errors = [];

// Validation
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

// Prevent duplicate category (case-insensitive)
$stmt = mysqli_prepare($conn, "SELECT id FROM categories WHERE LOWER(TRIM(name)) = LOWER(?)");
if ($stmt) {
    mysqli_stmt_bind_param($stmt, 's', $name);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);

    if (mysqli_stmt_num_rows($stmt) > 0) {
        mysqli_stmt_close($stmt);
        $_SESSION['errors'] = ["A category named '" . htmlspecialchars($name) . "' already exists."];
        header("Location: ../categories.php");
        exit;
    }
    mysqli_stmt_close($stmt);
}

// Insert into categories table
$insertStmt = mysqli_prepare($conn, "INSERT INTO categories (name) VALUES (?)");
if ($insertStmt) {
    mysqli_stmt_bind_param($insertStmt, 's', $name);
    if (mysqli_stmt_execute($insertStmt)) {
        $_SESSION['success'] = "Category '" . htmlspecialchars($name) . "' created successfully.";
    } else {
        $_SESSION['errors'] = ["Failed to create category: " . mysqli_error($conn)];
    }
    mysqli_stmt_close($insertStmt);
} else {
    $_SESSION['errors'] = ["Database query error: " . mysqli_error($conn)];
}

header("Location: ../categories.php");
exit;

