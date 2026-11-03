<?php
require_once __DIR__ . '/../settings/session.php';
require_once __DIR__ . '/../settings/db.php';


if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../register.php");
    exit;
}

$name     = trim($_POST['name']  ?? '');
$email    = trim($_POST['email'] ?? '');
$password = $_POST['pwd']  ?? '';
$role     = $_POST['role'] ?? '';

$allowedRoles = ['Admin', 'Manager', 'Store Director'];
$errors = [];

if ($name === '' || $email === '' || $password === '') {
    $errors[] = "All fields are required";
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = "Email address is incorrect";
}
if (strlen($password) < 6) {
    $errors[] = "Password must be at least 6 characters";
}
if (!in_array($role, $allowedRoles, true)) {
    $errors[] = "Invalid role selected";
}

if ($errors) {
    $_SESSION['errors'] = $errors;
    header("Location: ../register.php");
    exit;
}

// duplicate email check
$stmt = mysqli_prepare($conn, "SELECT id FROM admins WHERE email = ?");
mysqli_stmt_bind_param($stmt, 's', $email);
mysqli_stmt_execute($stmt);
mysqli_stmt_store_result($stmt);

if (mysqli_stmt_num_rows($stmt) > 0) {
    $_SESSION['errors'] = ["This email is already registered"];
    header("Location: ../register.php");
    exit;
}
mysqli_stmt_close($stmt);

// insert with hashed password
$hash = password_hash($password, PASSWORD_DEFAULT);
$stmt = mysqli_prepare($conn, "INSERT INTO admins (name, email, password_hash, role) VALUES (?, ?, ?, ?)");
mysqli_stmt_bind_param($stmt, 'ssss', $name, $email, $hash, $role);

if (mysqli_stmt_execute($stmt)) {
    $_SESSION['success'] = "Account created. Please log in.";
    header("Location: ../login.php");
} else {
    $_SESSION['errors'] = ["Something went wrong. Please try again."];
    header("Location: ../register.php");
}
mysqli_stmt_close($stmt);
exit;
