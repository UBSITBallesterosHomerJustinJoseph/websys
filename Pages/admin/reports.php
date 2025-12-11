<?php
// Admin/reports.php
include '../../db_connect.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("location: ../customer/index.php");
    exit();
}

$admin_id = $_SESSION['user_id'];

// Helper to get counts for sidebar badges
function admin_get_count($conn, $sql) {
    $result = $conn->query($sql);
    if ($result && $row = $result->fetch_assoc()) {
        return (int)($row['count'] ?? 0);
    }
    return 0;
}

// Sidebar stats so badges remain visible on this page
$stats = [
    'pending' => admin_get_count($farmcart->conn, "SELECT COUNT(*) as count FROM products WHERE approval_status = 'pending'"),
    'approved' => admin_get_count($farmcart->conn, "SELECT COUNT(*) as count FROM products WHERE approval_status = 'approved'"),
    'declined' => admin_get_count($farmcart->conn, "SELECT COUNT(*) as count FROM products WHERE approval_status = 'rejected'"),
    'unverified_farmers' => admin_get_count($farmcart->conn, "SELECT COUNT(*) as count FROM farmer_profiles WHERE is_verified_farmer = 0"),
    'categories' => admin_get_count($farmcart->conn, "SELECT COUNT(*) as count FROM categories"),
    'users' => admin_get_count($farmcart->conn, "SELECT COUNT(*) as count FROM users")
];

// Get date range filters (default to last 30 days)
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-30 days'));
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

// Calculate statistics
// Total Revenue
$revenue_query = "SELECT COALESCE(SUM(total_amount), 0) as total_revenue 
                  FROM orders 
                  WHERE status != 'cancelled' 
                  AND payment_status = 'paid'
                  AND DATE(created_at) BETWEEN ? AND ?";
$stmt = $farmcart->conn->prepare($revenue_query);
$stmt->bind_param("ss", $start_date, $end_date);
$stmt->execute();
$revenue_result = $stmt->get_result();
$total_revenue = $revenue_result->fetch_assoc()['total_revenue'];
$stmt->close();

// Total Orders
$orders_query = "SELECT COUNT(*) as total_orders 
                 FROM orders 
                 WHERE DATE(created_at) BETWEEN ? AND ?";
$stmt = $farmcart->conn->prepare($orders_query);
$stmt->bind_param("ss", $start_date, $end_date);
$stmt->execute();
$orders_result = $stmt->get_result();
$total_orders = $orders_result->fetch_assoc()['total_orders'];
$stmt->close();

// Total Users
$users_query = "SELECT COUNT(*) as total_users FROM users";
$users_result = $farmcart->conn->query($users_query);
$total_users = $users_result->fetch_assoc()['total_users'];

// Total Products
$products_query = "SELECT COUNT(*) as total_products FROM products WHERE is_active = 1";
$products_result = $farmcart->conn->query($products_query);
$total_products = $products_result->fetch_assoc()['total_products'];

// Total Farmers
$farmers_query = "SELECT COUNT(*) as total_farmers FROM users WHERE role = 'farmer'";
$farmers_result = $farmcart->conn->query($farmers_query);
$total_farmers = $farmers_result->fetch_assoc()['total_farmers'];

// Total Customers
$customers_query = "SELECT COUNT(*) as total_customers FROM users WHERE role = 'customer'";
$customers_result = $farmcart->conn->query($customers_query);
$total_customers = $customers_result->fetch_assoc()['total_customers'];

// Average Order Value
$avg_order_query = "SELECT COALESCE(AVG(total_amount), 0) as avg_order 
                    FROM orders 
                    WHERE status != 'cancelled' 
                    AND DATE(created_at) BETWEEN ? AND ?";
$stmt = $farmcart->conn->prepare($avg_order_query);
$stmt->bind_param("ss", $start_date, $end_date);
$stmt->execute();
$avg_order_result = $stmt->get_result();
$avg_order_value = $avg_order_result->fetch_assoc()['avg_order'];
$stmt->close();

// Orders by Status
$status_query = "SELECT status, COUNT(*) as count 
                 FROM orders 
                 WHERE DATE(created_at) BETWEEN ? AND ?
                 GROUP BY status";
