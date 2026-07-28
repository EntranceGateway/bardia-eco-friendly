<?php
/**
 * CMS — Gallery Images Management
 */

$pageTitle = 'Gallery Images';
require_once __DIR__ . '/includes/header.php';

$success = '';
$error   = '';

// ---------- Handle POST ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'delete') {
        try {
            $id    = (int) ($_POST['id'] ?? 0);
            $fetch = $conn->prepare("SELECT image_url FROM gallery_images WHERE id = ?");
            $fetch->bind_param('i', $id);
            $fetch->execute();
            $imgRow = $fetch->get_result()->fetch_assoc();
            $fetch->close();

            $stmt = $conn->prepare("DELETE FROM gallery_images WHERE id = ?");
            $stmt->bind_param('i', $id);
            if ($stmt->execute()) {
                $success = 'Image deleted.';
                if ($imgRow) {
                    $filename = basename($imgRow['image_url']);
                    $path     = __DIR__ . '/../storage/gallery/' . $filename;
                    if (file_exists($path)) @unlink($path);
                }
            } else {
                $error = 'Delete failed.';
            }
            $stmt->close();
        } catch (mysqli_sql_exception $e) {
            $error = 'Database error during deletion: ' . $e->getMessage();
        }

    } elseif ($action === 'upload') {
        $categoryId = (int) ($_POST['category_id'] ?? 0);
        $files      = $_FILES['images'] ?? null;
        $maxBytes   = 5 * 1024 * 1024; // 5MB
        $allowed    = ['image/jpeg', 'image/png', 'image/webp'];

        if ($categoryId <= 0) { $error = 'Please select a category.'; }
        elseif (!$files || empty($files['name'][0])) { $error = 'Please select at least one image.'; }
        else {
            $uploadedCount = 0;
            $errors = [];

            for ($i = 0; $i < count($files['name']); $i++) {
                $file = [
                    'name'     => $files['name'][$i],
                    'type'     => $files['type'][$i],
                    'tmp_name' => $files['tmp_name'][$i],
                    'error'    => $files['error'][$i],
                    'size'     => $files['size'][$i]
                ];

                if ($file['error'] !== UPLOAD_ERR_OK) {
                    $errHint = match ($file['error']) {
                        UPLOAD_ERR_INI_SIZE   => "Exceeds server's upload_max_filesize limit.",
                        UPLOAD_ERR_FORM_SIZE  => "Exceeds maximum allowed form file size.",
                        UPLOAD_ERR_PARTIAL    => "File was only partially uploaded.",
                        UPLOAD_ERR_NO_FILE    => "No file was uploaded.",
                        UPLOAD_ERR_NO_TMP_DIR => "Missing temporary upload folder on server.",
                        UPLOAD_ERR_CANT_WRITE => "Failed to write file to server disk.",
                        default               => "Upload error code: " . $file['error']
                    };
                    $errors[] = "File '{$file['name']}': {$errHint}";
                    continue;
                }

                $finfo    = finfo_open(FILEINFO_MIME_TYPE);
                $mimeType = finfo_file($finfo, $file['tmp_name']);
                finfo_close($finfo);

                if ($file['size'] > $maxBytes) {
                    $errors[] = "File '{$file['name']}' is too large (max 5MB).";
                } elseif (!in_array($mimeType, $allowed, true)) {
                    $errors[] = "File '{$file['name']}' is an invalid type. Use JPG, PNG or WebP.";
                } else {
                    $imageSize = getimagesize($file['tmp_name']);
                    if ($imageSize === false) {
                        $errors[] = "File '{$file['name']}' is not a valid image.";
                        continue;
                    }
                    $width  = (int) $imageSize[0];
                    $height = (int) $imageSize[1];
                    $ext    = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                    $filename = time() . '_' . $i . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                    $uploadDir = __DIR__ . '/../storage/gallery/';
                    if (!is_dir($uploadDir)) @mkdir($uploadDir, 0775, true);

                    if (move_uploaded_file($file['tmp_name'], $uploadDir . $filename)) {
                        $imageUrl  = '/storage/gallery/' . $filename;
                        $rawTitle  = trim($_POST['title_text'] ?? '');
                        $titleText = $rawTitle !== '' ? $rawTitle : ucfirst(str_replace(['_', '-'], ' ', pathinfo($file['name'], PATHINFO_FILENAME)));
                        $altText   = trim($_POST['alt_text'] ?? '') ?: $titleText;
                        $caption   = trim($_POST['caption'] ?? '');
                        $order     = (int) ($_POST['display_order'] ?? 0);
                        
                        // Generate unique slug per file to avoid UNIQUE key constraint collision on uk_galimg_slug
                        $baseSlug = strtolower(preg_replace('/[^a-z0-9-]/', '-', $titleText));
                        $baseSlug = trim(preg_replace('/-+/', '-', $baseSlug), '-');
                        if (empty($baseSlug)) { $baseSlug = 'gallery-image'; }
                        $slug = $baseSlug . '-' . time() . '-' . ($i + 1) . '-' . bin2hex(random_bytes(2));

                        try {
                            $stmt = $conn->prepare("INSERT INTO gallery_images (category_id, image_url, slug, alt_text, title_text, caption, width, height, display_order) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                            $stmt->bind_param('isssssiii', $categoryId, $imageUrl, $slug, $altText, $titleText, $caption, $width, $height, $order);
                            if ($stmt->execute()) {
                                $uploadedCount++;
                            } else {
                                @unlink($uploadDir . $filename);
                                $errors[] = "Failed to save '{$file['name']}' to database.";
                            }
                            $stmt->close();
                        } catch (mysqli_sql_exception $e) {
                            @unlink($uploadDir . $filename);
                            $errors[] = "Database error for '{$file['name']}': " . $e->getMessage();
                        }
                    } else {
                        $errors[] = "Failed to move '{$file['name']}' to storage folder ('storage/gallery/'). Please check directory permissions on the server.";
                    }
                }
            }
            if ($uploadedCount > 0) $success = "Successfully uploaded $uploadedCount image(s).";
            if (!empty($errors)) $error = implode("<br>", $errors);
        }

    } elseif ($action === 'update') {
        try {
            $id         = (int) ($_POST['id'] ?? 0);
            $categoryId = (int) ($_POST['category_id'] ?? 0);
            $altText    = trim($_POST['alt_text'] ?? '');
            $titleText  = trim($_POST['title_text'] ?? '');
            $caption    = trim($_POST['caption'] ?? '');
            $order      = (int) ($_POST['display_order'] ?? 0);
            $isActive   = isset($_POST['is_active']) ? 1 : 0;
            $newFile    = $_FILES['new_image'] ?? null;
            
            $slug         = trim($_POST['slug']          ?? '');
            if (empty($slug)) { $slug = strtolower(preg_replace('/[^a-z0-9-]/', '-', $titleText)); }
            $metaTitle    = trim($_POST['meta_title']    ?? '');
            $metaDesc     = trim($_POST['meta_description'] ?? '');
            $metaKeywords = trim($_POST['meta_keywords'] ?? '');
            $metaImage    = trim($_POST['meta_image']    ?? '');
            $canonicalUrl = trim($_POST['canonical_url'] ?? '');
            $robots       = trim($_POST['robots']        ?? 'index,follow');

            if ($id <= 0) { $error = 'Invalid image ID.'; }
            else {
                $conn->begin_transaction();
                
                if ($newFile && !empty($newFile['name'])) {
                    $maxBytes = 5 * 1024 * 1024;
                    $allowed  = ['image/jpeg', 'image/png', 'image/webp'];
                    $finfo    = finfo_open(FILEINFO_MIME_TYPE);
                    $mimeType = finfo_file($finfo, $newFile['tmp_name']);
                    finfo_close($finfo);

                    if ($newFile['size'] > $maxBytes) { $error = 'Image is too large (max 5MB).'; }
                    elseif (!in_array($mimeType, $allowed, true)) { $error = 'Invalid file type.'; }
                    else {
                        $imageSize = getimagesize($newFile['tmp_name']);
                        if ($imageSize === false) {
                            $error = 'Uploaded file is not a valid image.';
                        }
                    }

                    if (!$error) {
                        $old = $conn->prepare("SELECT image_url FROM gallery_images WHERE id = ?");
                        $old->bind_param('i', $id); $old->execute();
                        $oldRes = $old->get_result()->fetch_assoc();
                        $old->close();
                        $width  = (int) $imageSize[0];
                        $height = (int) $imageSize[1];

                        $ext      = pathinfo($newFile['name'], PATHINFO_EXTENSION);
                        $filename = time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                        if (move_uploaded_file($newFile['tmp_name'], __DIR__ . '/../storage/gallery/' . $filename)) {
                            $newUrl = '/storage/gallery/' . $filename;
                            $updFile = $conn->prepare("UPDATE gallery_images SET image_url = ?, width = ?, height = ? WHERE id = ?");
                            $updFile->bind_param('siii', $newUrl, $width, $height, $id);
                            if ($updFile->execute()) {
                                if ($oldRes) {
                                    $oldFilename = basename($oldRes['image_url']);
                                    $oldPath = __DIR__ . '/../storage/gallery/' . $oldFilename;
                                    if (file_exists($oldPath)) @unlink($oldPath);
                                }
                            } else {
                                @unlink(__DIR__ . '/../storage/gallery/' . $filename);
                                $error = 'Image replacement failed.';
                            }
                            $updFile->close();
                        } else {
                            $error = 'Failed to move replacement image to storage.';
                        }
                    }
                }

                if (!$error) {
                    $stmt = $conn->prepare("UPDATE gallery_images SET category_id = ?, slug = ?, alt_text = ?, title_text = ?, caption = ?, display_order = ?, is_active = ?, meta_title = ?, meta_description = ?, meta_keywords = ?, meta_image = ?, canonical_url = ?, robots = ? WHERE id = ?");
                    $stmt->bind_param('issssiissssssi', $categoryId, $slug, $altText, $titleText, $caption, $order, $isActive, $metaTitle, $metaDesc, $metaKeywords, $metaImage, $canonicalUrl, $robots, $id);
                    if ($stmt->execute()) {
                        $conn->commit();
                        $success = 'Image updated.';
                    } else {
                        $conn->rollback();
                        $error = 'Update failed.';
                    }
                    $stmt->close();
                } else {
                    $conn->rollback();
                }
            }
        } catch (mysqli_sql_exception $e) {
            if ($conn->in_transaction) $conn->rollback();
            $error = 'Database error during update: ' . $e->getMessage();
        }
    } elseif ($action === 'sync') {
        $galleryDir = __DIR__ . '/../storage/gallery/';
        $synced = 0;
        if (is_dir($galleryDir)) {
            $defaultCatId = 1;
            $catCheck = $conn->query("SELECT id FROM gallery_categories WHERE is_active = 1 ORDER BY id ASC LIMIT 1");
            if ($catCheck && $cRow = $catCheck->fetch_assoc()) {
                $defaultCatId = (int) $cRow['id'];
            }

            $dbUrls = [];
            $urlRes = $conn->query("SELECT image_url FROM gallery_images");
            if ($urlRes) {
                while ($u = $urlRes->fetch_assoc()) {
                    $dbUrls[] = $u['image_url'];
                }
            }

            $files = scandir($galleryDir);
            foreach ($files as $file) {
                if ($file === '.' || $file === '..' || strpos($file, '.') === 0) continue;
                $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true)) continue;

                $imageUrl = '/storage/gallery/' . $file;
                if (!in_array($imageUrl, $dbUrls, true)) {
                    $filePath = $galleryDir . $file;
                    $width = 800; $height = 600;
                    $imageSize = @getimagesize($filePath);
                    if ($imageSize) {
                        $width = (int) $imageSize[0];
                        $height = (int) $imageSize[1];
                    }

                    $titleText = ucfirst(str_replace(['_', '-'], ' ', pathinfo($file, PATHINFO_FILENAME)));
                    $slug = strtolower(preg_replace('/[^a-z0-9-]/', '-', $titleText));
                    $slug = trim(preg_replace('/-+/', '-', $slug), '-');
                    $altText = $titleText;
                    $caption = 'Image ' . $file;

                    $stmt = $conn->prepare("INSERT INTO gallery_images (category_id, image_url, slug, alt_text, title_text, caption, width, height, display_order, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0, 1)");
                    $stmt->bind_param('isssssii', $defaultCatId, $imageUrl, $slug, $altText, $titleText, $caption, $width, $height);
                    if ($stmt->execute()) {
                        $synced++;
                    }
                    $stmt->close();
                }
            }
        }
        $success = "Synced $synced image(s) from disk into database.";
    }
}

