<?php
session_start();
include '../../db_connect.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = $_SESSION['user_id'] ?? null;
    $action = $_POST['action'] ?? '';
    $cartId = $_POST['cart_id'] ?? null;
    $productId = $_POST['product_id'] ?? null;
    $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : null;

    if (!$productId) {
        echo json_encode(['success' => false, 'message' => 'Product ID is required.']);
        exit;
    }

    if ($userId) {
        // Logged-in user - cartId should be numeric
        if ($action === 'update' && $quantity !== null && $quantity > 0) {
            $cartIdInt = (int)$cartId;
            $sql = "UPDATE carts SET quantity = ? WHERE cart_id = ? AND user_id = ?";
            $stmt = $farmcart->conn->prepare($sql);
            $stmt->bind_param("iii", $quantity, $cartIdInt, $userId);
            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Cart updated.']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to update cart.']);
            }
            $stmt->close();
        } elseif ($action === 'remove') {
            $cartIdInt = (int)$cartId;
            $sql = "DELETE FROM carts WHERE cart_id = ? AND user_id = ?";
            $stmt = $farmcart->conn->prepare($sql);
            $stmt->bind_param("ii", $cartIdInt, $userId);
            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Item removed from cart.']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to remove item.']);
            }
            $stmt->close();
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid action.']);
        }
    } else {
        // Guest user
        if (!isset($_SESSION['guest_cart'])) {
            $_SESSION['guest_cart'] = [];
        }
        
        if ($action === 'update' && $quantity !== null && $quantity > 0) {
            if (isset($_SESSION['guest_cart'][$productId])) {
                $_SESSION['guest_cart'][$productId]['quantity'] = $quantity;
                echo json_encode(['success' => true, 'message' => 'Cart updated.']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Item not found in cart.']);
            }
        } elseif ($action === 'remove') {
            if (isset($_SESSION['guest_cart'][$productId])) {
                unset($_SESSION['guest_cart'][$productId]);
                echo json_encode(['success' => true, 'message' => 'Item removed from cart.']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Item not found in cart.']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid action.']);
        }
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}
?>

