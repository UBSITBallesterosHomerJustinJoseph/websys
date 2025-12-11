<?php
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

// Get date range from request or default to last 30 days
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-30 days'));
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

// Sales analytics
$sales_sql = "SELECT DATE(o.created_at) as date, SUM(oi.subtotal) as revenue, SUM(oi.quantity) as items_sold
              FROM orders o
              JOIN order_items oi ON o.order_id = oi.order_id
              JOIN inventory_lots il ON oi.lot_id = il.lot_id
              WHERE il.farmer_id = ? 
              AND o.status = 'delivered'
              AND DATE(o.created_at) BETWEEN ? AND ?
              GROUP BY DATE(o.created_at)
              ORDER BY date";
$sales_stmt = $farmcart->conn->prepare($sales_sql);
$sales_stmt->bind_param("iss", $farmer_id, $start_date, $end_date);
$sales_stmt->execute();
$sales_result = $sales_stmt->get_result();

// Top products
$top_products_sql = "SELECT p.product_name, SUM(oi.quantity) as total_sold, 
                     SUM(oi.subtotal) as total_revenue
                     FROM products p 
                     JOIN inventory_lots il ON p.product_id = il.product_id 
                     JOIN order_items oi ON il.lot_id = oi.lot_id 
                     JOIN orders o ON oi.order_id = o.order_id 
                     WHERE p.created_by = ? AND o.status = 'delivered'
                     AND DATE(o.created_at) BETWEEN ? AND ?
                     GROUP BY p.product_id 
                     ORDER BY total_sold DESC 
                     LIMIT 10";
$top_products_stmt = $farmcart->conn->prepare($top_products_sql);
$top_products_stmt->bind_param("iss", $farmer_id, $start_date, $end_date);
$top_products_stmt->execute();
$top_products_result = $top_products_stmt->get_result();

// Total revenue
$total_revenue_sql = "SELECT COALESCE(SUM(oi.subtotal), 0) as total_revenue
                      FROM order_items oi
                      JOIN inventory_lots il ON oi.lot_id = il.lot_id
                      JOIN orders o ON oi.order_id = o.order_id
                      WHERE il.farmer_id = ? 
                      AND o.status = 'delivered'
                      AND DATE(o.created_at) BETWEEN ? AND ?";
$total_revenue_stmt = $farmcart->conn->prepare($total_revenue_sql);
$total_revenue_stmt->bind_param("iss", $farmer_id, $start_date, $end_date);
$total_revenue_stmt->execute();
$total_revenue_result = $total_revenue_stmt->get_result();
$total_revenue = $total_revenue_result->fetch_assoc()['total_revenue'];

// Total orders
$total_orders_sql = "SELECT COUNT(DISTINCT o.order_id) as total_orders
                     FROM orders o
                     JOIN order_items oi ON o.order_id = oi.order_id
                     JOIN inventory_lots il ON oi.lot_id = il.lot_id
                     WHERE il.farmer_id = ? 
                     AND o.status = 'delivered'
                     AND DATE(o.created_at) BETWEEN ? AND ?";
$total_orders_stmt = $farmcart->conn->prepare($total_orders_sql);
$total_orders_stmt->bind_param("iss", $farmer_id, $start_date, $end_date);
$total_orders_stmt->execute();
$total_orders_result = $total_orders_stmt->get_result();
$total_orders = $total_orders_result->fetch_assoc()['total_orders'];

