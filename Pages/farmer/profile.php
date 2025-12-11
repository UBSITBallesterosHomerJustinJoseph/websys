<?php
// farmer/profile.php
include '../../db_connect.php';

// Check if user is logged in and is a farmer
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'farmer') {
    header("location: ../customer/index.php");
    exit();
}

$farmer_id = $_SESSION['user_id'];
$farmer_profile = $farmcart->get_farmer_profile($farmer_id);

// Calculate total products count for sidebar
$count_query = "SELECT COUNT(*) as total FROM products WHERE created_by = ?";
$count_stmt = $farmcart->conn->prepare($count_query);
if ($count_stmt) {
    $count_stmt->bind_param("i", $farmer_id);
    $count_stmt->execute();
    $count_result = $count_stmt->get_result();
    $total_products_count = $count_result->fetch_assoc()['total'] ?? 0;
    $count_stmt->close();
} else {
    $total_products_count = 0;
}

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $farm_name = $farmcart->conn->real_escape_string($_POST['farm_name']);
    $farm_location = $farmcart->conn->real_escape_string($_POST['farm_location']);
    $farm_size = (int)$_POST['farm_size'];
    $farming_method = $farmcart->conn->real_escape_string($_POST['farming_method']);
    $years_experience = (int)$_POST['years_experience'];
    $certification_details = $farmcart->conn->real_escape_string($_POST['certification_details']);
    $bio = $farmcart->conn->real_escape_string($_POST['bio']);

    if ($farmer_profile) {
        // Update existing profile
        $sql = "UPDATE farmer_profiles SET 
                farm_name = ?, farm_location = ?, farm_size = ?, farming_method = ?, 
                years_experience = ?, certification_details = ?, bio = ?, updated_at = NOW()
                WHERE user_id = ?";
        $stmt = $farmcart->conn->prepare($sql);
        $stmt->bind_param("ssisisii", $farm_name, $farm_location, $farm_size, $farming_method, 
                         $years_experience, $certification_details, $bio, $farmer_id);
    } else {
        // Create new profile
        $sql = "INSERT INTO farmer_profiles 
                (user_id, farm_name, farm_location, farm_size, farming_method, years_experience, 
                 certification_details, bio, is_verified_farmer, created_at, updated_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0, NOW(), NOW())";
        $stmt = $farmcart->conn->prepare($sql);
        $stmt->bind_param("issisisis", $farmer_id, $farm_name, $farm_location, $farm_size, 
                         $farming_method, $years_experience, $certification_details, $bio);
    }

    if ($stmt->execute()) {
        $success = "Profile updated successfully!";
        $farmer_profile = $farmcart->get_farmer_profile($farmer_id); // Refresh profile data
    } else {
        $error = "Failed to update profile: " . $stmt->error;
    }
    $stmt->close();
}

