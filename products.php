<?php 
require_once __DIR__ . '/settings/session.php';
require_once __DIR__ . '/settings/auth.php';
require_once __DIR__ . '/settings/db.php';

$errors  = $_SESSION['errors']  ?? [];
$success = $_SESSION['success'] ?? '';
unset($_SESSION['errors'], $_SESSION['success']);

// Retrieve filter criteria from GET query parameters
$filterCategory = isset($_GET['category']) ? trim($_GET['category']) : '';
$filterBrand    = isset($_GET['brand']) ? trim($_GET['brand']) : '';
$filterStatus   = isset($_GET['status']) ? trim($_GET['status']) : '';
$filterSearch   = isset($_GET['search']) ? trim($_GET['search']) : '';

// Fetch all categories for filter dropdown
$categoriesList = [];
$catRes = @mysqli_query($conn, "SELECT id, name FROM categories ORDER BY name ASC");
if ($catRes) {
    while ($row = mysqli_fetch_assoc($catRes)) {
        $categoriesList[] = $row;
    }
}

// Fetch all brands for filter dropdown
$brandsList = [];
$brandRes = @mysqli_query($conn, "SELECT id, name FROM brands ORDER BY name ASC");
if ($brandRes) {
    while ($row = mysqli_fetch_assoc($brandRes)) {
        $brandsList[] = $row;
    }
}

// Fetch total products count for reference
$totalCountRes = @mysqli_query($conn, "SELECT COUNT(*) AS total FROM products");
$totalCountRow = $totalCountRes ? mysqli_fetch_assoc($totalCountRes) : null;
$totalProductsCount = $totalCountRow ? (int)$totalCountRow['total'] : 0;

// Build query dynamically with prepared statement
$sql = "SELECT p.*, c.name AS category_name, b.name AS brand_name 
        FROM products p 
        LEFT JOIN categories c ON c.id = p.category_id 
        LEFT JOIN brands b ON b.id = p.brand_id 
        WHERE 1=1";
$params = [];
$types  = "";

if ($filterCategory !== '' && is_numeric($filterCategory)) {
    $sql .= " AND p.category_id = ?";
    $params[] = (int)$filterCategory;
    $types .= "i";
}

if ($filterBrand !== '' && is_numeric($filterBrand)) {
    $sql .= " AND p.brand_id = ?";
    $params[] = (int)$filterBrand;
    $types .= "i";
}

if ($filterStatus !== '') {
    $sql .= " AND p.status = ?";
    $params[] = $filterStatus;
    $types .= "s";
}

if ($filterSearch !== '') {
    $sql .= " AND (p.name LIKE ? OR p.sku LIKE ? OR c.name LIKE ? OR b.name LIKE ?)";
    $like = '%' . $filterSearch . '%';
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $types .= "ssss";
}

$sql .= " ORDER BY p.id DESC";

$products = [];
if (!empty($params)) {
    $stmt = mysqli_prepare($conn, $sql);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        if ($res) {
            while ($row = mysqli_fetch_assoc($res)) {
                $products[] = $row;
            }
        }
        mysqli_stmt_close($stmt);
    }
} else {
    $res = @mysqli_query($conn, $sql);
    if ($res) {
        while ($row = mysqli_fetch_assoc($res)) {
            $products[] = $row;
        }
    }
}

