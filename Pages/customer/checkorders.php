<?php
session_start();
include '../../db_connect.php';

$userId = $_SESSION['user_id'] ?? null;
$orders = [];

if ($userId) {
    $sql = "
        SELECT order_id, status, created_at, total_amount, payment_method, payment_status
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
        $orders[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>My Orders | FarmCart</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="../../Assets/css/navbar.css" />
  <link rel="stylesheet" href="../../Assets/css/orders.css" />
</head>
<body>
  <?php require_once '../../Includes/navbar.php'; ?>

  <main class="container py-4">
    <div class="row justify-content-center">
      <div class="col-12 col-md-10 col-lg-8">
        <div class="orders-container">
          <h1 class="orders-title">📦 My Orders</h1>

          <?php if (!empty($orders)): ?>
            <?php foreach ($orders as $order): ?>
              <div class="order-card">
                <h4>Order #<?= $order['order_id']; ?></h4>
                <p><strong>Placed:</strong> <?= htmlspecialchars($order['created_at']); ?></p>
                <p><strong>Total:</strong> ₱<?= number_format($order['total_amount'], 2); ?></p>
                <p><strong>Payment:</strong> <?= htmlspecialchars($order['payment_method']); ?> (<?= htmlspecialchars($order['payment_status']); ?>)</p>

                <!-- Timeline -->
                <div class="timeline">
                  <div class="timeline-step <?= ($order['status'] == 'placed' || $order['status'] != '') ? 'active' : ''; ?>">
                    <span class="circle">1</span>
                    <span class="label">Placed</span>
                  </div>
                  <div class="timeline-step <?= ($order['status'] == 'confirmed' || in_array($order['status'], ['ready','completed'])) ? 'active' : ''; ?>">
                    <span class="circle">2</span>
                    <span class="label">Confirmed</span>
                  </div>
                  <div class="timeline-step <?= ($order['status'] == 'ready' || $order['status'] == 'completed') ? 'active' : ''; ?>">
                    <span class="circle">3</span>
                    <span class="label">Ready</span>
                  </div>
                  <div class="timeline-step <?= ($order['status'] == 'completed') ? 'active' : ''; ?>">
                    <span class="circle">4</span>
                    <span class="label">Completed</span>
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
