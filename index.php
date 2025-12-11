<?php
include 'db_connect.php';

// Handle logout request
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: index.php");
    exit();
}

// Initialize variables
$can_create_store = false;
$store_status = null;
$user_role = 'guest'; // Default role for non-logged-in users

// Check if user is logged in to show appropriate content
if (isset($_SESSION['user_id'])) {
    $can_create_store = $farmcart->canBecomeFarmer($_SESSION['user_id']);
    $store_status = $farmcart->get_user($_SESSION['user_id']);
    $user_role = $_SESSION['user_role'];
}

// Handle store creation (only if logged in)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_store'])) {
    if (!isset($_SESSION['user_id'])) {
        $error = "Please log in to create a store";
    } else {
        $result = $farmcart->setupFarmerStore(
            $_SESSION['user_id'],
            $_POST['store_name'],
            $_POST['store_address'],
            $_POST['farm_size'],
            $_POST['farming_method'],
            $_POST['years_experience'],
            $_POST['certification_details'],
            $_POST['bio']
        );

        if ($result['success']) {
            $success = $result['message'];
            header("Location: ../../farmer/index.php");
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
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FarmCart - Fresh Farm Products</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Navbar CSS -->
    <link rel="stylesheet" href="Assets/css/navbar.css">
    <link rel="stylesheet" href="Assets/css/customer.css">
</head>
<body>
    <!-- Include Navbar -->
    <?php include 'Includes/navbar.php'; ?>

    <!-- Store Creation Modal (only show if user is logged in as customer) -->
    <?php if (isset($_SESSION['user_id']) && $user_role === 'customer' && $can_create_store): ?>
    <div class="modal fade" id="storeModal" tabindex="-1" aria-labelledby="storeModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="storeModalLabel">Setup Your Farm Store</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                    <?php endif; ?>

                    <form method="post" id="storeForm">
                        <!-- Farm Name -->
                        <div class="mb-3">
                            <label for="store_name" class="form-label">Farm Name *</label>
                            <input type="text" class="form-control" id="store_name" name="store_name" required>
                        </div>

                        <!-- Farm Location -->
                        <div class="mb-3">
                            <label for="store_address" class="form-label">Farm Location *</label>
                            <textarea class="form-control" id="store_address" name="store_address" rows="2" required></textarea>
                        </div>

                        <!-- Farm Size -->
                        <div class="mb-3">
                            <label for="farm_size" class="form-label">Farm Size (hectares)</label>
                            <input type="number" class="form-control" id="farm_size" name="farm_size" min="0">
                        </div>

                        <!-- Farming Method -->
                        <div class="mb-3">
                            <label for="farming_method" class="form-label">Farming Method</label>
                            <select class="form-control" id="farming_method" name="farming_method">
                                <option value="Organic">Organic</option>
                                <option value="Conventional">Conventional</option>
                                <option value="Hydroponic">Hydroponic</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>

                        <!-- Years of Experience -->
                        <div class="mb-3">
                            <label for="years_experience" class="form-label">Years of Experience</label>
                            <input type="number" class="form-control" id="years_experience" name="years_experience" min="0">
                        </div>

                        <!-- Certification Details -->
                        <div class="mb-3">
                            <label for="certification_details" class="form-label">Certification Details</label>
                            <textarea class="form-control" id="certification_details" name="certification_details" rows="2"></textarea>
                        </div>

                        <!-- Farmer Bio -->
                        <div class="mb-3">
                            <label for="bio" class="form-label">Farmer Bio</label>
                            <textarea class="form-control" id="bio" name="bio" rows="3"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" form="storeForm" name="create_store" class="btn btn-primary">Create Store</button>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Enhanced Hero Section -->
    <section class="hero-section">
        <div class="hero-background">
            <div class="hero-pattern"></div>
        </div>
        <div class="container">
            <div class="hero-content">
                <div class="hero-badge">Fresh From Farm</div>
                <h1 class="hero-title">Fresh Vegetables & Livestock</h1>
                <p class="hero-subtitle">Direct from local farms to your doorstep. Fresh, organic, and humanely raised with care.</p>
                <div class="hero-actions">
                    <button class="btn btn-primary hero-btn" onclick="scrollToCategories()">
                        <i class="fas fa-shopping-basket me-2"></i>
                        Shop Now
                    </button>

                    <!-- Dynamic Button based on login status -->
                    <?php if (!isset($_SESSION['user_id'])): ?>
                        <!-- Show login/signup buttons for guests -->
                        <a href="Register/login.php" class="btn btn-success hero-btn">
                            <i class="fas fa-sign-in-alt me-2"></i>
                            Login to Shop
                        </a>
                        <a href="Register/index.php" class="btn btn-outline-light hero-btn">
                            <i class="fas fa-user-plus me-2"></i>
                            Sign Up
                        </a>
                    <?php elseif ($user_role === 'customer'): ?>
                        <!-- Show store setup for logged-in customers -->
                        <button class="btn btn-success hero-btn" data-bs-toggle="modal" data-bs-target="#storeModal">
                            <i class="fas fa-store me-2"></i>
                            Set Up Your Store
                        </button>
                    <?php endif; ?>
                </div>
                <div class="hero-stats">
                    <div class="stat-item">
                        <div class="stat-number">500+</div>
                        <div class="stat-label">Happy Customers</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number">50+</div>
                        <div class="stat-label">Local Farms</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number">24/7</div>
                        <div class="stat-label">Fresh Delivery</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="hero-scroll-indicator">
            <i class="fas fa-chevron-down"></i>
        </div>
    </section>

    <!-- Enhanced Categories Section -->
    <section class="categories-section" id="categories">
        <div class="container">
            <div class="section-header text-center">
                <h2 class="section-title">Our Farm Categories</h2>
                <p class="section-subtitle">Discover fresh produce from our trusted local farmers</p>
            </div>

            <div class="categories-grid">
                <div class="category-card" onclick="showCategory('vegetables')">
                    <div class="category-icon-wrapper">
                        <span class="category-icon">🥦</span>
                    </div>
                    <h3 class="category-title">Fresh Vegetables</h3>
                    <p class="category-description">Seasonal organic vegetables from local farms</p>
                    <div class="category-badge">Popular</div>
                </div>

                <div class="category-card" onclick="showCategory('fruits')">
                    <div class="category-icon-wrapper">
                        <span class="category-icon">🍎</span>
                    </div>
                    <h3 class="category-title">Fresh Fruits</h3>
                    <p class="category-description">Sweet and juicy seasonal fruits</p>
                </div>

                <div class="category-card" onclick="showCategory('eggs')">
                    <div class="category-icon-wrapper">
                        <span class="category-icon">🥚</span>
                    </div>
                    <h3 class="category-title">Farm Eggs</h3>
                    <p class="category-description">Free-range and organic eggs</p>
                </div>

                <div class="category-card" onclick="showCategory('artisan')">
                    <div class="category-icon-wrapper">
                        <span class="category-icon">🧀</span>
                    </div>
                    <h3 class="category-title">Artisan Products</h3>
                    <p class="category-description">Local handmade dairy and more</p>
                </div>

                <div class="category-card" onclick="showCategory('livestock')">
                    <div class="category-icon-wrapper">
                        <span class="category-icon">🐄</span>
                    </div>
                    <h3 class="category-title">Livestock</h3>
                    <p class="category-description">Humanely raised meat products</p>
                    <div class="category-badge">Best Seller</div>
                </div>

                <div class="category-card" onclick="showCategory('honey')">
                    <div class="category-icon-wrapper">
                        <span class="category-icon">🍯</span>
                    </div>
                    <h3 class="category-title">Pure Honey</h3>
                    <p class="category-description">100% natural raw honey</p>
                </div>

                <div class="category-card" onclick="showCategory('fish')">
                    <div class="category-icon-wrapper">
                        <span class="category-icon">🎣</span>
                    </div>
                    <h3 class="category-title">Fresh Fish</h3>
                    <p class="category-description">Local catch delivered fresh</p>
                    <div class="category-badge">New</div>
                </div>

                <div class="category-card view-all-card" onclick="showAllCategories()">
                    <div class="category-icon-wrapper">
                        <span class="category-icon">📦</span>
                    </div>
                    <h3 class="category-title">View All</h3>
                    <p class="category-description">Explore all categories</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Premium Livestock Section -->
    <section class="premium-section">
        <div class="container">
            <div class="section-header text-center">
                <h2 class="section-title">Premium Livestock</h2>
                <p class="section-subtitle">Humanely raised with care on local family farms</p>
            </div>

            <div class="products-grid">
                <?php
                // Sample products data
                $products = [
                    [
                        'icon' => '🐖',
                        'title' => 'Pasture-Raised Pork',
                        'description' => 'Humanely raised pork with no antibiotics or hormones. Available as cuts or whole/half animals.',
                        'price' => '200',
                        'badge' => 'Most Popular',
                        'tags' => ['Local Farm', 'Free Range']
                    ],
                    [
                        'icon' => '🐐',
                        'title' => 'Fresh Goat Meat',
                        'description' => 'Tender goat meat raised on natural diet, perfect for traditional dishes.',
                        'price' => '350',
                        'badge' => '',
                        'tags' => ['Local Farm', 'Natural Diet']
                    ],
                    [
                        'icon' => '🐔',
                        'title' => 'Free-Range Chicken',
                        'description' => 'Pasture-raised chickens with access to outdoors and natural diet.',
                        'price' => '250',
                        'badge' => 'Free Range',
                        'tags' => ['No Antibiotics', 'Free Range']
                    ],
                    [
                        'icon' => '🐄',
                        'title' => 'Grass-Fed Beef',
                        'description' => 'Grass-fed beef raised on local family farms, fed on open pasture.',
                        'price' => '450',
                        'badge' => '',
                        'tags' => ['Grass Fed', 'Local Farm']
                    ]
                ];

                foreach ($products as $product): 
                ?>
                <div class="product-card premium">
                    <?php if ($product['badge']): ?>
                        <div class="product-badge"><?php echo $product['badge']; ?></div>
                    <?php endif; ?>
                    <div class="product-image">
                        <span class="product-icon"><?php echo $product['icon']; ?></span>
                    </div>
                    <div class="product-content">
                        <h3 class="product-title"><?php echo $product['title']; ?></h3>
                        <p class="product-description"><?php echo $product['description']; ?></p>
                        <div class="product-price">
                            <span class="price-main">₱<?php echo $product['price']; ?></span>
                            <span class="price-unit">/kg</span>
                        </div>
                        <div class="product-tags">
                            <?php foreach ($product['tags'] as $tag): ?>
                                <span class="product-tag"><?php echo $tag; ?></span>
                            <?php endforeach; ?>
                        </div>
                        <button class="btn-add-to-cart" onclick="handleAddToCart('<?php echo $product['title']; ?>')">
                            <i class="fas fa-cart-plus"></i>
                            <?php echo isset($_SESSION['user_id']) ? 'Add to Cart' : 'View Details'; ?>
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Call to Action for Non-Logged-In Users -->
            <?php if (!isset($_SESSION['user_id'])): ?>
            <div class="text-center mt-5">
                <div class="cta-box">
                    <h3>Ready to Shop?</h3>
                    <p>Create an account to start ordering fresh farm products</p>
                    <div class="cta-actions">
                        <a href="Register/index.php" class="btn btn-primary btn-lg">
                            <i class="fas fa-user-plus me-2"></i>
                            Sign Up Now
                        </a>
                        <a href="Register/login.php" class="btn btn-outline-primary btn-lg">
                            <i class="fas fa-sign-in-alt me-2"></i>
                            Login
                        </a>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Seasonal Harvest Section -->
    <section class="seasonal-section">
        <div class="container">
            <div class="section-header text-center">
                <h2 class="section-title">Seasonal Harvest Calendar</h2>
                <p class="section-subtitle">Enjoy produce at its peak flavor and nutritional value</p>
            </div>

            <div class="seasonal-grid">
                <!-- Seasonal cards remain the same -->
                <div class="season-card current">
                    <div class="season-header">
                        <h3 class="season-name">January</h3>
                        <div class="season-badge">Current</div>
                    </div>
                    <div class="season-produce">
                        <div class="produce-item">
                            <span class="produce-icon">🍊</span>
                            <span class="produce-name">Citrus Fruits</span>
                        </div>
                        <div class="produce-item">
                            <span class="produce-icon">🥬</span>
                            <span class="produce-name">Leafy Greens</span>
                        </div>
                        <div class="produce-item">
                            <span class="produce-icon">🥕</span>
                            <span class="produce-name">Root Vegetables</span>
                        </div>
                    </div>
                </div>

                <!-- ... other seasonal cards ... -->
            </div>
        </div>
    </section>

    <!-- Newsletter Section -->
    <section class="newsletter-section">
        <div class="container">
            <div class="newsletter-content">
                <h2>Stay Updated with Fresh Harvests</h2>
                <p>Get notified about seasonal offers and new arrivals</p>
                <form class="newsletter-form">
                    <div class="input-group">
                        <input type="email" placeholder="Enter your email" class="form-control" required>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-paper-plane me-2"></i>
                            Subscribe
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </section>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Auto-show store modal only if user is eligible and logged in
        document.addEventListener('DOMContentLoaded', function() {
            <?php if (isset($_SESSION['user_id']) && $user_role === 'customer' && $can_create_store): ?>
            var storeModal = new bootstrap.Modal(document.getElementById('storeModal'));
            storeModal.show();
            <?php endif; ?>
        });

        function scrollToCategories() {
            document.getElementById('categories').scrollIntoView({ 
                behavior: 'smooth' 
            });
        }

        function handleAddToCart(productName) {
            <?php if (!isset($_SESSION['user_id'])): ?>
                // Redirect to login if not logged in
                window.location.href = '../auth/login.php?redirect=' + encodeURIComponent(window.location.pathname);
            <?php else: ?>
                // Add to cart logic for logged-in users
                console.log('Adding to cart:', productName);
                // Implement your add to cart functionality here
            <?php endif; ?>
        }

        function showCategory(category) {
            <?php if (!isset($_SESSION['user_id'])): ?>
                window.location.href = '../auth/login.php?category=' + category;
            <?php else: ?>
                // Navigate to category for logged-in users
                console.log('Showing category:', category);
            <?php endif; ?>
        }

        function showAllCategories() {
            <?php if (!isset($_SESSION['user_id'])): ?>
                window.location.href = '../auth/login.php';
            <?php else: ?>
                // Show all categories for logged-in users
                console.log('Showing all categories');
            <?php endif; ?>
        }
    </script>
</body>
</html>