// ---------- Fetch categories ----------
$categories = [];
try {
    $catResult = db_query($conn, "SELECT id, name FROM gallery_categories ORDER BY display_order ASC");
    while ($c = $catResult->fetch_assoc()) {
        $c['id'] = (int) $c['id'];
        $categories[] = $c;
    }
} catch (RuntimeException $e) {
    error_log('[gallery-images] categories fetch: ' . $e->getMessage());
    $error = 'Could not load gallery categories. Please try again.';
}

// ---------- Optional filter by category ----------
$filterCat = (int) ($_GET['category_id'] ?? 0);

$imgSql = "SELECT gi.id, gi.category_id, gi.image_url, gi.slug, gi.alt_text, gi.title_text, gi.caption, gi.width, gi.height, gi.display_order, gi.is_active, gi.meta_title, gi.meta_description, gi.meta_keywords, gi.meta_image, gi.canonical_url, gi.robots,
                  gc.name AS category_name
           FROM gallery_images gi
           LEFT JOIN gallery_categories gc ON gi.category_id = gc.id";
if ($filterCat > 0) $imgSql .= " WHERE gi.category_id = " . $filterCat;
$imgSql .= " ORDER BY gi.display_order ASC, gi.id ASC";

// ---------- Fetch images ----------
$images = [];
try {
    $imgRes = db_query($conn, $imgSql);
    while ($r = $imgRes->fetch_assoc()) {
        $r['id']            = (int)  $r['id'];
        $r['category_id']   = (int)  $r['category_id'];
        $r['display_order'] = (int)  $r['display_order'];
        $r['is_active']     = (bool) $r['is_active'];
        $r['width']         = $r['width'] !== null ? (int) $r['width'] : null;
        $r['height']        = $r['height'] !== null ? (int) $r['height'] : null;
        $images[] = $r;
    }
} catch (RuntimeException $e) {
    error_log('[gallery-images] images fetch: ' . $e->getMessage());
    $error = 'Could not load gallery images. Please try again.';
}
?>

