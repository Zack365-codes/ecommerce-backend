<?php 
require_once __DIR__ . '/settings/session.php';
require_once __DIR__ . '/settings/auth.php';
require_once __DIR__ . '/settings/db.php';

$errors  = $_SESSION['errors']  ?? [];
$success = $_SESSION['success'] ?? '';
unset($_SESSION['errors'], $_SESSION['success']);

// Ensure table exists
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS `website_content` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `hero_headline` VARCHAR(255) NOT NULL,
  `hero_subtitle` VARCHAR(255) NOT NULL,
  `hero_description` TEXT NOT NULL,
  `promo_headline` VARCHAR(255) NOT NULL,
  `promo_cta_text` VARCHAR(100) NOT NULL,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

// Fetch saved content row
$contentRes = mysqli_query($conn, "SELECT * FROM website_content WHERE id = 1");
$content = $contentRes ? mysqli_fetch_assoc($contentRes) : null;

// Default fallback values if empty
$heroHeadline    = $content['hero_headline']    ?? 'Step Into Uncompromising Refinement.';
$heroSubtitle    = $content['hero_subtitle']    ?? 'Autumn / Winter 2026 Collection';
$heroDescription = $content['hero_description'] ?? 'Handcrafted from full-grain Italian calfskin and Goodyear-welted for generations of distinction. Engineered for the modern Pakistani gentleman.';
$promoHeadline   = $content['promo_headline']   ?? 'The Goodyear Welted Oxford Experience';
$promoCtaText    = $content['promo_cta_text']    ?? 'Discover The Oxford';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Website Content | SHOES LAB Admin Dashboard</title>
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
        <h1 class="page-title">Website Content Editor (CMS)</h1>
        <p class="page-subtitle">Configure editorial headlines, hero imagery, and brand benefits text.</p>
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

    <form action="actions/handle_update_content.php" method="POST">
      <div class="row g-4">
        
        <div class="col-lg-8">
          
          <!-- Hero Section CMS -->
          <div class="admin-card mb-4">
            <div class="admin-card-header"><h5 class="admin-card-title">Homepage Hero Banner</h5></div>
            <div class="admin-card-body">
              <div class="mb-3">
                <label class="form-label small fw-bold">Main Headline</label>
                <input type="text" name="hero_headline" class="form-control" value="<?= htmlspecialchars($heroHeadline) ?>" required>
              </div>
              <div class="mb-3">
                <label class="form-label small fw-bold">Subtitle</label>
                <input type="text" name="hero_subtitle" class="form-control" value="<?= htmlspecialchars($heroSubtitle) ?>">
              </div>
              <div class="mb-3">
                <label class="form-label small fw-bold">Lead Paragraph</label>
                <textarea name="hero_description" class="form-control" rows="3"><?= htmlspecialchars($heroDescription) ?></textarea>
              </div>
            </div>
          </div>

          <!-- Promo Section CMS -->
          <div class="admin-card mb-4">
            <div class="admin-card-header"><h5 class="admin-card-title">Mid-Season Promotional Banner</h5></div>
            <div class="admin-card-body">
              <div class="mb-3">
                <label class="form-label small fw-bold">Banner Headline</label>
                <input type="text" name="promo_headline" class="form-control" value="<?= htmlspecialchars($promoHeadline) ?>" required>
              </div>
              <div class="mb-3">
                <label class="form-label small fw-bold">Call to Action Button</label>
                <input type="text" name="promo_cta_text" class="form-control" value="<?= htmlspecialchars($promoCtaText) ?>">
              </div>
            </div>
          </div>

          <button type="submit" class="btn btn-dark px-4 py-2">
            <i class="bi bi-check2-circle me-1"></i> Save Content Revisions
          </button>

        </div>

        <div class="col-lg-4">
          <div class="admin-card">
            <div class="admin-card-header"><h5 class="admin-card-title">Database Status</h5></div>
            <div class="admin-card-body small text-muted">
              <p class="mb-2"><strong>Table:</strong> <code>website_content</code></p>
              <p class="mb-2"><strong>Fields Managed:</strong></p>
              <ul class="ps-3 mb-3">
                <li><code>hero_headline</code></li>
                <li><code>hero_subtitle</code></li>
                <li><code>hero_description</code></li>
                <li><code>promo_headline</code></li>
                <li><code>promo_cta_text</code></li>
              </ul>
              <div class="border-top pt-2">
                <span class="text-muted">Last Updated:</span><br>
                <strong class="text-dark">
                  <?= !empty($content['updated_at']) ? date('M d, Y h:i A', strtotime($content['updated_at'])) : 'Not updated yet' ?>
                </strong>
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