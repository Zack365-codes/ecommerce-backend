<?php
require_once __DIR__ . '/../settings/session.php';
require_once __DIR__ . '/../settings/auth.php';
require_once __DIR__ . '/../settings/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../settings.php");
    exit();
}

// 1. Collect form inputs
$brand_name                  = trim($_POST['brand_name'] ?? '');
$helpline_phone              = trim($_POST['helpline_phone'] ?? '');
$support_email               = trim($_POST['support_email'] ?? '');
$delivery_fee_pkr            = isset($_POST['delivery_fee_pkr']) && is_numeric($_POST['delivery_fee_pkr']) ? (float)$_POST['delivery_fee_pkr'] : 0;
$free_delivery_threshold_pkr = isset($_POST['free_delivery_threshold_pkr']) && is_numeric($_POST['free_delivery_threshold_pkr']) ? (float)$_POST['free_delivery_threshold_pkr'] : 0;
$delivery_estimate           = trim($_POST['delivery_estimate'] ?? '');
$exchange_policy             = trim($_POST['exchange_policy'] ?? '');

$errors = [];

if (empty($brand_name)) {
    $errors[] = "Brand Name cannot be empty.";
}

if (empty($support_email)) {
    $errors[] = "Support Email cannot be empty.";
} elseif (!filter_var($support_email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = "Please provide a valid support email address.";
}

if (!empty($errors)) {
    $_SESSION['errors'] = $errors;
    header("Location: ../settings.php");
    exit();
}

// 2. Check store_settings table structure
$isKeyValue = false;
$colCheck = mysqli_query($conn, "SHOW COLUMNS FROM `store_settings` LIKE 'setting_key'");
if ($colCheck && mysqli_num_rows($colCheck) > 0) {
    $isKeyValue = true;
}

if ($isKeyValue) {
    // Structure A: Key-Value storage (setting_key, setting_value)
    $settingsMap = [
        'brand_name'                  => $brand_name,
        'helpline_phone'              => $helpline_phone,
        'support_email'               => $support_email,
        'delivery_fee_pkr'            => (string)$delivery_fee_pkr,
        'free_delivery_threshold_pkr' => (string)$free_delivery_threshold_pkr,
        'delivery_estimate'           => $delivery_estimate,
        'exchange_policy'             => $exchange_policy,
    ];

    $stmt = mysqli_prepare($conn, "INSERT INTO `store_settings` (`setting_key`, `setting_value`) VALUES (?, ?) ON DUPLICATE KEY UPDATE `setting_value` = VALUES(`setting_value`)");
    if ($stmt) {
        foreach ($settingsMap as $k => $v) {
            mysqli_stmt_bind_param($stmt, "ss", $k, $v);
            mysqli_stmt_execute($stmt);
        }
        mysqli_stmt_close($stmt);
    }
} else {
    // Structure B: Direct Columns Table (id = 1)
    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS `store_settings` (
      `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
      `brand_name` VARCHAR(100) NOT NULL,
      `helpline_phone` VARCHAR(50) NOT NULL,
      `support_email` VARCHAR(100) NOT NULL,
      `delivery_fee_pkr` DECIMAL(10,2) NOT NULL DEFAULT 250.00,
      `free_delivery_threshold_pkr` DECIMAL(10,2) NOT NULL DEFAULT 10000.00,
      `delivery_estimate` VARCHAR(255) NOT NULL,
      `exchange_policy` VARCHAR(255) NOT NULL,
      `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    $checkRes = mysqli_query($conn, "SELECT id FROM `store_settings` WHERE id = 1");
    if ($checkRes && mysqli_num_rows($checkRes) > 0) {
        $stmt = mysqli_prepare($conn, "UPDATE `store_settings` SET 
            brand_name = ?, 
            helpline_phone = ?, 
            support_email = ?, 
            delivery_fee_pkr = ?, 
            free_delivery_threshold_pkr = ?, 
            delivery_estimate = ?, 
            exchange_policy = ? 
            WHERE id = 1");
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "sssddss", $brand_name, $helpline_phone, $support_email, $delivery_fee_pkr, $free_delivery_threshold_pkr, $delivery_estimate, $exchange_policy);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }
    } else {
        $stmt = mysqli_prepare($conn, "INSERT INTO `store_settings` (id, brand_name, helpline_phone, support_email, delivery_fee_pkr, free_delivery_threshold_pkr, delivery_estimate, exchange_policy) VALUES (1, ?, ?, ?, ?, ?, ?, ?)");
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "sssddss", $brand_name, $helpline_phone, $support_email, $delivery_fee_pkr, $free_delivery_threshold_pkr, $delivery_estimate, $exchange_policy);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }
    }
}

$_SESSION['success'] = "Store configuration settings successfully updated!";
header("Location: ../settings.php");
exit();

