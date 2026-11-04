<?php
require_once __DIR__ . "/../settings/session.php";
require_once __DIR__ . "/../settings/auth.php";
require_once __DIR__ . "/../settings/db.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../brands.php");
    exit;
}

$id   = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$name = trim($_POST['name'] ?? '');
$errors = [];

// Validation
if ($id <= 0) {
    $errors[] = "Invalid brand selected.";
}
if ($name === '') {
    $errors[] = "Brand name is required.";
} elseif (mb_strlen($name) > 120) {
    $errors[] = "Brand name cannot exceed 120 characters.";
}

if (!empty($errors)) {
    $_SESSION['errors'] = $errors;
    header("Location: ../brands.php");
    exit;
}

// Check brand exists
$chk = mysqli_prepare($conn, "SELECT id FROM brands WHERE id = ?");
if ($chk) {
    mysqli_stmt_bind_param($chk, 'i', $id);
    mysqli_stmt_execute($chk);
    mysqli_stmt_store_result($chk);
    if (mysqli_stmt_num_rows($chk) === 0) {
        mysqli_stmt_close($chk);
        $_SESSION['errors'] = ["Brand not found."];
        header("Location: ../brands.php");
        exit;
    }
    mysqli_stmt_close($chk);
}

// Prevent duplicate brand name on other brands
$dupStmt = mysqli_prepare($conn, "SELECT id FROM brands WHERE LOWER(TRIM(name)) = LOWER(?) AND id != ?");
if ($dupStmt) {
    mysqli_stmt_bind_param($dupStmt, 'si', $name, $id);
    mysqli_stmt_execute($dupStmt);
    mysqli_stmt_store_result($dupStmt);

    if (mysqli_stmt_num_rows($dupStmt) > 0) {
        mysqli_stmt_close($dupStmt);
        $_SESSION['errors'] = ["A brand named '" . htmlspecialchars($name) . "' already exists."];
        header("Location: ../brands.php");
        exit;
    }
    mysqli_stmt_close($dupStmt);
}

// Update brand
$updateStmt = mysqli_prepare($conn, "UPDATE brands SET name = ? WHERE id = ?");
if ($updateStmt) {
    mysqli_stmt_bind_param($updateStmt, 'si', $name, $id);
    if (mysqli_stmt_execute($updateStmt)) {
        $_SESSION['success'] = "Brand '" . htmlspecialchars($name) . "' has been updated successfully.";
    } else {
        $_SESSION['errors'] = ["Failed to update brand: " . mysqli_error($conn)];
    }
    mysqli_stmt_close($updateStmt);
} else {
    $_SESSION['errors'] = ["Database error: " . mysqli_error($conn)];
}

header("Location: ../brands.php");
exit;

