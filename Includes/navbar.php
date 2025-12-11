<?php
// Determine the current directory structure
$current_script = $_SERVER['SCRIPT_NAME'];


// Check current location
$is_in_header = strpos($current_script, 'Header') !== false;
$is_in_pages_customer = strpos($current_script, 'Pages/customer') !== false;
$is_in_pages_farmer = strpos($current_script, 'Pages/farmer') !== false;
$is_in_pages_admin = strpos($current_script, 'Pages/admin') !== false;
$is_in_register = strpos($current_script, 'Register') !== false;

// Get the current page filename
$current_page = basename($_SERVER['PHP_SELF']);

// Set paths based on current location
if ($is_in_header) {
    // When in Header folder (about.php, contacts.php)
    $home_path = '../Pages/customer/index.php';
    $products_path = '../Pages/customer/products.php';
    $about_path = 'about.php';
    $contact_path = 'contacts.php';
    $logout_path = '../Register/login.php?logout=1';
    $register_path = '../Register/index.php';
    $farmer_dashboard_path = '../Pages/farmer/index.php';
    $admin_dashboard_path = '../Pages/admin/index.php';
    $profile_path = '../Pages/customer/profile.php';
    $edit_profile_path = '../Pages/customer/edit-profile.php';
    $orders_path = '../Pages/customer/orders.php';
    $checkorders_path = '../Pages/customer/checkorders.php';
    $wishlist_path = '../Pages/customer/wishlist.php';
    $setup_store_path = '../Pages/customer/setUpStore.php';
} elseif ($is_in_pages_customer) {
    // When in Pages/customer folder (index.php, products.php)
    $home_path = 'index.php';
    $products_path = 'products.php';
    $about_path = '../../Header/about.php';
    $contact_path = '../../Header/contacts.php';
    $logout_path = '../../Register/login.php?logout=1';
    $register_path = '../../Register/index.php';
    $farmer_dashboard_path = '../farmer/index.php';
    $admin_dashboard_path = '../admin/index.php';
    $profile_path = 'profile.php';
    $edit_profile_path = 'edit-profile.php';
    $orders_path = 'orders.php';
    $checkorders_path = 'checkorders.php';
    $wishlist_path = 'wishlist.php';
    $setup_store_path = 'setUpStore.php';
} elseif ($is_in_pages_farmer) {
    // When in Pages/farmer folder
    $home_path = '../customer/index.php';
    $products_path = '../customer/products.php';
    $about_path = '../../../Header/about.php';
    $contact_path = '../../../Header/contacts.php';
    $logout_path = '../../../Register/login.php?logout=1';
    $register_path = '../../../Register/index.php';
    $farmer_dashboard_path = 'index.php';
    $admin_dashboard_path = '../admin/index.php';
    $profile_path = '../customer/profile.php';
    $edit_profile_path = '../customer/edit-profile.php';
    $orders_path = '../customer/orders.php';
    $checkorders_path = '../customer/checkorders.php';
    $wishlist_path = '../customer/wishlist.php';
    $setup_store_path = '../customer/setUpStore.php';
} elseif ($is_in_pages_admin) {
    // When in Pages/admin folder
    $home_path = '../customer/index.php';
    $products_path = '../customer/products.php';
    $about_path = '../../../Header/about.php';
    $contact_path = '../../../Header/contacts.php';
    $logout_path = '../../../Register/login.php?logout=1';
    $register_path = '../../../Register/index.php';
    $farmer_dashboard_path = '../farmer/index.php';
    $admin_dashboard_path = 'index.php';
    $profile_path = '../customer/profile.php';
    $edit_profile_path = '../customer/edit-profile.php';
    $orders_path = '../customer/orders.php';
    $checkorders_path = '../customer/checkorders.php';
    $wishlist_path = '../customer/wishlist.php';
    $setup_store_path = '../customer/setUpStore.php';
} else {
    // Default fallback (adjust based on your project structure)
    $home_path = 'Pages/customer/index.php';
    $products_path = 'Pages/customer/products.php';
    $about_path = 'Header/about.php';
    $contact_path = 'Header/contacts.php';
    $logout_path = 'Register/login.php?logout=1';
    $register_path = 'Register/index.php';
    $farmer_dashboard_path = 'Pages/farmer/index.php';
    $admin_dashboard_path = 'Pages/admin/index.php';
    $profile_path = 'Pages/customer/profile.php';
    $edit_profile_path = 'Pages/customer/edit-profile.php';
    $orders_path = 'Pages/customer/orders.php';
    $checkorders_path = 'Pages/customer/checkorders.php';
    $wishlist_path = 'Pages/customer/wishlist.php';
    $setup_store_path = 'Pages/customer/setUpStore.php';
}

// Check if user can create store (only for logged-in customers)
$can_create_store = false;
$user_role = null;
$user_data = null;

// Check if user is logged in
$is_logged_in = isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);

