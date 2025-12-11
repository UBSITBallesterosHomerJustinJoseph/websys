<?php
session_start();
include '../../db_connect.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check if user is logged in or not
    $userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
    $productId = $_POST['product_id'] ?? null;
    $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;

    // Validate the inputs
    if ($productId && is_numeric($quantity) && $quantity > 0) {
        // Verify product exists and is available
        $checkProduct = $farmcart->conn->prepare("SELECT product_id, base_price FROM products WHERE product_id = ? AND approval_status = 'approved' AND is_listed = TRUE AND (is_expired IS NULL OR is_expired = 0)");
        $checkProduct->bind_param("i", $productId);
        $checkProduct->execute();
        $productResult = $checkProduct->get_result();
        
        if ($productResult->num_rows === 0) {
            echo json_encode(['success' => false, 'message' => 'Product not found or not available.']);
            exit;
        }
        
        $product = $productResult->fetch_assoc();
        $checkProduct->close();
        
        // If user is logged in, associate the cart with the user
        if ($userId) {
            // Check if carts table exists, if not create it
            $checkTable = $farmcart->conn->query("SHOW TABLES LIKE 'carts'");
            if ($checkTable->num_rows === 0) {
                $createTable = "CREATE TABLE IF NOT EXISTS carts (
                    cart_id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NOT NULL,
                    product_id INT NOT NULL,
                    quantity INT NOT NULL DEFAULT 1,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    UNIQUE KEY unique_user_product (user_id, product_id),
                    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
                    FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE CASCADE
                )";
                $farmcart->conn->query($createTable);
            }
            
            $sql = "SELECT * FROM carts WHERE user_id = ? AND product_id = ?";
            $stmt = $farmcart->conn->prepare($sql);
            $stmt->bind_param("ii", $userId, $productId);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                // If product already in cart, update quantity
                $sql = "UPDATE carts SET quantity = quantity + ? WHERE user_id = ? AND product_id = ?";
                $stmt = $farmcart->conn->prepare($sql);
                $stmt->bind_param("iii", $quantity, $userId, $productId);
                if ($stmt->execute()) {
                    echo json_encode(['success' => true, 'message' => 'Product quantity updated in your cart.']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Failed to update cart.']);
                }
            } else {
                // Add new product to cart
                $sql = "INSERT INTO carts (user_id, product_id, quantity) VALUES (?, ?, ?)";
                $stmt = $farmcart->conn->prepare($sql);
                $stmt->bind_param("iii", $userId, $productId, $quantity);
                if ($stmt->execute()) {
                    echo json_encode(['success' => true, 'message' => 'Product added to your cart.']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Failed to add product to cart.']);
                }
            }
            $stmt->close();
        } else {
            // Handle case where user is not logged in
            if (!isset($_SESSION['guest_cart'])) {
                $_SESSION['guest_cart'] = [];
            }
            if (!isset($_SESSION['guest_cart'][$productId])) {
                $_SESSION['guest_cart'][$productId] = [
                    'product_id' => $productId,
                    'quantity' => 0,
                    'base_price' => $product['base_price']
                ];
            }
            $_SESSION['guest_cart'][$productId]['quantity'] += $quantity;
            echo json_encode(['success' => true, 'message' => 'Product added to your cart (Guest).']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid product or quantity.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}
?>
