<?php 
require_once __DIR__ . '/settings/session.php';
require_once __DIR__ . '/settings/auth.php';
require_once __DIR__ . '/settings/db.php';

$errors  = $_SESSION['errors']  ?? [];
$success = $_SESSION['success'] ?? '';
unset($_SESSION['errors'], $_SESSION['success']);

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    $_SESSION['errors'] = ["Invalid product selected for editing."];
    header("Location: products.php");
    exit;
}

// Fetch product details
$stmt = mysqli_prepare($conn, "SELECT * FROM products WHERE id = ?");
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$product = mysqli_fetch_assoc($res);
mysqli_stmt_close($stmt);

if (!$product) {
    $_SESSION['errors'] = ["Product with ID #$id was not found in the catalog."];
    header("Location: products.php");
    exit;
}

// Fetch categories dynamically
$categories = [];
$catResult = @mysqli_query($conn, "SELECT id, name FROM categories ORDER BY name ASC");
if ($catResult) {
    while ($row = mysqli_fetch_assoc($catResult)) {
        $categories[] = $row;
    }
}

// Fetch brands dynamically
$brands = [];
$brandResult = @mysqli_query($conn, "SELECT id, name FROM brands ORDER BY name ASC");
if ($brandResult) {
    while ($row = mysqli_fetch_assoc($brandResult)) {
        $brands[] = $row;
    }
}

