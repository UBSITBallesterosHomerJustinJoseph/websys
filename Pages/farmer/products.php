<?php
// farmer/my_products.php
include '../../db_connect.php';

// Check if user is logged in and is a farmer
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'farmer') {
    header("location: ../customer/index.php");
    exit();
}

$farmer_id = $_SESSION['user_id'];
$farmer_name = $_SESSION['first_name'] . ' ' . $_SESSION['last_name'];
$farmer_profile = $farmcart->get_farmer_profile($farmer_id);

// Ensure expiration columns exist and mark expired products
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
$farmcart->conn->query("UPDATE products SET is_expired = 1, is_listed = 0 WHERE (is_expired IS NULL OR is_expired = 0) AND expires_at IS NOT NULL AND expires_at <= NOW()");

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

// Handle product deletion
if (isset($_GET['delete']) && $_GET['delete'] > 0) {
    $delete_id = (int)$_GET['delete'];
    
    // Verify product belongs to this farmer
    $verify_sql = "SELECT product_id FROM products WHERE product_id = ? AND created_by = ?";
    $verify_stmt = $farmcart->conn->prepare($verify_sql);
    $verify_stmt->bind_param("ii", $delete_id, $farmer_id);
    $verify_stmt->execute();
    $verify_result = $verify_stmt->get_result();
    
    if ($verify_result->num_rows > 0) {
        // Delete product images first
        $delete_images_sql = "DELETE FROM product_images WHERE product_id = ?";
        $del_img_stmt = $farmcart->conn->prepare($delete_images_sql);
        $del_img_stmt->bind_param("i", $delete_id);
        $del_img_stmt->execute();
        $del_img_stmt->close();
        
        // Delete product
        $delete_sql = "DELETE FROM products WHERE product_id = ? AND created_by = ?";
        $del_stmt = $farmcart->conn->prepare($delete_sql);
        $del_stmt->bind_param("ii", $delete_id, $farmer_id);
        
        if ($del_stmt->execute()) {
            $_SESSION['delete_success'] = "Product deleted successfully!";
            header("Location: products.php");
            exit();
        } else {
            $_SESSION['delete_error'] = "Failed to delete product: " . $del_stmt->error;
        }
        $del_stmt->close();
    } else {
        $_SESSION['delete_error'] = "Product not found or you don't have permission to delete it.";
    }
    $verify_stmt->close();
    
    header("Location: products.php");
    exit();
}

// Fetch farmer's products with approval status
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';
$where_clauses = ["p.created_by = ?"];

if ($status_filter === 'expired') {
    $where_clauses[] = "(p.is_expired = 1)";
} else {
    $where_clauses[] = "(p.is_expired IS NULL OR p.is_expired = 0)";
    if (in_array($status_filter, ['pending', 'approved', 'rejected'])) {
        $where_clauses[] = "p.approval_status = '" . $farmcart->conn->real_escape_string($status_filter) . "'";
    }
}

$products_sql = "SELECT
                    p.*,
                    c.category_name,
                    pi.image_url,
                    COALESCE(p.quantity, 0) as quantity,
                    CASE
                        WHEN p.is_expired = 1 THEN 'Expired'
                        WHEN p.approval_status = 'pending' THEN 'Awaiting Approval'
                        WHEN p.approval_status = 'approved' THEN 'Approved'
                        WHEN p.approval_status = 'rejected' THEN 'Rejected'
                        ELSE 'Unknown'
                    END as approval_text,
                    CASE
                        WHEN p.is_expired = 1 THEN 'secondary'
                        WHEN p.approval_status = 'pending' THEN 'warning'
                        WHEN p.approval_status = 'approved' THEN 'success'
                        WHEN p.approval_status = 'rejected' THEN 'danger'
                        ELSE 'secondary'
                    END as approval_badge
                 FROM products p
                 LEFT JOIN categories c ON p.category_id = c.category_id
                 LEFT JOIN product_images pi ON p.product_id = pi.product_id AND pi.is_primary = TRUE
                 WHERE " . implode(' AND ', $where_clauses) . "
                 ORDER BY p.created_at DESC";

