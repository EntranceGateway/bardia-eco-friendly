<?php
/**
 * CMS — Activities & Wildlife Experiences Management
 * Manages all 12 Wildlife Experiences, details, icons, display order, and popup details modal.
 */

$pageTitle = 'Activities & Wildlife Experiences';
require_once __DIR__ . '/includes/header.php';

$success = '';
$error   = '';

// ---------- Handle POST ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);
        $stmt = $conn->prepare("DELETE FROM activities WHERE id = ?");
        $stmt->bind_param('i', $id);
        $success = $stmt->execute() ? 'Activity deleted.' : '';
        if (!$success) $error = 'Delete failed: ' . $stmt->error;
        $stmt->close();

    } elseif (in_array($action, ['create', 'update'], true)) {
        $id               = (int)   ($_POST['id']               ?? 0);
        $title            = trim($_POST['title']             ?? '');
        $slug             = strtolower(trim($_POST['slug']   ?? ''));
        $icon             = trim($_POST['icon']              ?? '');
        $shortDescription = trim($_POST['short_description'] ?? '');
        $description      = trim($_POST['description']       ?? '');
        $metaTitle        = trim($_POST['meta_title']        ?? '');
        $metaDesc         = trim($_POST['meta_description']  ?? '');
        $metaKeywords     = trim($_POST['meta_keywords']     ?? '');
        $metaImage        = trim($_POST['meta_image']        ?? '');
        $canonicalUrl     = trim($_POST['canonical_url']     ?? '');
        $robots           = trim($_POST['robots']            ?? 'index,follow');
        $displayOrder     = (int)   ($_POST['display_order']   ?? 0);
        $isActive         = isset($_POST['is_active']) ? 1 : 0;

        if (empty($title)) {
            $error = 'Activity title is required.';
        } else {
            if (empty($slug)) {
                $slug = strtolower(preg_replace('/[^a-z0-9-]/', '-', $title));
            }
            $slug = preg_replace('/-+/', '-', trim($slug, '-'));

            if ($action === 'create') {
                $stmt = $conn->prepare(
                    "INSERT INTO activities (title, slug, icon, short_description, description, meta_title, meta_description, meta_keywords, meta_image, canonical_url, robots, display_order, is_active)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
                );
                $stmt->bind_param('sssssssssssii', $title, $slug, $icon, $shortDescription, $description, $metaTitle, $metaDesc, $metaKeywords, $metaImage, $canonicalUrl, $robots, $displayOrder, $isActive);
                $success = $stmt->execute() ? 'Activity created successfully.' : '';
                if (!$success) $error = 'Create failed: ' . $stmt->error;
                $stmt->close();
            } else {
                $stmt = $conn->prepare(
                    "UPDATE activities SET title=?, slug=?, icon=?, short_description=?, description=?, meta_title=?, meta_description=?, meta_keywords=?, meta_image=?, canonical_url=?, robots=?, display_order=?, is_active=?
                     WHERE id=?"
                );
                $stmt->bind_param('sssssssssssiii', $title, $slug, $icon, $shortDescription, $description, $metaTitle, $metaDesc, $metaKeywords, $metaImage, $canonicalUrl, $robots, $displayOrder, $isActive, $id);
                $success = $stmt->execute() ? 'Activity updated successfully.' : '';
                if (!$success) $error = 'Update failed: ' . $stmt->error;
                $stmt->close();
            }
        }
    }
}

// ---------- Fetch all activities ----------
$activities = [];
$res = $conn->query("SELECT id, title, slug, icon, short_description, description, meta_title, meta_description, meta_keywords, meta_image, canonical_url, robots, display_order, is_active, updated_at FROM activities ORDER BY display_order ASC, id ASC");
if ($res) {
    while ($r = $res->fetch_assoc()) {
        $r['id']            = (int) $r['id'];
        $r['display_order'] = (int) $r['display_order'];
        $r['is_active']     = (bool) $r['is_active'];
        $activities[]       = $r;
    }
}
?>

<?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>
<?php if ($error):   ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

<div style="margin-bottom:16px; display:flex; justify-content:space-between; align-items:center;">
    <button class="btn btn-primary" onclick="openActivityModal()">+ Add Wildlife Experience</button>
    <span style="color:var(--muted); font-size:0.9rem;">Total Activities: <strong><?= count($activities) ?></strong></span>
</div>

