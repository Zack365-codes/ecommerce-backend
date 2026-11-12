<?php 
require_once __DIR__ . '/settings/session.php';
require_once __DIR__ . '/settings/auth.php';
require_once __DIR__ . '/settings/db.php';

$errors  = $_SESSION['errors']  ?? [];
$success = $_SESSION['success'] ?? '';
unset($_SESSION['errors'], $_SESSION['success']);

// 1. Fetch current store settings from database
$settings = [];
$isKeyValue = false;
$colCheck = @mysqli_query($conn, "SHOW COLUMNS FROM `store_settings` LIKE 'setting_key'");
if ($colCheck && mysqli_num_rows($colCheck) > 0) {
    $isKeyValue = true;
}

if ($isKeyValue) {
    // Structure A: Key-Value Table
    $res = @mysqli_query($conn, "SELECT setting_key, setting_value FROM `store_settings`");
    if ($res) {
        while ($row = mysqli_fetch_assoc($res)) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
    }
} else {
    // Structure B: Direct Columns Table
    $res = @mysqli_query($conn, "SELECT * FROM `store_settings` WHERE id = 1");
    if ($res && $row = mysqli_fetch_assoc($res)) {
        $settings = $row;
    }
}

// Default Fallback Values
$brandName        = $settings['brand_name'] ?? 'SHOES LAB';
$helplinePhone    = $settings['helpline_phone'] ?? '+92 300 8472911';
$supportEmail     = $settings['support_email'] ?? 'concierge@shoeslab.pk';
$deliveryFee      = $settings['delivery_fee_pkr'] ?? '250';
$freeThreshold    = $settings['free_delivery_threshold_pkr'] ?? '10000';
$deliveryEstimate = $settings['delivery_estimate'] ?? '2-3 business days for major cities (Lahore, Karachi, Islamabad); 3-5 days nationwide.';
$exchangePolicy   = $settings['exchange_policy'] ?? '15-Day Complimentary Doorstep Exchange for sizing adjustment.';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Settings | SHOES LAB Admin Dashboard</title>
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
        <h1 class="page-title">Storefront Configuration & Logistics Settings</h1>
        <p class="page-subtitle">Configure Pakistani delivery thresholds, store contact details, and policies.</p>
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

    <form action="actions/handle_update_settings.php" method="POST">
      <div class="row g-4">
        
        <div class="col-lg-8">
          
          <!-- Store Info -->
          <div class="admin-card mb-4">
            <div class="admin-card-header"><h5 class="admin-card-title">Store Identity</h5></div>
            <div class="admin-card-body">
              <div class="row g-3">
                <div class="col-md-6">
                  <label class="form-label small fw-bold">Brand Name</label>
                  <input type="text" name="brand_name" class="form-control" value="<?= htmlspecialchars($brandName) ?>" required>
                </div>
                <div class="col-md-6">
                  <label class="form-label small fw-bold">Helpline Phone</label>
                  <input type="text" name="helpline_phone" class="form-control" value="<?= htmlspecialchars($helplinePhone) ?>">
                </div>
                <div class="col-md-6">
                  <label class="form-label small fw-bold">Support Email</label>
                  <input type="email" name="support_email" class="form-control" value="<?= htmlspecialchars($supportEmail) ?>" required>
                </div>
                <div class="col-md-6">
                  <label class="form-label small fw-bold">Currency & Timezone</label>
                  <input type="text" class="form-control bg-light text-muted" value="PKR (Pakistan Standard Time - PKT)" readonly>
                </div>
              </div>
            </div>
          </div>

          <!-- Logistics Settings -->
          <div class="admin-card mb-4">
            <div class="admin-card-header"><h5 class="admin-card-title">Delivery Fees & Nationwide Logistics</h5></div>
            <div class="admin-card-body">
              <div class="row g-3">
                <div class="col-md-6">
                  <label class="form-label small fw-bold">Standard Delivery Fee (PKR)</label>
                  <input type="number" step="0.01" name="delivery_fee_pkr" class="form-control" value="<?= htmlspecialchars($deliveryFee) ?>" min="0" required>
                </div>
                <div class="col-md-6">
                  <label class="form-label small fw-bold">Free Delivery Threshold (PKR)</label>
                  <input type="number" step="0.01" name="free_delivery_threshold_pkr" class="form-control" value="<?= htmlspecialchars($freeThreshold) ?>" min="0" required>
                </div>
                <div class="col-12">
                  <label class="form-label small fw-bold">Delivery Estimate Notice</label>
                  <input type="text" name="delivery_estimate" class="form-control" value="<?= htmlspecialchars($deliveryEstimate) ?>">
                </div>
              </div>
            </div>
          </div>

          <!-- Policies -->
          <div class="admin-card mb-4">
            <div class="admin-card-header"><h5 class="admin-card-title">Exchange & Warranty Policies</h5></div>
            <div class="admin-card-body">
              <div class="mb-3">
                <label class="form-label small fw-bold">Exchange Period</label>
                <input type="text" name="exchange_policy" class="form-control" value="<?= htmlspecialchars($exchangePolicy) ?>">
              </div>
            </div>
          </div>

          <button type="submit" class="btn btn-dark px-4 py-2">
            <i class="bi bi-check2-circle me-1"></i> Save Settings
          </button>

        </div>

        <div class="col-lg-4">
          <div class="admin-card mb-4">
            <div class="admin-card-header"><h5 class="admin-card-title">Database Storage</h5></div>
            <div class="admin-card-body small text-muted">
              <div class="mb-2"><strong>Table:</strong> <code>store_settings</code></div>
              <div class="mb-2"><strong>Active Settings Keys:</strong></div>
              <ul class="ps-3 mb-3">
                <li><code>brand_name</code></li>
                <li><code>helpline_phone</code></li>
                <li><code>support_email</code></li>
                <li><code>delivery_fee_pkr</code></li>
                <li><code>free_delivery_threshold_pkr</code></li>
                <li><code>delivery_estimate</code></li>
                <li><code>exchange_policy</code></li>
              </ul>
              <div class="border-top pt-2">
                <span class="text-muted">Storage Mode:</span><br>
                <strong class="text-dark"><?= $isKeyValue ? 'Key-Value Pairs' : 'Dedicated Columns' ?></strong>
              </div>
            </div>
          </div>

          <div class="admin-card">
            <div class="admin-card-header"><h5 class="admin-card-title">Quick Status</h5></div>
            <div class="admin-card-body small">
              <div class="d-flex justify-content-between mb-2">
                <span class="text-muted">Primary Gateway:</span>
                <strong>Cash on Delivery (Active)</strong>
              </div>
              <div class="d-flex justify-content-between mb-2">
                <span class="text-muted">Currency:</span>
                <strong>PKR (₨)</strong>
              </div>
              <div class="d-flex justify-content-between mb-2">
                <span class="text-muted">Free Shipping Above:</span>
                <strong class="text-success">PKR <?= number_format((float)$freeThreshold) ?></strong>
              </div>
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