<?php
session_start();
include '../../db_connect.php';

$userId = $_SESSION['user_id'] ?? null;

// Require login for checkout
if (!$userId) {
    $_SESSION['redirect_after_login'] = 'checkout.php';
    header('Location: ../index.php');
    exit;
}

$items = [];
$subtotal = 0;
$customer = null;

// --- Fetch Cart Items ---
if ($userId) {
    $stmt = $farmcart->conn->prepare("
        SELECT c.cart_id, c.product_id, p.product_name, c.quantity, p.base_price, p.unit_type
        FROM carts c
        JOIN products p ON c.product_id = p.product_id
        WHERE c.user_id = ?
    ");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $items[] = $row;
        $subtotal += $row['base_price'] * $row['quantity'];
    }
    $stmt->close();

    // --- Fetch Customer Info ---
    $stmtUser = $farmcart->conn->prepare("
        SELECT first_name, last_name, email, phone_number 
        FROM users WHERE user_id = ?
    ");
    $stmtUser->bind_param("i", $userId);
    $stmtUser->execute();
    $customer = $stmtUser->get_result()->fetch_assoc();
    $stmtUser->close();
} else {
    // Guest cart
    $guestCart = $_SESSION['guest_cart'] ?? [];
    foreach ($guestCart as $productId => $cartItem) {
        if (isset($cartItem['product_id'])) {
            $sql = "SELECT product_id, product_name, base_price, unit_type 
                    FROM products 
                    WHERE product_id = ? AND approval_status = 'approved' AND is_listed = TRUE";
            $stmt = $farmcart->conn->prepare($sql);
            if ($stmt) {
                $stmt->bind_param("i", $productId);
                $stmt->execute();
                $result = $stmt->get_result();
                if ($product = $result->fetch_assoc()) {
                    $product['quantity'] = $cartItem['quantity'];
                    $product['cart_id'] = 'guest_' . $productId;
                    $items[] = $product;
                    $subtotal += $product['base_price'] * $cartItem['quantity'];
                }
                $stmt->close();
            }
        }
    }
}

// Redirect if cart is empty
if (empty($items)) {
    header('Location: cart.php');
    exit;
}

$total = $subtotal; // pickup only, no shipping fee
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Checkout | FarmCart</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="../../Assets/css/navbar.css" />
  <link rel="stylesheet" href="../../Assets/css/checkout.css" />
</head>
<body>

  <?php require_once '../../Includes/navbar.php'; ?>

  <div class="checkout-container">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h1 class="checkout-title mb-0">🧾 Checkout (Pickup)</h1>
      <a href="cart.php" class="btn btn-outline-secondary">
        <i class="fas fa-arrow-left me-2"></i>Back to Cart
      </a>
    </div>
    
    <?php if (isset($_SESSION['checkout_error'])): ?>
      <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?= htmlspecialchars($_SESSION['checkout_error']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
      <?php unset($_SESSION['checkout_error']); ?>
    <?php endif; ?>

    <div class="row">
      <!-- Left Column -->
      <div class="col-md-7">
        <form action="place_order.php" method="POST" class="checkout-form">

          <!-- Customer Info -->
          <div class="checkout-card">
            <h3><i class="fas fa-user"></i> Customer Information</h3>
            <p class="form-instructions">Your details are auto‑filled. Update if needed.</p>

            <div class="mb-3">
              <label class="form-label">Full Name</label>
              <input type="text" class="form-control" name="fullname"
                value="<?= htmlspecialchars(($customer['first_name'] ?? '') . ' ' . ($customer['last_name'] ?? '')) ?>" required>
            </div>
            <div class="mb-3">
              <label class="form-label">Email</label>
              <input type="email" class="form-control" name="email"
                value="<?= htmlspecialchars($customer['email'] ?? '') ?>" required>
            </div>
            <div class="mb-3">
              <label class="form-label">Phone</label>
              <input type="text" class="form-control" name="phone"
                value="<?= htmlspecialchars($customer['phone_number'] ?? '') ?>" required>
            </div>
          </div>

          <!-- Pickup Details -->
          <div class="checkout-card">
            <h3><i class="fas fa-store"></i> Pickup Details</h3>
            <p class="form-instructions">Select your preferred branch and schedule your pickup.</p>

            <div class="mb-3">
              <label class="form-label">Pickup Location</label>
              <select class="form-select" name="pickup_location" required>
                <option value="mankayan">Main Store – Mankayan</option>
                <option value="la_trinidad">Sub Store – La Trinidad</option>
              </select>
            </div>
            <div class="mb-3">
              <label class="form-label">Pickup Date</label>
              <input type="date" class="form-control" name="pickup_date" required>
            </div>
            <div class="alert alert-info">
              <i class="fas fa-info-circle me-2"></i>
              <strong>Note:</strong> The seller will notify you when your order is ready for pickup. You don't need to specify a time.
            </div>
          </div>

          <!-- Payment -->
          <div class="checkout-card">
            <h3><i class="fas fa-credit-card"></i> Payment Method</h3>
            <p class="form-instructions">Choose how you'll pay at pickup.</p>
            <div class="mb-3">
              <select class="form-select" name="payment_method" required>
                <option value="cod">Cash on Pickup</option>
                <option value="gcash">GCash</option>
                <option value="paymaya">PayMaya</option>
                <option value="bank_transfer">Bank Transfer</option>
              </select>
            </div>
          </div>
          
          <!-- Hidden fields for cart items -->
          <?php foreach ($items as $index => $item): ?>
            <input type="hidden" name="items[<?= $index; ?>][product_id]" value="<?= htmlspecialchars($item['product_id']); ?>">
            <input type="hidden" name="items[<?= $index; ?>][quantity]" value="<?= htmlspecialchars($item['quantity']); ?>">
            <input type="hidden" name="items[<?= $index; ?>][price]" value="<?= htmlspecialchars($item['base_price']); ?>">
          <?php endforeach; ?>
          <input type="hidden" name="total_amount" value="<?= $total; ?>">

          <!-- Final Reminder -->
          <p class="form-instructions">✅ Review your details carefully before confirming your pickup order.</p>
          <button type="submit" class="btn-place-order">Confirm Pickup Order</button>
        </form>
      </div>

      <!-- Right Column -->
      <div class="col-md-5">
        <div class="checkout-card order-summary">
          <h3><i class="fas fa-shopping-basket"></i> Order Summary</h3>
          <?php if (!empty($items)): ?>
            <?php foreach ($items as $item): ?>
              <div class="summary-item">
                <span><?= htmlspecialchars($item['product_name']); ?> (x<?= (int)$item['quantity']; ?>)</span>
                <span>₱<?= number_format($item['base_price'] * $item['quantity'], 2); ?></span>
              </div>
            <?php endforeach; ?>
            <hr>
            <div class="summary-total">
              <span>Total</span>
              <span>₱<?= number_format($total, 2); ?></span>
            </div>
          <?php else: ?>
            <p>Your cart is empty.</p>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

  <?php require_once '../../Includes/footer.php'; ?>

  <!-- Bootstrap JS Bundle (required for dropdown functionality) -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
