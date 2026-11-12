<?php
require_once __DIR__ . '/../settings/session.php';
require_once __DIR__ . '/../settings/auth.php';
require_once __DIR__ . '/../settings/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../profile.php");
    exit();
}

$name            = trim($_POST['name'] ?? '');
$email           = trim($_POST['email'] ?? '');
$phone           = trim($_POST['phone'] ?? '');
$role            = trim($_POST['role'] ?? 'Store Director');
$newPassword     = $_POST['new_password'] ?? '';
$confirmPassword = $_POST['confirm_password'] ?? '';

$errors = [];

// 1. Validations
if (empty($name)) {
    $errors[] = "Full Name cannot be empty.";
}

if (empty($email)) {
    $errors[] = "Email Address cannot be empty.";
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = "Please provide a valid email address.";
}

// Password validation (if entered)
$shouldUpdatePassword = false;
$passwordHash = '';
if (!empty($newPassword)) {
    if (strlen($newPassword) < 6) {
        $errors[] = "New password must be at least 6 characters long.";
    } elseif ($newPassword !== $confirmPassword) {
        $errors[] = "New password and Confirm Password do not match.";
    } else {
        $shouldUpdatePassword = true;
        $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
    }
}

if (!empty($errors)) {
    $_SESSION['errors'] = $errors;
    header("Location: ../profile.php");
    exit();
}

// 2. Discover column names of table `profile`
$colRes = mysqli_query($conn, "SHOW COLUMNS FROM `profile`");
$columns = [];
if ($colRes) {
    while ($col = mysqli_fetch_assoc($colRes)) {
        $columns[] = strtolower($col['Field']);
    }
}

// Dynamically map to the column names created by user in `profile` table
$nameCol  = in_array('full_name', $columns) ? 'full_name' : (in_array('name', $columns) ? 'name' : '');
$emailCol = in_array('email_address', $columns) ? 'email_address' : (in_array('email', $columns) ? 'email' : '');
$phoneCol = in_array('phone_number', $columns) ? 'phone_number' : (in_array('phone', $columns) ? 'phone' : '');
$roleCol  = in_array('administrative_role', $columns) ? 'administrative_role' : (in_array('role', $columns) ? 'role' : '');
$passCol  = in_array('password_hash', $columns) ? 'password_hash' : (in_array('password', $columns) ? 'password' : '');

// 3. Check if any row exists in `profile`
$rowRes = mysqli_query($conn, "SELECT id FROM `profile` ORDER BY id ASC LIMIT 1");
$existingProfile = ($rowRes) ? mysqli_fetch_assoc($rowRes) : null;

if ($existingProfile) {
    // UPDATE existing row in `profile`
    $profileId = (int)$existingProfile['id'];
    $setParts = [];
    $params = [];
    $types = '';

    if ($nameCol) {
        $setParts[] = "`$nameCol` = ?";
        $params[] = $name;
        $types .= 's';
    }
    if ($emailCol) {
        $setParts[] = "`$emailCol` = ?";
        $params[] = $email;
        $types .= 's';
    }
    if ($phoneCol) {
        $setParts[] = "`$phoneCol` = ?";
        $params[] = $phone;
        $types .= 's';
    }
    if ($roleCol) {
        $setParts[] = "`$roleCol` = ?";
        $params[] = $role;
        $types .= 's';
    }
    if ($shouldUpdatePassword && $passCol) {
        $setParts[] = "`$passCol` = ?";
        $params[] = $passwordHash;
        $types .= 's';
    }

    if (!empty($setParts)) {
        $sql = "UPDATE `profile` SET " . implode(', ', $setParts) . " WHERE id = ?";
        $params[] = $profileId;
        $types .= 'i';

        $stmt = mysqli_prepare($conn, $sql);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, $types, ...$params);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }
    }
} else {
    // INSERT first row into `profile`
    $insertCols = [];
    $placeholders = [];
    $params = [];
    $types = '';

    if ($nameCol) {
        $insertCols[] = "`$nameCol`";
        $placeholders[] = '?';
        $params[] = $name;
        $types .= 's';
    }
    if ($emailCol) {
        $insertCols[] = "`$emailCol`";
        $placeholders[] = '?';
        $params[] = $email;
        $types .= 's';
    }
    if ($phoneCol) {
        $insertCols[] = "`$phoneCol`";
        $placeholders[] = '?';
        $params[] = $phone;
        $types .= 's';
    }
    if ($roleCol) {
        $insertCols[] = "`$roleCol`";
        $placeholders[] = '?';
        $params[] = $role;
        $types .= 's';
    }
    if ($passCol) {
        $insertCols[] = "`$passCol`";
        $placeholders[] = '?';
        $params[] = !empty($passwordHash) ? $passwordHash : password_hash('password', PASSWORD_DEFAULT);
        $types .= 's';
    }

    if (!empty($insertCols)) {
        $sql = "INSERT INTO `profile` (" . implode(', ', $insertCols) . ") VALUES (" . implode(', ', $placeholders) . ")";
        $stmt = mysqli_prepare($conn, $sql);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, $types, ...$params);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }
    }
}

// Update session name & role for topbar
$_SESSION['user_name'] = $name;
$_SESSION['user_role'] = $role;

$_SESSION['success'] = "Profile data successfully saved in database!";
header("Location: ../profile.php");
exit();
