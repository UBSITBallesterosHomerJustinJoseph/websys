<?php
// Admin/partials/product_card_approved.php
$status_badge = 'bg-success';
$status_text = 'Approved';
$show_actions = false;

$farmer_name = htmlspecialchars($row['farmer_first'] . ' ' . $row['farmer_last']);
$farm_name = !empty($row['farm_name']) ? htmlspecialchars($row['farm_name']) : 'No farm name';
$expires_at = !empty($row['expires_at']) ? date('M d, Y h:i A', strtotime($row['expires_at'])) : 'No expiry set';

// Normalize image path for admin view
$image_url = !empty($row['image_url']) ? $row['image_url'] : '';
if (!empty($image_url) && !preg_match('/^https?:\/\//', $image_url)) {
    if (strpos($image_url, 'uploads/') === 0) {
        $image_url = '../farmer/' . $image_url;
    }
}
?>

<div class="col-md-4 col-lg-3 mb-4">
    <div class="card product-card h-100">
        <div class="position-absolute top-0 end-0 m-2">
            <span class="badge <?= $status_badge ?>"><?= $status_text ?></span>
        </div>

        <?php if (!empty($image_url)): ?>
            <img src="<?= htmlspecialchars($image_url); ?>"
                 class="card-img-top"
                 alt="<?= htmlspecialchars($row['product_name']); ?>"
                 style="height: 200px; object-fit: cover;"
                 onerror="this.src='https://via.placeholder.com/300x200?text=No+Image'">
        <?php else: ?>
            <div class="card-img-top bg-light d-flex align-items-center justify-content-center" style="height: 200px;">
                <i class="fas fa-seedling fa-3x text-muted"></i>
            </div>
        <?php endif; ?>

        <div class="card-body d-flex flex-column">
            <h5 class="card-title"><?= htmlspecialchars($row['product_name']); ?></h5>

            <div class="mb-2">
                <small class="text-muted">
                    <i class="fas fa-user me-1"></i>
                    <?= $farmer_name ?>
                </small>
            </div>

            <div class="mb-2">
                <small class="text-muted">
                    <i class="fas fa-tractor me-1"></i>
                    <?= $farm_name ?>
                </small>
            </div>

            <div class="mb-2">
                <small class="text-muted">
                    <i class="fas fa-tag me-1"></i>
                    <?= htmlspecialchars($row['category_name']); ?> (<?= htmlspecialchars($row['category_type']); ?>)
                </small>
            </div>

            <div class="mb-2">
                <small class="text-muted">
                    <i class="fas fa-money-bill-wave me-1"></i>
                    ₱<?= number_format($row['base_price'], 2); ?> per <?= $row['unit_type']; ?>
                </small>
            </div>
            <div class="mt-auto">
                <small class="text-muted">
                    <i class="fas fa-calendar me-1"></i>
                    Added: <?= date('M d, Y', strtotime($row['created_at'])); ?>
                </small>
            </div>
            <div class="mb-2">
                <small class="text-muted">
                    <i class="fas fa-hourglass-half me-1"></i>
                    Expires: <?= $expires_at; ?>
                </small>
            </div>

            <p class="small mb-3 flex-grow-1">
                <?= nl2br(htmlspecialchars(substr($row['description'], 0, 120))); ?>
                <?= strlen($row['description']) > 120 ? '...' : ''; ?>
            </p>

            
        </div>
        <div class="card-footer bg-white border-top-0">
            <div class="d-grid gap-2">
                <button type="button"
                        class="btn btn-sm btn-outline-secondary"
                        data-bs-toggle="modal"
                        data-bs-target="#productDetailsModal"
                        data-product='<?= json_encode([
                            'name' => $row['product_name'],
                            'category' => $row['category_name'],
                            'category_type' => $row['category_type'],
                            'unit' => $row['unit_type'],
                            'price' => number_format($row['base_price'], 2),
                            'description' => $row['description'],
                            'expires_at' => !empty($row['expires_at']) ? date('M d, Y h:i A', strtotime($row['expires_at'])) : 'No expiry set',
                        ], JSON_HEX_APOS | JSON_HEX_QUOT); ?>'>
                    <i class="fas fa-list me-1"></i>Details
                </button>
                <?php if (!empty($image_url)): ?>
                    <button type="button" class="btn btn-sm btn-outline-primary"
                            onclick="viewImage('<?= htmlspecialchars($image_url, ENT_QUOTES) ?>', '<?= htmlspecialchars($row['product_name'], ENT_QUOTES) ?>')">
                        <i class="fas fa-eye me-1"></i>View Image
                    </button>
                <?php else: ?>
                    <span class="btn btn-sm btn-outline-secondary disabled">
                        <i class="fas fa-image me-1"></i>No Image
                    </span>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
