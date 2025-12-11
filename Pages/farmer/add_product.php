<?php
// farmer/add_product.php
include '../../db_connect.php';

// Check if user is logged in and is a farmer
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'farmer') {
    header("location: ../customer/index.php");
    exit();
}

$farmer_id = $_SESSION['user_id'];
$farmer_name = $_SESSION['first_name'] . ' ' . $_SESSION['last_name'];
$farmer_profile = $farmcart->get_farmer_profile($farmer_id);

// Calculate counts for sidebar badges
$total_products_count = 0;
$count_query = "SELECT COUNT(*) AS total FROM products WHERE created_by = ?";
$count_stmt = $farmcart->conn->prepare($count_query);
if ($count_stmt) {
    $count_stmt->bind_param("i", $farmer_id);
    $count_stmt->execute();
    $count_result = $count_stmt->get_result();
    $total_products_count = $count_result->fetch_assoc()['total'] ?? 0;
    $count_stmt->close();
}

// Fetch categories
$categories_sql = "SELECT * FROM categories WHERE is_active = TRUE ORDER BY category_name";
$categories_result = $farmcart->conn->query($categories_sql);

$error = '';
$success = '';

// Handle product creation
// Handle product creation
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
        // Insert product with pending approval status and not listed
        $sql = "INSERT INTO products (product_name, description, category_id, unit_type, base_price, created_by, approval_status, is_active, is_listed)
                VALUES (?, ?, ?, ?, ?, ?, 'pending', TRUE, FALSE)";
        $stmt = $farmcart->conn->prepare($sql);

        if ($stmt) {
            $stmt->bind_param("ssisdi", $product_name, $description, $category_id, $unit_type, $base_price, $farmer_id);

            if ($stmt->execute()) {
                $product_id = $stmt->insert_id;

                // Handle image upload
                if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
                    $uploadDir = "uploads/";
                    if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

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

                // Send notification to admin (you need to implement this function)
                sendAdminNotification($product_id, $product_name, $farmer_id, $farmer_name);

                $success = "Product submitted successfully! It is now awaiting admin approval. You can view it in 'My Products' with status 'Awaiting Approval'.";
                // Clear form
                $_POST = array();
            } else {
                $error = "Failed to create product: " . $stmt->error;
            }
            $stmt->close();
        } else {
            $error = "Failed to prepare statement: " . $farmcart->conn->error;
        }
    }
}

