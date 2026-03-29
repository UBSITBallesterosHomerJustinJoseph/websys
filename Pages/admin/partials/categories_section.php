<?php
global $categories;
// Reset pointer if needed
if ($categories && is_object($categories)) {
    $categories->data_seek(0);
}

// Display success/error messages
if (isset($_SESSION['admin_success'])) {
    echo '<div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i>' . htmlspecialchars($_SESSION['admin_success']) . '
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
          </div>';
    unset($_SESSION['admin_success']);
}

if (isset($_SESSION['admin_error'])) {
    echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i>' . htmlspecialchars($_SESSION['admin_error']) . '
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
          </div>';
    unset($_SESSION['admin_error']);
}
?>

<div class="row">
    <div class="col-lg-4 mb-4">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">
                    <i class="fas fa-plus-circle me-2"></i>Add New Category
                </h5>
                <form method="post" enctype="multipart/form-data" class="admin-form">
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
                    <div class="mb-3">
                        <label class="form-label">Category Image</label>
                        <input type="file" name="category_image" class="form-control" accept="image/*">
                        <div class="form-text">Upload an image for this category (JPG, PNG, GIF, WebP)</div>
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
                        <?= $categories && is_object($categories) ? $categories->num_rows : 0 ?> Categories
                    </span>
                </div>

                <?php if ($categories && is_object($categories) && $categories->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table class="table admin-table">
                            <thead>
                                <tr>
                                    <th>Image</th>
                                    <th>Category Name</th>
                                    <th>Type</th>
                                    <th>Description</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($category = $categories->fetch_assoc()): ?>
                                    <tr>
                                        <td data-label="Image">
                                            <?php if (!empty($category['image_url'])): ?>
                                                <img src="../../<?= htmlspecialchars($category['image_url']) ?>" 
                                                     alt="<?= htmlspecialchars($category['category_name']) ?>"
                                                     class="img-thumbnail" 
                                                     style="width: 60px; height: 60px; object-fit: cover; cursor: pointer;"
                                                     onclick="viewCategoryImage('../../<?= htmlspecialchars($category['image_url']) ?>', '<?= htmlspecialchars($category['category_name']) ?>')">
                                            <?php else: ?>
                                                <div class="bg-light d-flex align-items-center justify-content-center" 
                                                     style="width: 60px; height: 60px; border-radius: 4px;">
                                                    <i class="fas fa-image text-muted"></i>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td data-label="Category Name">
                                            <strong><?= htmlspecialchars($category['category_name']) ?></strong>
                                        </td>
                                        <td data-label="Type">
                                            <span class="badge bg-secondary"><?= htmlspecialchars($category['category_type']) ?></span>
                                        </td>
                                        <td data-label="Description">
                                            <?= htmlspecialchars(substr($category['description'] ?? '', 0, 50)) ?>
                                            <?= strlen($category['description'] ?? '') > 50 ? '...' : '' ?>
                                        </td>
                                        <td data-label="Status">
                                            <span class="badge bg-<?= $category['is_active'] ? 'success' : 'danger' ?>">
                                                <?= $category['is_active'] ? 'Active' : 'Inactive' ?>
                                            </span>
                                        </td>
                                        <td data-label="Actions">
                                            <div class="btn-group" role="group">
                                                <button type="button" 
                                                        class="btn btn-sm btn-primary" 
                                                        onclick="editCategory(<?= htmlspecialchars(json_encode($category), ENT_QUOTES) ?>)"
                                                        title="Edit Category">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button type="button" 
                                                        class="btn btn-sm btn-danger" 
                                                        onclick="deleteCategory(<?= $category['category_id'] ?>, '<?= htmlspecialchars($category['category_name'], ENT_QUOTES) ?>')"
                                                        title="Delete Category">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
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

<!-- Edit Category Modal -->
<div class="modal fade" id="editCategoryModal" tabindex="-1" aria-labelledby="editCategoryModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editCategoryModalLabel">
                    <i class="fas fa-edit me-2"></i>Edit Category
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" name="category_id" id="edit_category_id">
                    
                    <div class="mb-3">
                        <label class="form-label">Category Name</label>
                        <input type="text" name="category_name" id="edit_category_name" class="form-control" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Category Type</label>
                        <select name="category_type" id="edit_category_type" class="form-control" required>
                            <option value="">Select Category Type</option>
                            <option value="vegetables">Vegetables</option>
                            <option value="fruits">Fruits</option>
                            <option value="livestock">Livestock</option>
                            <option value="poultry">Poultry</option>
                            <option value="dairy">Dairy</option>
                            <option value="grains">Grains</option>
                            <option value="herbs">Herbs</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" id="edit_category_description" class="form-control" rows="3"></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Current Image</label>
                        <div id="edit_current_image" class="mb-2"></div>
                        <input type="hidden" name="delete_image" id="delete_image_flag" value="0">
                        <div id="image_actions" class="mb-2" style="display: none;">
                            <button type="button" class="btn btn-sm btn-danger" onclick="deleteCurrentImage()">
                                <i class="fas fa-trash me-1"></i>Delete Current Image
                            </button>
                        </div>
                        <label class="form-label">Upload New Image</label>
                        <input type="file" name="category_image" id="edit_category_image" class="form-control" accept="image/*" onchange="return enableImageUpload()">
                        <div class="form-text" id="upload_help_text">First delete the current image, then upload a new one</div>
                    </div>
                    
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="is_active" id="edit_is_active" value="1">
                            <label class="form-check-label" for="edit_is_active">
                                Active
                            </label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="edit_category" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Category Image View Modal -->
<div class="modal fade" id="categoryImageModal" tabindex="-1" aria-labelledby="categoryImageModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="categoryImageModalLabel">Category Image</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center">
                <img id="categoryModalImage" src="" alt="Category Image" class="img-fluid rounded">
            </div>
        </div>
    </div>
</div>

<script>
function editCategory(category) {
    document.getElementById('edit_category_id').value = category.category_id;
    document.getElementById('edit_category_name').value = category.category_name;
    document.getElementById('edit_category_type').value = category.category_type;
    document.getElementById('edit_category_description').value = category.description || '';
    document.getElementById('edit_is_active').checked = category.is_active == 1;
    document.getElementById('delete_image_flag').value = '0';
    document.getElementById('edit_category_image').value = '';
    
    // Show current image
    const currentImageDiv = document.getElementById('edit_current_image');
    const imageActionsDiv = document.getElementById('image_actions');
    if (category.image_url) {
        currentImageDiv.innerHTML = `
            <img src="../../${category.image_url}" 
                 alt="${category.category_name}" 
                 class="img-thumbnail" 
                 style="max-width: 200px; max-height: 200px; object-fit: cover;"
                 id="current_category_image">
        `;
        imageActionsDiv.style.display = 'block';
    } else {
        currentImageDiv.innerHTML = '<p class="text-muted">No image uploaded</p>';
        imageActionsDiv.style.display = 'none';
    }
    
    const modal = new bootstrap.Modal(document.getElementById('editCategoryModal'));
    modal.show();
}

function deleteCurrentImage() {
    if (confirm('Are you sure you want to delete the current image? You can then upload a new one.')) {
        document.getElementById('delete_image_flag').value = '1';
        document.getElementById('edit_current_image').innerHTML = '<p class="text-success"><i class="fas fa-check-circle me-1"></i>Image deleted. You can now upload a new image.</p>';
        document.getElementById('image_actions').style.display = 'none';
        document.getElementById('upload_help_text').innerHTML = '<span class="text-success">Image deleted. You can now upload a new image.</span>';
    }
}

function enableImageUpload() {
    // Check if there's a current image that hasn't been marked for deletion
    const currentImage = document.getElementById('current_category_image');
    const deleteFlag = document.getElementById('delete_image_flag');
    const fileInput = document.getElementById('edit_category_image');
    
    if (fileInput.files.length > 0) {
        if (currentImage && deleteFlag.value == '0') {
            alert('Please delete the current image first before uploading a new one.');
            fileInput.value = '';
            return false;
        }
    }
    return true;
}

function deleteCategory(categoryId, categoryName) {
    if (confirm(`⚠️ WARNING: Delete Category\n\nAre you sure you want to delete the category "${categoryName}"?\n\nThis will:\n- Delete the category permanently\n- Delete the category image\n- Cannot be undone\n\nNote: Categories with associated products cannot be deleted.`)) {
        window.location.href = 'index.php?tab=categories&delete_category=' + categoryId;
    }
}

function viewCategoryImage(imageUrl, categoryName) {
    document.getElementById('categoryModalImage').src = imageUrl;
    document.getElementById('categoryImageModalLabel').textContent = categoryName + ' - Image';
    const modal = new bootstrap.Modal(document.getElementById('categoryImageModal'));
    modal.show();
}
</script>
