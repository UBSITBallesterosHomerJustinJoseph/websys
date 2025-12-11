<?php
session_start();
include '../../db_connect.php';

$userId = $_SESSION['user_id'] ?? null;

$items = [];
$total = 0;

if ($userId) {
    // Logged-in users: fetch cart from DB
    $sql = "SELECT p.product_name, c.quantity, p.base_price 
            FROM carts c
            JOIN products p ON c.product_id = p.product_id
            WHERE c.user_id = ?";
    $stmt = $farmcart->conn->prepare($sql);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $items[] = $row;
        $total += $row['base_price'] * $row['quantity'];
    }
} else {
    // Guest cart from session
    $items = $_SESSION['guest_cart'] ?? [];
    foreach ($items as $item) {
        $total += $item['base_price'] * $item['quantity'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Your Cart | FarmCart</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />

  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" />
  <!-- Google Fonts -->
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet" />
  <!-- Custom Navbar CSS -->
  <link rel="stylesheet" href="../../Assets/css/navbar.css" />
    <link rel="stylesheet" href="../../Assets/css/cart.css" />

  
</head>
<body>

  <!-- Navbar -->
  <?php require_once '../../Includes/navbar.php'; ?>

  <div class="cart-container">
    <h1 class="cart-title">🛒 Your Shopping Cart</h1>

    <?php if (!empty($items)): ?>
      <?php foreach ($items as $item): ?>
        <div class="cart-item">
          <div class="cart-item-info">
            <span class="cart-item-name"><?= htmlspecialchars($item['product_name']); ?></span><br />
            <span class="cart-item-qty">Quantity: <?= (int)$item['quantity']; ?></span>
          </div>

          <div class="cart-item-price">
            ₱<?= number_format($item['base_price'] * $item['quantity'], 2); ?>
          </div>
        </div>
      <?php endforeach; ?>

      <div class="cart-footer">
        <div class="cart-total">
          <span>Total:</span>
          <span>₱<?= number_format($total, 2); ?></span>
        </div>

        <button class="btn-checkout">
          Proceed to Checkout
        </button>

        <a href="products.php" class="btn-back">
          ← Continue Shopping
        </a>
      </div>
    <?php else: ?>
      <div class="cart-empty">
        <div class="cart-empty-icon">🛍️</div>
        <h3>Your cart is empty</h3>
        <p>Browse fresh farm products and add them to your cart.</p>
        <a href="products.php" class="btn btn-success">Shop Now</a>
      </div>
    <?php endif; ?>
  </div>

  <!-- Footer -->
  <?php require_once '../../Includes/footer.php'; ?>

  <!-- Bootstrap JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
