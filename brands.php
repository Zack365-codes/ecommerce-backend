<?php 
require_once __DIR__ . '/settings/session.php';
require_once __DIR__ . '/settings/auth.php';
require_once __DIR__ . '/settings/db.php';

$errors  = $_SESSION['errors']  ?? [];
$success = $_SESSION['success'] ?? '';
unset($_SESSION['errors'], $_SESSION['success']);

// Fetch brands from database with product count
$brands = [];
$brandQuery = "SELECT b.id, b.name, b.created_at, 
               (SELECT COUNT(*) FROM products p WHERE p.brand_id = b.id) AS product_count 
               FROM brands b 
               ORDER BY b.id DESC";
$result = @mysqli_query($conn, $brandQuery);
if (!$result) {
    // fallback if products table is not created yet
    $brandQuery = "SELECT b.id, b.name, b.created_at, 0 AS product_count FROM brands b ORDER BY b.id DESC";
    $result = @mysqli_query($conn, $brandQuery);
}

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $brands[] = $row;
    }
}

function getBrandInitials($name) {
    $words = preg_split("/[\s,-]+/", trim($name));
    $initials = '';
    foreach (array_slice($words, 0, 2) as $w) {
        $initials .= mb_strtoupper(mb_substr($w, 0, 1));
    }
    return $initials ?: 'BR';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Brands | SHOES LAB Admin Dashboard</title>
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
        <h1 class="page-title">Footwear Brands & Labels</h1>
        <p class="page-subtitle">Manage proprietary atelier labels within SHOES LAB.</p>
      </div>
      <button class="btn btn-sm btn-dark" data-bs-toggle="modal" data-bs-target="#addBrandModal">
        <i class="bi bi-plus-lg me-1"></i> Add Brand Label
      </button>
    </div>

    <?php if (!empty($errors)): ?>
      <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
        <ul class="mb-0">
          <?php foreach ($errors as $error): ?>
            <li><?= htmlspecialchars($error) ?></li>
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

    <?php if (!empty($brands)): ?>
      <div class="row g-4">
        <?php foreach ($brands as $brand): ?>
          <div class="col-md-6 col-lg-3">
            <div class="admin-card h-100 p-4 text-center d-flex flex-column">
              <div class="rounded-circle bg-dark text-white d-inline-flex align-items-center justify-content-center fw-bold fs-5 mb-3 mx-auto" style="width: 64px; height: 64px;">
                <?= htmlspecialchars(getBrandInitials($brand['name'])) ?>
              </div>
              <h5 class="fw-bold fs-6 mb-1 text-dark"><?= htmlspecialchars($brand['name']) ?></h5>
              <div>
                <a href="products.php?brand=<?= (int)$brand['id'] ?>" class="badge bg-light text-dark border mb-3 text-decoration-none" title="Filter products by this brand">
                  <?= (int)($brand['product_count'] ?? 0) ?> Shoe Models <i class="bi bi-arrow-up-right small"></i>
                </a>
              </div>
              <p class="text-muted small mb-4">Added <?= !empty($brand['created_at']) ? date('M d, Y', strtotime($brand['created_at'])) : '—' ?></p>
              <div class="d-flex justify-content-center gap-2 mt-auto">
                <button type="button" class="btn btn-sm btn-outline-dark px-3 btn-edit-brand" data-id="<?= (int)$brand['id'] ?>" data-name="<?= htmlspecialchars($brand['name']) ?>" data-bs-toggle="modal" data-bs-target="#editBrandModal" title="Edit Brand">
                  <i class="bi bi-pencil me-1"></i> Edit
                </button>
                <form action="actions/handle_delete_brand.php" method="POST" onsubmit="return confirm('Are you sure you want to delete brand \'<?= htmlspecialchars(addslashes($brand['name'])) ?>\'?');">
                  <input type="hidden" name="id" value="<?= (int)$brand['id'] ?>">
                  <button type="submit" class="btn btn-sm btn-outline-danger px-3" title="Delete Brand">
                    <i class="bi bi-trash me-1"></i> Delete
                  </button>
                </form>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <div class="admin-card p-5 text-center text-muted">
        <div class="mb-2"><i class="bi bi-award fs-1 text-secondary"></i></div>
        <h5 class="fw-bold text-dark mb-2">No Brands Found</h5>
        <p class="mb-3">No atelier brand labels found in the database yet.</p>
        <button class="btn btn-sm btn-dark" data-bs-toggle="modal" data-bs-target="#addBrandModal">
          <i class="bi bi-plus-lg me-1"></i> Add Your First Brand
        </button>
      </div>
    <?php endif; ?>

    <!-- Add Brand Modal -->
    <div class="modal fade" id="addBrandModal" tabindex="-1" aria-labelledby="addBrandModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <form action="actions/handle_add_brand.php" method="POST">
            <div class="modal-header">
              <h5 class="modal-title fs-6 fw-bold" id="addBrandModalLabel">Add Brand Label</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
              <div class="mb-3">
                <label for="brandName" class="form-label small fw-bold">Brand Name <span class="text-danger">*</span></label>
                <input type="text" name="name" id="brandName" class="form-control" placeholder="e.g. SHOES LAB Black Label" required maxlength="120" autocomplete="off" autofocus>
                <div class="form-text small text-muted">Only unique brand names are stored. Duplicates are automatically prevented.</div>
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
              <button type="submit" class="btn btn-sm btn-dark">
                <i class="bi bi-check-lg me-1"></i> Save Brand
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- Edit Brand Modal -->
    <div class="modal fade" id="editBrandModal" tabindex="-1" aria-labelledby="editBrandModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <form action="actions/handle_edit_brand.php" method="POST">
            <input type="hidden" name="id" id="editBrandId" value="">
            <div class="modal-header">
              <h5 class="modal-title fs-6 fw-bold" id="editBrandModalLabel">Edit Brand Label</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
              <div class="mb-3">
                <label for="editBrandName" class="form-label small fw-bold">Brand Name <span class="text-danger">*</span></label>
                <input type="text" name="name" id="editBrandName" class="form-control" placeholder="e.g. SHOES LAB Black Label" required maxlength="120" autocomplete="off">
                <div class="form-text small text-muted">Duplicate brand names are automatically prevented.</div>
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
              <button type="submit" class="btn btn-sm btn-dark">
                <i class="bi bi-check-lg me-1"></i> Update Brand
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  
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
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      document.querySelectorAll('.btn-edit-brand').forEach(function(btn) {
        btn.addEventListener('click', function() {
          document.getElementById('editBrandId').value = this.getAttribute('data-id');
          document.getElementById('editBrandName').value = this.getAttribute('data-name');
        });
      });
    });
  </script>
</body>
</html>