<?php

// echo "login";
// exit;

require_once  __DIR__ . "/settings/session.php";
$errors = $_SESSION['errors'] ?? [];
unset($_SESSION['errors']);
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Portal Login | SHOES LAB</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link rel="stylesheet" href="assets/css/dashboard.css?v=shoeslab">
</head>

<body style="background: radial-gradient(circle at 75% 20%, rgba(144, 164, 174, 0.25) 0%, transparent 60%), linear-gradient(145deg, #1c2428 0%, #37474F 65%, #161d20 100%); min-height: 100vh; display: flex; align-items: center;">

  <div class="container">
    <div class="admin-auth-box">
      <div class="text-center mb-4">
        <h2 class="fw-bold tracking-wider mb-1" style="letter-spacing: 0.15em;">SHOES LAB</h2>
        <span class="badge bg-dark border text-white px-3 py-1">ADMINISTRATIVE CONSOLE</span>
        <?php if (!empty($errors)) { ?>
          <div class="alert alert-danger mt-3">
            <ul>
              <?php foreach ($errors as $error) { ?>
                <li><?= htmlspecialchars($error) ?></li>
              <?php } ?>
            </ul>

          </div>

        <?php } ?>
      </div>



      <form action="./actions/handle_login.php" method="POST">
        <div class="mb-3">
          <label class="form-label small fw-bold text-uppercase">Admin Email</label>
          <input name="email" type="email" class="form-control" required>
        </div>

        <div class="mb-3">
          <div class="d-flex justify-content-between">
            <label class="form-label small fw-bold text-uppercase">Password</label>
            <a href="forgot-password.php" class="small text-muted">Forgot?</a>
          </div>
          <input name="password" type="password" class="form-control" required>
        </div>

        <div class="form-check mb-4">
          <input class="form-check-input" type="checkbox" id="authRemember" checked>
          <label class="form-check-label small text-muted" for="authRemember">Remember this console session</label>
        </div>

        <button type="submit" class="btn btn-dark w-100 py-2 mb-3 fw-bold">Sign In To Dashboard</button>

        <div class="d-flex justify-content-between small text-muted">
          <a href="register.php" class="text-muted">Register New Staff</a>
          <a href="../front-site/index.html" class="text-muted">Back To Storefront &rarr;</a>
        </div>
      </form>
    </div>
  </div>

</body>

</html>