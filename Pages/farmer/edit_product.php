<?php
// farmer/edit_product.php
include '../../db_connect.php';

// Check if user is logged in and is a farmer
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'farmer') {
    header("location: ../customer/index.php");
    exit();
}

$farmer_id = $_SESSION['user_id'];
$farmer_name = $_SESSION['first_name'] . ' ' . $_SESSION['last_name'];
$farmer_profile = $farmcart->get_farmer_profile($farmer_id);

// Ensure expiration columns exist (safe inline migration)
function ensure_product_expiration_columns($conn) {
    $columns = [
        'expiration_duration_seconds' => "INT NULL DEFAULT NULL",
        'expires_at' => "DATETIME NULL DEFAULT NULL",
        'is_expired' => "TINYINT(1) NOT NULL DEFAULT 0",
        'approved_at' => "DATETIME NULL DEFAULT NULL"
    ];
    foreach ($columns as $name => $definition) {
        $check = $conn->query("SHOW COLUMNS FROM products LIKE '{$name}'");
        if ($check && $check->num_rows === 0) {
            $conn->query("ALTER TABLE products ADD COLUMN {$name} {$definition}");
        }
    }
}
ensure_product_expiration_columns($farmcart->conn);

// Get product ID from URL
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['edit_error'] = "Product ID is required.";
    header("location: products.php");
    exit();
}

$product_id = (int)$_GET['id'];

// Fetch product data
$product_sql = "SELECT p.*, c.category_name, pi.image_url 
                FROM products p
                LEFT JOIN categories c ON p.category_id = c.category_id
                LEFT JOIN product_images pi ON p.product_id = pi.product_id AND pi.is_primary = TRUE
                WHERE p.product_id = ? AND p.created_by = ?";
$stmt = $farmcart->conn->prepare($product_sql);
$stmt->bind_param("ii", $product_id, $farmer_id);
$stmt->execute();
$product_result = $stmt->get_result();
$product = $product_result->fetch_assoc();
$stmt->close();

if (!$product) {
    $_SESSION['edit_error'] = "Product not found or you don't have permission to edit it.";
    header("location: products.php");
    exit();
}

// Check if approval_status column exists
$check_column = $farmcart->conn->query("SHOW COLUMNS FROM products LIKE 'approval_status'");
$has_approval_status = $check_column && $check_column->num_rows > 0;

// Only allow editing if product is pending (awaiting approval)
if ($has_approval_status && isset($product['approval_status']) && $product['approval_status'] !== 'pending') {
    $_SESSION['edit_error'] = "You can only edit products that are awaiting approval. Approved or rejected products cannot be edited.";
    header("location: products.php");
    exit();
}

// Calculate total products count for sidebar
$count_query = "SELECT COUNT(*) as total FROM products WHERE created_by = ?";
$count_stmt = $farmcart->conn->prepare($count_query);
if ($count_stmt) {
    $count_stmt->bind_param("i", $farmer_id);
    $count_stmt->execute();
    $count_result = $count_stmt->get_result();
    $total_products_count = $count_result->fetch_assoc()['total'] ?? 0;
    $count_stmt->close();
} else {
    $total_products_count = 0;
}

// Fetch categories
$categories_sql = "SELECT * FROM categories WHERE is_active = TRUE ORDER BY category_name";
$categories_result = $farmcart->conn->query($categories_sql);

$error = '';
$success = '';
$existing_duration = isset($product['expiration_duration_seconds']) ? (int)$product['expiration_duration_seconds'] : 0;
$prefill_months = intdiv($existing_duration, 2592000); // 30 days month
$remaining = $existing_duration - ($prefill_months * 2592000);
$prefill_days = intdiv($remaining, 86400);
$remaining -= ($prefill_days * 86400);
$prefill_hours = intdiv($remaining, 3600);
$remaining -= ($prefill_hours * 3600);
$prefill_seconds = $remaining;