$stmt = $farmcart->conn->prepare($status_query);
$stmt->bind_param("ss", $start_date, $end_date);
$stmt->execute();
$status_result = $stmt->get_result();
$orders_by_status = [];
while ($row = $status_result->fetch_assoc()) {
    $orders_by_status[$row['status']] = $row['count'];
}
$stmt->close();

// Top Products (by order items)
$top_products_query = "SELECT 
                          p.product_name,
                          c.category_name,
                          SUM(oi.quantity) as total_quantity,
                          SUM(oi.subtotal) as total_revenue,
                          COUNT(DISTINCT o.order_id) as order_count
                       FROM order_items oi
                       JOIN inventory_lots il ON oi.lot_id = il.lot_id
                       JOIN products p ON il.product_id = p.product_id
                       JOIN categories c ON p.category_id = c.category_id
                       JOIN orders o ON oi.order_id = o.order_id
                       WHERE o.status != 'cancelled'
                       AND DATE(o.created_at) BETWEEN ? AND ?
                       GROUP BY p.product_id
                       ORDER BY total_revenue DESC
                       LIMIT 10";
$stmt = $farmcart->conn->prepare($top_products_query);
$stmt->bind_param("ss", $start_date, $end_date);
$stmt->execute();
$top_products_result = $stmt->get_result();
$stmt->close();

// Top Farmers (by revenue)
$top_farmers_query = "SELECT 
                         u.first_name,
                         u.last_name,
                         fp.farm_name,
                         SUM(oi.subtotal) as total_revenue,
                         COUNT(DISTINCT o.order_id) as order_count
                      FROM order_items oi
                      JOIN inventory_lots il ON oi.lot_id = il.lot_id
                      JOIN products p ON il.product_id = p.product_id
                      JOIN users u ON p.created_by = u.user_id
                      LEFT JOIN farmer_profiles fp ON u.user_id = fp.user_id
                      JOIN orders o ON oi.order_id = o.order_id
                      WHERE o.status != 'cancelled'
                      AND DATE(o.created_at) BETWEEN ? AND ?
                      GROUP BY u.user_id
                      ORDER BY total_revenue DESC
                      LIMIT 10";
$stmt = $farmcart->conn->prepare($top_farmers_query);
$stmt->bind_param("ss", $start_date, $end_date);
$stmt->execute();
$top_farmers_result = $stmt->get_result();
$stmt->close();

// Daily Revenue (for chart)
$daily_revenue_query = "SELECT 
                           DATE(created_at) as date,
                           COALESCE(SUM(total_amount), 0) as revenue,
                           COUNT(*) as orders
                        FROM orders
                        WHERE status != 'cancelled'
                        AND payment_status = 'paid'
                        AND DATE(created_at) BETWEEN ? AND ?
                        GROUP BY DATE(created_at)
                        ORDER BY date ASC";
$stmt = $farmcart->conn->prepare($daily_revenue_query);
$stmt->bind_param("ss", $start_date, $end_date);
$stmt->execute();
$daily_revenue_result = $stmt->get_result();
$daily_data = [];
while ($row = $daily_revenue_result->fetch_assoc()) {
    $daily_data[] = $row;
}
$stmt->close();

// Revenue by Category
$category_revenue_query = "SELECT 
                              c.category_name,
                              SUM(oi.subtotal) as revenue,
                              COUNT(DISTINCT o.order_id) as orders
                           FROM order_items oi
                           JOIN inventory_lots il ON oi.lot_id = il.lot_id
                           JOIN products p ON il.product_id = p.product_id
                           JOIN categories c ON p.category_id = c.category_id
                           JOIN orders o ON oi.order_id = o.order_id
                           WHERE o.status != 'cancelled'
                           AND DATE(o.created_at) BETWEEN ? AND ?
                           GROUP BY c.category_id
                           ORDER BY revenue DESC";
