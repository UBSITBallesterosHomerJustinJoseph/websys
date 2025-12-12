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
            // Get product_id from inventory_lots
            $productCheck = $farmcart->getPDO()->prepare("
                SELECT il.product_id FROM inventory_lots il
                JOIN order_items oi ON il.lot_id = oi.lot_id
                WHERE oi.order_item_id = ?
            ");
            $productCheck->execute([$item['order_item_id']]);
            $productData = $productCheck->fetch();
            $item['product_id'] = $productData['product_id'] ?? 0;
            
            // Check if review exists for this product and order
            if ($item['product_id'] > 0) {
                $reviewCheck = $farmcart->getPDO()->prepare("
                    SELECT review_id FROM reviews 
                    WHERE order_id = ? AND product_id = ?
                ");
                $reviewCheck->execute([$row['order_id'], $item['product_id']]);
                $item['has_review'] = $reviewCheck->fetch() ? true : false;
            } else {
                $item['has_review'] = false;
            }
            
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
                            <td>
                              <?= htmlspecialchars($item['product_name']); ?> 
                              <?php if (!empty($item['lot_number'])): ?>
                                <small class="text-muted">(Lot: <?= htmlspecialchars($item['lot_number']); ?>)</small>
                              <?php endif; ?>
                              <?php if (in_array($order['status'], ['delivered', 'ready_to_pickup']) && !empty($item['product_id'])): ?>
                                <br>
                                <?php if ($item['has_review']): ?>
                                  <small class="text-success"><i class="fas fa-check-circle"></i> Reviewed</small>
                                <?php else: ?>
                                  <button class="btn btn-sm btn-outline-primary mt-1" onclick="openReviewModal(<?= $item['product_id']; ?>, <?= $order['order_id']; ?>, '<?= htmlspecialchars($item['product_name']); ?>')">
                                    <i class="fas fa-star"></i> Write Review
                                  </button>
                                <?php endif; ?>
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

  <!-- Review Modal -->
  <div class="modal fade" id="reviewModal" tabindex="-1">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Write a Review</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <p><strong>Product:</strong> <span id="reviewProductName"></span></p>
          <form id="reviewForm">
            <input type="hidden" id="reviewProductId" name="product_id">
            <input type="hidden" id="reviewOrderId" name="order_id">
            
            <div class="mb-3">
              <label class="form-label">Rating <span class="text-danger">*</span></label>
              <div class="rating-input">
                <input type="radio" name="rating" value="5" id="rating5" required>
                <label for="rating5" class="star-label">★</label>
                <input type="radio" name="rating" value="4" id="rating4" required>
                <label for="rating4" class="star-label">★</label>
                <input type="radio" name="rating" value="3" id="rating3" required>
                <label for="rating3" class="star-label">★</label>
                <input type="radio" name="rating" value="2" id="rating2" required>
                <label for="rating2" class="star-label">★</label>
                <input type="radio" name="rating" value="1" id="rating1" required>
                <label for="rating1" class="star-label">★</label>
              </div>
            </div>
            
            <div class="mb-3">
              <label for="reviewText" class="form-label">Review (Optional)</label>
              <textarea class="form-control" id="reviewText" name="review_text" rows="4" placeholder="Share your experience with this product..."></textarea>
            </div>
            
            <div id="reviewMessage" class="alert" style="display: none;"></div>
          </form>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="button" class="btn btn-primary" onclick="submitReview()">Submit Review</button>
        </div>
      </div>
    </div>
  </div>

  <style>
    .rating-input {
      display: flex;
      flex-direction: row-reverse;
      justify-content: flex-end;
      gap: 5px;
    }
    .rating-input input[type="radio"] {
      display: none;
    }
    .rating-input label.star-label {
      font-size: 2rem;
      color: #ddd;
      cursor: pointer;
      transition: color 0.2s;
    }
    .rating-input input[type="radio"]:checked ~ label.star-label,
    .rating-input label.star-label:hover,
    .rating-input label.star-label:hover ~ label.star-label {
      color: #ffc107;
    }
  </style>

  <!-- Bootstrap JS Bundle (required for dropdown functionality) -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    function openReviewModal(productId, orderId, productName) {
      document.getElementById('reviewProductId').value = productId;
      document.getElementById('reviewOrderId').value = orderId;
      document.getElementById('reviewProductName').textContent = productName;
      document.getElementById('reviewForm').reset();
      document.getElementById('reviewMessage').style.display = 'none';
      new bootstrap.Modal(document.getElementById('reviewModal')).show();
    }

    function submitReview() {
      const form = document.getElementById('reviewForm');
      const formData = new FormData(form);
      const messageDiv = document.getElementById('reviewMessage');
      
      // Validate rating
      const rating = formData.get('rating');
      if (!rating) {
        messageDiv.className = 'alert alert-danger';
        messageDiv.textContent = 'Please select a rating.';
        messageDiv.style.display = 'block';
        return;
      }

      fetch('submit_review.php', {
        method: 'POST',
        body: formData
      })
      .then(response => response.json())
      .then(data => {
        messageDiv.style.display = 'block';
        if (data.success) {
          messageDiv.className = 'alert alert-success';
          messageDiv.textContent = data.message;
          setTimeout(() => {
            location.reload();
          }, 1500);
        } else {
          messageDiv.className = 'alert alert-danger';
          messageDiv.textContent = data.message;
        }
      })
      .catch(error => {
        messageDiv.className = 'alert alert-danger';
        messageDiv.textContent = 'An error occurred. Please try again.';
        messageDiv.style.display = 'block';
      });
    }
  </script>
</body>
</html>
