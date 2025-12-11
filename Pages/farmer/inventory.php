<?php
// farmer/inventory.php
include '../../db_connect.php';

// Check if user is logged in and is a farmer
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'farmer') {
    header("location: ../customer/index.php");
    exit();
}

$farmer_id = $_SESSION['user_id'];

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

$error = '';
$inventory_result = null;

try {
    // First, let's check if the required tables exist and debug the query
    $check_tables = $farmcart->conn->query("SHOW TABLES LIKE 'inventory_lots'");
    if ($check_tables->num_rows == 0) {
        throw new Exception("Inventory table not found. Please check your database setup.");
    }

    // Fetch inventory lots with error handling
    $inventory_sql = "SELECT il.*, p.product_name, p.product_image, c.category_name
                     FROM inventory_lots il
                     JOIN products p ON il.product_id = p.product_id
                     LEFT JOIN categories c ON p.category_id = c.category_id
                     WHERE il.farmer_id = ?
                     ORDER BY il.created_at DESC";
    
    $inventory_stmt = $farmcart->conn->prepare($inventory_sql);
    
    if (!$inventory_stmt) {
        throw new Exception("Failed to prepare statement: " . $farmcart->conn->error);
    }
    
    $inventory_stmt->bind_param("i", $farmer_id);
    
    if (!$inventory_stmt->execute()) {
        throw new Exception("Failed to execute query: " . $inventory_stmt->error);
    }
    
    $inventory_result = $inventory_stmt->get_result();
    
    if (!$inventory_result) {
        throw new Exception("Failed to get result: " . $inventory_stmt->error);
    }

} catch (Exception $e) {
    $error = $e->getMessage();
    // Fallback: Try a simpler query to see what data we have
    $simple_query = $farmcart->conn->query("
        SELECT 'inventory_lots' as table_name, COUNT(*) as count FROM inventory_lots 
        UNION ALL 
        SELECT 'products' as table_name, COUNT(*) as count FROM products
        UNION ALL
        SELECT 'categories' as table_name, COUNT(*) as count FROM categories
    ");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Inventory & Stocks | FarmCart</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <style>
    body { background-color: #f8f9fa; margin: 0; padding: 0; overflow-x: hidden; }
    .dashboard-container { display: flex; min-height: 100vh; }
    .sidebar-column { width: 280px; min-width: 280px; background: linear-gradient(180deg, #4E653D 0%, #3a5230 100%); position: fixed; left: 0; top: 0; height: 100vh; overflow-y: auto; z-index: 1000; box-shadow: 2px 0 10px rgba(0,0,0,0.1); }
    .main-content-column { flex: 1; margin-left: 280px; min-height: 100vh; padding: 0; }
    .content-area { padding: 30px; min-height: 100vh; background: #f8f9fa; }
    .form-card { background: white; border-radius: 15px; box-shadow: 0 5px 25px rgba(0,0,0,0.08); border: none; }
    .product-image { width: 50px; height: 50px; object-fit: cover; border-radius: 8px; }
  </style>
</head>
<body>
  <div class="dashboard-container">
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
    <div class="main-content-column">
      <div class="content-area">
        <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-4 mb-4 border-bottom">
          <div>
            <h1 class="h2 text-success fw-bold"><i class="fas fa-warehouse me-2"></i>Inventory & Stocks</h1>
            <p class="text-muted mb-0">Manage your product inventory and stock levels.</p>
          </div>
        </div>

        <?php if (!empty($error)): ?>
          <div class="alert alert-danger alert-dismissible fade show">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <strong>Database Error:</strong> <?= htmlspecialchars($error) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            
            <?php if (isset($simple_query)): ?>
              <div class="mt-3">
                <h6>Database Table Check:</h6>
                <table class="table table-sm table-bordered">
                  <thead>
                    <tr>
                      <th>Table Name</th>
                      <th>Record Count</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php while ($row = $simple_query->fetch_assoc()): ?>
                      <tr>
                        <td><?= htmlspecialchars($row['table_name']) ?></td>
                        <td><?= $row['count'] ?></td>
                      </tr>
                    <?php endwhile; ?>
                  </tbody>
                </table>
              </div>
            <?php endif; ?>
          </div>
        <?php endif; ?>

        <?php if (empty($error) && $inventory_result): ?>
          <div class="row mb-4">
            <div class="col-md-3">
              <div class="card bg-primary text-white">
                <div class="card-body">
                  <div class="d-flex justify-content-between">
                    <div>
                      <?php
                      $total_items = 0;
                      $inventory_result->data_seek(0);
                      while ($item = $inventory_result->fetch_assoc()) {
                          $total_items += $item['available_quantity'];
                      }
                      $inventory_result->data_seek(0);
                      ?>
                      <h3 class="fw-bold"><?= $total_items ?></h3>
                      <p class="mb-0">Total Items</p>
                    </div>
                    <div class="align-self-center">
                      <i class="fas fa-boxes fa-2x"></i>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <div class="col-md-3">
              <div class="card bg-success text-white">
                <div class="card-body">
                  <div class="d-flex justify-content-between">
                    <div>
                      <?php
                      $low_stock_count = 0;
                      $inventory_result->data_seek(0);
                      while ($item = $inventory_result->fetch_assoc()) {
                          if ($item['available_quantity'] > 0 && $item['available_quantity'] < 10) {
                              $low_stock_count++;
                          }
                      }
                      $inventory_result->data_seek(0);
                      ?>
                      <h3 class="fw-bold"><?= $low_stock_count ?></h3>
                      <p class="mb-0">Low Stock</p>
                    </div>
                    <div class="align-self-center">
                      <i class="fas fa-exclamation-triangle fa-2x"></i>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <div class="col-md-3">
              <div class="card bg-warning text-white">
                <div class="card-body">
                  <div class="d-flex justify-content-between">
                    <div>
                      <?php
                      $out_of_stock_count = 0;
                      $inventory_result->data_seek(0);
                      while ($item = $inventory_result->fetch_assoc()) {
                          if ($item['available_quantity'] == 0) {
                              $out_of_stock_count++;
                          }
                      }
                      $inventory_result->data_seek(0);
                      ?>
                      <h3 class="fw-bold"><?= $out_of_stock_count ?></h3>
                      <p class="mb-0">Out of Stock</p>
                    </div>
                    <div class="align-self-center">
                      <i class="fas fa-times-circle fa-2x"></i>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <div class="col-md-3">
              <div class="card bg-info text-white">
                <div class="card-body">
                  <div class="d-flex justify-content-between">
                    <div>
                      <?php
                      $active_products = 0;
                      $inventory_result->data_seek(0);
                      while ($item = $inventory_result->fetch_assoc()) {
                          if ($item['status'] == 'active') {
                              $active_products++;
                          }
                      }
                      $inventory_result->data_seek(0);
                      ?>
                      <h3 class="fw-bold"><?= $active_products ?></h3>
                      <p class="mb-0">Active Lots</p>
                    </div>
                    <div class="align-self-center">
                      <i class="fas fa-check-circle fa-2x"></i>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="form-card p-4">
            <div class="table-responsive">
              <table class="table table-striped">
                <thead>
                  <tr>
                    <th>Product</th>
                    <th>Category</th>
                    <th>Lot Number</th>
                    <th>Quantity</th>
                    <th>Status</th>
                    <th>Harvest Date</th>
                    <th>Expiry Date</th>
                  </tr>
                </thead>
                <tbody>
                  <?php while ($item = $inventory_result->fetch_assoc()): ?>
                    <tr>
                      <td>
                        <div class="d-flex align-items-center">
                          <?php if (!empty($item['product_image'])): ?>
                            <img src="../<?= $item['product_image'] ?>" class="product-image me-2" alt="<?= htmlspecialchars($item['product_name']) ?>">
                          <?php else: ?>
                            <div class="product-image bg-light d-flex align-items-center justify-content-center me-2">
                              <i class="fas fa-image text-muted"></i>
                            </div>
                          <?php endif; ?>
                          <?= htmlspecialchars($item['product_name']) ?>
                        </div>
                      </td>
                      <td><?= htmlspecialchars($item['category_name'] ?? 'Uncategorized') ?></td>
                      <td><?= htmlspecialchars($item['lot_number'] ?? 'N/A') ?></td>
                      <td>
                        <span class="badge bg-<?= 
                          ($item['available_quantity'] ?? 0) == 0 ? 'danger' : 
                          (($item['available_quantity'] ?? 0) < 10 ? 'warning' : 'success')
                        ?>">
                          <?= $item['available_quantity'] ?? 0 ?>
                        </span>
                      </td>
                      <td>
                        <span class="badge bg-<?= ($item['status'] ?? '') == 'active' ? 'success' : 'secondary' ?>">
                          <?= ucfirst($item['status'] ?? 'unknown') ?>
                        </span>
                      </td>
                      <td><?= !empty($item['harvest_date']) ? date('M d, Y', strtotime($item['harvest_date'])) : 'N/A' ?></td>
                      <td><?= !empty($item['expiry_date']) ? date('M d, Y', strtotime($item['expiry_date'])) : 'N/A' ?></td>
                    </tr>
                  <?php endwhile; ?>
                </tbody>
              </table>
              
              <?php if ($inventory_result->num_rows == 0): ?>
                <div class="text-center py-5">
                  <i class="fas fa-warehouse fa-4x text-muted mb-3"></i>
                  <h4 class="text-muted">No Inventory Items Found</h4>
                  <p class="text-muted">Your inventory will appear here once you add products.</p>
                  <a href="add_product.php" class="btn btn-success">
                    <i class="fas fa-plus me-2"></i>Add Your First Product
                  </a>
                </div>
              <?php endif; ?>
            </div>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>