$stmt = $farmcart->conn->prepare($category_revenue_query);
$stmt->bind_param("ss", $start_date, $end_date);
$stmt->execute();
$category_revenue_result = $stmt->get_result();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Reports & Analytics | Admin Dashboard</title>
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../../Assets/css/admin.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .stat-card {
            border-radius: 15px;
            border: none;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }
        .chart-container {
            position: relative;
            height: 300px;
            margin-bottom: 30px;
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar Column -->
        <div class="sidebar-column">
            <?php include '../../Includes/admin_sidebar.php'; ?>
        </div>

        <!-- Main Content Column -->
        <div class="main-content-column">
            <div class="content-area">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1 class="h2 mb-0">
                        <i class="fas fa-chart-bar me-2"></i>Reports & Analytics
                    </h1>
                    <div>
                        <form method="get" class="d-flex gap-2">
                            <input type="date" name="start_date" class="form-control" value="<?= $start_date ?>" required>
                            <input type="date" name="end_date" class="form-control" value="<?= $end_date ?>" required>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-filter me-1"></i>Filter
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Key Metrics Cards -->
                <div class="row g-4 mb-4">
                    <div class="col-md-3">
                        <div class="card stat-card bg-primary text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-white-50 mb-2">Total Revenue</h6>
                                        <h3 class="mb-0">₱<?= number_format($total_revenue, 2) ?></h3>
                                        <small class="text-white-50">Period: <?= date('M d', strtotime($start_date)) ?> - <?= date('M d, Y', strtotime($end_date)) ?></small>
                                    </div>
                                    <div class="stat-icon bg-white bg-opacity-25">
                                        <i class="fas fa-dollar-sign"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="card stat-card bg-success text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-white-50 mb-2">Total Orders</h6>
                                        <h3 class="mb-0"><?= number_format($total_orders) ?></h3>
                                        <small class="text-white-50">In selected period</small>
                                    </div>
                                    <div class="stat-icon bg-white bg-opacity-25">
                                        <i class="fas fa-shopping-cart"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="card stat-card bg-info text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-white-50 mb-2">Avg Order Value</h6>
                                        <h3 class="mb-0">₱<?= number_format($avg_order_value, 2) ?></h3>
                                        <small class="text-white-50">Per order</small>
                                    </div>
                                    <div class="stat-icon bg-white bg-opacity-25">
                                        <i class="fas fa-chart-line"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="card stat-card bg-warning text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-white-50 mb-2">Total Users</h6>
                                        <h3 class="mb-0"><?= number_format($total_users) ?></h3>
                                        <small class="text-white-50">All time</small>
                                    </div>
                                    <div class="stat-icon bg-white bg-opacity-25">
                                        <i class="fas fa-users"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- User Statistics -->
                <div class="row g-4 mb-4">
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-body text-center">
                                <h5 class="text-muted">Total Customers</h5>
                                <h2 class="text-primary"><?= number_format($total_customers) ?></h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-body text-center">
                                <h5 class="text-muted">Total Farmers</h5>
                                <h2 class="text-success"><?= number_format($total_farmers) ?></h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-body text-center">
                                <h5 class="text-muted">Total Products</h5>
                                <h2 class="text-info"><?= number_format($total_products) ?></h2>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Charts Row -->
                <div class="row g-4 mb-4">
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-chart-line me-2"></i>Daily Revenue Trend</h5>
                            </div>
                            <div class="card-body">
                                <div class="chart-container">
                                    <canvas id="revenueChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-chart-pie me-2"></i>Orders by Status</h5>
                            </div>
                            <div class="card-body">
                                <div class="chart-container">
                                    <canvas id="statusChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Top Products and Farmers -->
                <div class="row g-4 mb-4">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-star me-2"></i>Top 10 Products</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Product</th>
                                                <th>Category</th>
                                                <th>Revenue</th>
                                                <th>Orders</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if ($top_products_result->num_rows > 0): ?>
                                                <?php while ($product = $top_products_result->fetch_assoc()): ?>
                                                    <tr>
                                                        <td><?= htmlspecialchars($product['product_name']) ?></td>
                                                        <td><span class="badge bg-secondary"><?= htmlspecialchars($product['category_name']) ?></span></td>
                                                        <td>₱<?= number_format($product['total_revenue'], 2) ?></td>
                                                        <td><?= $product['order_count'] ?></td>
                                                    </tr>
                                                <?php endwhile; ?>
                                            <?php else: ?>
                                                <tr>
                                                    <td colspan="4" class="text-center text-muted">No data available</td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-tractor me-2"></i>Top 10 Farmers</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Farmer</th>
                                                <th>Farm</th>
                                                <th>Revenue</th>
                                                <th>Orders</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if ($top_farmers_result->num_rows > 0): ?>
                                                <?php while ($farmer = $top_farmers_result->fetch_assoc()): ?>
                                                    <tr>
                                                        <td><?= htmlspecialchars($farmer['first_name'] . ' ' . $farmer['last_name']) ?></td>
                                                        <td><?= htmlspecialchars($farmer['farm_name'] ?: 'N/A') ?></td>
                                                        <td>₱<?= number_format($farmer['total_revenue'], 2) ?></td>
                                                        <td><?= $farmer['order_count'] ?></td>
                                                    </tr>
                                                <?php endwhile; ?>
                                            <?php else: ?>
                                                <tr>
                                                    <td colspan="4" class="text-center text-muted">No data available</td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Revenue by Category -->
                <div class="row g-4">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-tags me-2"></i>Revenue by Category</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Category</th>
                                                <th>Revenue</th>
                                                <th>Orders</th>
                                                <th>Percentage</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php 
                                            $category_total = 0;
                                            $category_data = [];
                                            while ($cat = $category_revenue_result->fetch_assoc()) {
                                                $category_total += $cat['revenue'];
                                                $category_data[] = $cat;
                                            }
                                            ?>
                                            <?php if (count($category_data) > 0): ?>
                                                <?php foreach ($category_data as $cat): ?>
                                                    <tr>
                                                        <td><strong><?= htmlspecialchars($cat['category_name']) ?></strong></td>
                                                        <td>₱<?= number_format($cat['revenue'], 2) ?></td>
                                                        <td><?= $cat['orders'] ?></td>
                                                        <td>
                                                            <div class="progress" style="height: 20px;">
                                                                <div class="progress-bar" role="progressbar" 
                                                                     style="width: <?= $category_total > 0 ? ($cat['revenue'] / $category_total * 100) : 0 ?>%">
                                                                    <?= $category_total > 0 ? number_format($cat['revenue'] / $category_total * 100, 1) : 0 ?>%
                                                                </div>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <tr>
                                                    <td colspan="4" class="text-center text-muted">No data available</td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Revenue Chart
        const revenueData = <?= json_encode($daily_data) ?>;
        const revenueLabels = revenueData.map(d => new Date(d.date).toLocaleDateString('en-US', { month: 'short', day: 'numeric' }));
        const revenueValues = revenueData.map(d => parseFloat(d.revenue));

        const revenueCtx = document.getElementById('revenueChart').getContext('2d');
        new Chart(revenueCtx, {
            type: 'line',
            data: {
                labels: revenueLabels,
                datasets: [{
                    label: 'Revenue (₱)',
                    data: revenueValues,
                    borderColor: 'rgb(75, 192, 192)',
                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return '₱' + value.toLocaleString();
                            }
                        }
                    }
                }
            }
        });

        // Status Chart
        const statusData = <?= json_encode($orders_by_status) ?>;
        const statusLabels = Object.keys(statusData);
        const statusValues = Object.values(statusData);
        const statusColors = {
            'pending': 'rgb(255, 206, 86)',
            'confirmed': 'rgb(54, 162, 235)',
            'preparing': 'rgb(153, 102, 255)',
            'shipping': 'rgb(255, 159, 64)',
            'delivered': 'rgb(75, 192, 192)',
            'cancelled': 'rgb(255, 99, 132)',
            'refunded': 'rgb(201, 203, 207)'
        };

        const statusCtx = document.getElementById('statusChart').getContext('2d');
        new Chart(statusCtx, {
            type: 'doughnut',
            data: {
                labels: statusLabels.map(s => s.charAt(0).toUpperCase() + s.slice(1)),
                datasets: [{
                    data: statusValues,
                    backgroundColor: statusLabels.map(s => statusColors[s] || 'rgb(201, 203, 207)')
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    </script>
</body>
</html>