<?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

<!-- Upload form -->
<div class="card" style="margin-bottom:20px;">
    <div class="card-header"><h2>Upload New Image</h2></div>
    <div class="card-body">
        <?php if (empty($categories)): ?>
            <p style="color:#6b7280;">No gallery categories found. <a href="gallery-categories.php">Create one first.</a></p>
        <?php else: ?>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="upload">
            <div class="form-row">
                <div class="form-group">
                    <label for="upCat">Category <span style="color:red">*</span></label>
                    <select id="upCat" name="category_id" class="form-control" required>
                        <option value="">— Select —</option>
                        <?php foreach ($categories as $c): ?>
                            <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="upAlt">Alt Text</label>
                    <input type="text" class="form-control" id="upAlt" name="alt_text" placeholder="Tiger in the grass">
                </div>
                <div class="form-group">
                    <label for="upTitle">Image Title</label>
                    <input type="text" class="form-control" id="upTitle" name="title_text" placeholder="Royal Bengal tiger">
                </div>
                <div class="form-group">
                    <label for="upOrder">Display Order</label>
                    <input type="number" class="form-control" id="upOrder" name="display_order" value="0" min="0">
                </div>
            </div>
            <div class="form-group">
                <label for="upCaption">Caption</label>
                <input type="text" class="form-control" id="upCaption" name="caption" placeholder="Wildlife sighting near Bardia National Park">
            </div>
            <div class="form-group">
                <label for="upFile">Select Images <span style="color:red">*</span></label>
                <input type="file" class="form-control" id="upFile" name="images[]"
                       accept="image/jpeg,image/png,image/webp" multiple required>
                <small style="color:#6b7280;font-size:.78rem;">You can select multiple files. JPG, PNG, WebP — max 5MB per image.</small>
            </div>
            <button type="submit" class="btn btn-primary">📤 Bulk Upload</button>

        </form>
        <?php endif; ?>
    </div>
