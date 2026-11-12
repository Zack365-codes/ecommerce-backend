<?php 
require_once __DIR__ . '/settings/session.php';
require_once __DIR__ . '/settings/auth.php';
require_once __DIR__ . '/settings/db.php';

$errors  = $_SESSION['errors']  ?? [];
$success = $_SESSION['success'] ?? '';
unset($_SESSION['errors'], $_SESSION['success']);

// Fetch profile data from `profile` table
$profileRes = @mysqli_query($conn, "SELECT * FROM `profile` ORDER BY id ASC LIMIT 1");
$profile = ($profileRes) ? mysqli_fetch_assoc($profileRes) : null;

$adminName  = $profile['name'] ?? $profile['full_name'] ?? ($_SESSION['user_name'] ?? 'M. Asif');
$adminRole  = $profile['role'] ?? $profile['administrative_role'] ?? ($_SESSION['user_role'] ?? 'Store Director');
$adminEmail = $profile['email'] ?? $profile['email_address'] ?? 'admin@shoeslab.pk';
$adminPhone = $profile['phone'] ?? $profile['phone_number'] ?? '+92 300 8472911';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Profile | SHOES LAB Admin Dashboard</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link rel="stylesheet" href="assets/css/dashboard.css?v=shoeslab">
  <link rel="stylesheet" href="assets/css/responsive.css">
</head>
<body>

  <div class="admin-wrapper">
    
    <?php require_once __DIR__ . "/partials/sidebar.php" ?>
    
