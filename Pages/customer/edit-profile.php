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

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userData = [
        'email'        => $_POST['email'],
        'first_name'   => $_POST['first_name'],
        'last_name'    => $_POST['last_name'],
        'phone_number' => $_POST['phone_number'],
        'address'      => $_POST['address']
    ];

    $farmData = [
        'farm_name'            => $_POST['farm_name'],
        'farm_location'        => $_POST['farm_location'],
        'farm_size'            => $_POST['farm_size'],
        'farming_method'       => $_POST['farming_method'],
        'years_experience'     => $_POST['years_experience'],
        'certification_details'=> $_POST['certification_details'],
        'bio'                  => $_POST['bio']
    ];

    $userUpdated = $userObj->updateUser($userId, $userData);
    $farmUpdated = $userObj->updateFarmProfile($userId, $farmData);

    if ($userUpdated || $farmUpdated) {
        $message = "✅ Profile updated successfully!";
        $user    = $userObj->getById($userId);
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
  <link rel="stylesheet" href="../../Assets/css/navbar.css">
  <link rel="stylesheet" href="../../Assets/css/customer.css">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <style>
 .profile-header-modern {
  background: linear-gradient(135deg, var(--primary-green), var(--dark-green));
  padding: 5rem 0;
  border-bottom: 4px solid var(--accent-gold);
  position: relative;
  z-index: 1;
}

.profile-icon-wrapper {
  display: flex;
  justify-content: center;
  align-items: center;
}

.profile-icon-circle {
  width: 80px;
  height: 80px;
  background-color: rgba(255, 255, 255, 0.1);
  border-radius: 50%;
  display: flex;
  justify-content: center;
  align-items: center;
  backdrop-filter: blur(6px);
  box-shadow: 0 0 10px rgba(0,0,0,0.2);
}

.accent-line {
  width: 100px;
  height: 4px;
  background: var(--accent-gold);
  border-radius: 2px;
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
    <div class="card shadow p-4">
      <?php if ($message): ?>
        <div class="alert alert-info text-center"><?php echo htmlspecialchars($message); ?></div>
      <?php endif; ?>

      <form method="post">
        <!-- User Info -->
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

<div class="col-12">
  <label class="form-label">Address</label>
  <input type="text" name="address" class="form-control" 
         value="<?php echo htmlspecialchars($user['address'] ?? ''); ?>" required>
  <div class="form-text">Your current residential or business address.</div>
</div>

<hr>

<!-- Farm Info -->
<h6 class="fw-bold text-dark-green"><i class="fas fa-seedling me-2 text-accent"></i> Farm Information</h6>
<p class="text-muted mb-4">This section helps customers understand your farming practices.</p>

<div class="col-md-6">
  <label class="form-label">Farm Name</label>
  <input type="text" name="farm_name" class="form-control" 
         value="<?php echo htmlspecialchars($user['farm_name'] ?? ''); ?>" required>
  <div class="form-text">Your farm’s registered or known name.</div>
</div>

<div class="col-md-6">
  <label class="form-label">Farm Location</label>
  <input type="text" name="farm_location" class="form-control" 
         value="<?php echo htmlspecialchars($user['farm_location'] ?? ''); ?>" required>
  <div class="form-text">City, province, or barangay where your farm is located.</div>
</div>

<div class="col-md-6">
  <label class="form-label">Farm Size (hectares)</label>
  <input type="number" name="farm_size" class="form-control" 
         value="<?php echo htmlspecialchars($user['farm_size'] ?? ''); ?>" required>
  <div class="form-text">Total land area used for farming.</div>
</div>

<div class="col-md-6">
  <label for="farming_method" class="form-label">Farming Method</label>
  <select class="form-select" id="farming_method" name="farming_method" required>
    <option value="Organic" <?php echo ($user['farming_method'] ?? '') === 'Organic' ? 'selected' : ''; ?>>Organic</option>
    <option value="Conventional" <?php echo ($user['farming_method'] ?? '') === 'Conventional' ? 'selected' : ''; ?>>Conventional</option>
    <option value="Hydroponic" <?php echo ($user['farming_method'] ?? '') === 'Hydroponic' ? 'selected' : ''; ?>>Hydroponic</option>
    <option value="Other" <?php echo ($user['farming_method'] ?? '') === 'Other' ? 'selected' : ''; ?>>Other</option>
  </select>
  <div class="form-text">Choose your primary farming approach.</div>
</div>

<div class="col-md-6">
  <label class="form-label">Years of Experience</label>
  <input type="number" name="years_experience" class="form-control" 
         value="<?php echo htmlspecialchars($user['years_experience'] ?? ''); ?>" required>
  <div class="form-text">How long you’ve been farming (in years).</div>
</div>

<div class="col-md-6">
  <label class="form-label">Certification Details</label>
  <input type="text" name="certification_details" class="form-control" 
         value="<?php echo htmlspecialchars($user['certification_details'] ?? ''); ?>">
  <div class="form-text">List any certifications (e.g., Organic, GAP, Fair Trade).</div>
</div>

<div class="col-12">
  <label class="form-label">Bio</label>
  <textarea name="bio" class="form-control" rows="3" required><?php echo htmlspecialchars($user['bio'] ?? ''); ?></textarea>
  <div class="form-text">Tell us about your farm, values, and story.</div>
</div>
      </form>
    </div>
  </div>
</body>
</html>
