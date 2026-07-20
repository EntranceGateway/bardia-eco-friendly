<?php
/**
 * CMS — Pages Management
 */

$pageTitle = 'Pages';
require_once __DIR__ . '/includes/header.php';

$success = '';
$error   = '';

// ---------- Handle POST ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'delete') {
        $id   = (int) ($_POST['id'] ?? 0);
        $stmt = $conn->prepare("DELETE FROM pages WHERE id = ?");
        $stmt->bind_param('i', $id);
        $success = $stmt->execute() ? 'Page deleted.' : '';
        if (!$success) $error = 'Delete failed: ' . $stmt->error;
        $stmt->close();

    } elseif (in_array($action, ['create', 'update'], true)) {
        $id           = (int)   ($_POST['id']            ?? 0);
        $title        = trim($_POST['title']         ?? '');
        $slug         = strtolower(trim($_POST['slug'] ?? ''));
        $content      = trim($_POST['content']       ?? '');
        $status       = trim($_POST['status']        ?? 'draft');
        
        $metaTitle    = trim($_POST['meta_title']    ?? '');
        $metaDesc     = trim($_POST['meta_description'] ?? '');
        $metaKeywords = trim($_POST['meta_keywords'] ?? '');
        $metaImage    = trim($_POST['meta_image']    ?? '');
        $canonicalUrl = trim($_POST['canonical_url'] ?? '');
        $robots       = trim($_POST['robots']        ?? 'index,follow');

        if (empty($slug)) {
            $slug = strtolower(preg_replace('/[^a-z0-9-]/', '-', $title));
        }
        $slug = preg_replace('/-+/', '-', trim($slug, '-'));

        if (empty($title)) { $error = 'Title is required.'; }
        elseif (empty($slug)) { $error = 'Slug is required.'; }
        else {
            // Check slug uniqueness
            $dup = $conn->prepare("SELECT id FROM pages WHERE slug = ? AND id != ?");
            $dup->bind_param('si', $slug, $id);
            $dup->execute(); $dup->store_result();
            if ($dup->num_rows > 0) { $error = 'A page with this slug already exists.'; }
            $dup->close();

            if (!$error) {
                if ($action === 'create') {
                    $stmt = $conn->prepare("INSERT INTO pages (title, slug, content, status, meta_title, meta_description, meta_keywords, meta_image, canonical_url, robots) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->bind_param('ssssssssss', $title, $slug, $content, $status, $metaTitle, $metaDesc, $metaKeywords, $metaImage, $canonicalUrl, $robots);
                    $success = $stmt->execute() ? 'Page created.' : '';
                    if (!$success) $error = 'Create failed: ' . $stmt->error;
                    $stmt->close();
                } else {
                    $stmt = $conn->prepare("UPDATE pages SET title=?, slug=?, content=?, status=?, meta_title=?, meta_description=?, meta_keywords=?, meta_image=?, canonical_url=?, robots=? WHERE id=?");
                    $stmt->bind_param('ssssssssssi', $title, $slug, $content, $status, $metaTitle, $metaDesc, $metaKeywords, $metaImage, $canonicalUrl, $robots, $id);
                    $success = $stmt->execute() ? 'Page updated.' : '';
                    if (!$success) $error = 'Update failed: ' . $stmt->error;
                    $stmt->close();
                }
            }
        }
    }
}

