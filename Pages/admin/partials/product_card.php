<?php
// Determine status and badge color
$status_badge = 'bg-warning';
$status_text = 'Pending';
$show_actions = true;

$farmer_name = htmlspecialchars($row['farmer_first'] . ' ' . $row['farmer_last']);
$farm_name = !empty($row['farm_name']) ? htmlspecialchars($row['farm_name']) : 'No farm name';

// Fix image path - if it's relative, prepend the correct path from admin directory
$image_url = !empty($row['image_url']) ? $row['image_url'] : '';
if (!empty($image_url) && !preg_match('/^https?:\/\//', $image_url)) {
    // If path starts with "uploads/", it's relative to farmer directory
    if (strpos($image_url, 'uploads/') === 0) {
        $image_url = '../farmer/' . $image_url;
    }
}
?>

<div class="col-md-4 col-lg-3">
    <div class="card product-card">
        <div class="position-absolute top-0 end-0 m-2">
            <span class="badge <?= $status_badge ?>"><?= $status_text ?></span>
        </div>

        <?php if (!empty($image_url)): ?>
            <img src="<?= htmlspecialchars($image_url); ?>"
                 class="card-img-top"
                 alt="<?= htmlspecialchars($row['product_name']); ?>"
                 style="height: 200px; object-fit: cover;"
                 onerror="this.src='https://via.placeholder.com/300x220?text=No+Image'">
        <?php else: ?>
            <div class="card-img-top bg-light d-flex align-items-center justify-content-center" style="height: 200px;">
                <i class="fas fa-seedling fa-3x text-muted"></i>
            </div>
        <?php endif; ?>

        <div class="card-body">
            <h5 class="card-title"><?= htmlspecialchars($row['product_name']); ?></h5>

            <p class="text-muted mb-1">
                <i class="fas fa-user me-1"></i>
                <?= $farmer_name ?>
            </p>

            <p class="text-muted mb-1">
                <i class="fas fa-tractor me-1"></i>
                <?= $farm_name ?>
            </p>

            <p class="text-muted mb-1">
                <i class="fas fa-tag me-1"></i>
                <?= htmlspecialchars($row['category_name']); ?> (<?= htmlspecialchars($row['category_type']); ?>)
            </p>

            <p class="mb-2">
                <strong class="text-success fs-5">₱<?= number_format($row['base_price'], 2); ?></strong>
                <?php if (!empty($row['unit_type'])): ?>
                    <span class="badge bg-secondary ms-2"><?= strtoupper($row['unit_type']); ?></span>
                <?php else: ?>
                    <span class="badge bg-secondary ms-2">N/A</span>
                <?php endif; ?>
            </p>
            <p class="text-muted small mb-2">
                <i class="fas fa-weight me-1"></i>Per <?= !empty($row['unit_type']) ? htmlspecialchars($row['unit_type']) : 'unit'; ?>
            </p>

            <p class="small mb-2 text-truncate-3">
                <?= nl2br(htmlspecialchars(substr($row['description'], 0, 150))); ?>
                <?= strlen($row['description']) > 150 ? '...' : ''; ?>
            </p>
        </div>

        <?php if ($show_actions): ?>
            <div class="card-footer bg-white border-top-0 pt-0">
                <div class="action-buttons d-flex flex-wrap gap-2">
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
                    <a href="?approve=<?= $row['product_id']; ?>"
                       class="btn btn-sm btn-success confirm-action"
                       data-action="approve this product">
                        <i class="fas fa-check me-1"></i>Approve
                    </a>
                    <button class="btn btn-sm btn-danger"
                            data-bs-toggle="modal"
                            data-bs-target="#declineModal"
                            data-id="<?= $row['product_id']; ?>"
                            data-name="<?= htmlspecialchars($row['product_name']); ?>">
                        <i class="fas fa-times me-1"></i>Decline
                    </button>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>
