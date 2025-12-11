<?php
// Admin/index.php
include '../../db_connect.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("location: ../customer/index.php");
    exit();
}

$admin_id = $_SESSION['user_id'];

// Handle product approval (for products table)
if (isset($_GET['approve'])) {
    $id = (int)$_GET['approve'];
    $stmt = $farmcart->conn->prepare("UPDATE products SET approval_status='approved', is_listed=TRUE, reviewed_by=?, reviewed_at=NOW() WHERE product_id=? AND approval_status='pending'");
    if ($stmt) {
        $stmt->bind_param("ii", $admin_id, $id);
        $stmt->execute();
        $stmt->close();

        // Also approve any pending inventory lots for this product
        $update_lots = $farmcart->conn->prepare("UPDATE inventory_lots SET status='approved', approved_by=?, approved_at=NOW() WHERE product_id=? AND status='pending'");
        if ($update_lots) {
            $update_lots->bind_param("ii", $admin_id, $id);
            $update_lots->execute();
            $update_lots->close();
        }

        // Add notification
        addNotification('product_approved', $id);

        header("Location: index.php?tab=pending");
        exit();
    }
}

// Handle product decline
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['decline'])) {
    $id = (int)$_POST['decline_id'];
    $reason = $farmcart->conn->real_escape_string($_POST['decline_reason']);
    $stmt = $farmcart->conn->prepare("UPDATE products SET approval_status='rejected', admin_notes=?, reviewed_by=?, reviewed_at=NOW() WHERE product_id=? AND approval_status='pending'");
    if ($stmt) {
        $stmt->bind_param("sii", $reason, $admin_id, $id);
        $stmt->execute();
        $stmt->close();

        // Also reject any pending inventory lots for this product
        $update_lots = $farmcart->conn->prepare("UPDATE inventory_lots SET status='rejected', approved_by=?, approved_at=NOW(), rejection_reason=? WHERE product_id=? AND status='pending'");
        if ($update_lots) {
            $update_lots->bind_param("isi", $admin_id, $reason, $id);
            $update_lots->execute();
            $update_lots->close();
        }

        // Add notification
        addNotification('product_declined', $id);

        header("Location: index.php?tab=pending");
        exit();
    }
}

// Handle farmer verification
if (isset($_GET['verify_farmer'])) {
    $user_id = (int)$_GET['verify_farmer'];
    $stmt = $farmcart->conn->prepare("UPDATE farmer_profiles SET is_verified_farmer=1 WHERE user_id=?");
    if ($stmt) {
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->close();

        // Add notification
        addNotification('farmer_verified', $user_id);

        header("Location: index.php?tab=farmers");
        exit();
    }
}

// Handle category creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_category'])) {
    $category_name = $farmcart->conn->real_escape_string($_POST['category_name']);
    $category_type = $farmcart->conn->real_escape_string($_POST['category_type']);
    $description = $farmcart->conn->real_escape_string($_POST['description']);

    $stmt = $farmcart->conn->prepare("INSERT INTO categories (category_name, category_type, description, is_active) VALUES (?, ?, ?, 1)");
    if ($stmt) {
        $stmt->bind_param("sss", $category_name, $category_type, $description);
        $stmt->execute();
        $stmt->close();

        // Add notification
        addNotification('category_added', $farmcart->conn->insert_id);

        header("Location: index.php?tab=categories");
        exit();
    }
}

