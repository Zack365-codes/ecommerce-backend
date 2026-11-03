<?php
require_once __DIR__ . '/settings/session.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Reset Admin Password | SHOES LAB</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="assets/css/dashboard.css?v=shoeslab">
</head>
<body style="background: radial-gradient(circle at 75% 20%, rgba(144, 164, 174, 0.25) 0%, transparent 60%), linear-gradient(145deg, #1c2428 0%, #37474F 65%, #161d20 100%); min-height: 100vh; display: flex; align-items: center;">
  <div class="container">
    <div class="admin-auth-box text-center">
      <h2 class="fw-bold mb-2">Password Recovery</h2>
      <p class="text-muted small mb-4">Enter your administrative email to receive security credentials.</p>
      <form action="login.php" method="GET" class="text-start">
        <div class="mb-3">
          <label class="form-label small fw-bold">Admin Email</label>
          <input type="email" class="form-control" placeholder="admin@shoeslab.pk" required>
        </div>
        <button type="submit" class="btn btn-dark w-100 py-2 mb-3">Send Recovery Link</button>
        <div class="text-center small"><a href="login.php" class="text-muted">Back to Login</a></div>
      </form>
    </div>
  </div>
</body>
</html>

