<?php
include '../../db_connect.php';
// Check if user is logged in and is a farmer
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'farmer') {
    return; // Don't show sidebar if not a farmer
}

// Get farmer profile
$farmer_id = $_SESSION['user_id'];
$farmer_profile = $farmcart->get_farmer_profile($farmer_id);

// Get current page to highlight active link
$current_page = basename($_SERVER['PHP_SELF']);

// Get stats from passed data or set defaults
$total_products = $sidebar_stats['total_products'] ?? 0;
$pending_orders = $sidebar_stats['pending_orders'] ?? 0;
$low_stock = $sidebar_stats['low_stock'] ?? 0;
?>

<!-- Farmer Sidebar -->
<div class="farmer-sidebar-container">
    <div class="position-sticky pt-3">
        <!-- Farmer Profile Summary -->
        <div class="text-center mb-4 p-3 sidebar-profile rounded">
            <div class="farmer-avatar mb-3">
                <i class="fas fa-tractor fa-2x text-white"></i>
            </div>
            <h6 class="mb-1 fw-bold text-white">
                <?php 
                if ($farmer_profile && !empty($farmer_profile['farm_name'])) {
                    echo htmlspecialchars($farmer_profile['farm_name']);
                } else {
                    echo htmlspecialchars($_SESSION['first_name'] . ' ' . $_SESSION['last_name']);
                }
                ?>
            </h6>
            <small class="text-light">Farmer Account</small>
        </div>

        <!-- Navigation Menu -->
        <ul class="nav flex-column">
            <li class="nav-item mb-2">
                <a class="nav-link sidebar-link <?php echo ($current_page == 'index.php') ? 'active' : ''; ?>" href="index.php">
                    <i class="fas fa-tachometer-alt me-2"></i>
                    Dashboard
                </a>
            </li>
            
            <li class="nav-item mb-2">
                <a class="nav-link sidebar-link <?php echo ($current_page == 'add_product.php') ? 'active' : ''; ?>" href="add_product.php">
                    <i class="fas fa-plus-circle me-2"></i>
                    Add Product
                </a>
            </li>
            
            <li class="nav-item mb-2">
                <a class="nav-link sidebar-link <?php echo ($current_page == 'products.php') ? 'active' : ''; ?>" href="products.php">
                    <i class="fas fa-box me-2"></i>
                    My Products
                    <span class="badge bg-light text-dark float-end"><?= $total_products ?></span>
                </a>
            </li>
            
            <li class="nav-item mb-2">
                <a class="nav-link sidebar-link <?php echo ($current_page == 'inventory.php') ? 'active' : ''; ?>" href="inventory.php">
                    <i class="fas fa-warehouse me-2"></i>
                    Inventory
                </a>
            </li>
            
            <li class="nav-item mb-2">
                <a class="nav-link sidebar-link <?php echo ($current_page == 'profile.php') ? 'active' : ''; ?>" href="profile.php">
                    <i class="fas fa-user-edit me-2"></i>
                    Edit Profile
                </a>
            </li>
            
            <li class="nav-item mb-2">
                <a class="nav-link sidebar-link <?php echo ($current_page == 'orders.php') ? 'active' : ''; ?>" href="orders.php">
                    <i class="fas fa-shopping-cart me-2"></i>
                    Orders
                    <?php if (isset($sidebar_stats['pending_orders']) && $sidebar_stats['pending_orders'] > 0): ?>
                        <span class="badge bg-danger float-end"><?= $sidebar_stats['pending_orders']; ?></span>
                    <?php endif; ?>
                </a>
            </li>
            
            <li class="nav-item mb-2">
                <a class="nav-link sidebar-link <?php echo ($current_page == 'analytics.php') ? 'active' : ''; ?>" href="analytics.php">
                    <i class="fas fa-chart-line me-2"></i>
                    Analytics
                </a>
            </li>
        </ul>

        <!-- Quick Stats -->
        <div class="mt-4 p-3 sidebar-stats rounded">
            <h6 class="fw-bold mb-3 text-white">Quick Stats</h6>
            <div class="small text-light">
                <div class="d-flex justify-content-between mb-2">
                    <span>Total Products:</span>
                    <strong><?= $total_products ?></strong>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span>Pending Review:</span>
                    <strong><?= $pending_orders ?></strong>
                </div>
                <div class="d-flex justify-content-between">
                    <span>Farm Status:</span>
                    <strong><?= ($farmer_profile && $farmer_profile['is_verified_farmer']) ? 'Verified' : 'Pending' ?></strong>
                </div>
            </div>
        </div>

        <!-- Back to Store -->
        <div class="mt-3">
            <a href="../customer/index.php" class="btn btn-outline-light btn-sm w-100">
                <i class="fas fa-store me-1"></i>
                Back to Store
            </a>
        </div>
    </div>
</div>

<style>
.farmer-sidebar-container {
    padding: 20px 15px;
    height: 100%;
    overflow-y: auto;
}

.sidebar-profile {
    background: rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.2);
}

.sidebar-stats {
    background: rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.2);
}

.sidebar-link {
    color: rgba(255, 255, 255, 0.8) !important;
    border-radius: 8px;
    padding: 12px 15px;
    transition: all 0.3s ease;
    border: 1px solid transparent;
    margin-bottom: 5px;
    text-decoration: none;
    display: block;
}

.sidebar-link:hover {
    color: white !important;
    background-color: rgba(255, 255, 255, 0.1);
    border-color: rgba(255, 255, 255, 0.3);
    transform: translateX(5px);
    text-decoration: none;
}

.sidebar-link.active {
    background-color: rgba(255, 255, 255, 0.2);
    color: white !important;
    border-color: rgba(255, 255, 255, 0.4);
    font-weight: 600;
}

.sidebar-link.active i {
    color: white !important;
}

.sidebar-link i {
    color: rgba(255, 255, 255, 0.7);
    transition: color 0.3s ease;
    width: 20px;
    text-align: center;
}

.sidebar-link:hover i,
.sidebar-link.active i {
    color: white !important;
}

.farmer-avatar {
    width: 70px;
    height: 70px;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto;
    color: white;
    border: 2px solid rgba(255, 255, 255, 0.3);
}

/* Badge styling for sidebar */
.sidebar-link .badge {
    font-size: 0.7em;
    padding: 4px 8px;
}

/* Scrollbar styling */
.farmer-sidebar-container::-webkit-scrollbar {
    width: 6px;
}

.farmer-sidebar-container::-webkit-scrollbar-track {
    background: rgba(255, 255, 255, 0.1);
}

.farmer-sidebar-container::-webkit-scrollbar-thumb {
    background: rgba(255, 255, 255, 0.3);
    border-radius: 3px;
}

.farmer-sidebar-container::-webkit-scrollbar-thumb:hover {
    background: rgba(255, 255, 255, 0.5);
}
</style>