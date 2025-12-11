<?php
include '../../db_connect.php';

// Determine the current directory structure
$current_script = $_SERVER['SCRIPT_NAME'];
$is_in_pages_farmer = strpos($current_script, '../../Pages/farmer') !== false;

// Set logout path based on current location
if ($is_in_pages_farmer) {
    $logout_path = '../Register/login.php?logout=1';
} else {
    // Default fallback
    $logout_path = 'Register/login.php?logout=1';
}

// Admin/admin_sidebar.php
// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    return; // Don't show sidebar if not an admin
}

// Get current page to highlight active link
$current_page = basename($_SERVER['PHP_SELF']);

// Get admin stats from passed data or calculate them
$pending_products = isset($stats['pending']) ? $stats['pending'] : 0;
$unverified_farmers = isset($stats['unverified_farmers']) ? $stats['unverified_farmers'] : 0;
$total_users = isset($stats['users']) ? $stats['users'] : 0;
?>

<!-- Admin Sidebar -->
<div class="admin-sidebar-container">
    <div class="position-sticky pt-3">
        <!-- Admin Profile Summary -->
        <div class="text-center mb-4 p-3 admin-profile rounded">
            <div class="admin-avatar mb-3">
                <i class="fas fa-user-shield fa-2x text-white"></i>
            </div>
            <h6 class="mb-1 fw-bold text-white">
                <?= htmlspecialchars($_SESSION['first_name'] . ' ' . $_SESSION['last_name']) ?>
            </h6>
            <small class="text-light">Administrator</small>
            <div class="mt-2">
                <span class="badge bg-danger">Admin Level</span>
            </div>
        </div>

        <!-- Navigation Menu -->
        <ul class="nav flex-column">
            <li class="nav-item mb-2">
                <a class="nav-link admin-sidebar-link <?php echo ($current_page == 'index.php') ? 'active' : ''; ?>" href="index.php">
                    <i class="fas fa-tachometer-alt me-2"></i>
                    Dashboard
                </a>
            </li>

            <li class="nav-item mb-2">
                <a class="nav-link admin-sidebar-link <?php echo (in_array($current_page, ['index.php']) && isset($_GET['tab']) && $_GET['tab'] == 'pending') ? 'active' : ''; ?>" href="index.php?tab=pending">
                    <i class="fas fa-clock me-2"></i>
                    Pending Products
                    <?php if ($pending_products > 0): ?>
                        <span class="badge bg-warning float-end"><?= $pending_products ?></span>
                    <?php endif; ?>
                </a>
            </li>

            <li class="nav-item mb-2">
                <a class="nav-link admin-sidebar-link <?php echo (in_array($current_page, ['index.php']) && isset($_GET['tab']) && $_GET['tab'] == 'farmers') ? 'active' : ''; ?>" href="index.php?tab=farmers">
                    <i class="fas fa-tractor me-2"></i>
                    Farmer Management
                    <?php if ($unverified_farmers > 0): ?>
                        <span class="badge bg-warning float-end"><?= $unverified_farmers ?></span>
                    <?php endif; ?>
                </a>
            </li>

            <li class="nav-item mb-2">
                <a class="nav-link admin-sidebar-link <?php echo (in_array($current_page, ['index.php']) && isset($_GET['tab']) && $_GET['tab'] == 'categories') ? 'active' : ''; ?>" href="index.php?tab=categories">
                    <i class="fas fa-tags me-2"></i>
                    Categories
                </a>
            </li>

            <li class="nav-item mb-2">
                <a class="nav-link admin-sidebar-link <?php echo (in_array($current_page, ['index.php']) && isset($_GET['tab']) && $_GET['tab'] == 'users') ? 'active' : ''; ?>" href="index.php?tab=users">
                    <i class="fas fa-users me-2"></i>
                    User Management
                    <span class="badge bg-info float-end"><?= $total_users ?></span>
                </a>
            </li>


            <li class="nav-item mb-2">
                <a class="nav-link admin-sidebar-link <?php echo ($current_page == 'settings.php') ? 'active' : ''; ?>" href="settings.php">
                    <i class="fas fa-cog me-2"></i>
                    System Settings
                </a>
            </li>
        </ul>

        <!-- Admin Tools Section -->
        <div class="mt-4 p-3 admin-tools rounded">
            <h6 class="fw-bold mb-3 text-white">Admin Tools</h6>
            <div class="d-grid gap-2">
                <a href="edit_profile.php" class="btn btn-outline-light btn-sm">
                    <i class="fas fa-user-edit me-1"></i>
                    Edit Profile
                </a>
                <a href="change_password.php" class="btn btn-outline-light btn-sm">
                    <i class="fas fa-key me-1"></i>
                    Change Password
                </a>
                <a href="../customer/index.php" class="btn btn-outline-info btn-sm">
                    <i class="fas fa-store me-1"></i>
                    View Store
                </a>
            </div>
        </div>

        <!-- Quick Stats -->
        <div class="mt-3 p-3 admin-stats rounded">
            <h6 class="fw-bold mb-3 text-white">System Overview</h6>
            <div class="small text-light">
                <div class="d-flex justify-content-between mb-2">
                    <span>Pending Reviews:</span>
                    <strong class="badge bg-warning text-dark"><?= $pending_products ?></strong>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span>Farmers Pending:</span>
                    <strong class="badge bg-warning text-dark"><?= $unverified_farmers ?></strong>
                </div>
                <div class="d-flex justify-content-between">
                    <span>Total Users:</span>
                    <strong class="badge bg-info"><?= $total_users ?></strong>
                </div>
            </div>
        </div>

       <!-- Logout Button -->
