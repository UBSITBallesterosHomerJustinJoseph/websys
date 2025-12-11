<?php
session_start();
include '../../db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check if user is logged in or not
    $userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
    $productId = $_POST['product_id'] ?? null;
    $quantity = $_POST['quantity'] ?? 1;

    // Validate the inputs
    if ($productId && is_numeric($quantity)) {
        // If user is logged in, associate the cart with the user
        if ($userId) {
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
                echo "Product quantity updated in your cart.";
            } else {
                // Add new product to cart
                $sql = "INSERT INTO carts (user_id, product_id, quantity) VALUES (?, ?, ?)";
                $stmt = $farmcart->conn->prepare($sql);
                $stmt->bind_param("iii", $userId, $productId, $quantity);
                $stmt->execute();
                echo "Product added to your cart.";
            }
        } else {
            // Handle case where user is not logged in
            // Optionally, store cart data in session or cookie for guest users
            $_SESSION['guest_cart'][$productId] = ($_SESSION['guest_cart'][$productId] ?? 0) + $quantity;
            echo "Product added to your cart (Guest).";
        }
    } else {
        echo "Invalid product or quantity.";
    }
} else {
    echo "Invalid request.";
}
?>
