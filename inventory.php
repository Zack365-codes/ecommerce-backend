<?php 
require_once __DIR__ . '/settings/session.php';
require_once __DIR__ . '/settings/auth.php';
require_once __DIR__ . '/settings/db.php';

$errors  = $_SESSION['errors']  ?? [];
$success = $_SESSION['success'] ?? '';
unset($_SESSION['errors'], $_SESSION['success']);

// 1. Simple Summary Queries (Aasan step-by-step tareeqa)

// 1. Total models count
$res1 = mysqli_query($conn, "SELECT COUNT(*) AS total FROM products");
$row1 = mysqli_fetch_assoc($res1);
$total_models = $row1 ? (int)$row1['total'] : 0;

// 2. Total units (pairs)
$res2 = mysqli_query($conn, "SELECT SUM(stock_quantity) AS total FROM products");
$row2 = mysqli_fetch_assoc($res2);
$total_units = $row2 ? (int)$row2['total'] : 0;

// 3. Low stock models count
$res3 = mysqli_query($conn, "SELECT COUNT(*) AS total FROM products WHERE stock_quantity > 0 AND stock_quantity <= low_stock_threshold");
$row3 = mysqli_fetch_assoc($res3);
$low_stock_count = $row3 ? (int)$row3['total'] : 0;

// 4. Out of stock models count
$res4 = mysqli_query($conn, "SELECT COUNT(*) AS total FROM products WHERE stock_quantity = 0");
$row4 = mysqli_fetch_assoc($res4);
$out_of_stock_count = $row4 ? (int)$row4['total'] : 0;

$stats = [
    'total_models'       => $total_models,
    'total_units'        => $total_units,
    'low_stock_count'    => $low_stock_count,
    'out_of_stock_count' => $out_of_stock_count
];

// 2. Filter parameter (all, in, low, out)
$stockFilter = trim($_GET['filter'] ?? '');

// 3. Fetch Products with Category and Brand names
$invQuery = "SELECT p.id, p.name, p.sku, p.stock_quantity, p.low_stock_threshold, p.status, p.image_url, 
                    c.name AS category_name, b.name AS brand_name
             FROM products p
             LEFT JOIN categories c ON c.id = p.category_id
             LEFT JOIN brands b ON b.id = p.brand_id";

if ($stockFilter === 'low') {
    $invQuery .= " WHERE p.stock_quantity > 0 AND p.stock_quantity <= p.low_stock_threshold";
} elseif ($stockFilter === 'out') {
    $invQuery .= " WHERE p.stock_quantity = 0";
} elseif ($stockFilter === 'in') {
    $invQuery .= " WHERE p.stock_quantity > p.low_stock_threshold";
}

$invQuery .= " ORDER BY p.stock_quantity ASC, p.id DESC";

$inventory = [];
$res = @mysqli_query($conn, $invQuery);
if ($res) {
    while ($row = mysqli_fetch_assoc($res)) {
        $inventory[] = $row;
    }
}

// 4. Fetch all products for the Adjustment Modal dropdown
$allProducts = [];
$allProdRes = @mysqli_query($conn, "SELECT id, name, sku, stock_quantity FROM products ORDER BY name ASC");
if ($allProdRes) {
    while ($row = mysqli_fetch_assoc($allProdRes)) {
        $allProducts[] = $row;
    }
}

