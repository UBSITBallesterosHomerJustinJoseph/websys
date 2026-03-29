<?php
session_start();
include '../../db_connect.php';

header('Content-Type: application/json');

$product_id = isset($_GET['product_id']) ? (int)$_GET['product_id'] : 0;

if ($product_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid product ID.']);
    exit();
}

$reviews = $farmcart->getProductReviews($product_id, 10);
$rating_data = $farmcart->getProductRating($product_id);

echo json_encode([
    'success' => true,
    'reviews' => $reviews,
    'rating' => $rating_data
]);
exit();
?>

