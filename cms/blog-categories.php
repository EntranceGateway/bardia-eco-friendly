<?php
/**
 * CMS — Blog Categories Management
 */

$pageTitle = 'Blog Categories';
require_once __DIR__ . '/includes/header.php';

$success = '';
$error   = '';

// ---------- Handle POST ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);

        // Delete associated blog post images from disk
        $imgResult = $conn->prepare("SELECT featured_image FROM blog_posts WHERE category_id = ? AND featured_image IS NOT NULL AND featured_image != ''");
        $imgResult->bind_param('i', $id);
        $imgResult->execute();
        $imgRes = $imgResult->get_result();
        while ($row = $imgRes->fetch_assoc()) {
            $filename = basename($row['featured_image']);
            $path = __DIR__ . '/../storage/blog/' . $filename;
            if (file_exists($path)) @unlink($path);
        }
        $imgResult->close();

        $stmt = $conn->prepare("DELETE FROM blog_categories WHERE id = ?");
        $stmt->bind_param('i', $id);
        $success = $stmt->execute() ? 'Category and its posts deleted.' : '';
        if (!$success) $error = 'Delete failed: ' . $stmt->error;
        $stmt->close();

    } elseif (in_array($action, ['create', 'update'], true)) {
        $id           = (int)  ($_POST['id']            ?? 0);
        $name         = trim(  $_POST['name']           ?? '');
        $slug         = trim(  $_POST['slug']           ?? '');
        $description  = trim(  $_POST['description']    ?? '');
        $displayOrder = (int)  ($_POST['display_order'] ?? 0);
        $isActive     = isset($_POST['is_active']) ? 1 : 0;

        // Auto-generate slug from name if empty
        if (empty($slug)) {
            $slug = strtolower(trim($name));
            $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
            $slug = trim($slug, '-');
        }

        if (empty($name))     { $error = 'Category name is required.'; }
        elseif (empty($slug)) { $error = 'Slug is required.'; }
        else {
            if ($action === 'create') {
                // Check slug uniqueness
                $check = $conn->prepare("SELECT id FROM blog_categories WHERE slug = ?");
                $check->bind_param('s', $slug);
                $check->execute();
                $check->store_result();
                if ($check->num_rows > 0) {
                    $error = 'A category with this slug already exists.';
                }
                $check->close();

                if (!$error) {
                    $stmt = $conn->prepare("INSERT INTO blog_categories (name, slug, description, display_order, is_active) VALUES (?, ?, ?, ?, ?)");
                    $stmt->bind_param('sssii', $name, $slug, $description, $displayOrder, $isActive);
                    $success = $stmt->execute() ? 'Category created.' : '';
                    if (!$success) $error = 'Create failed: ' . $stmt->error;
                    $stmt->close();
                }
            } else {
                if ($id <= 0) { $error = 'Invalid ID.'; }
                else {
                    // Check slug uniqueness (exclude current)
                    $check = $conn->prepare("SELECT id FROM blog_categories WHERE slug = ? AND id != ?");
                    $check->bind_param('si', $slug, $id);
                    $check->execute();
                    $check->store_result();
                    if ($check->num_rows > 0) {
                        $error = 'Another category with this slug already exists.';
                    }
                    $check->close();

                    if (!$error) {
                        $stmt = $conn->prepare("UPDATE blog_categories SET name=?, slug=?, description=?, display_order=?, is_active=? WHERE id=?");
                        $stmt->bind_param('sssiii', $name, $slug, $description, $displayOrder, $isActive, $id);
                        $success = $stmt->execute() ? 'Category updated.' : '';
                        if (!$success) $error = 'Update failed: ' . $stmt->error;
                        $stmt->close();
                    }
                }
            }
        }
    }
}

// ---------- Fetch categories with post counts ----------
$categories = [];
try {
    $res = db_query($conn,
        "SELECT bc.id, bc.name, bc.slug, bc.description, bc.display_order, bc.is_active,
                bc.created_at, bc.updated_at,
                COUNT(bp.id) AS post_count
         FROM blog_categories bc
         LEFT JOIN blog_posts bp ON bc.id = bp.category_id
         GROUP BY bc.id
         ORDER BY bc.display_order ASC, bc.id ASC"
    );
    while ($r = $res->fetch_assoc()) {
        $r['id']            = (int)  $r['id'];
        $r['display_order'] = (int)  $r['display_order'];
        $r['is_active']     = (bool) $r['is_active'];
        $r['post_count']    = (int)  $r['post_count'];
        $categories[] = $r;
    }
} catch (RuntimeException $e) {
    error_log('[blog-categories] fetch: ' . $e->getMessage());
    $error = 'Could not load categories. Please try again.';
}
?>

<?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>
<?php if ($error):   ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

<div style="margin-bottom:16px;">
    <button class="btn btn-primary" onclick="openModal()">+ Add Category</button>
</div>