$stmt = $farmcart->conn->prepare($products_sql);
$stmt->bind_param("i", $farmer_id);
$stmt->execute();
$products_result = $stmt->get_result();
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
        padding: 0.25em 0.5em;
        border-radius: 0.25rem;
    }

    .status-warning {
        background-color: #ffc107;
        color: #000;
    }

    .status-success {
        background-color: #198754;
        color: #fff;
    }

    .status-danger {
        background-color: #dc3545;
        color: #fff;
    }

    .status-secondary {
        background-color: #6c757d;
        color: #fff;
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
                            <i class="fas fa-seedling me-2"></i>
                            My Products
                        </h1>
                        <p class="text-muted mb-0">Manage your farm products</p>
                    </div>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="add_product.php" class="btn btn-success">
                            <i class="fas fa-plus me-2"></i>Add New Product
                        </a>
                    </div>
                </div>

                <!-- Error/Success Messages -->
                <?php if (isset($_SESSION['delete_success'])): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <i class="fas fa-check-circle me-2"></i>
                        <?= htmlspecialchars($_SESSION['delete_success']) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php unset($_SESSION['delete_success']); ?>
                <?php endif; ?>

                <?php if (isset($_SESSION['delete_error'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <?= htmlspecialchars($_SESSION['delete_error']) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php unset($_SESSION['delete_error']); ?>
                <?php endif; ?>

                <?php if (isset($_SESSION['edit_error'])): ?>
                    <div class="alert alert-warning alert-dismissible fade show">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <?= htmlspecialchars($_SESSION['edit_error']) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php unset($_SESSION['edit_error']); ?>
                <?php endif; ?>

                <!-- Status Filters -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="btn-group" role="group">
                            <a href="products.php?status=all" class="btn btn-outline-secondary <?= (!isset($_GET['status']) || $_GET['status'] == 'all') ? 'active' : '' ?>">All</a>
                            <a href="products.php?status=pending" class="btn btn-outline-warning <?= (isset($_GET['status']) && $_GET['status'] == 'pending') ? 'active' : '' ?>">Awaiting Approval</a>
                            <a href="products.php?status=approved" class="btn btn-outline-success <?= (isset($_GET['status']) && $_GET['status'] == 'approved') ? 'active' : '' ?>">Approved</a>
                            <a href="products.php?status=rejected" class="btn btn-outline-danger <?= (isset($_GET['status']) && $_GET['status'] == 'rejected') ? 'active' : '' ?>">Rejected</a>
                            <a href="products.php?status=expired" class="btn btn-outline-secondary <?= (isset($_GET['status']) && $_GET['status'] == 'expired') ? 'active' : '' ?>">Expired</a>
                        </div>
                    </div>
                </div>

                <!-- Products Grid -->
                <div class="row">
                    <?php if ($products_result->num_rows > 0): ?>
                        <?php while ($product = $products_result->fetch_assoc()): ?>
                            <?php $status_key = (!empty($product['is_expired']) && $product['is_expired']) ? 'expired' : strtolower($product['approval_status'] ?? ''); ?>
                            <div class="col-md-4 mb-4 product-card-wrapper" data-status="<?= $status_key ?>">
                                <div class="card product-card h-100">
                                    <!-- Product Image -->
                                    <?php if (!empty($product['image_url'])): ?>
                                        <img src="<?= htmlspecialchars($product['image_url']) ?>"
                                             class="card-img-top product-image"
                                             alt="<?= htmlspecialchars($product['product_name']) ?>">
                                    <?php else: ?>
                                        <div class="card-img-top product-image bg-light d-flex align-items-center justify-content-center">
                                            <i class="fas fa-seedling fa-3x text-muted"></i>
                                        </div>
                                    <?php endif; ?>

                                    <div class="card-body">
                                        <!-- Approval Status Badge -->
                                        <div class="mb-2">
                                            <span class="badge status-badge status-<?= $product['approval_badge'] ?>">
                                                <?= $product['approval_text'] ?>
                                            </span>
                                        </div>

                                        <!-- Product Name -->
                                        <h5 class="card-title"><?= htmlspecialchars($product['product_name']) ?></h5>

                                        <!-- Category -->
                                        <p class="card-text text-muted small mb-1">
                                            <i class="fas fa-tag me-1"></i>
                                            <?= htmlspecialchars($product['category_name']) ?>
                                        </p>

                                        <!-- Price -->
                                        <p class="card-text">
                                            <strong>₱<?= number_format($product['base_price'], 2) ?></strong>
                                            <span class="text-muted">per <?= $product['unit_type'] ?></span>
                                        </p>
                                        
                                        <!-- Stock Quantity -->
                                        <p class="card-text">
                                            <i class="fas fa-box me-1"></i>
                                            <strong>Stock:</strong> 
                                            <span class="badge bg-<?= ($product['quantity'] ?? 0) > 0 ? 'success' : 'danger'; ?>">
                                                <?= number_format($product['quantity'] ?? 0, 0); ?> <?= $product['unit_type'] ?>
                                            </span>
                                        </p>

                                        <!-- Description Preview -->
                                        <p class="card-text small text-truncate" style="max-height: 60px; overflow: hidden;">
                                            <?= htmlspecialchars(substr($product['description'], 0, 100)) ?>
                                            <?= strlen($product['description']) > 100 ? '...' : '' ?>
                                        </p>

                                        <!-- Admin Notes (if rejected) -->
                                        <?php if ($product['approval_status'] == 'rejected' && !empty($product['admin_notes'])): ?>
                                            <div class="alert alert-danger small p-2 mt-2">
                                                <strong><i class="fas fa-exclamation-circle me-1"></i>Rejection Reason:</strong>
                                                <div class="mt-1"><?= nl2br(htmlspecialchars($product['admin_notes'])) ?></div>
                                            </div>
                                        <?php endif; ?>

                                        <!-- Created Date -->
                                        <p class="card-text small text-muted">
                                            <i class="far fa-clock me-1"></i>
                                            Added: <?= date('M d, Y', strtotime($product['created_at'])) ?>
                                        </p>
                                        <p class="card-text small text-muted">
                                            <i class="fas fa-hourglass-half me-1"></i>
                                            <?php if (!empty($product['expires_at'])): ?>
                                                Expires: <?= date('M d, Y h:i A', strtotime($product['expires_at'])) ?>
                                            <?php else: ?>
                                                No expiry set
                                            <?php endif; ?>
                                        </p>
                                    </div>

                                    <div class="card-footer bg-white border-top-0">
                                        <div class="d-flex justify-content-between flex-wrap gap-2">
                                            <!-- View Image Button -->
                                            <?php if (!empty($product['image_url'])): ?>
                                                <button type="button" class="btn btn-sm btn-outline-primary"
                                                        onclick="viewImage('<?= htmlspecialchars($product['image_url']) ?>', '<?= htmlspecialchars($product['product_name']) ?>')">
                                                    <i class="fas fa-eye me-1"></i>View Image
                                                </button>
                                            <?php else: ?>
                                                <span class="btn btn-sm btn-outline-secondary disabled">
                                                    <i class="fas fa-image me-1"></i>No Image
                                                </span>
                                            <?php endif; ?>

                                            <!-- Edit Button (only for pending products) -->
                                            <?php
                                            $check_column = $farmcart->conn->query("SHOW COLUMNS FROM products LIKE 'approval_status'");
                                            $has_approval_status = $check_column && $check_column->num_rows > 0;
                                            $is_expired = !empty($product['is_expired']);
                                            $can_edit = !$is_expired && (!$has_approval_status || ($product['approval_status'] ?? '') == 'pending');
                                            $is_rejected = !$is_expired && $has_approval_status && ($product['approval_status'] ?? '') == 'rejected';
                                            ?>
                                            <?php if ($is_expired): ?>
                                                <span class="btn btn-sm btn-outline-secondary disabled" title="Expired products cannot be edited">
                                                    <i class="fas fa-edit me-1"></i>Edit
                                                </span>
                                            <?php elseif ($can_edit): ?>
                                                <a href="edit_product.php?id=<?= $product['product_id'] ?>"
                                                   class="btn btn-sm btn-outline-warning">
                                                    <i class="fas fa-edit me-1"></i>Edit
                                                </a>
                                            <?php elseif ($is_rejected): ?>
                                                <a href="declined_edit_product.php?id=<?= $product['product_id'] ?>"
                                                   class="btn btn-sm btn-outline-warning">
                                                    <i class="fas fa-redo me-1"></i>Resubmit
                                                </a>
                                            <?php else: ?>
                                                <span class="btn btn-sm btn-outline-secondary disabled" title="Only pending or rejected products can be edited">
                                                    <i class="fas fa-edit me-1"></i>Edit
                                                </span>
                                            <?php endif; ?>

                                            <!-- Delete Button (available; only action for expired) -->
                                            <button type="button" class="btn btn-sm btn-outline-danger"
                                                    onclick="confirmDelete(<?= $product['product_id'] ?>, '<?= htmlspecialchars(addslashes($product['product_name'])) ?>')">
                                                <i class="fas fa-trash me-1"></i>Delete
                                            </button>

                                            <!-- Add Stock Button (only if approved and not expired) -->
                                            <?php if (!$is_expired && $has_approval_status && ($product['approval_status'] ?? '') == 'approved'): ?>
                                                <button type="button" 
                                                        class="btn btn-sm btn-success"
                                                        onclick="openAddStockModal(<?= $product['product_id'] ?>, '<?= htmlspecialchars(addslashes($product['product_name'])) ?>', <?= (int)($product['quantity'] ?? 0) ?>)">
                                                    <i class="fas fa-box me-1"></i>Add Stock
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="col-12">
                            <div class="text-center py-5">
                                <i class="fas fa-seedling fa-4x text-muted mb-3"></i>
                                <h3 class="text-muted">No Products Yet</h3>
                                <p class="text-muted">You haven't added any products to your farm yet.</p>
                                <a href="add_product.php" class="btn btn-success btn-lg">
                                    <i class="fas fa-plus me-2"></i>Add Your First Product
                                </a>
                            </div>
                        </div>
                    <?php endif; ?>
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

    <!-- Add Stock Modal -->
    <div class="modal fade" id="addStockModal" tabindex="-1" aria-labelledby="addStockModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title" id="addStockModalLabel">
                        <i class="fas fa-box me-2"></i>Add Stock
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="addStockForm">
                        <input type="hidden" id="stock_product_id" name="product_id">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Product</label>
                            <input type="text" id="stock_product_name" class="form-control" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Current Stock</label>
                            <input type="text" id="stock_current_qty" class="form-control" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Quantity to Add <span class="text-danger">*</span></label>
                            <input type="number" id="stock_quantity" name="quantity" class="form-control form-control-lg" 
                                   min="1" step="1" required placeholder="Enter quantity">
                            <div class="form-text">Enter whole numbers only (e.g., 10, 50, 100)</div>
                        </div>
                        <div id="stockMessage" class="alert d-none"></div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-success" onclick="submitAddStock()">
                        <i class="fas fa-plus me-2"></i>Add Stock
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // View Image Function
        function viewImage(imageUrl, productName) {
            document.getElementById('modalImage').src = imageUrl;
            document.getElementById('imageModalLabel').textContent = productName;
            const modal = new bootstrap.Modal(document.getElementById('imageModal'));
            modal.show();
        }

        // Confirm Delete Function
        function confirmDelete(productId, productName) {
            if (confirm('Are you sure you want to delete "' + productName + '"? This action cannot be undone.')) {
                window.location.href = 'products.php?delete=' + productId;
            }
        }

        // Filter products by status
        const urlParams = new URLSearchParams(window.location.search);
        const status = urlParams.get('status');

        if (status && status !== 'all') {
            document.querySelectorAll('.product-card-wrapper').forEach(wrapper => {
                const productStatus = wrapper.getAttribute('data-status');
                if (productStatus === status) {
                    wrapper.style.display = 'block';
                } else {
                    wrapper.style.display = 'none';
                }
            });
        } else {
            // Show all products
            document.querySelectorAll('.product-card-wrapper').forEach(wrapper => {
                wrapper.style.display = 'block';
            });
        }

        // Add Stock Modal Functions
        function openAddStockModal(productId, productName, currentQty) {
            document.getElementById('stock_product_id').value = productId;
            document.getElementById('stock_product_name').value = productName;
            document.getElementById('stock_current_qty').value = currentQty + ' units';
            document.getElementById('stock_quantity').value = '';
            document.getElementById('stockMessage').classList.add('d-none');
            const modal = new bootstrap.Modal(document.getElementById('addStockModal'));
            modal.show();
        }

        function submitAddStock() {
            const productId = document.getElementById('stock_product_id').value;
            const quantity = parseInt(document.getElementById('stock_quantity').value);
            const messageDiv = document.getElementById('stockMessage');
            const submitBtn = event.target;
            
            if (!quantity || quantity <= 0) {
                messageDiv.className = 'alert alert-danger';
                messageDiv.textContent = 'Please enter a valid quantity greater than 0.';
                messageDiv.classList.remove('d-none');
                return;
            }
            
            if (!Number.isInteger(quantity)) {
                messageDiv.className = 'alert alert-danger';
                messageDiv.textContent = 'Quantity must be a whole number.';
                messageDiv.classList.remove('d-none');
                return;
            }
            
            // Disable button during request
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Adding...';
            
            const formData = new FormData();
            formData.append('product_id', productId);
            formData.append('quantity', quantity);
            
            fetch('add_stock.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    messageDiv.className = 'alert alert-success';
                    messageDiv.textContent = data.message;
                    messageDiv.classList.remove('d-none');
                    
                    // Update the page after 1.5 seconds
                    setTimeout(() => {
                        location.reload();
                    }, 1500);
                } else {
                    messageDiv.className = 'alert alert-danger';
                    messageDiv.textContent = data.message || 'Failed to add stock.';
                    messageDiv.classList.remove('d-none');
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<i class="fas fa-plus me-2"></i>Add Stock';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                messageDiv.className = 'alert alert-danger';
                messageDiv.textContent = 'An error occurred. Please try again.';
                messageDiv.classList.remove('d-none');
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-plus me-2"></i>Add Stock';
            });
        }
    </script>
</body>
</html>
<?php $stmt->close(); ?>