// Function to send notification to admin (you need to implement this based on your notification system)
function sendAdminNotification($product_id, $product_name, $farmer_id, $farmer_name) {
    global $farmcart;

    // Get all admin users
    $admin_sql = "SELECT user_id FROM users WHERE role = 'admin'";
    $admin_result = $farmcart->conn->query($admin_sql);

    while ($admin = $admin_result->fetch_assoc()) {
        $notification_sql = "INSERT INTO notifications (user_id, title, message, type, related_id, action_url)
                            VALUES (?, 'New Product Pending Approval', ?, 'approval', ?, ?)";
        $stmt = $farmcart->conn->prepare($notification_sql);

        $message = "New product '{$product_name}' added by farmer {$farmer_name} needs approval.";
        $action_url = "../admin/product_approval.php?product_id={$product_id}";

        $stmt->bind_param("issi", $admin['user_id'], $message, $product_id, $action_url);
        $stmt->execute();
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Add Product | FarmCart</title>
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
    
    .preview-image {
        max-width: 200px;
        max-height: 200px;
        border-radius: 10px;
        object-fit: cover;
        border: 3px solid #e9ecef;
    }
    
    .required-field::after {
        content: " *";
        color: #dc3545;
    }
  </style>
</head>
<body>
  <div class="dashboard-container">
    <!-- Sidebar Column -->
    <div class="sidebar-column">
      <?php 
        $sidebar_stats = [
          'total_products' => $total_products_count,
          'pending_orders' => 0,
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
              <i class="fas fa-plus-circle me-2"></i>Add New Product
            </h1>
            <p class="text-muted mb-0">Add your farm products to the marketplace.</p>
          </div>
          <div class="btn-toolbar mb-2 mb-md-0">
            <a href="products.php" class="btn btn-outline-primary me-2">
              <i class="fas fa-arrow-left me-1"></i>
              Back to Products
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

        <!-- Product Form -->
        <div class="form-card p-4">
          <form method="post" enctype="multipart/form-data" id="productForm">
            <div class="row g-4">
              <!-- Product Information -->
              <div class="col-md-8">
                <h5 class="text-success mb-4"><i class="fas fa-info-circle me-2"></i>Product Information</h5>
                
                <div class="row g-3">
                  <div class="col-md-6">
                    <label class="form-label fw-semibold required-field">Product Name</label>
                    <input type="text" name="product_name" class="form-control form-control-lg" 
                           placeholder="e.g., Organic Tomatoes" 
                           value="<?= isset($_POST['product_name']) ? htmlspecialchars($_POST['product_name']) : '' ?>" required>
                  </div>
                  
                  <div class="col-md-6">
                    <label class="form-label fw-semibold required-field">Category</label>
                    <select name="category_id" class="form-control form-control-lg" required>
                      <option value="">Select Category</option>
                      <?php while ($category = $categories_result->fetch_assoc()): ?>
                        <option value="<?= $category['category_id'] ?>" 
                                <?= (isset($_POST['category_id']) && $_POST['category_id'] == $category['category_id']) ? 'selected' : '' ?>>
                          <?= htmlspecialchars($category['category_name']) ?> (<?= ucfirst($category['category_type']) ?>)
                        </option>
                      <?php endwhile; ?>
                    </select>
                  </div>

                  <div class="col-md-6">
                    <label class="form-label fw-semibold required-field">Unit Type</label>
                    <select name="unit_type" class="form-control form-control-lg" required>
                      <option value="">Select Unit</option>
                      <option value="kg" <?= (isset($_POST['unit_type']) && $_POST['unit_type'] == 'kg') ? 'selected' : '' ?>>Kilogram (kg)</option>
                      <option value="g" <?= (isset($_POST['unit_type']) && $_POST['unit_type'] == 'g') ? 'selected' : '' ?>>Gram (g)</option>
                      <option value="piece" <?= (isset($_POST['unit_type']) && $_POST['unit_type'] == 'piece') ? 'selected' : '' ?>>Piece</option>
                      <option value="liter" <?= (isset($_POST['unit_type']) && $_POST['unit_type'] == 'liter') ? 'selected' : '' ?>>Liter</option>
                      <option value="sack" <?= (isset($_POST['unit_type']) && $_POST['unit_type'] == 'sack') ? 'selected' : '' ?>>Sack</option>
                      <option value="dozen" <?= (isset($_POST['unit_type']) && $_POST['unit_type'] == 'dozen') ? 'selected' : '' ?>>Dozen</option>
                      <option value="bunch" <?= (isset($_POST['unit_type']) && $_POST['unit_type'] == 'bunch') ? 'selected' : '' ?>>Bunch</option>
                      <option value="pack" <?= (isset($_POST['unit_type']) && $_POST['unit_type'] == 'pack') ? 'selected' : '' ?>>Pack</option>
                    </select>
                  </div>

                  <div class="col-md-6">
                    <label class="form-label fw-semibold required-field">Base Price (₱)</label>
                    <div class="input-group">
                      <span class="input-group-text">₱</span>
                      <input type="number" name="base_price" class="form-control form-control-lg" 
                             placeholder="0.00" step="0.01" min="0.01" 
                             value="<?= isset($_POST['base_price']) ? htmlspecialchars($_POST['base_price']) : '' ?>" required>
                    </div>
                  </div>

                  <div class="col-12">
                    <label class="form-label fw-semibold required-field">Product Description</label>
                    <textarea name="description" class="form-control" rows="5" 
                              placeholder="Describe your product in detail (quality, farming methods, storage instructions, etc.)..." 
                              required><?= isset($_POST['description']) ? htmlspecialchars($_POST['description']) : '' ?></textarea>
                    <div class="form-text">Provide detailed information about your product to attract customers.</div>
                  </div>
                </div>
              </div>

              <!-- Image Upload -->
              <div class="col-md-4">
                <h5 class="text-success mb-4"><i class="fas fa-image me-2"></i>Product Image</h5>
                
                <div class="text-center">
                  <div class="mb-3">
                    <img id="imagePreview" src="https://via.placeholder.com/200x200?text=Product+Image" class="preview-image" alt="Preview">
                  </div>
                  <input type="file" name="image" class="form-control" accept="image/*" onchange="previewImage(this)">
                  <div class="form-text">
                    Supported formats: JPG, JPEG, PNG, GIF<br>
                    Max size: 5MB<br>
                    <span class="text-muted">Optional but recommended</span>
                  </div>
                </div>

                <!-- Farmer Info -->
                <div class="mt-4 p-3 bg-light rounded">
                  <h6 class="fw-semibold mb-2">Farmer Information</h6>
                  <p class="mb-1"><strong>Name:</strong> <?= htmlspecialchars($farmer_name) ?></p>
                  <?php if ($farmer_profile && !empty($farmer_profile['farm_name'])): ?>
                    <p class="mb-1"><strong>Farm:</strong> <?= htmlspecialchars($farmer_profile['farm_name']) ?></p>
                  <?php endif; ?>
                  <?php if ($farmer_profile && !empty($farmer_profile['farm_location'])): ?>
                    <p class="mb-1"><strong>Location:</strong> <?= htmlspecialchars($farmer_profile['farm_location']) ?></p>
                  <?php endif; ?>
                  <p class="mb-0 text-muted small">After creating the product, you'll need to add inventory stock.</p>
                </div>

                <!-- Form Requirements -->
                <div class="mt-3 p-3 border rounded">
                  <h6 class="fw-semibold mb-2">Required Fields</h6>
                  <ul class="small mb-0">
                    <li>Product Name</li>
                    <li>Category</li>
                    <li>Unit Type</li>
                    <li>Base Price</li>
                    <li>Description</li>
                  </ul>
                </div>
              </div>
            </div>

            <!-- Submit Button -->
            <div class="row mt-4">
              <div class="col-12">
                <div class="d-flex gap-3">
                  <button type="submit" name="add_product" class="btn btn-success btn-lg px-5">
                    <i class="fas fa-save me-2"></i>
                    Create Product
                  </button>
                  <a href="products.php" class="btn btn-outline-secondary btn-lg">
                    <i class="fas fa-times me-2"></i>
                    Cancel
                  </a>
                </div>
              </div>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    function previewImage(input) {
      const preview = document.getElementById('imagePreview');
      if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
          preview.src = e.target.result;
        }
        reader.readAsDataURL(input.files[0]);
      }
    }

    // Form validation
    document.addEventListener('DOMContentLoaded', function() {
      const form = document.getElementById('productForm');
      form.addEventListener('submit', function(e) {
        const price = document.querySelector('input[name="base_price"]');
        if (price && parseFloat(price.value) <= 0) {
          e.preventDefault();
          alert('Please enter a valid price greater than 0.');
          price.focus();
        }
      });
    });
  </script>
</body>
</html>
