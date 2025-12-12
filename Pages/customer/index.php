<?php
include '../../db_connect.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("location: ../index.php");
    exit();
}

$can_create_store = $farmcart->canBecomeFarmer($_SESSION['user_id']);
$store_status = $farmcart->get_user($_SESSION['user_id']);

// Handle store creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_store'])) {
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

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header("location: ../Register/login.php");
    exit();
}

// Fetch categories for display
$categories = [];
$categories_query = "SELECT * FROM categories WHERE is_active = 1 ORDER BY category_name LIMIT 4";
$categories_result = $farmcart->conn->query($categories_query);
if ($categories_result && $categories_result->num_rows > 0) {
    while ($row = $categories_result->fetch_assoc()) {
        $categories[] = $row;
    }
}

// Fetch new approved products (latest 3)
$new_products = [];
$new_products_query = "SELECT
                    p.product_id,
                    p.product_name,
                    p.description,
                    p.category_id,
                    p.unit_type,
                    p.base_price,
                    p.quantity,
                    p.expires_at,
                    p.approval_status,
                    p.created_at,
                    c.category_name,
                    c.category_type,
                    pi.image_url,
                    u.first_name as farmer_first,
                    u.last_name as farmer_last,
                    fp.farm_name
                 FROM products p
                 LEFT JOIN categories c ON p.category_id = c.category_id
                 LEFT JOIN product_images pi ON p.product_id = pi.product_id AND pi.is_primary = TRUE
                 LEFT JOIN users u ON p.created_by = u.user_id
                 LEFT JOIN farmer_profiles fp ON p.created_by = fp.user_id
                 WHERE p.approval_status = 'approved'
                   AND p.is_listed = TRUE
                   AND (p.is_expired IS NULL OR p.is_expired = 0)
                   AND (p.expires_at IS NULL OR p.expires_at > NOW())
                 ORDER BY p.created_at DESC
                 LIMIT 3";
$new_products_result = $farmcart->conn->query($new_products_query);
if ($new_products_result && $new_products_result->num_rows > 0) {
    while ($row = $new_products_result->fetch_assoc()) {
        $new_products[] = $row;
    }
}

// Define image paths for products
$product_images = [
    'pork' => '../../Assets/images/products/pork.jpg',
    'goat' => '../../Assets/images/products/goat-meat.jpg',
    'chicken' => '../../Assets/images/products/chicken.jpg',
    'beef' => '../../Assets/images/products/beef.jpg'
];


$pageTitle = "Farm Fresh Market - Premium Local Produce, Livestock & Seasonal Harvests";
$currentYear = date("Y");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <meta name="description" content="Discover premium quality produce, livestock, and seasonal harvests from local farms. Fresh fish, fruits, vegetables, grass-fed beef, free-range chicken, and more delivered from farm to your table.">
    <meta name="keywords" content="farm fresh, local produce, organic vegetables, grass-fed beef, free-range chicken, seasonal harvest, fresh fish, farm market">
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- Remix Icon -->
    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    
