<?php
// Add this at the VERY TOP
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Define base URL - adjust according to your project structure
// If your project is in root, use '/'
// If your project is in a folder like 'websys', use '/websys/'
$base_url = '/websys/';

// Set ABSOLUTE paths from the root
$home_path = $base_url . 'index.php';
$customer_home_path = $base_url . 'Pages/customer/index.php';
$products_path = $base_url . 'Pages/customer/products.php';
$about_path = $base_url . 'Header/about.php';
$contact_path = $base_url . 'Header/contacts.php';
$logout_path = $base_url . 'Register/login.php?logout=1';
$register_path = $base_url . 'Register/index.php';
$farmer_dashboard_path = $base_url . 'Pages/farmer/index.php';
$admin_dashboard_path = $base_url . 'Pages/admin/index.php';
$profile_path = $base_url . 'Pages/customer/profile.php';
$edit_profile_path = $base_url . 'Pages/customer/edit-profile.php';
$orders_path = $base_url . 'Pages/customer/orders.php';
$wishlist_path = $base_url . 'Pages/customer/wishlist.php';
$setup_store_path = $base_url . 'Pages/customer/setUpStore.php';

// Check if user is logged in
$is_logged_in = isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);

// Smart home link: if logged in as customer, go to customer home, else go to public home
$smart_home_path = $home_path;
if ($is_logged_in && isset($_SESSION['user_role'])) {
    if ($_SESSION['user_role'] === 'customer') {
        $smart_home_path = $customer_home_path;
    } elseif ($_SESSION['user_role'] === 'farmer') {
        $smart_home_path = $farmer_dashboard_path;
    } elseif ($_SESSION['user_role'] === 'admin') {
        $smart_home_path = $admin_dashboard_path;
    }
}

// Check if user can create store (only for logged-in customers)
$can_create_store = false;
$user_role = null;
$user_data = null;

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

