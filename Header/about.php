<?php
session_start();
include '../db_connect.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("location: ../index.php");
    exit();
}

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header("location: ../Register/login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>About | FarmCart</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <!-- Bootstrap & Fonts -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <!-- FarmCart Styles -->
  <link rel="stylesheet" href="../Assets/css/navbar.css">
  <link rel="stylesheet" href="../Assets/css/customer.css">
  <link rel="stylesheet" href="../Assets/css/about.css">
</head>
<body>

  <!-- Navbar -->
  <?php include '../Includes/navbar.php'; ?>

  <!-- Hero Section -->
  <section class="about-section py-5" style="background-color: var(--light-bg);">
    <div class="container">
      <div class="text-center mb-5">
        <span class="about-badge text-uppercase fw-semibold px-3 py-1 rounded-pill" style="background-color: var(--accent-gold); color: var(--dark-green); font-size: 0.85rem;">Who We Are</span>
        <h2 class="about-title mt-3 fw-bold" style="color: var(--primary-green); font-size: 2.5rem;">About <span class="text-accent" style="color: var(--accent-gold);">FarmCart</span></h2>
        <p class="about-subtitle mt-2 text-muted" style="font-size: 1.1rem;">Connecting farmers and conscious consumers through sustainability, fairness, and community.</p>
      </div>

      <div class="row g-5 align-items-center">
        <!-- Left Column -->
        <div class="col-md-6">
          <div class="about-text">
            <p class="lead text-dark" style="color: var(--text-dark);">
              FarmCart is a community-driven platform that connects local farmers with conscious consumers. We believe in ethical sourcing, sustainable agriculture, and empowering small-scale producers.
            </p>
            <p style="color: var(--text-light);">
              Founded in <strong>Baguio City</strong>, FarmCart was born out of a desire to make fresh, organic, and humanely raised produce accessible to everyone. We work directly with family farms to ensure transparency, quality, and fair pricing.
            </p>
            <p style="color: var(--text-light);">
              Our mission is to build a healthier food system — one that values people, animals, and the planet. Whether you're shopping for vegetables, livestock, or artisanal goods, you're supporting a movement that puts community first.
            </p>
          </div>
        </div>

        <!-- Right Column -->
        <div class="col-md-6">
          <div class="card shadow-lg border-0" style="border-radius: 12px;">
            <div class="card-body p-4">
              <h5 class="mb-4 fw-bold" style="color: var(--dark-green);"><i class="fas fa-heart text-accent me-2" style="color: var(--accent-gold);"></i> Our Values</h5>
              <ul class="list-unstyled values-list">
                <li class="mb-2"><i class="fas fa-leaf text-success me-2"></i> Sustainability & Ethical Farming</li>
                <li class="mb-2"><i class="fas fa-users text-success me-2"></i> Local Empowerment</li>
                <li class="mb-2"><i class="fas fa-handshake text-success me-2"></i> Transparency & Fair Trade</li>
                <li class="mb-2"><i class="fas fa-apple-alt text-success me-2"></i> Health & Wellness</li>
                <li class="mb-2"><i class="fas fa-seedling text-success me-2"></i> Community Collaboration</li>
              </ul>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Footer -->
  <?php include '../Includes/footer.php'; ?>

  <!-- Bootstrap JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>