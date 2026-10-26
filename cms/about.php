<?php
/**
 * CMS — About Us Page
 * Edits every block of the public /about page: hero, story, values and team members.
 */

$pageTitle = 'About Us Page';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/../includes/functions.php'; // asset_url(), frontend_url()

$success = '';
$error   = '';

$sectionTypes = [
    'hero'  => ['label' => 'Hero',          'help' => 'Top of the page. Only the first active row is shown. Subtitle = intro sentence.'],
    'story' => ['label' => 'Our Story',     'help' => 'Only the first active row is shown. Body = story paragraphs (blank line between paragraphs). Subtitle = pull quote.'],
    'value' => ['label' => 'Values',        'help' => 'Each row is one value. Title + short body. Icon is optional (emoji, or: compass, hiking, binoculars, globe).'],
    'team'  => ['label' => 'Hosts & Team',  'help' => 'Each row is one person or team. Title = name, Subtitle = role, Body = short bio, Image = photo.'],
];

$uploadDir = __DIR__ . '/../storage/about/';

/** Validates and stores an uploaded image; returns its /storage path, '' if none sent, or null on error. */
function about_store_upload(array $file, string $uploadDir, string &$error): ?string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return '';
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $error = 'Image upload failed (code ' . (int) $file['error'] . '). Try a smaller file.';
        return null;
    }
    if ($file['size'] > 5 * 1024 * 1024) {
        $error = 'Image is too large (max 5MB).';
        return null;
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime  = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    $extensions = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];

    if (!isset($extensions[$mime]) || getimagesize($file['tmp_name']) === false) {
        $error = 'Invalid image. Use JPG, PNG or WebP.';
        return null;
    }

    if (!is_dir($uploadDir)) @mkdir($uploadDir, 0775, true);
    $filename = time() . '_' . bin2hex(random_bytes(4)) . '.' . $extensions[$mime];

    if (!move_uploaded_file($file['tmp_name'], $uploadDir . $filename)) {
        $error = 'Could not save the uploaded image.';
        return null;
    }
    return '/storage/about/' . $filename;
}

/** Deletes a previously uploaded About image (never touches bundled /gallery images). */
function about_delete_upload(?string $path, string $uploadDir): void
{
    if ($path && strpos($path, '/storage/about/') === 0) {
        $file = $uploadDir . basename($path);
        if (is_file($file)) @unlink($file);
    }
}

/** Preview URL for the CMS table: uploads come from the backend, bundled images from the website. */
function about_preview_url(string $path): string
{
    if (preg_match('#^https?://#', $path)) return $path;
    return strpos($path, '/storage/') === 0 ? asset_url($path) : frontend_url($path);
}

// ---------- Handle POST ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id     = (int) ($_POST['id'] ?? 0);

    $oldImage = null;
    if ($id > 0) {
        $stmt = $conn->prepare("SELECT image_url FROM about_sections WHERE id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $oldImage = $stmt->get_result()->fetch_assoc()['image_url'] ?? null;
        $stmt->close();
    }

    if ($action === 'delete') {
        $stmt = $conn->prepare("DELETE FROM about_sections WHERE id = ?");
        $stmt->bind_param('i', $id);
        if ($stmt->execute()) {
            about_delete_upload($oldImage, $uploadDir);
            $success = 'Block deleted.';
        } else {
            $error = 'Delete failed: ' . $stmt->error;
        }
        $stmt->close();

    } elseif (in_array($action, ['create', 'update'], true)) {
        $type         = $_POST['section_type'] ?? '';
        $title        = trim($_POST['title'] ?? '');
        $subtitle     = trim($_POST['subtitle'] ?? '');
        $body         = trim($_POST['body'] ?? '');
        $imageUrl     = trim($_POST['image_url'] ?? '');
        $imageAlt     = trim($_POST['image_alt'] ?? '');
        $icon         = trim($_POST['icon'] ?? '');
        $displayOrder = (int) ($_POST['display_order'] ?? 0);
        $isActive     = isset($_POST['is_active']) ? 1 : 0;

        if (!isset($sectionTypes[$type])) {
            $error = 'Choose a valid section.';
        } elseif ($title === '') {
            $error = 'Title is required.';
        } else {
            $uploaded = about_store_upload($_FILES['image_file'] ?? [], $uploadDir, $error);
            if ($uploaded) {
                $imageUrl = $uploaded;
            }
        }

        if (!$error) {
            if ($action === 'create') {
                $stmt = $conn->prepare(
                    "INSERT INTO about_sections (section_type, title, subtitle, body, image_url, image_alt, icon, display_order, is_active)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
                );
                $stmt->bind_param('sssssssii', $type, $title, $subtitle, $body, $imageUrl, $imageAlt, $icon, $displayOrder, $isActive);
            } else {
                $stmt = $conn->prepare(
                    "UPDATE about_sections SET section_type=?, title=?, subtitle=?, body=?, image_url=?, image_alt=?, icon=?, display_order=?, is_active=?
                     WHERE id=?"
                );
                $stmt->bind_param('sssssssiii', $type, $title, $subtitle, $body, $imageUrl, $imageAlt, $icon, $displayOrder, $isActive, $id);
            }

            if ($stmt->execute()) {
                // Clean up the replaced upload only after the new value is saved
                if ($action === 'update' && $oldImage !== $imageUrl) {
                    about_delete_upload($oldImage, $uploadDir);
                }
                $success = $action === 'create' ? 'Block added.' : 'Block updated.';
            } else {
                if ($uploaded ?? null) about_delete_upload($uploaded, $uploadDir);
                $error = 'Save failed: ' . $stmt->error;
            }
            $stmt->close();
        }
    }
}