// Handle product update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_product'])) {
    // Verify product is still pending and belongs to farmer
    $verify_sql = "SELECT approval_status FROM products WHERE product_id = ? AND created_by = ?";
    $verify_stmt = $farmcart->conn->prepare($verify_sql);
    $verify_stmt->bind_param("ii", $product_id, $farmer_id);
    $verify_stmt->execute();
    $verify_result = $verify_stmt->get_result();
    $verify_product = $verify_result->fetch_assoc();
    $verify_stmt->close();

    if (!$verify_product) {
        $error = "Product not found or you don't have permission to edit it.";
    } elseif ($has_approval_status && $verify_product['approval_status'] !== 'pending') {
        $error = "This product can no longer be edited. Only products awaiting approval can be modified.";
    } else {
        $product_name = $farmcart->conn->real_escape_string($_POST['product_name']);
        $category_id = (int)$_POST['category_id'];
        $description = $farmcart->conn->real_escape_string($_POST['description']);
        $unit_type = $farmcart->conn->real_escape_string($_POST['unit_type']);
        $base_price = floatval($_POST['base_price']);
        $exp_months = isset($_POST['exp_months']) ? max(0, (int)$_POST['exp_months']) : 0;
        $exp_days = isset($_POST['exp_days']) ? max(0, (int)$_POST['exp_days']) : 0;
        $exp_hours = isset($_POST['exp_hours']) ? max(0, (int)$_POST['exp_hours']) : 0;
        $exp_seconds = isset($_POST['exp_seconds']) ? max(0, (int)$_POST['exp_seconds']) : 0;
        $expiration_duration_seconds = (($exp_months * 30 + $exp_days) * 24 * 3600) + ($exp_hours * 3600) + $exp_seconds;

        // Validate required fields
        if (empty($product_name) || empty($category_id) || empty($unit_type) || $base_price <= 0) {
            $error = "Please fill in all required fields with valid values.";
        } elseif ($expiration_duration_seconds <= 0) {
            $error = "Please provide an expiration duration greater than zero.";
        } else {
            // Update product (keep approval_status as pending)
            $update_sql = "UPDATE products SET 
                          product_name = ?, 
                          description = ?, 
                          category_id = ?, 
                          unit_type = ?, 
                          base_price = ?,
                          expiration_duration_seconds = ?,
                          expires_at = NULL,
                          is_expired = 0,
                          approved_at = NULL,
                          approval_status = 'pending',
                          is_listed = FALSE
                          WHERE product_id = ? AND created_by = ?";
            $update_stmt = $farmcart->conn->prepare($update_sql);

            if ($update_stmt) {
                // Types: s s i s d i i i (8 params)
                $update_stmt->bind_param("ssisdiii", $product_name, $description, $category_id, $unit_type, $base_price, $expiration_duration_seconds, $product_id, $farmer_id);

                if ($update_stmt->execute()) {
                    // Handle image upload if new image is provided
                    if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
                        $uploadDir = "uploads/";
                        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

                        $origName = basename($_FILES['image']['tmp_name']);
                        $safeName = time() . "_" . preg_replace('/[^A-Za-z0-9._-]/', '_', basename($_FILES['image']['name']));
                        $targetFile = $uploadDir . $safeName;

                        $imageFileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));
                        $allowed = ['jpg','jpeg','png','gif'];

                        $check = getimagesize($_FILES['image']['tmp_name']);
                        if ($check !== false && in_array($imageFileType, $allowed)) {
                            if (move_uploaded_file($_FILES['image']['tmp_name'], $targetFile)) {
                                // Delete old primary image
                                $delete_old_sql = "DELETE FROM product_images WHERE product_id = ? AND is_primary = TRUE";
                                $del_stmt = $farmcart->conn->prepare($delete_old_sql);
                                $del_stmt->bind_param("i", $product_id);
                                $del_stmt->execute();
                                $del_stmt->close();

                                // Insert new product image
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

                    // Refresh product data after update
                    $refresh_sql = "SELECT p.*, c.category_name, pi.image_url 
                                    FROM products p
                                    LEFT JOIN categories c ON p.category_id = c.category_id
                                    LEFT JOIN product_images pi ON p.product_id = pi.product_id AND pi.is_primary = TRUE
                                    WHERE p.product_id = ? AND p.created_by = ?";
                    $refresh_stmt = $farmcart->conn->prepare($refresh_sql);
                    $refresh_stmt->bind_param("ii", $product_id, $farmer_id);
                    $refresh_stmt->execute();
                    $refresh_result = $refresh_stmt->get_result();
                    $product = $refresh_result->fetch_assoc();
                    $refresh_stmt->close();

                    $success = "Product updated successfully! It is now awaiting admin approval again.";
                } else {
                    $error = "Failed to update product: " . $update_stmt->error;
                }
                $update_stmt->close();
            } else {
                $error = "Failed to prepare statement: " . $farmcart->conn->error;
            }
        }
    }
}

