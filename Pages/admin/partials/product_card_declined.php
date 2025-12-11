<?php
$status_badge = 'bg-danger';
$status_text = 'Declined';
$show_actions = false;

$farmer_name = htmlspecialchars($row['farmer_first'] . ' ' . $row['farmer_last']);
$farm_name = !empty($row['farm_name']) ? htmlspecialchars($row['farm_name']) : 'No farm name';

// Check if admin_notes exists (it might be called decline_reason in some versions)
$decline_reason = !empty($row['admin_notes']) ? $row['admin_notes'] : 'No reason provided';

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
                <span class="badge bg-secondary ms-2"><?= strtoupper($row['unit_type']); ?></span>
            </p>
            <p class="text-muted small mb-2">
                <i class="fas fa-weight me-1"></i>Per <?= htmlspecialchars($row['unit_type']); ?>
            </p>

            <p class="small mb-2 text-truncate-3">
                <?= nl2br(htmlspecialchars(substr($row['description'], 0, 150))); ?>
                <?= strlen($row['description']) > 150 ? '...' : ''; ?>
            </p>

            <div class="alert alert-danger small p-2 mt-2">
                <strong><i class="fas fa-exclamation-circle me-1"></i>Decline Reason:</strong><br>
                <?= nl2br(htmlspecialchars($decline_reason)); ?>
                <?php if (!empty($row['reviewer_first'])): ?>
                    <hr class="my-1">
                    <small>Reviewed by: <?= htmlspecialchars($row['reviewer_first'] . ' ' . $row['reviewer_last']); ?></small>
                <?php endif; ?>
            </div>
        </div>

        <div class="card-footer bg-white border-top-0">
            <?php if (!empty($image_url)): ?>
                <button type="button" class="btn btn-sm btn-outline-primary w-100"
                        onclick="viewImage('<?= htmlspecialchars($image_url, ENT_QUOTES) ?>', '<?= htmlspecialchars($row['product_name'], ENT_QUOTES) ?>')">
                    <i class="fas fa-eye me-1"></i>View Image
                </button>
            <?php else: ?>
                <span class="btn btn-sm btn-outline-secondary disabled w-100">
                    <i class="fas fa-image me-1"></i>No Image
                </span>
            <?php endif; ?>
        </div>
    </div>
</div>
