<?php
session_start();
include '../db_connect.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/vendor/autoload.php';

// Guard
if (!isset($_SESSION['user_id'])) {
    header("location: ../index.php");
    exit();
}

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
  <!-- FarmCart Styles -->
  <link rel="stylesheet" href="../Assets/css/navbar.css">
  <style>
    body { background-color: var(--light-bg); font-family: 'Inter', sans-serif; }
    .contact-badge {
      background: var(--accent-gold); color: var(--dark-green);
      font-weight: 600; border-radius: 25px; padding: 6px 14px;
    }
    .contact-title { color: var(--primary-green); font-weight: 700; font-size: 2.2rem; }
    .contact-title .text-accent { color: var(--accent-gold); }
    .contact-subtitle { color: var(--text-light); }
    .card { border-radius: 12px; border: 1px solid var(--border-light); }
    .form-label { font-weight: 600; color: var(--dark-green); }
    .form-control {
      border-radius: 8px; border: 2px solid var(--border-light);
      transition: all 0.3s ease; padding: 0.75rem;
    }
    .form-control:focus {
      border-color: var(--accent-gold);
      box-shadow: 0 0 0 0.2rem rgba(212,175,55,0.25);
    }
    .btn-success {
      background: linear-gradient(135deg, var(--accent-gold), #E8C547);
      color: var(--dark-green); font-weight: 600;
      border-radius: 8px; border: none; transition: all 0.3s ease;
    }
    .btn-success:hover {
      background: linear-gradient(135deg, #c19b2a, #d4b342);
      transform: translateY(-2px);
      box-shadow: 0 6px 15px rgba(212,175,55,0.3);
    }
    .alert { border-radius: 8px; font-weight: 500; text-align: center; }
  </style>
</head>
<body>

  <!-- Navbar -->
  <?php include '../Includes/navbar.php'; ?>

  <!-- Contact Section -->
  <section class="contact-section py-5">
    <div class="container">
      <div class="text-center mb-5">
        <span class="contact-badge">We’d Love to Hear From You</span>
        <h2 class="contact-title">Get in <span class="text-accent">Touch</span></h2>
        <p class="contact-subtitle">Have questions, feedback, or ideas? Reach out and let’s grow together.</p>
      </div>

      <div class="row g-5">
        <!-- Contact Form -->
        <div class="col-md-6">
          <div class="card shadow-lg border-0">
            <div class="card-body p-4">
              <?php if ($success): ?><div class="alert alert-success"><?php echo $success; ?></div><?php endif; ?>
              <?php if ($error): ?><div class="alert alert-danger"><?php echo $error; ?></div><?php endif; ?>

              <form method="post">
                <div class="mb-3">
                  <label for="name" class="form-label">Your Name *</label>
                  <input type="text" class="form-control" id="name" name="name" required>
                </div>
                <div class="mb-3">
                  <label for="email" class="form-label">Your Email *</label>
                  <input type="email" class="form-control" id="email" name="email" required>
                </div>
                <div class="mb-3">
                  <label for="message" class="form-label">Your Message *</label>
                  <textarea class="form-control" id="message" name="message" rows="5" required></textarea>
                </div>
                <button type="submit" class="btn btn-success btn-lg w-100" name="send">
                  <i class="fas fa-paper-plane me-2"></i>Send Message
                </button>
              </form>
            </div>
          </div>
        </div>

        <!-- Contact Info -->
        <div class="col-md-6">
          <div class="card shadow-lg border-0">
            <div class="card-body p-4">
              <h5 class="mb-4 fw-bold" style="color: var(--dark-green);">
                <i class="fas fa-map-marker-alt me-2" style="color: var(--accent-gold);"></i> FarmCart Headquarters
              </h5>
              <p><i class="fas fa-location-dot text-success me-2"></i> Baguio City, Philippines</p>
              <p><i class="fas fa-envelope text-success me-2"></i> support@farmcart.ph</p>
              <p><i class="fas fa-phone text-success me-2"></i> +63 912 345 6789</p>
              <hr>
              <h6 class="text-muted fw-bold">Business Hours</h6>
              <p>Monday to Friday: 8:00 AM – 6:00 PM</p>
              <p>Saturday to Sunday: Closed</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Footer -->
  <?php include '../Includes/footer.php'; ?>

  <!-- Bootstrap JS (needed for dropdowns) -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
