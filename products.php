<?php 
require_once __DIR__ . '/settings/session.php';
require_once __DIR__ . '/settings/auth.php';
require_once __DIR__ . '/settings/db.php';

$errors  = $_SESSION['errors']  ?? [];
$success = $_SESSION['success'] ?? '';
unset($_SESSION['errors'], $_SESSION['success']);

$products = [];
$prodQuery = "SELECT p.*, c.name AS category_name, b.name AS brand_name 
              FROM products p 
              LEFT JOIN categories c ON c.id = p.category_id 
              LEFT JOIN brands b ON b.id = p.brand_id 
              ORDER BY p.id DESC";
$res = @mysqli_query($conn, $prodQuery);
if ($res) {
    while ($row = mysqli_fetch_assoc($res)) {
        $products[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Products | SHOES LAB Admin Dashboard</title>
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
        
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
      <div>
        <h1 class="page-title">Footwear Inventory Catalog</h1>
        <p class="page-subtitle">Manage footwear models, pricing in PKR, sizes, and stock availability.</p>
      </div>
      <div class="d-flex gap-2">
        <a href="product-add.php" class="btn btn-sm btn-dark">
          <i class="bi bi-plus-lg me-1"></i> Add New Product
        </a>
      </div>
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

    <!-- Filters & Search Toolbar -->
    <div class="admin-card mb-4">
      <div class="admin-card-body p-3">
        <div class="row g-3 align-items-center">
          <div class="col-md-6">
            <div class="input-group">
              <span class="input-group-text bg-white"><i class="bi bi-search text-muted"></i></span>
              <input type="text" id="productFilterInput" class="form-control" placeholder="Search by name, SKU or model...">
            </div>
          </div>
          <div class="col-md-6 text-md-end">
            <span class="text-muted small"><strong><?= count($products) ?></strong> Items in Database</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Products Table -->
    <div class="admin-card">
      <div class="admin-table-wrap">
        <table class="admin-table">
          <thead>
            <tr>
              <th width="40"><input type="checkbox" id="selectAllRows" class="form-check-input"></th>
              <th>Product</th>
              <th>SKU</th>
              <th>Category</th>
              <th>Brand</th>
              <th>Selling Price</th>
              <th>Stock</th>
              <th>Status</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody class="filterable-table-body">
            <?php if (!empty($products)): ?>
              <?php foreach ($products as $prod): ?>
                <?php 
                  $imgSrc = htmlspecialchars($prod['image_url'] ?? '');
                  if (!$imgSrc) {
                      $imgSrc = 'https://images.unsplash.com/photo-1614252235316-8c857d38b5f4?q=80&w=200&auto=format&fit=crop';
                  }
                  $statusClass = 'badge-status-success';
                  if ($prod['status'] === 'Draft') $statusClass = 'badge-status-warning';
                  if ($prod['status'] === 'Out of Stock') $statusClass = 'badge-status-danger';
                  if ($prod['status'] === 'Archived') $statusClass = 'badge-status-secondary';
                ?>
                <tr>
                  <td><input type="checkbox" class="form-check-input row-select-check"></td>
                  <td>
                    <div class="d-flex align-items-center gap-3">
                      <img src="<?= $imgSrc ?>" class="rounded-1" style="width: 44px; height: 44px; object-fit: cover;" alt="<?= htmlspecialchars($prod['name']) ?>">
                      <div>
                        <div class="fw-semibold text-dark"><?= htmlspecialchars($prod['name']) ?></div>
                        <small class="text-muted"><?= htmlspecialchars($prod['brand_name'] ?? 'No Brand') ?></small>
                      </div>
                    </div>
                  </td>
                  <td><code><?= htmlspecialchars($prod['sku']) ?></code></td>
                  <td><?= htmlspecialchars($prod['category_name'] ?? 'Uncategorized') ?></td>
                  <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($prod['brand_name'] ?? '—') ?></span></td>
                  <td class="fw-bold">PKR <?= number_format($prod['price_pkr']) ?></td>
                  <td>
                    <span class="badge bg-light text-dark border">
                      <?= (int)$prod['stock_quantity'] ?> pairs
                    </span>
                  </td>
                  <td>
                    <span class="badge-status <?= $statusClass ?>"><?= htmlspecialchars($prod['status']) ?></span>
                  </td>
                  <td>
                    <div class="d-flex gap-1">
                      <form action="actions/handle_delete_product.php" method="POST" onsubmit="return confirm('Are you sure you want to delete product \'<?= htmlspecialchars(addslashes($prod['name'])) ?>\'?');">
                        <input type="hidden" name="id" value="<?= (int)$prod['id'] ?>">
                        <button type="submit" class="btn btn-sm btn-outline-danger py-1 px-2" title="Delete product">
                          <i class="bi bi-trash"></i>
                        </button>
                      </form>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr>
                <td colspan="9" class="text-center py-5 text-muted">
                  <div class="mb-2"><i class="bi bi-box-seam fs-1 text-secondary"></i></div>
                  <h6 class="fw-bold text-dark mb-2">No Products in Database</h6>
                  <p class="mb-3 small">You have not added any footwear models yet.</p>
                  <a href="product-add.php" class="btn btn-sm btn-dark">
                    <i class="bi bi-plus-lg me-1"></i> Add Your First Product
                  </a>
                </td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>

      <div class="p-3 border-top d-flex justify-content-between align-items-center">
        <span class="small text-muted">Showing <?= count($products) ?> product(s)</span>
      </div>
    </div>
  
      </main>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="assets/js/dashboard.js"></script>
</body>
</html>
