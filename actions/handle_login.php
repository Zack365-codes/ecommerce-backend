<?php
require_once __DIR__ . "/../settings/session.php";
require_once __DIR__ . "/../settings/db.php";

$errors = [];
if ($_SERVER['REQUEST_METHOD']  == "POST") {

    $email        = trim($_POST['email'] ?? '');
    $password     = $_POST['password'] ?? '';


    // email and password fields are required
    if (empty($email) || empty($password)) {
        $errors[] = "Email and Password are required";
    }

    // validate email
    if (filter_var($email, FILTER_VALIDATE_EMAIL) == false) {
        $errors[] = "Email address is incorrect";
    }

    // errors then return back to form
    if (!empty($errors)) {
        $_SESSION['errors'] = $errors;
        header("Location: ../login.php");
        exit;
    }


    // check email is registered or not
    $stmt = mysqli_prepare($conn, "SELECT id, name, role, password_hash, status FROM admins WHERE email = ?");
    mysqli_stmt_bind_param($stmt, 's', $email);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);

    if (mysqli_num_rows($result) == 0) {
        mysqli_stmt_close($stmt);
        $errors[] = "Please check your credentials";
        $_SESSION['errors'] = $errors;
        header("Location: ../login.php");
        exit;
    }

    $user = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    // check account status
    if (isset($user['status']) && $user['status'] !== 'Active') {
        $errors[] = "Your account is " . strtolower($user['status']) . ". Please contact an administrator.";
        $_SESSION['errors'] = $errors;
        header("Location: ../login.php");
        exit;
    }

    // verify password
    if (password_verify($password, $user['password_hash']) == false) {
        $errors[] = "Wrong credentials";
        $_SESSION['errors'] = $errors;
        header("Location: ../login.php");
        exit;
    }

    // regenerate session id to prevent session fixation
    session_regenerate_id(true);

    $_SESSION['user_id']   = $user['id'];
    $_SESSION['user_name'] = $user['name'] ?? 'Admin';
    $_SESSION['user_role'] = $user['role'] ?? 'Manager';

    // update last login timestamp
    $updateStmt = mysqli_prepare($conn, "UPDATE admins SET last_login_at = NOW() WHERE id = ?");
    mysqli_stmt_bind_param($updateStmt, 'i', $user['id']);
    mysqli_stmt_execute($updateStmt);
    mysqli_stmt_close($updateStmt);

    header("Location: ../index.php");
    exit;
}