<style>
    /* Navigation Styles */
    nav {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        z-index: 50;
        transition: all 0.3s ease;
        background: transparent;
    }
    
    nav.scrolled {
        background: rgba(15, 46, 21, 0.95);
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }
    
    .nav-container {
        max-width: 1280px;
        margin: 0 auto;
        padding: 0 1.5rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
        height: 4rem;
    }
    
    .nav-brand {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        text-decoration: none;
    }
    
    .nav-brand img {
        height: 2.5rem;
        width: auto;
    }
    
    .nav-brand h1 {
        font-size: 1.25rem;
        font-weight: 700;
        color: white;
        margin: 0;
    }
    
    .nav-links {
        display: none;
        gap: 2rem;
        align-items: center;
    }
    
    .nav-links a {
        color: white;
        text-decoration: none;
        font-size: 0.875rem;
        font-weight: 500;
        transition: color 0.3s;
    }
    
    .nav-links a:hover {
        color: #DAE2CB;
    }
    
    .search-form {
        position: relative;
        display: flex;
        align-items: center;
        background: rgba(255,255,255,0.2);
        border: 1px solid rgba(255,255,255,0.3);
        border-radius: 0.5rem;
        padding: 0 0.5rem;
    }
    
    .search-input {
        background: transparent;
        border: none;
        color: white;
        padding: 0.5rem 0.75rem;
        font-size: 0.875rem;
        flex: 1;
        outline: none;
    }
    
    .search-input::placeholder {
        color: rgba(255,255,255,0.7);
    }
    
    .search-btn {
        background: none;
        border: none;
        color: white;
        cursor: pointer;
        padding: 0.5rem;
        transition: color 0.3s;
    }
    
    .search-btn:hover {
        color: #DAE2CB;
    }
    
    .search-suggestions {
        position: absolute;
        top: 100%;
        left: 0;
        right: 0;
        background: white;
        border: 1px solid #e5e7eb;
        border-radius: 0.5rem;
        margin-top: 0.5rem;
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        display: none;
        z-index: 100;
    }
    
    .search-suggestion-item {
        padding: 0.75rem 1rem;
        color: #0F2E15;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 0.75rem;
        transition: background-color 0.3s;
    }
    
    .search-suggestion-item:hover {
        background-color: #f3f4f6;
    }
    
    .search-categories {
        border-top: 1px solid #e5e7eb;
        padding: 0.5rem;
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
    }
    
    .search-category {
        background-color: #DAE2CB;
        color: #0F2E15;
        padding: 0.25rem 0.75rem;
        border-radius: 9999px;
        font-size: 0.75rem;
        cursor: pointer;
        transition: background-color 0.3s;
    }
    
    .search-category:hover {
        background-color: #0F2E15;
        color: white;
    }
    
    /* Dropdown Styles */
    .dropdown {
        position: relative;
        display: inline-block;
    }
    
    .user-dropdown-btn {
        background: rgba(255, 255, 255, 0.1);
        color: white;
        border: 1px solid rgba(255, 255, 255, 0.2);
        border-radius: 50px;
        padding: 0.5rem 1rem 0.5rem 0.75rem;
        font-size: 0.875rem;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        min-width: 140px;
        justify-content: center;
        backdrop-filter: blur(10px);
    }
    
    .user-dropdown-btn:hover {
        background: rgba(255, 255, 255, 0.15);
        border-color: rgba(255, 255, 255, 0.3);
        transform: translateY(-1px);
    }
    
    .user-dropdown-btn i {
        font-size: 1.25rem;
        transition: transform 0.3s ease;
    }
    
    .user-dropdown-btn.active i {
        transform: rotate(180deg);
    }
    
    .dropdown-menu {
        position: absolute;
        top: calc(100% + 0.5rem);
        right: 0;
        min-width: 220px;
        background: white;
        border-radius: 12px;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1), 0 2px 10px rgba(15, 46, 21, 0.05);
        border: 1px solid rgba(15, 46, 21, 0.1);
        padding: 0.5rem;
        opacity: 0;
        transform: translateY(-10px);
        visibility: hidden;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        z-index: 1000;
    }
    
    .dropdown-menu.show {
        opacity: 1;
        transform: translateY(0);
        visibility: visible;
    }
    
    .dropdown-menu::before {
        content: '';
        position: absolute;
        top: -6px;
        right: 20px;
        width: 12px;
        height: 12px;
        background: white;
        transform: rotate(45deg);
        border-left: 1px solid rgba(15, 46, 21, 0.1);
        border-top: 1px solid rgba(15, 46, 21, 0.1);
        z-index: -1;
    }
    
    .dropdown-item {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 0.75rem 1rem;
        color: #374151;
        text-decoration: none;
        font-size: 0.875rem;
        font-weight: 500;
        border-radius: 8px;
        transition: all 0.2s ease;
        cursor: pointer;
        margin: 0.125rem 0;
    }
    
    .dropdown-item:hover {
        background: linear-gradient(135deg, #f0f7ee 0%, #e8f0e5 100%);
        color: #0F2E15;
        transform: translateX(2px);
    }
    
    .dropdown-item i {
        font-size: 1.125rem;
        color: #4b5563;
        transition: color 0.2s ease;
        width: 20px;
        text-align: center;
    }
    
    .dropdown-item:hover i {
        color: #0F2E15;
    }
    
    .dropdown-divider {
        height: 1px;
        background: linear-gradient(to right, transparent, rgba(15, 46, 21, 0.1), transparent);
        margin: 0.5rem 0.75rem;
    }
    
    .dropdown-header {
        padding: 0.75rem 1rem;
        font-size: 0.75rem;
        color: #6b7280;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        border-bottom: 1px solid rgba(15, 46, 21, 0.1);
        margin-bottom: 0.5rem;
    }
    
    /* Badge for notifications */
    .dropdown-badge {
        margin-left: auto;
        background: #dc2626;
        color: white;
        font-size: 0.75rem;
        padding: 0.125rem 0.5rem;
        border-radius: 9999px;
        font-weight: 600;
    }
    
    /* Profile info section */
    .dropdown-profile {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 1rem;
        border-bottom: 1px solid rgba(15, 46, 21, 0.1);
        margin-bottom: 0.5rem;
    }
    
    .dropdown-profile-avatar {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: linear-gradient(135deg, #0F2E15, #2d5a3a);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-weight: 600;
        font-size: 1rem;
    }
    
    .dropdown-profile-info {
        flex: 1;
    }
    
    .dropdown-profile-name {
        font-weight: 600;
        color: #0F2E15;
        font-size: 0.875rem;
    }
    
    .dropdown-profile-role {
        font-size: 0.75rem;
        color: #6b7280;
        text-transform: capitalize;
    }
    
    /* Additional styles for dropdown */
    .truncate {
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        max-width: 100px;
    }
    
    .logout-item {
        color: #dc2626 !important;
    }
    
    .logout-item:hover {
        background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%) !important;
        color: #b91c1c !important;
    }
    
    .logout-item i {
        color: #dc2626 !important;
    }
    
    /* Animation for dropdown items */
    @keyframes slideIn {
        from {
            opacity: 0;
            transform: translateX(-10px);
        }
        to {
            opacity: 1;
            transform: translateX(0);
        }
    }
    
    .dropdown-menu.show .dropdown-item {
        animation: slideIn 0.3s ease forwards;
    }
    
    .dropdown-menu.show .dropdown-item:nth-child(1) { animation-delay: 0.1s; }
    .dropdown-menu.show .dropdown-item:nth-child(2) { animation-delay: 0.15s; }
    .dropdown-menu.show .dropdown-item:nth-child(3) { animation-delay: 0.2s; }
    .dropdown-menu.show .dropdown-item:nth-child(4) { animation-delay: 0.25s; }
    .dropdown-menu.show .dropdown-item:nth-child(5) { animation-delay: 0.3s; }
    
    @media (min-width: 768px) {
        .nav-links {
            display: flex;
        }
        .nav-menu-toggle {
            display: none;
        }
    }
    
    @media (max-width: 767px) {
        .nav-links {
            display: none;
        }
        .search-form {
            display: none;
        }
        .nav-menu-toggle {
            display: block;
        }
    }
    
    /* Mobile responsive adjustments for dropdown */
    @media (max-width: 640px) {
        .user-dropdown-btn span:not(.ri-arrow-down-s-line) {
            display: none;
        }
        
        .user-dropdown-btn {
            min-width: auto;
            padding: 0.5rem;
            border-radius: 50%;
            width: 40px;
            height: 40px;
        }
        
        .dropdown-menu {
            position: fixed;
            top: auto;
            bottom: 0;
            left: 0;
            right: 0;
            min-width: 100%;
            border-radius: 20px 20px 0 0;
            transform: translateY(100%);
            max-height: 70vh;
            overflow-y: auto;
        }
        
        .dropdown-menu.show {
            transform: translateY(0);
        }
        
        .dropdown-menu::before {
            display: none;
        }
    }
</style>

    <!-- Navigation -->
    <nav>
        <div class="nav-container">
            <a href="<?php echo $smart_home_path; ?>" class="nav-brand">
                <img src="https://static.readdy.ai/image/4ca41f25234899b8f8c841da212115f9/0edf46ff3dcee02b8c0543247a6a8d9c.png" alt="Farm Fresh Market">
                <h1>Farm Fresh Market</h1>
            </a>
            
            <div class="nav-links">
                <a href="<?php echo $smart_home_path; ?>">Home</a>
                <a href="<?php echo $products_path; ?>">Products</a>
                <a href="<?php echo $about_path; ?>">About</a>
                <a href="<?php echo $contact_path; ?>">Contact</a>
            </div>
            
            <form class="search-form" id="searchForm">
                <input type="text" class="search-input" placeholder="Search products..." id="searchInput">
                <button type="submit" class="search-btn">
                    <i class="ri-search-line"></i>
                </button>
                <div class="search-suggestions" id="searchSuggestions">
                    <!-- Search suggestions remain the same -->
                </div>
            </form>
            
            <div style="display: flex; gap: 1rem; align-items: center;">
                <?php if (!$is_logged_in): ?>
                    <a href="<?php echo $register_path; ?>" style="background-color: #0F2E15; color: white; border: none; padding: 0.5rem 1rem; border-radius: 0.375rem; cursor: pointer; font-weight: 600; transition: background-color 0.3s; text-decoration: none;">Sign In</a>
                <?php else: ?>
                    <div class="dropdown">
                        <?php
                        // Ensure we have user data when logged in
                        if ($is_logged_in && !isset($user_data) && isset($farmcart)) {
                            $user_data = $farmcart->get_user($_SESSION['user_id']);
                        }

                        // Compute a friendly display name for the dropdown
                        $display_name = 'User';
                        $user_initials = 'U';

                        if (isset($user_data) && is_array($user_data)) {
                            if (!empty($user_data['first_name']) || !empty($user_data['last_name'])) {
                                $display_name = trim(($user_data['first_name'] ?? '') . ' ' . ($user_data['last_name'] ?? ''));
                                // Get initials for avatar
                                $first_initial = !empty($user_data['first_name']) ? substr($user_data['first_name'], 0, 1) : '';
                                $last_initial = !empty($user_data['last_name']) ? substr($user_data['last_name'], 0, 1) : '';
                                $user_initials = strtoupper($first_initial . $last_initial);
                            } elseif (!empty($user_data['user_name'])) {
                                $display_name = $user_data['user_name'];
                                $user_initials = strtoupper(substr($user_data['user_name'], 0, 2));
                            } elseif (!empty($user_data['email'])) {
                                $display_name = $user_data['email'];
                                $user_initials = strtoupper(substr($user_data['email'], 0, 1));
                            }
                        }
                        ?>
                        
                        <button class="user-dropdown-btn" type="button" id="userDropdown" onclick="toggleDropdown()">
                            <i class="ri-user-circle-line"></i> 
                            <span class="truncate"><?php echo htmlspecialchars($display_name); ?></span>
                            <i class="ri-arrow-down-s-line ml-auto" style="margin-left: auto;"></i>
                        </button>
                        
                        <div class="dropdown-menu" id="dropdownMenu">
                            <!-- Profile Info -->
                            <div class="dropdown-profile">
                                <div class="dropdown-profile-avatar">
                                    <?php echo $user_initials; ?>
                                </div>
                                <div class="dropdown-profile-info">
                                    <div class="dropdown-profile-name truncate"><?php echo htmlspecialchars($display_name); ?></div>
                                    <div class="dropdown-profile-role"><?php echo ucfirst($user_role ?? 'user'); ?></div>
                                </div>
                            </div>
                            
                            <!-- Navigation Links -->
                            <?php if ($user_role === 'farmer'): ?>
                                <div class="dropdown-header">Farmer Dashboard</div>
                                <a href="<?php echo $farmer_dashboard_path; ?>" class="dropdown-item">
                                    <i class="ri-dashboard-line"></i> 
                                    <span>Dashboard</span>
                                </a>
                                <a href="<?php echo $profile_path; ?>" class="dropdown-item">
                                    <i class="ri-user-line"></i> 
                                    <span>Profile</span>
                                </a>
                                <a href="<?php echo $orders_path; ?>" class="dropdown-item">
                                    <i class="ri-shopping-cart-line"></i> 
                                    <span>Orders</span>
                                </a>
                                
                            <?php elseif ($user_role === 'admin'): ?>
                                <div class="dropdown-header">Admin Panel</div>
                                <a href="<?php echo $admin_dashboard_path; ?>" class="dropdown-item">
                                    <i class="ri-dashboard-line"></i> 
                                    <span>Admin Dashboard</span>
                                </a>
                                <a href="<?php echo $profile_path; ?>" class="dropdown-item">
                                    <i class="ri-user-line"></i> 
                                    <span>Profile</span>
                                </a>
                                <a href="<?php echo $edit_profile_path; ?>" class="dropdown-item">
                                    <i class="ri-edit-line"></i> 
                                    <span>Edit Profile</span>
                                </a>
                                
                            <?php else: ?>
                                <div class="dropdown-header">My Account</div>
                                <a href="<?php echo $profile_path; ?>" class="dropdown-item">
                                    <i class="ri-user-line"></i> 
                                    <span>Profile</span>
                                </a>
                                <a href="<?php echo $edit_profile_path; ?>" class="dropdown-item">
                                    <i class="ri-edit-line"></i> 
                                    <span>Edit Profile</span>
                                </a>
                                <a href="<?php echo $orders_path; ?>" class="dropdown-item">
                                    <i class="ri-shopping-cart-line"></i> 
                                    <span>My Orders</span>
                                </a>
                                <?php if ($can_create_store): ?>
                                    <a href="<?php echo $setup_store_path; ?>" class="dropdown-item">
                                        <i class="ri-store-2-line"></i> 
                                        <span>Setup Your Store</span>
                                        <i class="ri-sparkling-line" style="color: #f59e0b;"></i>
                                    </a>
                                <?php endif; ?>
                            <?php endif; ?>
                            
                            <div class="dropdown-divider"></div>
                            
                            <!-- Logout -->
                            <a href="<?php echo $logout_path; ?>" class="dropdown-item logout-item">
                                <i class="ri-logout-circle-line"></i> 
                                <span>Logout</span>
                            </a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            
            <button class="nav-menu-toggle" onclick="toggleMenu()">
                <i class="ri-menu-line"></i>
            </button>
        </div>
    </nav>

    <script>
        // Your JavaScript remains the same
        function toggleDropdown() {
            const menu = document.getElementById('dropdownMenu');
            const button = document.getElementById('userDropdown');
            
            if (menu) {
                menu.classList.toggle('show');
                button.classList.toggle('active');
            }
        }
        
        function toggleMenu() {
            const links = document.querySelector('.nav-links');
            const searchForm = document.querySelector('.search-form');
            if (links) {
                links.style.display = links.style.display === 'none' ? 'flex' : 'none';
            }
            if (searchForm) {
                searchForm.style.display = searchForm.style.display === 'none' ? 'block' : 'none';
            }
        }
        
        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            const dropdown = document.querySelector('.dropdown');
            const menu = document.getElementById('dropdownMenu');
            const button = document.getElementById('userDropdown');
            
            if (dropdown && !dropdown.contains(e.target)) {
                if (menu) {
                    menu.classList.remove('show');
                }
                if (button) {
                    button.classList.remove('active');
                }
            }
        });
        
        // Close dropdown on escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                const menu = document.getElementById('dropdownMenu');
                const button = document.getElementById('userDropdown');
                
                if (menu) {
                    menu.classList.remove('show');
                }
                if (button) {
                    button.classList.remove('active');
                }
            }
        });
        
        // Update nav background on scroll
        window.addEventListener('scroll', function() {
            const nav = document.querySelector('nav');
            if (window.scrollY > 100) {
                nav.classList.add('scrolled');
            } else {
                nav.classList.remove('scrolled');
            }
        });
        
        // Search functionality - FIXED for absolute paths
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

            // Search function - FIXED with proper path
            function performSearch(term) {
                console.log('Searching for:', term);
                searchSuggestions.style.display = 'none';
                // Get the products path from PHP
                const productsPath = '<?php echo $products_path; ?>';
                // Append search parameter correctly
                const separator = productsPath.includes('?') ? '&' : '?';
                window.location.href = productsPath + separator + 'search=' + encodeURIComponent(term);
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

        // Smooth scroll for navigation links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                const href = this.getAttribute('href');
                if (href.startsWith('#')) {
                    e.preventDefault();
                    const target = document.querySelector(href);
                    if (target) {
                        target.scrollIntoView({ behavior: 'smooth' });
                    }
                }
            });
        });
    </script>