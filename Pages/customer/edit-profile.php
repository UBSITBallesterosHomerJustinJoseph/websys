<?php
session_start();
include '../../db_connect.php';
require_once 'User.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../index.php");
    exit();
}

$userObj = new User($conn);
$userId  = $_SESSION['user_id'];
$user    = $userObj->getById($userId); // must join users + farmer_profiles

// Check user role and farm profile status
$user_role = $_SESSION['user_role'] ?? 'customer';
$has_farm_profile = !empty($user['farm_name']);
// Customers without farm profile cannot edit farm info; farmers and admins can always edit
$is_customer_without_farm = ($user_role === 'customer' && !$has_farm_profile);
$can_edit_farm_info = ($user_role === 'farmer' || $user_role === 'admin' || $has_farm_profile);

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userData = [
        'email'        => $_POST['email'],
        'first_name'   => $_POST['first_name'],
        'last_name'    => $_POST['last_name'],
        'phone_number' => $_POST['phone_number'],
        'address'      => $_POST['address']
    ];

    $userUpdated = $userObj->updateUser($userId, $userData);
    
    // Only update farm data if user can edit farm info
    $farmUpdated = false;
    if ($can_edit_farm_info) {
        $farmData = [
            'farm_name'            => $_POST['farm_name'] ?? '',
            'farm_location'        => $_POST['farm_location'] ?? '',
            'farm_size'            => $_POST['farm_size'] ?? '',
            'farming_method'       => $_POST['farming_method'] ?? '',
            'years_experience'     => $_POST['years_experience'] ?? '',
            'certification_details'=> $_POST['certification_details'] ?? '',
            'bio'                  => $_POST['bio'] ?? ''
        ];
        $farmUpdated = $userObj->updateFarmProfile($userId, $farmData);
    }

    if ($userUpdated || $farmUpdated) {
        $message = "✅ Profile updated successfully!";
        $user    = $userObj->getById($userId);
        // Refresh farm profile status
        $has_farm_profile = !empty($user['farm_name']);
        $is_customer_without_farm = ($user_role === 'customer' && !$has_farm_profile);
        $can_edit_farm_info = ($user_role === 'farmer' || $user_role === 'admin' || $has_farm_profile);
    } else {
        $message = "❌ Error updating profile.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Edit Profile | FarmCart</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <!-- Bootstrap & Fonts -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
  <!-- FarmCart Styles -->
  <link rel="stylesheet" href="../../Assets/css/variables.css">
  <link rel="stylesheet" href="../../Assets/css/navbar.css">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
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
    .profile-header-modern {
      background: linear-gradient(135deg, #0F2E15 0%, #1a4a2a 50%, #2d5a3a 100%);
      padding: 6rem 0 4rem;
      border-bottom: 4px solid #FFD700;
      position: relative;
      z-index: 1;
      margin-top: -4rem;
      overflow: hidden;
    }

    .profile-header-modern::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: url('data:image/svg+xml,<svg width="100" height="100" xmlns="http://www.w3.org/2000/svg"><circle cx="50" cy="50" r="2" fill="rgba(255,255,255,0.05)"/></svg>');
      opacity: 0.3;
    }

    .profile-header-modern .container {
      position: relative;
      z-index: 1;
    }

    .profile-icon-wrapper {
      display: flex;
      justify-content: center;
      align-items: center;
      margin-bottom: 1rem;
    }

    .profile-icon-circle {
      width: 100px;
      height: 100px;
      background: linear-gradient(135deg, rgba(255,255,255,0.15), rgba(255,255,255,0.05));
      border-radius: 50%;
      display: flex;
      justify-content: center;
      align-items: center;
      backdrop-filter: blur(10px);
      box-shadow: 0 8px 24px rgba(0,0,0,0.2);
      border: 2px solid rgba(255,255,255,0.2);
      transition: transform 0.3s ease;
    }

    .profile-icon-circle:hover {
      transform: scale(1.05);
    }

    .profile-icon-circle i {
      font-size: 2.5rem;
      color: white;
    }

    .profile-header-modern h1 {
      font-weight: 800;
      font-size: clamp(2rem, 5vw, 3rem);
      color: white;
      text-shadow: 2px 2px 8px rgba(0,0,0,0.2);
      margin-bottom: 0.5rem;
    }

    .profile-header-modern p {
      color: rgba(255,255,255,0.9);
      font-size: clamp(1rem, 2vw, 1.2rem);
    }

    .accent-line {
      width: 120px;
      height: 5px;
      background: linear-gradient(90deg, transparent, #FFD700, transparent);
      margin: 1.5rem auto 0;
      border-radius: 3px;
      box-shadow: 0 2px 8px rgba(255,215,0,0.3);
    }

    /* Form Card */
    .profile-form-card {
      border-radius: 20px;
      border: none;
      box-shadow: var(--shadow-lg);
      background: var(--white);
      margin-top: -3rem;
      position: relative;
      z-index: 2;
      overflow: hidden;
    }

    .profile-form-card::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      height: 5px;
      background: linear-gradient(90deg, #0F2E15, #FFD700, #0F2E15);
    }

    /* Form Styling */
    .form-section {
      padding: 2rem;
    }

    .form-section-title {
      font-size: 1.5rem;
      font-weight: 700;
      color: #0F2E15;
      margin-bottom: 1.5rem;
      padding-bottom: 0.75rem;
      border-bottom: 3px solid #e8f0e5;
      display: flex;
      align-items: center;
      gap: 0.75rem;
    }

    .form-section-title i {
      color: #FFD700;
      font-size: 1.75rem;
    }

    .form-label {
      font-weight: 600;
      color: #0F2E15;
      margin-bottom: 0.5rem;
      font-size: 0.95rem;
      display: flex;
      align-items: center;
      gap: 0.5rem;
    }

    .form-label::before {
      content: '';
      width: 4px;
      height: 16px;
      background: linear-gradient(135deg, #0F2E15, #FFD700);
      border-radius: 2px;
    }

    .form-control,
    .form-select {
      border-radius: 10px;
      padding: 0.875rem 1rem;
      border: 2px solid var(--border-color);
      transition: all 0.3s ease;
      font-size: 0.95rem;
      background: #ffffff;
    }

    .form-control:focus,
    .form-select:focus {
      border-color: #0F2E15;
      box-shadow: 0 0 0 0.2rem rgba(15,46,21,0.15);
      background: #ffffff;
      outline: none;
    }

    .form-control:hover,
    .form-select:hover {
      border-color: #DAE2CB;
    }

    textarea.form-control {
      resize: vertical;
      min-height: 100px;
    }

    .form-text {
      color: var(--text-muted);
      font-size: 0.85rem;
      margin-top: 0.25rem;
      display: flex;
      align-items: center;
      gap: 0.25rem;
    }

    .form-text::before {
      content: 'ℹ️';
      font-size: 0.75rem;
    }

    /* Alert Styling */
    .alert {
      border-radius: 12px;
      font-weight: 500;
      padding: 1rem 1.5rem;
      border: none;
      box-shadow: var(--shadow-sm);
    }

    .alert-info {
      background: linear-gradient(135deg, #e7f3ff, #d0e7ff);
      color: #0c5460;
      border-left: 4px solid #17a2b8;
    }

    .alert-warning {
      background: linear-gradient(135deg, #fff3cd, #ffe69c);
      color: #856404;
      border-left: 4px solid #ffc107;
      border-radius: 12px;
      padding: 1.25rem 1.5rem;
      font-weight: 500;
    }

    .alert-warning i {
      font-size: 1.25rem;
      margin-right: 0.5rem;
    }

    .farm-section-disabled {
      opacity: 0.6;
      pointer-events: none;
      position: relative;
    }

    .farm-section-disabled::after {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: rgba(255, 255, 255, 0.7);
      border-radius: 12px;
      z-index: 1;
    }

    .btn-setup-store {
      background: linear-gradient(135deg, #FFD700, #FFA500);
      color: #0F2E15;
      border: none;
      border-radius: 12px;
      padding: 0.875rem 2rem;
      font-weight: 700;
      font-size: 1rem;
      transition: all 0.3s ease;
      box-shadow: 0 4px 12px rgba(255,215,0,0.3);
      display: inline-flex;
      align-items: center;
      gap: 0.5rem;
      text-decoration: none;
    }

    .btn-setup-store:hover {
      background: linear-gradient(135deg, #FFC107, #FF8C00);
      transform: translateY(-2px);
      box-shadow: 0 6px 16px rgba(255,215,0,0.4);
      color: #0F2E15;
    }

    /* Buttons */
    .btn-submit {
      background: linear-gradient(135deg, #0F2E15, #1a4a2a);
      color: white;
      border: none;
      border-radius: 12px;
      padding: 0.875rem 2.5rem;
      font-weight: 700;
      font-size: 1rem;
      transition: all 0.3s ease;
      box-shadow: 0 4px 12px rgba(15,46,21,0.3);
      display: inline-flex;
      align-items: center;
      gap: 0.5rem;
    }

    .btn-submit:hover {
      background: linear-gradient(135deg, #1a4a2a, #2d5a3a);
      transform: translateY(-2px);
      box-shadow: 0 6px 16px rgba(15,46,21,0.4);
      color: white;
    }

    .btn-cancel {
      background: #f8f9fa;
      color: #6c757d;
      border: 2px solid #e9ecef;
      border-radius: 12px;
      padding: 0.875rem 2.5rem;
      font-weight: 600;
      font-size: 1rem;
      transition: all 0.3s ease;
    }

    .btn-cancel:hover {
      background: #e9ecef;
      border-color: #dee2e6;
      color: #495057;
      transform: translateY(-2px);
    }

    /* Form Row Spacing */
    .row {
      margin-bottom: 1.25rem;
    }

    .row:last-child {
      margin-bottom: 0;
    }

    hr {
      border: none;
      height: 2px;
      background: linear-gradient(90deg, transparent, #e8f0e5, transparent);
      margin: 2rem 0;
    }

    /* Responsive */
    @media (max-width: 768px) {
      .profile-header-modern {
        padding: 5rem 0 3rem;
      }

      .profile-form-card {
        margin-top: -2rem;
      }

      .form-section {
        padding: 1.5rem;
      }

      .profile-icon-circle {
        width: 80px;
        height: 80px;
      }

      .profile-icon-circle i {
        font-size: 2rem;
      }
    }
  </style>
</head>
<body>

  <?php include '../../Includes/navbar.php'; ?>

  <!-- Header -->
<section class="profile-header-modern">
  <div class="container text-center">
    <div class="profile-icon-wrapper mb-3">
      <div class="profile-icon-circle">
        <i class="fas fa-user fa-2x text-white"></i>
      </div>
    </div>
    <h1 class="fw-bold text-white">My Profile</h1>
    <p class="text-white-50">Manage your account details!</p>
    <div class="accent-line mt-3 mx-auto"></div>
  </div>
</section>

  <!-- Profile Form -->
  <div class="container py-5">
    <div class="card profile-form-card">
      <div class="form-section">
        <?php if ($message): ?>
          <div class="alert alert-info text-center mb-4"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <?php if ($is_customer_without_farm): ?>
          <div class="alert alert-warning mb-4">
            <i class="fas fa-exclamation-triangle"></i>
            <strong>Farm Profile Setup Required</strong><br>
            <small>You need to set up a store account first before you can edit farm information. 
            <a href="setUpStore.php" class="fw-bold text-decoration-none" style="color: #856404;">Click here to set up your store</a>.</small>
          </div>
        <?php endif; ?>

        <form method="post">
          <!-- User Info Section -->
          <div class="form-section-title">
            <i class="fas fa-user"></i>
            <span>Personal Information</span>
          </div>

          <div class="row">
            <div class="col-md-6">
              <label class="form-label">Email</label>
              <input type="email" name="email" class="form-control" 
                     value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" required>
              <div class="form-text">Enter a valid email address for login and notifications.</div>
            </div>

            <div class="col-md-6">
              <label class="form-label">Phone Number</label>
              <input type="text" name="phone_number" class="form-control" 
                     value="<?php echo htmlspecialchars($user['phone_number'] ?? ''); ?>">
              <div class="form-text">Provide your mobile or landline number (optional).</div>
            </div>
          </div>

          <div class="row">
            <div class="col-md-6">
              <label class="form-label">First Name</label>
              <input type="text" name="first_name" class="form-control" 
                     value="<?php echo htmlspecialchars($user['first_name'] ?? ''); ?>" required>
              <div class="form-text">Your given name.</div>
            </div>

            <div class="col-md-6">
              <label class="form-label">Last Name</label>
              <input type="text" name="last_name" class="form-control" 
                     value="<?php echo htmlspecialchars($user['last_name'] ?? ''); ?>" required>
              <div class="form-text">Your family name.</div>
            </div>
          </div>

          <div class="row">
            <div class="col-12">
              <label class="form-label">Address</label>
              <input type="text" name="address" class="form-control" 
                     value="<?php echo htmlspecialchars($user['address'] ?? ''); ?>" required>
              <div class="form-text">Your current residential or business address.</div>
            </div>
          </div>

          <hr>

          <!-- Farm Info Section -->
          <div class="form-section-title">
            <i class="fas fa-seedling"></i>
            <span>Farm Information</span>
          </div>
          
          <?php if ($is_customer_without_farm): ?>
            <div class="alert alert-warning mb-4">
              <i class="fas fa-info-circle"></i>
              <strong>Store Account Required</strong><br>
              <small>To edit farm information, you must first set up a store account. 
              <a href="setUpStore.php" class="btn-setup-store mt-3">
                <i class="fas fa-store me-2"></i>Set Up Store Account
              </a></small>
            </div>
            <div class="farm-section-disabled">
          <?php endif; ?>

          <p class="text-muted mb-4">This section helps customers understand your farming practices.</p>

          <div class="row">
            <div class="col-md-6">
              <label class="form-label">Farm Name</label>
              <input type="text" name="farm_name" class="form-control" 
                     value="<?php echo htmlspecialchars($user['farm_name'] ?? ''); ?>" 
                     <?php echo $can_edit_farm_info ? 'required' : 'disabled'; ?>>
              <div class="form-text">Your farm's registered or known name.</div>
            </div>

            <div class="col-md-6">
              <label class="form-label">Farm Location</label>
              <input type="text" name="farm_location" class="form-control" 
                     value="<?php echo htmlspecialchars($user['farm_location'] ?? ''); ?>" 
                     <?php echo $can_edit_farm_info ? 'required' : 'disabled'; ?>>
              <div class="form-text">City, province, or barangay where your farm is located.</div>
            </div>
          </div>

          <div class="row">
            <div class="col-md-6">
              <label class="form-label">Farm Size (hectares)</label>
              <input type="number" name="farm_size" class="form-control" 
                     value="<?php echo htmlspecialchars($user['farm_size'] ?? ''); ?>" 
                     <?php echo $can_edit_farm_info ? 'required' : 'disabled'; ?>>
              <div class="form-text">Total land area used for farming.</div>
            </div>

            <div class="col-md-6">
              <label for="farming_method" class="form-label">Farming Method</label>
              <select class="form-select" id="farming_method" name="farming_method" 
                      <?php echo $can_edit_farm_info ? 'required' : 'disabled'; ?>>
                <option value="">Select farming method</option>
                <option value="Organic" <?php echo ($user['farming_method'] ?? '') === 'Organic' ? 'selected' : ''; ?>>Organic</option>
                <option value="Conventional" <?php echo ($user['farming_method'] ?? '') === 'Conventional' ? 'selected' : ''; ?>>Conventional</option>
                <option value="Hydroponic" <?php echo ($user['farming_method'] ?? '') === 'Hydroponic' ? 'selected' : ''; ?>>Hydroponic</option>
                <option value="Other" <?php echo ($user['farming_method'] ?? '') === 'Other' ? 'selected' : ''; ?>>Other</option>
              </select>
              <div class="form-text">Choose your primary farming approach.</div>
            </div>
          </div>

          <div class="row">
            <div class="col-md-6">
              <label class="form-label">Years of Experience</label>
              <input type="number" name="years_experience" class="form-control" 
                     value="<?php echo htmlspecialchars($user['years_experience'] ?? ''); ?>" 
                     <?php echo $can_edit_farm_info ? 'required' : 'disabled'; ?>>
              <div class="form-text">How long you've been farming (in years).</div>
            </div>

            <div class="col-md-6">
              <label class="form-label">Certification Details</label>
              <input type="text" name="certification_details" class="form-control" 
                     value="<?php echo htmlspecialchars($user['certification_details'] ?? ''); ?>"
                     <?php echo $can_edit_farm_info ? '' : 'disabled'; ?>>
              <div class="form-text">List any certifications (e.g., Organic, GAP, Fair Trade).</div>
            </div>
          </div>

          <div class="row">
            <div class="col-12">
              <label class="form-label">Bio</label>
              <textarea name="bio" class="form-control" rows="4" 
                        <?php echo $can_edit_farm_info ? 'required' : 'disabled'; ?>><?php echo htmlspecialchars($user['bio'] ?? ''); ?></textarea>
              <div class="form-text">Tell us about your farm, values, and story.</div>
            </div>
          </div>

          <?php if ($is_customer_without_farm): ?>
            </div>
          <?php endif; ?>

          <!-- Submit Buttons -->
          <div class="row mt-4">
            <div class="col-12 d-flex gap-3 justify-content-end flex-wrap">
              <a href="profile.php" class="btn btn-cancel">
                <i class="fas fa-times me-2"></i>Cancel
              </a>
              <button type="submit" class="btn btn-submit">
                <i class="fas fa-save me-2"></i>Save Changes
              </button>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>
</body>
</html>
