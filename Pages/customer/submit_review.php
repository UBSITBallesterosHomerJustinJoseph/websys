<?php
session_start();
include '../../db_connect.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please log in to submit a review.']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit();
}

$customer_id = (int)$_SESSION['user_id'];
$product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
$order_id = isset($_POST['order_id']) ? (int)$_POST['order_id'] : 0;
$rating = isset($_POST['rating']) ? (int)$_POST['rating'] : 0;
$review_text = isset($_POST['review_text']) ? trim($_POST['review_text']) : '';

// Validation
if ($product_id <= 0 || $order_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid product or order ID.']);
    exit();
}

if ($rating < 1 || $rating > 5) {
    echo json_encode(['success' => false, 'message' => 'Rating must be between 1 and 5.']);
    exit();
}

// Submit review
$result = $farmcart->addReview($product_id, $customer_id, $order_id, $rating, $review_text);
echo json_encode($result);
exit();
?>

