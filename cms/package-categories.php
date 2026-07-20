<?php
/**
 * CMS — Package Categories Management
 */

$pageTitle = 'Package Categories';
require_once __DIR__ . '/includes/header.php';

$success = '';
$error   = '';

// ---------- Handle POST ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'delete') {
        $id   = (int) ($_POST['id'] ?? 0);
        $stmt = $conn->prepare("DELETE FROM package_categories WHERE id = ?");
        $stmt->bind_param('i', $id);
        $success = $stmt->execute() ? 'Category deleted (all packages in it were also removed).' : '';
        if (!$success) $error = 'Delete failed: ' . $stmt->error;
        $stmt->close();

    } elseif (in_array($action, ['create', 'update'], true)) {
        $id           = (int)   ($_POST['id']            ?? 0);
        $name         = trim($_POST['name']          ?? '');
        $slug         = strtolower(trim($_POST['slug'] ?? ''));
        $displayOrder = (int)   ($_POST['display_order'] ?? 0);
        $isActive     = isset($_POST['is_active']) ? 1 : 0;
        
        $metaTitle    = trim($_POST['meta_title']    ?? '');
        $metaDesc     = trim($_POST['meta_description'] ?? '');
        $metaKeywords = trim($_POST['meta_keywords'] ?? '');
        $metaImage    = trim($_POST['meta_image']    ?? '');
        $canonicalUrl = trim($_POST['canonical_url'] ?? '');
        $robots       = trim($_POST['robots']        ?? 'index,follow');

        $slug = preg_replace('/[^a-z0-9-]/', '-', $slug);
        $slug = preg_replace('/-+/', '-', trim($slug, '-'));

        if (empty($name)) { $error = 'Name is required.'; }
        elseif (empty($slug)) { $error = 'Slug is required.'; }
        else {
            // Check slug uniqueness (exclude self for update)
            $dup = $conn->prepare("SELECT id FROM package_categories WHERE slug = ? AND id != ?");
            $dup->bind_param('si', $slug, $id);
            $dup->execute(); $dup->store_result();
            if ($dup->num_rows > 0) { $error = 'A category with this slug already exists.'; }
            $dup->close();

            if (!$error) {
                if ($action === 'create') {
                    $stmt = $conn->prepare("INSERT INTO package_categories (name, slug, display_order, is_active, meta_title, meta_description, meta_keywords, meta_image, canonical_url, robots) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->bind_param('ssiissssss', $name, $slug, $displayOrder, $isActive, $metaTitle, $metaDesc, $metaKeywords, $metaImage, $canonicalUrl, $robots);
                    $success = $stmt->execute() ? 'Category created.' : '';
                    if (!$success) $error = 'Create failed: ' . $stmt->error;
                    $stmt->close();
                } else {
                    $stmt = $conn->prepare("UPDATE package_categories SET name=?, slug=?, display_order=?, is_active=?, meta_title=?, meta_description=?, meta_keywords=?, meta_image=?, canonical_url=?, robots=? WHERE id=?");
                    $stmt->bind_param('ssiissssssi', $name, $slug, $displayOrder, $isActive, $metaTitle, $metaDesc, $metaKeywords, $metaImage, $canonicalUrl, $robots, $id);
                    $success = $stmt->execute() ? 'Category updated.' : '';
                    if (!$success) $error = 'Update failed: ' . $stmt->error;
                    $stmt->close();
                }
            }
        }
    }
}