<div class="card">
    <div class="tbl-wrap">
        <table>
            <thead><tr><th>Name</th><th>Slug</th><th>Description</th><th>Posts</th><th>Order</th><th>Active</th><th>Actions</th></tr></thead>
            <tbody>
            <?php foreach ($categories as $c): ?>
                <tr>
                    <td><strong><?= htmlspecialchars($c['name']) ?></strong></td>
                    <td><code style="font-size:.8rem;background:#f3f4f6;padding:2px 6px;border-radius:4px;"><?= htmlspecialchars($c['slug']) ?></code></td>
                    <td><small style="color:#6b7280;"><?= htmlspecialchars(mb_strimwidth($c['description'] ?? '', 0, 60, '…')) ?></small></td>
                    <td><span class="badge badge-green"><?= $c['post_count'] ?></span></td>
                    <td><?= $c['display_order'] ?></td>
                    <td><?= $c['is_active'] ? '<span class="badge badge-green">Active</span>' : '<span class="badge badge-red">Off</span>' ?></td>
                    <td>
                        <button class="btn btn-secondary btn-sm" onclick='openModal(<?= htmlspecialchars(json_encode($c), ENT_QUOTES) ?>)'>Edit</button>
                        <form method="POST" style="display:inline;" onsubmit="return confirmDelete('Delete category &quot;<?= htmlspecialchars(addslashes($c['name'])) ?>&quot; and all its posts?')">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= $c['id'] ?>">
                            <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($categories)): ?>
                <tr><td colspan="7" style="color:#6b7280;text-align:center;">No blog categories yet.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Mobile cards -->
<div class="m-cards">
    <?php foreach ($categories as $c): ?>
    <div class="m-card">
        <div class="m-title"><?= htmlspecialchars($c['name']) ?></div>
        <div class="m-row"><span>Slug</span><span><?= htmlspecialchars($c['slug']) ?></span></div>
        <div class="m-row"><span>Posts</span><span><?= $c['post_count'] ?></span></div>
        <div class="m-row"><span>Order</span><span><?= $c['display_order'] ?></span></div>
        <div class="m-row"><span>Active</span><span><?= $c['is_active'] ? 'Yes' : 'No' ?></span></div>
        <div class="m-actions">
            <button class="btn btn-secondary btn-sm" onclick='openModal(<?= htmlspecialchars(json_encode($c), ENT_QUOTES) ?>)'>Edit</button>
            <form method="POST" onsubmit="return confirmDelete()">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?= $c['id'] ?>">
                <button type="submit" class="btn btn-danger btn-sm">Delete</button>
            </form>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Add/Edit Modal -->
<div class="modal-backdrop" id="catModal">
    <div class="modal">
        <h3 id="modalTitle">Add Category</h3>
        <form method="POST" id="catForm">
            <input type="hidden" name="action" id="fAction" value="create">
            <input type="hidden" name="id" id="fId" value="">
            <div class="form-group">
                <label for="fName">Name <span style="color:red">*</span></label>
                <input type="text" class="form-control" id="fName" name="name" required>
            </div>
            <div class="form-group">
                <label for="fSlug">Slug</label>
                <input type="text" class="form-control" id="fSlug" name="slug" placeholder="Auto-generated from name if empty">
                <small style="color:#6b7280;font-size:.78rem;">URL-friendly identifier. Leave blank to auto-generate.</small>
            </div>
            <div class="form-group">
                <label for="fDesc">Description</label>
                <textarea class="form-control" id="fDesc" name="description" rows="3" placeholder="Brief description of this category"></textarea>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label for="fOrder">Display Order</label>
                    <input type="number" class="form-control" id="fOrder" name="display_order" value="0" min="0">
                </div>
                <div class="form-group" style="display:flex;align-items:flex-end;">
                    <div class="check-group">
                        <input type="checkbox" id="fActive" name="is_active" value="1" checked>
                        <label for="fActive">Active</label>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                <button type="submit" class="btn btn-primary" id="submitBtn">Save Category</button>
            </div>
        </form>
    </div>
</div>

<script>
// Auto-generate slug from name
document.getElementById('fName').addEventListener('input', function() {
    const slugField = document.getElementById('fSlug');
    if (document.getElementById('fAction').value === 'create' && !slugField.dataset.manual) {
        slugField.value = this.value.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '');
    }
});
document.getElementById('fSlug').addEventListener('input', function() {
    this.dataset.manual = this.value ? 'true' : '';
});

function openModal(cat) {
    document.getElementById('catModal').classList.add('active');
    const slugField = document.getElementById('fSlug');
    slugField.dataset.manual = '';

    if (cat) {
        document.getElementById('modalTitle').textContent = 'Edit Category';
        document.getElementById('fAction').value  = 'update';
        document.getElementById('fId').value      = cat.id;
        document.getElementById('fName').value    = cat.name || '';
        slugField.value = cat.slug || '';
        slugField.dataset.manual = 'true';
        document.getElementById('fDesc').value    = cat.description || '';
        document.getElementById('fOrder').value   = cat.display_order || 0;
        document.getElementById('fActive').checked = !!cat.is_active;
        document.getElementById('submitBtn').textContent = 'Update Category';
    } else {
        document.getElementById('modalTitle').textContent = 'Add Category';
        document.getElementById('fAction').value = 'create';
        document.getElementById('catForm').reset();
        document.getElementById('fActive').checked = true;
        document.getElementById('submitBtn').textContent = 'Save Category';
    }
}

function closeModal() {
    document.getElementById('catModal').classList.remove('active');
}

document.getElementById('catModal').addEventListener('click', function(e) {
    if (e.target === this) closeModal();
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