if ($is_logged_in) {
    // Safely get user role from session with fallback
    if (isset($_SESSION['user_role'])) {
        $user_role = $_SESSION['user_role'];
    } else {
        // If role is not in session, fetch from database (only if farmcart exists)
        if (isset($farmcart)) {
            $user_data = $farmcart->get_user($_SESSION['user_id']);
            if ($user_data && isset($user_data['role'])) {
                $user_role = $user_data['role'];
                $_SESSION['user_role'] = $user_role; // Update session
                // Also store user name in session for easy access
                if (isset($user_data['first_name']) && isset($user_data['last_name'])) {
                    $_SESSION['first_name'] = $user_data['first_name'];
                    $_SESSION['last_name'] = $user_data['last_name'];
                } elseif (isset($user_data['user_name'])) {
                    $_SESSION['user_name'] = $user_data['user_name'];
                }
            }
        }
    }

    // Only check for store creation if user is a customer and farmcart exists
    if ($user_role === 'customer' && isset($farmcart)) {
        $can_create_store = $farmcart->canBecomeFarmer($_SESSION['user_id']);
    }

    // Fetch user data if not already fetched
    if (!isset($user_data) && isset($farmcart)) {
        $user_data = $farmcart->get_user($_SESSION['user_id']);
    }
}
?>

<!-- Navigation Bar -->
<nav class="navbar navbar-expand-lg navbar-dark fixed-top">
    <div class="container">
        <a class="navbar-brand" href="<?php echo $home_path; ?>">
            <span class="brand-farm">Farm</span><span class="brand-cart">Cart</span>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link <?php echo ($current_page == 'index.php') ? 'active' : ''; ?>" href="<?php echo $home_path; ?>">
                        <i class="fas fa-home"></i> Home
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo ($current_page == 'products.php') ? 'active' : ''; ?>" href="<?php echo $products_path; ?>">
                        <i class="fas fa-shopping-basket"></i> Products
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo ($current_page == 'about.php') ? 'active' : ''; ?>" href="<?php echo $about_path; ?>">
                        <i class="fas fa-info-circle"></i> About
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo ($current_page == 'contacts.php') ? 'active' : ''; ?>" href="<?php echo $contact_path; ?>">
                        <i class="fas fa-phone-alt"></i> Contact
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo ($current_page == 'cart.php') ? 'active' : ''; ?>" href="<?php echo str_replace('products.php', 'cart.php', $products_path); ?>">
                        <i class="fas fa-shopping-cart"></i> Cart
                    </a>
                </li> 

            </ul>

            <!-- Search Form -->
            <form class="search-form" id="searchForm">
                <input type="text" class="search-input" placeholder="Search for fruits, vegetables, dairy..." id="searchInput">
                <button type="submit" class="search-btn">
                    <i class="fas fa-search"></i>
                </button>
                <div class="search-suggestions" id="searchSuggestions">
                    <div class="search-suggestion-item">
                        <i class="fas fa-apple-alt"></i>
                        <span>Fresh Apples</span>
                    </div>
                    <div class="search-suggestion-item">
                        <i class="fas fa-carrot"></i>
                        <span>Organic Carrots</span>
                    </div>
                    <div class="search-suggestion-item">
                        <i class="fas fa-cheese"></i>
                        <span>Farm Cheese</span>
                    </div>
                    <div class="search-suggestion-item">
                        <i class="fas fa-egg"></i>
                        <span>Free-range Eggs</span>
                    </div>
                    <div class="search-categories">
                        <span class="search-category">Fruits</span>
                        <span class="search-category">Vegetables</span>
                        <span class="search-category">Dairy</span>
                        <span class="search-category">Organic</span>
                        <span class="search-category">Local</span>
                    </div>
                </div>
            </form>

            <!-- Auth Buttons & User Dropdown -->
            <div class="navbar-auth-section">
                <?php if(!$is_logged_in): ?>
                    <!-- Show login/register buttons for guests ONLY -->
                    <div class="auth-buttons">
                        <a href="<?php echo $register_path; ?>" class="btn btn-success btn-sm">
                            <i class="fas fa-store me-1"></i> Become a Seller
                        </a>
                    </div>
                <?php else: ?>
                    <!-- Show user dropdown for logged-in users ONLY -->
                    <div class="dropdown">
                        <?php
                        // Safely get display name
                        $display_name = 'User';
                        if (isset($_SESSION['first_name']) && isset($_SESSION['last_name'])) {
                            $display_name = $_SESSION['first_name'] . ' ' . $_SESSION['last_name'];
                        } elseif (isset($_SESSION['user_name'])) {
                            $display_name = $_SESSION['user_name'];
                        } elseif (isset($user_data)) {
                            if (isset($user_data['first_name']) && isset($user_data['last_name'])) {
                                $display_name = $user_data['first_name'] . ' ' . $user_data['last_name'];
                                $_SESSION['first_name'] = $user_data['first_name'];
                                $_SESSION['last_name'] = $user_data['last_name'];
                            } elseif (isset($user_data['user_name'])) {
                                $display_name = $user_data['user_name'];
                                $_SESSION['user_name'] = $user_data['user_name'];
                            }
                        }
                        ?>
                        <button class="btn dropdown-toggle user-dropdown-btn" type="button" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-user-circle me-1"></i> <?php echo htmlspecialchars($display_name); ?>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">

                            <?php if ($user_role === 'farmer'): ?>
                                <!-- Farmer-specific menu -->
                                <li><a class="dropdown-item" href="<?php echo $profile_path; ?>"><i class="fas fa-user-circle me-2"></i>Profile</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="<?php echo $checkorders_path; ?>"><i class="fas fa-box me-2"></i>My Orders</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="<?php echo $farmer_dashboard_path; ?>"><i class="fas fa-tachometer-alt me-2"></i>Farmer Dashboard</a></li>
                                <li><hr class="dropdown-divider"></li>

                            <?php elseif ($user_role === 'admin'): ?>
                                <!-- Admin-specific menu -->
                                <li><a class="dropdown-item" href="<?php echo $profile_path; ?>"><i class="fas fa-user-circle me-2"></i>Profile</a></li>
                                <li><a class="dropdown-item" href="<?php echo $edit_profile_path; ?>"><i class="fas fa-edit me-2"></i>Edit Profile</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="<?php echo $checkorders_path; ?>"><i class="fas fa-box me-2"></i>My Orders</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="<?php echo $admin_dashboard_path; ?>"><i class="fas fa-cog me-2"></i>Admin Dashboard</a></li>
                                <li><hr class="dropdown-divider"></li>

                            <?php else: ?>
                                <!-- Customer menu (default) -->
                                <li><a class="dropdown-item" href="<?php echo $profile_path; ?>"><i class="fas fa-user-circle me-2"></i>Profile</a></li>
                                <li><a class="dropdown-item" href="<?php echo $edit_profile_path; ?>"><i class="fas fa-edit me-2"></i>Edit Profile</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="<?php echo $checkorders_path; ?>"><i class="fas fa-box me-2"></i>My Orders</a></li>
                                <li><hr class="dropdown-divider"></li>

                                <?php if ($can_create_store): ?>
                                    <li><a class="dropdown-item" href="<?php echo $setup_store_path; ?>"><i class="fas fa-store me-2"></i>Setup Your Store</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                <?php endif; ?>

                            <?php endif; ?>

                            <!-- Logout (common for all roles) -->
                            <!-- In navbar.php, update the logout link -->
