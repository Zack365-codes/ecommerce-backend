<?php
require_once __DIR__ . "/../settings/session.php";
require_once __DIR__ . "/../settings/auth.php";
require_once __DIR__ . "/../settings/db.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../products.php");
    exit;
}

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

if ($id <= 0) {
    $_SESSION['errors'] = ["Invalid product selected."];
    header("Location: ../products.php");
    exit;
}

// Fetch image to delete from disk if local
$imgStmt = mysqli_prepare($conn, "SELECT image_url, name FROM products WHERE id = ?");
if ($imgStmt) {
    mysqli_stmt_bind_param($imgStmt, 'i', $id);
    mysqli_stmt_execute($imgStmt);
    mysqli_stmt_bind_result($imgStmt, $imageUrl, $prodName);
    if (mysqli_stmt_fetch($imgStmt)) {
        mysqli_stmt_close($imgStmt);

        // Delete product
        $delStmt = mysqli_prepare($conn, "DELETE FROM products WHERE id = ?");
        if ($delStmt) {
            mysqli_stmt_bind_param($delStmt, 'i', $id);
            if (mysqli_stmt_execute($delStmt)) {
                // Delete image file if exists in uploads
                if ($imageUrl && strpos($imageUrl, 'uploads/products/') === 0) {
                    $fullPath = __DIR__ . '/../' . $imageUrl;
                    if (file_exists($fullPath)) {
                        @unlink($fullPath);
                    }
                }
                $_SESSION['success'] = "Product '" . htmlspecialchars($prodName) . "' was deleted successfully.";
            } else {
                $_SESSION['errors'] = ["Failed to delete product: " . mysqli_error($conn)];
            }
            mysqli_stmt_close($delStmt);
        }
    } else {
        mysqli_stmt_close($imgStmt);
        $_SESSION['errors'] = ["Product not found."];
    }
}

header("Location: ../products.php");
exit;