<!-- Table View -->
<div class="card">
    <div class="tbl-wrap">
        <table>
            <thead>
                <tr>
                    <th style="width:50px;">Icon</th>
                    <th>Activity Title</th>
                    <th>Slug</th>
                    <th>Short Summary</th>
                    <th>Order</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($activities as $act): ?>
                    <tr>
                        <td style="font-size:1.5rem; text-align:center;"><?= htmlspecialchars($act['icon'] ?: '🌿') ?></td>
                        <td><strong><?= htmlspecialchars($act['title']) ?></strong></td>
                        <td><code style="background:#f0f0f0;padding:2px 6px;border-radius:4px;"><?= htmlspecialchars($act['slug']) ?></code></td>
                        <td style="max-width:300px; color:#4b5563; font-size:0.85rem; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">
                            <?= htmlspecialchars($act['short_description'] ?: substr($act['description'], 0, 80) . '...') ?>
                        </td>
                        <td><?= $act['display_order'] ?></td>
                        <td>
                            <?php if ($act['is_active']): ?>
                                <span class="badge badge-green">Active</span>
                            <?php else: ?>
                                <span class="badge badge-red">Inactive</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <button class="btn btn-secondary btn-sm" onclick='showDetailsModal(<?= htmlspecialchars(json_encode($act), ENT_QUOTES) ?>)'>👁 View Details</button>
                            <button class="btn btn-secondary btn-sm" onclick='openActivityModal(<?= htmlspecialchars(json_encode($act), ENT_QUOTES) ?>)'>✏️ Edit</button>
                            <form method="POST" style="display:inline;" onsubmit="return confirm('Delete activity &quot;<?= htmlspecialchars(addslashes($act['title'])) ?>&quot;?')">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= $act['id'] ?>">
                                <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($activities)): ?>
                    <tr><td colspan="7" style="text-align:center; color:var(--muted);">No activities found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Mobile Cards View -->
<div class="m-cards">
    <?php foreach ($activities as $act): ?>
        <div class="m-card">
            <div class="m-title"><?= htmlspecialchars($act['icon']) ?> <?= htmlspecialchars($act['title']) ?></div>
            <div class="m-row"><span>Slug</span><span><?= htmlspecialchars($act['slug']) ?></span></div>
            <div class="m-row"><span>Status</span><span><?= $act['is_active'] ? 'Active' : 'Inactive' ?></span></div>
            <div class="m-actions">
                <button class="btn btn-secondary btn-sm" onclick='showDetailsModal(<?= htmlspecialchars(json_encode($act), ENT_QUOTES) ?>)'>👁 View Details</button>
                <button class="btn btn-secondary btn-sm" onclick='openActivityModal(<?= htmlspecialchars(json_encode($act), ENT_QUOTES) ?>)'>✏️ Edit</button>
                <form method="POST" style="display:inline;" onsubmit="return confirm('Delete activity?')">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="<?= $act['id'] ?>">
                    <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                </form>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<!-- Edit / Create Modal -->
<div class="modal-backdrop" id="activityModalBackdrop">
    <div class="modal" style="width: min(680px, 96vw);">
        <h3 id="modalHeading">Add Wildlife Experience</h3>
        <form method="POST" id="activityForm">
            <input type="hidden" name="action" id="formAction" value="create">
            <input type="hidden" name="id" id="activityId" value="0">

            <div class="form-row">
                <div class="form-group">
                    <label for="actTitle">Title *</label>
                    <input id="actTitle" class="form-control" name="title" required placeholder="e.g. Jeep Safari">
                </div>
                <div class="form-group" style="max-width:120px;">
                    <label for="actIcon">Icon (Emoji)</label>
                    <input id="actIcon" class="form-control" name="icon" placeholder="🚙">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="actSlug">Slug</label>
                    <input id="actSlug" class="form-control" name="slug" placeholder="jeep-safari">
                </div>
                <div class="form-group" style="max-width:140px;">
                    <label for="actOrder">Display Order</label>
                    <input type="number" id="actOrder" class="form-control" name="display_order" value="1">
                </div>
            </div>

            <div class="form-group">
                <label for="actShortDesc">Short Summary (Preview)</label>
                <input id="actShortDesc" class="form-control" name="short_description" placeholder="Brief 1-sentence summary for card grid">
            </div>

            <div class="form-group">
                <label for="actDesc">Full Description (Popup Modal Text) *</label>
                <textarea id="actDesc" class="form-control" name="description" rows="5" required placeholder="Detailed activity description..."></textarea>
            </div>

            <h4 style="margin: 16px 0 10px; font-size:0.95rem; color:var(--brand-dk);">SEO Options (Optional)</h4>
            
            <div class="form-group">
                <label for="actMetaTitle">SEO Meta Title</label>
                <input id="actMetaTitle" class="form-control" name="meta_title" placeholder="Jeep Safari | Bardia Eco-Friendly Homestay">
            </div>

            <div class="form-group">
                <label for="actMetaDesc">SEO Meta Description</label>
                <textarea id="actMetaDesc" class="form-control" name="meta_description" rows="2" placeholder="Search engine snippet..."></textarea>
            </div>

            <div class="check-group" style="margin-bottom:16px;">
                <input type="checkbox" id="actActive" name="is_active" value="1" checked>
                <label for="actActive">Active (Visible on public website & API)</label>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeActivityModal()">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Activity</button>
            </div>
        </form>
    </div>
