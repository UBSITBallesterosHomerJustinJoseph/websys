<?php
global $users;
?>
<!-- Delete User Modal -->
<div class="modal fade" id="deleteUserModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="post" class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Delete User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="user_id_to_delete" id="user_id_to_delete" value="">
                <p>Are you sure you want to delete user: <strong id="user_name_to_delete"></strong>?</p>
                <p class="text-danger"><strong>Warning:</strong> This action cannot be undone. All associated data will be deleted.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" name="delete_user" class="btn btn-danger">Delete User</button>
            </div>
        </form>
    </div>
</div>

<script>
function deleteUser(userId, userName) {
    document.getElementById('user_id_to_delete').value = userId;
    document.getElementById('user_name_to_delete').textContent = userName;
    const modal = new bootstrap.Modal(document.getElementById('deleteUserModal'));
    modal.show();
}
</script>

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
                            <th>Actions</th>
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
                                <td data-label="Actions">
                                    <?php if ($user['user_id'] != $_SESSION['user_id']): ?>
                                        <button class="btn btn-sm btn-danger" 
                                                onclick="deleteUser(<?= $user['user_id'] ?>, '<?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name'], ENT_QUOTES) ?>')"
                                                title="Delete User">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    <?php else: ?>
                                        <span class="text-muted small">Current User</span>
                                    <?php endif; ?>
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
