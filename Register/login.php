<?php
// login.php - Updated with proper logout handling

// Start session at the very beginning
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Handle logout request FIRST
if (isset($_GET['logout']) && $_GET['logout'] == 1) {
    // Include db_connect to get FarmCart class
    require_once '../db_connect.php';

    // Clear all session variables
    $_SESSION = array();

    // Delete the session cookie
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }

    // Destroy the session
    session_destroy();

    // Clear any output buffers
    while (ob_get_level()) {
        ob_end_clean();
    }

    // Set a session variable to indicate logout
    session_start();
    $_SESSION['logout_message'] = "You have been successfully logged out.";

    // Redirect to the same page without logout parameter
    header("Location: login.php");
    exit();
}

// Include db_connect AFTER logout handling
require_once '../db_connect.php';

// Now handle login form submission
$error = '';
$success_msg = '';

// Check for logout message
if (isset($_SESSION['logout_message'])) {
    $success_msg = $_SESSION['logout_message'];
    unset($_SESSION['logout_message']); // Clear the message
}

if (isset($_POST['login'])) {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    if (empty($email) || empty($password)) {
        $error = "Please enter both email and password.";
    } else {
        $result = $farmcart->login($email, $password);

        if ($result['success']) {
            // Login successful - redirect based on role or return URL
            $role = $result['role'];

            // Store a login message
            $_SESSION['login_message'] = "Welcome back! You have successfully logged in.";

            // Check if there's a redirect parameter
            if (isset($_GET['redirect']) && !empty($_GET['redirect'])) {
                $redirect_url = urldecode($_GET['redirect']);
                // Validate redirect URL to prevent open redirect vulnerabilities
                // Only allow redirects within the same domain
                if (strpos($redirect_url, '/websys/') === 0 || strpos($redirect_url, 'websys/') === 0) {
                    header("Location: " . $redirect_url);
                } else {
                    // Invalid redirect, use default
                    if ($role === 'admin') {
                        header("Location: ../Pages/admin/index.php");
                    } elseif ($role === 'farmer') {
                        header("Location: ../Pages/farmer/index.php");
                    } else {
                        header("Location: ../Pages/customer/index.php");
                    }
                }
            } else {
                // Default redirect based on role
                if ($role === 'admin') {
                    header("Location: ../Pages/admin/index.php");
                } elseif ($role === 'farmer') {
                    header("Location: ../Pages/farmer/index.php");
                } else {
                    header("Location: ../Pages/customer/index.php");
                }
            }
            exit();
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
  <title>FarmCart - Login</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
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

.login-container {
  background: rgba(255, 255, 255, 0.95);
  backdrop-filter: blur(12px);
  border-radius: 20px;
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.25);
  padding: 40px 35px;
  width: 100%;
  max-width: 420px;
  transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.login-container:hover {
  transform: translateY(-5px);
  box-shadow: 0 15px 35px rgba(0, 0, 0, 0.3);
}

.login-header {
  text-align: center;
  margin-bottom: 25px;
}

.login-header img {
  width: 140px;
  margin-bottom: 10px;
}

.login-header h2 {
  color: #1b4332; /* dark forest green */
  font-weight: 700;
  font-size: 1.9rem;
}

.login-header p {
  color: #555;
  font-size: 1rem;
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

.text-muted {
  font-size: 0.9rem;
  color: #40916c; /* subtle green accent */
}

/* Responsive tweaks */
@media (max-width: 576px) {
  .login-container {
    padding: 30px 20px;
  }
  .login-header h2 {
    font-size: 1.6rem;
  }
}

  </style>
</head>
<body>
  <div class="login-container">
    <div class="login-header">
      <img src="LOGOFARMCART.png" alt="FarmCart Logo">
      <h2>Welcome Back!</h2>
      <p>Sign in to your FarmCart account</p>
    </div>

    <!-- Success Message -->
    <?php if (!empty($success_msg)): ?>
      <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle me-2"></i>
        <?php echo $success_msg; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    <?php endif; ?>

    <!-- Error Message -->
    <?php if (!empty($error)): ?>
      <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-triangle me-2"></i>
        <?php echo $error; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    <?php endif; ?>

    <!-- Login Form -->
    <form method="post">
      <div class="mb-3">
        <label class="form-label">Email Address</label>
        <div class="input-group">
          <span class="input-group-text"><i class="fas fa-envelope"></i></span>
          <input type="email" name="email" class="form-control" placeholder="Enter your email" required
            value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
        </div>
      </div>

      <div class="mb-4">
        <label class="form-label">Password</label>
        <div class="input-group">
          <span class="input-group-text"><i class="fas fa-lock"></i></span>
          <input type="password" name="password" class="form-control" placeholder="Enter your password" required id="password">
          <button type="button" class="btn btn-outline-secondary" onclick="togglePassword()">
            <i class="fas fa-eye"></i>
          </button>
        </div>
      </div>

      <div class="d-grid gap-3">
        <button name="login" class="btn btn-success btn-lg">
          <i class="fas fa-sign-in-alt me-2"></i>Login
        </button>
        <a href="index.php" class="btn btn-primary btn-lg">
          <i class="fas fa-user-plus me-2"></i>Register New Account
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
    function togglePassword() {
      const passwordInput = document.getElementById('password');
      const toggleIcon = document.querySelector('.btn-outline-secondary i');

      if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        toggleIcon.className = 'fas fa-eye-slash';
      } else {
        passwordInput.type = 'password';
        toggleIcon.className = 'fas fa-eye';
      }
    }
  </script>
</body>
</html>
