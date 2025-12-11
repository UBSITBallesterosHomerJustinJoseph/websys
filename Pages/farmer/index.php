<?php
// Farmer/index.php
include '../../db_connect.php';

// Check if user is logged in and is a farmer
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'farmer') {
    header("location: ../customer/index.php");
    exit();
}

$farmer_id = $_SESSION['user_id'];
$farmer_name = $_SESSION['first_name'] . ' ' . $_SESSION['last_name'];
$farmer_profile = $farmcart->get_farmer_profile($farmer_id);

// Ensure uploads folder exists
$uploadDir = "uploads/";
if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

$error = '';
$success = '';

// HANDLE PRODUCT CREATION
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_product'])) {
    $product_name = $farmcart->conn->real_escape_string($_POST['product_name']);
    $category_id = (int)$_POST['category_id'];
    $description = $farmcart->conn->real_escape_string($_POST['description']);
    $unit_type = $farmcart->conn->real_escape_string($_POST['unit_type']);
    $base_price = floatval($_POST['base_price']);
    
    // Validate required fields
    if (empty($product_name) || empty($category_id) || empty($unit_type) || $base_price <= 0) {
        $error = "Please fill in all required fields with valid values.";
    } else {
        // Insert product
        $sql = "INSERT INTO products (product_name, description, category_id, unit_type, base_price, created_by, is_active) 
                VALUES (?, ?, ?, ?, ?, ?, TRUE)";
        $stmt = $farmcart->conn->prepare($sql);
        
        if ($stmt) {
            $stmt->bind_param("sisdsi", $product_name, $description, $category_id, $unit_type, $base_price, $farmer_id);
            
            if ($stmt->execute()) {
                $product_id = $stmt->insert_id;
                
                // Handle image upload
                if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
                    $origName = basename($_FILES['image']['name']);
                    $safeName = time() . "_" . preg_replace('/[^A-Za-z0-9._-]/', '_', $origName);
                    $targetFile = $uploadDir . $safeName;

                    $imageFileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));
                    $allowed = ['jpg','jpeg','png','gif'];

                    $check = getimagesize($_FILES['image']['tmp_name']);
                    if ($check !== false && in_array($imageFileType, $allowed)) {
                        if (move_uploaded_file($_FILES['image']['tmp_name'], $targetFile)) {
                            // Insert product image
                            $image_sql = "INSERT INTO product_images (product_id, image_url, is_primary, uploaded_by) VALUES (?, ?, TRUE, ?)";
                            $image_stmt = $farmcart->conn->prepare($image_sql);
                            if ($image_stmt) {
                                $image_stmt->bind_param("isi", $product_id, $targetFile, $farmer_id);
                                $image_stmt->execute();
                                $image_stmt->close();
                            }
                        }
                    }
                }
                
                $success = "Product created successfully! You can now add inventory for this product.";
                $stmt->close();
            } else {
                $error = "Failed to create product: " . $stmt->error;
            }
        } else {
            $error = "Failed to prepare statement: " . $farmcart->conn->error;
        }
    }
}

// HANDLE INVENTORY ADDITION
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_inventory'])) {
    $product_id = (int)$_POST['product_id'];
    $quantity = floatval($_POST['quantity']);
    $available_quantity = floatval($_POST['available_quantity']);
    $harvest_date = $farmcart->conn->real_escape_string($_POST['harvest_date']);
    $expiry_date = $farmcart->conn->real_escape_string($_POST['expiry_date']);
    $origin_location = $farmcart->conn->real_escape_string($_POST['origin_location']);
    $cost_price = floatval($_POST['cost_price']);
    $selling_price = floatval($_POST['selling_price']);
    $quality_grade = $farmcart->conn->real_escape_string($_POST['quality_grade']);
    $is_organic = isset($_POST['is_organic']) ? 1 : 0;
    $certification_details = $farmcart->conn->real_escape_string($_POST['certification_details']);
    
    // Generate lot number
    $lot_number = "LOT_" . time() . "_" . $product_id;
    
    // Insert inventory lot
    $sql = "INSERT INTO inventory_lots (product_id, farmer_id, lot_number, quantity, available_quantity, harvest_date, expiry_date, origin_location, cost_price, selling_price, quality_grade, is_organic, certification_details, status) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')";
    $stmt = $farmcart->conn->prepare($sql);
    
    if ($stmt) {
        $stmt->bind_param("iisddsssddsiss", $product_id, $farmer_id, $lot_number, $quantity, $available_quantity, $harvest_date, $expiry_date, $origin_location, $cost_price, $selling_price, $quality_grade, $is_organic, $certification_details);
        
        if ($stmt->execute()) {
            $success = "Inventory lot added successfully! Waiting for admin approval.";
        } else {
            $error = "Failed to add inventory: " . $stmt->error;
        }
        $stmt->close();
    } else {
        $error = "Failed to prepare statement: " . $farmcart->conn->error;
    }
}

