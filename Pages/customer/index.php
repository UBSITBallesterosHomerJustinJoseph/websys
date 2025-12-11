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

// Define image paths for categories
$category_images = [
    'vegetables' => '../../Assets/images/categories/vegetables.jpg',
    'fruits' => '../../Assets/images/categories/fruits.jpg',
    'eggs' => '../../Assets/images/categories/eggs.jpg',
    'dairy' => '../../Assets/images/categories/dairy.jpg',
    'livestock' => '../../Assets/images/categories/livestock.jpg',
    'honey' => '../../Assets/images/categories/honey.jpg',
    'fish' => '../../Assets/images/categories/fish.jpg',
    'all' => '../../Assets/images/categories/all.jpg'
];

// Define image paths for products
$product_images = [
    'pork' => '../../Assets/images/products/pork.jpg',
    'goat' => '../../Assets/images/products/goat-meat.jpg',
    'chicken' => '../../Assets/images/products/chicken.jpg',
    'beef' => '../../Assets/images/products/beef.jpg'
];

// Define image paths for seasonal produce
$season_images = [
    'january' => '../../Assets/images/seasons/january.jpg',
    'april' => '../../Assets/images/seasons/april.jpg',
    'july' => '../../Assets/images/seasons/july.jpg',
    'october' => '../../Assets/images/seasons/october.jpg'
];
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
    <link rel="stylesheet" href="../../Assets/css/navbar.css">
    <link rel="stylesheet" href="../../Assets/css/customer.css">
    <style>
        /* Remove underlines from all links */
        .category-card-link,
        .product-card-link,
        .season-card-link {
            text-decoration: none !important;
        }

        /* Ensure no text decoration on hover */
        .category-card-link:hover,
        .product-card-link:hover,
        .season-card-link:hover {
            text-decoration: none !important;
        }

        /* Image styling */
        .category-image {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 50%;
            margin-bottom: 15px;
            border: 3px solid #e8f5e9;
        }

        .product-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
            border-radius: 10px 10px 0 0;
        }

        .season-image {
            width: 100%;
            height: 150px;
            object-fit: cover;
            border-radius: 10px 10px 0 0;
        }

        .produce-item-image {
            width: 40px;
            height: 40px;
            object-fit: cover;
            border-radius: 50%;
            margin-right: 10px;
        }

        /* Fallback for missing images */
        .image-fallback {
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
        }

        .category-image-fallback {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 24px;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <!-- Include Navbar -->
    <?php include '../../Includes/navbar.php'; ?>

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
                    <button class="btn btn-primary hero-btn" onclick="location.href='products.php?category=all'">
                        <i class="fas fa-shopping-basket me-2"></i>
                        Shop Now
                    </button>
                    <button class="btn btn-outline-secondary hero-btn">
                        <i class="fas fa-play-circle me-2"></i>
                        How It Works
                    </button>

                <!-- Become a Farmer Button -->
                <?php if ($_SESSION['user_role'] === 'customer'): ?>
                <a href="setUpStore.php" class="btn btn-success hero-btn">
                    <i class="fas fa-store me-2"></i>
                    Set Up Your Store
                </a>
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
    <section class="categories-section">
        <div class="container">
            <div class="section-header text-center">
                <h2 class="section-title">Our Farm Categories</h2>
                <p class="section-subtitle">Discover fresh produce from our trusted local farmers</p>
            </div>

            <div class="categories-grid">
                <!-- Category 1: Vegetables -->
                <a href="products.php?category=vegetables" class="category-card-link text-decoration-none">
                    <div class="category-card">
                        <div class="category-icon-wrapper">
                            <?php if (file_exists($category_images['vegetables'])): ?>
                                <img src="<?php echo $category_images['vegetables']; ?>" alt="Fresh Vegetables" class="category-image">
                            <?php else: ?>
                                <div class="category-image-fallback">🥦</div>
                            <?php endif; ?>
                        </div>
                        <h3 class="category-title">Fresh Vegetables</h3>
                        <p class="category-description">Seasonal organic vegetables from local farms</p>
                        <div class="category-badge">Popular</div>
                    </div>
                </a>

                <!-- Category 2: Fruits -->
                <a href="products.php?category=fruits" class="category-card-link text-decoration-none">
                    <div class="category-card">
                        <div class="category-icon-wrapper">
                            <?php if (file_exists($category_images['fruits'])): ?>
                                <img src="<?php echo $category_images['fruits']; ?>" alt="Fresh Fruits" class="category-image">
                            <?php else: ?>
                                <div class="category-image-fallback">🍎</div>
                            <?php endif; ?>
                        </div>
                        <h3 class="category-title">Fresh Fruits</h3>
                        <p class="category-description">Sweet and juicy seasonal fruits</p>
                    </div>
                </a>

                <!-- Category 3: Eggs -->
                <a href="products.php?category=eggs" class="category-card-link text-decoration-none">
                    <div class="category-card">
                        <div class="category-icon-wrapper">
                            <?php if (file_exists($category_images['eggs'])): ?>
                                <img src="<?php echo $category_images['eggs']; ?>" alt="Farm Eggs" class="category-image">
                            <?php else: ?>
                                <div class="category-image-fallback">🥚</div>
                            <?php endif; ?>
                        </div>
                        <h3 class="category-title">Farm Eggs</h3>
                        <p class="category-description">Free-range and organic eggs</p>
                    </div>
                </a>

                <!-- Category 4: Dairy -->
                <a href="products.php?category=dairy" class="category-card-link text-decoration-none">
                    <div class="category-card">
                        <div class="category-icon-wrapper">
                            <?php if (file_exists($category_images['dairy'])): ?>
                                <img src="<?php echo $category_images['dairy']; ?>" alt="Dairy Products" class="category-image">
                            <?php else: ?>
                                <div class="category-image-fallback">🧀</div>
                            <?php endif; ?>
                        </div>
                        <h3 class="category-title">Artisans</h3>
                        <p class="category-description">Local handmade products and more</p>
                    </div>
                </a>

                <!-- Category 5: Livestock -->
                <a href="products.php?category=livestock" class="category-card-link text-decoration-none">
                    <div class="category-card">
                        <div class="category-icon-wrapper">
                            <?php if (file_exists($category_images['livestock'])): ?>
                                <img src="<?php echo $category_images['livestock']; ?>" alt="Livestock" class="category-image">
                            <?php else: ?>
                                <div class="category-image-fallback">🐄</div>
                            <?php endif; ?>
                        </div>
                        <h3 class="category-title">Livestock</h3>
                        <p class="category-description">Humanely raised meat products</p>
                        <div class="category-badge">Best Seller</div>
                    </div>
                </a>

                <!-- Category 6: Honey -->
                <a href="products.php?category=honey" class="category-card-link text-decoration-none">
                    <div class="category-card">
                        <div class="category-icon-wrapper">
                            <?php if (file_exists($category_images['honey'])): ?>
                                <img src="<?php echo $category_images['honey']; ?>" alt="Pure Honey" class="category-image">
                            <?php else: ?>
                                <div class="category-image-fallback">🍯</div>
                            <?php endif; ?>
                        </div>
                        <h3 class="category-title">Pure Honey</h3>
                        <p class="category-description">100% natural raw honey</p>
                    </div>
                </a>

                <!-- Category 7: Fish -->
                <a href="products.php?category=fish" class="category-card-link text-decoration-none">
                    <div class="category-card">
                        <div class="category-icon-wrapper">
                            <?php if (file_exists($category_images['fish'])): ?>
                                <img src="<?php echo $category_images['fish']; ?>" alt="Fresh Fish" class="category-image">
                            <?php else: ?>
                                <div class="category-image-fallback">🎣</div>
                            <?php endif; ?>
                        </div>
                        <h3 class="category-title">Fresh Fish</h3>
                        <p class="category-description">Local catch delivered fresh</p>
                        <div class="category-badge">New</div>
                    </div>
                </a>

                <!-- View All -->
                <a href="products.php?category=all" class="category-card-link text-decoration-none">
                    <div class="category-card view-all-card">
                        <div class="category-icon-wrapper">
                            <?php if (file_exists($category_images['all'])): ?>
                                <img src="<?php echo $category_images['all']; ?>" alt="All Categories" class="category-image">
                            <?php else: ?>
                                <div class="category-image-fallback">📦</div>
                            <?php endif; ?>
                        </div>
                        <h3 class="category-title">View All</h3>
                        <p class="category-description">Explore all categories</p>
                    </div>
                </a>
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
                <!-- Product 1: Pork -->
                <a href="products.php?category=livestock&product=pork" class="product-card-link text-decoration-none">
                    <div class="product-card premium">
                        <div class="product-badge">Most Popular</div>
                        <div class="product-image-container">
                            <?php if (file_exists($product_images['pork'])): ?>
                                <img src="<?php echo $product_images['pork']; ?>" alt="Pasture-Raised Pork" class="product-image">
                            <?php else: ?>
                                <div class="product-image image-fallback">
                                    <span>🐖</span>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="product-content">
                            <div class="product-rating">
                                <span class="rating-stars">
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star-half-alt"></i>
                                </span>
                                <span class="rating-value">4.5</span>
                            </div>
                            <h3 class="product-title">Pasture-Raised Pork</h3>
                            <p class="product-farm">Happy Farm</p>
                            <div class="product-price">
                                <span class="price-main">₱200</span>
                                <span class="price-unit">/kg</span>
                            </div>
                            <div class="product-delivery-info">
                                <span class="delivery-badge">
                                    <i class="fas fa-truck me-1"></i>
                                    Free Delivery
                                </span>
                                <span class="stock-badge in-stock">
                                    <i class="fas fa-check-circle me-1"></i>
                                    In Stock
                                </span>
                            </div>
                            <div class="product-quantity">
                                <button class="quantity-btn minus">-</button>
                                <span class="quantity-value">1</span>
                                <button class="quantity-btn plus">+</button>
                                <button class="btn-add-to-cart">
                                    <i class="fas fa-cart-plus me-2"></i>
                                    Add to Cart
                                </button>
                            </div>
                        </div>
                    </div>
                </a>

                <!-- Product 2: Goat Meat -->
                <a href="products.php?category=livestock&product=goat" class="product-card-link text-decoration-none">
                    <div class="product-card premium">
                        <div class="product-image-container">
                            <?php if (file_exists($product_images['goat'])): ?>
                                <img src="<?php echo $product_images['goat']; ?>" alt="Fresh Goat Meat" class="product-image">
                            <?php else: ?>
                                <div class="product-image image-fallback">
                                    <span>🐐</span>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="product-content">
                            <div class="product-rating">
                                <span class="rating-stars">
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                </span>
                                <span class="rating-value">5.0</span>
                            </div>
                            <h3 class="product-title">Fresh Goat Meat</h3>
                            <p class="product-farm">Mountain Goat Farm</p>
                            <div class="product-price">
                                <span class="price-main">₱350</span>
                                <span class="price-unit">/kg</span>
                            </div>
                            <div class="product-delivery-info">
                                <span class="delivery-badge">
                                    <i class="fas fa-truck me-1"></i>
                                    Free Delivery
                                </span>
                                <span class="stock-badge in-stock">
                                    <i class="fas fa-check-circle me-1"></i>
                                    In Stock
                                </span>
                            </div>
                            <div class="product-quantity">
                                <button class="quantity-btn minus">-</button>
                                <span class="quantity-value">1</span>
                                <button class="quantity-btn plus">+</button>
                                <button class="btn-add-to-cart">
                                    <i class="fas fa-cart-plus me-2"></i>
                                    Add to Cart
                                </button>
                            </div>
                        </div>
                    </div>
                </a>

                <!-- Product 3: Chicken -->
                <a href="products.php?category=livestock&product=chicken" class="product-card-link text-decoration-none">
                    <div class="product-card premium">
                        <div class="product-badge">Free Range</div>
                        <div class="product-image-container">
                            <?php if (file_exists($product_images['chicken'])): ?>
                                <img src="<?php echo $product_images['chicken']; ?>" alt="Free-Range Chicken" class="product-image">
                            <?php else: ?>
                                <div class="product-image image-fallback">
                                    <span>🐔</span>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="product-content">
                            <div class="product-rating">
                                <span class="rating-stars">
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="far fa-star"></i>
                                </span>
                                <span class="rating-value">4.0</span>
                            </div>
                            <h3 class="product-title">Free-Range Chicken</h3>
                            <p class="product-farm">Sunny Farm</p>
                            <div class="product-price">
                                <span class="price-main">₱250</span>
                                <span class="price-unit">/kg</span>
                            </div>
                            <div class="product-delivery-info">
                                <span class="delivery-badge">
                                    <i class="fas fa-truck me-1"></i>
                                    Free Delivery
                                </span>
                                <span class="stock-badge in-stock">
                                    <i class="fas fa-check-circle me-1"></i>
                                    In Stock
                                </span>
                            </div>
                            <div class="product-quantity">
                                <button class="quantity-btn minus">-</button>
                                <span class="quantity-value">1</span>
                                <button class="quantity-btn plus">+</button>
                                <button class="btn-add-to-cart">
                                    <i class="fas fa-cart-plus me-2"></i>
                                    Add to Cart
                                </button>
                            </div>
                        </div>
                    </div>
                </a>

                <!-- Product 4: Beef -->
                <a href="products.php?category=livestock&product=beef" class="product-card-link text-decoration-none">
                    <div class="product-card premium">
                        <div class="product-image-container">
                            <?php if (file_exists($product_images['beef'])): ?>
                                <img src="<?php echo $product_images['beef']; ?>" alt="Grass-Fed Beef" class="product-image">
                            <?php else: ?>
                                <div class="product-image image-fallback">
                                    <span>🐄</span>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="product-content">
                            <div class="product-rating">
                                <span class="rating-stars">
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star-half-alt"></i>
                                </span>
                                <span class="rating-value">4.5</span>
                            </div>
                            <h3 class="product-title">Grass-Fed Beef</h3>
                            <p class="product-farm">Green Pastures Farm</p>
                            <div class="product-price">
                                <span class="price-main">₱450</span>
                                <span class="price-unit">/kg</span>
                            </div>
                            <div class="product-delivery-info">
                                <span class="delivery-badge">
                                    <i class="fas fa-truck me-1"></i>
                                    Free Delivery
                                </span>
                                <span class="stock-badge in-stock">
                                    <i class="fas fa-check-circle me-1"></i>
                                    In Stock
                                </span>
                            </div>
                            <div class="product-quantity">
                                <button class="quantity-btn minus">-</button>
                                <span class="quantity-value">1</span>
                                <button class="quantity-btn plus">+</button>
                                <button class="btn-add-to-cart">
                                    <i class="fas fa-cart-plus me-2"></i>
                                    Add to Cart
                                </button>
                            </div>
                        </div>
                    </div>
                </a>
            </div>

            <!-- View All Products Button -->
            <div class="text-center mt-5">
                <a href="products.php?category=livestock" class="btn btn-outline-primary btn-lg text-decoration-none">
                    <i class="fas fa-eye me-2"></i>
                    View All Livestock Products
                </a>
            </div>
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
                <!-- January -->
                <a href="products.php?category=seasonal&month=january" class="season-card-link text-decoration-none">
                    <div class="season-card current">
                        <div class="season-image-container">
                            <?php if (file_exists($season_images['january'])): ?>
                                <img src="<?php echo $season_images['january']; ?>" alt="January Harvest" class="season-image">
                            <?php else: ?>
                                <div class="season-image image-fallback">
                                    January
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="season-content">
                            <div class="season-header">
                                <h3 class="season-name">January</h3>
                                <div class="season-badge">Current</div>
                            </div>
                            <div class="season-produce">
                                <div class="produce-item">
                                    <img src="../../Assets/images/produce/citrus.jpg" alt="Citrus Fruits" class="produce-item-image">
                                    <span class="produce-name">Citrus Fruits</span>
                                </div>
                                <div class="produce-item">
                                    <img src="../../Assets/images/produce/greens.jpg" alt="Leafy Greens" class="produce-item-image">
                                    <span class="produce-name">Leafy Greens</span>
                                </div>
                                <div class="produce-item">
                                    <img src="../../Assets/images/produce/root-vegetables.jpg" alt="Root Vegetables" class="produce-item-image">
                                    <span class="produce-name">Root Vegetables</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </a>

                <!-- April -->
                <a href="products.php?category=seasonal&month=april" class="season-card-link text-decoration-none">
                    <div class="season-card">
                        <div class="season-image-container">
                            <?php if (file_exists($season_images['april'])): ?>
                                <img src="<?php echo $season_images['april']; ?>" alt="April Harvest" class="season-image">
                            <?php else: ?>
                                <div class="season-image image-fallback">
                                    April
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="season-content">
                            <div class="season-header">
                                <h3 class="season-name">April</h3>
                            </div>
                            <div class="season-produce">
                                <div class="produce-item">
                                    <img src="../../Assets/images/produce/strawberries.jpg" alt="Strawberries" class="produce-item-image">
                                    <span class="produce-name">Strawberries</span>
                                </div>
                                <div class="produce-item">
                                    <img src="../../Assets/images/produce/asparagus.jpg" alt="Asparagus" class="produce-item-image">
                                    <span class="produce-name">Asparagus</span>
                                </div>
                                <div class="produce-item">
                                    <img src="../../Assets/images/produce/spring-greens.jpg" alt="Spring Greens" class="produce-item-image">
                                    <span class="produce-name">Spring Greens</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </a>

                <!-- July -->
                <a href="products.php?category=seasonal&month=july" class="season-card-link text-decoration-none">
                    <div class="season-card">
                        <div class="season-image-container">
                            <?php if (file_exists($season_images['july'])): ?>
                                <img src="<?php echo $season_images['july']; ?>" alt="July Harvest" class="season-image">
                            <?php else: ?>
                                <div class="season-image image-fallback">
                                    July
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="season-content">
                            <div class="season-header">
                                <h3 class="season-name">July</h3>
                            </div>
                            <div class="season-produce">
                                <div class="produce-item">
                                    <img src="../../Assets/images/produce/stone-fruits.jpg" alt="Stone Fruits" class="produce-item-image">
                                    <span class="produce-name">Stone Fruits</span>
                                </div>
                                <div class="produce-item">
                                    <img src="../../Assets/images/produce/tomatoes.jpg" alt="Tomatoes" class="produce-item-image">
                                    <span class="produce-name">Tomatoes</span>
                                </div>
                                <div class="produce-item">
                                    <img src="../../Assets/images/produce/corn.jpg" alt="Sweet Corn" class="produce-item-image">
                                    <span class="produce-name">Sweet Corn</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </a>

                <!-- October -->
                <a href="products.php?category=seasonal&month=october" class="season-card-link text-decoration-none">
                    <div class="season-card">
                        <div class="season-image-container">
                            <?php if (file_exists($season_images['october'])): ?>
                                <img src="<?php echo $season_images['october']; ?>" alt="October Harvest" class="season-image">
                            <?php else: ?>
                                <div class="season-image image-fallback">
                                    October
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="season-content">
                            <div class="season-header">
                                <h3 class="season-name">October</h3>
                            </div>
                            <div class="season-produce">
                                <div class="produce-item">
                                    <img src="../../Assets/images/produce/pumpkins.jpg" alt="Pumpkins" class="produce-item-image">
                                    <span class="produce-name">Pumpkins</span>
                                </div>
                                <div class="produce-item">
                                    <img src="../../Assets/images/produce/apples.jpg" alt="Apples" class="produce-item-image">
                                    <span class="produce-name">Apples</span>
                                </div>
                                <div class="produce-item">
                                    <img src="../../Assets/images/produce/potatoes.jpg" alt="Potatoes" class="produce-item-image">
                                    <span class="produce-name">Potatoes</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </a>
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
                        <input type="email" placeholder="Enter your email" class="form-control">
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

</body>
</html>
