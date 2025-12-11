<?php
// Admin/orders.php
include '../../db_connect.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("location: ../customer/index.php");
    exit();
}

$admin_id = $_SESSION['user_id'];

// Fetch all orders with customer info and item count
$orders_query = "SELECT 
                    o.*,
                    u.first_name,
                    u.last_name,
                    u.email,
                    u.phone_number,
                    COUNT(oi.order_item_id) as item_count
                 FROM orders o
                 LEFT JOIN users u ON o.customer_id = u.user_id
                 LEFT JOIN order_items oi ON o.order_id = oi.order_id
                 GROUP BY o.order_id
                 ORDER BY o.created_at DESC";

$orders_result = $farmcart->conn->query($orders_query);

// Check for query errors
if (!$orders_result) {
    $error_message = "Error fetching orders: " . $farmcart->conn->error;
}

// Include admin sidebar stats
$stats = [
    'pending' => 0,
    'approved' => 0,
    'declined' => 0,
    'unverified_farmers' => 0,
    'categories' => 0,
    'users' => 0
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Orders Management | Admin Dashboard</title>
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../../Assets/css/admin.css">
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
                <h1 class="h2 mb-4">
                    <i class="fas fa-shopping-cart me-2"></i>Orders Management
                </h1>

                <!-- Error Message -->
                <?php if (isset($error_message)): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <?= htmlspecialchars($error_message) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Orders Table -->
                <div class="card">
                    <div class="card-body">
                        <?php if ($orders_result && $orders_result->num_rows > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Order ID</th>
                                            <th>Customer</th>
                                            <th>Items</th>
                                            <th>Total Amount</th>
                                            <th>Payment Status</th>
                                            <th>Order Status</th>
                                            <th>Date</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($order = $orders_result->fetch_assoc()): ?>
                                            <tr>
                                                <td>#<?= $order['order_id'] ?></td>
                                                <td>
                                                    <?= htmlspecialchars($order['first_name'] . ' ' . $order['last_name']) ?><br>
                                                    <small class="text-muted"><?= htmlspecialchars($order['email']) ?></small>
                                                </td>
                                                <td><?= $order['item_count'] ?> items</td>
                                                <td>₱<?= number_format($order['total_amount'], 2) ?></td>
                                                <td>
                                                    <span class="badge bg-<?= $order['payment_status'] == 'paid' ? 'success' : ($order['payment_status'] == 'failed' ? 'danger' : 'warning') ?>">
                                                        <?= ucfirst($order['payment_status']) ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="badge bg-<?= $order['status'] == 'pending' ? 'warning' : ($order['status'] == 'delivered' ? 'success' : ($order['status'] == 'cancelled' ? 'danger' : 'info')) ?>">
                                                        <?= ucfirst($order['status']) ?>
                                                    </span>
                                                </td>
                                                <td><?= date('M d, Y', strtotime($order['created_at'])) ?></td>
                                                <td>
                                                    <a href="view_order.php?id=<?= $order['order_id'] ?>" class="btn btn-sm btn-primary">
                                                        <i class="fas fa-eye"></i> View
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-5">
                                <i class="fas fa-shopping-cart fa-4x text-muted mb-3"></i>
                                <h5>No Orders Yet</h5>
                                <p class="text-muted">There are no orders in the system.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>