<link rel="stylesheet" href="../../Assets/css/style-index.css">
</head>
<body>
    <div class="min-h-screen" style="font-family: 'Inter', sans-serif;">
        <!-- Include Navbar -->
        <?php include '../../Includes/navbar.php'; ?>

    <!-- Hero Section -->
    <section id="home" class="hero">
        <div class="hero-content">
            <h2>Fresh from Farm<span class="highlight">to Your Table</span></h2>
            <p>Discover premium quality produce, livestock, and seasonal harvests from local farms</p>
            <div class="hero-buttons">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="Pages/customer/products.php" class="btn btn-primary">Shop Now</a>
                <?php else: ?>
                    <a href="Register/index.php" class="btn btn-primary">Shop Now</a>
                <?php endif; ?>
                <!-- Become a Farmer Button -->
                <?php if ($_SESSION['user_role'] === 'customer'): ?>
                    <a href="setUpStore.php" class="bg-[#0F2E15] text-white font-semibold py-3 px-6 rounded-lg hover:bg-[#1B4D2E] transition-colors duration-300 text-decoration-none inline-flex items-center justify-center">
                    <i class="fas fa-store me-2"></i>
                        Set Up Your Store
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </section>

        <!-- Main Content -->
            <div class="min-h-screen" style="background-color: rgb(218, 226, 203);">
                
                  <!-- Featured Section -->
    <section id="featured" class="featured-section">
        <div class="container">
            <div class="section-header">
                <h2>Featured This Week</h2>
                <p>Discover our handpicked selections and weekly specials</p>
            </div>

            <div class="carousel">
                <div class="carousel-inner">
                    <div class="carousel-slide">
                        <div class="slide-content">
                            <h3>Farm Fresh Organic Produce</h3>
                            <p>Hand-picked daily from our certified organic farms. Experience the difference of truly fresh vegetables and fruits.</p>
                            <a href="Pages/customer/products.php" class="btn btn-primary" style="width: fit-content;">Shop Produce</a>
                        </div>
                        <div class="slide-image">
                            <img src="https://readdy.ai/api/search-image?query=Beautiful%20organic%20farm%20produce%20display%20with%20colorful%20vegetables%20and%20fruits%20arranged%20artistically%2C%20professional%20food%20photography%2C%20vibrant%20natural%20colors%2C%20clean%20white%20background%2C%20premium%20quality%20presentation&width=800&height=450&seq=carousel001&orientation=landscape" alt="Organic Produce">
                        </div>
                    </div>
                </div>
                
                <div class="carousel-controls">
                    <div class="carousel-dot active"></div>
                    <div class="carousel-dot"></div>
                    <div class="carousel-dot"></div>
                </div>
            </div>
        </div>
    </section>