</div>

<!-- Details Popup Screen / Modal -->
<div class="modal-backdrop" id="detailsModalBackdrop">
    <div class="modal" style="width: min(640px, 94vw);">
        <div style="display:flex; justify-content:space-between; align-items:flex-start; border-bottom: 1px solid var(--border); padding-bottom: 12px; margin-bottom: 16px;">
            <div style="display:flex; align-items:center; gap:10px;">
                <span id="detailIcon" style="font-size:2rem;">🚙</span>
                <div>
                    <h3 id="detailTitle" style="margin:0;">Jeep Safari</h3>
                    <code id="detailSlug" style="font-size:0.8rem; background:#f0f0f0; padding:1px 6px; border-radius:4px; color:#4b5563;">jeep-safari</code>
                </div>
            </div>
            <button class="btn btn-secondary btn-sm" onclick="closeDetailsModal()" style="font-size:1.1rem; padding:2px 8px;">✕</button>
        </div>

        <div style="background:#f9fafb; padding:14px; border-radius:8px; border:1px solid #e5e7eb; margin-bottom:16px;">
            <strong style="font-size:0.85rem; color:var(--brand-dk); text-transform:uppercase; letter-spacing:0.05em; display:block; margin-bottom:4px;">Short Summary</strong>
            <p id="detailShortDesc" style="margin:0; font-size:0.92rem; color:#374151;"></p>
        </div>

        <div style="margin-bottom:20px;">
            <strong style="font-size:0.85rem; color:var(--brand-dk); text-transform:uppercase; letter-spacing:0.05em; display:block; margin-bottom:8px;">Full Details & Experience Overview</strong>
            <div id="detailFullDesc" style="line-height:1.6; font-size:0.95rem; color:#1f2937; white-space:pre-line;"></div>
        </div>

        <div style="border-top:1px solid var(--border); padding-top:12px; font-size:0.8rem; color:var(--muted); display:flex; justify-content:space-between;">
            <span>Display Order: <strong id="detailOrder">1</strong></span>
            <span>Status: <strong id="detailStatus">Active</strong></span>
        </div>

        <div class="modal-footer" style="margin-top:16px;">
            <button type="button" class="btn btn-primary" onclick="closeDetailsModal()">Close</button>
        </div>
    </div>
</div>

<script>
function openActivityModal(data = null) {
    const modal = document.getElementById('activityModalBackdrop');
    const heading = document.getElementById('modalHeading');
    const action = document.getElementById('formAction');
    
    if (data) {
        heading.innerText = 'Edit Wildlife Experience';
        action.value = 'update';
        document.getElementById('activityId').value = data.id || 0;
        document.getElementById('actTitle').value = data.title || '';
        document.getElementById('actSlug').value = data.slug || '';
        document.getElementById('actIcon').value = data.icon || '';
        document.getElementById('actShortDesc').value = data.short_description || '';
        document.getElementById('actDesc').value = data.description || '';
        document.getElementById('actMetaTitle').value = data.meta_title || '';
        document.getElementById('actMetaDesc').value = data.meta_description || '';
        document.getElementById('actOrder').value = data.display_order || 1;
        document.getElementById('actActive').checked = !!data.is_active;
    } else {
        heading.innerText = 'Add Wildlife Experience';
        action.value = 'create';
        document.getElementById('activityForm').reset();
        document.getElementById('activityId').value = 0;
        document.getElementById('actActive').checked = true;
    }
    
    modal.classList.add('active');
}

function closeActivityModal() {
    document.getElementById('activityModalBackdrop').classList.remove('active');
}

function showDetailsModal(data) {
    document.getElementById('detailIcon').innerText = data.icon || '🌿';
    document.getElementById('detailTitle').innerText = data.title || '';
    document.getElementById('detailSlug').innerText = data.slug || '';
    document.getElementById('detailShortDesc').innerText = data.short_description || '(No short summary)';
    document.getElementById('detailFullDesc').innerText = data.description || '';
    document.getElementById('detailOrder').innerText = data.display_order || 1;
    document.getElementById('detailStatus').innerText = data.is_active ? 'Active' : 'Inactive';

    document.getElementById('detailsModalBackdrop').classList.add('active');
}

function closeDetailsModal() {
    document.getElementById('detailsModalBackdrop').classList.remove('active');
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
