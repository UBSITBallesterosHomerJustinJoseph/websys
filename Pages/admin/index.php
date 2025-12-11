<?php
// Admin/index.php
include '../../db_connect.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("location: ../customer/index.php");
    exit();
}

$admin_id = $_SESSION['user_id'];

// Ensure expiration columns exist (inline safe migration)
function ensure_product_expiration_columns($conn) {
    $columns = [
        'expiration_duration_seconds' => "INT NULL DEFAULT NULL",
        'expires_at' => "DATETIME NULL DEFAULT NULL",
        'is_expired' => "TINYINT(1) NOT NULL DEFAULT 0",
        'approved_at' => "DATETIME NULL DEFAULT NULL"
    ];
    foreach ($columns as $name => $definition) {
        $check = $conn->query("SHOW COLUMNS FROM products LIKE '{$name}'");
        if ($check && $check->num_rows === 0) {
            $conn->query("ALTER TABLE products ADD COLUMN {$name} {$definition}");
        }
    }
}
ensure_product_expiration_columns($farmcart->conn);

// Mark expired products
$farmcart->conn->query("UPDATE products SET is_expired = 1, is_listed = 0 WHERE (is_expired IS NULL OR is_expired = 0) AND expires_at IS NOT NULL AND expires_at <= NOW()");

