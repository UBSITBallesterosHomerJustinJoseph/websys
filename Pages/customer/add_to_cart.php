<?php
session_start();
include '../../db_connect.php';

// Check if user is logged in
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    // Return JSON response for AJAX requests
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Please log in to add products to cart.',
        'redirect' => '/websys/Register/login.php?redirect=' . urlencode($_SERVER['HTTP_REFERER'] ?? '/websys/Pages/customer/products.php')
    ]);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = $_SESSION['user_id'];
    $productId = $_POST['product_id'] ?? null;
    $quantity = $_POST['quantity'] ?? 1;

    // Validate the inputs
    if ($productId && is_numeric($quantity) && $quantity > 0) {
        // Check if product exists and is available
        $check_product = $farmcart->conn->prepare("SELECT product_id, base_price FROM products WHERE product_id = ? AND approval_status = 'approved' AND is_listed = 1 AND (is_expired IS NULL OR is_expired = 0)");
        $check_product->bind_param("i", $productId);
        $check_product->execute();
        $product_result = $check_product->get_result();
        
        if ($product_result->num_rows === 0) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Product not available.']);
            exit();
        }
        
        // Check if product already in cart
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
            $stmt->execute();
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'message' => 'Product quantity updated in your cart.']);
        } else {
            // Add new product to cart
            $sql = "INSERT INTO carts (user_id, product_id, quantity) VALUES (?, ?, ?)";
            $stmt = $farmcart->conn->prepare($sql);
            $stmt->bind_param("iii", $userId, $productId, $quantity);
            $stmt->execute();
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'message' => 'Product added to your cart.']);
        }
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Invalid product or quantity.']);
    }
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
}
?>
