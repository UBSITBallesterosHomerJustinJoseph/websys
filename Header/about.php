<?php
session_start();
include '../db_connect.php';

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
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <!-- Remix Icon -->
  <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
  <!-- FarmCart Styles -->
  <link rel="stylesheet" href="../Assets/css/navbar.css">
  <link rel="stylesheet" href="../Assets/css/customer.css">
  <style>
    :root {
        --primary-green: #0F2E15;
        --secondary-green: #1a4727;
        --light-mint: #DAE2CB;
        --accent-gold: #FFD700;
        --dark-green: #0a2410;
        --text-dark: #2c3e29;
        --text-light: #5a6d58;
        --white: #ffffff;
        --light-bg: #f9fbf7;
        --border-light: #e0e6d8;
        --shadow-md: 0 4px 16px rgba(0, 0, 0, 0.12);
        --shadow-lg: 0 8px 30px rgba(0, 0, 0, 0.15);
        --border-radius: 12px;
    }
    
    body { 
        background-color: var(--light-bg); 
        font-family: 'Inter', sans-serif;
        padding-top: 4rem; /* Compensate for fixed navbar */
    }
    
    /* Hero Section */
    .about-hero {
        background: linear-gradient(rgba(15, 46, 21, 0.95), rgba(15, 46, 21, 0.95)), 
                    url('https://images.unsplash.com/photo-1500382017468-9049fed747ef?ixlib=rb-1.2.1&auto=format&fit=crop&w=1920&q=80');
        background-size: cover;
        background-position: center;
        padding: 6rem 0 4rem;
        color: white;
        text-align: center;
        margin-top: -4rem; /* Pull up to hide navbar gap */
    }
    
    .about-badge {
        display: inline-block;
        background: var(--accent-gold);
        color: var(--dark-green);
        padding: 8px 20px;
        border-radius: 50px;
        font-weight: 600;
        font-size: 0.9rem;
        text-transform: uppercase;
        letter-spacing: 1px;
        margin-bottom: 1.5rem;
    }
    
    .about-title {
        font-size: 3rem;
        font-weight: 800;
        color: var(--white);
        margin-bottom: 1rem;
        line-height: 1.2;
    }
    
    .about-title .text-accent {
        color: var(--accent-gold);
    }
    
    .about-subtitle {
        font-size: 1.2rem;
        color: var(--light-mint);
        max-width: 700px;
        margin: 0 auto 2rem;
        line-height: 1.6;
    }
    
    /* Mission Section */
    .mission-section {
        padding: 5rem 0;
        background: var(--light-bg);
    }
    
    .mission-card {
        background: var(--white);
        border-radius: var(--border-radius);
        padding: 2.5rem;
        height: 100%;
        box-shadow: var(--shadow-md);
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        border: 1px solid var(--border-light);
    }
    
    .mission-card:hover {
        transform: translateY(-5px);
        box-shadow: var(--shadow-lg);
    }
    
    .mission-icon {
        width: 80px;
        height: 80px;
        background: linear-gradient(135deg, var(--light-mint), #c0d0af);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 1.5rem;
        color: var(--primary-green);
        font-size: 2rem;
    }
    
    .mission-card h3 {
        color: var(--dark-green);
        font-size: 1.5rem;
        font-weight: 700;
        margin-bottom: 1rem;
        text-align: center;
    }
    
    .mission-card p {
        color: var(--text-light);
        line-height: 1.6;
        text-align: center;
    }
    
    /* Values Section */
    .values-section {
        padding: 5rem 0;
        background: var(--white);
    }
    
    .values-container {
        background: linear-gradient(135deg, var(--light-bg), #f0f5ea);
        border-radius: var(--border-radius);
        padding: 3rem;
        box-shadow: var(--shadow-md);
        border: 1px solid var(--border-light);
    }
    
    .values-list {
        list-style: none;
        padding: 0;
    }
    
    .values-list li {
        font-size: 1.1rem;
        color: var(--text-dark);
        margin-bottom: 1rem;
        display: flex;
        align-items: center;
        padding: 0.75rem 1rem;
        background: var(--white);
        border-radius: 8px;
        transition: all 0.3s ease;
        border: 1px solid var(--border-light);
    }
    
    .values-list li:hover {
        background: var(--light-mint);
        transform: translateX(10px);
        border-color: var(--primary-green);
    }
    
    .values-list li i {
        color: var(--accent-gold);
        margin-right: 1rem;
        font-size: 1.2rem;
        width: 24px;
        text-align: center;
    }
    
    .values-list li strong {
        color: var(--dark-green);
        margin-right: 0.5rem;
    }
    
    /* Stats Section */
    .stats-section {
        padding: 4rem 0;
        background: linear-gradient(135deg, var(--primary-green), var(--secondary-green));
        color: white;
    }
    
    .stat-item {
        text-align: center;
    }
    
    .stat-number {
        font-size: 3rem;
        font-weight: 800;
        color: var(--accent-gold);
        margin-bottom: 0.5rem;
        line-height: 1;
    }
    
    .stat-label {
        font-size: 1.1rem;
        color: var(--light-mint);
        text-transform: uppercase;
        letter-spacing: 1px;
    }
    
    /* Team Section */
    .team-section {
        padding: 5rem 0;
        background: var(--light-bg);
    }
    
    .team-member {
        text-align: center;
        margin-bottom: 2rem;
    }
    
    .team-avatar {
        width: 120px;
        height: 120px;
        border-radius: 50%;
        background: linear-gradient(135deg, var(--light-mint), #c0d0af);
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 1rem;
        color: var(--primary-green);
        font-size: 2.5rem;
        border: 4px solid white;
        box-shadow: var(--shadow-md);
    }
    
    .team-member h4 {
        color: var(--dark-green);
        font-weight: 600;
        margin-bottom: 0.5rem;
    }
    
    .team-member p {
        color: var(--text-light);
        font-size: 0.9rem;
    }
    
    /* CTA Section */
    .cta-section {
        padding: 5rem 0;
        background: var(--white);
        text-align: center;
    }
    
    .cta-card {
        background: linear-gradient(135deg, var(--light-mint), #e8f0e5);
        border-radius: var(--border-radius);
        padding: 4rem;
        max-width: 800px;
        margin: 0 auto;
        box-shadow: var(--shadow-lg);
        border: 1px solid var(--border-light);
    }
    
    .cta-card h2 {
        color: var(--dark-green);
        font-size: 2.5rem;
        font-weight: 700;
        margin-bottom: 1rem;
    }
    
    .cta-card p {
        color: var(--text-light);
        font-size: 1.2rem;
        margin-bottom: 2rem;
    }
    
    .cta-buttons {
        display: flex;
        gap: 1rem;
        justify-content: center;
        flex-wrap: wrap;
    }
    
    .btn-primary {
        background: linear-gradient(135deg, var(--primary-green), var(--secondary-green));
        color: white;
        border: none;
        padding: 0.75rem 2rem;
        border-radius: 50px;
        font-weight: 600;
        text-decoration: none;
        transition: all 0.3s ease;
    }
    
    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: var(--shadow-md);
        color: white;
    }
    
    .btn-secondary {
        background: transparent;
        color: var(--primary-green);
        border: 2px solid var(--primary-green);
        padding: 0.75rem 2rem;
        border-radius: 50px;
        font-weight: 600;
        text-decoration: none;
        transition: all 0.3s ease;
    }
    
    .btn-secondary:hover {
        background: var(--primary-green);
        color: white;
        transform: translateY(-2px);
    }
    
    /* Responsive Design */
    @media (max-width: 768px) {
        body {
            padding-top: 0;
        }
        
        .about-hero {
            padding: 5rem 0 3rem;
            margin-top: 0;
        }
        
        .about-title {
            font-size: 2.2rem;
        }
        
        .about-subtitle {
            font-size: 1.1rem;
        }
        
        .mission-card {
            padding: 2rem;
            margin-bottom: 1.5rem;
        }
        
        .values-container {
            padding: 2rem;
        }
        
        .cta-card {
            padding: 2rem;
        }
        
        .cta-card h2 {
            font-size: 2rem;
        }
        
        .stat-number {
            font-size: 2.5rem;
        }
    }
    
    @media (max-width: 576px) {
        .about-title {
            font-size: 1.8rem;
        }
        
        .about-badge {
            font-size: 0.8rem;
            padding: 6px 16px;
        }
        
        .cta-buttons {
            flex-direction: column;
        }
        
        .btn-primary,
        .btn-secondary {
            width: 100%;
            text-align: center;
        }
    }
    
    /* Animation */
    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(30px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    .mission-card,
    .values-list li,
    .team-member,
    .stat-item {
        animation: fadeInUp 0.6s ease forwards;
        opacity: 0;
    }
  </style>
</head>
<body>

  <!-- Navbar -->
  <?php include '../Includes/navbar.php'; ?>

  <!-- Hero Section -->
  <section class="about-hero">
    <div class="container">
      <span class="about-badge">About FarmCart</span>
      <h1 class="about-title">Building a <span class="text-accent">Sustainable</span> Future Together</h1>
      <p class="about-subtitle">Connecting farmers and conscious consumers through sustainability, fairness, and community.</p>
    </div>
  </section>

  <!-- Mission Section -->
  <section class="mission-section">
    <div class="container">
      <div class="text-center mb-5">
        <h2 class="fw-bold" style="color: var(--primary-green); font-size: 2.2rem;">Our Mission & Vision</h2>
        <p class="text-muted" style="max-width: 700px; margin: 0 auto;">We're redefining how communities access fresh, sustainable food</p>
      </div>

      <div class="row g-4">
        <div class="col-md-4">
          <div class="mission-card">
            <div class="mission-icon">
              <i class="fas fa-leaf"></i>
            </div>
            <h3>Sustainable Farming</h3>
            <p>Promoting environmentally friendly agricultural practices that protect our planet while producing high-quality, nutritious food.</p>
          </div>
        </div>
        
        <div class="col-md-4">
          <div class="mission-card">
            <div class="mission-icon">
              <i class="fas fa-hands-helping"></i>
            </div>
            <h3>Community Focus</h3>
            <p>Building strong relationships between local farmers and consumers, creating economic opportunities and food security.</p>
          </div>
        </div>
        
        <div class="col-md-4">
          <div class="mission-card">
            <div class="mission-icon">
              <i class="fas fa-balance-scale"></i>
            </div>
            <h3>Fair Trade</h3>
            <p>Ensuring farmers receive fair compensation for their hard work while keeping prices affordable for consumers.</p>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Values Section -->
  <section class="values-section">
    <div class="container">
      <div class="row align-items-center">
        <div class="col-lg-6 mb-5 mb-lg-0">
          <h2 class="fw-bold mb-4" style="color: var(--primary-green); font-size: 2.2rem;">Our Core Values</h2>
          <p class="text-muted mb-4">
            FarmCart was founded in <strong>Baguio City</strong> with a simple vision: to make fresh, organic, and humanely raised produce accessible to everyone. We work directly with family farms to ensure transparency, quality, and fair pricing.
          </p>
          <p class="text-muted">
            Our mission is to build a healthier food system — one that values people, animals, and the planet. Whether you're shopping for vegetables, livestock, or artisanal goods, you're supporting a movement that puts community first.
          </p>
        </div>
        
        <div class="col-lg-6">
          <div class="values-container">
            <ul class="values-list">
              <li><i class="fas fa-seedling"></i> <strong>Sustainability:</strong> We promote regenerative farming practices</li>
              <li><i class="fas fa-handshake"></i> <strong>Transparency:</strong> Full traceability from farm to table</li>
              <li><i class="fas fa-heart"></i> <strong>Quality:</strong> Only the freshest, highest-quality products</li>
              <li><i class="fas fa-users"></i> <strong>Community:</strong> Supporting local economies and connections</li>
              <li><i class="fas fa-balance-scale-right"></i> <strong>Fairness:</strong> Fair prices for farmers and consumers</li>
            </ul>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- CTA Section -->
  <section class="cta-section">
    <div class="container">
      <div class="cta-card">
        <h2>Join Our Community</h2>
        <p>Whether you're a farmer looking to sell your products or a consumer wanting fresh, local food - FarmCart is here for you.</p>
        <div class="cta-buttons">
          <a href="../Pages/customer/products.php" class="btn-primary">
            <i class="fas fa-shopping-basket me-2"></i>Shop Now
          </a>
          <a href="../Pages/customer/setUpStore.php" class="btn-secondary">
            <i class="fas fa-store me-2"></i>Sell with Us
          </a>
        </div>
      </div>
    </div>
  </section>

  <!-- Footer -->
  <?php include '../Includes/footer.php'; ?>

  <!-- Bootstrap JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  
  <script>
    // Animation on scroll
    document.addEventListener('DOMContentLoaded', function() {
      const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
      };

      const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
          if (entry.isIntersecting) {
            entry.target.style.opacity = '1';
            entry.target.style.transform = 'translateY(0)';
          }
        });
      }, observerOptions);

      // Observe all animated elements
      document.querySelectorAll('.mission-card, .values-list li, .team-member, .stat-item').forEach(el => {
        observer.observe(el);
      });

      // Stats counter animation
      const statNumbers = document.querySelectorAll('.stat-number');
      statNumbers.forEach(stat => {
        const finalValue = parseInt(stat.textContent);
        let currentValue = 0;
        const increment = finalValue / 50;
        const timer = setInterval(() => {
          currentValue += increment;
          if (currentValue >= finalValue) {
            stat.textContent = finalValue + (stat.textContent.includes('%') ? '%' : '+');
            clearInterval(timer);
          } else {
            stat.textContent = Math.floor(currentValue) + (stat.textContent.includes('%') ? '%' : '+');
          }
        }, 30);
      });
    });
  </script>
</body>
</html>