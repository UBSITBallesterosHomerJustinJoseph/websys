<?php
session_start();
include '../../db_connect.php';

// Check if user is logged in and is a farmer
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'farmer') {
    header('Location: ../index.php');
    exit;
}

$farmer_id = $_SESSION['user_id'];

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $order_id = (int)$_POST['order_id'];
    $new_status = $_POST['status'];
    $notes = $_POST['notes'] ?? '';
    
    // Validate status
    $valid_statuses = ['pending', 'confirmed', 'preparing', 'ready_to_pickup', 'cancelled'];
    if (!in_array($new_status, $valid_statuses)) {
        $_SESSION['error'] = 'Invalid status.';
        header('Location: orders.php');
        exit;
    }
    
    // Verify order belongs to this farmer's products
    $checkOrder = $farmcart->conn->prepare("
        SELECT o.order_id 
        FROM orders o
        JOIN order_items oi ON o.order_id = oi.order_id
        JOIN inventory_lots il ON oi.lot_id = il.lot_id
        WHERE o.order_id = ? AND il.farmer_id = ?
        LIMIT 1
    ");
    $checkOrder->bind_param("ii", $order_id, $farmer_id);
    $checkOrder->execute();
    $orderResult = $checkOrder->get_result();
    
    if ($orderResult->num_rows > 0) {
        // Update order status
        $updateStmt = $farmcart->conn->prepare("UPDATE orders SET status = ? WHERE order_id = ?");
        $updateStmt->bind_param("si", $new_status, $order_id);
        
        if ($updateStmt->execute()) {
            // Add to status history
            $historyStmt = $farmcart->conn->prepare("
                INSERT INTO order_status_history (order_id, status, notes, created_by) 
                VALUES (?, ?, ?, ?)
            ");
            $historyStmt->bind_param("issi", $order_id, $new_status, $notes, $farmer_id);
            $historyStmt->execute();
            $historyStmt->close();
            
            $_SESSION['success'] = 'Order status updated successfully!';
        } else {
            $_SESSION['error'] = 'Failed to update order status.';
        }
        $updateStmt->close();
    } else {
        $_SESSION['error'] = 'Order not found or you do not have permission to update it.';
    }
    $checkOrder->close();
    
    header('Location: orders.php');
    exit;
}

// Fetch orders for this farmer
$orders_sql = "
    SELECT DISTINCT o.order_id, o.customer_id, o.total_amount, o.status, 
           o.created_at, o.payment_method, o.payment_status, o.shipping_address,
           u.first_name, u.last_name, u.email, u.phone_number,
           COUNT(DISTINCT oi.order_item_id) as item_count
    FROM orders o
    JOIN order_items oi ON o.order_id = oi.order_id
    JOIN inventory_lots il ON oi.lot_id = il.lot_id
    JOIN users u ON o.customer_id = u.user_id
    WHERE il.farmer_id = ?
    GROUP BY o.order_id
    ORDER BY o.created_at DESC
";
$orders_stmt = $farmcart->conn->prepare($orders_sql);
$orders_stmt->bind_param("i", $farmer_id);
$orders_stmt->execute();
$orders_result = $orders_stmt->get_result();
$orders = [];
while ($row = $orders_result->fetch_assoc()) {
    // Get order items for this order
    $items_sql = "
        SELECT oi.*, p.product_name, p.unit_type, il.lot_number
        FROM order_items oi
        JOIN inventory_lots il ON oi.lot_id = il.lot_id
        JOIN products p ON il.product_id = p.product_id
        WHERE oi.order_id = ? AND il.farmer_id = ?
    ";
    $items_stmt = $farmcart->conn->prepare($items_sql);
    $items_stmt->bind_param("ii", $row['order_id'], $farmer_id);
    $items_stmt->execute();
    $items_result = $items_stmt->get_result();
    $row['items'] = [];
    while ($item = $items_result->fetch_assoc()) {
        $row['items'][] = $item;
    }
    $items_stmt->close();
    
    // Get status history
    $history_sql = "
        SELECT * FROM order_status_history 
        WHERE order_id = ? 
        ORDER BY created_at DESC
    ";
    $history_stmt = $farmcart->conn->prepare($history_sql);
    $history_stmt->bind_param("i", $row['order_id']);
    $history_stmt->execute();
    $history_result = $history_stmt->get_result();
    $row['history'] = [];
    while ($history = $history_result->fetch_assoc()) {
        $row['history'][] = $history;
    }
    $history_stmt->close();
    
    $orders[] = $row;
}
$orders_stmt->close();

// Get stats
$stats_sql = "
    SELECT 
        COUNT(DISTINCT CASE WHEN o.status = 'pending' THEN o.order_id END) as pending_count,
        COUNT(DISTINCT CASE WHEN o.status = 'confirmed' THEN o.order_id END) as confirmed_count,
        COUNT(DISTINCT CASE WHEN o.status = 'preparing' THEN o.order_id END) as preparing_count,
        COUNT(DISTINCT CASE WHEN o.status = 'ready_to_pickup' THEN o.order_id END) as ready_count,
        SUM(CASE WHEN o.status != 'cancelled' THEN o.total_amount ELSE 0 END) as total_revenue
    FROM orders o
    JOIN order_items oi ON o.order_id = oi.order_id
    JOIN inventory_lots il ON oi.lot_id = il.lot_id
    WHERE il.farmer_id = ?
";
$stats_stmt = $farmcart->conn->prepare($stats_sql);
$stats_stmt->bind_param("i", $farmer_id);
$stats_stmt->execute();
$stats_result = $stats_stmt->get_result();
$stats = $stats_result->fetch_assoc();
$stats_stmt->close();

// Get farmer name
$farmer_sql = "SELECT first_name, last_name FROM users WHERE user_id = ?";
$farmer_stmt = $farmcart->conn->prepare($farmer_sql);
$farmer_stmt->bind_param("i", $farmer_id);
$farmer_stmt->execute();
$farmer_result = $farmer_stmt->get_result();
$farmer_data = $farmer_result->fetch_assoc();
$farmer_name = ($farmer_data['first_name'] ?? '') . ' ' . ($farmer_data['last_name'] ?? '');
$farmer_stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Manage Orders | FarmCart</title>
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
    
    .order-card {
        background: white;
        border-radius: 12px;
        padding: 1.5rem;
        margin-bottom: 1.5rem;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        transition: box-shadow 0.3s;
    }
    
    .order-card:hover {
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    }
    
    .status-badge {
        font-size: 0.875rem;
        padding: 0.5rem 1rem;
    }
    
    .timeline {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-top: 1.5rem;
        padding: 1rem;
        background: #f8f9fa;
        border-radius: 8px;
    }
    
    .timeline-step {
        flex: 1;
        text-align: center;
        position: relative;
    }
    
    .timeline-step:not(:last-child)::after {
        content: '';
        position: absolute;
        top: 20px;
        left: 60%;
        width: 80%;
        height: 2px;
        background: #dee2e6;
        z-index: 0;
    }
    
    .timeline-step.active:not(:last-child)::after {
        background: #4E653D;
    }
    
    .timeline-step .circle {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: #dee2e6;
        color: #6c757d;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 0.5rem;
        font-weight: 600;
        position: relative;
        z-index: 1;
    }
    
    .timeline-step.active .circle {
        background: #4E653D;
        color: white;
    }
    
    .timeline-step .label {
        font-size: 0.75rem;
        color: #6c757d;
        display: block;
    }
    
    .timeline-step.active .label {
        color: #4E653D;
        font-weight: 600;
    }
  </style>
</head>
<body>
  <div class="dashboard-container">
    <!-- Sidebar Column -->
    <div class="sidebar-column">
      <?php 
      $sidebar_stats = [
          'total_products' => 0,
          'pending_orders' => $stats['pending_count'] ?? 0,
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
              <i class="fas fa-shopping-cart me-2"></i>Manage Orders
            </h1>
            <p class="text-muted mb-0">Track and update order status for pickup orders.</p>
          </div>
        </div>

        <?php if (isset($_SESSION['success'])): ?>
          <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i><?= htmlspecialchars($_SESSION['success']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>
          <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
          <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i><?= htmlspecialchars($_SESSION['error']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>
          <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <!-- Stats Cards -->
        <div class="row mb-4">
          <div class="col-md-3 mb-3">
            <div class="card stats-card bg-warning">
              <div class="card-body">
                <h6 class="card-title text-white-50">Pending</h6>
                <h2 class="mb-0"><?= $stats['pending_count'] ?? 0; ?></h2>
              </div>
            </div>
          </div>
          <div class="col-md-3 mb-3">
            <div class="card stats-card bg-info">
              <div class="card-body">
                <h6 class="card-title text-white-50">Confirmed</h6>
                <h2 class="mb-0"><?= $stats['confirmed_count'] ?? 0; ?></h2>
              </div>
            </div>
          </div>
          <div class="col-md-3 mb-3">
            <div class="card stats-card bg-primary">
              <div class="card-body">
                <h6 class="card-title text-white-50">Preparing</h6>
                <h2 class="mb-0"><?= $stats['preparing_count'] ?? 0; ?></h2>
              </div>
            </div>
          </div>
          <div class="col-md-3 mb-3">
            <div class="card stats-card bg-success">
              <div class="card-body">
                <h6 class="card-title text-white-50">Ready to Pickup</h6>
                <h2 class="mb-0"><?= $stats['ready_count'] ?? 0; ?></h2>
              </div>
            </div>
          </div>
        </div>

        <!-- Orders List -->
        <?php if (!empty($orders)): ?>
          <?php foreach ($orders as $order): ?>
            <div class="order-card">
              <div class="d-flex justify-content-between align-items-start mb-3">
                <div>
                  <h4 class="mb-1">Order #<?= $order['order_id']; ?></h4>
                  <p class="text-muted mb-0">
                    <i class="fas fa-user me-1"></i>
                    <?= htmlspecialchars($order['first_name'] . ' ' . $order['last_name']); ?>
                    <span class="mx-2">|</span>
                    <i class="fas fa-calendar me-1"></i>
                    <?= date('M d, Y h:i A', strtotime($order['created_at'])); ?>
                  </p>
                </div>
                <span class="badge status-badge bg-<?= 
                  $order['status'] == 'pending' ? 'warning' : 
                  ($order['status'] == 'confirmed' ? 'info' : 
                  ($order['status'] == 'preparing' ? 'primary' : 
                  ($order['status'] == 'ready_to_pickup' ? 'success' : 'danger'))); 
                ?>">
                  <?= ucfirst(str_replace('_', ' ', $order['status'])); ?>
                </span>
              </div>

              <div class="row mb-3">
                <div class="col-md-6">
                  <p class="mb-1"><strong>Total Amount:</strong> ₱<?= number_format($order['total_amount'], 2); ?></p>
                  <p class="mb-1"><strong>Payment:</strong> <?= ucfirst(str_replace('_', ' ', $order['payment_method'])); ?> 
                    <span class="badge bg-<?= $order['payment_status'] == 'paid' ? 'success' : 'warning'; ?>">
                      <?= ucfirst($order['payment_status']); ?>
                    </span>
                  </p>
                  <p class="mb-0"><strong>Items:</strong> <?= $order['item_count']; ?> item(s)</p>
                </div>
                <div class="col-md-6">
                  <?php if (!empty($order['shipping_address'])): ?>
                    <p class="mb-1"><strong>Pickup Details:</strong></p>
                    <pre class="bg-light p-2 rounded small mb-0" style="white-space: pre-wrap; font-family: inherit;"><?= htmlspecialchars($order['shipping_address']); ?></pre>
                  <?php endif; ?>
                </div>
              </div>

              <!-- Order Items -->
              <?php if (!empty($order['items'])): ?>
                <div class="table-responsive mb-3">
                  <table class="table table-sm table-bordered">
                    <thead class="table-light">
                      <tr>
                        <th>Product</th>
                        <th>Quantity</th>
                        <th>Unit Price</th>
                        <th>Subtotal</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php foreach ($order['items'] as $item): ?>
                        <tr>
                          <td><?= htmlspecialchars($item['product_name']); ?></td>
                          <td><?= number_format($item['quantity'], 2); ?> <?= htmlspecialchars($item['unit_type']); ?></td>
                          <td>₱<?= number_format($item['unit_price'], 2); ?></td>
                          <td>₱<?= number_format($item['subtotal'], 2); ?></td>
                        </tr>
                      <?php endforeach; ?>
                    </tbody>
                  </table>
                </div>
              <?php endif; ?>

              <!-- Timeline -->
              <div class="timeline">
                <div class="timeline-step <?= ($order['status'] == 'pending' || $order['status'] != '') ? 'active' : ''; ?>">
                  <span class="circle">1</span>
                  <span class="label">Pending</span>
                </div>
                <div class="timeline-step <?= ($order['status'] == 'confirmed' || in_array($order['status'], ['preparing','ready_to_pickup'])) ? 'active' : ''; ?>">
                  <span class="circle">2</span>
                  <span class="label">Confirmed</span>
                </div>
                <div class="timeline-step <?= ($order['status'] == 'preparing' || $order['status'] == 'ready_to_pickup') ? 'active' : ''; ?>">
                  <span class="circle">3</span>
                  <span class="label">Preparing</span>
                </div>
                <div class="timeline-step <?= ($order['status'] == 'ready_to_pickup') ? 'active' : ''; ?>">
                  <span class="circle">4</span>
                  <span class="label">Ready to Pickup</span>
                </div>
              </div>

              <!-- Update Status Form -->
              <div class="mt-3 pt-3 border-top">
                <form method="POST" class="row g-3">
                  <input type="hidden" name="order_id" value="<?= $order['order_id']; ?>">
                  <div class="col-md-4">
                    <label class="form-label"><strong>Update Status:</strong></label>
                    <select name="status" class="form-select" required>
                      <option value="pending" <?= $order['status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                      <option value="confirmed" <?= $order['status'] == 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                      <option value="preparing" <?= $order['status'] == 'preparing' ? 'selected' : ''; ?>>Preparing</option>
                      <option value="ready_to_pickup" <?= $order['status'] == 'ready_to_pickup' ? 'selected' : ''; ?>>Ready to Pickup</option>
                      <option value="cancelled" <?= $order['status'] == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                    </select>
                  </div>
                  <div class="col-md-6">
                    <label class="form-label"><strong>Notes (Optional):</strong></label>
                    <input type="text" name="notes" class="form-control" placeholder="Add a note about this status change...">
                  </div>
                  <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" name="update_status" class="btn btn-success w-100">
                      <i class="fas fa-save me-1"></i>Update
                    </button>
                  </div>
                </form>
              </div>
            </div>
          <?php endforeach; ?>
        <?php else: ?>
          <div class="text-center py-5">
            <i class="fas fa-shopping-cart fa-4x text-muted mb-3"></i>
            <h3>No Orders Yet</h3>
            <p class="text-muted">Orders will appear here once customers place orders for your products.</p>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

