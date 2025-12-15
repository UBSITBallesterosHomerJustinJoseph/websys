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
                <a class="nav-link sidebar-link <?php echo ($current_page == 'orders.php') ? 'active' : ''; ?>" href="orders.php">
                    <i class="fas fa-shopping-cart me-2"></i>
                    Orders
                    <span class="badge bg-warning float-end"><?= $pending_orders ?></span>
                </a>
            </li>
            
            <li class="nav-item mb-2">
                <a class="nav-link sidebar-link <?php echo ($current_page == 'inventory.php') ? 'active' : ''; ?>" href="inventory.php">
                    <i class="fas fa-warehouse me-2"></i>
                    Inventory
                    <?php if ($low_stock > 0): ?>
                    <span class="badge bg-danger float-end"><?= $low_stock ?></span>
                    <?php endif; ?>
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
            
            <li class="nav-item mb-2">
                <a class="nav-link sidebar-link <?php echo ($current_page == 'reviews.php') ? 'active' : ''; ?>" href="reviews.php">
                    <i class="fas fa-star me-2"></i>
                    Reviews
                </a>
            </li>
        </ul>

        <!-- Quick Stats -->
        <div class="mt-4 p-3 sidebar-stats rounded">
            <h6 class="fw-bold mb-3 text-white">Quick Stats</h6>
            <div class="small text-light">
                <div class="d-flex justify-content-between mb-2">
                    <span>Total Products:</span>
                    <strong class="text-warning"><?= $total_products ?></strong>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span>Pending Orders:</span>
                    <strong class="text-warning"><?= $pending_orders ?></strong>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span>Low Stock:</span>
                    <strong class="text-danger"><?= $low_stock ?></strong>
                </div>
                <div class="d-flex justify-content-between">
                    <span>Farm Status:</span>
                    <strong class="<?= ($farmer_profile && $farmer_profile['is_verified_farmer']) ? 'text-success' : 'text-warning' ?>">
                        <?= ($farmer_profile && $farmer_profile['is_verified_farmer']) ? 'Verified' : 'Pending' ?>
                    </strong>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="mt-3 p-3 sidebar-stats rounded">
            <h6 class="fw-bold mb-3 text-white">Quick Actions</h6>
            <div class="d-grid gap-2">
                <a href="add_product.php" class="btn btn-success btn-sm">
                    <i class="fas fa-plus me-1"></i> Add New Product
                </a>
                <a href="orders.php" class="btn btn-warning btn-sm">
                    <i class="fas fa-eye me-1"></i> View Orders
                </a>
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
/* Farmer Sidebar Styling */
.farmer-sidebar-container {
    background: linear-gradient(180deg, #0F2E15 0%, #1a4727 100%);
    padding: 20px 15px;
    height: 100vh;
    overflow-y: auto;
    border-right: 1px solid rgba(218, 226, 203, 0.1);
    box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
}

/* Profile Section */
.sidebar-profile {
    background: rgba(15, 46, 21, 0.8);
    border: 1px solid rgba(218, 226, 203, 0.2);
    position: relative;
    overflow: hidden;
}

.sidebar-profile::before {
    content: '';
    position: absolute;
    top: -50%;
    left: -50%;
    width: 200%;
    height: 200%;
    background: radial-gradient(circle, rgba(218, 226, 203, 0.1) 0%, transparent 70%);
}

/* Stats Section */
.sidebar-stats {
    background: rgba(15, 46, 21, 0.8);
    border: 1px solid rgba(218, 226, 203, 0.2);
    position: relative;
    overflow: hidden;
}

.sidebar-stats::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -50%;
    width: 200%;
    height: 200%;
    background: radial-gradient(circle, rgba(218, 226, 203, 0.05) 0%, transparent 70%);
}

/* Navigation Links */
.sidebar-link {
    color: rgba(218, 226, 203, 0.8) !important;
    background: transparent;
    border-radius: 8px;
    padding: 12px 15px;
    transition: all 0.3s ease;
    border: 1px solid transparent;
    margin-bottom: 5px;
    text-decoration: none;
    display: block;
    position: relative;
    overflow: hidden;
}

.sidebar-link::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(218, 226, 203, 0.1), transparent);
    transition: left 0.5s ease;
}

.sidebar-link:hover {
    color: #DAE2CB !important;
    background-color: rgba(218, 226, 203, 0.1);
    border-color: rgba(218, 226, 203, 0.3);
    transform: translateX(5px);
    text-decoration: none;
}

.sidebar-link:hover::before {
    left: 100%;
}

.sidebar-link.active {
    background: linear-gradient(90deg, rgba(218, 226, 203, 0.2), rgba(15, 46, 21, 0.8));
    color: #DAE2CB !important;
    border-color: rgba(218, 226, 203, 0.4);
    font-weight: 600;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
}

.sidebar-link.active::after {
    content: '';
    position: absolute;
    right: 0;
    top: 50%;
    transform: translateY(-50%);
    width: 3px;
    height: 60%;
    background: #DAE2CB;
    border-radius: 2px 0 0 2px;
}

.sidebar-link.active i {
    color: #DAE2CB !important;
}

.sidebar-link i {
    color: rgba(218, 226, 203, 0.7);
    transition: color 0.3s ease;
    width: 20px;
    text-align: center;
}

.sidebar-link:hover i,
.sidebar-link.active i {
    color: #DAE2CB !important;
}

/* Farmer Avatar */
.farmer-avatar {
    width: 70px;
    height: 70px;
    background: linear-gradient(135deg, rgba(218, 226, 203, 0.2), rgba(15, 46, 21, 0.8));
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto;
    color: #DAE2CB;
    border: 2px solid rgba(218, 226, 203, 0.3);
    position: relative;
    overflow: hidden;
}