</div>

<!-- Filter + list -->
<div class="card">
    <div class="card-header">
        <h2>Gallery Images</h2>
        <div style="display:flex;gap:12px;align-items:center;">
            <form method="POST" style="display:inline;">
                <input type="hidden" name="action" value="sync">
                <button type="submit" class="btn btn-secondary btn-sm" title="Register existing disk files in storage/gallery/ into database">🔄 Sync Storage Images</button>
            </form>
            <form method="GET" style="display:flex;gap:8px;align-items:center;">
                <select name="category_id" class="form-control" style="width:auto;">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $c): ?>
                        <option value="<?= $c['id'] ?>" <?= $c['id'] === $filterCat ? 'selected' : '' ?>><?= htmlspecialchars($c['name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="btn btn-secondary btn-sm">Filter</button>
            </form>
        </div>
    </div>
    <!-- Desktop table -->
    <div class="tbl-wrap">
        <table>
            <thead>
                <tr><th style="width:70px;">Preview</th><th>Category</th><th>Alt Text</th><th>Title</th><th>Order</th><th>Active</th><th>Actions</th></tr>
            </thead>
            <tbody>
            <?php foreach ($images as $img): ?>
                <tr>
                    <td>
                        <img src="<?= htmlspecialchars(asset_url($img['image_url'])) ?>"
                             alt="<?= htmlspecialchars($img['alt_text'] ?? '') ?>"
                             style="width:56px;height:56px;object-fit:cover;border-radius:6px;border:1px solid #e0e0e0;">
                    </td>
                    <td><?= htmlspecialchars($img['category_name'] ?? '—') ?></td>
                    <td><?= htmlspecialchars($img['alt_text'] ?? '—') ?></td>
                    <td><?= htmlspecialchars($img['title_text'] ?? '—') ?></td>
                    <td><?= $img['display_order'] ?></td>
                    <td><?= $img['is_active'] ? '<span class="badge badge-green">Active</span>' : '<span class="badge badge-red">Inactive</span>' ?></td>
                    <td>
                        <button class="btn btn-secondary btn-sm" data-img="<?= htmlspecialchars(json_encode($img), ENT_QUOTES, 'UTF-8') ?>" onclick="openEditFromBtn(this)">Edit</button>
                        <form method="POST" style="display:inline;" onsubmit="return confirmDelete()">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= $img['id'] ?>">
                            <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($images)): ?>
                <tr><td colspan="7" style="color:#6b7280;text-align:center;">No images yet.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
    <!-- Mobile cards -->
    <div class="m-cards" style="padding:16px;">
        <?php foreach ($images as $img): ?>
        <div class="m-card" style="display:flex;gap:12px;">
            <img src="<?= htmlspecialchars(asset_url($img['image_url'])) ?>"
                 alt="" style="width:60px;height:60px;object-fit:cover;border-radius:6px;flex-shrink:0;">
            <div style="flex:1;">
                <div class="m-title"><?= htmlspecialchars($img['alt_text'] ?: 'No alt text') ?></div>
                <div class="m-row"><span>Category</span><span><?= htmlspecialchars($img['category_name'] ?? '—') ?></span></div>
                <div class="m-row"><span>Title</span><span><?= htmlspecialchars($img['title_text'] ?? '—') ?></span></div>
                <div class="m-row"><span>Active</span><span><?= $img['is_active'] ? 'Yes' : 'No' ?></span></div>
                <div class="m-actions">
                    <button class="btn btn-secondary btn-sm" data-img="<?= htmlspecialchars(json_encode($img), ENT_QUOTES, 'UTF-8') ?>" onclick="openEditFromBtn(this)">Edit</button>
                    <form method="POST" onsubmit="return confirmDelete()">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= $img['id'] ?>">
                        <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                    </form>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Edit modal -->
<div class="modal-backdrop" id="editModal">
    <div class="modal">
        <h3>Edit Image Metadata</h3>
        <form method="POST" id="editForm" enctype="multipart/form-data">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="id"     id="eId"     value="">
            <div class="form-group">
                <label for="eCat">Category</label>
                <select id="eCat" name="category_id" class="form-control" required>
                    <?php foreach ($categories as $c): ?>
                        <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="eAlt">Alt Text</label>
                <input type="text" class="form-control" id="eAlt" name="alt_text">
            </div>
            <div class="form-group">
                <label for="eTitle">Image Title</label>
                <input type="text" class="form-control" id="eTitle" name="title_text">
            </div>
            <div class="form-group">
                <label for="eCaption">Caption</label>
                <input type="text" class="form-control" id="eCaption" name="caption">
            </div>
            <div class="form-group">
                <label for="eFile">Replace Image (optional)</label>
                <input type="file" class="form-control" id="eFile" name="new_image"
                       accept="image/jpeg,image/png,image/webp">
                <small style="color:#6b7280;font-size:.78rem;">JPG, PNG, WebP — max 5MB</small>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label for="eOrder">Display Order</label>
                    <input type="number" class="form-control" id="eOrder" name="display_order" value="0" min="0">
                </div>
                <div class="form-group" style="display:flex;align-items:flex-end;">
                    <div class="check-group">
                        <input type="checkbox" id="eActive" name="is_active" value="1">
                        <label for="eActive">Active</label>
                    </div>
                </div>
            </div>
            
            <hr style="margin:20px 0; border:0; border-top:1px solid var(--border);">
            <h4 style="margin-bottom:12px; font-size:0.95rem; color:var(--brand-dk);">SEO Settings</h4>
            <div class="form-row">
                <div class="form-group">
                    <label for="eSlug">URL Slug (Auto-generated if empty)</label>
                    <input type="text" class="form-control" id="eSlug" name="slug" pattern="[a-z0-9\-]+">
                </div>
                <div class="form-group">
                    <label for="eMetaTitle">Meta Title</label>
                    <input type="text" class="form-control" id="eMetaTitle" name="meta_title">
                </div>
            </div>
            <div class="form-group">
                <label for="eMetaDesc">Meta Description</label>
                <textarea class="form-control" id="eMetaDesc" name="meta_description" rows="2"></textarea>
            </div>
            <div class="form-group">
                <label for="eMetaKeywords">Meta Keywords</label>
                <input type="text" class="form-control" id="eMetaKeywords" name="meta_keywords" placeholder="safari, wildlife, homestay">
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label for="eMetaImage">Meta Image URL</label>
                    <input type="text" class="form-control" id="eMetaImage" name="meta_image">
                </div>
                <div class="form-group">
                    <label for="eCanonical">Canonical URL</label>
                    <input type="url" class="form-control" id="eCanonical" name="canonical_url">
                </div>
            </div>
            <div class="form-group">
                <label for="eRobots">Robots</label>
                <input type="text" class="form-control" id="eRobots" name="robots" value="index,follow">
            </div>
            <hr style="margin:20px 0; border:0; border-top:1px solid var(--border);">
            
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeEdit()">Cancel</button>
                <button type="submit" class="btn btn-primary">Update</button>
            </div>
        </form>
    </div>
</div>

<script>
function openEditFromBtn(btn) {
    try {
        var img = JSON.parse(btn.getAttribute('data-img'));
        openEdit(img);
    } catch (e) {
        console.error('Failed to parse image data', e);
    }
}
function openEdit(img) {
    document.getElementById('editModal').classList.add('active');
    document.getElementById('eId').value    = img.id;
    document.getElementById('eCat').value   = img.category_id;
    document.getElementById('eAlt').value   = img.alt_text || '';
    document.getElementById('eTitle').value = img.title_text || '';
    document.getElementById('eCaption').value = img.caption || '';
    document.getElementById('eOrder').value = img.display_order || 0;
    
    document.getElementById('eSlug').value       = img.slug || '';
    document.getElementById('eMetaTitle').value  = img.meta_title || '';
    document.getElementById('eMetaDesc').value   = img.meta_description || '';
    document.getElementById('eMetaKeywords').value= img.meta_keywords || '';
    document.getElementById('eMetaImage').value  = img.meta_image || '';
    document.getElementById('eCanonical').value  = img.canonical_url || '';
    document.getElementById('eRobots').value     = img.robots || 'index,follow';
    
    document.getElementById('eActive').checked = !!img.is_active;
}
function closeEdit() {
    document.getElementById('editModal').classList.remove('active');
}
document.getElementById('editModal').addEventListener('click', function(e) {
    if (e.target === this) closeEdit();
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
