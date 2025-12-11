<?php
include '../../db_connect.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("location: ../index.php");
    exit();
}

// Get category from URL
$category_name = $_GET['category'] ?? '';
$category_type = $_GET['type'] ?? '';

// Map category names to types
$category_mapping = [
    'vegetables' => 'vegetables',
    'fruits' => 'fruits',
    'eggs' => 'poultry',
    'dairy' => 'dairy',
    'livestock' => 'livestock',
    'honey' => 'herbs',
    'fish' => 'livestock'
];

$category_type = $category_mapping[$category_name] ?? $category_type;

// Get category display name and description
$category_display_names = [
    'vegetables' => 'Fresh Vegetables',
    'fruits' => 'Fresh Fruits',
    'poultry' => 'Farm Eggs & Poultry',
    'dairy' => 'Artisan Dairy Products',
    'livestock' => 'Premium Livestock',
    'herbs' => 'Pure Honey & Herbs'
];

$category_descriptions = [
    'vegetables' => 'Fresh, seasonal organic vegetables from local farms. Harvested at peak freshness for maximum flavor and nutrition.',
    'fruits' => 'Sweet and juicy seasonal fruits grown with care. Perfect for snacking, desserts, and healthy eating.',
    'poultry' => 'Free-range eggs and humanely raised poultry. No antibiotics or hormones, just natural goodness.',
    'dairy' => 'Artisan dairy products made with traditional methods. Cheese, milk, and yogurt from happy animals.',
    'livestock' => 'Humanely raised meat products from local family farms. Grass-fed, free-range, and naturally raised.',
    'herbs' => '100% natural raw honey and fresh herbs. Pure, unprocessed, and full of natural goodness.'
];

$display_name = $category_display_names[$category_type] ?? ucfirst($category_name);
$description = $category_descriptions[$category_type] ?? 'Discover our fresh farm products.';

$products = $sample_products[$category_type] ?? [];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $display_name; ?> | FarmCart</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- CSS -->
    <link rel="stylesheet" href="../../Assets/css/navbar.css">
    <link rel="stylesheet" href="../../Assets/css/customer.css">
    <link rel="stylesheet" href="../../Assets/css/products.css">