// Reset categories result pointer for form
$categories_result = $farmcart->conn->query($categories_sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Edit Product | FarmCart</title>
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
              <i class="fas fa-edit me-2"></i>Edit Product
            </h1>
            <p class="text-muted mb-0">Update your product information.</p>
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
                           value="<?= htmlspecialchars($product['product_name']) ?>" required>
                  </div>
                  
                  <div class="col-md-6">
                    <label class="form-label fw-semibold required-field">Category</label>
                    <select name="category_id" class="form-control form-control-lg" required>
                      <option value="">Select Category</option>
                      <?php while ($category = $categories_result->fetch_assoc()): ?>
                        <option value="<?= $category['category_id'] ?>" 
                                <?= ($product['category_id'] == $category['category_id']) ? 'selected' : '' ?>>
                          <?= htmlspecialchars($category['category_name']) ?> (<?= ucfirst($category['category_type']) ?>)
                        </option>
                      <?php endwhile; ?>
                    </select>
                  </div>

                  <div class="col-md-6">
                    <label class="form-label fw-semibold required-field">Unit Type</label>
                    <select name="unit_type" class="form-control form-control-lg" required>
                      <option value="">Select Unit</option>
                      <option value="kg" <?= ($product['unit_type'] == 'kg') ? 'selected' : '' ?>>Kilogram (kg)</option>
                      <option value="g" <?= ($product['unit_type'] == 'g') ? 'selected' : '' ?>>Gram (g)</option>
                      <option value="piece" <?= ($product['unit_type'] == 'piece') ? 'selected' : '' ?>>Piece</option>
                      <option value="liter" <?= ($product['unit_type'] == 'liter') ? 'selected' : '' ?>>Liter</option>
                      <option value="sack" <?= ($product['unit_type'] == 'sack') ? 'selected' : '' ?>>Sack</option>
                      <option value="dozen" <?= ($product['unit_type'] == 'dozen') ? 'selected' : '' ?>>Dozen</option>
                      <option value="bunch" <?= ($product['unit_type'] == 'bunch') ? 'selected' : '' ?>>Bunch</option>
                      <option value="pack" <?= ($product['unit_type'] == 'pack') ? 'selected' : '' ?>>Pack</option>
                    </select>
                  </div>

                  <div class="col-md-6">
                    <label class="form-label fw-semibold required-field">Base Price (₱)</label>
                    <div class="input-group">
                      <span class="input-group-text">₱</span>
                      <input type="number" name="base_price" class="form-control form-control-lg" 
                             placeholder="0.00" step="0.01" min="0.01" 
                             value="<?= htmlspecialchars($product['base_price']) ?>" required>
                    </div>
                  </div>

                  <div class="col-12">
                    <label class="form-label fw-semibold required-field">Expiration Duration</label>
                    <div class="row g-2">
                      <div class="col-6 col-md-3">
                        <div class="input-group input-group-sm">
                          <span class="input-group-text">Months</span>
                          <input type="number" name="exp_months" min="0" class="form-control" value="<?= $prefill_months ?>">
                        </div>
                      </div>
                      <div class="col-6 col-md-3">
                        <div class="input-group input-group-sm">
                          <span class="input-group-text">Days</span>
                          <input type="number" name="exp_days" min="0" class="form-control" value="<?= $prefill_days ?>">
                        </div>
                      </div>
                      <div class="col-6 col-md-3">
                        <div class="input-group input-group-sm">
                          <span class="input-group-text">Hours</span>
                          <input type="number" name="exp_hours" min="0" class="form-control" value="<?= $prefill_hours ?>">
                        </div>
                      </div>
                      <div class="col-6 col-md-3">
                        <div class="input-group input-group-sm">
                          <span class="input-group-text">Seconds</span>
                          <input type="number" name="exp_seconds" min="0" class="form-control" value="<?= $prefill_seconds ?>">
                        </div>
                      </div>
                    </div>
                    <div class="form-text">Product will expire after admin approval using this duration (months counted as 30 days).</div>
                  </div>

                  <div class="col-12">
                    <label class="form-label fw-semibold required-field">Product Description</label>
                    <textarea name="description" class="form-control" rows="5" 
                              placeholder="Describe your product in detail (quality, farming methods, storage instructions, etc.)..." 
                              required><?= htmlspecialchars($product['description']) ?></textarea>
                    <div class="form-text">Provide detailed information about your product to attract customers.</div>
                  </div>
                </div>
              </div>

              <!-- Image Upload -->
              <div class="col-md-4">
                <h5 class="text-success mb-4"><i class="fas fa-image me-2"></i>Product Image</h5>
                
                <div class="text-center">
                  <div class="mb-3">
                    <?php if (!empty($product['image_url'])): ?>
                      <img id="imagePreview" src="<?= htmlspecialchars($product['image_url']) ?>" class="preview-image" alt="Current Image">
                    <?php else: ?>
                      <img id="imagePreview" src="https://via.placeholder.com/200x200?text=Product+Image" class="preview-image" alt="Preview">
                    <?php endif; ?>
                  </div>
                  <input type="file" name="image" id="imageInput" class="form-control" accept="image/jpeg,image/jpg,image/png,image/gif" onchange="previewImage(this)">
                  <div class="form-text">
                    Supported formats: JPG, JPEG, PNG, GIF<br>
                    Max size: 5MB<br>
                    <span class="text-muted">Leave empty to keep current image</span>
                  </div>
                  <div id="fileError" class="text-danger mt-2" style="display: none;">
                    <i class="fas fa-exclamation-circle me-1"></i>
                    <span id="fileErrorText">Unsupported file format. Please select JPG, JPEG, PNG, or GIF.</span>
                  </div>
                </div>

                <!-- Status Info -->
                <div class="mt-4 p-3 bg-warning bg-opacity-10 rounded border border-warning">
                  <h6 class="fw-semibold mb-2"><i class="fas fa-info-circle me-1"></i>Note</h6>
                  <p class="mb-0 small">After editing, this product will need admin approval again.</p>
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
                    <li>Expiration Duration</li>
                  </ul>
                </div>
              </div>
            </div>

            <!-- Submit Button -->
            <div class="row mt-4">
              <div class="col-12">
                <div class="d-flex gap-3">
                  <button type="submit" name="update_product" class="btn btn-success btn-lg px-5">
                    <i class="fas fa-save me-2"></i>
                    Update Product
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
      const errorDiv = document.getElementById('fileError');
      const errorText = document.getElementById('fileErrorText');
      
      // Hide error initially
      errorDiv.style.display = 'none';
      
      if (input.files && input.files[0]) {
        const file = input.files[0];
        const fileName = file.name.toLowerCase();
        const fileExtension = fileName.split('.').pop();
        
        // Allowed file types
        const allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
        const allowedMimeTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
        
        // Check file extension
        const isValidExtension = allowedExtensions.includes(fileExtension);
        
        // Check MIME type
        const isValidMimeType = allowedMimeTypes.includes(file.type);
        
        // Check file size (5MB = 5 * 1024 * 1024 bytes)
        const maxSize = 5 * 1024 * 1024; // 5MB in bytes
        const isValidSize = file.size <= maxSize;
        
        if (!isValidExtension || !isValidMimeType) {
          // Unsupported format
          errorText.textContent = 'Unsupported file format. Please select JPG, JPEG, PNG, or GIF.';
          errorDiv.style.display = 'block';
          input.value = ''; // Clear the input
          preview.src = '<?= !empty($product['image_url']) ? htmlspecialchars($product['image_url'], ENT_QUOTES) : "https://via.placeholder.com/200x200?text=Product+Image" ?>'; // Reset preview
          return false;
        } else if (!isValidSize) {
          // File too large
          errorText.textContent = 'File size exceeds 5MB. Please select a smaller file.';
          errorDiv.style.display = 'block';
          input.value = ''; // Clear the input
          preview.src = '<?= !empty($product['image_url']) ? htmlspecialchars($product['image_url'], ENT_QUOTES) : "https://via.placeholder.com/200x200?text=Product+Image" ?>'; // Reset preview
          return false;
        } else {
          // Valid file - show preview
          const reader = new FileReader();
          reader.onload = function(e) {
            preview.src = e.target.result;
          };
          reader.readAsDataURL(file);
        }
      } else {
        // No file selected - reset to current image
        preview.src = '<?= !empty($product['image_url']) ? htmlspecialchars($product['image_url'], ENT_QUOTES) : "https://via.placeholder.com/200x200?text=Product+Image" ?>';
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