$hasActiveFilters = ($filterCategory !== '' || $filterBrand !== '' || $filterStatus !== '' || $filterSearch !== '');
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
        <form method="GET" action="products.php" id="productsFilterForm">
          <div class="row g-2 align-items-center">
            
            <!-- Search Input -->
            <div class="col-12 col-md-4 col-lg-3">
              <div class="input-group input-group-sm">
                <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                <input type="text" name="search" id="productFilterInput" class="form-control form-control-sm border-start-0" 
                       placeholder="Search name, SKU..." value="<?= htmlspecialchars($filterSearch) ?>">
              </div>
            </div>

            <!-- Categories Filter -->
            <div class="col-6 col-md-3 col-lg-2">
              <select name="category" id="categoryFilter" class="form-select form-select-sm" onchange="this.form.submit()">
                <option value="">All Categories</option>
                <?php foreach ($categoriesList as $cat): ?>
                  <option value="<?= (int)$cat['id'] ?>" <?= ($filterCategory !== '' && (int)$filterCategory === (int)$cat['id']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($cat['name']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <!-- Brands Filter -->
            <div class="col-6 col-md-3 col-lg-2">
              <select name="brand" id="brandFilter" class="form-select form-select-sm" onchange="this.form.submit()">
                <option value="">All Brands</option>
                <?php foreach ($brandsList as $b): ?>
                  <option value="<?= (int)$b['id'] ?>" <?= ($filterBrand !== '' && (int)$filterBrand === (int)$b['id']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($b['name']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <!-- Status Filter -->
            <div class="col-6 col-md-2 col-lg-2">
              <select name="status" id="statusFilter" class="form-select form-select-sm" onchange="this.form.submit()">
                <option value="">All Statuses</option>
                <option value="Active" <?= $filterStatus === 'Active' ? 'selected' : '' ?>>Active</option>
                <option value="Draft" <?= $filterStatus === 'Draft' ? 'selected' : '' ?>>Draft</option>
                <option value="Out of Stock" <?= $filterStatus === 'Out of Stock' ? 'selected' : '' ?>>Out of Stock</option>
                <option value="Archived" <?= $filterStatus === 'Archived' ? 'selected' : '' ?>>Archived</option>
              </select>
            </div>

            <!-- Filter & Reset Buttons -->
            <div class="col-6 col-md-12 col-lg-3 d-flex gap-2 justify-content-lg-end">
              <button type="submit" class="btn btn-sm btn-dark flex-fill flex-lg-grow-0 px-3">
                <i class="bi bi-funnel me-1"></i> Filter
              </button>
              <?php if ($hasActiveFilters): ?>
                <a href="products.php" class="btn btn-sm btn-outline-secondary px-3" title="Clear all filters">
                  <i class="bi bi-x-circle me-1"></i> Reset
                </a>
              <?php endif; ?>
            </div>

          </div>
        </form>
      </div>

      <?php if ($hasActiveFilters): ?>
        <div class="px-3 pb-3 pt-0 d-flex align-items-center flex-wrap gap-2 border-top pt-2">
          <small class="text-muted fw-bold">Active Filters:</small>
          
          <?php if ($filterSearch !== ''): ?>
            <span class="badge bg-light text-dark border">
              Search: "<?= htmlspecialchars($filterSearch) ?>"
              <a href="<?= 'products.php?' . http_build_query(array_merge($_GET, ['search' => ''])) ?>" class="text-danger ms-1 text-decoration-none" title="Remove search filter">&times;</a>
            </span>
          <?php endif; ?>

          <?php if ($filterCategory !== ''): ?>
            <?php 
              $catName = 'ID ' . $filterCategory;
              foreach ($categoriesList as $c) {
                if ((int)$c['id'] === (int)$filterCategory) { $catName = $c['name']; break; }
              }
            ?>
            <span class="badge bg-light text-dark border">
              Category: <?= htmlspecialchars($catName) ?>
              <a href="<?= 'products.php?' . http_build_query(array_merge($_GET, ['category' => ''])) ?>" class="text-danger ms-1 text-decoration-none" title="Remove category filter">&times;</a>
            </span>
          <?php endif; ?>

          <?php if ($filterBrand !== ''): ?>
            <?php 
              $brName = 'ID ' . $filterBrand;
              foreach ($brandsList as $b) {
                if ((int)$b['id'] === (int)$filterBrand) { $brName = $b['name']; break; }
              }
            ?>
            <span class="badge bg-light text-dark border">
              Brand: <?= htmlspecialchars($brName) ?>
              <a href="<?= 'products.php?' . http_build_query(array_merge($_GET, ['brand' => ''])) ?>" class="text-danger ms-1 text-decoration-none" title="Remove brand filter">&times;</a>
            </span>
          <?php endif; ?>

          <?php if ($filterStatus !== ''): ?>
            <span class="badge bg-light text-dark border">
              Status: <?= htmlspecialchars($filterStatus) ?>
              <a href="<?= 'products.php?' . http_build_query(array_merge($_GET, ['status' => ''])) ?>" class="text-danger ms-1 text-decoration-none" title="Remove status filter">&times;</a>
            </span>
          <?php endif; ?>

          <a href="products.php" class="small text-danger ms-2 text-decoration-none">Clear all filters</a>
        </div>
      <?php endif; ?>
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
                        <div><a href="product-edit.php?id=<?= (int)$prod['id'] ?>" class="fw-semibold text-dark text-decoration-none"><?= htmlspecialchars($prod['name']) ?></a></div>
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
                      <a href="product-edit.php?id=<?= (int)$prod['id'] ?>" class="btn btn-sm btn-outline-dark py-1 px-2" title="Edit product">
                        <i class="bi bi-pencil"></i>
                      </a>
                      <form action="actions/handle_delete_product.php" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete product \'<?= htmlspecialchars(addslashes($prod['name'])) ?>\'?');">
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
                  <div class="mb-2"><i class="bi <?= $hasActiveFilters ? 'bi-funnel text-secondary' : 'bi-box-seam text-secondary' ?> fs-1"></i></div>
                  <h6 class="fw-bold text-dark mb-2">
                    <?= $hasActiveFilters ? 'No Products Match Selected Filters' : 'No Products in Database' ?>
                  </h6>
                  <p class="mb-3 small">
                    <?= $hasActiveFilters ? 'Try adjusting or clearing your category, brand, or status filters.' : 'You have not added any footwear models yet.' ?>
                  </p>
                  <?php if ($hasActiveFilters): ?>
                    <a href="products.php" class="btn btn-sm btn-outline-dark">
                      <i class="bi bi-x-circle me-1"></i> Clear All Filters
                    </a>
                  <?php else: ?>
                    <a href="product-add.php" class="btn btn-sm btn-dark">
                      <i class="bi bi-plus-lg me-1"></i> Add Your First Product
                    </a>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>

      <div class="p-3 border-top d-flex justify-content-between align-items-center">
        <span class="small text-muted">
          <?php if ($hasActiveFilters): ?>
            Showing <strong><?= count($products) ?></strong> of <?= $totalProductsCount ?> product(s) matching filter criteria
          <?php else: ?>
            Showing <strong><?= count($products) ?></strong> product(s) in catalog
          <?php endif; ?>
        </span>
        <?php if ($hasActiveFilters): ?>
          <a href="products.php" class="btn btn-sm btn-link text-decoration-none text-muted p-0 small">
            Reset to All Products
          </a>
        <?php endif; ?>
      </div>
    </div>
  
      </main>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="assets/js/dashboard.js"></script>
</body>
</html>