<li>
    <?php
    // Get logout path from farmcart if available
    if (isset($farmcart) && method_exists($farmcart, 'get_logout_path')) {
        $logout_path = $farmcart->get_logout_path();
    } else {
        // Fallback to calculated path
        $logout_path = $logout_path; // Use your existing $logout_path variable
    }
    ?>
    <a class="dropdown-item text-danger" href="<?php echo $logout_path; ?>">
        <i class="fas fa-sign-out-alt me-2"></i>Logout
    </a>
</li>
                        </ul>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>

<script>
    // Navbar scroll effect
    window.addEventListener('scroll', function() {
        const navbar = document.querySelector('.navbar');
        if (window.scrollY > 50) {
            navbar.classList.add('scrolled');
        } else {
            navbar.classList.remove('scrolled');
        }
    });

    // Search functionality
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('searchInput');
        const searchForm = document.getElementById('searchForm');
        const searchSuggestions = document.getElementById('searchSuggestions');

        if (!searchInput || !searchForm || !searchSuggestions) return;

        // Show suggestions when input is focused
        searchInput.addEventListener('focus', function() {
            searchSuggestions.style.display = 'block';
        });

        // Hide suggestions when clicking outside
        document.addEventListener('click', function(e) {
            if (!searchForm.contains(e.target)) {
                searchSuggestions.style.display = 'none';
            }
        });

        // Handle search form submission
        searchForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const searchTerm = searchInput.value.trim();
            if (searchTerm) {
                performSearch(searchTerm);
            }
        });

        // Handle suggestion clicks
        document.querySelectorAll('.search-suggestion-item').forEach(item => {
            item.addEventListener('click', function() {
                searchInput.value = this.querySelector('span').textContent;
                performSearch(searchInput.value);
            });
        });

        // Handle category clicks
        document.querySelectorAll('.search-category').forEach(category => {
            category.addEventListener('click', function() {
                searchInput.value = this.textContent;
                performSearch(this.textContent);
            });
        });

        // Search function
        function performSearch(term) {
            console.log('Searching for:', term);
            searchSuggestions.style.display = 'none';
            // Get the products path from PHP
            const productsPath = '<?php echo $products_path; ?>';
            // Extract the filename without path for search parameter
            const fileName = productsPath.includes('/') ?
                productsPath.split('/').pop() : productsPath;
            window.location.href = fileName + '?search=' + encodeURIComponent(term);
        }

        // Real-time search suggestions
        searchInput.addEventListener('input', function() {
            const term = this.value.toLowerCase();
            if (term.length > 2) {
                // Implement AJAX search suggestions here if needed
                console.log('Fetching suggestions for:', term);
            }
        });
    });
</script>
