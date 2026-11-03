<?php
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<aside class="admin-sidebar" style="background-color: #1A1A1A !important;">
  <div class="sidebar-brand" style="background-color: #1A1A1A !important;">
    <a href="index.php" class="sidebar-brand-title text-decoration-none">
      SHOES LAB <span class="badge-role">ADMIN</span>
    </a>
  </div>
  <ul class="sidebar-menu" style="background-color: #1A1A1A !important;">
    <li class="sidebar-heading">Operations</li>

    <li class="sidebar-item">
      <a href="index.php" class="sidebar-link <?= ($currentPage === 'index.php') ? 'active' : '' ?>">
        <i class="bi bi-grid"></i>
        <span>Overview</span>
      </a>
    </li>

    <li class="sidebar-item">
      <a href="products.php" class="sidebar-link <?= ($currentPage === 'products.php' || $currentPage === 'product-edit.php') ? 'active' : '' ?>">
        <i class="bi bi-box-seam"></i>
        <span>Products</span>
      </a>
    </li>

    <li class="sidebar-item">
      <a href="product-add.php" class="sidebar-link <?= ($currentPage === 'product-add.php') ? 'active' : '' ?>">
        <i class="bi bi-plus-circle"></i>
        <span>Add Product</span>
      </a>
    </li>

    <li class="sidebar-item">
      <a href="categories.php" class="sidebar-link <?= ($currentPage === 'categories.php') ? 'active' : '' ?>">
        <i class="bi bi-tags"></i>
        <span>Categories</span>
      </a>
    </li>

    <li class="sidebar-item">
      <a href="brands.php" class="sidebar-link <?= ($currentPage === 'brands.php') ? 'active' : '' ?>">
        <i class="bi bi-award"></i>
        <span>Brands</span>
      </a>
    </li>

    <li class="sidebar-item">
      <a href="inventory.php" class="sidebar-link <?= ($currentPage === 'inventory.php') ? 'active' : '' ?>">
        <i class="bi bi-boxes"></i>
        <span>Inventory</span>
      </a>
    </li>

    <li class="sidebar-item">
      <a href="orders.php" class="sidebar-link <?= ($currentPage === 'orders.php' || $currentPage === 'order-details.php') ? 'active' : '' ?>">
        <i class="bi bi-cart-check"></i>
        <span>Orders</span>
      </a>
    </li>

    <li class="sidebar-item">
      <a href="customers.php" class="sidebar-link <?= ($currentPage === 'customers.php' || $currentPage === 'customer-details.php') ? 'active' : '' ?>">
        <i class="bi bi-people"></i>
        <span>Customers</span>
      </a>
    </li>

    <li class="sidebar-item">
      <a href="reviews.php" class="sidebar-link <?= ($currentPage === 'reviews.php') ? 'active' : '' ?>">
        <i class="bi bi-chat-left-quote"></i>
        <span>Reviews</span>
      </a>
    </li>

    <li class="sidebar-item">
      <a href="banners.php" class="sidebar-link <?= ($currentPage === 'banners.php') ? 'active' : '' ?>">
        <i class="bi bi-images"></i>
        <span>Banners</span>
      </a>
    </li>

    <li class="sidebar-item">
      <a href="website-content.php" class="sidebar-link <?= ($currentPage === 'website-content.php') ? 'active' : '' ?>">
        <i class="bi bi-layout-text-window-reverse"></i>
        <span>Website Content</span>
      </a>
    </li>

    <li class="sidebar-item">
      <a href="settings.php" class="sidebar-link <?= ($currentPage === 'settings.php') ? 'active' : '' ?>">
        <i class="bi bi-gear"></i>
        <span>Settings</span>
      </a>
    </li>

    <li class="sidebar-item">
      <a href="profile.php" class="sidebar-link <?= ($currentPage === 'profile.php') ? 'active' : '' ?>">
        <i class="bi bi-person-badge"></i>
        <span>Profile</span>
      </a>
    </li>

  </ul>
  <div class="sidebar-footer" style="background-color: #1A1A1A !important;">
    <a href="../front-site/index.html" class="btn btn-outline-light btn-sm w-100 mb-2 text-start">
      <i class="bi bi-box-arrow-up-right me-2"></i> Visit Storefront
    </a>
    <a href="logout.php" class="btn btn-dark btn-sm w-100 text-start text-danger"> 
       <i class="bi bi-power me-2"></i> Sign Out
    </a>
  </div>
</aside>
