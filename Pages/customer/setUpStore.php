<?php
include '../../db_connect.php';

// Check if user is logged in and is a customer
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'customer') {
    header("location: index.php");
    exit();
}

// Check if user can create store
if (!$farmcart->canBecomeFarmer($_SESSION['user_id'])) {
    header("location: index.php?error=You cannot create a store");
    exit();
}

// Handle store creation form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_store'])) {
    $result = $farmcart->setupFarmerStore(
        $_SESSION['user_id'],
        $_POST['store_name'],
        $_POST['store_address'],
        $_POST['farm_size'],
        $_POST['farming_method'],
        $_POST['years_experience'],
        $_POST['certification_details'],
        $_POST['bio']
    );

    if ($result['success']) {
        // Update session role to farmer
        $_SESSION['user_role'] = 'farmer';
        $success_message = "You set up your store successfully! Redirecting to your dashboard...";
        
        // Show success message for 3 seconds then redirect
        echo "<script>
            setTimeout(function() {
                window.location.href = '../farmer/index.php';
            }, 3000);
        </script>";
    } else {
        $error = $result['message'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup Your Farm Store | FarmCart</title>\
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- CSS -->
    <link rel="stylesheet" href="../../Assets/css/navbar.css">
    <link rel="stylesheet" href="../../Assets/css/customer.css">
</head>
<body>
    <!-- Include Navbar -->
    <?php include '../../Includes/navbar.php'; ?>
    <div class="dropdown">
  <button class="btn user-dropdown-btn dropdown-toggle"
          type="button"
          id="userDropdown"
          data-bs-toggle="dropdown"
          aria-expanded="false">
    <i class="fas fa-user-circle me-2"></i>
    <?php echo htmlspecialchars($_SESSION['first_name'] ?? 'Account'); ?>
  </button>

  <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
    <li><a class="dropdown-item" href="../Pages/customer/profile.php"><i class="fas fa-user me-2"></i> Profile</a></li>
    <li><a class="dropdown-item" href="../Pages/customer/edit-profile.php"><i class="fas fa-edit me-2"></i> Edit Profile</a></li>
    <li><hr class="dropdown-divider"></li>
    <?php if ($_SESSION['user_role'] === 'customer'): ?>
      <li><a class="dropdown-item" href="../Pages/customer/setUpStore.php"><i class="fas fa-store me-2"></i> Setup Store</a></li>
      <li><hr class="dropdown-divider"></li>
    <?php endif; ?>
    <li><a class="dropdown-item text-danger" href="?logout=1"><i class="fas fa-sign-out-alt me-2"></i> Logout</a></li>
  </ul>
</div>
<Style>
    
</Style>
    <!-- Store Setup Form -->
    <section class="container mt-5" style="padding-top: 100px;">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow">
                    <div class="card-header bg-success text-white">
                        <h3 class="mb-0"><i class="fas fa-store me-2"></i>Setup Your Farm Store</h3>
                    </div>
                    <div class="card-body">
                        <?php if (isset($success_message)): ?>
                            <div class="alert alert-success">
                                <i class="fas fa-check-circle me-2"></i>
                                <?php echo htmlspecialchars($success_message); ?>
                            </div>
                        <?php endif; ?>

                        <?php if (isset($error)): ?>
                            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                        <?php endif; ?>

                        <?php if (!isset($success_message)): ?>
                        <form method="post" id="storeForm">
  <!-- Farm Name -->
  <div class="mb-3">
    <label for="store_name" class="form-label">Farm Name *</label>
    <input type="text" class="form-control" id="store_name" name="store_name" required>
    <div class="form-text">Enter the official name of your farm (e.g., “Green Valley Organics”).</div>
  </div>

  <!-- Farm Location -->
  <div class="mb-3">
    <label for="store_address" class="form-label">Farm Location *</label>
    <textarea class="form-control" id="store_address" name="store_address" rows="2" required></textarea>
    <div class="form-text">Provide a detailed address or landmark so customers can identify your farm.</div>
  </div>

  <!-- Farm Size -->
  <div class="mb-3">
    <label for="farm_size" class="form-label">Farm Size (hectares)</label>
    <input type="number" class="form-control" id="farm_size" name="farm_size" min="0">
    <div class="form-text">Specify approximate size (e.g., 5 hectares). Leave blank if unsure.</div>
  </div>

  <!-- Farming Method -->
  <div class="mb-3">
    <label for="farming_method" class="form-label">Farming Method</label>
    <select class="form-select" id="farming_method" name="farming_method">
      <option value="Organic">Organic</option>
      <option value="Conventional">Conventional</option>
      <option value="Hydroponic">Hydroponic</option>
      <option value="Other">Other</option>
    </select>
    <div class="form-text">Choose your primary farming approach. This helps customers understand your practices.</div>
  </div>

  <!-- Years of Experience -->
  <div class="mb-3">
    <label for="years_experience" class="form-label">Years of Experience</label>
    <input type="number" class="form-control" id="years_experience" name="years_experience" min="0">
    <div class="form-text">Indicate how long you’ve been farming (e.g., 10 years).</div>
  </div>

  <!-- Certification Details -->
  <div class="mb-3">
    <label for="certification_details" class="form-label">Certification Details</label>
    <textarea class="form-control" id="certification_details" name="certification_details" rows="2"></textarea>
    <div class="form-text">List certifications (e.g., “Organic Certified by XYZ”). Leave blank if none.</div>
  </div>

  <!-- Farmer Bio -->
  <div class="mb-3">
    <label for="bio" class="form-label">Farmer Bio</label>
    <textarea class="form-control" id="bio" name="bio" rows="3"></textarea>
    <div class="form-text">Write a short introduction about yourself and your farming journey.</div>
  </div>

  <div class="d-grid gap-2">
    <button type="submit" name="create_store" class="btn btn-success btn-lg">
      <i class="fas fa-store me-2"></i>Create Farm Store
    </button>
    <a href="index.php" class="btn btn-secondary">Cancel</a>
  </div>
</form>

                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Bootstrap JS -->