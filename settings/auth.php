<?php
require_once __DIR__ . "/session.php";

// Avoid redirect loop if auth.php is loaded on login/register pages
$currentPage = basename($_SERVER['PHP_SELF'] ?? '');
if ($currentPage === 'login.php' || $currentPage === 'register.php') {
    return;
}

if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    // Dynamically calculate the web path to dashboard root to avoid 404 from actions/
    $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
    $baseDir   = (basename($scriptDir) === 'actions') ? dirname($scriptDir) : $scriptDir;
    $loginUrl  = rtrim($baseDir, '/') . '/login.php';

    $_SESSION['errors'] = ["Please log in to access the administrative console."];
    header("Location: " . $loginUrl);
    exit();
}