// ---------- Fetch all blocks, grouped by section ----------
$blocks = array_fill_keys(array_keys($sectionTypes), []);
$res = $conn->query("SELECT id, section_type, title, subtitle, body, image_url, image_alt, icon, display_order, is_active FROM about_sections ORDER BY display_order ASC, id ASC");
if ($res) {
    while ($r = $res->fetch_assoc()) {
        $r['id']            = (int) $r['id'];
        $r['display_order'] = (int) $r['display_order'];
        $r['is_active']     = (bool) $r['is_active'];
        if (isset($blocks[$r['section_type']])) {
            $blocks[$r['section_type']][] = $r;
        }
    }
} else {
    $error = $error ?: 'The about_sections table is missing. Run backend/migrations/add_about_sections.sql first.';
}
?>

<?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>
<?php if ($error):   ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

<div style="margin-bottom:16px; display:flex; justify-content:space-between; align-items:center; gap:12px; flex-wrap:wrap;">
    <button class="btn btn-primary" onclick="openAboutModal()">+ Add Block</button>
    <a class="btn btn-secondary" href="<?= htmlspecialchars(frontend_url('/about')) ?>" target="_blank" rel="noopener">View About page ↗</a>
</div>

<?php foreach ($sectionTypes as $type => $meta): ?>
    <div class="card" style="margin-bottom:20px;">
        <div style="display:flex; justify-content:space-between; align-items:center; gap:12px; margin-bottom:6px; flex-wrap:wrap;">
            <h3 style="margin:0;"><?= htmlspecialchars($meta['label']) ?></h3>
            <button class="btn btn-secondary btn-sm" onclick='openAboutModal({ section_type: <?= json_encode($type) ?> })'>+ Add <?= htmlspecialchars($meta['label']) ?></button>
        </div>
        <p style="margin:0 0 12px; color:var(--muted); font-size:0.85rem;"><?= htmlspecialchars($meta['help']) ?></p>

        <div class="tbl-wrap">
            <table>
                <thead>
                    <tr>
                        <th style="width:70px;">Image</th>
                        <th>Title</th>
                        <th>Subtitle</th>
                        <th>Order</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($blocks[$type] as $b): ?>
                        <tr>
                            <td>
                                <?php if ($b['image_url']): ?>
                                    <img src="<?= htmlspecialchars(about_preview_url($b['image_url'])) ?>" alt="" style="width:56px; height:40px; object-fit:cover; border-radius:6px;">
                                <?php else: ?>
                                    <span style="font-size:1.3rem;"><?= htmlspecialchars(mb_strlen($b['icon'] ?? '') <= 4 ? ($b['icon'] ?? '') : '') ?></span>
                                <?php endif; ?>
                            </td>
                            <td><strong><?= htmlspecialchars($b['title']) ?></strong></td>
                            <td style="max-width:280px; color:#4b5563; font-size:0.85rem; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;"><?= htmlspecialchars($b['subtitle'] ?? '') ?></td>
                            <td><?= $b['display_order'] ?></td>
                            <td>
                                <?php if ($b['is_active']): ?>
                                    <span class="badge badge-green">Active</span>
                                <?php else: ?>
                                    <span class="badge badge-red">Hidden</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <button class="btn btn-secondary btn-sm" onclick='openAboutModal(<?= htmlspecialchars(json_encode($b), ENT_QUOTES) ?>)'>✏️ Edit</button>
                                <form method="POST" style="display:inline;" onsubmit="return confirmDelete('Delete &quot;<?= htmlspecialchars(addslashes($b['title'])) ?>&quot;?')">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= $b['id'] ?>">
                                    <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($blocks[$type])): ?>
                        <tr><td colspan="6" style="text-align:center; color:var(--muted);">Nothing here yet. The website shows its built-in default text for this section until you add one.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endforeach; ?>

