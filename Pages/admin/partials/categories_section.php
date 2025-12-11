<?php
global $categories;
?>

<div class="row">
    <div class="col-lg-4 mb-4">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">
                    <i class="fas fa-plus-circle me-2"></i>Add New Category
                </h5>
                <form method="post" class="admin-form">
                    <div class="mb-3">
                        <label class="form-label">Category Name</label>
                        <input type="text" name="category_name" class="form-control"
                               placeholder="e.g., Fruits, Livestock" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Category Type</label>
                        <select name="category_type" class="form-control" required>
                            <option value="">Select Category Type</option>
                            <option value="vegetables">Vegetables</option>
                            <option value="fruits">Fruits</option>
                            <option value="livestock">Livestock</option>
                            <option value="poultry">Poultry</option>
                            <option value="dairy">Dairy</option>
                            <option value="grains">Grains</option>
                            <option value="herbs">Herbs</option>
                        </select>
                        <div class="form-text">Select the type of category from the dropdown</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="3"
                                  placeholder="Optional description"></textarea>
                    </div>
                    <button type="submit" name="add_category" class="btn btn-success w-100">
                        <i class="fas fa-save me-2"></i>Add Category
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-list me-2"></i>Existing Categories
                    </h5>
                    <span class="badge bg-info">
                        <?= $categories->num_rows ?> Categories
                    </span>
                </div>

                <?php if ($categories->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table class="table admin-table">
                            <thead>
                                <tr>
                                    <th>Category Name</th>
                                    <th>Type</th>
                                    <th>Description</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($category = $categories->fetch_assoc()): ?>
                                    <tr>
                                        <td data-label="Category Name">
                                            <strong><?= htmlspecialchars($category['category_name']) ?></strong>
                                        </td>
                                        <td data-label="Type"><?= htmlspecialchars($category['category_type']) ?></td>
                                        <td data-label="Description"><?= htmlspecialchars($category['description']) ?></td>
                                        <td data-label="Status">
                                            <span class="badge bg-<?= $category['is_active'] ? 'success' : 'danger' ?>">
                                                <?= $category['is_active'] ? 'Active' : 'Inactive' ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-tags"></i>
                        <h5>No Categories Yet</h5>
                        <p>Add your first category using the form.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
