<?php
session_start();
include '../../db_connect.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("location: ../customer/index.php");
    exit();
}

$admin_id = $_SESSION['user_id'];
$message = '';
$error = '';

// Fetch admin data
$admin_sql = "SELECT * FROM users WHERE user_id = ?";
$admin_stmt = $farmcart->conn->prepare($admin_sql);
$admin_stmt->bind_param("i", $admin_id);
$admin_stmt->execute();
$admin_result = $admin_stmt->get_result();
$admin = $admin_result->fetch_assoc();
$admin_stmt->close();

// Calculate stats for sidebar
$pending_query = $farmcart->conn->query("SELECT COUNT(*) as count FROM products WHERE approval_status = 'pending' AND (is_expired IS NULL OR is_expired = 0)");
$pending_count = 0;
if ($pending_query) {
    $pending_count = $pending_query->fetch_assoc()['count'];
}

$unverified_query = $farmcart->conn->query("SELECT COUNT(*) as count FROM farmer_profiles WHERE is_verified_farmer = 0");
$unverified_count = 0;
if ($unverified_query) {
    $unverified_count = $unverified_query->fetch_assoc()['count'];
}

$users_query = $farmcart->conn->query("SELECT COUNT(*) as count FROM users");
$users_count = 0;
if ($users_query) {
    $users_count = $users_query->fetch_assoc()['count'];
}

$stats = [
    'pending' => $pending_count,
    'unverified_farmers' => $unverified_count,
    'users' => $users_count
];

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone_number = trim($_POST['phone_number'] ?? '');
    $address = trim($_POST['address'] ?? '');
    
    if (empty($first_name) || empty($last_name) || empty($email)) {
        $error = "Please fill in all required fields.";
    } else {
        // Check if email is already taken by another user
        $check_email = $farmcart->conn->prepare("SELECT user_id FROM users WHERE email = ? AND user_id != ?");
        $check_email->bind_param("si", $email, $admin_id);
        $check_email->execute();
        $email_result = $check_email->get_result();
        
        if ($email_result->num_rows > 0) {
            $error = "Email is already taken by another user.";
        } else {
            $update_sql = "UPDATE users SET first_name = ?, last_name = ?, email = ?, phone_number = ?, address = ? WHERE user_id = ?";
            $update_stmt = $farmcart->conn->prepare($update_sql);
            $update_stmt->bind_param("sssssi", $first_name, $last_name, $email, $phone_number, $address, $admin_id);
            
            if ($update_stmt->execute()) {
                $_SESSION['first_name'] = $first_name;
                $_SESSION['last_name'] = $last_name;
                $message = "Profile updated successfully!";
                // Refresh admin data
                $admin_stmt = $farmcart->conn->prepare($admin_sql);
                $admin_stmt->bind_param("i", $admin_id);
                $admin_stmt->execute();
                $admin_result = $admin_stmt->get_result();
                $admin = $admin_result->fetch_assoc();
                $admin_stmt->close();
            } else {
                $error = "Failed to update profile: " . $update_stmt->error;
            }
            $update_stmt->close();
        }
        $check_email->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Edit Profile | Admin | FarmCart</title>
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../../Assets/css/admin.css">
    <style>
        .profile-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 25px rgba(0,0,0,0.08);
            padding: 2rem;
        }
        .profile-header {
            background: linear-gradient(135deg, #2c3e50 0%, #1a2530 100%);
            color: white;
            padding: 2rem;
            border-radius: 15px 15px 0 0;
            margin: -2rem -2rem 2rem -2rem;
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <div class="sidebar-column">
            <?php include '../../Includes/admin_sidebar.php'; ?>
        </div>

        <div class="main-content-column">
            <nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom">
                <div class="container-fluid">
                    <div class="d-flex justify-content-between w-100">
                        <div class="d-flex align-items-center">
                            <a href="index.php" class="btn btn-outline-secondary me-3">
                                <i class="fas fa-arrow-left me-1"></i>Back to Dashboard
                            </a>
                            <h4 class="mb-0 text-dark">
                                <i class="fas fa-user-edit me-2 text-primary"></i>Edit Profile
                            </h4>
                        </div>
                    </div>
                </div>
            </nav>

            <div class="content-area">
                <div class="container-fluid">
                    <div class="row justify-content-center">
                        <div class="col-md-10 col-lg-8">
                            <div class="profile-card">
                                <div class="profile-header">
                                    <h3 class="mb-0"><i class="fas fa-user-shield me-2"></i>Admin Profile</h3>
                                    <p class="mb-0 text-white-50">Update your personal information</p>
                                </div>

                                <?php if ($message): ?>
                                    <div class="alert alert-success alert-dismissible fade show">
                                        <i class="fas fa-check-circle me-2"></i><?= htmlspecialchars($message); ?>
                                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                    </div>
                                <?php endif; ?>

                                <?php if ($error): ?>
                                    <div class="alert alert-danger alert-dismissible fade show">
                                        <i class="fas fa-exclamation-circle me-2"></i><?= htmlspecialchars($error); ?>
                                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                    </div>
                                <?php endif; ?>

                                <form method="post">
                                    <input type="hidden" name="update_profile" value="1">
                                    
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold">First Name <span class="text-danger">*</span></label>
                                            <input type="text" name="first_name" class="form-control form-control-lg" 
                                                   value="<?= htmlspecialchars($admin['first_name'] ?? ''); ?>" required>
                                        </div>
                                        
                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold">Last Name <span class="text-danger">*</span></label>
                                            <input type="text" name="last_name" class="form-control form-control-lg" 
                                                   value="<?= htmlspecialchars($admin['last_name'] ?? ''); ?>" required>
                                        </div>
                                        
                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold">Email <span class="text-danger">*</span></label>
                                            <input type="email" name="email" class="form-control form-control-lg" 
                                                   value="<?= htmlspecialchars($admin['email'] ?? ''); ?>" required>
                                        </div>
                                        
                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold">Phone Number</label>
                                            <input type="text" name="phone_number" class="form-control form-control-lg" 
                                                   value="<?= htmlspecialchars($admin['phone_number'] ?? ''); ?>">
                                        </div>
                                        
                                        <div class="col-12">
                                            <label class="form-label fw-semibold">Address</label>
                                            <textarea name="address" class="form-control" rows="3"><?= htmlspecialchars($admin['address'] ?? ''); ?></textarea>
                                        </div>
                                        
                                        <div class="col-12 mt-4">
                                            <div class="d-flex gap-3">
                                                <button type="submit" class="btn btn-primary btn-lg">
                                                    <i class="fas fa-save me-2"></i>Save Changes
                                                </button>
                                                <a href="index.php" class="btn btn-outline-secondary btn-lg">
                                                    <i class="fas fa-times me-2"></i>Cancel
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