// Set sidebar stats before including sidebar (around line 97)
$sidebar_stats = [
    'total_products' => $total_products_count,
    'pending_orders' => 0,
    'low_stock' => 0
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Analytics | FarmCart</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <style>
    body { background-color: #f8f9fa; margin: 0; padding: 0; overflow-x: hidden; }
    .dashboard-container { display: flex; min-height: 100vh; }
    .sidebar-column { width: 280px; min-width: 280px; background: linear-gradient(180deg, #4E653D 0%, #3a5230 100%); position: fixed; left: 0; top: 0; height: 100vh; overflow-y: auto; z-index: 1000; box-shadow: 2px 0 10px rgba(0,0,0,0.1); }
    .main-content-column { flex: 1; margin-left: 280px; min-height: 100vh; padding: 0; }
    .content-area { padding: 30px; min-height: 100vh; background: #f8f9fa; }
    .form-card { background: white; border-radius: 15px; box-shadow: 0 5px 25px rgba(0,0,0,0.08); border: none; }
    .chart-container { position: relative; height: 300px; width: 100%; }
  </style>
</head>
<body>
  <div class="dashboard-container">
    <div class="sidebar-column">
      <?php include '../../Includes/sidebar.php'; ?>
    </div>
    <div class="main-content-column">
      <div class="content-area">
        <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-4 mb-4 border-bottom">
          <div>
            <h1 class="h2 text-success fw-bold"><i class="fas fa-chart-line me-2"></i>Analytics</h1>
            <p class="text-muted mb-0">Track your sales performance and product analytics.</p>
          </div>
        </div>

        <!-- Date Filter -->
        <div class="form-card p-4 mb-4">
          <form method="get" class="row g-3">
            <div class="col-md-4">
              <label class="form-label fw-semibold">Start Date</label>
              <input type="date" name="start_date" class="form-control" value="<?= $start_date ?>" required>
            </div>
            <div class="col-md-4">
              <label class="form-label fw-semibold">End Date</label>
              <input type="date" name="end_date" class="form-control" value="<?= $end_date ?>" required>
            </div>
            <div class="col-md-4">
              <label class="form-label">&nbsp;</label>
              <button type="submit" class="btn btn-success w-100">
                <i class="fas fa-filter me-2"></i>Apply Filter
              </button>
            </div>
          </form>
        </div>

        <!-- Summary Cards -->
        <div class="row mb-4">
          <div class="col-md-4">
            <div class="card bg-primary text-white">
              <div class="card-body">
                <div class="d-flex justify-content-between">
                  <div>
                    <h3 class="fw-bold">₱<?= number_format($total_revenue, 2) ?></h3>
                    <p class="mb-0">Total Revenue</p>
                  </div>
                  <div class="align-self-center">
                    <i class="fas fa-money-bill-wave fa-2x"></i>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="col-md-4">
            <div class="card bg-success text-white">
              <div class="card-body">
                <div class="d-flex justify-content-between">
                  <div>
                    <h3 class="fw-bold"><?= $total_orders ?></h3>
                    <p class="mb-0">Total Orders</p>
                  </div>
                  <div class="align-self-center">
                    <i class="fas fa-shopping-cart fa-2x"></i>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="col-md-4">
            <div class="card bg-info text-white">
              <div class="card-body">
                <div class="d-flex justify-content-between">
                  <div>
                    <h3 class="fw-bold"><?= $top_products_result->num_rows ?></h3>
                    <p class="mb-0">Products Sold</p>
                  </div>
                  <div class="align-self-center">
                    <i class="fas fa-box fa-2x"></i>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="row">
          <!-- Sales Chart -->
          <div class="col-lg-8 mb-4">
            <div class="form-card p-4 h-100">
              <h5 class="text-success mb-4"><i class="fas fa-chart-bar me-2"></i>Sales Trend</h5>
              <div class="chart-container">
                <canvas id="salesChart"></canvas>
              </div>
            </div>
          </div>

          <!-- Top Products -->
          <div class="col-lg-4 mb-4">
            <div class="form-card p-4 h-100">
              <h5 class="text-success mb-4"><i class="fas fa-star me-2"></i>Top Products</h5>
              <div class="table-responsive">
                <table class="table table-sm">
                  <thead>
                    <tr>
                      <th>Product</th>
                      <th>Sold</th>
                      <th>Revenue</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php while ($product = $top_products_result->fetch_assoc()): ?>
                      <tr>
                        <td class="text-truncate" style="max-width: 120px;" title="<?= htmlspecialchars($product['product_name']) ?>">
                          <?= htmlspecialchars($product['product_name']) ?>
                        </td>
                        <td><?= number_format($product['total_sold']) ?></td>
                        <td>₱<?= number_format($product['total_revenue'], 2) ?></td>
                      </tr>
                    <?php endwhile; ?>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script>
    // Sales Chart
    const salesCtx = document.getElementById('salesChart').getContext('2d');
    const salesChart = new Chart(salesCtx, {
        type: 'line',
        data: {
            labels: [
                <?php 
                $sales_result->data_seek(0);
                $labels = [];
                while ($sale = $sales_result->fetch_assoc()) {
                    $labels[] = "'" . date('M d', strtotime($sale['date'])) . "'";
                }
                echo implode(', ', $labels);
                ?>
            ],
            datasets: [{
                label: 'Daily Revenue (₱)',
                data: [
                    <?php 
                    $sales_result->data_seek(0);
                    $data = [];
                    while ($sale = $sales_result->fetch_assoc()) {
                        $data[] = $sale['revenue'];
                    }
                    echo implode(', ', $data);
                    ?>
                ],
                borderColor: '#28a745',
                backgroundColor: 'rgba(40, 167, 69, 0.1)',
                borderWidth: 2,
                fill: true,
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        drawBorder: false
                    }
                },
                x: {
                    grid: {
                        display: false
                    }
                }
            }
        }
    });
  </script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
