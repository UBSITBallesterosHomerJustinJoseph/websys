<?php
// Make sure $farmers is available
global $farmcart;
?>

<div class="card">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h5 class="card-title mb-0">
                <i class="fas fa-tractor me-2"></i>Farmer Management
            </h5>
            <span class="badge bg-info">
                <?= $farmers->num_rows ?> Total Farmers
            </span>
        </div>

        <?php if ($farmers->num_rows > 0): ?>
            <div class="table-responsive">
                <table class="table admin-table">
                    <thead>
                        <tr>
                            <th>Farmer Name</th>
                            <th>Farm Name</th>
                            <th>Location</th>
                            <th>Farming Method</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($farmer = $farmers->fetch_assoc()): ?>
                            <tr>
                                <td data-label="Farmer Name">
                                    <strong><?= htmlspecialchars($farmer['first_name'] . ' ' . $farmer['last_name']) ?></strong><br>
                                    <small class="text-muted"><?= htmlspecialchars($farmer['email']) ?></small>
                                </td>
                                <td data-label="Farm Name"><?= htmlspecialchars($farmer['farm_name'] ?? 'Not set') ?></td>
                                <td data-label="Location"><?= htmlspecialchars($farmer['farm_location'] ?? 'Not set') ?></td>
                                <td data-label="Farming Method"><?= htmlspecialchars($farmer['farming_method'] ?? 'Not specified') ?></td>
                                <td data-label="Status">
                                    <span class="badge bg-<?= $farmer['is_verified_farmer'] ? 'success' : 'warning' ?>">
                                        <?= $farmer['is_verified_farmer'] ? 'Verified' : 'Pending' ?>
                                    </span>
                                </td>
                                <td data-label="Actions">
                                    <?php if (!$farmer['is_verified_farmer']): ?>
                                        <a href="?verify_farmer=<?= $farmer['user_id'] ?>"
                                           class="btn btn-sm btn-success confirm-action"
                                           data-action="verify this farmer">
                                            <i class="fas fa-check me-1"></i>Verify
                                        </a>
                                    <?php else: ?>
                                        <span class="text-success">
                                            <i class="fas fa-check-circle me-1"></i>Verified
                                        </span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-tractor"></i>
                <h5>No Farmers Registered</h5>
                <p class="text-muted">There are no registered farmers yet.</p>
            </div>
        <?php endif; ?>
    </div>
</div>
