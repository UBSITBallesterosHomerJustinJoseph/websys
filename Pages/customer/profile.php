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

  <link rel="stylesheet" href="../../Assets/css/variables.css">
  <style>
    :root {
      --primary-green: #0F2E15;
      --dark-green: #1a4a2a;
      --accent-gold: #FFD700;
      --light-bg: #f8f9fa;
      --white: #ffffff;
      --text-dark: #2c3e50;
      --text-muted: #6c757d;
      --border-color: #e9ecef;
      --shadow-sm: 0 2px 4px rgba(0,0,0,0.08);
      --shadow-md: 0 4px 12px rgba(0,0,0,0.1);
      --shadow-lg: 0 8px 24px rgba(0,0,0,0.12);
    }

    body { 
      background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
      font-family: 'Inter', sans-serif;
      padding-top: 4rem;
      min-height: 100vh;
    }

    /* Profile Header */
    .profile-header {
      background: linear-gradient(135deg, #0F2E15 0%, #1a4a2a 50%, #2d5a3a 100%);
      color: var(--white);
      padding: 6rem 0 4rem;
      text-align: center;
      position: relative;
      overflow: hidden;
      margin-top: -4rem;
    }

    .profile-header::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: url('data:image/svg+xml,<svg width="100" height="100" xmlns="http://www.w3.org/2000/svg"><circle cx="50" cy="50" r="2" fill="rgba(255,255,255,0.05)"/></svg>');
      opacity: 0.3;
    }

    .profile-header .container {
      position: relative;
      z-index: 1;
    }

    .profile-header h1 { 
      font-weight: 800; 
      font-size: clamp(2rem, 5vw, 3.5rem);
      margin-bottom: 0.75rem;
      text-shadow: 2px 2px 8px rgba(0,0,0,0.2);
      letter-spacing: -0.5px;
    }

    .profile-header p { 
      color: rgba(255,255,255,0.9); 
      font-size: clamp(1rem, 2vw, 1.25rem);
      font-weight: 400;
    }

    .accent-line { 
      width: 120px; 
      height: 5px; 
      background: linear-gradient(90deg, transparent, #FFD700, transparent);
      margin: 1.5rem auto;
      border-radius: 3px;
      box-shadow: 0 2px 8px rgba(255,215,0,0.3);
    }

    /* Profile Card */
    .profile-card { 
      border-radius: 20px; 
      border: none; 
      box-shadow: var(--shadow-lg);
      background: var(--white);
      overflow: hidden;
      transition: transform 0.3s ease, box-shadow 0.3s ease;
      margin-top: -3rem;
      position: relative;
      z-index: 2;
    }

    .profile-card:hover {
      transform: translateY(-4px);
      box-shadow: 0 12px 32px rgba(0,0,0,0.15);
    }

    .profile-icon-wrapper {
      background: linear-gradient(135deg, #f0f7ee 0%, #e8f0e5 100%);
      padding: 2rem;
      border-radius: 16px;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      margin-bottom: 1rem;
      box-shadow: var(--shadow-sm);
    }

    .profile-icon { 
      font-size: 5rem; 
      background: linear-gradient(135deg, #FFD700, #FFA500);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
    }

    .profile-info h5 {
      font-size: 1.75rem;
      font-weight: 700;
      color: var(--text-dark);
      margin-bottom: 1.25rem;
    }

    .profile-info p {
      color: var(--text-muted);
      font-size: 1rem;
      margin-bottom: 0.75rem;
      display: flex;
      align-items: center;
      gap: 0.5rem;
    }

    .profile-info i {
      color: #0F2E15;
      width: 20px;
      font-size: 1.1rem;
    }

    /* Farm Info Card */
    .farm-info-card {
      background: linear-gradient(135deg, #f0f7ee 0%, #ffffff 100%);
      border: 2px solid #e8f0e5;
      border-radius: 16px;
      padding: 1.5rem;
      margin-top: 1.5rem;
      transition: all 0.3s ease;
    }

    .farm-info-card:hover {
      border-color: #DAE2CB;
      box-shadow: var(--shadow-md);
      transform: translateY(-2px);
    }

    .farm-info-card h5 {
      color: #0F2E15;
      font-weight: 700;
      margin-bottom: 1rem;
      display: flex;
      align-items: center;
      gap: 0.5rem;
    }

    .farm-info-card ul {
      list-style: none;
      padding: 0;
    }

    .farm-info-card li {
      padding: 0.5rem 0;
      color: var(--text-dark);
      display: flex;
      align-items: flex-start;
      gap: 0.75rem;
      border-bottom: 1px solid rgba(0,0,0,0.05);
    }

    .farm-info-card li:last-child {
      border-bottom: none;
    }

    .farm-info-card li i {
      color: #0F2E15;
      margin-top: 0.25rem;
      flex-shrink: 0;
    }

    .badge-verified {
      background: linear-gradient(135deg, #28a745, #20c997);
      color: white;
      padding: 0.4rem 0.8rem;
      border-radius: 20px;
      font-size: 0.85rem;
      font-weight: 600;
      display: inline-flex;
      align-items: center;
      gap: 0.4rem;
      box-shadow: 0 2px 8px rgba(40,167,69,0.3);
    }

    /* Buttons */
    .btn-edit {
      background: linear-gradient(135deg, #FFD700, #FFA500);
      color: #0F2E15;
      font-weight: 700;
      border-radius: 12px;
      border: none;
      padding: 0.75rem 2rem;
      font-size: 1rem;
      transition: all 0.3s ease;
      box-shadow: 0 4px 12px rgba(255,215,0,0.3);
      display: inline-flex;
      align-items: center;
      gap: 0.5rem;
    }

    .btn-edit:hover { 
      background: linear-gradient(135deg, #FFC107, #FF8C00);
      transform: translateY(-2px);
      box-shadow: 0 6px 16px rgba(255,215,0,0.4);
      color: #0F2E15;
    }

    .btn-success {
      background: linear-gradient(135deg, #0F2E15, #1a4a2a);
      border: none;
      border-radius: 10px;
      padding: 0.6rem 1.5rem;
      font-weight: 600;
      transition: all 0.3s ease;
    }

    .btn-success:hover {
      background: linear-gradient(135deg, #1a4a2a, #2d5a3a);
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(15,46,21,0.3);
    }

    /* Section Headers */
    .section-header {
      font-weight: 700;
      color: #0F2E15;
      font-size: 1.25rem;
      margin-bottom: 1rem;
      display: flex;
      align-items: center;
      gap: 0.5rem;
      padding-bottom: 0.75rem;
      border-bottom: 2px solid #e8f0e5;
    }

    .section-header i {
      color: #FFD700;
    }

    /* Responsive */
    @media (max-width: 768px) {
      .profile-header {
        padding: 5rem 0 3rem;
      }

      .profile-card {
        margin-top: -2rem;
      }

      .profile-icon {
        font-size: 4rem;
      }

      .profile-info h5 {
        font-size: 1.5rem;
      }
    }
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
          <div class="profile-icon-wrapper">
            <i class="fas fa-user-circle profile-icon"></i>
          </div>
        </div>
        <div class="col-md-9 profile-info">
          <h5><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h5>
          <p><i class="fas fa-envelope"></i><?php echo htmlspecialchars($user['email']); ?></p>
          <p><i class="fas fa-phone"></i><?php echo htmlspecialchars($user['phone_number']); ?></p>
          <p><i class="fas fa-map-marker-alt"></i><?php echo htmlspecialchars($user['address']); ?></p>
        </div>
      </div>
      <hr>

      <!-- Farm Info -->
      <h6 class="section-header"><i class="fas fa-seedling"></i> Farm Information</h6>
      <?php if (!empty($user['farm_name'])): ?>
        <div class="farm-info-card">
          <h5>
            <i class="fas fa-leaf"></i><?php echo htmlspecialchars($user['farm_name']); ?>
            <?php if (!empty($user['is_verified_farmer']) && $user['is_verified_farmer']): ?>
              <span class="badge-verified ms-2"><i class="fas fa-check"></i> Verified Farmer</span>
            <?php endif; ?>
          </h5>
          <ul>
            <li><i class="fas fa-map-marker-alt"></i><span><?php echo htmlspecialchars($user['farm_location']); ?></span></li>
            <li><i class="fas fa-ruler-combined"></i><span><?php echo htmlspecialchars($user['farm_size']); ?> hectares</span></li>
            <li><i class="fas fa-seedling"></i><span><?php echo htmlspecialchars($user['farming_method']); ?></span></li>
            <li><i class="fas fa-calendar-alt"></i><span><?php echo htmlspecialchars($user['years_experience']); ?> years experience</span></li>
            <li><i class="fas fa-certificate"></i><span><?php echo htmlspecialchars($user['certification_details']); ?></span></li>
            <li><i class="fas fa-info-circle"></i><span><?php echo htmlspecialchars($user['bio']); ?></span></li>
          </ul>
          <small class="text-muted d-block mt-3">Created: <?php echo htmlspecialchars($user['created_at']); ?> | Updated: <?php echo htmlspecialchars($user['updated_at']); ?></small>
          <div class="d-flex gap-2 mt-3 flex-wrap">
            <a href="edit-profile.php" class="btn btn-outline-success btn-sm"><i class="fas fa-edit me-1"></i>Edit Farm Profile</a>
            <a href="store-dashboard.php" class="btn btn-success btn-sm"><i class="fas fa-chart-line me-1"></i>Go to Dashboard</a>
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
