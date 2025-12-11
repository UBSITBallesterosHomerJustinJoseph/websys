<?php
session_start();
include '../../db_connect.php';

header('Content-Type: application/json');

// Check if user is logged in and is a farmer
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'farmer') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $farmer_id = $_SESSION['user_id'];
    $product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
    $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 0;
    
    // Validate inputs
    if ($product_id <= 0 || $quantity <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid product ID or quantity.']);
        exit;
    }
    
    // Verify product belongs to this farmer
    $verify_sql = "SELECT product_id, product_name, quantity FROM products WHERE product_id = ? AND created_by = ?";
    $verify_stmt = $farmcart->conn->prepare($verify_sql);
    $verify_stmt->bind_param("ii", $product_id, $farmer_id);
    $verify_stmt->execute();
    $verify_result = $verify_stmt->get_result();
    
    if ($verify_result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Product not found or you do not have permission to update it.']);
        $verify_stmt->close();
        exit;
    }
    
    $product = $verify_result->fetch_assoc();
    $verify_stmt->close();
    
    // Update quantity
    $new_quantity = ($product['quantity'] ?? 0) + $quantity;
    $update_sql = "UPDATE products SET quantity = ? WHERE product_id = ? AND created_by = ?";
    $update_stmt = $farmcart->conn->prepare($update_sql);
    $update_stmt->bind_param("iii", $new_quantity, $product_id, $farmer_id);
    
    if ($update_stmt->execute()) {
        echo json_encode([
            'success' => true, 
            'message' => "Successfully added {$quantity} units. New total: {$new_quantity} units.",
            'new_quantity' => $new_quantity
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update stock: ' . $update_stmt->error]);
    }
    
    $update_stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}
?>