// ---------- Fetch pages ----------
$pages = [];
try {
    $res = db_query($conn, "
        SELECT id, title, slug, content, status, meta_title, meta_description, meta_keywords, meta_image, canonical_url, robots, updated_at
        FROM pages
        ORDER BY title ASC
    ");
    while ($r = $res->fetch_assoc()) {
        $r['id'] = (int) $r['id'];
        $pages[] = $r;
    }
} catch (RuntimeException $e) {
    error_log('[pages] fetch: ' . $e->getMessage());
    $error = 'Could not load pages. Please try again.';
}
?>

<?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>
<?php if ($error):   ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

<div style="margin-bottom:16px;">
    <button class="btn btn-primary" onclick="openModal()">+ Add Page</button>
</div>

<div class="card">
    <div class="tbl-wrap">
        <table>
            <thead><tr><th>Title</th><th>Slug</th><th>Status</th><th>Last Updated</th><th>Actions</th></tr></thead>
            <tbody>
            <?php foreach ($pages as $p): ?>
                <tr>
                    <td><strong><?= htmlspecialchars($p['title']) ?></strong></td>
                    <td><code style="background:#f0f0f0;padding:1px 6px;border-radius:4px;"><?= htmlspecialchars($p['slug']) ?></code></td>
                    <td>
                        <?php if ($p['status'] === 'published'): ?>
                            <span class="badge badge-green">Published</span>
                        <?php elseif ($p['status'] === 'archived'): ?>
                            <span class="badge badge-red">Archived</span>
                        <?php else: ?>
                            <span class="badge badge-gold">Draft</span>
                        <?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars($p['updated_at']) ?></td>
                    <td>
                        <button class="btn btn-secondary btn-sm" onclick='openModal(<?= htmlspecialchars(json_encode($p), ENT_QUOTES) ?>)'>Edit</button>
                        <form method="POST" style="display:inline;" onsubmit="return confirmDelete('Delete &quot;<?= htmlspecialchars(addslashes($p['title'])) ?>&quot;?')">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= $p['id'] ?>">
                            <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($pages)): ?>
                <tr><td colspan="5" style="color:#6b7280;text-align:center;">No pages yet.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="m-cards">
    <?php foreach ($pages as $p): ?>
    <div class="m-card">
        <div class="m-title"><?= htmlspecialchars($p['title']) ?></div>
        <div class="m-row"><span>Slug</span><span><?= htmlspecialchars($p['slug']) ?></span></div>
        <div class="m-row"><span>Status</span><span><?= htmlspecialchars($p['status']) ?></span></div>
        <div class="m-actions">
            <button class="btn btn-secondary btn-sm" onclick='openModal(<?= htmlspecialchars(json_encode($p), ENT_QUOTES) ?>)'>Edit</button>
            <form method="POST" onsubmit="return confirmDelete()">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?= $p['id'] ?>">
                <button type="submit" class="btn btn-danger btn-sm">Delete</button>
            </form>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<div class="modal-backdrop" id="pageModal">
    <div class="modal" style="width: min(800px, 96vw);">
        <h3 id="modalTitle">Add Page</h3>
        <form method="POST" id="pageForm">
            <input type="hidden" name="action" id="fAction" value="create">
            <input type="hidden" name="id"     id="fId"     value="">
            <div class="form-row">
                <div class="form-group">
                    <label for="fTitle">Title <span style="color:red">*</span></label>
                    <input type="text" class="form-control" id="fTitle" name="title" required>
                </div>
                <div class="form-group">
                    <label for="fStatus">Status</label>
                    <select class="form-control" id="fStatus" name="status">
                        <option value="draft">Draft</option>
                        <option value="published" selected>Published</option>
                        <option value="archived">Archived</option>
                    </select>
                </div>
            </div>
            
            <div class="form-group">
                <label for="fContent">Content (HTML)</label>
                <textarea class="form-control" id="fContent" name="content" rows="6"></textarea>
            </div>
            
            <hr style="margin:20px 0; border:0; border-top:1px solid var(--border);">
            <h4 style="margin-bottom:12px; font-size:0.95rem; color:var(--brand-dk);">SEO Settings</h4>
            <div class="form-row">
                <div class="form-group">
                    <label for="fSlug">URL Slug (Auto-generated if empty)</label>
                    <input type="text" class="form-control" id="fSlug" name="slug" pattern="[a-z0-9\-]+">
                    <small style="color:#6b7280;font-size:.78rem;">e.g. about-us, contact</small>
                </div>
                <div class="form-group">
                    <label for="fMetaTitle">Meta Title</label>
                    <input type="text" class="form-control" id="fMetaTitle" name="meta_title">
                </div>
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
            
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                <button type="submit" class="btn btn-primary" id="submitBtn">Save</button>
            </div>
        </form>
    </div>
</div>

<script>
function openModal(page) {
    document.getElementById('pageModal').classList.add('active');
    if (page) {
        document.getElementById('modalTitle').textContent = 'Edit Page';
        document.getElementById('fAction').value = 'update';
        document.getElementById('fId').value     = page.id;
        document.getElementById('fTitle').value   = page.title || '';
        document.getElementById('fSlug').value   = page.slug || '';
        document.getElementById('fContent').value   = page.content || '';
        document.getElementById('fStatus').value   = page.status || 'draft';
        
        document.getElementById('fMetaTitle').value  = page.meta_title || '';
        document.getElementById('fMetaDesc').value   = page.meta_description || '';
        document.getElementById('fMetaKeywords').value= page.meta_keywords || '';
        document.getElementById('fMetaImage').value  = page.meta_image || '';
        document.getElementById('fCanonical').value  = page.canonical_url || '';
        document.getElementById('fRobots').value     = page.robots || 'index,follow';
        
        document.getElementById('submitBtn').textContent = 'Update';
    } else {
        document.getElementById('modalTitle').textContent = 'Add Page';
        document.getElementById('fAction').value = 'create';
        document.getElementById('pageForm').reset();
        document.getElementById('fStatus').value = 'published';
        document.getElementById('submitBtn').textContent = 'Save';
    }
}
function closeModal() { document.getElementById('pageModal').classList.remove('active'); }
document.getElementById('pageModal').addEventListener('click', e => { if (e.target === this) closeModal(); });
function confirmDelete(msg = 'Are you sure?') { return confirm(msg); }
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
