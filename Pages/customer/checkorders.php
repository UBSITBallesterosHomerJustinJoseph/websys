<?php
session_start();
include '../../db_connect.php';

$userId = $_SESSION['user_id'] ?? null;
$orders = [];

if ($userId) {
    $sql = "
        SELECT order_id, status, created_at, total_amount, payment_method, payment_status, shipping_address, order_notes
        FROM orders
        WHERE customer_id = ?
        ORDER BY created_at DESC
    ";
    $stmt = $farmcart->conn->prepare($sql);

    if (!$stmt) {
        die("Prepare failed: " . $farmcart->conn->error);
    }

    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        // Fetch order items for each order
        $itemsSql = "
            SELECT oi.order_item_id, oi.quantity, oi.unit_price, oi.subtotal,
                   p.product_name, p.unit_type,
                   il.lot_number
            FROM order_items oi
            JOIN inventory_lots il ON oi.lot_id = il.lot_id
            JOIN products p ON il.product_id = p.product_id
            WHERE oi.order_id = ?
        ";
        $itemsStmt = $farmcart->conn->prepare($itemsSql);
        $itemsStmt->bind_param("i", $row['order_id']);
        $itemsStmt->execute();
        $itemsResult = $itemsStmt->get_result();
        $row['items'] = [];
        while ($item = $itemsResult->fetch_assoc()) {
            $row['items'][] = $item;
        }
        $itemsStmt->close();
        
        $orders[] = $row;
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>My Orders | FarmCart</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" />
  <link rel="stylesheet" href="../../Assets/css/navbar.css" />
  <link rel="stylesheet" href="../../Assets/css/orders.css" />
  <style>
    .order-card {
      background: white;
      border-radius: 8px;
      padding: 1.5rem;
      margin-bottom: 1.5rem;
      box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    .order-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 1rem;
      padding-bottom: 1rem;
      border-bottom: 1px solid #eee;
    }
    .order-info p {
      margin-bottom: 0.5rem;
    }
    .shipping-info {
      background: #f8f9fa;
      padding: 0.75rem;
      border-radius: 4px;
    }
    .order-items {
      background: #f8f9fa;
      padding: 1rem;
      border-radius: 4px;
    }
    .order-items table {
      margin-bottom: 0;
    }
  </style>
</head>
<body>
  <?php require_once '../../Includes/navbar.php'; ?>

  <main class="container py-4">
    <div class="row justify-content-center">
      <div class="col-12 col-md-10 col-lg-8">
        <div class="orders-container">
          <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="orders-title mb-0">📦 My Orders</h1>
            <a href="products.php" class="btn btn-outline-secondary">
              <i class="fas fa-arrow-left me-2"></i>Back to Products
            </a>
          </div>
          
          <?php if (isset($_SESSION['order_success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
              <i class="fas fa-check-circle me-2"></i>
              Order placed successfully! Order ID: #<?= $_SESSION['order_id'] ?? ''; ?>
              <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['order_success'], $_SESSION['order_id']); ?>
          <?php endif; ?>

          <?php if (!empty($orders)): ?>
            <?php foreach ($orders as $order): ?>
              <div class="order-card">
                <div class="order-header">
                  <h4>Order #<?= $order['order_id']; ?></h4>
                  <span class="badge bg-<?= 
                    $order['status'] == 'pending' ? 'warning' : 
                    ($order['status'] == 'confirmed' ? 'info' : 
                    ($order['status'] == 'preparing' ? 'primary' : 
                    ($order['status'] == 'ready_to_pickup' ? 'success' : 
                    ($order['status'] == 'cancelled' ? 'danger' : 'secondary')))); 
                  ?>">
                    <?= ucfirst(str_replace('_', ' ', $order['status'])); ?>
                  </span>
                </div>
                
                <div class="order-info">
                  <p><strong>Placed:</strong> <?= date('F d, Y h:i A', strtotime($order['created_at'])); ?></p>
                  <p><strong>Total:</strong> ₱<?= number_format($order['total_amount'], 2); ?></p>
                  <p><strong>Payment:</strong> <?= ucfirst(str_replace('_', ' ', $order['payment_method'])); ?> 
                    <span class="badge bg-<?= $order['payment_status'] == 'paid' ? 'success' : ($order['payment_status'] == 'pending' ? 'warning' : 'danger'); ?>">
                      <?= ucfirst($order['payment_status']); ?>
                    </span>
                  </p>
                  
                  <?php if (!empty($order['shipping_address'])): ?>
                    <div class="shipping-info mt-2">
                      <strong>Pickup Details:</strong>
                      <pre class="mb-0" style="white-space: pre-wrap; font-family: inherit;"><?= htmlspecialchars($order['shipping_address']); ?></pre>
                    </div>
                  <?php endif; ?>
                </div>

                <!-- Order Items -->
                <?php if (!empty($order['items'])): ?>
                  <div class="order-items mt-3">
                    <h6>Order Items:</h6>
                    <table class="table table-sm">
                      <thead>
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
                            <td><?= htmlspecialchars($item['product_name']); ?> 
                              <?php if (!empty($item['lot_number'])): ?>
                                <small class="text-muted">(Lot: <?= htmlspecialchars($item['lot_number']); ?>)</small>
                              <?php endif; ?>
                            </td>
                            <td><?= number_format($item['quantity'], 2); ?> <?= htmlspecialchars($item['unit_type'] ?? ''); ?></td>
                            <td>₱<?= number_format($item['unit_price'], 2); ?></td>
                            <td>₱<?= number_format($item['subtotal'], 2); ?></td>
                          </tr>
                        <?php endforeach; ?>
                      </tbody>
                    </table>
                  </div>
                <?php endif; ?>

                <!-- Timeline -->
                <div class="timeline mt-3">
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
              </div>
            <?php endforeach; ?>
          <?php else: ?>
            <div class="orders-empty text-center">
              <h3>No orders yet</h3>
              <p>Once you place an order, you’ll see its progress here.</p>
              <a href="products.php" class="btn btn-shop">Shop Now</a>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </main>

  <?php require_once '../../Includes/footer.php'; ?>
</body>
</html>