</head>
<body>
    <!-- Include Navbar -->
    <?php include '../../Includes/navbar.php'; ?>

    <!-- Category Header -->
    <section class="category-header">
        <div class="container">

            <div class="category-hero">
                <div class="category-icon-large">
                    <?php
                    $category_icons = [
                        'vegetables' => '🥦',
                        'fruits' => '🍎',
                        'poultry' => '🥚',
                        'dairy' => '🧀',
                        'livestock' => '🐄',
                        'herbs' => '🍯'
                    ];
                    echo $category_icons[$category_type] ?? '📦';
                    ?>
                </div>
                <h1 class="category-title"><?php echo $display_name; ?></h1>
                <p class="category-description"><?php echo $description; ?></p>

                <div class="category-stats">
                    <div class="stat">
                        <span class="stat-number"><?php echo count($products); ?></span>
                        <span class="stat-label">Products</span>
                    </div>
                    <div class="stat">
                        <span class="stat-number">15+</span>
                        <span class="stat-label">Local Farms</span>
                    </div>
                    <div class="stat">
                        <span class="stat-number">24h</span>
                        <span class="stat-label">Fresh Delivery</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Products Grid -->
    <section class="products-listing">
        <div class="container">
            <!-- Filters and Sorting -->
            <div class="products-header">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <h2 class="section-title">Available Products</h2>
                        <p class="text-muted">Showing <?php echo count($products); ?> products</p>
                    </div>
                    <div class="col-md-6 text-end">
                        <div class="sorting-options">
                            <select class="form-select" style="max-width: 200px;">
                                <option>Sort by: Popular</option>
                                <option>Price: Low to High</option>
                                <option>Price: High to Low</option>
                                <option>Rating: Highest First</option>
                                <option>Newest First</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Products Grid -->
            <div class="products-grid">
                <?php if (!empty($products)): ?>
                    <?php foreach ($products as $product): ?>
                    <div class="product-card">
                        <div class="product-image">
                            <span class="product-emoji"><?php echo $product['image']; ?></span>
                            <div class="product-actions">
                                <button class="btn-wishlist" title="Add to Wishlist">
                                    <i class="far fa-heart"></i>
                                </button>
                            </div>
                        </div>

                        <div class="product-content">
                            <div class="product-rating">
                                <span class="stars">
                                    <?php
                                    $rating = $product['rating'];
                                    $fullStars = floor($rating);
                                    $halfStar = ($rating - $fullStars) >= 0.5;

                                    for ($i = 1; $i <= 5; $i++) {
                                        if ($i <= $fullStars) {
                                            echo '<i class="fas fa-star"></i>';
                                        } elseif ($halfStar && $i == $fullStars + 1) {
                                            echo '<i class="fas fa-star-half-alt"></i>';
                                        } else {
                                            echo '<i class="far fa-star"></i>';
                                        }
                                    }
                                    ?>
                                </span>
                                <span class="rating-value"><?php echo $rating; ?></span>
                            </div>

                            <h3 class="product-title"><?php echo $product['name']; ?></h3>
                            <p class="product-farmer">
                                <i class="fas fa-tractor me-1"></i>
                                <?php echo $product['farmer']; ?>
                            </p>

                            <div class="product-price">
                                <span class="price">₱<?php echo number_format($product['price'], 2); ?></span>
                                <span class="unit">/<?php echo $product['unit']; ?></span>
                            </div>

                            <div class="product-meta">
                                <span class="meta-item">
                                    <i class="fas fa-shipping-fast"></i>
                                    Free Delivery
                                </span>
                                <span class="meta-item">
                                    <i class="fas fa-check-circle"></i>
                                    In Stock
                                </span>
                            </div>

                            <div class="product-actions-bottom">
                                <div class="quantity-selector">
                                    <button class="quantity-btn minus">-</button>
                                    <input type="number" class="quantity-input" value="1" min="1" max="10">
                                    <button class="quantity-btn plus">+</button>
                                </div>
                                <button class="btn-add-to-cart" data-product-id="<?php echo $product['id']; ?>">
                                    <i class="fas fa-cart-plus"></i>
                                    Add to Cart
                                </button>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="no-products">
                        <div class="no-products-icon">📦</div>
                        <h3>No Products Available</h3>
                        <p>We're working on adding more products to this category.</p>
                        <a href="index.php" class="btn btn-primary">
                            <i class="fas fa-arrow-left me-2"></i>
                            Back to Home
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Featured Farmers Section -->
    <section class="featured-farmers">
        <div class="container">
            <h2 class="section-title text-center">Featured Farmers</h2>
            <p class="section-subtitle text-center">Meet the local farmers behind these amazing products</p>

            <div class="farmers-grid">
                <div class="farmer-card">
                    <div class="farmer-avatar">
                        <span>🚜</span>
                    </div>
                    <h4>Green Valley Farm</h4>
                    <p>Specializing in organic vegetables since 2010</p>
                    <div class="farmer-rating">
                        <i class="fas fa-star"></i>
                        <span>4.8 (120 reviews)</span>
                    </div>
                </div>

                <div class="farmer-card">
                    <div class="farmer-avatar">
                        <span>🌞</span>
                    </div>
                    <h4>Sunshine Farm</h4>
                    <p>Fresh fruits and free-range poultry</p>
                    <div class="farmer-rating">
                        <i class="fas fa-star"></i>
                        <span>4.9 (95 reviews)</span>
                    </div>
                </div>

                <div class="farmer-card">
                    <div class="farmer-avatar">
                        <span>🏔️</span>
                    </div>
                    <h4>Mountain View Farm</h4>
                    <p>Grass-fed livestock and dairy products</p>
                    <div class="farmer-rating">
                        <i class="fas fa-star"></i>
                        <span>4.7 (80 reviews)</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Quantity selector functionality
        document.querySelectorAll('.quantity-btn').forEach(button => {
            button.addEventListener('click', function() {
                const input = this.parentElement.querySelector('.quantity-input');
                let value = parseInt(input.value);

                if (this.classList.contains('plus')) {
                    input.value = value + 1;
                } else if (this.classList.contains('minus') && value > 1) {
                    input.value = value - 1;
                }
            });
        });

        // Add to cart functionality
        document.querySelectorAll('.btn-add-to-cart').forEach(button => {
            button.addEventListener('click', function() {
                const productId = this.getAttribute('data-product-id');
                const quantity = this.parentElement.querySelector('.quantity-input').value;

                // Show success message
                const originalText = this.innerHTML;
                this.innerHTML = '<i class="fas fa-check"></i> Added!';
                this.disabled = true;

                setTimeout(() => {
                    this.innerHTML = originalText;
                    this.disabled = false;
                }, 2000);

                // Here you would typically send an AJAX request to add to cart
                console.log(`Added product ${productId} with quantity ${quantity} to cart`);
            });
        });

        // Wishlist functionality
        document.querySelectorAll('.btn-wishlist').forEach(button => {
            button.addEventListener('click', function() {
                const icon = this.querySelector('i');
                if (icon.classList.contains('far')) {
                    icon.classList.remove('far');
                    icon.classList.add('fas');
                    icon.style.color = '#ff4757';
                } else {
                    icon.classList.remove('fas');
                    icon.classList.add('far');
                    icon.style.color = '';
                }
            });
        });
    </script>
</body>
</html>
