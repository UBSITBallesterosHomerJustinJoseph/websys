<?php
session_start();
include '../../db_connect.php';
require_once 'User.php';

// Guard: must be logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../index.php");
    exit();
}

$userObj = new User($conn);
$userId  = $_SESSION['user_id'];
$user    = $userObj->getById($userId); // Ensure this joins farmer_profiles table
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Customer Profile | FarmCart</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <!-- Bootstrap -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <!-- Fonts -->
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
  <!-- FarmCart Styles -->
  <link rel="stylesheet" href="../../Assets/css/navbar.css">

  <style>
    body { background-color: var(--light-bg); font-family: 'Inter', sans-serif; }
    .profile-header {
      background: linear-gradient(135deg, var(--primary-green), var(--dark-green));
      color: var(--white);
      padding: 5rem 0;
      text-align: center;
      border-bottom: 4px solid var(--accent-gold);
    }
    .profile-header h1 { font-weight: 800; font-size: 3rem; margin-bottom: 0.5rem; }
    .profile-header p { color: rgba(255,255,255,0.85); font-size: 1.2rem; }
    .accent-line { width: 100px; height: 4px; background: var(--accent-gold); margin: 1rem auto; border-radius: 2px; }
    .profile-card { border-radius: 16px; border: none; box-shadow: 0 8px 25px rgba(0,0,0,0.1); }
    .profile-icon { font-size: 4rem; color: var(--accent-gold); }
    .btn-edit {
      background: linear-gradient(135deg, var(--accent-gold), #E8C547);
      color: var(--dark-green); font-weight: 600; border-radius: 10px; border: none;
    }
    .btn-edit:hover { background: linear-gradient(135deg, #c19b2a, #d4b342); }
  </style>
</head>
<body>

  <!-- Navbar -->
  <?php include '../../Includes/navbar.php'; ?>

  <!-- Header -->
  <section class="profile-header">
    <div class="container">
      <h1><i class="fas fa-user-circle me-2 text-accent"></i> My Profile</h1>
      <p>View and manage your account details</p>
      <div class="accent-line"></div>
    </div>
  </section>

  <!-- Profile Card -->
  <div class="container py-5">
    <div class="card profile-card p-4">
      <div class="row align-items-center">
        <div class="col-md-3 text-center">
          <i class="fas fa-user-circle profile-icon"></i>
        </div>
        <div class="col-md-9 profile-info">
          <h5><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h5>
          <p><i class="fas fa-envelope me-2 text-success"></i><?php echo htmlspecialchars($user['email']); ?></p>
          <p><i class="fas fa-phone me-2 text-success"></i><?php echo htmlspecialchars($user['phone_number']); ?></p>
          <p><i class="fas fa-map-marker-alt me-2 text-success"></i><?php echo htmlspecialchars($user['address']); ?></p>
        </div>
      </div>
      <hr>

      <!-- Farm Info -->
      <h6 class="fw-bold text-dark-green"><i class="fas fa-seedling me-2 text-accent"></i> Farm Information</h6>
      <?php if (!empty($user['farm_name'])): ?>
        <div class="card mt-3 border-0 shadow-sm">
          <div class="card-body">
            <h5 class="card-title text-success d-flex align-items-center">
              <i class="fas fa-leaf me-2"></i><?php echo htmlspecialchars($user['farm_name']); ?>
              <?php if (!empty($user['is_verified_farmer']) && $user['is_verified_farmer']): ?>
                <span class="badge bg-success ms-2"><i class="fas fa-check"></i> Verified Farmer</span>
              <?php endif; ?>
            </h5>
            <ul class="list-unstyled mb-3">
              <li><i class="fas fa-map-marker-alt me-2 text-success"></i><?php echo htmlspecialchars($user['farm_location']); ?></li>
              <li><i class="fas fa-ruler-combined me-2 text-success"></i><?php echo htmlspecialchars($user['farm_size']); ?> hectares</li>
              <li><i class="fas fa-seedling me-2 text-success"></i><?php echo htmlspecialchars($user['farming_method']); ?></li>
              <li><i class="fas fa-calendar-alt me-2 text-success"></i><?php echo htmlspecialchars($user['years_experience']); ?> years experience</li>
              <li><i class="fas fa-certificate me-2 text-success"></i><?php echo htmlspecialchars($user['certification_details']); ?></li>
              <li><i class="fas fa-info-circle me-2 text-success"></i><?php echo htmlspecialchars($user['bio']); ?></li>
            </ul>
            <small class="text-muted">Created: <?php echo htmlspecialchars($user['created_at']); ?> | Updated: <?php echo htmlspecialchars($user['updated_at']); ?></small>
            <div class="d-flex gap-2 mt-3">
              <a href="edit-profile.php" class="btn btn-outline-success btn-sm"><i class="fas fa-edit me-1"></i>Edit Farm Profile</a>
              <a href="store-dashboard.php" class="btn btn-success btn-sm"><i class="fas fa-chart-line me-1"></i>Go to Dashboard</a>
            </div>
          </div>
        </div>
      <?php else: ?>
        <p class="text-muted">You don’t have a farm profile yet. <a href="setUpStore.php" class="text-success fw-bold">Setup your farm</a>.</p>
      <?php endif; ?>

      <div class="mt-3">
        <a href="edit-profile.php" class="btn btn-edit"><i class="fas fa-edit me-2"></i>Edit Profile</a>
      </div>
    </div>
  </div>

  <!-- Bootstrap JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