<li><a class="dropdown-item text-danger"
       href="../../Register/login.php?logout=1"
       onclick="return confirm('Are you sure you want to logout?')">
    <i class="fas fa-sign-out-alt me-2"></i>Logout
</a></li>
<script>
function confirmLogout() {
    if (confirm('Are you sure you want to logout?')) {
        window.location.href = 'logout.php';
    }
}
</script>
    </div>
</div>

<style>
/* Admin Sidebar Styles */
.admin-sidebar-container {
    padding: 20px 15px;
    height: 100%;
    overflow-y: auto;
    background: linear-gradient(180deg, #2c3e50 0%, #1a2530 100%);
}

.admin-profile {
    background: rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.2);
}

.admin-tools {
    background: rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.2);
}

.admin-stats {
    background: rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.2);
}

.admin-sidebar-link {
    color: rgba(255, 255, 255, 0.8) !important;
    border-radius: 8px;
    padding: 12px 15px;
    transition: all 0.3s ease;
    border: 1px solid transparent;
    margin-bottom: 5px;
    text-decoration: none;
    display: block;
    background: rgba(255, 255, 255, 0.05);
}

.admin-sidebar-link:hover {
    color: white !important;
    background-color: rgba(41, 128, 185, 0.3);
    border-color: rgba(255, 255, 255, 0.3);
    transform: translateX(5px);
    text-decoration: none;
}

.admin-sidebar-link.active {
    background-color: rgba(41, 128, 185, 0.5);
    color: white !important;
    border-color: rgba(255, 255, 255, 0.4);
    font-weight: 600;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.admin-sidebar-link.active i {
    color: white !important;
}

.admin-sidebar-link i {
    color: rgba(255, 255, 255, 0.7);
    transition: color 0.3s ease;
    width: 20px;
    text-align: center;
}

.admin-sidebar-link:hover i,
.admin-sidebar-link.active i {
    color: white !important;
}

.admin-avatar {
    width: 70px;
    height: 70px;
    background: rgba(41, 128, 185, 0.5);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto;
    color: white;
    border: 2px solid rgba(255, 255, 255, 0.3);
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
}

/* Badge styling for admin sidebar */
.admin-sidebar-link .badge {
    font-size: 0.7em;
    padding: 4px 8px;
    font-weight: 600;
}

/* Scrollbar styling */
.admin-sidebar-container::-webkit-scrollbar {
    width: 6px;
}

.admin-sidebar-container::-webkit-scrollbar-track {
    background: rgba(255, 255, 255, 0.1);
}

.admin-sidebar-container::-webkit-scrollbar-thumb {
    background: rgba(255, 255, 255, 0.3);
    border-radius: 3px;
}

.admin-sidebar-container::-webkit-scrollbar-thumb:hover {
    background: rgba(255, 255, 255, 0.5);
}

/* Button styling */
.admin-tools .btn {
    transition: all 0.3s ease;
}

.admin-tools .btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .admin-sidebar-container {
        padding: 15px 10px;
    }

    .admin-sidebar-link {
        padding: 10px 12px;
        font-size: 0.9rem;
    }

    .admin-avatar {
        width: 60px;
        height: 60px;
    }
}
</style>