.farmer-avatar::before {
    content: '';
    position: absolute;
    top: -10px;
    left: -10px;
    right: -10px;
    bottom: -10px;
    background: radial-gradient(circle, rgba(218, 226, 203, 0.1) 0%, transparent 70%);
}

/* Badge styling for sidebar */
.sidebar-link .badge {
    font-size: 0.7em;
    padding: 4px 8px;
    font-weight: 600;
    min-width: 24px;
    text-align: center;
}

/* Button Styling */
.btn-success {
    background: linear-gradient(135deg, #28a745, #218838);
    border: 1px solid rgba(218, 226, 203, 0.3);
    color: white;
}

.btn-success:hover {
    background: linear-gradient(135deg, #218838, #1e7e34);
    border-color: rgba(218, 226, 203, 0.5);
    transform: translateY(-1px);
}

.btn-warning {
    background: linear-gradient(135deg, #ffc107, #e0a800);
    border: 1px solid rgba(218, 226, 203, 0.3);
    color: #0F2E15;
    font-weight: 600;
}

.btn-warning:hover {
    background: linear-gradient(135deg, #e0a800, #d39e00);
    border-color: rgba(218, 226, 203, 0.5);
    transform: translateY(-1px);
}

.btn-outline-light {
    background: transparent;
    border: 1px solid rgba(218, 226, 203, 0.3);
    color: #DAE2CB;
}

.btn-outline-light:hover {
    background: rgba(218, 226, 203, 0.1);
    border-color: rgba(218, 226, 203, 0.5);
    color: #DAE2CB;
}

/* Scrollbar styling */
.farmer-sidebar-container::-webkit-scrollbar {
    width: 6px;
}

.farmer-sidebar-container::-webkit-scrollbar-track {
    background: rgba(218, 226, 203, 0.1);
    border-radius: 3px;
}

.farmer-sidebar-container::-webkit-scrollbar-thumb {
    background: rgba(218, 226, 203, 0.3);
    border-radius: 3px;
}

.farmer-sidebar-container::-webkit-scrollbar-thumb:hover {
    background: rgba(218, 226, 203, 0.5);
}

/* Text Colors */
.text-white {
    color: #DAE2CB !important;
}

.text-light {
    color: rgba(218, 226, 203, 0.8) !important;
}

.text-warning {
    color: #ffc107 !important;
}

.text-danger {
    color: #dc3545 !important;
}

.text-success {
    color: #28a745 !important;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .farmer-sidebar-container {
        height: auto;
        position: fixed;
        top: 0;
        left: -280px;
        width: 280px;
        z-index: 1050;
        transition: left 0.3s ease;
        box-shadow: 5px 0 15px rgba(0, 0, 0, 0.2);
    }
    
    .farmer-sidebar-container.show {
        left: 0;
    }
    
    .sidebar-link {
        padding: 10px 12px;
    }
    
    .farmer-avatar {
        width: 60px;
        height: 60px;
    }
}

/* Animation for sidebar */
@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateX(-20px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

.sidebar-link {
    animation: fadeIn 0.3s ease forwards;
    opacity: 0;
}

.sidebar-link:nth-child(1) { animation-delay: 0.1s; }
.sidebar-link:nth-child(2) { animation-delay: 0.15s; }
.sidebar-link:nth-child(3) { animation-delay: 0.2s; }
.sidebar-link:nth-child(4) { animation-delay: 0.25s; }
.sidebar-link:nth-child(5) { animation-delay: 0.3s; }
.sidebar-link:nth-child(6) { animation-delay: 0.35s; }
.sidebar-link:nth-child(7) { animation-delay: 0.4s; }
.sidebar-link:nth-child(8) { animation-delay: 0.45s; }

/* Mobile toggle button (add this to your main page if needed) */
.sidebar-toggle-btn {
    position: fixed;
    top: 15px;
    left: 15px;
    z-index: 1040;
    background: #0F2E15;
    border: 1px solid rgba(218, 226, 203, 0.3);
    color: #DAE2CB;
    border-radius: 4px;
    padding: 8px 12px;
    cursor: pointer;
    display: none;
}

@media (max-width: 768px) {
    .sidebar-toggle-btn {
        display: block;
    }
}
</style>

<!-- Mobile Toggle Script (add this to your main page) -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const toggleBtn = document.querySelector('.sidebar-toggle-btn');
    const sidebar = document.querySelector('.farmer-sidebar-container');
    
    if (toggleBtn && sidebar) {
        toggleBtn.addEventListener('click', function() {
            sidebar.classList.toggle('show');
        });
        
        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', function(e) {
            if (window.innerWidth <= 768) {
                if (!sidebar.contains(e.target) && !toggleBtn.contains(e.target) && sidebar.classList.contains('show')) {
                    sidebar.classList.remove('show');
                }
            }
        });
        
        // Add overlay when sidebar is open
        const createOverlay = () => {
            const overlay = document.createElement('div');
            overlay.className = 'sidebar-overlay';
            overlay.style.cssText = `
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: rgba(0, 0, 0, 0.5);
                z-index: 1049;
                display: none;
            `;
            document.body.appendChild(overlay);
            
            overlay.addEventListener('click', function() {
                sidebar.classList.remove('show');
                overlay.style.display = 'none';
            });
            
            return overlay;
        };
        
        const overlay = createOverlay();
        
        // Show/hide overlay with sidebar
        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.attributeName === 'class') {
                    if (sidebar.classList.contains('show')) {
                        overlay.style.display = 'block';
                    } else {
                        overlay.style.display = 'none';
                    }
                }
            });
        });
        
        observer.observe(sidebar, { attributes: true });
    }
});
</script>