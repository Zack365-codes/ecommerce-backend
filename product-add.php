<?php 
require_once __DIR__ . '/settings/session.php';
require_once __DIR__ . '/settings/auth.php';
require_once __DIR__ . '/settings/db.php';

$errors  = $_SESSION['errors']  ?? [];
$success = $_SESSION['success'] ?? '';
unset($_SESSION['errors'], $_SESSION['success']);

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
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Add Product | SHOES LAB Admin Dashboard</title>
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
        <h1 class="page-title">Add New Footwear Model</h1>
        <p class="page-subtitle">Configure specifications, pricing, imagery, and size EU 39-46 variant matrix.</p>
      </div>
      <a href="products.php" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i> Back to Products
      </a>
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

    <form action="actions/handle_add_product.php" method="POST" enctype="multipart/form-data">
      <div class="row g-4">
        
        <!-- Left Column: Primary Details -->
        <div class="col-lg-8">
          
          <!-- Basic Info -->
          <div class="admin-card mb-4">
            <div class="admin-card-header"><h5 class="admin-card-title">1. Basic Information</h5></div>
            <div class="admin-card-body">
              <div class="row g-3">
                <div class="col-md-8">
                  <label class="form-label small fw-bold">Product Name <span class="text-danger">*</span></label>
                  <input type="text" name="name" class="form-control" placeholder="e.g. Royal Cap-Toe Oxford in Mahogany" required>
                </div>
                <div class="col-md-4">
                  <label class="form-label small fw-bold">SKU Code <span class="text-danger">*</span></label>
                  <input type="text" name="sku" class="form-control" placeholder="NF-OXF-25" required>
                </div>
                <div class="col-md-6">
                  <label class="form-label small fw-bold">Category <span class="text-danger">*</span></label>
                  <select name="category_id" class="form-select" required>
                    <option value="">-- Choose Category --</option>
                    <?php if (!empty($categories)): ?>
                      <?php foreach ($categories as $cat): ?>
                        <option value="<?= (int)$cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                      <?php endforeach; ?>
                    <?php else: ?>
                      <option value="" disabled>No categories available (Create in Categories first)</option>
                    <?php endif; ?>
                  </select>
                </div>
                <div class="col-md-6">
                  <label class="form-label small fw-bold">Atelier Brand <span class="text-danger">*</span></label>
                  <select name="brand_id" class="form-select" required>
                    <option value="">-- Choose Atelier Brand --</option>
                    <?php if (!empty($brands)): ?>
                      <?php foreach ($brands as $brand): ?>
                        <option value="<?= (int)$brand['id'] ?>"><?= htmlspecialchars($brand['name']) ?></option>
                      <?php endforeach; ?>
                    <?php else: ?>
                      <option value="" disabled>No brands available (Create in Brands first)</option>
                    <?php endif; ?>
                  </select>
                </div>
                <div class="col-12">
                  <label class="form-label small fw-bold">Short Silhouette Description</label>
                  <textarea name="short_description" class="form-control" rows="2" placeholder="Brief editorial note displayed on product card..."></textarea>
                </div>
                <div class="col-12">
                  <label class="form-label small fw-bold">Full Craftsmanship Description</label>
                  <textarea name="description" class="form-control" rows="4" placeholder="Detail the leather tanning, last shape, and resoling properties..."></textarea>
                </div>
              </div>
            </div>
          </div>

          <!-- Pricing & Inventory -->
          <div class="admin-card mb-4">
            <div class="admin-card-header"><h5 class="admin-card-title">2. Pricing in Pakistani Rupee (PKR)</h5></div>
            <div class="admin-card-body">
              <div class="row g-3">
                <div class="col-md-4">
                  <label class="form-label small fw-bold">Selling Price (PKR) <span class="text-danger">*</span></label>
                  <input type="number" step="0.01" name="price_pkr" class="form-control" placeholder="14999" required min="0">
                </div>
                <div class="col-md-4">
                  <label class="form-label small fw-bold">Original / Retail Price (PKR)</label>
                  <input type="number" step="0.01" name="original_price_pkr" class="form-control" placeholder="17500" min="0">
                </div>
                <div class="col-md-4">
                  <label class="form-label small fw-bold">Cost of Goods (Internal PKR)</label>
                  <input type="number" step="0.01" name="cost_price_pkr" class="form-control" placeholder="6500" min="0">
                </div>
              </div>
            </div>
          </div>

          <!-- Variants Matrix -->
          <div class="admin-card mb-4">
            <div class="admin-card-header d-flex justify-content-between align-items-center">
              <h5 class="admin-card-title">3. Size EU 39–46 Variant Inventory</h5>
              <span class="text-muted small">Auto-calculated total stock</span>
            </div>
            <div class="admin-card-body p-0">
              <table class="table variant-table table-bordered mb-0 small" id="variantsTable">
                <thead class="table-light">
                  <tr>
                    <th>Shoe Size</th>
                    <th>Colorway</th>
                    <th>Variant Suffix</th>
                    <th>Stock Qty</th>
                    <th>Status</th>
                    <th class="text-center" style="width: 100px;">Action</th>
                  </tr>
                </thead>
                <tbody id="variantsTableBody">
                  <?php
                  $sizes = [39, 40, 41, 42, 43, 44, 45, 46];
                  foreach ($sizes as $sz):
                  ?>
                    <tr id="variant-row-<?= $sz ?>">
                      <td>
                        <div class="input-group input-group-sm" style="width: 110px;">
                          <span class="input-group-text bg-white fw-bold">EU</span>
                          <input type="number" name="variants[<?= $sz ?>][size]" class="form-control form-control-sm fw-bold variant-size-input" value="<?= $sz ?>" min="20" max="65" required>
                        </div>
                      </td>
                      <td>
                        <input type="text" name="variants[<?= $sz ?>][color]" class="form-control form-control-sm" value="Nero Black">
                      </td>
                      <td><code>-[SKU]-<?= $sz ?></code></td>
                      <td>
                        <input type="number" name="variants[<?= $sz ?>][stock]" class="form-control form-control-sm variant-stock-input" value="4" min="0" style="width: 80px;">
                      </td>
                      <td><span class="text-success small"><i class="bi bi-check-circle"></i> Enabled</span></td>
                      <td class="text-center">
                        <button type="button" class="btn btn-sm btn-outline-danger py-0 px-2 btn-remove-variant" onclick="removeVariantRow(this)" title="Exclude this size">
                          <i class="bi bi-trash"></i> Remove
                        </button>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>

              <!-- Add Variant Button Toolbar -->
              <div class="p-2 bg-light border-top d-flex align-items-center justify-content-between">
                <button type="button" class="btn btn-sm btn-dark" onclick="addNewVariantRow()">
                  <i class="bi bi-plus-lg me-1"></i> Add Another Size Variant
                </button>
                <span class="text-muted small">Supports custom EU shoe sizes (e.g. EU 37, 38, 47, 48)</span>
              </div>
            </div>
          </div>

        </div>

        <!-- Right Column: Images & Meta -->
        <div class="col-lg-4">
          
          <!-- Image Upload Box -->
          <div class="admin-card mb-4">
            <div class="admin-card-header"><h5 class="admin-card-title">Product Imagery <span class="text-danger">*</span></h5></div>
            <div class="admin-card-body text-center">
              <div class="img-upload-box mb-3" onclick="document.getElementById('fileInputProd').click();" style="cursor: pointer;">
                <i class="bi bi-cloud-arrow-up display-5 text-muted mb-2 d-inline-block"></i>
                <div class="fw-semibold small">Click or drag images to upload</div>
                <div class="text-muted" style="font-size: 0.72rem;">PNG, JPG, or WEBP up to 5MB (Square 1:1 recommended)</div>
                <input type="file" name="image" id="fileInputProd" class="d-none product-image-input" data-preview-target="imagePreviewContainer" accept="image/png, image/jpeg, image/webp" required>
              </div>

              <div id="imagePreviewContainer" class="d-flex flex-wrap gap-2 justify-content-center">
                <span class="text-muted small fst-italic">No image selected yet</span>
              </div>
            </div>
          </div>

          <!-- Product Specifications -->
          <div class="admin-card mb-4">
            <div class="admin-card-header"><h5 class="admin-card-title">Footwear Attributes</h5></div>
            <div class="admin-card-body">
              <div class="mb-3">
                <label class="form-label small fw-bold">Upper Material</label>
                <input type="text" name="material" class="form-control form-control-sm" value="Full Grain Italian Calfskin">
              </div>
              <div class="mb-3">
                <label class="form-label small fw-bold">Sole Construction</label>
                <input type="text" name="sole_type" class="form-control form-control-sm" value="Goodyear Welted Closed-Channel Leather">
              </div>
              <div class="mb-3">
                <label class="form-label small fw-bold">Occasion</label>
                <input type="text" name="occasion" class="form-control form-control-sm" value="Formal Boardrooms, Black Tie">
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
                  <option value="Active" selected>Active (Visible in Store)</option>
                  <option value="Draft">Draft</option>
                  <option value="Archived">Archived</option>
                </select>
              </div>

              <div class="form-check mb-2">
                <input class="form-check-input" type="checkbox" name="is_new" value="1" id="flagNewArrival" checked>
                <label class="form-check-label small" for="flagNewArrival">New Arrival Tag</label>
              </div>
              <div class="form-check">
                <input class="form-check-input" type="checkbox" name="is_sale" value="1" id="flagOnSale">
                <label class="form-check-label small" for="flagOnSale">Show On Sale</label>
              </div>
            </div>
          </div>

          <!-- Action Buttons -->
          <div class="d-flex flex-column gap-2">
            <button type="submit" class="btn btn-dark w-100 py-2"><i class="bi bi-check-lg me-1"></i> Save Footwear Model</button>
            <a href="products.php" class="btn btn-outline-secondary w-100">Cancel</a>
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
  <script>
    let variantRowIndex = 100;

    function removeVariantRow(btn) {
      const row = btn.closest('tr');
      if (row) {
        row.remove();
      }
    }

    function addNewVariantRow(size = '', color = 'Nero Black', stock = 4) {
      const tbody = document.getElementById('variantsTableBody');
      if (!tbody) return;

      // If size not passed, calculate next size
      if (!size) {
        const existingSizes = Array.from(tbody.querySelectorAll('.variant-size-input')).map(input => parseInt(input.value) || 0);
        const maxExisting = existingSizes.length ? Math.max(...existingSizes) : 38;
        size = maxExisting + 1;
      }

      const rowId = 'var_' + (++variantRowIndex);
      const tr = document.createElement('tr');
      tr.id = 'variant-row-' + rowId;
      tr.innerHTML = `
        <td>
          <div class="input-group input-group-sm" style="width: 110px;">
            <span class="input-group-text bg-white fw-bold">EU</span>
            <input type="number" name="variants[${rowId}][size]" class="form-control form-control-sm fw-bold variant-size-input" value="${size}" min="20" max="65" required>
          </div>
        </td>
        <td>
          <input type="text" name="variants[${rowId}][color]" class="form-control form-control-sm" value="${color}">
        </td>
        <td><code>-[SKU]-VAR</code></td>
        <td>
          <input type="number" name="variants[${rowId}][stock]" class="form-control form-control-sm variant-stock-input" value="${stock}" min="0" style="width: 80px;">
        </td>
        <td><span class="text-success small"><i class="bi bi-check-circle"></i> Enabled</span></td>
        <td class="text-center">
          <button type="button" class="btn btn-sm btn-outline-danger py-0 px-2 btn-remove-variant" onclick="removeVariantRow(this)" title="Exclude this size">
            <i class="bi bi-trash"></i> Remove
          </button>
        </td>
      `;
      tbody.appendChild(tr);
    }
  </script>
</body>
</html>