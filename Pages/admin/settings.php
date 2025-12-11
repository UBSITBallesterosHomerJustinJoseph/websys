<?php
// Admin/settings.php
include '../../db_connect.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("location: ../customer/index.php");
    exit();
}

$admin_id = $_SESSION['user_id'];
$success = '';
$error = '';

// Create settings table if it doesn't exist
$create_table = "CREATE TABLE IF NOT EXISTS system_settings (
    setting_key VARCHAR(100) PRIMARY KEY,
    setting_value TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";
$farmcart->conn->query($create_table);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_settings'])) {
    $site_name = $farmcart->conn->real_escape_string($_POST['site_name']);
    $site_description = $farmcart->conn->real_escape_string($_POST['site_description']);
    $contact_email = $farmcart->conn->real_escape_string($_POST['contact_email']);
    $maintenance_mode = isset($_POST['maintenance_mode']) ? 1 : 0;
    $currency_symbol = $farmcart->conn->real_escape_string($_POST['currency_symbol']);

    // Save settings using INSERT ... ON DUPLICATE KEY UPDATE
    $settings = [
        'site_name' => $site_name,
        'site_description' => $site_description,
        'contact_email' => $contact_email,
        'maintenance_mode' => $maintenance_mode,
        'currency_symbol' => $currency_symbol
    ];

    foreach ($settings as $key => $value) {
        $stmt = $farmcart->conn->prepare("INSERT INTO system_settings (setting_key, setting_value) VALUES (?, ?) 
                                         ON DUPLICATE KEY UPDATE setting_value = ?, updated_at = NOW()");
        $stmt->bind_param("sss", $key, $value, $value);
        $stmt->execute();
        $stmt->close();
    }

    $success = "Settings saved successfully!";
}

// Fetch current settings
$settings_query = "SELECT setting_key, setting_value FROM system_settings";
$settings_result = $farmcart->conn->query($settings_query);
$current_settings = [];
while ($row = $settings_result->fetch_assoc()) {
    $current_settings[$row['setting_key']] = $row['setting_value'];
}

// Set defaults if not set
$site_name = $current_settings['site_name'] ?? 'FarmCart';
$site_description = $current_settings['site_description'] ?? 'Fresh From Farm To Table';
$contact_email = $current_settings['contact_email'] ?? 'admin@farmcart.com';
$maintenance_mode = isset($current_settings['maintenance_mode']) ? (int)$current_settings['maintenance_mode'] : 0;
$currency_symbol = $current_settings['currency_symbol'] ?? '₱';

// Include admin sidebar stats
$stats = [
    'pending' => 0,
    'approved' => 0,
    'declined' => 0,
    'unverified_farmers' => 0,
    'categories' => 0,
    'users' => 0
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>System Settings | Admin Dashboard</title>
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
            <div class="content-area">
                <h1 class="h2 mb-4">
                    <i class="fas fa-cog me-2"></i>System Settings
                </h1>

                <!-- Success Message -->
                <?php if (!empty($success)): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <i class="fas fa-check-circle me-2"></i>
                        <?= htmlspecialchars($success) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Error Message -->
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <?= htmlspecialchars($error) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Settings Form -->
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-sliders-h me-2"></i>General Settings</h5>
                    </div>
                    <div class="card-body">
                        <form method="post">
                            <div class="row g-4">
                                <!-- Site Name -->
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">
                                        <i class="fas fa-store me-2"></i>Site Name
                                    </label>
                                    <input type="text" name="site_name" class="form-control" 
                                           value="<?= htmlspecialchars($site_name) ?>" 
                                           placeholder="FarmCart" required>
                                    <small class="text-muted">The name of your marketplace</small>
                                </div>

                                <!-- Contact Email -->
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">
                                        <i class="fas fa-envelope me-2"></i>Contact Email
                                    </label>
                                    <input type="email" name="contact_email" class="form-control" 
                                           value="<?= htmlspecialchars($contact_email) ?>" 
                                           placeholder="admin@farmcart.com" required>
                                    <small class="text-muted">Main contact email address</small>
                                </div>

                                <!-- Site Description -->
                                <div class="col-12">
                                    <label class="form-label fw-bold">
                                        <i class="fas fa-info-circle me-2"></i>Site Description
                                    </label>
                                    <textarea name="site_description" class="form-control" rows="3" 
                                              placeholder="Fresh From Farm To Table"><?= htmlspecialchars($site_description) ?></textarea>
                                    <small class="text-muted">Brief description of your marketplace</small>
                                </div>

                                <!-- Currency Symbol -->
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">
                                        <i class="fas fa-dollar-sign me-2"></i>Currency Symbol
                                    </label>
                                    <input type="text" name="currency_symbol" class="form-control" 
                                           value="<?= htmlspecialchars($currency_symbol) ?>" 
                                           placeholder="₱" maxlength="5" required>
                                    <small class="text-muted">Currency symbol used throughout the site</small>
                                </div>

                                <!-- Maintenance Mode -->
                                <div class="col-md-6">
                                    <label class="form-label fw-bold d-block">
                                        <i class="fas fa-tools me-2"></i>Maintenance Mode
                                    </label>
                                    <div class="form-check form-switch mt-2">
                                        <input class="form-check-input" type="checkbox" name="maintenance_mode" 
                                               id="maintenance_mode" <?= $maintenance_mode ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="maintenance_mode">
                                            <?= $maintenance_mode ? 'Enabled' : 'Disabled' ?>
                                        </label>
                                    </div>
                                    <small class="text-muted d-block mt-1">
                                        When enabled, only admins can access the site
                                    </small>
                                </div>
                            </div>

                            <!-- Submit Button -->
                            <div class="mt-4">
                                <button type="submit" name="save_settings" class="btn btn-primary btn-lg">
                                    <i class="fas fa-save me-2"></i>Save Settings
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Update maintenance mode label on toggle
        document.getElementById('maintenance_mode').addEventListener('change', function() {
            const label = this.nextElementSibling;
            label.textContent = this.checked ? 'Enabled' : 'Disabled';
        });
    </script>
</body>
</html>


