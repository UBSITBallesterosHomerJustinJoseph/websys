<?php
global $users;
?>

<div class="card">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h5 class="card-title mb-0">
                <i class="fas fa-users me-2"></i>User Accounts
            </h5>
            <span class="badge bg-info">
                <?= $users->num_rows ?> Total Users
            </span>
        </div>

        <?php if ($users->num_rows > 0): ?>
            <div class="table-responsive">
                <table class="table admin-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Role</th>
                            <th>Joined</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($user = $users->fetch_assoc()): ?>
                            <tr>
                                <td data-label="Name">
                                    <strong><?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?></strong>
                                </td>
                                <td data-label="Email"><?= htmlspecialchars($user['email']) ?></td>
                                <td data-label="Phone"><?= htmlspecialchars($user['phone_number'] ?? 'Not set') ?></td>
                                <td data-label="Role">
                                    <span class="badge bg-<?=
                                        $user['role'] == 'admin' ? 'danger' :
                                        ($user['role'] == 'farmer' ? 'success' : 'primary')
                                    ?>">
                                        <?= ucfirst($user['role']) ?>
                                    </span>
                                </td>
                                <td data-label="Joined">
                                    <?= date('M d, Y', strtotime($user['created_at'])) ?>
                                </td>
                                <td data-label="Status">
                                    <span class="badge bg-<?= $user['is_verified'] ? 'success' : 'warning' ?>">
                                        <?= $user['is_verified'] ? 'Verified' : 'Pending' ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-users"></i>
                <h5>No Users Registered</h5>
                <p>There are no registered users yet.</p>
            </div>
        <?php endif; ?>
    </div>
</div>
