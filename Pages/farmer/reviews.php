<?php
session_start();
include '../../db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'farmer') {
    header('Location: ../../Register/login.php');
    exit();
}

$farmer_id = (int)$_SESSION['user_id'];
$reviews = $farmcart->getFarmerReviews($farmer_id, 100);

// Get overall stats
$pdo = $farmcart->getPDO();
$stats_stmt = $pdo->prepare("
    SELECT 
        AVG(r.rating) as avg_rating,
        COUNT(*) as total_reviews,
        COUNT(CASE WHEN r.rating = 5 THEN 1 END) as five_star,
        COUNT(CASE WHEN r.rating = 4 THEN 1 END) as four_star,
        COUNT(CASE WHEN r.rating = 3 THEN 1 END) as three_star,
        COUNT(CASE WHEN r.rating = 2 THEN 1 END) as two_star,
        COUNT(CASE WHEN r.rating = 1 THEN 1 END) as one_star
    FROM reviews r
    JOIN products p ON r.product_id = p.product_id
    WHERE p.created_by = ? AND r.is_approved = 1
");
$stats_stmt->execute([$farmer_id]);
$stats = $stats_stmt->fetch();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Reviews | FarmCart</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .review-card {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .stats-card {
            background: linear-gradient(135deg, #0F2E15 0%, #1a4a2a 100%);
            color: white;
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }
    </style>
</head>
<body>
    <?php include '../../Includes/navbar.php'; ?>
    
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3"><i class="fas fa-star me-2"></i>Product Reviews</h1>
            <a href="index.php" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i>Back to Dashboard
            </a>
        </div>

        <?php if ($stats['total_reviews'] > 0): ?>
            <!-- Stats Card -->
            <div class="stats-card">
                <div class="row text-center">
                    <div class="col-md-3">
                        <h2 class="mb-0"><?= number_format($stats['avg_rating'], 1); ?></h2>
                        <p class="mb-0">Average Rating</p>
                        <div class="text-warning mt-2">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <i class="fas fa-star<?= $i <= round($stats['avg_rating']) ? '' : '-o' ?>"></i>
                            <?php endfor; ?>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <h2 class="mb-0"><?= $stats['total_reviews']; ?></h2>
                        <p class="mb-0">Total Reviews</p>
                    </div>
                    <div class="col-md-3">
                        <h2 class="mb-0"><?= $stats['five_star'] + $stats['four_star']; ?></h2>
                        <p class="mb-0">Positive (4-5★)</p>
                    </div>
                    <div class="col-md-3">
                        <h2 class="mb-0"><?= $stats['one_star'] + $stats['two_star']; ?></h2>
                        <p class="mb-0">Needs Improvement</p>
                    </div>
                </div>
            </div>

            <!-- Rating Distribution -->
            <div class="card mb-4">
                <div class="card-body">
                    <h5 class="card-title">Rating Distribution</h5>
                    <?php for ($rating = 5; $rating >= 1; $rating--): ?>
                        <?php 
                        $count = $stats[strval($rating) . '_star'] ?? 0;
                        $percentage = $stats['total_reviews'] > 0 ? ($count / $stats['total_reviews']) * 100 : 0;
                        ?>
                        <div class="d-flex align-items-center mb-2">
                            <div class="me-2" style="width: 100px;">
                                <span><?= $rating; ?>★</span>
                            </div>
                            <div class="flex-grow-1">
                                <div class="progress" style="height: 20px;">
                                    <div class="progress-bar bg-warning" role="progressbar" 
                                         style="width: <?= $percentage; ?>%">
                                        <?= $count; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endfor; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Reviews List -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">All Reviews</h5>
            </div>
            <div class="card-body">
                <?php if (!empty($reviews)): ?>
                    <?php foreach ($reviews as $review): ?>
                        <div class="review-card">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div>
                                    <h6 class="mb-1"><?= htmlspecialchars($review['product_name']); ?></h6>
                                    <div class="text-warning mb-2">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <i class="fas fa-star<?= $i <= $review['rating'] ? '' : '-o' ?>"></i>
                                        <?php endfor; ?>
                                    </div>
                                </div>
                                <small class="text-muted"><?= date('M d, Y h:i A', strtotime($review['created_at'])); ?></small>
                            </div>
                            
                            <?php if (!empty($review['review_text'])): ?>
                                <p class="mb-2"><?= nl2br(htmlspecialchars($review['review_text'])); ?></p>
                            <?php endif; ?>
                            
                            <div class="d-flex align-items-center">
                                <?php if (!empty($review['profile_image'])): ?>
                                    <img src="../../<?= htmlspecialchars($review['profile_image']); ?>" 
                                         alt="Customer" 
                                         class="rounded-circle me-2" 
                                         style="width: 30px; height: 30px; object-fit: cover;">
                                <?php else: ?>
                                    <i class="fas fa-user-circle fa-2x text-muted me-2"></i>
                                <?php endif; ?>
                                <div>
                                    <strong><?= htmlspecialchars($review['first_name'] . ' ' . $review['last_name']); ?></strong>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="text-center py-5">
                        <i class="fas fa-star fa-3x text-muted mb-3"></i>
                        <h5>No Reviews Yet</h5>
                        <p class="text-muted">Reviews from customers will appear here once they rate your products.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

