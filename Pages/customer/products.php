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
    'all' => 'All Products',
    'vegetables' => 'Fresh Vegetables',
    'fruits' => 'Fresh Fruits',
    'poultry' => 'Farm Eggs & Poultry',
    'dairy' => 'Artisan Dairy Products',
    'livestock' => 'Premium Livestock',
    'herbs' => 'Pure Honey & Herbs'
];

$category_descriptions = [
    'all' => 'Browse all available farm-fresh products from local farmers.',
    'vegetables' => 'Fresh, seasonal organic vegetables from local farms. Harvested at peak freshness for maximum flavor and nutrition.',
    'fruits' => 'Sweet and juicy seasonal fruits grown with care. Perfect for snacking, desserts, and healthy eating.',
    'poultry' => 'Free-range eggs and humanely raised poultry. No antibiotics or hormones, just natural goodness.',
    'dairy' => 'Artisan dairy products made with traditional methods. Cheese, milk, and yogurt from happy animals.',
    'livestock' => 'Humanely raised meat products from local family farms. Grass-fed, free-range, and naturally raised.',
    'herbs' => '100% natural raw honey and fresh herbs. Pure, unprocessed, and full of natural goodness.'
];

$display_name = $category_display_names[$category_name] ?? ($category_display_names[$category_type] ?? ucfirst($category_name));
$description = $category_descriptions[$category_name] ?? ($category_descriptions[$category_type] ?? 'Discover our fresh farm products.');

// Fetch approved products from database
$products_query = "SELECT
                    p.product_id,
                    p.product_name,
                    p.description,
                    p.category_id,
                    p.unit_type,
                    p.base_price,
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
                   AND (p.expires_at IS NULL OR p.expires_at > NOW())";

// Filter by category if not 'all'
$stmt = null;
if ($category_name !== 'all' && !empty($category_type)) {
    $products_query .= " AND c.category_type = ?";
    $stmt = $farmcart->conn->prepare($products_query);
    if ($stmt) {
        $stmt->bind_param("s", $category_type);
        $stmt->execute();
        $products_result = $stmt->get_result();
    } else {
        $products_result = false;
    }
} else {
    $products_result = $farmcart->conn->query($products_query);
}