<div class="admin-main">
      
    <!-- Topbar -->
    <header class="admin-topbar">
      <div class="d-flex align-items-center gap-3">
        <button class="btn btn-link text-dark p-0 d-lg-none" id="sidebarToggleBtn" aria-label="Toggle Navigation">
          <i class="bi bi-list fs-3"></i>
        </button>
        <div class="topbar-search">
          <i class="bi bi-search search-icon"></i>
          <input type="text" class="form-control" id="tableSearchInput" placeholder="Quick search inventory, orders...">
        </div>
      </div>

      <div class="topbar-actions">
        <a href="../front-site/index.html" target="_blank" class="btn btn-sm btn-outline-secondary d-none d-md-inline-flex align-items-center gap-1">
          <i class="bi bi-eye"></i> View Site
        </a>
        
        <div class="dropdown">
          <a href="#" class="topbar-icon-btn" data-bs-toggle="dropdown" aria-expanded="false" title="Notifications">
            <i class="bi bi-bell"></i>
            <span class="topbar-badge-dot"></span>
          </a>
          <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0" style="min-width: 280px;">
            <li><h6 class="dropdown-header text-uppercase small fw-bold">Recent Alerts</h6></li>
            <li><a class="dropdown-item small py-2" href="orders.php"><i class="bi bi-bag-check text-success me-2"></i> New Order #NF-88291 received (PKR 14,999)</a></li>
            <li><a class="dropdown-item small py-2" href="inventory.php"><i class="bi bi-exclamation-triangle text-warning me-2"></i> Low stock: Sahara Suede Chukka (9 left)</a></li>
            <li><a class="dropdown-item small py-2" href="reviews.php"><i class="bi bi-star text-primary me-2"></i> New 5-star review pending approval</a></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item text-center small text-primary fw-semibold" href="orders.php">View All Notifications</a></li>
          </ul>
        </div>

        <div class="dropdown">
          <a href="#" class="d-flex align-items-center text-decoration-none dropdown-toggle text-dark" data-bs-toggle="dropdown" aria-expanded="false">
            <img src="https://images.unsplash.com/photo-1534528741775-53994a69daeb?q=80&w=200&auto=format&fit=crop" class="admin-avatar me-2" alt="Admin">
            <div class="d-none d-sm-block text-start">
              <div class="fw-semibold small leading-none">M. Asif</div>
              <div class="text-muted" style="font-size: 0.7rem;">Store Director</div>
            </div>
          </a>
          <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0">
            <li><a class="dropdown-item" href="profile.php"><i class="bi bi-person me-2 text-muted"></i> My Profile</a></li>
            <li><a class="dropdown-item" href="settings.php"><i class="bi bi-gear me-2 text-muted"></i> Store Settings</a></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item text-danger" href="logout.php"><i class="bi bi-box-arrow-right me-2"></i> Logout</a></li>
          </ul>
        </div>
      </div>
    </header>
    

      <main class="admin-content">
        
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h1 class="page-title">Administrator Profile</h1>
        <p class="page-subtitle">Manage personal credentials and security access.</p>
      </div>
    </div>

    <!-- Alert Messages -->
    <?php if (!empty($errors)): ?>
      <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
        <ul class="mb-0">
          <?php foreach ($errors as $err): ?>
            <li><?= htmlspecialchars($err) ?></li>
          <?php endforeach; ?>
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    <?php endif; ?>

    <?php if (!empty($success)): ?>
      <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
        <i class="bi bi-check-circle me-2"></i><?= htmlspecialchars($success) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    <?php endif; ?>

    <form action="actions/handle_update_profile.php" method="POST">
      <div class="row g-4">
        
        <div class="col-lg-8">
          <div class="admin-card mb-4">
            <div class="admin-card-header"><h5 class="admin-card-title">Account Details</h5></div>
            <div class="admin-card-body">
              <div class="row g-3">
                <div class="col-md-6">
                  <label class="form-label small fw-bold">Full Name</label>
                  <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($adminName) ?>" required>
                </div>
                <div class="col-md-6">
                  <label class="form-label small fw-bold">Administrative Role</label>
                  <input type="text" name="role" class="form-control bg-light" value="<?= htmlspecialchars($adminRole) ?>" readonly>
                </div>
                <div class="col-md-6">
                  <label class="form-label small fw-bold">Email Address</label>
                  <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($adminEmail) ?>" required>
                </div>
                <div class="col-md-6">
                  <label class="form-label small fw-bold">Phone Number</label>
                  <input type="tel" name="phone" class="form-control" value="<?= htmlspecialchars($adminPhone) ?>">
                </div>
              </div>
            </div>
          </div>

          <div class="admin-card mb-4">
            <div class="admin-card-header"><h5 class="admin-card-title">Security & Password</h5></div>
            <div class="admin-card-body">
              <p class="text-muted small mb-3">Leave blank if you do not want to change your current password.</p>
              <div class="row g-3">
                <div class="col-md-6">
                  <label class="form-label small fw-bold">New Password</label>
                  <input type="password" name="new_password" class="form-control" placeholder="••••••••" autocomplete="new-password">
                </div>
                <div class="col-md-6">
                  <label class="form-label small fw-bold">Confirm New Password</label>
                  <input type="password" name="confirm_password" class="form-control" placeholder="••••••••" autocomplete="new-password">
                </div>
              </div>
            </div>
          </div>

          <button type="submit" class="btn btn-dark px-4 py-2">
            <i class="bi bi-check2-circle me-1"></i> Update Profile
          </button>
        </div>

        <div class="col-lg-4">
          <div class="admin-card p-4 text-center">
            <div class="rounded-circle bg-dark text-white d-inline-flex align-items-center justify-content-center fw-bold fs-3 mb-3 mx-auto" style="width: 80px; height: 80px;">
              <?= strtoupper(substr($adminName, 0, 1)) ?>
            </div>
            <h5 class="fw-bold mb-1"><?= htmlspecialchars($adminName) ?></h5>
            <div class="badge bg-light text-dark border mb-3"><?= htmlspecialchars($adminRole) ?></div>
            
            <div class="border-top pt-3 text-start small text-muted">
              <div class="mb-2"><i class="bi bi-envelope me-2"></i><?= htmlspecialchars($adminEmail) ?></div>
              <div class="mb-2"><i class="bi bi-telephone me-2"></i><?= htmlspecialchars($adminPhone ?: 'No phone added') ?></div>
              <div class="mb-2"><i class="bi bi-database-check me-2 text-success"></i>Table: <code>profile</code></div>
              <div><i class="bi bi-shield-check me-2 text-primary"></i>Text Credentials Only</div>
            </div>
          </div>
        </div>

      </div>
    </form>
  
      </main>
    </div>
  </div>

  <!-- Delete Confirmation Modal (Shared) -->
  <div class="modal fade" id="deleteConfirmModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
      <div class="modal-content border-0 shadow">
        <div class="modal-body text-center p-4">
          <div class="text-danger fs-1 mb-2"><i class="bi bi-exclamation-circle"></i></div>
          <h5 class="fw-bold mb-2">Confirm Delete?</h5>
          <p class="text-muted small mb-4">Are you sure you want to remove <strong id="deleteTargetName">this item</strong> from the database? This action is simulated for the prototype.</p>
          <div class="d-flex justify-content-center gap-2">
            <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="button" class="btn btn-sm btn-danger px-3" id="btnConfirmDelete">Delete</button>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="assets/js/dashboard.js"></script>
</body>
</html>