// Handle product approval (for products table)
if (isset($_GET['approve'])) {
    $id = (int)$_GET['approve'];
    $stmt = $farmcart->conn->prepare("UPDATE products 
                                      SET approval_status='approved',
                                          is_listed=TRUE,
                                          reviewed_by=?,
                                          reviewed_at=NOW(),
                                          approved_at=NOW(),
                                          expires_at = CASE 
                                              WHEN expiration_duration_seconds IS NOT NULL THEN DATE_ADD(NOW(), INTERVAL expiration_duration_seconds SECOND)
                                              ELSE NULL
                                          END,
                                          is_expired = 0
                                      WHERE product_id=? AND approval_status='pending'");
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

// Handle user deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_user'])) {
    $user_id_to_delete = (int)$_POST['user_id_to_delete'];
    
    // Prevent self-deletion
    if ($user_id_to_delete == $admin_id) {
        $_SESSION['error'] = 'You cannot delete your own account.';
        header("Location: index.php?tab=users");
        exit();
    }
    
    // Delete user (cascade will handle related records)
    $delete_stmt = $farmcart->conn->prepare("DELETE FROM users WHERE user_id = ?");
    if ($delete_stmt) {
        $delete_stmt->bind_param("i", $user_id_to_delete);
        if ($delete_stmt->execute()) {
            $_SESSION['success'] = 'User deleted successfully.';
        } else {
            $_SESSION['error'] = 'Failed to delete user: ' . $delete_stmt->error;
        }
        $delete_stmt->close();
    }
    
    header("Location: index.php?tab=users");
    exit();
}

// Handle category creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_category'])) {
    $category_name = $farmcart->conn->real_escape_string($_POST['category_name']);
    $category_type = $farmcart->conn->real_escape_string($_POST['category_type']);
    $description = $farmcart->conn->real_escape_string($_POST['description'] ?? '');
    $image_url = '';

    // Handle image upload
    if (isset($_FILES['category_image']) && $_FILES['category_image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../../Assets/images/categories/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $file_extension = strtolower(pathinfo($_FILES['category_image']['name'], PATHINFO_EXTENSION));
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        
        if (in_array($file_extension, $allowed_extensions)) {
            $file_name = 'category_' . time() . '_' . uniqid() . '.' . $file_extension;
            $file_path = $upload_dir . $file_name;
            
            if (move_uploaded_file($_FILES['category_image']['tmp_name'], $file_path)) {
                $image_url = 'Assets/images/categories/' . $file_name;
            }
        }
    }

    $stmt = $farmcart->conn->prepare("INSERT INTO categories (category_name, category_type, description, image_url, is_active) VALUES (?, ?, ?, ?, 1)");
    if ($stmt) {
        $stmt->bind_param("ssss", $category_name, $category_type, $description, $image_url);
        $stmt->execute();
        $stmt->close();

        // Add notification
        addNotification('category_added', $farmcart->conn->insert_id);

        header("Location: index.php?tab=categories");
        exit();
    }
}

// Handle category update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_category'])) {
    $category_id = intval($_POST['category_id']);
    $category_name = $farmcart->conn->real_escape_string($_POST['category_name']);
    $category_type = $farmcart->conn->real_escape_string($_POST['category_type']);
    $description = $farmcart->conn->real_escape_string($_POST['description'] ?? '');
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    // Get current image URL
    $current_image_query = $farmcart->conn->prepare("SELECT image_url FROM categories WHERE category_id = ?");
    $current_image_query->bind_param("i", $category_id);
    $current_image_query->execute();
    $current_image_result = $current_image_query->get_result();
    $current_image = $current_image_result->fetch_assoc()['image_url'] ?? '';
    $current_image_query->close();

    // Check if image should be deleted
    if (isset($_POST['delete_image']) && $_POST['delete_image'] == '1') {
        // Delete old image if exists
        if (!empty($current_image) && file_exists('../../' . $current_image)) {
            @unlink('../../' . $current_image);
        }
        $image_url = '';
    } else {
        $image_url = $current_image;
    }

    // Handle image upload if new image is provided
    if (isset($_FILES['category_image']) && $_FILES['category_image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../../Assets/images/categories/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $file_extension = strtolower(pathinfo($_FILES['category_image']['name'], PATHINFO_EXTENSION));
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        
        if (in_array($file_extension, $allowed_extensions)) {
            // Delete old image if exists
            if (!empty($current_image) && file_exists('../../' . $current_image)) {
                @unlink('../../' . $current_image);
            }

            $file_name = 'category_' . time() . '_' . uniqid() . '.' . $file_extension;
            $file_path = $upload_dir . $file_name;
            
            if (move_uploaded_file($_FILES['category_image']['tmp_name'], $file_path)) {
                $image_url = 'Assets/images/categories/' . $file_name;
            }
        }
    }

    $stmt = $farmcart->conn->prepare("UPDATE categories SET category_name = ?, category_type = ?, description = ?, image_url = ?, is_active = ? WHERE category_id = ?");
    if ($stmt) {
        $stmt->bind_param("ssssii", $category_name, $category_type, $description, $image_url, $is_active, $category_id);
        $stmt->execute();
        $stmt->close();

        header("Location: index.php?tab=categories");
        exit();
    }
}

// Handle category image deletion
if (isset($_GET['delete_category_image'])) {
    $category_id = intval($_GET['delete_category_image']);
    
    // Get image URL before deletion
    $image_query = $farmcart->conn->prepare("SELECT image_url FROM categories WHERE category_id = ?");
    $image_query->bind_param("i", $category_id);
    $image_query->execute();
    $image_result = $image_query->get_result();
    if ($image_result->num_rows > 0) {
        $image_data = $image_result->fetch_assoc();
        $image_path = '../../' . $image_data['image_url'];
        if (!empty($image_data['image_url']) && file_exists($image_path)) {
            @unlink($image_path);
        }
        
        // Update category to remove image URL
        $update_stmt = $farmcart->conn->prepare("UPDATE categories SET image_url = '' WHERE category_id = ?");
        $update_stmt->bind_param("i", $category_id);
        $update_stmt->execute();
        $update_stmt->close();
    }
    $image_query->close();

    header("Location: index.php?tab=categories");
    exit();
}

// Handle category deletion
if (isset($_GET['delete_category'])) {
    $category_id = intval($_GET['delete_category']);
    
    // Check if category has associated products
    $products_check = $farmcart->conn->prepare("SELECT COUNT(*) as product_count FROM products WHERE category_id = ?");
    $products_check->bind_param("i", $category_id);
    $products_check->execute();
    $products_result = $products_check->get_result();
    $product_count = $products_result->fetch_assoc()['product_count'] ?? 0;
    $products_check->close();
    
    if ($product_count > 0) {
        $_SESSION['admin_error'] = "Cannot delete category. There are {$product_count} product(s) associated with this category. Please remove or reassign products first.";
        header("Location: index.php?tab=categories");
        exit();
    }
    
    // Get image URL before deletion
    $image_query = $farmcart->conn->prepare("SELECT image_url FROM categories WHERE category_id = ?");
    $image_query->bind_param("i", $category_id);
    $image_query->execute();
    $image_result = $image_query->get_result();
    if ($image_result->num_rows > 0) {
        $image_data = $image_result->fetch_assoc();
        $image_path = '../../' . $image_data['image_url'];
        if (!empty($image_data['image_url']) && file_exists($image_path)) {
            @unlink($image_path);
        }
    }
    $image_query->close();

    // Delete category
    $stmt = $farmcart->conn->prepare("DELETE FROM categories WHERE category_id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $category_id);
        if ($stmt->execute()) {
            $_SESSION['admin_success'] = "Category deleted successfully!";
        } else {
            $_SESSION['admin_error'] = "Error deleting category: " . $stmt->error;
        }
        $stmt->close();
    } else {
        $_SESSION['admin_error'] = "Error preparing delete statement.";
    }

    header("Location: index.php?tab=categories");
    exit();
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
                    p.expires_at,
                    p.expiration_duration_seconds,
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
                   AND (p.is_expired IS NULL OR p.is_expired = 0)
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
                    p.expires_at,
                    p.expiration_duration_seconds,
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
                   AND (p.is_expired IS NULL OR p.is_expired = 0)
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
                    p.expires_at,
                    p.expiration_duration_seconds,
                    p.admin_notes,
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
                   AND (p.is_expired IS NULL OR p.is_expired = 0)
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
} else {
    // Reset pointer for use in partial
    $categories->data_seek(0);
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
                                    <div class="stats-card bg-danger" data-tab="declined">
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

    <!-- Product Details Modal -->
    <div class="modal fade" id="productDetailsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Product Information</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Product Name</label>
                            <div class="form-control bg-light" id="detailName"></div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Category</label>
                            <div class="form-control bg-light" id="detailCategory"></div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Unit Type</label>
                            <div class="form-control bg-light" id="detailUnit"></div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Base Price (₱)</label>
                            <div class="form-control bg-light" id="detailPrice"></div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Expiry</label>
                            <div class="form-control bg-light" id="detailExpiry"></div>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Product Description</label>
                            <div class="form-control bg-light" id="detailDescription" style="min-height: 120px;"></div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
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

        // Product details modal population
        const detailsModal = document.getElementById('productDetailsModal');
        if (detailsModal) {
            detailsModal.addEventListener('show.bs.modal', event => {
                const button = event.relatedTarget;
                if (!button) return;
                const productData = button.getAttribute('data-product');
                if (!productData) return;
                try {
                    const data = JSON.parse(productData);
                    document.getElementById('detailName').textContent = data.name || '—';
                    const categoryText = data.category
                        ? `${data.category}${data.category_type ? ' (' + data.category_type + ')' : ''}`
                        : '—';
                    document.getElementById('detailCategory').textContent = categoryText;
                    document.getElementById('detailUnit').textContent = data.unit || '—';
                    document.getElementById('detailPrice').textContent = data.price ? `₱${data.price}` : '—';
                    document.getElementById('detailDescription').textContent = data.description || '—';
                    // If provided, show expiry; otherwise keep previous text
                    const expiryEl = document.getElementById('detailExpiry');
                    if (expiryEl) {
                        expiryEl.textContent = data.expires_at || '—';
                    }
                } catch (e) {
                    console.error('Failed to parse product data', e);
                }
            });
        }
    </script>
</body>
</html>
