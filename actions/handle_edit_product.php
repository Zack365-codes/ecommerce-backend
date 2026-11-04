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

// Fetch existing product
$checkStmt = mysqli_prepare($conn, "SELECT * FROM products WHERE id = ?");
mysqli_stmt_bind_param($checkStmt, 'i', $id);
mysqli_stmt_execute($checkStmt);
$checkRes = mysqli_stmt_get_result($checkStmt);
$existingProduct = mysqli_fetch_assoc($checkRes);
mysqli_stmt_close($checkStmt);

if (!$existingProduct) {
    $_SESSION['errors'] = ["Product does not exist."];
    header("Location: ../products.php");
    exit;
}

$errors = [];

// 1. Sanitize text fields
$name              = trim($_POST['name'] ?? '');
$sku               = strtoupper(trim($_POST['sku'] ?? ''));
$category_id       = !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null;
$brand_id          = !empty($_POST['brand_id']) ? (int)$_POST['brand_id'] : null;
$price_pkr         = isset($_POST['price_pkr']) && is_numeric($_POST['price_pkr']) ? (float)$_POST['price_pkr'] : null;
$original_price_pkr= !empty($_POST['original_price_pkr']) && is_numeric($_POST['original_price_pkr']) ? (float)$_POST['original_price_pkr'] : null;
$cost_price_pkr    = !empty($_POST['cost_price_pkr']) && is_numeric($_POST['cost_price_pkr']) ? (float)$_POST['cost_price_pkr'] : null;
$short_description = trim($_POST['short_description'] ?? '');
$description       = trim($_POST['description'] ?? '');
$material          = trim($_POST['material'] ?? '');
$sole_type         = trim($_POST['sole_type'] ?? '');
$occasion          = trim($_POST['occasion'] ?? '');
$status            = in_array($_POST['status'] ?? '', ['Active', 'Draft', 'Archived', 'Out of Stock'], true) ? $_POST['status'] : 'Active';
$is_sale           = isset($_POST['is_sale']) ? 1 : 0;
$is_new            = isset($_POST['is_new']) ? 1 : 0;

// 2. Validate required fields
if ($name === '') {
    $errors[] = "Product name is required.";
}
if ($sku === '') {
    $errors[] = "SKU code is required.";
}
if ($price_pkr === null || $price_pkr < 0) {
    $errors[] = "A valid selling price (PKR) is required.";
}

// 3. Check SKU duplicate (excluding this product)
if ($sku !== '') {
    $skuStmt = mysqli_prepare($conn, "SELECT id FROM products WHERE LOWER(TRIM(sku)) = LOWER(?) AND id != ?");
    if ($skuStmt) {
        mysqli_stmt_bind_param($skuStmt, 'si', $sku, $id);
        mysqli_stmt_execute($skuStmt);
        mysqli_stmt_store_result($skuStmt);
        if (mysqli_stmt_num_rows($skuStmt) > 0) {
            $errors[] = "Product SKU '$sku' is already used by another product.";
        }
        mysqli_stmt_close($skuStmt);
    }
}

// 4. Handle Optional Image Replacement
$imageUrl = $existingProduct['image_url'];

if (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
    if ($_FILES['image']['error'] !== UPLOAD_ERR_OK) {
        $errors[] = "Image upload failed with error code: " . $_FILES['image']['error'];
    } else {
        $fileTmpPath   = $_FILES['image']['tmp_name'];
        $fileName      = $_FILES['image']['name'];
        $fileSize      = $_FILES['image']['size'];
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];
        $allowedMimes      = ['image/jpeg', 'image/png', 'image/webp'];

        $fileMime = '';
        if (function_exists('mime_content_type')) {
            $fileMime = mime_content_type($fileTmpPath);
        } elseif (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $fileMime = finfo_file($finfo, $fileTmpPath);
            finfo_close($finfo);
        }

        if (!in_array($fileExtension, $allowedExtensions, true) || ($fileMime && !in_array($fileMime, $allowedMimes, true))) {
            $errors[] = "Invalid image type. Only JPG, PNG, and WEBP formats are allowed.";
        }

        if ($fileSize > 5 * 1024 * 1024) {
            $errors[] = "Image size cannot exceed 5MB.";
        }

        if (empty($errors)) {
            $uploadDir = __DIR__ . '/../uploads/products/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $uniqueFileName = 'prod_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $fileExtension;
            $destPath       = $uploadDir . $uniqueFileName;

            if (move_uploaded_file($fileTmpPath, $destPath)) {
                // Delete old image if stored in uploads
                if ($existingProduct['image_url'] && strpos($existingProduct['image_url'], 'uploads/products/') === 0) {
                    $oldFilePath = __DIR__ . '/../' . $existingProduct['image_url'];
                    if (file_exists($oldFilePath)) {
                        @unlink($oldFilePath);
                    }
                }
                $imageUrl = 'uploads/products/' . $uniqueFileName;
            } else {
                $errors[] = "Failed to save the new uploaded image.";
            }
        }
    }
}

if (!empty($errors)) {
    $_SESSION['errors'] = $errors;
    header("Location: ../product-edit.php?id=" . $id);
    exit;
}