// Helper function to add notifications
function addNotification($type, $related_id) {
    global $farmcart, $admin_id;

    $notifications = [
        'product_approved' => ['title' => 'Product Approved', 'message' => 'A product has been approved.'],
        'product_declined' => ['title' => 'Product Declined', 'message' => 'A product has been declined.'],
        'farmer_verified' => ['title' => 'Farmer Verified', 'message' => 'A farmer has been verified.'],
        'category_added' => ['title' => 'Category Added', 'message' => 'A new category has been added.']
    ];

    if (isset($notifications[$type])) {
        $notification = $notifications[$type];
        $stmt = $farmcart->conn->prepare("INSERT INTO notifications (user_id, title, message, type, related_id, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
        if ($stmt) {
            $stmt->bind_param("isssi", $admin_id, $notification['title'], $notification['message'], $type, $related_id);
            $stmt->execute();
            $stmt->close();
        }
    }
}

// Fetch pending products (from products table)
$pending_query = "SELECT
                    p.product_id,
                    p.product_name,
                    p.description,
                    p.category_id,
                    p.unit_type,
                    p.base_price,
                    p.approval_status,
                    p.created_by,
                    p.created_at,
                    p.updated_at,
                    c.category_name,
                    c.category_type,
                    pi.image_url,
                    u.first_name as farmer_first,
                    u.last_name as farmer_last,
                    u.email as farmer_email,
                    fp.farm_name
                 FROM products p
                 LEFT JOIN categories c ON p.category_id = c.category_id
                 LEFT JOIN product_images pi ON p.product_id = pi.product_id AND pi.is_primary = TRUE
                 LEFT JOIN users u ON p.created_by = u.user_id
                 LEFT JOIN farmer_profiles fp ON p.created_by = fp.user_id
                 WHERE p.approval_status = 'pending'
                 ORDER BY p.created_at DESC";
$pending = $farmcart->conn->query($pending_query);

// Check for query error
if (!$pending) {
    echo "Error in pending query: " . $farmcart->conn->error;
    $pending = false;
}

// Fetch approved products
$approved_query = "SELECT
                    p.product_id,
                    p.product_name,
                    p.description,
                    p.category_id,
                    p.unit_type,
                    p.base_price,
                    p.approval_status,
                    p.created_by,
                    p.created_at,
                    p.updated_at,
                    c.category_name,
                    c.category_type,
                    pi.image_url,
                    u.first_name as farmer_first,
                    u.last_name as farmer_last,
                    u.email as farmer_email,
                    fp.farm_name
                 FROM products p
                 LEFT JOIN categories c ON p.category_id = c.category_id
                 LEFT JOIN product_images pi ON p.product_id = pi.product_id AND pi.is_primary = TRUE
                 LEFT JOIN users u ON p.created_by = u.user_id
                 LEFT JOIN farmer_profiles fp ON p.created_by = fp.user_id
                 WHERE p.approval_status = 'approved'
                 ORDER BY p.created_at DESC";
$approved = $farmcart->conn->query($approved_query);

if (!$approved) {
    echo "Error in approved query: " . $farmcart->conn->error;
    $approved = false;
}

// Fetch declined products
$declined_query = "SELECT
                    p.product_id,
                    p.product_name,
                    p.description,
                    p.category_id,
                    p.unit_type,
                    p.base_price,
                    p.approval_status,
                    p.created_by,
                    p.created_at,
                    p.updated_at,
                    c.category_name,
                    c.category_type,
                    pi.image_url,
                    u.first_name as farmer_first,
                    u.last_name as farmer_last,
                    u.email as farmer_email,
                    fp.farm_name,
                    u2.first_name as reviewer_first,
                    u2.last_name as reviewer_last
                 FROM products p
                 LEFT JOIN categories c ON p.category_id = c.category_id
                 LEFT JOIN product_images pi ON p.product_id = pi.product_id AND pi.is_primary = TRUE
                 LEFT JOIN users u ON p.created_by = u.user_id
                 LEFT JOIN farmer_profiles fp ON p.created_by = fp.user_id
                 LEFT JOIN users u2 ON p.reviewed_by = u2.user_id
                 WHERE p.approval_status = 'rejected'
                 ORDER BY p.reviewed_at DESC";
$declined = $farmcart->conn->query($declined_query);

if (!$declined) {
    echo "Error in declined query: " . $farmcart->conn->error;
    $declined = false;
}

// Fetch farmers for verification
$farmers_query = "
    SELECT u.user_id, u.first_name, u.last_name, u.email, u.phone_number,
           fp.farm_name, fp.farm_location, fp.farming_method, fp.is_verified_farmer
    FROM users u
    LEFT JOIN farmer_profiles fp ON u.user_id = fp.user_id
    WHERE u.role = 'farmer'
    ORDER BY fp.is_verified_farmer ASC, u.created_at DESC";
$farmers = $farmcart->conn->query($farmers_query);

if (!$farmers) {
    echo "Error in farmers query: " . $farmcart->conn->error;
    $farmers = false;
}

// Fetch categories
$categories = $farmcart->conn->query("SELECT * FROM categories ORDER BY category_name, category_type");
if (!$categories) {
    echo "Error in categories query: " . $farmcart->conn->error;
    $categories = false;
}

// Fetch all users
$users = $farmcart->conn->query("SELECT * FROM users ORDER BY created_at DESC");
if (!$users) {
    echo "Error in users query: " . $farmcart->conn->error;
    $users = false;
}

// Get stats for cards - safely handle errors
$stats = [
    'pending' => ($pending && is_object($pending)) ? $pending->num_rows : 0,
    'approved' => ($approved && is_object($approved)) ? $approved->num_rows : 0,
    'declined' => ($declined && is_object($declined)) ? $declined->num_rows : 0,
    'unverified_farmers' => 0,
    'categories' => ($categories && is_object($categories)) ? $categories->num_rows : 0,
    'users' => ($users && is_object($users)) ? $users->num_rows : 0
];

// Get unverified farmers count
$unverified_query = $farmcart->conn->query("SELECT COUNT(*) as count FROM farmer_profiles WHERE is_verified_farmer = 0");
if ($unverified_query) {
    $stats['unverified_farmers'] = $unverified_query->fetch_assoc()['count'];
}

// Determine active tab
$tab = isset($_GET['tab']) ? $_GET['tab'] : 'pending';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Admin Dashboard | FarmCart</title>
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../../Assets/css/admin.css">
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar Column -->
        <div class="sidebar-column">
            <?php include '../../Includes/admin_sidebar.php'; ?>
        </div>

        <!-- Main Content Column -->
        <div class="main-content-column">
            <!-- Top Navigation Bar -->
            <nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom">
                <div class="container-fluid">
                    <div class="d-flex justify-content-between w-100">
                        <div class="d-flex align-items-center">
                            <button class="btn btn-outline-secondary me-3 d-md-none" id="sidebarToggle">
                                <i class="fas fa-bars"></i>
                            </button>
                            <h4 class="mb-0 text-dark">
                                <i class="fas fa-user-shield me-2 text-primary"></i>
                                Admin Dashboard
                            </h4>
                        </div>
                        <div class="d-flex align-items-center">
                            <div class="dropdown">
                                <button class="btn btn-outline-secondary dropdown-toggle" type="button"
                                        data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="fas fa-user-circle me-2"></i>
                                    <?= htmlspecialchars($_SESSION['first_name'] . ' ' . $_SESSION['last_name']) ?>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li><a class="dropdown-item" href="edit_profile.php">
                                        <i class="fas fa-user-edit me-2"></i>Edit Profile
                                    </a></li>
                                    <li><a class="dropdown-item" href="change_password.php">
                                        <i class="fas fa-key me-2"></i>Change Password
                                    </a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item" href="../customer/index.php">
                                        <i class="fas fa-store me-2"></i>View Store
                                    </a></li>
                                    <li><a class="dropdown-item text-danger" href="../../Register/login.php?logout=1">
                                        <i class="fas fa-sign-out-alt me-2"></i>Logout
                                    </a></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </nav>

            <!-- Main Content Area -->
            <div class="content-area">
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-12">
                            <!-- Page Header -->
                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <h2 class="text-dark">
                                    <i class="fas fa-user-shield me-2"></i>Admin Dashboard
                                </h2>
                                <div class="text-muted">
                                    <?= date('F j, Y') ?>
                                </div>
                            </div>

                            <!-- Quick Stats -->
                            <div class="row mb-4">
                                <div class="col-md-2 col-6 mb-3">
                                    <div class="stats-card bg-primary" data-tab="pending">
                                        <div class="card-body">
                                            <h4><?= $stats['pending'] ?></h4>
                                            <p>Pending Products</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-2 col-6 mb-3">
                                    <div class="stats-card bg-success" data-tab="approved">
                                        <div class="card-body">
                                            <h4><?= $stats['approved'] ?></h4>
                                            <p>Approved Products</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-2 col-6 mb-3">
                                    <div class="stats-card bg-warning" data-tab="farmers">
                                        <div class="card-body">
                                            <h4><?= $stats['unverified_farmers'] ?></h4>
                                            <p>Pending Farmers</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-2 col-6 mb-3">
                                    <div class="stats-card bg-info" data-tab="categories">
                                        <div class="card-body">
                                            <h4><?= $stats['categories'] ?></h4>
                                            <p>Categories</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-2 col-6 mb-3">
                                    <div class="stats-card bg-secondary" data-tab="users">
                                        <div class="card-body">
                                            <h4><?= $stats['users'] ?></h4>
                                            <p>Total Users</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-2 col-6 mb-3">
                                    <div class="stats-card bg-danger">
                                        <div class="card-body">
                                            <h4><?= $stats['declined'] ?></h4>
                                            <p>Declined Products</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Tabs -->
                            <div class="admin-tabs">
                                <ul class="nav nav-tabs" id="adminTabs" role="tablist">
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link <?= $tab=='pending' ? 'active' : ''; ?>"
                                                id="pending-tab" data-bs-toggle="tab"
                                                data-bs-target="#pending" type="button" role="tab">
                                            <i class="fas fa-clock me-1"></i>Pending
                                            <span class="badge bg-primary ms-1"><?= $stats['pending']; ?></span>
                                        </button>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link <?= $tab=='approved' ? 'active' : ''; ?>"
                                                id="approved-tab" data-bs-toggle="tab"
                                                data-bs-target="#approved" type="button" role="tab">
                                            <i class="fas fa-check me-1"></i>Approved
                                            <span class="badge bg-success ms-1"><?= $stats['approved']; ?></span>
                                        </button>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link <?= $tab=='declined' ? 'active' : ''; ?>"
                                                id="declined-tab" data-bs-toggle="tab"
                                                data-bs-target="#declined" type="button" role="tab">
                                            <i class="fas fa-times me-1"></i>Declined
                                            <span class="badge bg-danger ms-1"><?= $stats['declined']; ?></span>
                                        </button>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link <?= $tab=='farmers' ? 'active' : ''; ?>"
                                                id="farmers-tab" data-bs-toggle="tab"
                                                data-bs-target="#farmers" type="button" role="tab">
                                            <i class="fas fa-tractor me-1"></i>Farmers
                                        </button>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link <?= $tab=='categories' ? 'active' : ''; ?>"
                                                id="categories-tab" data-bs-toggle="tab"
                                                data-bs-target="#categories" type="button" role="tab">
                                            <i class="fas fa-tags me-1"></i>Categories
                                        </button>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link <?= $tab=='users' ? 'active' : ''; ?>"
                                                id="users-tab" data-bs-toggle="tab"
                                                data-bs-target="#users" type="button" role="tab">
                                            <i class="fas fa-users me-1"></i>Users
                                        </button>
                                    </li>
                                </ul>

                                <div class="tab-content mt-3" id="tab-content">
                                    <!-- PENDING PRODUCTS -->
                                    <div class="tab-pane fade <?= $tab=='pending' ? 'show active' : ''; ?>" id="pending" role="tabpanel">
                                        <?php if ($pending && $pending->num_rows > 0): ?>
                                            <div class="row g-4">
                                                <?php while ($row = $pending->fetch_assoc()): ?>
                                                    <?php include 'partials/product_card.php'; ?>
                                                <?php endwhile; ?>
                                            </div>
                                        <?php else: ?>
                                            <div class="empty-state">
                                                <i class="fas fa-check-circle"></i>
                                                <h4>No Pending Products</h4>
                                                <p>All products have been reviewed.</p>
                                            </div>
                                        <?php endif; ?>
                                    </div>

                                    <!-- APPROVED PRODUCTS -->
                                    <div class="tab-pane fade <?= $tab=='approved' ? 'show active' : ''; ?>" id="approved" role="tabpanel">
                                        <?php if ($approved && $approved->num_rows > 0): ?>
                                            <div class="row g-4">
                                                <?php while ($row = $approved->fetch_assoc()): ?>
                                                    <?php include 'partials/product_card_approved.php'; ?>
                                                <?php endwhile; ?>
                                            </div>
                                        <?php else: ?>
                                            <div class="empty-state">
                                                <i class="fas fa-inbox"></i>
                                                <h4>No Approved Products</h4>
                                                <p>Approved products will appear here.</p>
                                            </div>
                                        <?php endif; ?>
                                    </div>

                                    <!-- DECLINED PRODUCTS -->
                                    <div class="tab-pane fade <?= $tab=='declined' ? 'show active' : ''; ?>" id="declined" role="tabpanel">
                                        <?php if ($declined && $declined->num_rows > 0): ?>
                                            <div class="row g-4">
                                                <?php while ($row = $declined->fetch_assoc()): ?>
                                                    <?php include 'partials/product_card_declined.php'; ?>
                                                <?php endwhile; ?>
                                            </div>
                                        <?php else: ?>
                                            <div class="empty-state">
                                                <i class="fas fa-check-circle"></i>
                                                <h4>No Declined Products</h4>
                                                <p>No products have been declined yet.</p>
                                            </div>
                                        <?php endif; ?>
                                    </div>

                                    <!-- FARMER MANAGEMENT -->
                                    <div class="tab-pane fade <?= $tab=='farmers' ? 'show active' : ''; ?>" id="farmers" role="tabpanel">
                                        <?php include 'partials/farmers_table.php'; ?>
                                    </div>

                                    <!-- CATEGORY MANAGEMENT -->
                                    <div class="tab-pane fade <?= $tab=='categories' ? 'show active' : ''; ?>" id="categories" role="tabpanel">
                                        <?php include 'partials/categories_section.php'; ?>
                                    </div>

                                    <!-- USER MANAGEMENT -->
                                    <div class="tab-pane fade <?= $tab=='users' ? 'show active' : ''; ?>" id="users" role="tabpanel">
                                        <?php include 'partials/users_table.php'; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Decline Modal -->
    <div class="modal fade" id="declineModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <form method="post" class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Decline Product</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="decline_id" id="decline_id" value="">
                    <div class="mb-3">
                        <label class="form-label">Reason for declining (required)</label>
                        <textarea name="decline_reason" id="decline_reason" class="form-control" rows="4" required></textarea>
                        <div class="form-text">This reason will be shown to the farmer.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="decline" class="btn btn-danger">Decline Product</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Image View Modal -->
    <div class="modal fade" id="imageModal" tabindex="-1" aria-labelledby="imageModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="imageModalLabel">Product Image</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center">
                    <img id="modalImage" src="" alt="Product Image" class="img-fluid rounded">
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom Admin JS -->
    <script src="../../Assets/js/admin.js"></script>
    <script>
        // View Image Function
        function viewImage(imageUrl, productName) {
            document.getElementById('modalImage').src = imageUrl;
            document.getElementById('imageModalLabel').textContent = productName;
            const modal = new bootstrap.Modal(document.getElementById('imageModal'));
            modal.show();
        }
    </script>
</body>
</html>
