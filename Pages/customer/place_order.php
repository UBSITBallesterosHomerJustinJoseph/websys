<?php
session_start();
include '../../db_connect.php';

$userId = $_SESSION['user_id'] ?? null;

if (!$userId) {
    header('Location: ../index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $fullname = $_POST['fullname'] ?? '';
    $email = $_POST['email'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $pickupLocation = $_POST['pickup_location'] ?? '';
    $pickupDate = $_POST['pickup_date'] ?? '';
    $paymentMethod = $_POST['payment_method'] ?? 'cod';
    $items = $_POST['items'] ?? [];
    $totalAmount = $_POST['total_amount'] ?? 0;
    
    // Validate required fields
    if (empty($fullname) || empty($email) || empty($phone) || empty($pickupLocation) || 
        empty($pickupDate) || empty($items) || $totalAmount <= 0) {
        $_SESSION['checkout_error'] = 'Please fill in all required fields.';
        header('Location: checkout.php');
        exit;
    }
    
    // Build shipping address (pickup location + date)
    $shippingAddress = "Pickup at: " . ucfirst(str_replace('_', ' ', $pickupLocation)) . 
                       "\nDate: " . date('F d, Y', strtotime($pickupDate)) . 
                       "\nNote: Seller will notify you when order is ready for pickup.";
    
    $orderNotes = "Customer: $fullname\nEmail: $email\nPhone: $phone";
    
    // Start transaction
    $farmcart->conn->begin_transaction();
    
    try {
        // Verify we have items
        if (empty($items)) {
            throw new Exception("Cart is empty. Please add items to your cart.");
        }
        
        // Create order first
        $orderSql = "INSERT INTO orders (customer_id, total_amount, status, shipping_address, order_notes, payment_method, payment_status) 
                     VALUES (?, ?, 'pending', ?, ?, ?, 'pending')";
        $orderStmt = $farmcart->conn->prepare($orderSql);
        $orderStmt->bind_param("idsss", $userId, $totalAmount, $shippingAddress, $orderNotes, $paymentMethod);
        
        if (!$orderStmt->execute()) {
            throw new Exception("Failed to create order: " . $orderStmt->error);
        }
        
        $orderId = $farmcart->conn->insert_id;
        $orderStmt->close();
        
        // Process each cart item
        foreach ($items as $item) {
            $productId = $item['product_id'] ?? null;
            $quantity = $item['quantity'] ?? 0;
            $unitPrice = $item['price'] ?? 0;
            
            if (!$productId || $quantity <= 0) {
                continue;
            }
            
            // Check product quantity and update it
            $checkProductSql = "SELECT quantity, base_price, created_by FROM products WHERE product_id = ?";
            $checkProductStmt = $farmcart->conn->prepare($checkProductSql);
            $checkProductStmt->bind_param("i", $productId);
            $checkProductStmt->execute();
            $productResult = $checkProductStmt->get_result();
            
            if ($productResult->num_rows === 0) {
                $checkProductStmt->close();
                continue;
            }
            
            $product = $productResult->fetch_assoc();
            $availableQty = (int)($product['quantity'] ?? 0);
            $sellingPrice = $product['base_price'] ?? $unitPrice;
            $farmerId = $product['created_by'] ?? $userId;
            
            // Adjust quantity if needed
            if ($availableQty < $quantity) {
                $quantity = $availableQty;
                if ($quantity <= 0) {
                    $checkProductStmt->close();
                    continue;
                }
            }
            
            // Create a lot for this order (for order_items table requirement)
            $lotNumber = "ORDER_" . time() . "_" . $productId;
            $createLotSql = "INSERT INTO inventory_lots 
                             (product_id, farmer_id, lot_number, quantity, available_quantity, 
                              selling_price, status) 
                             VALUES (?, ?, ?, ?, ?, ?, 'available')";
            $createLotStmt = $farmcart->conn->prepare($createLotSql);
            $createLotStmt->bind_param("iisddd", $productId, $farmerId, $lotNumber, $quantity, $quantity, $sellingPrice);
            
            if (!$createLotStmt->execute()) {
                $checkProductStmt->close();
                continue;
            }
            
            $lotId = $farmcart->conn->insert_id;
            $createLotStmt->close();
            $checkProductStmt->close();
            
            // Calculate subtotal
            $subtotal = $sellingPrice * $quantity;
            
            // Create order item
            $itemSql = "INSERT INTO order_items (order_id, lot_id, quantity, unit_price, subtotal) 
                        VALUES (?, ?, ?, ?, ?)";
            $itemStmt = $farmcart->conn->prepare($itemSql);
            $itemStmt->bind_param("iiddd", $orderId, $lotId, $quantity, $sellingPrice, $subtotal);
            
            if (!$itemStmt->execute()) {
                throw new Exception("Failed to create order item: " . $itemStmt->error);
            }
            
            // Update product quantity
            $updateProductSql = "UPDATE products SET quantity = quantity - ? WHERE product_id = ?";
            $updateProductStmt = $farmcart->conn->prepare($updateProductSql);
            $updateProductStmt->bind_param("ii", $quantity, $productId);
            $updateProductStmt->execute();
            $updateProductStmt->close();
            
            $itemStmt->close();
        }
        
        // Clear cart after successful order (both database and session)
        $clearCartSql = "DELETE FROM carts WHERE user_id = ?";
        $clearCartStmt = $farmcart->conn->prepare($clearCartSql);
        $clearCartStmt->bind_param("i", $userId);
        $clearCartStmt->execute();
        $clearCartStmt->close();
        
        // Also clear guest cart if exists
        if (isset($_SESSION['guest_cart'])) {
            unset($_SESSION['guest_cart']);
        }
        
        // Commit transaction
        $farmcart->conn->commit();
        
        // Redirect to order confirmation
        $_SESSION['order_success'] = true;
        $_SESSION['order_id'] = $orderId;
        header('Location: checkorders.php');
        exit;
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $farmcart->conn->rollback();
        $_SESSION['checkout_error'] = 'Failed to place order: ' . $e->getMessage();
        header('Location: checkout.php');
        exit;
    }
} else {
    header('Location: checkout.php');
    exit;
}
?>