<!-- Categories Section -->
    <section id="categories" class="categories-section">
        <div class="container">
            <div class="section-header">
                <h2>Our Categories</h2>
                <p>Explore our wide range of fresh, locally-sourced products carefully selected for quality and taste</p>
            </div>

            <div class="categories-grid">
                <?php if (!empty($categories)): ?>
                    <?php if (!empty($categories)): ?>
                        <?php foreach (array_slice($categories, 0, 4) as $category): ?>
                        <div class="category-card" onclick="window.location='products.php?category=<?php echo urlencode($category['category_type']); ?>'">
                            <img src="<?php echo htmlspecialchars($category['image_url'] ?? 'https://readdy.ai/api/search-image?query=Fresh%20produce%20category&width=400&height=300'); ?>" alt="<?php echo htmlspecialchars($category['category_name']); ?>" class="category-image">
                            <div class="category-content">
                                <h3><?php echo htmlspecialchars($category['category_name']); ?></h3>
                                <p><?php echo htmlspecialchars($category['description'] ?? 'Fresh from local farms'); ?></p>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div style="grid-column: 1/-1; text-align: center; padding: 2rem;">
                            <p>Categories coming soon!</p>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div style="grid-column: 1/-1; text-align: center; padding: 2rem;">
                        <p>Categories coming soon!</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- New Products Section -->
    <section id="products" class="products-section" style="background-color: rgba(255,255,255,0.5);">
        <div class="container">
            <div class="section-header">
                <h2>New Products</h2>
                <p>Check out our latest arrivals and seasonal specialties</p>
            </div>

            <div class="products-grid">
                <?php if (!empty($new_products)): ?>
                    <?php foreach ($new_products as $product): ?>
                        <?php
                        $image_url = !empty($product['image_url']) ? $product['image_url'] : '';
                        if (!empty($image_url) && !preg_match('/^https?:\/\//', $image_url)) {
                            if (strpos($image_url, 'uploads/') === 0) {
                                $image_url = '../farmer/' . $image_url;
                            }
                        }
                        if (empty($image_url)) {
                            $image_url = 'https://via.placeholder.com/400x350?text=No+Image';
                        }
                        ?>
                        <div class="product-card">
                            <img src="<?= htmlspecialchars($image_url); ?>" alt="<?= htmlspecialchars($product['product_name']); ?>" class="product-image" onerror="this.src='https://via.placeholder.com/400x350?text=No+Image'">
                            <div class="product-content">
                                <h3><?= htmlspecialchars($product['product_name']); ?></h3>
                                <p><?= htmlspecialchars(substr($product['description'] ?? 'Fresh from local farms', 0, 60)) . (strlen($product['description'] ?? '') > 60 ? '...' : ''); ?></p>
                                <div class="product-price">₱<?= number_format($product['base_price'], 2); ?>/<?= htmlspecialchars($product['unit_type']); ?></div>
                                <button class="btn-add-to-cart" onclick="window.location='products.php?product_id=<?= $product['product_id']; ?>'">View Product</button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div style="grid-column: 1/-1; text-align: center; padding: 2rem;">
                        <p>No new products available at the moment. Check back soon!</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

              <!-- Livestock Section -->
    <section id="livestock" class="livestock-section">
        <div class="container">
            <div class="section-header">
                <h2>Livestock</h2>
                <p>Ethically raised, grass-fed, and hormone-free meat from our trusted local farms</p>
            </div>

            <?php
            // Fetch livestock products from database
            $livestock_products = [];
            if (isset($farmcart) && $farmcart->conn) {
                $livestock_products_query = "SELECT
                        p.product_id,
                        p.product_name,
                        p.description,
                        p.base_price,
                        p.unit_type,
                        p.quantity,
                        c.category_name,
                        c.category_type,
                        pi.image_url,
                        u.first_name as farmer_first,
                        u.last_name as farmer_last,
                        fp.farm_name
                     FROM products p
                     LEFT JOIN categories c ON p.category_id = c.category_id
                     LEFT JOIN product_images pi ON p.product_id = pi.product_id AND pi.is_primary = TRUE
                     LEFT JOIN users u ON p.created_by = u.user_id
                     LEFT JOIN farmer_profiles fp ON p.created_by = fp.user_id
                     WHERE p.approval_status = 'approved'
                       AND p.is_listed = TRUE
                       AND (p.is_expired IS NULL OR p.is_expired = 0)
                       AND (p.expires_at IS NULL OR p.expires_at > NOW())
                       AND c.category_type = 'livestock'
                     ORDER BY p.created_at DESC
                     LIMIT 4";
                $livestock_result = $farmcart->conn->query($livestock_products_query);
                if ($livestock_result && $livestock_result->num_rows > 0) {
                    while ($row = $livestock_result->fetch_assoc()) {
                        $livestock_products[] = $row;
                    }
                }
            }
            ?>

            <div class="livestock-grid">
                <?php if (!empty($livestock_products)): ?>
                    <?php foreach ($livestock_products as $product): ?>
                        <?php
                        $image_url = !empty($product['image_url']) ? $product['image_url'] : '';
                        if (!empty($image_url) && !preg_match('/^https?:\/\//', $image_url)) {
                            if (strpos($image_url, 'uploads/') === 0) {
                                $image_url = '../farmer/' . $image_url;
                            }
                        }
                        if (empty($image_url)) {
                            $image_url = 'https://via.placeholder.com/300x250?text=No+Image';
                        }
                        ?>
                        <div class="livestock-card" onclick="window.location='products.php?category=livestock'">
                            <img src="<?= htmlspecialchars($image_url); ?>" alt="<?= htmlspecialchars($product['product_name']); ?>" class="product-image" onerror="this.src='https://via.placeholder.com/300x250?text=No+Image'">
                            <div class="product-content">
                                <h3><?= htmlspecialchars($product['product_name']); ?></h3>
                                <p><?= htmlspecialchars(substr($product['description'] ?? 'Fresh from local farms', 0, 50)) . (strlen($product['description'] ?? '') > 50 ? '...' : ''); ?></p>
                                <div class="product-price">₱<?= number_format($product['base_price'], 2); ?>/<?= htmlspecialchars($product['unit_type']); ?></div>
                                <button class="btn-add-to-cart" onclick="event.stopPropagation(); window.location='products.php?category=livestock'">View Products</button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div style="grid-column: 1/-1; text-align: center; padding: 3rem;">
                        <i class="fas fa-cow fa-4x text-muted mb-3"></i>
                        <h3>No Livestock Products Yet</h3>
                        <p class="text-muted">Check back soon for fresh livestock products from our local farms.</p>
                        <a href="products.php?category=livestock" class="btn btn-primary mt-3">Browse All Products</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Seasonal Calendar Section -->
    <section id="calendar" class="seasonal-section" style="background-color: rgba(255,255,255,0.5);">
        <div class="container">
            <div class="section-header">
                <h2>Seasonal Harvest Calendar</h2>
                <p>Know when your favorite produce is at its peak freshness and flavor</p>
            </div>

            <div class="calendar-grid">
                <div class="month-card" style="background-color: #DBEAFE;">
                    <h3>January</h3>
                    <ul class="produce-list">
                        <li class="produce-item"><i class="ri-leaf-line"></i> Winter Squash</li>
                        <li class="produce-item"><i class="ri-leaf-line"></i> Citrus Fruits</li>
                        <li class="produce-item"><i class="ri-leaf-line"></i> Root Vegetables</li>
                    </ul>
                </div>

                <div class="month-card" style="background-color: #F3E8FF;">
                    <h3>February</h3>
                    <ul class="produce-list">
                        <li class="produce-item"><i class="ri-leaf-line"></i> Cabbage</li>
                        <li class="produce-item"><i class="ri-leaf-line"></i> Leeks</li>
                        <li class="produce-item"><i class="ri-leaf-line"></i> Potatoes</li>
                    </ul>
                </div>

                <div class="month-card" style="background-color: #DCFCE7;">
                    <h3>March</h3>
                    <ul class="produce-list">
                        <li class="produce-item"><i class="ri-leaf-line"></i> Asparagus</li>
                        <li class="produce-item"><i class="ri-leaf-line"></i> Peas</li>
                        <li class="produce-item"><i class="ri-leaf-line"></i> Radishes</li>
                    </ul>
                </div>

                <div class="month-card" style="background-color: #FEF3C7;">
                    <h3>April</h3>
                    <ul class="produce-list">
                        <li class="produce-item"><i class="ri-leaf-line"></i> Artichokes</li>
                        <li class="produce-item"><i class="ri-leaf-line"></i> Spinach</li>
                        <li class="produce-item"><i class="ri-leaf-line"></i> Spring Onions</li>
                    </ul>
                </div>

                <div class="month-card" style="background-color: #FCE7F3;">
                    <h3>May</h3>
                    <ul class="produce-list">
                        <li class="produce-item"><i class="ri-leaf-line"></i> Strawberries</li>
                        <li class="produce-item"><i class="ri-leaf-line"></i> Lettuce</li>
                        <li class="produce-item"><i class="ri-leaf-line"></i> Rhubarb</li>
                    </ul>
                </div>

                <div class="month-card" style="background-color: #FEE2E2;">
                    <h3>June</h3>
                    <ul class="produce-list">
                        <li class="produce-item"><i class="ri-leaf-line"></i> Berries</li>
                        <li class="produce-item"><i class="ri-leaf-line"></i> Cherries</li>
                        <li class="produce-item"><i class="ri-leaf-line"></i> Early Tomatoes</li>
                    </ul>
                </div>

                <div class="month-card" style="background-color: #FFEDD5;">
                    <h3>July</h3>
                    <ul class="produce-list">
                        <li class="produce-item"><i class="ri-leaf-line"></i> Corn</li>
                        <li class="produce-item"><i class="ri-leaf-line"></i> Peaches</li>
                        <li class="produce-item"><i class="ri-leaf-line"></i> Summer Squash</li>
                    </ul>
                </div>

                <div class="month-card" style="background-color: #E0E7FF;">
                    <h3>August</h3>
                    <ul class="produce-list">
                        <li class="produce-item"><i class="ri-leaf-line"></i> Melons</li>
                        <li class="produce-item"><i class="ri-leaf-line"></i> Peppers</li>
                        <li class="produce-item"><i class="ri-leaf-line"></i> Eggplant</li>
                    </ul>
                </div>

                <div class="month-card" style="background-color: #FEF08A;">
                    <h3>September</h3>
                    <ul class="produce-list">
                        <li class="produce-item"><i class="ri-leaf-line"></i> Apples</li>
                        <li class="produce-item"><i class="ri-leaf-line"></i> Pumpkins</li>
                        <li class="produce-item"><i class="ri-leaf-line"></i> Grapes</li>
                    </ul>
                </div>

                <div class="month-card" style="background-color: #FED7AA;">
                    <h3>October</h3>
                    <ul class="produce-list">
                        <li class="produce-item"><i class="ri-leaf-line"></i> Sweet Potatoes</li>
                        <li class="produce-item"><i class="ri-leaf-line"></i> Cranberries</li>
                        <li class="produce-item"><i class="ri-leaf-line"></i> Brussels Sprouts</li>
                    </ul>
                </div>

                <div class="month-card" style="background-color: #FEF3C7;">
                    <h3>November</h3>
                    <ul class="produce-list">
                        <li class="produce-item"><i class="ri-leaf-line"></i> Turnips</li>
                        <li class="produce-item"><i class="ri-leaf-line"></i> Parsnips</li>
                        <li class="produce-item"><i class="ri-leaf-line"></i> Late Harvest</li>
                    </ul>
                </div>

                <div class="month-card" style="background-color: #DCFCE7;">
                    <h3>December</h3>
                    <ul class="produce-list">
                        <li class="produce-item"><i class="ri-leaf-line"></i> Winter Greens</li>
                        <li class="produce-item"><i class="ri-leaf-line"></i> Stored Apples</li>
                        <li class="produce-item"><i class="ri-leaf-line"></i> Preserved Foods</li>
                    </ul>
                </div>
            </div>
        </div>
    </section>


        <!-- Bootstrap JS -->
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>

        <script>
            // Add hover effects for clickable cards
            document.addEventListener('DOMContentLoaded', function() {
                // Quantity controls
                document.querySelectorAll('.quantity-btn').forEach(button => {
                    button.addEventListener('click', function(e) {
                        e.preventDefault();
                        e.stopPropagation();

                        const quantitySpan = this.parentElement.querySelector('.quantity-value');
                        let quantity = parseInt(quantitySpan.textContent);

                        if (this.classList.contains('plus')) {
                            quantity++;
                        } else if (this.classList.contains('minus') && quantity > 1) {
                            quantity--;
                        }

                        quantitySpan.textContent = quantity;
                    });
                });

                // Add to cart buttons
                document.querySelectorAll('.btn-add-to-cart').forEach(button => {
                    button.addEventListener('click', function(e) {
                        e.preventDefault();
                        e.stopPropagation();

                        const productCard = this.closest('.product-card');
                        const productName = productCard.querySelector('.product-title').textContent;
                        const productPrice = productCard.querySelector('.price-main').textContent;
                        const quantity = productCard.querySelector('.quantity-value').textContent;

                        // Show success message
                        alert(`Added ${quantity} kg of ${productName} to cart for ₱${parseInt(productPrice) * parseInt(quantity)}`);

                        // You can replace this with actual cart functionality
                        // Example: addToCart(productId, quantity);
                    });
                });
            });
        </script>

    </div>
</body>
</html>