<!-- Add / Edit Modal -->
<div class="modal-backdrop" id="aboutModalBackdrop">
    <div class="modal" style="width: min(680px, 96vw);">
        <h3 id="aboutModalHeading">Add Block</h3>
        <form method="POST" id="aboutForm" enctype="multipart/form-data">
            <input type="hidden" name="action" id="aboutAction" value="create">
            <input type="hidden" name="id" id="aboutId" value="0">

            <div class="form-row">
                <div class="form-group">
                    <label for="aboutType">Section *</label>
                    <select id="aboutType" class="form-control" name="section_type" required onchange="updateAboutHelp()">
                        <?php foreach ($sectionTypes as $type => $meta): ?>
                            <option value="<?= $type ?>"><?= htmlspecialchars($meta['label']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group" style="max-width:140px;">
                    <label for="aboutOrder">Display Order</label>
                    <input type="number" id="aboutOrder" class="form-control" name="display_order" value="1">
                </div>
            </div>
            <p id="aboutHelp" style="margin:-4px 0 14px; color:var(--muted); font-size:0.82rem;"></p>

            <div class="form-group">
                <label for="aboutTitle">Title / Name *</label>
                <input id="aboutTitle" class="form-control" name="title" required>
            </div>

            <div class="form-group">
                <label for="aboutSubtitle">Subtitle / Role / Quote</label>
                <input id="aboutSubtitle" class="form-control" name="subtitle">
            </div>

            <div class="form-group">
                <label for="aboutBody">Body</label>
                <textarea id="aboutBody" class="form-control" name="body" rows="6" placeholder="Plain text. Leave a blank line between paragraphs."></textarea>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="aboutImageFile">Upload Image (JPG, PNG, WebP, max 5MB)</label>
                    <input type="file" id="aboutImageFile" class="form-control" name="image_file" accept="image/jpeg,image/png,image/webp">
                </div>
                <div class="form-group">
                    <label for="aboutImageUrl">…or Image Path</label>
                    <input id="aboutImageUrl" class="form-control" name="image_url" placeholder="/gallery/homestay.jpg">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="aboutImageAlt">Image Description (alt text)</label>
                    <input id="aboutImageAlt" class="form-control" name="image_alt" placeholder="What the photo shows">
                </div>
                <div class="form-group" style="max-width:200px;">
                    <label for="aboutIcon">Icon (values only)</label>
                    <input id="aboutIcon" class="form-control" name="icon" placeholder="🌿 or compass">
                </div>
            </div>

            <div class="check-group" style="margin-bottom:16px;">
                <input type="checkbox" id="aboutActive" name="is_active" value="1" checked>
                <label for="aboutActive">Active (visible on the website)</label>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeAboutModal()">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Block</button>
            </div>
        </form>
    </div>
</div>

<script>
const ABOUT_HELP = <?= json_encode(array_map(fn($m) => $m['help'], $sectionTypes)) ?>;

function updateAboutHelp() {
    document.getElementById('aboutHelp').innerText = ABOUT_HELP[document.getElementById('aboutType').value] || '';
}

function openAboutModal(data = {}) {
    const isEdit = !!data.id;
    document.getElementById('aboutForm').reset();
    document.getElementById('aboutModalHeading').innerText = isEdit ? 'Edit Block' : 'Add Block';
    document.getElementById('aboutAction').value = isEdit ? 'update' : 'create';
    document.getElementById('aboutId').value = data.id || 0;
    document.getElementById('aboutType').value = data.section_type || 'value';
    document.getElementById('aboutTitle').value = data.title || '';
    document.getElementById('aboutSubtitle').value = data.subtitle || '';
    document.getElementById('aboutBody').value = data.body || '';
    document.getElementById('aboutImageUrl').value = data.image_url || '';
    document.getElementById('aboutImageAlt').value = data.image_alt || '';
    document.getElementById('aboutIcon').value = data.icon || '';
    document.getElementById('aboutOrder').value = data.display_order || 1;
    document.getElementById('aboutActive').checked = isEdit ? !!data.is_active : true;
    updateAboutHelp();
    document.getElementById('aboutModalBackdrop').classList.add('active');
}

function closeAboutModal() {
    document.getElementById('aboutModalBackdrop').classList.remove('active');
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
