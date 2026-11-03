<?php
require_once __DIR__ . "/../settings/session.php";
require_once __DIR__ . "/../settings/auth.php";
require_once __DIR__ . "/../settings/db.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../product-add.php");
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

// 3. Check SKU duplicate in products table
if ($sku !== '') {
    $skuStmt = mysqli_prepare($conn, "SELECT id FROM products WHERE LOWER(TRIM(sku)) = LOWER(?)");
    if ($skuStmt) {
        mysqli_stmt_bind_param($skuStmt, 's', $sku);
        mysqli_stmt_execute($skuStmt);
        mysqli_stmt_store_result($skuStmt);
        if (mysqli_stmt_num_rows($skuStmt) > 0) {
            $errors[] = "Product SKU '$sku' already exists. Please choose a unique SKU.";
        }
        mysqli_stmt_close($skuStmt);
    }
}

// 4. Handle Image Upload
$imageUrl = '';
if (!isset($_FILES['image']) || $_FILES['image']['error'] === UPLOAD_ERR_NO_FILE) {
    $errors[] = "Product image is required.";
} elseif ($_FILES['image']['error'] !== UPLOAD_ERR_OK) {
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
            $imageUrl = 'uploads/products/' . $uniqueFileName;
        } else {
            $errors[] = "Failed to save the uploaded image to the server directory.";
        }
    }
}

// Redirect if errors
if (!empty($errors)) {
    $_SESSION['errors'] = $errors;
    header("Location: ../product-add.php");
    exit;
}

// 5. Generate SEO-friendly slug
function createSlug($text) {
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
    $text = preg_replace('~[^-\w]+~', '', $text);
    $text = trim($text, '-');
    $text = preg_replace('~-+~', '-', $text);
    $text = strtolower($text);
    return $text ?: 'product-' . time();
}

$baseSlug = createSlug($name);
$slug = $baseSlug;

// Ensure unique slug
$slugCheck = mysqli_prepare($conn, "SELECT id FROM products WHERE slug = ?");
if ($slugCheck) {
    mysqli_stmt_bind_param($slugCheck, 's', $slug);
    mysqli_stmt_execute($slugCheck);
    mysqli_stmt_store_result($slugCheck);
    if (mysqli_stmt_num_rows($slugCheck) > 0) {
        $slug = $baseSlug . '-' . substr(uniqid(), -5);
    }
    mysqli_stmt_close($slugCheck);
}

// 6. Calculate total stock quantity from variant matrix if provided
$totalStock = 0;
$variantsData = $_POST['variants'] ?? [];
if (!empty($variantsData) && is_array($variantsData)) {
    foreach ($variantsData as $v) {
        $stock = isset($v['stock']) ? max(0, (int)$v['stock']) : 0;
        $totalStock += $stock;
    }
}

// 7. Insert Product into products table
$insertSql = "INSERT INTO products (
    sku, name, slug, brand_id, category_id,
    price_pkr, original_price_pkr, cost_price_pkr,
    stock_quantity, material, sole_type, occasion,
    short_description, description, image_url,
    is_sale, is_new, status
) VALUES (
    ?, ?, ?, ?, ?,
    ?, ?, ?,
    ?, ?, ?, ?,
    ?, ?, ?,
    ?, ?, ?
)";

$stmt = mysqli_prepare($conn, $insertSql);
if (!$stmt) {
    $_SESSION['errors'] = ["Database error preparing product insert: " . mysqli_error($conn)];
    header("Location: ../product-add.php");
    exit;
}

mysqli_stmt_bind_param(
    $stmt,
    'sssiidddissssssiis',
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
    $status
);

if (mysqli_stmt_execute($stmt)) {
    $newProductId = mysqli_insert_id($conn);
    mysqli_stmt_close($stmt);

    // 8. Insert Product Variants if product_variants table exists
    if ($newProductId && !empty($variantsData) && is_array($variantsData)) {
        $varSql = "INSERT INTO product_variants 
                   (product_id, sku_variant, size_eu, color_name, stock_quantity, status) 
                   VALUES (?, ?, ?, ?, ?, ?)";
        $varStmt = @mysqli_prepare($conn, $varSql);
        if ($varStmt) {
            foreach ($variantsData as $sizeKey => $v) {
                $sizeEu    = (int)($v['size'] ?? $sizeKey);
                $color     = trim($v['color'] ?? 'Standard');
                $stockQty  = isset($v['stock']) ? max(0, (int)$v['stock']) : 0;
                $varSku    = $sku . '-' . $sizeEu;
                $varStatus = $stockQty > 5 ? 'In Stock' : ($stockQty > 0 ? 'Low Stock' : 'Out of Stock');

                mysqli_stmt_bind_param($varStmt, 'isisis', $newProductId, $varSku, $sizeEu, $color, $stockQty, $varStatus);
                @mysqli_stmt_execute($varStmt);
            }
            mysqli_stmt_close($varStmt);
        }
    }

    $_SESSION['success'] = "Footwear model '" . htmlspecialchars($name) . "' (SKU: $sku) has been created successfully!";
    header("Location: ../products.php");
    exit;
} else {
    $_SESSION['errors'] = ["Failed to create product: " . mysqli_error($conn)];
    mysqli_stmt_close($stmt);
    header("Location: ../product-add.php");
    exit;
}