// Fetch existing variants mapped by size_eu
$variants = [];
$varStmt = @mysqli_prepare($conn, "SELECT * FROM product_variants WHERE product_id = ?");
if ($varStmt) {
    mysqli_stmt_bind_param($varStmt, 'i', $id);
    mysqli_stmt_execute($varStmt);
    $varRes = mysqli_stmt_get_result($varStmt);
    while ($vRow = mysqli_fetch_assoc($varRes)) {
        $variants[(int)$vRow['size_eu']] = $vRow;
    }
    mysqli_stmt_close($varStmt);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Edit Product | SHOES LAB Admin Dashboard</title>
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
        <h1 class="page-title">Edit Footwear: <?= htmlspecialchars($product['name']) ?></h1>
        <p class="page-subtitle">Product ID: #<?= (int)$product['id'] ?> &bull; SKU: <?= htmlspecialchars($product['sku']) ?> &bull; Created <?= !empty($product['created_at']) ? date('d M Y', strtotime($product['created_at'])) : '—' ?></p>
      </div>
      <div class="d-flex gap-2">
        <a href="products.php" class="btn btn-sm btn-dark">
          <i class="bi bi-arrow-left me-1"></i> Back to Catalog
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

    <form action="actions/handle_edit_product.php" method="POST" enctype="multipart/form-data">
      <input type="hidden" name="id" value="<?= (int)$product['id'] ?>">

      <div class="row g-4">
        
        <div class="col-lg-8">
          <!-- Footwear Details -->
          <div class="admin-card mb-4">
            <div class="admin-card-header"><h5 class="admin-card-title">1. Footwear Details</h5></div>
            <div class="admin-card-body">
              <div class="row g-3">
                <div class="col-md-8">
                  <label class="form-label small fw-bold">Product Name <span class="text-danger">*</span></label>
                  <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($product['name']) ?>" required>
                </div>
                <div class="col-md-4">
                  <label class="form-label small fw-bold">SKU Code <span class="text-danger">*</span></label>
                  <input type="text" name="sku" class="form-control" value="<?= htmlspecialchars($product['sku']) ?>" required>
                </div>
                <div class="col-md-6">
                  <label class="form-label small fw-bold">Category <span class="text-danger">*</span></label>
                  <select name="category_id" class="form-select" required>
                    <option value="">-- Choose Category --</option>
                    <?php foreach ($categories as $cat): ?>
                      <option value="<?= (int)$cat['id'] ?>" <?= ($product['category_id'] == $cat['id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($cat['name']) ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="col-md-6">
                  <label class="form-label small fw-bold">Atelier Brand <span class="text-danger">*</span></label>
                  <select name="brand_id" class="form-select" required>
                    <option value="">-- Choose Atelier Brand --</option>
                    <?php foreach ($brands as $brand): ?>
                      <option value="<?= (int)$brand['id'] ?>" <?= ($product['brand_id'] == $brand['id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($brand['name']) ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="col-12">
                  <label class="form-label small fw-bold">Short Silhouette Description</label>
                  <textarea name="short_description" class="form-control" rows="2"><?= htmlspecialchars($product['short_description'] ?? '') ?></textarea>
                </div>
                <div class="col-12">
                  <label class="form-label small fw-bold">Full Craftsmanship Description</label>
                  <textarea name="description" class="form-control" rows="4"><?= htmlspecialchars($product['description'] ?? '') ?></textarea>
                </div>
              </div>
            </div>
          </div>

          <!-- Pricing in PKR -->
          <div class="admin-card mb-4">
            <div class="admin-card-header"><h5 class="admin-card-title">2. Pricing in Pakistani Rupee (PKR)</h5></div>
            <div class="admin-card-body">
              <div class="row g-3">
                <div class="col-md-4">
                  <label class="form-label small fw-bold">Selling Price (PKR) <span class="text-danger">*</span></label>
                  <input type="number" step="0.01" name="price_pkr" class="form-control" value="<?= htmlspecialchars($product['price_pkr']) ?>" required min="0">
                </div>
                <div class="col-md-4">
                  <label class="form-label small fw-bold">Original Price (PKR)</label>
                  <input type="number" step="0.01" name="original_price_pkr" class="form-control" value="<?= htmlspecialchars($product['original_price_pkr'] ?? '') ?>" min="0">
                </div>
                <div class="col-md-4">
                  <label class="form-label small fw-bold">Cost of Goods (Internal PKR)</label>
                  <input type="number" step="0.01" name="cost_price_pkr" class="form-control" value="<?= htmlspecialchars($product['cost_price_pkr'] ?? '') ?>" min="0">
                </div>
              </div>
            </div>
          </div>

          <!-- Size EU 39–46 Variants -->
          <div class="admin-card mb-4">
            <div class="admin-card-header d-flex justify-content-between align-items-center">
              <h5 class="admin-card-title">3. Size EU 39–46 Variant Inventory</h5>
              <span class="text-muted small">Total: <?= (int)$product['stock_quantity'] ?> pairs</span>
            </div>
            <div class="admin-card-body p-0">
              <table class="table variant-table table-bordered mb-0 small">
                <thead class="table-light">
                  <tr>
                    <th>Shoe Size</th>
                    <th>Colorway</th>
                    <th>Stock Qty</th>
                    <th>Status</th>
                  </tr>
                </thead>
                <tbody>
                  <?php
                  $sizes = [39, 40, 41, 42, 43, 44, 45, 46];
                  foreach ($sizes as $sz):
                    $vStock = isset($variants[$sz]) ? (int)$variants[$sz]['stock_quantity'] : 4;
                    $vColor = isset($variants[$sz]) ? $variants[$sz]['color_name'] : 'Nero Black';
                    $vStatus = $vStock > 5 ? 'In Stock' : ($vStock > 0 ? 'Low Stock' : 'Out of Stock');
                    $badgeClass = $vStock > 5 ? 'bg-success' : ($vStock > 0 ? 'bg-warning text-dark' : 'bg-danger');
                  ?>
                    <tr>
                      <td>
                        <strong>EU <?= $sz ?></strong>
                        <input type="hidden" name="variants[<?= $sz ?>][size]" value="<?= $sz ?>">
                      </td>
                      <td>
                        <input type="text" name="variants[<?= $sz ?>][color]" class="form-control form-control-sm" value="<?= htmlspecialchars($vColor) ?>">
                      </td>
                      <td>
                        <input type="number" name="variants[<?= $sz ?>][stock]" class="form-control form-control-sm" value="<?= $vStock ?>" min="0" style="width: 80px;">
                      </td>
                      <td><span class="badge <?= $badgeClass ?>"><?= $vStatus ?></span></td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <!-- Right Column: Images, Specs & Status -->
        <div class="col-lg-4">
          
          <!-- Image Box -->
          <div class="admin-card mb-4">
            <div class="admin-card-header"><h5 class="admin-card-title">Product Imagery</h5></div>
            <div class="admin-card-body text-center">
              <?php 
                $currentImg = htmlspecialchars($product['image_url'] ?? '');
                if (!$currentImg) {
                    $currentImg = 'https://images.unsplash.com/photo-1614252235316-8c857d38b5f4?q=80&w=200&auto=format&fit=crop';
                }
              ?>
              <div id="imagePreviewContainer" class="mb-3">
                <img src="<?= $currentImg ?>" class="img-thumbnail" style="width: 140px; height: 140px; object-fit: cover;" alt="<?= htmlspecialchars($product['name']) ?>">
              </div>
              
              <div class="img-upload-box mb-2 p-2" onclick="document.getElementById('fileInputEdit').click();" style="cursor: pointer; border: 1px dashed #ccc; border-radius: 6px;">
                <i class="bi bi-cloud-arrow-up fs-4 text-muted d-block"></i>
                <span class="small fw-semibold">Click to replace photo</span>
                <div class="text-muted" style="font-size: 0.7rem;">PNG, JPG, or WEBP up to 5MB</div>
                <input type="file" name="image" id="fileInputEdit" class="d-none product-image-input" data-preview-target="imagePreviewContainer" accept="image/png, image/jpeg, image/webp">
              </div>
              <div class="text-muted" style="font-size: 0.72rem;">Leave empty to keep the existing image.</div>
            </div>
          </div>

          <!-- Product Specifications -->
          <div class="admin-card mb-4">
            <div class="admin-card-header"><h5 class="admin-card-title">Footwear Attributes</h5></div>
            <div class="admin-card-body">
              <div class="mb-3">
                <label class="form-label small fw-bold">Upper Material</label>
                <input type="text" name="material" class="form-control form-control-sm" value="<?= htmlspecialchars($product['material'] ?? '') ?>">
              </div>
              <div class="mb-3">
                <label class="form-label small fw-bold">Sole Construction</label>
                <input type="text" name="sole_type" class="form-control form-control-sm" value="<?= htmlspecialchars($product['sole_type'] ?? '') ?>">
              </div>
              <div class="mb-3">
                <label class="form-label small fw-bold">Occasion</label>
                <input type="text" name="occasion" class="form-control form-control-sm" value="<?= htmlspecialchars($product['occasion'] ?? '') ?>">
              </div>
            </div>
          </div>

          <!-- Status & Badges -->
          <div class="admin-card mb-4">
            <div class="admin-card-header"><h5 class="admin-card-title">Publishing Flags</h5></div>
            <div class="admin-card-body">
              <div class="mb-3">
                <label class="form-label small fw-bold">Status</label>
                <select name="status" class="form-select form-select-sm">
                  <option value="Active" <?= ($product['status'] === 'Active') ? 'selected' : '' ?>>Active (Visible in Store)</option>
                  <option value="Draft" <?= ($product['status'] === 'Draft') ? 'selected' : '' ?>>Draft</option>
                  <option value="Archived" <?= ($product['status'] === 'Archived') ? 'selected' : '' ?>>Archived</option>
                  <option value="Out of Stock" <?= ($product['status'] === 'Out of Stock') ? 'selected' : '' ?>>Out of Stock</option>
                </select>
              </div>

              <div class="form-check mb-2">
                <input class="form-check-input" type="checkbox" name="is_new" value="1" id="flagNewArrival" <?= !empty($product['is_new']) ? 'checked' : '' ?>>
                <label class="form-check-label small" for="flagNewArrival">New Arrival Tag</label>
              </div>
              <div class="form-check">
                <input class="form-check-input" type="checkbox" name="is_sale" value="1" id="flagOnSale" <?= !empty($product['is_sale']) ? 'checked' : '' ?>>
                <label class="form-check-label small" for="flagOnSale">Show On Sale</label>
              </div>
            </div>
          </div>

          <!-- Buttons -->
          <button type="submit" class="btn btn-dark w-100 py-2 mb-2">
            <i class="bi bi-check-lg me-1"></i> Update Footwear Model
          </button>
          <a href="products.php" class="btn btn-outline-secondary w-100">Cancel</a>
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