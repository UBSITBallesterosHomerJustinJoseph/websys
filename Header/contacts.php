<?php
session_start();
include '../db_connect.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/vendor/autoload.php';

// Logout
if (isset($_GET['logout'])) {
    session_destroy();
    header("location: ../Register/login.php");
    exit();
}

// Handle contact form
$success = $error = '';
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['send'])) {
    $name = htmlspecialchars($_POST['name']);
    $email = htmlspecialchars($_POST['email']);
    $message = htmlspecialchars($_POST['message']);

    try {
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'zendrex211@gmail.com'; // Gmail
        $mail->Password   = 'rspb rzey dheq anxu';  // App password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        $mail->setFrom('youraddress@gmail.com', 'FarmCart Contact');
        $mail->addAddress('20180054@s.ubaguio.edu');

        $mail->isHTML(true);
        $mail->Subject = "New Contact Form Message from $name";
        $mail->Body    = "
            <h3>New Contact Message</h3>
            <p><strong>Name:</strong> $name</p>
            <p><strong>Email:</strong> $email</p>
            <p><strong>Message:</strong><br>$message</p>
        ";
        $mail->AltBody = "Name: $name\nEmail: $email\n\nMessage:\n$message";

        $mail->send();
        $success = "✅ Your message has been sent successfully!";
    } catch (Exception $e) {
        $error = "❌ Message could not be sent. Error: {$mail->ErrorInfo}";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Contact Us | FarmCart</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <!-- Bootstrap -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <!-- Fonts -->
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
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
    }
    
    body { 
        background-color: var(--light-bg); 
        font-family: 'Inter', sans-serif;
        padding-top: 4rem; /* Compensate for fixed navbar */
    }
    
    .contact-section {
        min-height: calc(100vh - 4rem);
        display: flex;
        align-items: center;
        padding: 4rem 0;
    }
    
    .contact-hero {
        background: linear-gradient(rgba(15, 46, 21, 0.95), rgba(15, 46, 21, 0.95)), 
                    url('https://images.unsplash.com/photo-1518837695005-2083093ee35b?ixlib=rb-1.2.1&auto=format&fit=crop&w=1920&q=80');
        background-size: cover;
        background-position: center;
        padding: 6rem 0 4rem;
        color: white;
        text-align: center;
        margin-top: -4rem;
    }
    
    .contact-badge {
        display: inline-block;
        background: var(--accent-gold); 
        color: var(--dark-green);
        font-weight: 600; 
        border-radius: 50px; 
        padding: 8px 20px;
        text-transform: uppercase;
        letter-spacing: 1px;
        font-size: 0.9rem;
        margin-bottom: 1.5rem;
    }
    
    .contact-title { 
        color: var(--white); 
        font-weight: 800; 
        font-size: 3rem;
        margin-bottom: 1rem;
        line-height: 1.2;
    }
    
    .contact-title .text-accent { 
        color: var(--accent-gold); 
    }
    
    .contact-subtitle { 
        color: var(--light-mint); 
        font-size: 1.2rem;
        max-width: 700px;
        margin: 0 auto;
        line-height: 1.6;
    }
    
    .card { 
        border-radius: 12px; 
        border: 1px solid var(--border-light); 
        box-shadow: var(--shadow-md);
        transition: transform 0.3s ease;
    }
    
    .card:hover {
        transform: translateY(-5px);
    }
    
    .form-label { 
        font-weight: 600; 
        color: var(--dark-green); 
        margin-bottom: 0.5rem;
    }
    
    .form-control {
        border-radius: 8px; 
        border: 2px solid var(--border-light);
        transition: all 0.3s ease; 
        padding: 0.75rem;
        background-color: var(--white);
    }
    
    .form-control:focus {
        border-color: var(--accent-gold);
        box-shadow: 0 0 0 0.2rem rgba(212,175,55,0.25);
        background-color: var(--white);
    }
    
    .btn-success {
        background: linear-gradient(135deg, var(--accent-gold), #E8C547);
        color: var(--dark-green); 
        font-weight: 600;
        border-radius: 8px; 
        border: none; 
        transition: all 0.3s ease;
        padding: 0.75rem 2rem;
        font-size: 1.1rem;
    }
    
    .btn-success:hover {
        background: linear-gradient(135deg, #c19b2a, #d4b342);
        transform: translateY(-2px);
        box-shadow: 0 6px 15px rgba(212,175,55,0.3);
        color: var(--dark-green);
    }
    
    .alert { 
        border-radius: 8px; 
        font-weight: 500; 
        text-align: center; 
        border: none;
        margin-bottom: 1.5rem;
    }
    
    .contact-icon {
        font-size: 1.5rem;
        color: var(--accent-gold);
        margin-right: 0.75rem;
        width: 30px;
        text-align: center;
    }
    
    .contact-info-item {
        display: flex;
        align-items: flex-start;
        margin-bottom: 1.5rem;
        padding: 1rem;
        background: rgba(218, 226, 203, 0.1);
        border-radius: 8px;
        transition: all 0.3s ease;
    }
    
    .contact-info-item:hover {
        background: rgba(218, 226, 203, 0.2);
        transform: translateX(5px);
    }
    
    .contact-info-item p {
        margin: 0;
        color: var(--text-dark);
        line-height: 1.5;
    }
    
    .contact-info-item strong {
        display: block;
        color: var(--dark-green);
        margin-bottom: 0.25rem;
        font-size: 1.1rem;
    }
    
    /* Responsive adjustments */
    @media (max-width: 768px) {
        body {
            padding-top: 0;
        }
        
        .contact-hero {
            padding: 5rem 0 3rem;
            margin-top: 0;
        }
        
        .contact-title {
            font-size: 2.2rem;
        }
        
        .contact-subtitle {
            font-size: 1.1rem;
        }
        
        .contact-section {
            padding: 2rem 0;
        }
    }
    
    @media (max-width: 576px) {
        .contact-title {
            font-size: 1.8rem;
        }
        
        .contact-badge {
            font-size: 0.8rem;
            padding: 6px 16px;
        }
    }
  </style>
</head>
<body>

  <!-- Navbar -->
  <?php include '../Includes/navbar.php'; ?>

  <!-- Contact Hero -->
  <section class="contact-hero">
    <div class="container">
      <span class="contact-badge">Contact Us</span>
      <h1 class="contact-title">Get in <span class="text-accent">Touch</span> With Us</h1>
      <p class="contact-subtitle">Have questions, feedback, or ideas? Reach out and let's grow together. We're here to help!</p>
    </div>
  </section>

  <!-- Contact Content -->
  <section class="contact-section">
    <div class="container">
      <div class="row g-5">
        <!-- Contact Form -->
        <div class="col-lg-7">
          <div class="card shadow-lg border-0">
            <div class="card-body p-4 p-md-5">
              <?php if ($success): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                  <?php echo $success; ?>
                  <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
              <?php endif; ?>
              
              <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                  <?php echo $error; ?>
                  <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
              <?php endif; ?>

              <h4 class="mb-4 fw-bold" style="color: var(--dark-green);">
                <i class="fas fa-envelope me-2" style="color: var(--accent-gold);"></i> Send Us a Message
              </h4>
              
              <form method="post">
                <div class="mb-4">
                  <label for="name" class="form-label">Your Name *</label>
                  <input type="text" class="form-control" id="name" name="name" required 
                         placeholder="Enter your full name">
                </div>
                
                <div class="mb-4">
                  <label for="email" class="form-label">Your Email *</label>
                  <input type="email" class="form-control" id="email" name="email" required 
                         placeholder="Enter your email address">
                </div>
                
                <div class="mb-4">
                  <label for="message" class="form-label">Your Message *</label>
                  <textarea class="form-control" id="message" name="message" rows="6" required 
                            placeholder="How can we help you?"></textarea>
                </div>
                
                <button type="submit" class="btn btn-success btn-lg w-100" name="send">
                  <i class="fas fa-paper-plane me-2"></i>Send Message
                </button>
              </form>
            </div>
          </div>
        </div>

        <!-- Contact Info -->
        <div class="col-lg-5">
          <div class="card shadow-lg border-0 h-100">
            <div class="card-body p-4 p-md-5">
              <h4 class="mb-4 fw-bold" style="color: var(--dark-green);">
                <i class="fas fa-map-marker-alt me-2" style="color: var(--accent-gold);"></i> Our Contact Information
              </h4>
              
              <div class="contact-info-item">
                <i class="fas fa-location-dot contact-icon"></i>
                <div>
                  <strong>FarmCart Headquarters</strong>
                  <p>Baguio City, Benguet<br>Philippines 2600</p>
                </div>
              </div>
              
              <div class="contact-info-item">
                <i class="fas fa-envelope contact-icon"></i>
                <div>
                  <strong>Email Address</strong>
                  <p>support@farmcart.ph<br>info@farmcart.ph</p>
                </div>
              </div>
              
              <div class="contact-info-item">
                <i class="fas fa-phone contact-icon"></i>
                <div>
                  <strong>Phone Numbers</strong>
                  <p>+63 912 345 6789<br>+63 917 890 1234</p>
                </div>
              </div>
              
              <div class="contact-info-item">
                <i class="fas fa-clock contact-icon"></i>
                <div>
                  <strong>Business Hours</strong>
                  <p>Monday - Friday: 8:00 AM – 6:00 PM<br>Saturday: 9:00 AM – 4:00 PM<br>Sunday: Closed</p>
                </div>
              </div>
              
              <hr class="my-4">
              
              <h6 class="text-muted fw-bold mb-3">Connect With Us</h6>
              <div class="d-flex gap-3">
                <a href="#" class="btn btn-outline-success">
                  <i class="fab fa-facebook-f"></i>
                </a>
                <a href="#" class="btn btn-outline-success">
                  <i class="fab fa-instagram"></i>
                </a>
                <a href="#" class="btn btn-outline-success">
                  <i class="fab fa-twitter"></i>
                </a>
                <a href="#" class="btn btn-outline-success">
                  <i class="fab fa-linkedin-in"></i>
                </a>
              </div>
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
  
  <script>
    // Initialize tooltips
    document.addEventListener('DOMContentLoaded', function() {
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        const tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
        
        // Auto-dismiss alerts after 5 seconds
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);
    });
  </script>
</body>
</html>