<?php
session_start();
include '../../db_connect.php';

$userId = $_SESSION['user_id'] ?? null;

$items = [];
$total = 0;

if ($userId) {
    // Logged-in users: fetch cart from DB
    $sql = "SELECT c.cart_id, c.product_id, p.product_name, c.quantity, p.base_price, p.unit_type,
                   pi.image_url
            FROM carts c
            JOIN products p ON c.product_id = p.product_id
            LEFT JOIN product_images pi ON p.product_id = pi.product_id AND pi.is_primary = TRUE
            WHERE c.user_id = ?";
    $stmt = $farmcart->conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $items[] = $row;
            $total += $row['base_price'] * $row['quantity'];
        }
        $stmt->close();
    }
} else {
    // Guest cart from session
    $guestCart = $_SESSION['guest_cart'] ?? [];
    foreach ($guestCart as $productId => $cartItem) {
        if (isset($cartItem['product_id'])) {
            // Fetch product details for guest cart
            $sql = "SELECT p.product_id, p.product_name, p.base_price, p.unit_type,
                           pi.image_url
                    FROM products p
                    LEFT JOIN product_images pi ON p.product_id = pi.product_id AND pi.is_primary = TRUE
                    WHERE p.product_id = ? AND p.approval_status = 'approved' AND p.is_listed = TRUE";
            $stmt = $farmcart->conn->prepare($sql);
            if ($stmt) {
                $stmt->bind_param("i", $productId);
                $stmt->execute();
                $result = $stmt->get_result();
                if ($product = $result->fetch_assoc()) {
                    $product['quantity'] = $cartItem['quantity'];
                    $product['cart_id'] = 'guest_' . $productId;
                    $items[] = $product;
                    $total += $product['base_price'] * $cartItem['quantity'];
                }
                $stmt->close();
            }
        }
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
  <style>
    .cart-items-list {
      margin-bottom: 30px;
    }
    
    .cart-item-img-placeholder {
      width: 80px;
      height: 80px;
      background: var(--light-bg);
      border-radius: 8px;
      display: flex;
      align-items: center;
      justify-content: center;
      color: var(--text-light);
      font-size: 2rem;
    }
    
    .cart-item-details {
      display: flex;
      flex-direction: column;
      gap: 8px;
      flex-grow: 1;
    }
    
    .cart-item-controls {
      display: flex;
      align-items: center;
      gap: 10px;
      margin-top: 10px;
    }
    
    .qty-btn {
      width: 32px;
      height: 32px;
      border: 1px solid var(--border-light);
      background: var(--white);
      border-radius: 6px;
      display: flex;
      align-items: center;
      justify-content: center;
      cursor: pointer;
      transition: all 0.2s ease;
      color: var(--text-dark);
    }
    
    .qty-btn:hover {
      background: var(--primary-green);
      color: var(--white);
      border-color: var(--primary-green);
    }
    
    .qty-btn:disabled {
      opacity: 0.5;
      cursor: not-allowed;
    }
    
    .qty-btn i {
      font-size: 0.75rem;
    }
    
    .btn-remove {
      background: transparent;
      border: 1px solid #dc3545;
      color: #dc3545;
      padding: 6px 12px;
      border-radius: 6px;
      cursor: pointer;
      transition: all 0.2s ease;
      font-size: 0.875rem;
    }
    
    .btn-remove:hover {
      background: #dc3545;
      color: var(--white);
    }
    
    .price-per-unit {
      font-size: 0.875rem;
      color: var(--text-light);
      margin-bottom: 4px;
    }
    
    .price-total {
      font-size: 1.2rem;
      font-weight: 700;
    }
    
    .cart-actions {
      display: flex;
      gap: 15px;
      flex-wrap: wrap;
    }
    
    .btn-checkout, .btn-back {
      text-decoration: none;
      display: inline-flex;
      align-items: center;
    }
    
    @media (max-width: 768px) {
      .cart-item {
        flex-direction: column;
        align-items: flex-start;
        gap: 15px;
      }
      
      .cart-item-price {
        text-align: left;
        width: 100%;
      }
      
      .cart-actions {
        flex-direction: column;
      }
      
      .btn-checkout, .btn-back {
        width: 100%;
        justify-content: center;
      }
    }
  </style>
</head>
<body>

  <!-- Navbar -->
  <?php require_once '../../Includes/navbar.php'; ?>

  <div class="cart-container">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h1 class="cart-title mb-0">🛒 Your Shopping Cart</h1>
      <a href="products.php" class="btn btn-outline-secondary">
        <i class="fas fa-arrow-left me-2"></i>Back to Products
      </a>
    </div>

    <?php if (!empty($items)): ?>
      <div class="cart-items-list">
        <?php foreach ($items as $item): ?>
          <?php
          $image_url = !empty($item['image_url']) ? $item['image_url'] : '';
          if (!empty($image_url) && !preg_match('/^https?:\/\//', $image_url)) {
              if (strpos($image_url, 'uploads/') === 0) {
                  $image_url = '../farmer/' . $image_url;
              }
          }
          ?>
          <div class="cart-item" data-cart-id="<?= htmlspecialchars($item['cart_id']); ?>" data-product-id="<?= htmlspecialchars($item['product_id']); ?>">
            <div class="cart-item-info">
              <?php if (!empty($image_url)): ?>
                <img src="<?= htmlspecialchars($image_url); ?>" alt="<?= htmlspecialchars($item['product_name']); ?>" class="cart-item-img" onerror="this.style.display='none'">
              <?php else: ?>
                <div class="cart-item-img-placeholder">
                  <i class="fas fa-seedling"></i>
                </div>
              <?php endif; ?>
              
              <div class="cart-item-details">
                <span class="cart-item-name"><?= htmlspecialchars($item['product_name']); ?></span>
                <span class="cart-item-unit">Per <?= htmlspecialchars($item['unit_type'] ?? 'unit'); ?></span>
                
                <div class="cart-item-controls">
                  <button class="qty-btn qty-decrease" onclick="updateQuantity(<?= htmlspecialchars($item['cart_id']); ?>, <?= htmlspecialchars($item['product_id']); ?>, -1)" title="Decrease quantity">
                    <i class="fas fa-minus"></i>
                  </button>
                  <span class="cart-item-qty"><?= (int)$item['quantity']; ?></span>
                  <button class="qty-btn qty-increase" onclick="updateQuantity(<?= htmlspecialchars($item['cart_id']); ?>, <?= htmlspecialchars($item['product_id']); ?>, 1)" title="Increase quantity">
                    <i class="fas fa-plus"></i>
                  </button>
                  <button class="btn-remove" onclick="removeFromCart(<?= htmlspecialchars($item['cart_id']); ?>, <?= htmlspecialchars($item['product_id']); ?>)" title="Remove item">
                    <i class="fas fa-trash"></i>
                  </button>
                </div>
              </div>
            </div>

            <div class="cart-item-price">
              <div class="price-per-unit">₱<?= number_format($item['base_price'], 2); ?> each</div>
              <div class="price-total">₱<?= number_format($item['base_price'] * $item['quantity'], 2); ?></div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>

      <div class="cart-footer">
        <div class="cart-total">
          <span>Total:</span>
          <span>₱<?= number_format($total, 2); ?></span>
        </div>

        <div class="cart-actions">
          <a href="checkout.php" class="btn-checkout">
            <i class="fas fa-credit-card me-2"></i>Proceed to Checkout
          </a>

          <a href="products.php" class="btn-back">
            <i class="fas fa-arrow-left me-2"></i>Continue Shopping
          </a>
        </div>
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
  
  <script>
    function updateQuantity(cartId, productId, change) {
      const cartItem = document.querySelector(`[data-cart-id="${cartId}"]`);
      const qtySpan = cartItem.querySelector('.cart-item-qty');
      const currentQty = parseInt(qtySpan.textContent);
      const newQty = currentQty + change;
      
      if (newQty < 1) {
        removeFromCart(cartId, productId);
        return;
      }
      
      // Disable buttons during update
      const buttons = cartItem.querySelectorAll('.qty-btn');
      buttons.forEach(btn => btn.disabled = true);
      
      const formData = new FormData();
      formData.append('action', 'update');
      formData.append('cart_id', cartId);
      formData.append('product_id', productId);
      formData.append('quantity', newQty);
      
      fetch('update_cart.php', {
        method: 'POST',
        body: formData
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          qtySpan.textContent = newQty;
          // Update price
          const priceTotal = cartItem.querySelector('.price-total');
          const basePrice = parseFloat(priceTotal.textContent.replace(/[₱,]/g, '')) / currentQty;
          priceTotal.textContent = '₱' + (basePrice * newQty).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
          
          // Update total
          location.reload(); // Simple reload to update total
        } else {
          alert(data.message || 'Failed to update quantity.');
          buttons.forEach(btn => btn.disabled = false);
        }
      })
      .catch(error => {
        console.error('Error:', error);
        alert('An error occurred. Please try again.');
        buttons.forEach(btn => btn.disabled = false);
      });
    }
    
    function removeFromCart(cartId, productId) {
      if (!confirm('Are you sure you want to remove this item from your cart?')) {
        return;
      }
      
      const cartItem = document.querySelector(`[data-cart-id="${cartId}"]`);
      cartItem.style.opacity = '0.5';
      
      const formData = new FormData();
      formData.append('action', 'remove');
      formData.append('cart_id', cartId);
      formData.append('product_id', productId);
      
      fetch('update_cart.php', {
        method: 'POST',
        body: formData
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          cartItem.remove();
          // Reload to update total and check if cart is empty
          location.reload();
        } else {
          alert(data.message || 'Failed to remove item.');
          cartItem.style.opacity = '1';
        }
      })
      .catch(error => {
        console.error('Error:', error);
        alert('An error occurred. Please try again.');
        cartItem.style.opacity = '1';
      });
    }
  </script>
</body>
</html>
