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
    $pickupTime = $_POST['pickup_time'] ?? '';
    $paymentMethod = $_POST['payment_method'] ?? 'cod';
    $items = $_POST['items'] ?? [];
    $totalAmount = $_POST['total_amount'] ?? 0;
    
    // Validate required fields
    if (empty($fullname) || empty($email) || empty($phone) || empty($pickupLocation) || 
        empty($pickupDate) || empty($pickupTime) || empty($items) || $totalAmount <= 0) {
        $_SESSION['checkout_error'] = 'Please fill in all required fields.';
        header('Location: checkout.php');
        exit;
    }
    
    // Build shipping address (pickup location + date/time)
    $shippingAddress = "Pickup at: " . ucfirst(str_replace('_', ' ', $pickupLocation)) . 
                       "\nDate: " . date('F d, Y', strtotime($pickupDate)) . 
                       "\nTime: " . date('h:i A', strtotime($pickupTime));
    
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
            
            // Find an available lot for this product
            // Try to find a lot with available quantity >= requested quantity
            $lotSql = "SELECT lot_id, available_quantity, selling_price 
                       FROM inventory_lots 
                       WHERE product_id = ? 
                         AND status = 'available' 
                         AND available_quantity >= ?
                       ORDER BY expiry_date ASC, created_at ASC
                       LIMIT 1";
            $lotStmt = $farmcart->conn->prepare($lotSql);
            $lotStmt->bind_param("id", $productId, $quantity);
            $lotStmt->execute();
            $lotResult = $lotStmt->get_result();
            
            if ($lotResult->num_rows > 0) {
                $lot = $lotResult->fetch_assoc();
                $lotId = $lot['lot_id'];
                $sellingPrice = $lot['selling_price'] ?? $unitPrice;
            } else {
                // If no lot found, try to find any available lot for this product (even with less quantity)
                $lotSql2 = "SELECT lot_id, available_quantity, selling_price 
                            FROM inventory_lots 
                            WHERE product_id = ? 
                              AND status = 'available' 
                              AND available_quantity > 0
                            ORDER BY expiry_date ASC, created_at ASC
                            LIMIT 1";
                $lotStmt2 = $farmcart->conn->prepare($lotSql2);
                $lotStmt2->bind_param("i", $productId);
                $lotStmt2->execute();
                $lotResult2 = $lotStmt2->get_result();
                
                if ($lotResult2->num_rows > 0) {
                    $lot = $lotResult2->fetch_assoc();
                    $lotId = $lot['lot_id'];
                    $sellingPrice = $lot['selling_price'] ?? $unitPrice;
                    // Adjust quantity to available quantity if needed
                    if ($lot['available_quantity'] < $quantity) {
                        $quantity = $lot['available_quantity'];
                    }
            } else {
                // No lot available - create a temporary lot automatically
                // Get product and farmer info
                $productSql = "SELECT p.base_price, p.created_by 
                               FROM products p 
                               WHERE p.product_id = ?";
                $productStmt = $farmcart->conn->prepare($productSql);
                $productStmt->bind_param("i", $productId);
                $productStmt->execute();
                $productResult = $productStmt->get_result();
                
                if ($productResult->num_rows > 0) {
                    $product = $productResult->fetch_assoc();
                    $farmerId = $product['created_by'] ?? $userId; // Use product creator as farmer
                    $sellingPrice = $product['base_price'] ?? $unitPrice;
                    
                    // Create a temporary lot for this order
                    $lotNumber = "TEMP_" . time() . "_" . $productId;
                    $createLotSql = "INSERT INTO inventory_lots 
                                     (product_id, farmer_id, lot_number, quantity, available_quantity, 
                                      selling_price, status) 
                                     VALUES (?, ?, ?, ?, ?, ?, 'available')";
                    $createLotStmt = $farmcart->conn->prepare($createLotSql);
                    $createLotStmt->bind_param("iisddd", $productId, $farmerId, $lotNumber, $quantity, $quantity, $sellingPrice);
                    
                    if ($createLotStmt->execute()) {
                        $lotId = $farmcart->conn->insert_id;
                        $orderNotes .= "\n\nNote: Auto-created lot for Product ID $productId (qty: $quantity).";
                    } else {
                        // If lot creation fails, skip this item
                        $orderNotes .= "\n\nNote: Product ID $productId (qty: $quantity) - Could not create lot. Please contact farmer.";
                        $productStmt->close();
                        continue;
                    }
                    $createLotStmt->close();
                } else {
                    $productStmt->close();
                    continue;
                }
                $productStmt->close();
            }
                $lotStmt2->close();
            }
            $lotStmt->close();
            
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
            
            // Update lot available quantity
            $updateLotSql = "UPDATE inventory_lots 
                             SET available_quantity = available_quantity - ? 
                             WHERE lot_id = ?";
            $updateLotStmt = $farmcart->conn->prepare($updateLotSql);
            $updateLotStmt->bind_param("di", $quantity, $lotId);
            $updateLotStmt->execute();
            $updateLotStmt->close();
            
            $itemStmt->close();
        }
        
        // Clear cart after successful order
        $clearCartSql = "DELETE FROM carts WHERE user_id = ?";
        $clearCartStmt = $farmcart->conn->prepare($clearCartSql);
        $clearCartStmt->bind_param("i", $userId);
        $clearCartStmt->execute();
        $clearCartStmt->close();
        
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

