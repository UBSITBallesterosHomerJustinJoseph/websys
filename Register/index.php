<?php
include '../db_connect.php';

$error = '';
$success = '';

if(isset($_POST['register'])){
    $first_name = mysqli_real_escape_string($farmcart->conn, $_POST['first_name']);
    $last_name = mysqli_real_escape_string($farmcart->conn, $_POST['last_name']);
    $email = mysqli_real_escape_string($farmcart->conn, $_POST['email']);
    $phone_number = mysqli_real_escape_string($farmcart->conn, $_POST['phone_number']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Basic validation
    if(empty($first_name) || empty($last_name) || empty($email) || empty($password)) {
        $error = "All required fields are required!";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match!";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters long!";
    } else {
        $result = $farmcart->register($first_name, $last_name, $email, $password, $phone_number, '', 'customer');

        if($result['success']) {
            $success = $result['message'];

            // Auto-login after successful registration
            $login_result = $farmcart->login($email, $password);
            if($login_result['success']) {
                echo "
                    <script>
                        alert('Registration successful!');
                        window.location.href = '../Pages/customer/index.php';
                    </script>
                ";
                exit();
            } else {
                $error = "Registration successful but login failed: " . $login_result['message'];
            }
        } else {
            $error = $result['message'];
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>FarmCart - Register</title>
  <link href="https://cdn.jsdelivr.net/npm/bootswatch@5.3.3/dist/flatly/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

  <style>
    body {
  font-family: 'Inter', sans-serif;
  background: linear-gradient(135deg, #0f2027, #203a43, #2c5364); /* deep green-teal gradient */
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 20px;
}

.register-container {
  background: rgba(255, 255, 255, 0.95);
  backdrop-filter: blur(12px);
  border-radius: 20px;
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.25);
  padding: 40px 35px;
  width: 100%;
  max-width: 520px;
  transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.register-container:hover {
  transform: translateY(-5px);
  box-shadow: 0 15px 35px rgba(0, 0, 0, 0.3);
}

.register-header {
  text-align: center;
  margin-bottom: 30px;
}

.register-header img {
  width: 160px;
  margin-bottom: 15px;
}

.register-header h2 {
  color: #1b4332; /* dark forest green */
  font-weight: 700;
  margin-bottom: 8px;
  font-size: 2rem;
}

.register-header p {
  color: #555;
  font-size: 1rem;
  margin: 0;
}

.form-label {
  font-weight: 600;
  color: #2c3e50;
}

.input-group-text {
  background: #2d6a4f; /* rich green accent */
  color: #fff;
  border: none;
}

.form-control {
  border-radius: 10px;
  border: 1px solid #ddd;
  padding: 12px;
  transition: border-color 0.3s ease, box-shadow 0.3s ease;
}

.form-control:focus {
  border-color: #2d6a4f;
  box-shadow: 0 0 8px rgba(45, 106, 79, 0.4);
}

.btn-success {
  background: #2d6a4f;
  border: none;
  border-radius: 10px;
  font-weight: 600;
  padding: 12px;
  transition: background 0.3s ease;
}

.btn-success:hover {
  background: #1b4332; /* darker hover */
}

.btn-primary {
  border-radius: 10px;
  font-weight: 600;
  padding: 12px;
  background: #40916c; /* lighter green for contrast */
  border: none;
}

.btn-primary:hover {
  background: #2d6a4f;
}

.btn-outline-secondary {
  border-radius: 10px;
}

.alert {
  border-radius: 10px;
  font-size: 0.95rem;
}

.form-text {
  font-size: 0.85rem;
  color: #777;
}

.text-muted {
  font-size: 0.9rem;
  color: #40916c; /* subtle green accent */
}

/* Responsive tweaks */
@media (max-width: 576px) {
  .register-container {
    padding: 30px 20px;
  }
  .register-header h2 {
    font-size: 1.6rem;
  }
}
  </style>
</head>
<body>
  <div class="register-container">
    <div class="register-header">
      <img src="LOGOFARMCART.png" alt="FarmCart Logo">
      <h2>Create Your Account</h2>
      <p>Join FarmCart and shop fresh from farm to table</p>
    </div>

    <!-- Error Message -->
    <?php if(!empty($error)): ?>
      <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-triangle me-2"></i>
        <?php echo $error; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    <?php endif; ?>

    <!-- Success Message -->
    <?php if(!empty($success)): ?>
      <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle me-2"></i>
        <?php echo $success; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    <?php endif; ?>

    <!-- Registration Form -->
    <form method="post">
      <div class="row g-3">
        <div class="col-md-6">
          <label class="form-label">First Name *</label>
          <div class="input-group">
            <span class="input-group-text"><i class="fas fa-user"></i></span>
            <input type="text" name="first_name" class="form-control" placeholder="Enter first name" required
              value="<?php echo isset($_POST['first_name']) ? htmlspecialchars($_POST['first_name']) : ''; ?>">
          </div>
        </div>
        <div class="col-md-6">
          <label class="form-label">Last Name *</label>
          <div class="input-group">
            <span class="input-group-text"><i class="fas fa-user"></i></span>
            <input type="text" name="last_name" class="form-control" placeholder="Enter last name" required
              value="<?php echo isset($_POST['last_name']) ? htmlspecialchars($_POST['last_name']) : ''; ?>">
          </div>
        </div>
      </div>

      <div class="mb-3">
        <label class="form-label">Email Address *</label>
        <div class="input-group">
          <span class="input-group-text"><i class="fas fa-envelope"></i></span>
          <input type="email" name="email" class="form-control" placeholder="Enter your email address" required
            value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
        </div>
      </div>

      <div class="mb-3">
        <label class="form-label">Phone Number *</label>
        <div class="input-group">
          <span class="input-group-text"><i class="fas fa-phone"></i></span>
          <input type="tel" name="phone_number" class="form-control" placeholder="Enter your phone number" required
            value="<?php echo isset($_POST['phone_number']) ? htmlspecialchars($_POST['phone_number']) : ''; ?>">
        </div>
      </div>

      <div class="mb-3">
        <label class="form-label">Create Password *</label>
        <div class="input-group">
          <span class="input-group-text"><i class="fas fa-lock"></i></span>
          <input type="password" name="password" class="form-control" placeholder="Create a password" required minlength="6" id="password">
          <button type="button" class="btn btn-outline-secondary" onclick="togglePassword('password')">
            <i class="fas fa-eye"></i>
          </button>
        </div>
        <div class="form-text">Must be at least 6 characters long.</div>
      </div>

      <div class="mb-4">
        <label class="form-label">Confirm Password *</label>
        <div class="input-group">
          <span class="input-group-text"><i class="fas fa-lock"></i></span>
          <input type="password" name="confirm_password" class="form-control" placeholder="Confirm your password" required id="confirmPassword">
          <button type="button" class="btn btn-outline-secondary" onclick="togglePassword('confirmPassword')">
            <i class="fas fa-eye"></i>
          </button>
        </div>
      </div>

      <div class="form-check mb-4">
        <input class="form-check-input" type="checkbox" id="termsAgree" required>
        <label class="form-check-label" for="termsAgree">
          I agree to the <a href="#" class="text-success">Terms of Service</a> and <a href="#" class="text-success">Privacy Policy</a>
        </label>
      </div>

      <div class="d-grid gap-3">
        <button type="submit" name="register" class="btn btn-success btn-lg">
          <i class="fas fa-user-plus me-2"></i>Create Account
        </button>
        <a href="login.php" class="btn btn-primary btn-lg">
          <i class="fas fa-arrow-left me-2"></i>Back to Login
        </a>
      </div>
    </form>

    <div class="text-center mt-4">
      <p class="text-muted">
        <i class="fas fa-seedling me-2"></i>FarmCart - Fresh From Farm To Table
      </p>
    </div>
  </div>

     <script>
    function togglePassword(fieldId) {
      const passwordInput = document.getElementById(fieldId);
      const toggleIcon = passwordInput.parentElement.querySelector('i');

      if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        toggleIcon.className = 'fas fa-eye-slash';
      } else {
        passwordInput.type = 'password';
        toggleIcon.className = 'fas fa-eye';
      }
    }

    // Form validation
    document.querySelector('form').addEventListener('submit', function(e) {
      const password = document.getElementById('password').value;
      const confirmPassword = document.getElementById('confirmPassword').value;
      const termsAgree = document.getElementById('termsAgree').checked;

      if (password !== confirmPassword) {
        e.preventDefault();
        alert('Passwords do not match!');
        return;
      }

      if (!termsAgree) {
        e.preventDefault();
        alert('Please agree to the Terms of Service and Privacy Policy.');
        return;
      }
    });
  </script>
</body>
</html>