// ---------- Fetch categories ----------
$categories = [];
try {
    $res = db_query($conn, "
        SELECT pc.id, pc.name, pc.slug, pc.display_order, pc.is_active, pc.meta_title, pc.meta_description, pc.meta_keywords, pc.meta_image, pc.canonical_url, pc.robots, COUNT(p.id) AS pkg_count
        FROM package_categories pc
        LEFT JOIN packages p ON pc.id = p.category_id
        GROUP BY pc.id
        ORDER BY pc.display_order ASC, pc.id ASC
    ");
    while ($r = $res->fetch_assoc()) {
        $r['id']            = (int)   $r['id'];
        $r['display_order'] = (int)   $r['display_order'];
        $r['is_active']     = (bool)  $r['is_active'];
        $r['pkg_count']     = (int)   $r['pkg_count'];
        $categories[] = $r;
    }
} catch (RuntimeException $e) {
    error_log('[package-categories] fetch: ' . $e->getMessage());
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
            <thead><tr><th>Name</th><th>Slug</th><th>Packages</th><th>Order</th><th>Active</th><th>Actions</th></tr></thead>
            <tbody>
            <?php foreach ($categories as $c): ?>
                <tr>
                    <td><strong><?= htmlspecialchars($c['name']) ?></strong></td>
                    <td><code style="background:#f0f0f0;padding:1px 6px;border-radius:4px;"><?= htmlspecialchars($c['slug']) ?></code></td>
                    <td><?= $c['pkg_count'] ?> package<?= $c['pkg_count'] !== 1 ? 's' : '' ?></td>
                    <td><?= $c['display_order'] ?></td>
                    <td><?= $c['is_active'] ? '<span class="badge badge-green">Active</span>' : '<span class="badge badge-red">Inactive</span>' ?></td>
                    <td>
                        <button class="btn btn-secondary btn-sm" onclick='openModal(<?= htmlspecialchars(json_encode($c), ENT_QUOTES) ?>)'>Edit</button>
                        <form method="POST" style="display:inline;" onsubmit="return confirmDelete('Delete &quot;<?= htmlspecialchars(addslashes($c['name'])) ?>&quot; and all its packages?')">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= $c['id'] ?>">
                            <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($categories)): ?>
                <tr><td colspan="6" style="color:#6b7280;text-align:center;">No categories yet.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="m-cards">
    <?php foreach ($categories as $c): ?>
    <div class="m-card">
        <div class="m-title"><?= htmlspecialchars($c['name']) ?></div>
        <div class="m-row"><span>Slug</span><span><?= htmlspecialchars($c['slug']) ?></span></div>
        <div class="m-row"><span>Packages</span><span><?= $c['pkg_count'] ?></span></div>
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

<div class="modal-backdrop" id="catModal">
    <div class="modal">
        <h3 id="modalTitle">Add Category</h3>
        <form method="POST" id="catForm">
            <input type="hidden" name="action" id="fAction" value="create">
            <input type="hidden" name="id"     id="fId"     value="">
            <div class="form-group">
                <label for="fName">Name <span style="color:red">*</span></label>
                <input type="text" class="form-control" id="fName" name="name" required oninput="autoSlug()">
            </div>
            <div class="form-group">
                <label for="fSlug">Slug <span style="color:red">*</span></label>
                <input type="text" class="form-control" id="fSlug" name="slug" required pattern="[a-z0-9-]+">
                <small style="color:#6b7280;font-size:.78rem;">e.g. homestay, safari</small>
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
            
            <hr style="margin:20px 0; border:0; border-top:1px solid var(--border);">
            <h4 style="margin-bottom:12px; font-size:0.95rem; color:var(--brand-dk);">SEO Settings</h4>
            <div class="form-group">
                <label for="fMetaTitle">Meta Title</label>
                <input type="text" class="form-control" id="fMetaTitle" name="meta_title">
            </div>
            <div class="form-group">
                <label for="fMetaDesc">Meta Description</label>
                <textarea class="form-control" id="fMetaDesc" name="meta_description" rows="2"></textarea>
            </div>
            <div class="form-group">
                <label for="fMetaKeywords">Meta Keywords</label>
                <input type="text" class="form-control" id="fMetaKeywords" name="meta_keywords" placeholder="safari, wildlife, homestay">
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label for="fMetaImage">Meta Image URL</label>
                    <input type="text" class="form-control" id="fMetaImage" name="meta_image">
                </div>
                <div class="form-group">
                    <label for="fCanonical">Canonical URL</label>
                    <input type="url" class="form-control" id="fCanonical" name="canonical_url">
                </div>
            </div>
            <div class="form-group">
                <label for="fRobots">Robots</label>
                <input type="text" class="form-control" id="fRobots" name="robots" value="index,follow">
            </div>
            <hr style="margin:20px 0; border:0; border-top:1px solid var(--border);">
            
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                <button type="submit" class="btn btn-primary" id="submitBtn">Save</button>
            </div>
        </form>
    </div>
</div>

<script>
function autoSlug() {
    if (document.getElementById('fAction').value !== 'create') return;
    document.getElementById('fSlug').value = document.getElementById('fName').value
        .toLowerCase().trim().replace(/[^a-z0-9\s-]/g, '').replace(/\s+/g,'-').replace(/-+/g,'-');
}
function openModal(cat) {
    document.getElementById('catModal').classList.add('active');
    if (cat) {
        document.getElementById('modalTitle').textContent = 'Edit Category';
        document.getElementById('fAction').value = 'update';
        document.getElementById('fId').value     = cat.id;
        document.getElementById('fName').value   = cat.name || '';
        document.getElementById('fSlug').value   = cat.slug || '';
        document.getElementById('fOrder').value  = cat.display_order || 0;
        document.getElementById('fMetaTitle').value  = cat.meta_title || '';
        document.getElementById('fMetaDesc').value   = cat.meta_description || '';
        document.getElementById('fMetaKeywords').value= cat.meta_keywords || '';
        document.getElementById('fMetaImage').value  = cat.meta_image || '';
        document.getElementById('fCanonical').value  = cat.canonical_url || '';
        document.getElementById('fRobots').value     = cat.robots || 'index,follow';
        document.getElementById('fActive').checked = !!cat.is_active;
        document.getElementById('submitBtn').textContent = 'Update';
    } else {
        document.getElementById('modalTitle').textContent = 'Add Category';
        document.getElementById('fAction').value = 'create';
        document.getElementById('catForm').reset();
        document.getElementById('fActive').checked = true;
        document.getElementById('submitBtn').textContent = 'Save';
    }
}
function closeModal() { document.getElementById('catModal').classList.remove('active'); }
document.getElementById('catModal').addEventListener('click', e => { if (e.target === this) closeModal(); });
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
