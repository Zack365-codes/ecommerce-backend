<?php
require_once __DIR__ . '/../settings/session.php';
require_once __DIR__ . '/../settings/auth.php';
require_once __DIR__ . '/../settings/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../website-content.php");
    exit();
}

$hero_headline    = trim($_POST['hero_headline'] ?? '');
$hero_subtitle    = trim($_POST['hero_subtitle'] ?? '');
$hero_description = trim($_POST['hero_description'] ?? '');
$promo_headline   = trim($_POST['promo_headline'] ?? '');
$promo_cta_text   = trim($_POST['promo_cta_text'] ?? '');

$errors = [];

if (empty($hero_headline)) {
    $errors[] = "Main Headline cannot be empty.";
}

if (empty($promo_headline)) {
    $errors[] = "Promotional Banner Headline cannot be empty.";
}

if (!empty($errors)) {
    $_SESSION['errors'] = $errors;
    header("Location: ../website-content.php");
    exit();
}

// Check if content row (id = 1) exists
$checkRes = mysqli_query($conn, "SELECT id FROM website_content WHERE id = 1");
if ($checkRes && mysqli_num_rows($checkRes) > 0) {
    // Update existing row
    $stmt = mysqli_prepare($conn, "UPDATE website_content SET 
        hero_headline = ?, 
        hero_subtitle = ?, 
        hero_description = ?, 
        promo_headline = ?, 
        promo_cta_text = ? 
        WHERE id = 1");
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "sssss", $hero_headline, $hero_subtitle, $hero_description, $promo_headline, $promo_cta_text);
        mysqli_stmt_execute($stmt);
       
    }
} else {
    // Insert new row with id = 1
    $stmt = mysqli_prepare($conn, "INSERT INTO website_content (id, hero_headline, hero_subtitle, hero_description, promo_headline, promo_cta_text) VALUES (1, ?, ?, ?, ?, ?)");
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "sssss", $hero_headline, $hero_subtitle, $hero_description, $promo_headline, $promo_cta_text);
        mysqli_stmt_execute($stmt);
  
    }
}

$_SESSION['success'] = "Website content updated successfully!";
header("Location: ../website-content.php");
exit();

