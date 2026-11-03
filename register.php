<?php
require_once __DIR__ . '/settings/session.php';

$errors  = $_SESSION['errors']  ?? [];
$success = $_SESSION['success'] ?? '';
unset($_SESSION['errors'], $_SESSION['success']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Staff Registration | SHOES LAB</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="assets/css/dashboard.css?v=shoeslab">
</head>
<body style="background: radial-gradient(circle at 75% 20%, rgba(144, 164, 174, 0.25) 0%, transparent 60%), linear-gradient(145deg, #1c2428 0%, #37474F 65%, #161d20 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 1rem;">

  <div class="container" style="max-width: 460px;">
    <div class="admin-auth-box w-100">

      <div class="text-center mb-4">
        <h2 class="fw-bold mb-1">SHOES LAB</h2>
        <span class="badge bg-dark border text-white px-3 py-1">STAFF REGISTRATION</span>
      </div>

      <?php if (!empty($errors)) { ?>
        <div class="alert alert-danger">
          <ul class="mb-0">
            <?php foreach ($errors as $error) { ?>
              <li><?= htmlspecialchars($error) ?></li>
            <?php } ?>
          </ul>
        </div>
      <?php } ?>

      <?php if ($success !== '') { ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
      <?php } ?>

      <form action="./actions/handle_register.php" method="POST" novalidate>
        <div class="mb-3">
          <label for="name" class="form-label small fw-bold">Full Name</label>
          <input id="name" name="name" type="text" class="form-control" placeholder="M. Asif" required>
        </div>

        <div class="mb-3">
          <label for="email" class="form-label small fw-bold">Official Email</label>
          <input id="email" name="email" type="email" class="form-control" placeholder="staff@shoeslab.pk" required>
        </div>

        <div class="mb-3">
          <label for="role" class="form-label small fw-bold">Role</label>
          <select id="role" name="role" class="form-select">
            <option>Manager</option>
            <option>Admin</option>
            <option>Store Director</option>
          </select>
        </div>

        <div class="mb-3">
          <label for="pwd" class="form-label small fw-bold">Password</label>
          <input id="pwd" name="pwd" type="password" class="form-control" minlength="6" placeholder="At least 6 characters" required>
        </div>

        <button type="submit" class="btn btn-dark w-100 py-2 mb-3">Create Staff Account</button>
        <div class="text-center small">
          <a href="login.php" class="text-muted">Already have access? Log in</a>
        </div>
      </form>

    </div>
  </div>

</body>
</html>