// FETCH farmer's products
$products_sql = "SELECT p.*, c.category_name, c.category_type,
                (SELECT image_url FROM product_images WHERE product_id = p.product_id AND is_primary = TRUE LIMIT 1) as primary_image
                FROM products p 
                JOIN categories c ON p.category_id = c.category_id 
                WHERE p.created_by = ? AND p.is_active = TRUE 
                ORDER BY p.created_at DESC";
$products_stmt = $farmcart->conn->prepare($products_sql);
$products_result = null;
$total_products = 0;

if ($products_stmt) {
    $products_stmt->bind_param("i", $farmer_id);
    $products_stmt->execute();
    $products_result = $products_stmt->get_result();
    $total_products = $products_result->num_rows;
}

// FETCH farmer's inventory lots with status counts
$inventory_sql = "SELECT il.*, p.product_name, c.category_name,
                  COUNT(*) as total_lots,
                  SUM(CASE WHEN il.status = 'pending' THEN 1 ELSE 0 END) as pending_count,
                  SUM(CASE WHEN il.status = 'approved' THEN 1 ELSE 0 END) as approved_count,
                  SUM(CASE WHEN il.status = 'rejected' THEN 1 ELSE 0 END) as rejected_count,
                  SUM(CASE WHEN il.status = 'available' THEN 1 ELSE 0 END) as available_count
                  FROM inventory_lots il
                  JOIN products p ON il.product_id = p.product_id
                  JOIN categories c ON p.category_id = c.category_id
                  WHERE il.farmer_id = ?
                  GROUP BY il.product_id
                  ORDER BY il.created_at DESC";
$inventory_stmt = $farmcart->conn->prepare($inventory_sql);
$inventory_result = null;
$total_inventory = 0;
$pending_count = 0;
$approved_count = 0;

if ($inventory_stmt) {
    $inventory_stmt->bind_param("i", $farmer_id);
    $inventory_stmt->execute();
    $inventory_result = $inventory_stmt->get_result();
    
    // Calculate total stats
    $stats_sql = "SELECT 
                  COUNT(*) as total_lots,
                  SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                  SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
                  SUM(CASE WHEN status = 'available' THEN 1 ELSE 0 END) as available,
                  SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected
                  FROM inventory_lots WHERE farmer_id = ?";
    $stats_stmt = $farmcart->conn->prepare($stats_sql);
    $stats_stmt->bind_param("i", $farmer_id);
    $stats_stmt->execute();
    $stats_result = $stats_stmt->get_result();
    $stats = $stats_result->fetch_assoc();
    
    $total_inventory = $stats['total_lots'] ?? 0;
    $pending_count = $stats['pending'] ?? 0;
    $approved_count = $stats['approved'] ?? 0;
    $available_count = $stats['available'] ?? 0;
    $rejected_count = $stats['rejected'] ?? 0;
}

// FETCH categories for dropdown
$categories_sql = "SELECT * FROM categories WHERE is_active = TRUE ORDER BY category_name";
$categories_result = $farmcart->conn->query($categories_sql);

// FETCH recent orders
$orders_sql = "SELECT o.*, SUM(oi.quantity) as total_quantity, SUM(oi.subtotal) as order_total
               FROM orders o
               JOIN order_items oi ON o.order_id = oi.order_id
               JOIN inventory_lots il ON oi.lot_id = il.lot_id
               WHERE il.farmer_id = ?
               GROUP BY o.order_id
               ORDER BY o.created_at DESC
               LIMIT 5";
$orders_stmt = $farmcart->conn->prepare($orders_sql);
$orders_result = null;

if ($orders_stmt) {
    $orders_stmt->bind_param("i", $farmer_id);
    $orders_stmt->execute();
    $orders_result = $orders_stmt->get_result();
}

