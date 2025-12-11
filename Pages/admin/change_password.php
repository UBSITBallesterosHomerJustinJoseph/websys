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

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
        $error = "All password fields are required.";
    } elseif ($newPassword !== $confirmPassword) {
        $error = "New passwords do not match.";
    } elseif (strlen($newPassword) < 6) {
        $error = "New password must be at least 6 characters long.";
    } else {
        // Verify current password
        $checkPassword = $farmcart->conn->prepare("SELECT password FROM users WHERE user_id = ?");
        $checkPassword->bind_param("i", $admin_id);
        $checkPassword->execute();
        $passwordResult = $checkPassword->get_result();
        
        if ($passwordResult->num_rows > 0) {
            $userData = $passwordResult->fetch_assoc();
            if (password_verify($currentPassword, $userData['password'])) {
                // Update password
                $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                $updatePassword = $farmcart->conn->prepare("UPDATE users SET password = ? WHERE user_id = ?");
                $updatePassword->bind_param("si", $hashedPassword, $admin_id);
                
                if ($updatePassword->execute()) {
                    $message = "Password changed successfully!";
                } else {
                    $error = "Failed to change password: " . $updatePassword->error;
                }
                $updatePassword->close();
            } else {
                $error = "Current password is incorrect.";
            }
        } else {
            $error = "User not found.";
        }
        $checkPassword->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Change Password | Admin | FarmCart</title>
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../../Assets/css/admin.css">
    <style>
        .password-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 25px rgba(0,0,0,0.08);
            padding: 2rem;
        }
        .password-header {
            background: linear-gradient(135deg, #2c3e50 0%, #1a2530 100%);
            color: white;
            padding: 2rem;
            border-radius: 15px 15px 0 0;
            margin: -2rem -2rem 2rem -2rem;
        }
        .password-input-group {
            position: relative;
        }
        .password-toggle {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #6c757d;
            cursor: pointer;
            z-index: 10;
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
                                <i class="fas fa-key me-2 text-primary"></i>Change Password
                            </h4>
                        </div>
                    </div>
                </div>
            </nav>

            <div class="content-area">
                <div class="container-fluid">
                    <div class="row justify-content-center">
                        <div class="col-md-8 col-lg-6">
                            <div class="password-card">
                                <div class="password-header">
                                    <h3 class="mb-0"><i class="fas fa-key me-2"></i>Change Password</h3>
                                    <p class="mb-0 text-white-50">Update your account password</p>
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
                                    <input type="hidden" name="change_password" value="1">
                                    
                                    <div class="mb-4">
                                        <label class="form-label fw-semibold">Current Password <span class="text-danger">*</span></label>
                                        <div class="password-input-group">
                                            <input type="password" name="current_password" id="current_password" 
                                                   class="form-control form-control-lg" required>
                                            <button type="button" class="password-toggle" onclick="togglePassword('current_password', this)">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-4">
                                        <label class="form-label fw-semibold">New Password <span class="text-danger">*</span></label>
                                        <div class="password-input-group">
                                            <input type="password" name="new_password" id="new_password" 
                                                   class="form-control form-control-lg" minlength="6" required>
                                            <button type="button" class="password-toggle" onclick="togglePassword('new_password', this)">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        </div>
                                        <div class="form-text">Minimum 6 characters</div>
                                    </div>
                                    
                                    <div class="mb-4">
                                        <label class="form-label fw-semibold">Confirm New Password <span class="text-danger">*</span></label>
                                        <div class="password-input-group">
                                            <input type="password" name="confirm_password" id="confirm_password" 
                                                   class="form-control form-control-lg" minlength="6" required>
                                            <button type="button" class="password-toggle" onclick="togglePassword('confirm_password', this)">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        </div>
                                    </div>
                                    
                                    <div class="alert alert-info">
                                        <i class="fas fa-info-circle me-2"></i>
                                        <strong>Password Requirements:</strong>
                                        <ul class="mb-0 mt-2">
                                            <li>Minimum 6 characters</li>
                                            <li>Use a combination of letters, numbers, and symbols for better security</li>
                                        </ul>
                                    </div>
                                    
                                    <div class="d-flex gap-3">
                                        <button type="submit" class="btn btn-primary btn-lg">
                                            <i class="fas fa-save me-2"></i>Change Password
                                        </button>
                                        <a href="index.php" class="btn btn-outline-secondary btn-lg">
                                            <i class="fas fa-times me-2"></i>Cancel
                                        </a>
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
    <script>
        function togglePassword(inputId, button) {
            const input = document.getElementById(inputId);
            const icon = button.querySelector('i');
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }
    </script>
</body>
</html>

