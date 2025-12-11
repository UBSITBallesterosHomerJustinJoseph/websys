<?php
// db_connect.php - Prevent multiple inclusions
if (!defined('FARMCART_DB_CONNECT')) {
    define('FARMCART_DB_CONNECT', true);

    // Start session only if not already started
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // Database configuration - only define if not already defined
    if (!defined('SERVER')) define("SERVER", "localhost");
    if (!defined('USER')) define("USER", "root");
    if (!defined('PASSWORD')) define("PASSWORD", "");
    if (!defined('DBNAME')) define("DBNAME", "farmcart");
    if (!defined('CHARSET')) define("CHARSET", "utf8mb4");

    class FarmCart {
        public $conn;

        function __construct() {
            $this->conn = mysqli_connect(SERVER, USER, PASSWORD, DBNAME);
            if (!$this->conn) {
                die("Connection failed: " . mysqli_connect_error());
            }
            // Set charset
            $this->conn->set_charset(CHARSET);
        }

        // Check if user can become a farmer (all customers can become farmers)
        public function canBecomeFarmer($user_id) {
            $user_id = (int)$user_id; // Sanitize input
            $query = "SELECT role FROM users WHERE user_id = $user_id";
            $result = mysqli_query($this->conn, $query);

            if($result && mysqli_num_rows($result) > 0) {
                $user = mysqli_fetch_assoc($result);
                // Customers can become farmers, farmers and admins cannot
                return isset($user['role']) && $user['role'] === 'customer';
            }
            return false;
        }

        // Setup farmer store
        public function setupFarmerStore(
            $user_id,
            $farm_name,
            $farm_location,
            $farm_size,
            $farming_method,
            $years_experience,
            $certification_details,
            $bio
        ) {
            try {
                // Begin transaction for safety
                mysqli_begin_transaction($this->conn);

                // Update user role to farmer
                $update_role = $this->conn->prepare("UPDATE users SET role = 'farmer' WHERE user_id = ?");
                $update_role->bind_param("i", $user_id);

                if (!$update_role->execute()) {
                    mysqli_rollback($this->conn);
                    return ['success' => false, 'message' => 'Failed to update user role: ' . $update_role->error];
                }

                // Insert farmer profile
                $insert_profile = $this->conn->prepare("
                    INSERT INTO farmer_profiles 
                    (user_id, farm_name, farm_location, farm_size, farming_method, years_experience, certification_details, bio, is_verified_farmer, created_at, updated_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0, NOW(), NOW())
                ");

                $farm_size = (float)$farm_size; // Changed to float for decimal values
                $years_experience = (int)$years_experience;

                $insert_profile->bind_param(
                    "issdisis",   // i=int, s=string, d=double (float)
                    $user_id,
                    $farm_name,
                    $farm_location,
                    $farm_size,
                    $farming_method,
                    $years_experience,
                    $certification_details,
                    $bio
                );

                if ($insert_profile->execute()) {
                    mysqli_commit($this->conn);
                    $_SESSION['user_role'] = 'farmer';
                    return ['success' => true, 'message' => 'Farmer store created successfully!'];
                } else {
                    mysqli_rollback($this->conn);
                    return ['success' => false, 'message' => 'Failed to create farmer profile: ' . $insert_profile->error];
                }
            } catch (Exception $e) {
                mysqli_rollback($this->conn);
                return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
            }
        }

        // Get user by ID
        public function get_user($user_id) {
            $user_id = (int)$user_id;
            $query = "SELECT * FROM users WHERE user_id = $user_id";
            $result = mysqli_query($this->conn, $query);
            if($result && mysqli_num_rows($result) > 0) {
                return mysqli_fetch_assoc($result);
            }
            return null;
        }

        // User Registration
        public function register($first_name, $last_name, $email, $password, $phone_number = '', $address = '', $role = 'customer') {
            // Check if email exists
            $email = $this->conn->real_escape_string($email);
            $check_email = "SELECT user_id FROM users WHERE email = '$email'";
            $result_check = mysqli_query($this->conn, $check_email);

            if($result_check && mysqli_num_rows($result_check) > 0) {
                return ['success' => false, 'message' => 'Email already registered!'];
            }

            // Hash password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            // Insert new user
            $first_name = $this->conn->real_escape_string($first_name);
            $last_name = $this->conn->real_escape_string($last_name);
            $phone_number = $this->conn->real_escape_string($phone_number);
            $address = $this->conn->real_escape_string($address);
            
            $insert = "INSERT INTO users (first_name, last_name, email, password, role, phone_number, address, is_verified, created_at, updated_at)
                       VALUES ('$first_name', '$last_name', '$email', '$hashed_password', '$role', '$phone_number', '$address', 0, NOW(), NOW())";

            $result = mysqli_query($this->conn, $insert);

            if($result) {
                $user_id = mysqli_insert_id($this->conn);
                return [
                    'success' => true,
                    'user_id' => $user_id,
                    'message' => 'Registration successful!'
                ];
            } else {
                return ['success' => false, 'message' => 'Registration failed: ' . mysqli_error($this->conn)];
            }
        }

        // User Login
        public function login($email, $password) {
            $email = $this->conn->real_escape_string($email);
            $login = "SELECT user_id, email, password, role, first_name, last_name, phone_number,
                             address, profile_image, is_verified
                      FROM users
                      WHERE email = '$email'";

            $result = mysqli_query($this->conn, $login);

            if($result && mysqli_num_rows($result) > 0) {
                $row = mysqli_fetch_assoc($result);

                if(password_verify($password, $row['password'])) {
                    // Set session variables
                    $_SESSION['user_id'] = $row['user_id'];
                    $_SESSION['user_email'] = $row['email'];
                    $_SESSION['user_name'] = $row['first_name'] . ' ' . $row['last_name'];
                    $_SESSION['first_name'] = $row['first_name'];
                    $_SESSION['last_name'] = $row['last_name'];
                    $_SESSION['user_role'] = $row['role'];
                    $_SESSION['phone_number'] = $row['phone_number'];
                    $_SESSION['address'] = $row['address'];
                    $_SESSION['profile_image'] = $row['profile_image'];
                    $_SESSION['is_verified'] = $row['is_verified'];
                    $_SESSION['login_time'] = time();

                    return [
                        'success' => true,
                        'role' => $row['role'],
                        'message' => 'Login successful!'
                    ];
                } else {
                    return ['success' => false, 'message' => 'Invalid password!'];
                }
            } else {
                return ['success' => false, 'message' => 'Invalid email or password!'];
            }
        }

        // Update User Profile
        public function update_profile($user_id, $first_name, $last_name, $email, $phone_number = null, $address = null, $profile_image = null) {
            $user_id = (int)$user_id;
            $email = $this->conn->real_escape_string($email);
            
            // Check if email is already taken by another user
            $check_email = "SELECT user_id FROM users WHERE email = '$email' AND user_id != $user_id";
            $result_check = mysqli_query($this->conn, $check_email);

            if($result_check && mysqli_num_rows($result_check) > 0) {
                return ['success' => false, 'message' => 'Email already taken by another user!'];
            }

            $first_name = $this->conn->real_escape_string($first_name);
            $last_name = $this->conn->real_escape_string($last_name);
            $phone_number = $this->conn->real_escape_string($phone_number);
            $address = $this->conn->real_escape_string($address);

            if($profile_image) {
                $profile_image = $this->conn->real_escape_string($profile_image);
                $update = "UPDATE users SET first_name = '$first_name', last_name = '$last_name', email = '$email',
                          phone_number = '$phone_number', address = '$address', profile_image = '$profile_image',
                          updated_at = NOW() WHERE user_id = $user_id";
            } else {
                $update = "UPDATE users SET first_name = '$first_name', last_name = '$last_name', email = '$email',
                          phone_number = '$phone_number', address = '$address', updated_at = NOW()
                          WHERE user_id = $user_id";
            }

            $result = mysqli_query($this->conn, $update);

            if($result) {
                // Update session data
                $_SESSION['first_name'] = $first_name;
                $_SESSION['last_name'] = $last_name;
                $_SESSION['email'] = $email;
                $_SESSION['phone_number'] = $phone_number;
                $_SESSION['address'] = $address;
                if($profile_image) $_SESSION['profile_image'] = $profile_image;

                return ['success' => true, 'message' => 'Profile updated successfully!'];
            } else {
                return ['success' => false, 'message' => 'Failed to update profile: ' . mysqli_error($this->conn)];
            }
        }

        // Check if user is logged in
        public function is_logged_in() {
            return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
        }

        // Logout user
// Logout user
public function logout() {
    // Clear all session variables
    $_SESSION = array();

    // Delete session cookie
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }

    // Destroy session
    session_destroy();

    // Clear any output buffers
    if (ob_get_length()) {
        ob_end_clean();
    }

    // Return true instead of redirecting
    return true;
}

// Function to get the correct logout path based on current location
public function get_logout_path() {
    $current_script = $_SERVER['SCRIPT_NAME'];

    if (strpos($current_script, 'Pages/admin') !== false) {
        return '../../../Register/login.php?logout=1';
    } elseif (strpos($current_script, 'Pages/farmer') !== false) {
        return '../../../Register/login.php?logout=1';
    } elseif (strpos($current_script, 'Pages/customer') !== false) {
        return '../../Register/login.php?logout=1';
    } elseif (strpos($current_script, 'Header') !== false) {
        return '../Register/login.php?logout=1';
    } elseif (strpos($current_script, 'Register') !== false) {
        return 'login.php?logout=1';
    } else {
        return 'Register/login.php?logout=1';
    }
}

        // Get farmer profile
        public function get_farmer_profile($user_id) {
            $user_id = (int)$user_id;
            $query = "SELECT * FROM farmer_profiles WHERE user_id = $user_id";
            $result = mysqli_query($this->conn, $query);
            if($result && mysqli_num_rows($result) > 0) {
                return mysqli_fetch_assoc($result);
            }
            return null;
        }

        // Close connection
        public function __destruct() {
            if($this->conn) {
                mysqli_close($this->conn);
            }
        }
    }

    // Create global instance only if not already created
    if (!isset($GLOBALS['farmcart'])) {
        $GLOBALS['farmcart'] = new FarmCart();
    }
}

// Make farmcart available globally
if (isset($GLOBALS['farmcart']) && !isset($farmcart)) {
    $farmcart = $GLOBALS['farmcart'];
}
?>