$products = [];
if ($products_result) {
    while ($row = $products_result->fetch_assoc()) {
        $products[] = $row;
    }
    if ($stmt) {
        $stmt->close();
    }
}
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
                        'all' => '🛒',
                        'vegetables' => '🥦',
                        'fruits' => '🍎',
                        'poultry' => '🥚',
                        'dairy' => '🧀',
                        'livestock' => '🐄',
                        'herbs' => '🍯'
                    ];
                    echo $category_icons[$category_name] ?? ($category_icons[$category_type] ?? '📦');
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
                        <span class="stat-number"><?php 
                            $farmers_count = $farmcart->conn->query("SELECT COUNT(DISTINCT created_by) as count FROM products WHERE approval_status = 'approved' AND is_listed = TRUE AND (is_expired IS NULL OR is_expired = 0)");
                            echo $farmers_count ? $farmers_count->fetch_assoc()['count'] : 0;
                        ?>+</span>
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
                        <p class="text-muted">Showing <?php echo count($products); ?> product<?php echo count($products) != 1 ? 's' : ''; ?></p>
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
            <div class="row g-4">
                <?php if (!empty($products)): ?>
                    <?php foreach ($products as $product): ?>
                        <?php
                        $farmer_name = htmlspecialchars($product['farmer_first'] . ' ' . $product['farmer_last']);
                        $farm_name = !empty($product['farm_name']) ? htmlspecialchars($product['farm_name']) : 'No farm name';
                        
                        // Fix image path
                        $image_url = !empty($product['image_url']) ? $product['image_url'] : '';
                        if (!empty($image_url) && !preg_match('/^https?:\/\//', $image_url)) {
                            if (strpos($image_url, 'uploads/') === 0) {
                                $image_url = '../farmer/' . $image_url;
                            }
                        }
                        
                        $expires_at = !empty($product['expires_at']) ? date('M d, Y h:i A', strtotime($product['expires_at'])) : 'No expiry set';
                        ?>
                        <div class="col-md-4 col-lg-3">
                            <div class="card product-card">
                                <?php if (!empty($image_url)): ?>
                                    <img src="<?= htmlspecialchars($image_url); ?>" 
                                         class="card-img-top" 
                                         alt="<?= htmlspecialchars($product['product_name']); ?>" 
                                         style="height: 200px; object-fit: cover;" 
                                         onerror="this.src='https://via.placeholder.com/300x220?text=No+Image'">
                                <?php else: ?>
                                    <div class="card-img-top bg-light d-flex align-items-center justify-content-center" style="height: 200px;">
                                        <i class="fas fa-seedling fa-3x text-muted"></i>
                                    </div>
                                <?php endif; ?>

                                <div class="card-body">
                                    <h5 class="card-title"><?= htmlspecialchars($product['product_name']); ?></h5>

                                    <p class="text-muted mb-1">
                                        <i class="fas fa-user me-1"></i>
                                        <?= $farmer_name ?>
                                    </p>

                                    <p class="text-muted mb-1">
                                        <i class="fas fa-tractor me-1"></i>
                                        <?= $farm_name ?>
                                    </p>

                                    <p class="text-muted mb-1">
                                        <i class="fas fa-tag me-1"></i>
                                        <?= htmlspecialchars($product['category_name']); ?> (<?= htmlspecialchars($product['category_type']); ?>)
                                    </p>

                                    <p class="mb-2">
                                        <strong class="text-success fs-5">₱<?= number_format($product['base_price'], 2); ?></strong>
                                    </p>
                                    <p class="text-muted small mb-2">
                                        <i class="fas fa-weight me-1"></i>Per <?= htmlspecialchars($product['unit_type']); ?>
                                    </p>

                                    <p class="small mb-2 text-truncate-3">
                                        <?= nl2br(htmlspecialchars(substr($product['description'], 0, 150))); ?>
                                        <?= strlen($product['description']) > 150 ? '...' : ''; ?>
                                    </p>
                                    
                                    <?php if ($expires_at !== 'No expiry set'): ?>
                                        <p class="card-text small text-muted">
                                            <i class="fas fa-hourglass-half me-1"></i>
                                            Expires: <?= $expires_at ?>
                                        </p>
                                    <?php endif; ?>
                                </div>

                                <div class="card-footer bg-white border-top-0 pt-0">
                                    <div class="action-buttons d-flex flex-wrap gap-2">
                                        <button type="button" 
                                                class="btn btn-sm btn-outline-secondary" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#productDetailsModal" 
                                                data-product='<?= json_encode([
                                                    'name' => $product['product_name'],
                                                    'category' => $product['category_name'],
                                                    'category_type' => $product['category_type'],
                                                    'unit' => $product['unit_type'],
                                                    'price' => number_format($product['base_price'], 2),
                                                    'description' => $product['description'],
                                                    'expires_at' => $expires_at,
                                                ], JSON_HEX_APOS | JSON_HEX_QUOT); ?>'>
                                            <i class="fas fa-list me-1"></i>Details
                                        </button>
                                        <?php if (!empty($image_url)): ?>
                                            <button type="button" 
                                                    class="btn btn-sm btn-outline-primary" 
                                                    onclick="viewImage('<?= htmlspecialchars($image_url, ENT_QUOTES) ?>', '<?= htmlspecialchars($product['product_name'], ENT_QUOTES) ?>')">
                                                <i class="fas fa-eye me-1"></i>View Image
                                            </button>
                                        <?php endif; ?>
                                        <button class="btn btn-sm btn-success" onclick="addToCart(<?= $product['product_id'] ?>, event)">
                                            <i class="fas fa-cart-plus me-1"></i>Add to Cart
                                        </button>
                                    </div>
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

    <!-- Product Details Modal -->
    <div class="modal fade" id="productDetailsModal" tabindex="-1" aria-labelledby="productDetailsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="productDetailsModalLabel">Product Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="productDetailsContent">
                        <!-- Content will be populated by JavaScript -->
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Image View Modal -->
    <div class="modal fade" id="imageModal" tabindex="-1" aria-labelledby="imageModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="imageModalLabel">Product Image</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center">
                    <img id="modalImage" src="" alt="Product Image" class="img-fluid rounded">
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // View Image Function
        function viewImage(imageUrl, productName) {
            document.getElementById('modalImage').src = imageUrl;
            document.getElementById('imageModalLabel').textContent = productName;
            const modal = new bootstrap.Modal(document.getElementById('imageModal'));
            modal.show();
        }

        // Add to Cart Function
        function addToCart(productId, evt) {
            // Get the button that was clicked
            const btn = evt ? evt.target.closest('button') : (window.event ? window.event.target.closest('button') : null);
            if (!btn) {
                // Fallback: find button by product ID
                const buttons = document.querySelectorAll(`button[onclick*="addToCart(${productId}"]`);
                if (buttons.length > 0) {
                    btn = buttons[buttons.length - 1];
                } else {
                    console.error('Could not find button');
                    return;
                }
            }
            const originalText = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Adding...';
            
            // Send AJAX request
            const formData = new FormData();
            formData.append('product_id', productId);
            formData.append('quantity', 1);
            
            fetch('add_to_cart.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Show success message
                    btn.innerHTML = '<i class="fas fa-check me-1"></i>Added!';
                    btn.classList.remove('btn-success');
                    btn.classList.add('btn-outline-success');
                    
                    // Show toast notification
                    showToast(data.message, 'success');
                    
                    // Reset button after 2 seconds
                    setTimeout(() => {
                        btn.innerHTML = originalText;
                        btn.classList.remove('btn-outline-success');
                        btn.classList.add('btn-success');
                        btn.disabled = false;
                    }, 2000);
                } else {
                    alert(data.message || 'Failed to add product to cart.');
                    btn.innerHTML = originalText;
                    btn.disabled = false;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred. Please try again.');
                btn.innerHTML = originalText;
                btn.disabled = false;
            });
        }
        
        // Toast notification function
        function showToast(message, type = 'info') {
            const toast = document.createElement('div');
            toast.className = `alert alert-${type === 'success' ? 'success' : 'danger'} alert-dismissible fade show position-fixed`;
            toast.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
            toast.innerHTML = `
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            document.body.appendChild(toast);
            
            // Auto remove after 3 seconds
            setTimeout(() => {
                toast.remove();
            }, 3000);
        }

        // Product Details Modal
        const productDetailsModal = document.getElementById('productDetailsModal');
        if (productDetailsModal) {
            productDetailsModal.addEventListener('show.bs.modal', function(event) {
                const button = event.relatedTarget;
                const productData = JSON.parse(button.getAttribute('data-product'));
                const modalTitle = productDetailsModal.querySelector('.modal-title');
                const modalBody = productDetailsModal.querySelector('#productDetailsContent');

                modalTitle.textContent = productData.name;

                modalBody.innerHTML = `
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="fw-bold">Product Information</h6>
                            <p><strong>Name:</strong> ${productData.name}</p>
                            <p><strong>Category:</strong> ${productData.category} (${productData.category_type})</p>
                            <p><strong>Unit:</strong> ${productData.unit}</p>
                            <p><strong>Price:</strong> ₱${productData.price}</p>
                            <p><strong>Expires:</strong> ${productData.expires_at}</p>
                        </div>
                        <div class="col-md-6">
                            <h6 class="fw-bold">Description</h6>
                            <p>${productData.description.replace(/\n/g, '<br>')}</p>
                        </div>
                    </div>
                `;
            });
        }
    </script>
</body>
</html>