// Set sidebar stats before including sidebar
$sidebar_stats = [
    'total_products' => $total_products_count,
    'pending_orders' => 0,
    'low_stock' => 0
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Edit Profile | FarmCart</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <style>
    body { background-color: #f8f9fa; margin: 0; padding: 0; overflow-x: hidden; }
    .dashboard-container { display: flex; min-height: 100vh; }
    .sidebar-column { width: 280px; min-width: 280px; background: linear-gradient(180deg, #4E653D 0%, #3a5230 100%); position: fixed; left: 0; top: 0; height: 100vh; overflow-y: auto; z-index: 1000; box-shadow: 2px 0 10px rgba(0,0,0,0.1); }
    .main-content-column { flex: 1; margin-left: 280px; min-height: 100vh; padding: 0; }
    .content-area { padding: 30px; min-height: 100vh; background: #f8f9fa; }
    .form-card { background: white; border-radius: 15px; box-shadow: 0 5px 25px rgba(0,0,0,0.08); border: none; }
  </style>
</head>
<body>
  <div class="dashboard-container">
    <div class="sidebar-column"><?php include '../../Includes/sidebar.php'; ?></div>
    <div class="main-content-column">
      <div class="content-area">
        <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-4 mb-4 border-bottom">
          <div>
            <h1 class="h2 text-success fw-bold"><i class="fas fa-user-edit me-2"></i>Edit Profile</h1>
            <p class="text-muted mb-0">Update your farm information and profile details.</p>
          </div>
        </div>

        <?php if (!empty($error)): ?>
          <div class="alert alert-danger alert-dismissible fade show">
            <i class="fas fa-exclamation-triangle me-2"></i><?= htmlspecialchars($error) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>
        <?php endif; ?>
        
        <?php if (!empty($success)): ?>
          <div class="alert alert-success alert-dismissible fade show">
            <i class="fas fa-check-circle me-2"></i><?= htmlspecialchars($success) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>
        <?php endif; ?>

        <div class="form-card p-4">
          <form method="post">
            <div class="row g-4">
              <div class="col-md-6">
                <h5 class="text-success mb-4"><i class="fas fa-tractor me-2"></i>Farm Information</h5>
                
                <div class="mb-3">
                  <label class="form-label fw-semibold">Farm Name *</label>
                  <input type="text" name="farm_name" class="form-control" 
                         value="<?= htmlspecialchars($farmer_profile['farm_name'] ?? '') ?>" 
                         placeholder="Enter your farm name" required>
                </div>

                <div class="mb-3">
                  <label class="form-label fw-semibold">Farm Location *</label>
                  <input type="text" name="farm_location" class="form-control" 
                         value="<?= htmlspecialchars($farmer_profile['farm_location'] ?? '') ?>" 
                         placeholder="Enter farm location" required>
                </div>

                <div class="mb-3">
                  <label class="form-label fw-semibold">Farm Size (hectares) *</label>
                  <input type="number" name="farm_size" class="form-control" 
                         value="<?= $farmer_profile['farm_size'] ?? '' ?>" 
                         placeholder="Enter farm size in hectares" step="0.1" min="0" required>
                </div>

                <div class="mb-3">
                  <label class="form-label fw-semibold">Farming Method</label>
                  <select name="farming_method" class="form-control">
                    <option value="">Select Farming Method</option>
                    <option value="Organic" <?= ($farmer_profile['farming_method'] ?? '') == 'Organic' ? 'selected' : '' ?>>Organic</option>
                    <option value="Conventional" <?= ($farmer_profile['farming_method'] ?? '') == 'Conventional' ? 'selected' : '' ?>>Conventional</option>
                    <option value="Hydroponic" <?= ($farmer_profile['farming_method'] ?? '') == 'Hydroponic' ? 'selected' : '' ?>>Hydroponic</option>
                    <option value="Aquaponic" <?= ($farmer_profile['farming_method'] ?? '') == 'Aquaponic' ? 'selected' : '' ?>>Aquaponic</option>
                    <option value="Permaculture" <?= ($farmer_profile['farming_method'] ?? '') == 'Permaculture' ? 'selected' : '' ?>>Permaculture</option>
                  </select>
                </div>
              </div>

              <div class="col-md-6">
                <h5 class="text-success mb-4"><i class="fas fa-user me-2"></i>Farmer Details</h5>

                <div class="mb-3">
                  <label class="form-label fw-semibold">Years of Experience *</label>
                  <input type="number" name="years_experience" class="form-control" 
                         value="<?= $farmer_profile['years_experience'] ?? '' ?>" 
                         placeholder="Enter years of farming experience" min="0" required>
                </div>

                <div class="mb-3">
                  <label class="form-label fw-semibold">Certifications</label>
                  <textarea name="certification_details" class="form-control" rows="3" 
                            placeholder="List any farming certifications or awards..."><?= htmlspecialchars($farmer_profile['certification_details'] ?? '') ?></textarea>
                </div>

                <div class="mb-3">
                  <label class="form-label fw-semibold">Bio/Description</label>
                  <textarea name="bio" class="form-control" rows="4" 
                            placeholder="Tell customers about your farm and farming practices..."><?= htmlspecialchars($farmer_profile['bio'] ?? '') ?></textarea>
                </div>
              </div>
            </div>

            <div class="row mt-4">
              <div class="col-12">
                <div class="d-flex gap-3">
                  <button type="submit" name="update_profile" class="btn btn-success btn-lg px-5">
                    <i class="fas fa-save me-2"></i>
                    Update Profile
                  </button>
                  <a href="index.php" class="btn btn-outline-secondary btn-lg">
                    <i class="fas fa-times me-2"></i>
                    Cancel
                  </a>
                </div>
              </div>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>