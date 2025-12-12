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
        private $pdo;

        function __construct() {
            // PDO connection for security
            try {
                $dsn = "mysql:host=" . SERVER . ";dbname=" . DBNAME . ";charset=" . CHARSET;
                $options = [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . CHARSET
                ];
                $this->pdo = new PDO($dsn, USER, PASSWORD, $options);
            } catch (PDOException $e) {
                error_log("PDO Connection error: " . $e->getMessage());
                die("Database connection failed. Please try again later.");
            }
            
            // MySQLi connection for backward compatibility
            $this->conn = mysqli_connect(SERVER, USER, PASSWORD, DBNAME);
            if (!$this->conn) {
                die("Connection failed: " . mysqli_connect_error());
            }
            $this->conn->set_charset(CHARSET);
        }

        // Get PDO instance
        public function getPDO() {
            return $this->pdo;
        }

        // Check if user can become a farmer (all customers can become farmers)
        public function canBecomeFarmer($user_id) {
            try {
                $stmt = $this->pdo->prepare("SELECT role FROM users WHERE user_id = ?");
                $stmt->execute([(int)$user_id]);
                $user = $stmt->fetch();
                return isset($user['role']) && $user['role'] === 'customer';
            } catch (PDOException $e) {
                error_log("canBecomeFarmer error: " . $e->getMessage());
                return false;
            }
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
                $this->pdo->beginTransaction();

                // Update user role to farmer
                $stmt = $this->pdo->prepare("UPDATE users SET role = 'farmer' WHERE user_id = ?");
                $stmt->execute([(int)$user_id]);

                // Insert farmer profile
                $stmt = $this->pdo->prepare("
                    INSERT INTO farmer_profiles 
                    (user_id, farm_name, farm_location, farm_size, farming_method, years_experience, certification_details, bio, is_verified_farmer, created_at, updated_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0, NOW(), NOW())
                ");
                
                $stmt->execute([
                    (int)$user_id,
                    $farm_name,
                    $farm_location,
                    (float)$farm_size,
                    $farming_method,
                    (int)$years_experience,
                    $certification_details,
                    $bio
                ]);

                $this->pdo->commit();
                $_SESSION['user_role'] = 'farmer';
                return ['success' => true, 'message' => 'Farmer store created successfully!'];
            } catch (PDOException $e) {
                $this->pdo->rollBack();
                error_log("setupFarmerStore error: " . $e->getMessage());
                return ['success' => false, 'message' => 'Failed to create farmer store. Please try again.'];
            }
        }

        // Get user by ID
        public function get_user($user_id) {
            try {
                $stmt = $this->pdo->prepare("SELECT * FROM users WHERE user_id = ?");
                $stmt->execute([(int)$user_id]);
                return $stmt->fetch() ?: null;
            } catch (PDOException $e) {
                error_log("get_user error: " . $e->getMessage());
                return null;
            }
        }

        // User Registration
        public function register($first_name, $last_name, $email, $password, $phone_number = '', $address = '', $role = 'customer') {
            try {
                // Check if email exists
                $stmt = $this->pdo->prepare("SELECT user_id FROM users WHERE email = ?");
                $stmt->execute([$email]);
                if ($stmt->fetch()) {
                    return ['success' => false, 'message' => 'Email already registered!'];
                }

                // Hash password
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);

                // Insert new user
                $stmt = $this->pdo->prepare("
                    INSERT INTO users (first_name, last_name, email, password, role, phone_number, address, is_verified, created_at, updated_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, 0, NOW(), NOW())
                ");
                
                $stmt->execute([$first_name, $last_name, $email, $hashed_password, $role, $phone_number, $address]);
                
                return [
                    'success' => true,
                    'user_id' => $this->pdo->lastInsertId(),
                    'message' => 'Registration successful!'
                ];
            } catch (PDOException $e) {
                error_log("register error: " . $e->getMessage());
                return ['success' => false, 'message' => 'Registration failed. Please try again.'];
            }
        }

        // User Login
        public function login($email, $password) {
            try {
                $stmt = $this->pdo->prepare("
                    SELECT user_id, email, password, role, first_name, last_name, phone_number, address, profile_image, is_verified
                    FROM users
                    WHERE email = ?
                ");
                $stmt->execute([$email]);
                $row = $stmt->fetch();

                if ($row && password_verify($password, $row['password'])) {
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
                    return ['success' => false, 'message' => 'Invalid email or password!'];
                }
            } catch (PDOException $e) {
                error_log("login error: " . $e->getMessage());
                return ['success' => false, 'message' => 'Login failed. Please try again.'];
            }
        }

        // Update User Profile
        public function update_profile($user_id, $first_name, $last_name, $email, $phone_number = null, $address = null, $profile_image = null) {
            try {
                // Check if email is taken by another user
                $stmt = $this->pdo->prepare("SELECT user_id FROM users WHERE email = ? AND user_id != ?");
                $stmt->execute([$email, (int)$user_id]);
                if ($stmt->fetch()) {
                    return ['success' => false, 'message' => 'Email already taken by another user!'];
                }

                if ($profile_image) {
                    $stmt = $this->pdo->prepare("
                        UPDATE users SET first_name = ?, last_name = ?, email = ?, phone_number = ?, address = ?, profile_image = ?, updated_at = NOW()
                        WHERE user_id = ?
                    ");
                    $stmt->execute([$first_name, $last_name, $email, $phone_number, $address, $profile_image, (int)$user_id]);
                } else {
                    $stmt = $this->pdo->prepare("
                        UPDATE users SET first_name = ?, last_name = ?, email = ?, phone_number = ?, address = ?, updated_at = NOW()
                        WHERE user_id = ?
                    ");
                    $stmt->execute([$first_name, $last_name, $email, $phone_number, $address, (int)$user_id]);
                }

                // Update session
                $_SESSION['first_name'] = $first_name;
                $_SESSION['last_name'] = $last_name;
                $_SESSION['email'] = $email;
                $_SESSION['phone_number'] = $phone_number;
                $_SESSION['address'] = $address;
                if ($profile_image) $_SESSION['profile_image'] = $profile_image;

                return ['success' => true, 'message' => 'Profile updated successfully!'];
            } catch (PDOException $e) {
                error_log("update_profile error: " . $e->getMessage());
                return ['success' => false, 'message' => 'Failed to update profile. Please try again.'];
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
            try {
                $stmt = $this->pdo->prepare("SELECT * FROM farmer_profiles WHERE user_id = ?");
                $stmt->execute([(int)$user_id]);
                return $stmt->fetch() ?: null;
            } catch (PDOException $e) {
                error_log("get_farmer_profile error: " . $e->getMessage());
                return null;
            }
        }

        // Add Review
        public function addReview($product_id, $customer_id, $order_id, $rating, $review_text = '') {
            try {
                // Check if review already exists
                $stmt = $this->pdo->prepare("SELECT review_id FROM reviews WHERE order_id = ? AND product_id = ?");
                $stmt->execute([(int)$order_id, (int)$product_id]);
                if ($stmt->fetch()) {
                    return ['success' => false, 'message' => 'You have already reviewed this product from this order.'];
                }

                // Verify order belongs to customer
                $stmt = $this->pdo->prepare("SELECT order_id FROM orders WHERE order_id = ? AND customer_id = ?");
                $stmt->execute([(int)$order_id, (int)$customer_id]);
                if (!$stmt->fetch()) {
                    return ['success' => false, 'message' => 'Invalid order.'];
                }

                $stmt = $this->pdo->prepare("
                    INSERT INTO reviews (product_id, customer_id, order_id, rating, review_text, is_approved, created_at)
                    VALUES (?, ?, ?, ?, ?, 1, NOW())
                ");
                
                $stmt->execute([
                    (int)$product_id,
                    (int)$customer_id,
                    (int)$order_id,
                    (int)$rating,
                    $review_text
                ]);

                return ['success' => true, 'message' => 'Review submitted successfully!'];
            } catch (PDOException $e) {
                error_log("addReview error: " . $e->getMessage());
                return ['success' => false, 'message' => 'Failed to submit review. Please try again.'];
            }
        }

        // Get Product Reviews
        public function getProductReviews($product_id, $limit = 10) {
            try {
                $stmt = $this->pdo->prepare("
                    SELECT r.*, u.first_name, u.last_name, u.profile_image
                    FROM reviews r
                    JOIN users u ON r.customer_id = u.user_id
                    WHERE r.product_id = ? AND r.is_approved = 1
                    ORDER BY r.created_at DESC
                    LIMIT ?
                ");
                $stmt->execute([(int)$product_id, (int)$limit]);
                return $stmt->fetchAll();
            } catch (PDOException $e) {
                error_log("getProductReviews error: " . $e->getMessage());
                return [];
            }
        }

        // Get Average Rating for Product
        public function getProductRating($product_id) {
            try {
                $stmt = $this->pdo->prepare("
                    SELECT AVG(rating) as avg_rating, COUNT(*) as review_count
                    FROM reviews
                    WHERE product_id = ? AND is_approved = 1
                ");
                $stmt->execute([(int)$product_id]);
                $result = $stmt->fetch();
                return [
                    'avg_rating' => round($result['avg_rating'] ?? 0, 1),
                    'review_count' => (int)($result['review_count'] ?? 0)
                ];
            } catch (PDOException $e) {
                error_log("getProductRating error: " . $e->getMessage());
                return ['avg_rating' => 0, 'review_count' => 0];
            }
        }

        // Get Farmer's Product Reviews
        public function getFarmerReviews($farmer_id, $limit = 50) {
            try {
                $stmt = $this->pdo->prepare("
                    SELECT r.*, p.product_name, u.first_name, u.last_name, u.profile_image
                    FROM reviews r
                    JOIN products p ON r.product_id = p.product_id
                    JOIN users u ON r.customer_id = u.user_id
                    WHERE p.created_by = ? AND r.is_approved = 1
                    ORDER BY r.created_at DESC
                    LIMIT ?
                ");
                $stmt->execute([(int)$farmer_id, (int)$limit]);
                return $stmt->fetchAll();
            } catch (PDOException $e) {
                error_log("getFarmerReviews error: " . $e->getMessage());
                return [];
            }
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