// 5. Fetch all variants grouped by product_id
$variantsByProduct = [];
$varRes = @mysqli_query($conn, "SELECT id, product_id, sku_variant, size_eu, color_name, stock_quantity, status FROM product_variants ORDER BY size_eu ASC");
if ($varRes) {
    while ($v = mysqli_fetch_assoc($varRes)) {
        $variantsByProduct[(int)$v['product_id']][] = [
            'id'             => (int)$v['id'],
            'product_id'     => (int)$v['product_id'],
            'sku_variant'    => $v['sku_variant'],
            'size_eu'        => (int)$v['size_eu'],
            'color_name'     => $v['color_name'],
            'stock_quantity' => (int)$v['stock_quantity'],
            'status'         => $v['status']
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Inventory | SHOES LAB Admin Dashboard</title>
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
            <input type="text" class="form-control" id="tableSearchInput" placeholder="Quick search inventory...">
          </div>
        </div>

        <div class="topbar-actions">
          <a href="../front-site/index.html" target="_blank" class="btn btn-sm btn-outline-secondary d-none d-md-inline-flex align-items-center gap-1">
            <i class="bi bi-eye"></i> View Site
          </a>

          <div class="dropdown">
            <a href="#" class="d-flex align-items-center text-decoration-none dropdown-toggle text-dark" data-bs-toggle="dropdown" aria-expanded="false">
              <img src="https://images.unsplash.com/photo-1534528741775-53994a69daeb?q=80&w=200&auto=format&fit=crop" class="admin-avatar me-2" alt="Admin">
              <div class="d-none d-sm-block text-start">
                <div class="fw-semibold small leading-none"><?= htmlspecialchars($_SESSION['user_name'] ?? 'Admin') ?></div>
                <div class="text-muted" style="font-size: 0.7rem;"><?= htmlspecialchars($_SESSION['user_role'] ?? 'Store Manager') ?></div>
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
        
        <!-- Page Title & Header Actions -->
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
          <div>
            <h1 class="page-title">Warehouse Inventory Tracking</h1>
            <p class="page-subtitle">Real-time stock ledger across all footwear models and sizes.</p>
          </div>
          <div class="d-flex gap-2">
            <button class="btn btn-sm btn-dark" data-bs-toggle="modal" data-bs-target="#stockAdjustModal">
              <i class="bi bi-arrow-down-up me-1"></i> Quick Stock Adjustment
            </button>
          </div>
        </div>

        <!-- Feedback Alerts -->
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

        <!-- KPI Summary Cards -->
        <div class="row g-3 mb-4">
          <div class="col-sm-6 col-xl-3">
            <div class="admin-card p-3 d-flex align-items-center justify-content-between">
              <div>
                <span class="text-muted small text-uppercase fw-bold">Total Stock</span>
                <h3 class="fw-bold mb-0 text-dark"><?= number_format($stats['total_units']) ?> <small class="fs-6 fw-normal text-muted">pairs</small></h3>
              </div>
              <div class="rounded-circle bg-primary bg-opacity-10 text-primary p-3">
                <i class="bi bi-boxes fs-4"></i>
              </div>
            </div>
          </div>

          <div class="col-sm-6 col-xl-3">
            <div class="admin-card p-3 d-flex align-items-center justify-content-between">
              <div>
                <span class="text-muted small text-uppercase fw-bold">Active Models</span>
                <h3 class="fw-bold mb-0 text-dark"><?= (int)$stats['total_models'] ?> <small class="fs-6 fw-normal text-muted">styles</small></h3>
              </div>
              <div class="rounded-circle bg-info bg-opacity-10 text-info p-3">
                <i class="bi bi-layers fs-4"></i>
              </div>
            </div>
          </div>

          <div class="col-sm-6 col-xl-3">
            <a href="inventory.php?filter=low" class="text-decoration-none">
              <div class="admin-card p-3 d-flex align-items-center justify-content-between border <?= ($stats['low_stock_count'] > 0) ? 'border-warning' : '' ?>">
                <div>
                  <span class="text-muted small text-uppercase fw-bold">Low Stock Alert</span>
                  <h3 class="fw-bold mb-0 text-warning"><?= (int)$stats['low_stock_count'] ?> <small class="fs-6 fw-normal text-muted">models</small></h3>
                </div>
                <div class="rounded-circle bg-warning bg-opacity-10 text-warning p-3">
                  <i class="bi bi-exclamation-triangle fs-4"></i>
                </div>
              </div>
            </a>
          </div>

          <div class="col-sm-6 col-xl-3">
            <a href="inventory.php?filter=out" class="text-decoration-none">
              <div class="admin-card p-3 d-flex align-items-center justify-content-between border <?= ($stats['out_of_stock_count'] > 0) ? 'border-danger' : '' ?>">
                <div>
                  <span class="text-muted small text-uppercase fw-bold">Out of Stock</span>
                  <h3 class="fw-bold mb-0 text-danger"><?= (int)$stats['out_of_stock_count'] ?> <small class="fs-6 fw-normal text-muted">models</small></h3>
                </div>
                <div class="rounded-circle bg-danger bg-opacity-10 text-danger p-3">
                  <i class="bi bi-x-circle fs-4"></i>
                </div>
              </div>
            </a>
          </div>
        </div>

        <!-- Filter Pills Bar -->
        <div class="d-flex align-items-center gap-2 mb-3 flex-wrap">
          <span class="small text-muted fw-bold me-2">Quick Filter:</span>
          <a href="inventory.php" class="btn btn-sm <?= ($stockFilter === '') ? 'btn-dark' : 'btn-outline-secondary' ?>">
            All (<?= (int)$stats['total_models'] ?>)
          </a>
          <a href="inventory.php?filter=in" class="btn btn-sm <?= ($stockFilter === 'in') ? 'btn-dark' : 'btn-outline-secondary' ?>">
            In Stock
          </a>
          <a href="inventory.php?filter=low" class="btn btn-sm <?= ($stockFilter === 'low') ? 'btn-warning text-dark' : 'btn-outline-warning text-dark' ?>">
            Low Stock (<?= (int)$stats['low_stock_count'] ?>)
          </a>
          <a href="inventory.php?filter=out" class="btn btn-sm <?= ($stockFilter === 'out') ? 'btn-danger' : 'btn-outline-danger' ?>">
            Out of Stock (<?= (int)$stats['out_of_stock_count'] ?>)
          </a>
        </div>

        <!-- Inventory Table Card -->
        <div class="admin-card">
          <div class="admin-table-wrap">
            <table class="admin-table">
              <thead>
                <tr>
                  <th>Footwear Model</th>
                  <th>SKU</th>
                  <th>Category / Brand</th>
                  <th>Available Stock</th>
                  <th>Reorder Threshold</th>
                  <th>Stock Status</th>
                  <th>Action</th>
                </tr>
              </thead>
              <tbody class="filterable-table-body">
                <?php if (!empty($inventory)): ?>
                  <?php foreach ($inventory as $item): ?>
                    <?php 
                      $imgSrc = htmlspecialchars($item['image_url'] ?? '');
                      if (!$imgSrc) {
                          $imgSrc = 'https://images.unsplash.com/photo-1614252235316-8c857d38b5f4?q=80&w=200&auto=format&fit=crop';
                      }
                      
                      $stock = (int)$item['stock_quantity'];
                      $threshold = (int)$item['low_stock_threshold'];
                      
                      if ($stock <= 0) {
                          $badgeClass = 'bg-danger';
                          $statusLabel = 'Out of Stock';
                      } elseif ($stock <= $threshold) {
                          $badgeClass = 'bg-warning text-dark';
                          $statusLabel = 'Low Stock';
                      } else {
                          $badgeClass = 'bg-success';
                          $statusLabel = 'In Stock';
                      }
                    ?>
                    <tr>
                      <td>
                        <div class="d-flex align-items-start gap-3">
                          <img src="<?= $imgSrc ?>" class="rounded-1 mt-1" style="width: 44px; height: 44px; object-fit: cover;" alt="<?= htmlspecialchars($item['name']) ?>">
                          <div>
                            <strong class="text-dark d-block"><?= htmlspecialchars($item['name']) ?></strong>
                            <small class="text-muted"><?= htmlspecialchars($item['category_name'] ?? 'Footwear') ?></small>
                            <?php $prodVars = $variantsByProduct[(int)$item['id']] ?? []; ?>
                            <?php if (!empty($prodVars)): ?>
                              <div class="d-flex flex-wrap gap-1 mt-2">
                                <?php foreach ($prodVars as $v): ?>
                                  <?php 
                                    $vStock = (int)$v['stock_quantity'];
                                    $vClass = ($vStock <= 0) ? 'bg-danger text-white' : (($vStock <= 2) ? 'bg-warning text-dark' : 'bg-light text-dark border');
                                  ?>
                                  <span class="badge <?= $vClass ?>" style="font-size: 0.68rem;" title="<?= htmlspecialchars($v['color_name']) ?>: <?= $vStock ?> pairs">
                                    EU <?= (int)$v['size_eu'] ?>: <strong><?= $vStock ?></strong>
                                  </span>
                                <?php endforeach; ?>
                              </div>
                            <?php endif; ?>
                          </div>
                        </div>
                      </td>
                      <td><code><?= htmlspecialchars($item['sku']) ?></code></td>
                      <td>
                        <div class="small">
                          <span class="text-muted">Cat:</span> <?= htmlspecialchars($item['category_name'] ?? '—') ?><br>
                          <span class="text-muted">Brand:</span> <?= htmlspecialchars($item['brand_name'] ?? '—') ?>
                        </div>
                      </td>
                      <td>
                        <strong class="fs-6 <?= ($stock <= 0) ? 'text-danger' : (($stock <= $threshold) ? 'text-warning' : 'text-dark') ?>">
                          <?= $stock ?> pairs
                        </strong>
                      </td>
                      <td class="text-muted">
                        <?= $threshold ?> pairs
                      </td>
                      <td>
                        <span class="badge <?= $badgeClass ?>">
                          <?= $statusLabel ?>
                        </span>
                      </td>
                      <td>
                        <button type="button" 
                                class="btn btn-sm btn-outline-dark py-1 px-2 btn-adjust-stock" 
                                data-id="<?= (int)$item['id'] ?>" 
                                data-name="<?= htmlspecialchars($item['name']) ?>" 
                                data-sku="<?= htmlspecialchars($item['sku']) ?>"
                                data-stock="<?= $stock ?>" 
                                data-bs-toggle="modal" 
                                data-bs-target="#stockAdjustModal" 
                                title="Adjust stock">
                          <i class="bi bi-pencil-square me-1"></i> Adjust
                        </button>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                <?php else: ?>
                  <tr>
                    <td colspan="7" class="text-center py-5 text-muted">
                      <div class="mb-2"><i class="bi bi-boxes fs-1 text-secondary"></i></div>
                      <h6 class="fw-bold text-dark mb-2">No Footwear Models Found</h6>
                      <p class="mb-3 small">No inventory matches your current selection.</p>
                      <?php if ($stockFilter !== ''): ?>
                        <a href="inventory.php" class="btn btn-sm btn-outline-dark">
                          <i class="bi bi-x-circle me-1"></i> Clear Filter
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
            <span class="small text-muted">Showing <?= count($inventory) ?> item(s)</span>
          </div>
        </div>

      </main>
    </div>
  </div>

  <!-- Stock Adjust Modal with Variant Management -->
  <div class="modal fade" id="stockAdjustModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
      <div class="modal-content">
        <form action="actions/handle_adjust_stock.php" method="POST" id="stockAdjustForm">
          <div class="modal-header">
            <h5 class="modal-title fs-6 fw-bold">Stock & Variant Adjustment</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            
            <!-- Select Product -->
            <div class="mb-3">
              <label class="form-label small fw-bold">Select Footwear Model</label>
              <select name="product_id" id="modalProductSelect" class="form-select" required onchange="onModalProductChange(this.value)">
                <option value="">-- Choose a Product --</option>
                <?php foreach ($allProducts as $p): ?>
                  <option value="<?= (int)$p['id'] ?>">
                    <?= htmlspecialchars($p['name']) ?> (<?= htmlspecialchars($p['sku']) ?>) — Total Stock: <?= (int)$p['stock_quantity'] ?> pairs
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <!-- Nav Tabs: Quick Adjust vs Direct Edit Sizes -->
            <ul class="nav nav-pills nav-fill mb-3 p-1 bg-light rounded" id="adjustTabs" role="tablist">
              <li class="nav-item" role="presentation">
                <button class="nav-link active py-1 small fw-semibold" id="tab-quick-link" data-bs-toggle="pill" data-bs-target="#tab-quick-pane" type="button" role="tab" onclick="setAdjustMode('quick')">
                  <i class="bi bi-plus-slash-minus me-1"></i> Quick Adjust (+ / -)
                </button>
              </li>
              <li class="nav-item" role="presentation">
                <button class="nav-link py-1 small fw-semibold" id="tab-direct-link" data-bs-toggle="pill" data-bs-target="#tab-direct-pane" type="button" role="tab" onclick="setAdjustMode('direct_variants')">
                  <i class="bi bi-grid-3x3 me-1"></i> Edit Size Quantities Directly
                </button>
              </li>
            </ul>

            <input type="hidden" name="adjust_mode" id="adjustModeInput" value="quick">

            <div class="tab-content" id="adjustTabsContent">
              <!-- Tab 1: Quick Adjust (+/-) -->
              <div class="tab-pane fade show active" id="tab-quick-pane" role="tabpanel">
                
                <div class="mb-3">
                  <label class="form-label small fw-bold">Select Variant Size to Adjust</label>
                  <select name="variant_id" id="modalVariantSelect" class="form-select">
                    <option value="0">All Sizes (General Product Stock)</option>
                  </select>
                  <div class="form-text small text-muted">Choose a specific size (e.g. EU 41) to adjust only that size, or choose "All Sizes" for general change.</div>
                </div>

                <div class="row g-2 mb-3">
                  <div class="col-6">
                    <label class="form-label small fw-bold">Adjustment Type</label>
                    <select name="action_type" id="modalActionType" class="form-select">
                      <option value="add">Add Stock (+ Received / Restocked)</option>
                      <option value="subtract">Deduct Stock (- Damaged / Correction)</option>
                    </select>
                  </div>
                  <div class="col-6">
                    <label class="form-label small fw-bold">Quantity (Pairs)</label>
                    <input type="number" name="quantity" id="modalQuantityInput" class="form-control" value="5" min="1">
                  </div>
                </div>

              </div>

              <!-- Tab 2: Direct Edit Size Quantities -->
              <div class="tab-pane fade" id="tab-direct-pane" role="tabpanel">
                <div class="mb-3">
                  <label class="form-label small fw-bold mb-1">Variant Size Matrix (Editable)</label>
                  <p class="text-muted small mb-2">Directly set the stock pairs available for each shoe size:</p>
                  
                  <div class="table-responsive border rounded" style="max-height: 250px; overflow-y: auto;">
                    <table class="table table-sm table-bordered bg-white mb-0 align-middle small">
                      <thead class="table-light sticky-top">
                        <tr>
                          <th>Shoe Size</th>
                          <th>Colorway</th>
                          <th>Current Stock</th>
                          <th style="width: 140px;">New Stock (Pairs)</th>
                        </tr>
                      </thead>
                      <tbody id="modalVariantsTableBody">
                        <tr>
                          <td colspan="4" class="text-center py-3 text-muted">Please select a footwear model first.</td>
                        </tr>
                      </tbody>
                    </table>
                  </div>
                </div>
              </div>
            </div>

            <!-- Optional Reason Note -->
            <div class="mb-2">
              <label class="form-label small fw-bold">Audit Reference Note (Optional)</label>
              <input type="text" name="reference_note" class="form-control" placeholder="e.g. Workshop batch #4 arrival, or inventory audit correction">
            </div>

          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-sm btn-dark" id="modalSubmitBtn">Record Adjustment</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="assets/js/dashboard.js"></script>
  <script>
    const allVariantsByProduct = <?= json_encode($variantsByProduct) ?>;

    function setAdjustMode(mode) {
      document.getElementById('adjustModeInput').value = mode;
      const submitBtn = document.getElementById('modalSubmitBtn');
      if (mode === 'direct_variants') {
        submitBtn.textContent = 'Save All Variant Sizes';
      } else {
        submitBtn.textContent = 'Record Adjustment';
      }
    }

    function onModalProductChange(prodId) {
      prodId = parseInt(prodId) || 0;
      const variants = allVariantsByProduct[prodId] || [];
      
      // 1. Populate variant dropdown for Quick Adjust tab
      const varSelect = document.getElementById('modalVariantSelect');
      varSelect.innerHTML = '<option value="0">All Sizes (General Product Stock)</option>';
      
      // 2. Populate direct edit table
      const tableBody = document.getElementById('modalVariantsTableBody');
      tableBody.innerHTML = '';

      if (variants.length > 0) {
        variants.forEach(v => {
          // Dropdown option
          const opt = document.createElement('option');
          opt.value = v.id;
          opt.textContent = `Size EU ${v.size_eu} (${v.color_name}) — Current: ${v.stock_quantity} pairs`;
          varSelect.appendChild(opt);

          // Table row for direct edit
          const tr = document.createElement('tr');
          const badgeClass = v.stock_quantity <= 0 ? 'bg-danger text-white' : (v.stock_quantity <= 2 ? 'bg-warning text-dark' : 'bg-light text-dark border');
          tr.innerHTML = `
            <td><strong>EU ${v.size_eu}</strong></td>
            <td>${v.color_name}</td>
            <td><span class="badge ${badgeClass}">${v.stock_quantity} pairs</span></td>
            <td>
              <input type="number" name="variant_stocks[${v.id}]" class="form-control form-control-sm" value="${v.stock_quantity}" min="0">
            </td>
          `;
          tableBody.appendChild(tr);
        });
      } else {
        tableBody.innerHTML = '<tr><td colspan="4" class="text-center py-3 text-muted">No individual size variants registered for this product.</td></tr>';
      }
    }

    // When clicking 'Adjust' on any table row, auto-select that product in the modal
    document.querySelectorAll('.btn-adjust-stock').forEach(btn => {
      btn.addEventListener('click', function() {
        const prodId = this.getAttribute('data-id');
        const select = document.getElementById('modalProductSelect');
        if (select && prodId) {
          select.value = prodId;
          onModalProductChange(prodId);
        }
      });
    });
  </script>
</body>
</html>