// 5. Slug Handling
function createSlug($text) {
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
    $text = preg_replace('~[^-\w]+~', '', $text);
    $text = trim($text, '-');
    $text = preg_replace('~-+~', '-', $text);
    $text = strtolower($text);
    return $text ?: 'product-' . time();
}

$slug = $existingProduct['slug'];
if ($name !== $existingProduct['name']) {
    $baseSlug = createSlug($name);
    $slug = $baseSlug;
    $slugCheck = mysqli_prepare($conn, "SELECT id FROM products WHERE slug = ? AND id != ?");
    if ($slugCheck) {
        mysqli_stmt_bind_param($slugCheck, 'si', $slug, $id);
        mysqli_stmt_execute($slugCheck);
        mysqli_stmt_store_result($slugCheck);
        if (mysqli_stmt_num_rows($slugCheck) > 0) {
            $slug = $baseSlug . '-' . substr(uniqid(), -5);
        }
        mysqli_stmt_close($slugCheck);
    }
}

// 6. Calculate total stock quantity from variant matrix
$totalStock = 0;
$variantsData = $_POST['variants'] ?? [];
if (!empty($variantsData) && is_array($variantsData)) {
    foreach ($variantsData as $v) {
        $stock = isset($v['stock']) ? max(0, (int)$v['stock']) : 0;
        $totalStock += $stock;
    }
}

// 7. Update Product in products table
$updateSql = "UPDATE products SET
    sku = ?,
    name = ?,
    slug = ?,
    brand_id = ?,
    category_id = ?,
    price_pkr = ?,
    original_price_pkr = ?,
    cost_price_pkr = ?,
    stock_quantity = ?,
    material = ?,
    sole_type = ?,
    occasion = ?,
    short_description = ?,
    description = ?,
    image_url = ?,
    is_sale = ?,
    is_new = ?,
    status = ?
WHERE id = ?";

$stmt = mysqli_prepare($conn, $updateSql);
if (!$stmt) {
    $_SESSION['errors'] = ["Database error preparing product update: " . mysqli_error($conn)];
    header("Location: ../product-edit.php?id=" . $id);
    exit;
}

mysqli_stmt_bind_param(
    $stmt,
    'sssiidddissssssiisi',
    $sku,
    $name,
    $slug,
    $brand_id,
    $category_id,
    $price_pkr,
    $original_price_pkr,
    $cost_price_pkr,
    $totalStock,
    $material,
    $sole_type,
    $occasion,
    $short_description,
    $description,
    $imageUrl,
    $is_sale,
    $is_new,
    $status,
    $id
);

if (mysqli_stmt_execute($stmt)) {
    mysqli_stmt_close($stmt);

    // 8. Update / Upsert Product Variants
    if (!empty($variantsData) && is_array($variantsData)) {
        foreach ($variantsData as $sizeKey => $v) {
            $sizeEu    = (int)($v['size'] ?? $sizeKey);
            $color     = trim($v['color'] ?? 'Nero Black');
            $stockQty  = isset($v['stock']) ? max(0, (int)$v['stock']) : 0;
            $varSku    = $sku . '-' . $sizeEu;
            $varStatus = $stockQty > 5 ? 'In Stock' : ($stockQty > 0 ? 'Low Stock' : 'Out of Stock');

            // Check if variant exists for this product and size
            $findStmt = @mysqli_prepare($conn, "SELECT id FROM product_variants WHERE product_id = ? AND size_eu = ?");
            if ($findStmt) {
                mysqli_stmt_bind_param($findStmt, 'ii', $id, $sizeEu);
                mysqli_stmt_execute($findStmt);
                mysqli_stmt_store_result($findStmt);
                $variantExists = mysqli_stmt_num_rows($findStmt) > 0;
                mysqli_stmt_close($findStmt);

                if ($variantExists) {
                    $upVar = @mysqli_prepare($conn, "UPDATE product_variants SET color_name = ?, stock_quantity = ?, status = ?, sku_variant = ? WHERE product_id = ? AND size_eu = ?");
                    if ($upVar) {
                        mysqli_stmt_bind_param($upVar, 'sissii', $color, $stockQty, $varStatus, $varSku, $id, $sizeEu);
                        @mysqli_stmt_execute($upVar);
                        mysqli_stmt_close($upVar);
                    }
                } else {
                    $inVar = @mysqli_prepare($conn, "INSERT INTO product_variants (product_id, sku_variant, size_eu, color_name, stock_quantity, status) VALUES (?, ?, ?, ?, ?, ?)");
                    if ($inVar) {
                        mysqli_stmt_bind_param($inVar, 'isisis', $id, $varSku, $sizeEu, $color, $stockQty, $varStatus);
                        @mysqli_stmt_execute($inVar);
                        mysqli_stmt_close($inVar);
                    }
                }
            }
        }
    }

    $_SESSION['success'] = "Footwear model '" . htmlspecialchars($name) . "' has been updated successfully!";
    header("Location: ../products.php");
    exit;
} else {
    $_SESSION['errors'] = ["Failed to update product: " . mysqli_error($conn)];
    mysqli_stmt_close($stmt);
    header("Location: ../product-edit.php?id=" . $id);
    exit;
}