// Check for success message from redirect
if (isset($_GET['success'])) {
    $success = htmlspecialchars(urldecode($_GET['success']));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Farmer Dashboard | FarmCart</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <style>
    body {
        background-color: #f8f9fa;
        margin: 0;
        padding: 0;
        overflow-x: hidden;
    }
    
    .dashboard-container {
        display: flex;
        min-height: 100vh;
    }
    
    .sidebar-column {
        width: 280px;
        min-width: 280px;
        background: linear-gradient(180deg, #4E653D 0%, #3a5230 100%);
        position: fixed;
        left: 0;
        top: 0;
        height: 100vh;
        overflow-y: auto;
        z-index: 1000;
        box-shadow: 2px 0 10px rgba(0,0,0,0.1);
    }
    
    .main-content-column {
        flex: 1;
        margin-left: 280px;
        min-height: 100vh;
        padding: 0;
    }
    
    .content-area {
        padding: 30px;
        min-height: 100vh;
        background: #f8f9fa;
    }
    
    .form-card {
        background: white;
        border-radius: 15px;
        box-shadow: 0 5px 25px rgba(0,0,0,0.08);
        border: none;
    }
    
    .stats-card {
        border-radius: 15px;
        border: none;
        color: white;
        transition: transform 0.2s;
        height: 100%;
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    }
    
    .stats-card:hover {
        transform: translateY(-5px);
    }
    
    .product-image {
        width: 100%;
        height: 200px;
        object-fit: cover;
        border-radius: 10px 10px 0 0;
    }
    
    .required-field::after {
        content: " *";
        color: #dc3545;
    }
    
    .status-badge {
        font-size: 0.75em;
    }
  </style>
</head>
<body>
  <div class="dashboard-container">
    <!-- Sidebar Column -->
    <div class="sidebar-column">
      <?php 
      $sidebar_stats = [
          'total_products' => $total_products,
          'pending_orders' => $pending_count,
          'low_stock' => 0
      ];
      include '../../Includes/sidebar.php'; 
      ?>
    </div>

    <!-- Main Content Column -->
    <div class="main-content-column">
      <div class="content-area">
        <!-- Header -->
        <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-4 mb-4 border-bottom">
          <div>
            <h1 class="h2 text-success fw-bold">
              <i class="fas fa-tachometer-alt me-2"></i>Farmer Dashboard
            </h1>
            <p class="text-muted mb-0">Welcome back, <?= htmlspecialchars($farmer_name) ?>! Manage your farm products here.</p>
          </div>
          <div class="btn-toolbar mb-2 mb-md-0">
            <a href="../customer/index.php" class="btn btn-outline-success me-2">
              <i class="fas fa-store me-1"></i>
              Visit Store
            </a>
            <a href="add_product.php" class="btn btn-success">
              <i class="fas fa-plus me-1"></i>
              Add Product
            </a>
          </div>
        </div>

        <?php if (!empty($error)): ?>
          <div class="alert alert-danger alert-dismissible fade show">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <?= htmlspecialchars($error) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>
        <?php endif; ?>
        
        <?php if (!empty($success)): ?>
          <div class="alert alert-success alert-dismissible fade show">
            <i class="fas fa-check-circle me-2"></i>
            <?= htmlspecialchars($success) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>
        <?php endif; ?>

        <!-- Quick Stats -->
        <div class="row mb-4">
          <div class="col-xl-3 col-md-6 mb-3">
            <div class="card stats-card bg-primary">
              <div class="card-body">
                <div class="d-flex justify-content-between">
                  <div>
                    <h3 class="card-title fw-bold"><?= $total_products ?></h3>
                    <p class="card-text mb-0">Total Products</p>
                  </div>
                  <div class="align-self-center">
                    <i class="fas fa-box fa-2x"></i>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="col-xl-3 col-md-6 mb-3">
            <div class="card stats-card bg-warning">
              <div class="card-body">
                <div class="d-flex justify-content-between">
                  <div>
                    <h3 class="card-title fw-bold"><?= $pending_count ?></h3>
                    <p class="card-text mb-0">Pending Approval</p>
                  </div>
                  <div class="align-self-center">
                    <i class="fas fa-clock fa-2x"></i>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="col-xl-3 col-md-6 mb-3">
            <div class="card stats-card bg-success">
              <div class="card-body">
                <div class="d-flex justify-content-between">
                  <div>
                    <h3 class="card-title fw-bold"><?= $available_count ?></h3>
                    <p class="card-text mb-0">Available Stock</p>
                  </div>
                  <div class="align-self-center">
                    <i class="fas fa-check-circle fa-2x"></i>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="col-xl-3 col-md-6 mb-3">
            <div class="card stats-card bg-info">
              <div class="card-body">
                <div class="d-flex justify-content-between">
                  <div>
                    <h3 class="card-title fw-bold"><?= $total_inventory ?></h3>
                    <p class="card-text mb-0">Total Lots</p>
                  </div>
                  <div class="align-self-center">
                    <i class="fas fa-warehouse fa-2x"></i>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="row">
          <!-- Recent Products -->
          <div class="col-lg-8">
            <div class="form-card p-4 mb-4">
              <div class="d-flex justify-content-between align-items-center mb-4">
                <h4 class="text-success mb-0">
                  <i class="fas fa-boxes me-2"></i>My Products
                </h4>
                <a href="products.php" class="btn btn-outline-primary">
                  <i class="fas fa-list me-1"></i>View All
                </a>
              </div>

              <?php if ($products_result && $products_result->num_rows > 0): ?>
                <div class="row g-3">
                  <?php while ($product = $products_result->fetch_assoc()): ?>
                    <div class="col-md-6">
                      <div class="card h-100 shadow-sm">
                        <?php if (!empty($product['primary_image'])): ?>
                          <img src="<?= htmlspecialchars($product['primary_image']); ?>" class="product-image" alt="<?= htmlspecialchars($product['product_name']); ?>">
                        <?php else: ?>
                          <div class="product-image bg-light d-flex align-items-center justify-content-center">
                            <i class="fas fa-image fa-3x text-muted"></i>
                          </div>
                        <?php endif; ?>
                        <div class="card-body">
                          <h5 class="card-title"><?= htmlspecialchars($product['product_name']); ?></h5>
                          <p class="text-muted mb-2">
                            <strong>Category:</strong> <?= htmlspecialchars($product['category_name']); ?><br>
                            <strong>Price:</strong> ₱<?= number_format($product['base_price'], 2); ?> per <?= htmlspecialchars($product['unit_type']); ?>
                          </p>
                          <p class="small text-muted mb-3"><?= nl2br(htmlspecialchars($product['description'])); ?></p>
                          
                          <div class="d-flex gap-2">
                            <a href="edit_product.php?id=<?= $product['product_id']; ?>" class="btn btn-sm btn-outline-primary flex-fill">
                              <i class="fas fa-edit me-1"></i>Edit
                            </a>
                            <a href="add_inventory.php?product_id=<?= $product['product_id']; ?>" class="btn btn-sm btn-outline-success">
                              <i class="fas fa-plus me-1"></i>Add Stock
                            </a>
                          </div>
                        </div>
                      </div>
                    </div>
                  <?php endwhile; ?>
                </div>
              <?php else: ?>
                <div class="text-center py-4">
                  <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                  <h4 class="text-muted">No Products Yet</h4>
                  <p class="text-muted mb-4">Start by adding your first farm product.</p>
                  <a href="add_product.php" class="btn btn-success">
                    <i class="fas fa-plus me-2"></i>Add Your First Product
                  </a>
                </div>
              <?php endif; ?>
            </div>
          </div>

          <!-- Recent Activity -->
          <div class="col-lg-4">
            <!-- Recent Orders -->
            <div class="form-card p-4 mb-4">
              <h5 class="text-success mb-3">
                <i class="fas fa-shopping-cart me-2"></i>Recent Orders
              </h5>
              
              <?php if ($orders_result && $orders_result->num_rows > 0): ?>
                <div class="list-group list-group-flush">
                  <?php while ($order = $orders_result->fetch_assoc()): ?>
                    <div class="list-group-item px-0">
                      <div class="d-flex justify-content-between align-items-start">
                        <div>
                          <h6 class="mb-1">Order #<?= $order['order_id']; ?></h6>
                          <small class="text-muted">
                            ₱<?= number_format($order['order_total'], 2); ?> • 
                            <?= date('M d', strtotime($order['created_at'])); ?>
                          </small>
                        </div>
                        <span class="badge bg-<?= 
                          $order['status'] == 'delivered' ? 'success' : 
                          ($order['status'] == 'pending' ? 'warning' : 'primary')
                        ?> status-badge">
                          <?= ucfirst($order['status']); ?>
                        </span>
                      </div>
                    </div>
                  <?php endwhile; ?>
                </div>
              <?php else: ?>
                <div class="text-center py-3">
                  <i class="fas fa-shopping-cart fa-2x text-muted mb-2"></i>
                  <p class="text-muted mb-0">No recent orders</p>
                </div>
              <?php endif; ?>
            </div>

            <!-- Quick Actions -->
            <div class="form-card p-4">
              <h5 class="text-success mb-3">
                <i class="fas fa-bolt me-2"></i>Quick Actions
              </h5>
              <div class="d-grid gap-2">
                <a href="add_product.php" class="btn btn-success">
                  <i class="fas fa-plus me-2"></i>Add New Product
                </a>
                <a href="inventory.php" class="btn btn-outline-primary">
                  <i class="fas fa-warehouse me-2"></i>Manage Inventory
                </a>
                <a href="profile.php" class="btn btn-outline-secondary">
                  <i class="fas fa-user-edit me-2"></i>Update Profile
                </a>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>