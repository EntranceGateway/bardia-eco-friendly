<?php
/**
 * CMS — Blog Posts Management
 */

$pageTitle = 'Blog Posts';
require_once __DIR__ . '/includes/header.php';

$success = '';
$error   = '';

// ---------- Fetch categories for dropdown ----------
$blogCategories = [];
try {
    $catRes = db_query($conn, "SELECT id, name FROM blog_categories WHERE is_active = 1 ORDER BY display_order ASC, id ASC");
    while ($c = $catRes->fetch_assoc()) { $c['id'] = (int) $c['id']; $blogCategories[] = $c; }
} catch (RuntimeException $e) {
    error_log('[blog-posts] categories fetch: ' . $e->getMessage());
    $error = 'Could not load blog categories. Please try again.';
}

// ---------- Handle POST ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);

        // Get image path before deleting
        $fetch = $conn->prepare("SELECT featured_image FROM blog_posts WHERE id = ?");
        $fetch->bind_param('i', $id);
        $fetch->execute();
        $imgRow = $fetch->get_result()->fetch_assoc();
        $fetch->close();

        $stmt = $conn->prepare("DELETE FROM blog_posts WHERE id = ?");
        $stmt->bind_param('i', $id);
        if ($stmt->execute()) {
            $success = 'Post deleted.';
            if ($imgRow && $imgRow['featured_image']) {
                $path = __DIR__ . '/../storage/blog/' . basename($imgRow['featured_image']);
                if (file_exists($path)) @unlink($path);
            }
        } else {
            $error = 'Delete failed: ' . $stmt->error;
        }
        $stmt->close();

    } elseif (in_array($action, ['create', 'update'], true)) {
        $id              = (int)  ($_POST['id']               ?? 0);
        $categoryId      = (int)  ($_POST['category_id']      ?? 0);
        $title           = trim(  $_POST['title']             ?? '');
        $slug            = trim(  $_POST['slug']              ?? '');
        $excerpt         = trim(  $_POST['excerpt']           ?? '');
        $content         =        $_POST['content']           ?? '';
        $authorName      = trim(  $_POST['author_name']       ?? '');
        $featuredImgAlt  = trim(  $_POST['featured_image_alt'] ?? '');
        $metaTitle       = trim(  $_POST['meta_title']        ?? '');
        $metaDescription = trim(  $_POST['meta_description']  ?? '');
        $metaKeywords    = trim(  $_POST['meta_keywords']     ?? '');
        $metaImage       = trim(  $_POST['meta_image']        ?? '');
        $canonicalUrl    = trim(  $_POST['canonical_url']     ?? '');
        $robots          =        $_POST['robots']            ?? 'index,follow';
        $isFeatured      = isset($_POST['is_featured']) ? 1 : 0;
        $status          =        $_POST['status']            ?? 'draft';
        $displayOrder    = (int)  ($_POST['display_order']    ?? 0);
        $isActive        = isset($_POST['is_active']) ? 1 : 0;

        // Auto-generate slug
        if (empty($slug)) {
            $slug = strtolower(trim($title));
            $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
            $slug = trim($slug, '-');
        }

        if ($categoryId <= 0)                             { $error = 'Please select a category.'; }
        elseif (empty($title))                            { $error = 'Post title is required.'; }
        elseif (!in_array($status, ['draft','published','archived'], true)) { $error = 'Invalid status.'; }
        else {
            if ($action === 'create') {
                // Check slug uniqueness
                $check = $conn->prepare("SELECT id FROM blog_posts WHERE slug = ?");
                $check->bind_param('s', $slug);
                $check->execute();
                $check->store_result();
                if ($check->num_rows > 0) { $error = 'A post with this slug already exists.'; }
                $check->close();

                if (!$error) {
                    // Handle image upload
                    $featuredImage = '';
                    if (isset($_FILES['featured_image']) && $_FILES['featured_image']['error'] === UPLOAD_ERR_OK) {
                        $file     = $_FILES['featured_image'];
                        $maxBytes = 5 * 1024 * 1024;
                        $allowed  = ['image/jpeg', 'image/png', 'image/webp'];
                        $finfo    = finfo_open(FILEINFO_MIME_TYPE);
                        $mimeType = finfo_file($finfo, $file['tmp_name']);
                        finfo_close($finfo);

                        if ($file['size'] > $maxBytes) { $error = 'Image too large (max 5MB).'; }
                        elseif (!in_array($mimeType, $allowed, true)) { $error = 'Invalid image type. Use JPG, PNG, or WebP.'; }
                        else {
                            $uploadDir = __DIR__ . '/../storage/blog/';
                            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
                            $ext      = pathinfo($file['name'], PATHINFO_EXTENSION);
                            $filename = time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                            if (move_uploaded_file($file['tmp_name'], $uploadDir . $filename)) {
                                $featuredImage = '/storage/blog/' . $filename;
                            } else { $error = 'Failed to save image.'; }
                        }
                    }

                    if (!$error) {
                        $publishedAt = ($status === 'published') ? date('Y-m-d H:i:s') : null;

                        $stmt = $conn->prepare(
                            "INSERT INTO blog_posts (category_id, title, slug, excerpt, content, featured_image, featured_image_alt, author_name, meta_title, meta_description, meta_keywords, meta_image, canonical_url, robots, is_featured, status, published_at, display_order, is_active)
                             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
                        );
                        $stmt->bind_param(
                            'isssssssssssssissii',
                            $categoryId, $title, $slug, $excerpt, $content,
                            $featuredImage, $featuredImgAlt, $authorName,
                            $metaTitle, $metaDescription, $metaKeywords, $metaImage, $canonicalUrl, $robots,
                            $isFeatured, $status, $publishedAt, $displayOrder, $isActive
                        );
                        $success = $stmt->execute() ? 'Blog post created.' : '';
                        if (!$success) { $error = 'Create failed: ' . $stmt->error; }
                        $stmt->close();
                    }
                }

            } else {
                if ($id <= 0) { $error = 'Invalid ID.'; }
                else {
                    // Check slug uniqueness (exclude current)
                    $check = $conn->prepare("SELECT id FROM blog_posts WHERE slug = ? AND id != ?");
                    $check->bind_param('si', $slug, $id);
                    $check->execute();
                    $check->store_result();
                    if ($check->num_rows > 0) { $error = 'Another post with this slug already exists.'; }
                    $check->close();

                    if (!$error) {
                        // Get existing post data
                        $existing = $conn->prepare("SELECT featured_image, status, published_at FROM blog_posts WHERE id = ?");
                        $existing->bind_param('i', $id);
                        $existing->execute();
                        $existingData = $existing->get_result()->fetch_assoc();
                        $existing->close();

                        $conn->begin_transaction();

                        try {
                            // Handle image replacement
                            if (isset($_FILES['featured_image']) && $_FILES['featured_image']['error'] === UPLOAD_ERR_OK) {
                                $file     = $_FILES['featured_image'];
                                $maxBytes = 5 * 1024 * 1024;
                                $allowed  = ['image/jpeg', 'image/png', 'image/webp'];
                                $finfo    = finfo_open(FILEINFO_MIME_TYPE);
                                $mimeType = finfo_file($finfo, $file['tmp_name']);
                                finfo_close($finfo);

                                if ($file['size'] > $maxBytes) { throw new RuntimeException('Image too large.'); }
                                if (!in_array($mimeType, $allowed, true)) { throw new RuntimeException('Invalid image type.'); }

                                $uploadDir = __DIR__ . '/../storage/blog/';
                                if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
                                $ext      = pathinfo($file['name'], PATHINFO_EXTENSION);
                                $filename = time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                                if (!move_uploaded_file($file['tmp_name'], $uploadDir . $filename)) {
                                    throw new RuntimeException('Failed to save image.');
                                }
                                $newImageUrl = '/storage/blog/' . $filename;

                                $imgStmt = $conn->prepare("UPDATE blog_posts SET featured_image = ?, featured_image_alt = ? WHERE id = ?");
                                $imgStmt->bind_param('ssi', $newImageUrl, $featuredImgAlt, $id);
                                if (!$imgStmt->execute()) { throw new RuntimeException('Image update failed.'); }
                                $imgStmt->close();

                                // Delete old image
                                if ($existingData['featured_image']) {
                                    $oldPath = __DIR__ . '/../storage/blog/' . basename($existingData['featured_image']);
                                    if (file_exists($oldPath)) @unlink($oldPath);
                                }
                            } else {
                                $altStmt = $conn->prepare("UPDATE blog_posts SET featured_image_alt = ? WHERE id = ?");
                                $altStmt->bind_param('si', $featuredImgAlt, $id);
                                $altStmt->execute();
                                $altStmt->close();
                            }

                            // Auto-set published_at
                            $publishedAt = $existingData['published_at'];
                            if ($status === 'published' && $existingData['status'] !== 'published') {
                                $publishedAt = date('Y-m-d H:i:s');
                            }

                            $stmt = $conn->prepare(
                                "UPDATE blog_posts SET category_id=?, title=?, slug=?, excerpt=?, content=?,
                                        author_name=?, meta_title=?, meta_description=?, meta_keywords=?, meta_image=?,
                                        canonical_url=?, robots=?, is_featured=?, status=?, published_at=?,
                                        display_order=?, is_active=?
                                 WHERE id=?"
                            );
                            $stmt->bind_param(
                                'isssssssssssissiii',
                                $categoryId, $title, $slug, $excerpt, $content,
                                $authorName, $metaTitle, $metaDescription, $metaKeywords, $metaImage,
                                $canonicalUrl, $robots, $isFeatured, $status, $publishedAt,
                                $displayOrder, $isActive, $id
                            );
                            if (!$stmt->execute()) { throw new RuntimeException('Update failed: ' . $stmt->error); }
                            $stmt->close();

                            $conn->commit();
                            $success = 'Blog post updated.';

                        } catch (Exception $e) {
                            $conn->rollback();
                            $error = $e->getMessage();
                        }
                    }
                }
            }
        }
    }
}

// ---------- Optional filter by category ----------
$filterCat = (int) ($_GET['category_id'] ?? 0);

// ---------- Fetch posts ----------
$posts = [];
try {
    $postSql = "SELECT bp.id, bp.category_id, bp.title, bp.slug, bp.excerpt,
                       bp.featured_image, bp.featured_image_alt, bp.author_name,
                       bp.meta_title, bp.meta_description, bp.meta_keywords, bp.meta_image, bp.canonical_url, bp.robots,
                       bp.is_featured, bp.status, bp.published_at,
                       bp.display_order, bp.is_active, bp.created_at, bp.content,
                       bc.name AS category_name
                FROM blog_posts bp
                LEFT JOIN blog_categories bc ON bp.category_id = bc.id";
    if ($filterCat > 0) $postSql .= " WHERE bp.category_id = " . $filterCat;
    $postSql .= " ORDER BY bp.created_at DESC";

    $res = db_query($conn, $postSql);
    while ($r = $res->fetch_assoc()) {
        $r['id']            = (int)  $r['id'];
        $r['category_id']   = (int)  $r['category_id'];
        $r['is_featured']   = (bool) $r['is_featured'];
        $r['display_order'] = (int)  $r['display_order'];
        $r['is_active']     = (bool) $r['is_active'];
        $posts[] = $r;
    }
} catch (RuntimeException $e) {
    error_log('[blog-posts] fetch: ' . $e->getMessage());
    $error = 'Could not load blog posts. Please try again.';
}

$statusOptions = ['draft' => '📝 Draft', 'published' => '🟢 Published', 'archived' => '📦 Archived'];
$robotsOptions = ['index,follow' => 'Index, Follow', 'noindex,follow' => 'NoIndex, Follow', 'noindex,nofollow' => 'NoIndex, NoFollow'];
?>

<style>
/* Blog-specific styles */
.img-preview {
    width: 56px; height: 56px; object-fit: cover;
    border-radius: 6px; border: 1px solid #e0e0e0;
}
.img-placeholder {
    width: 56px; height: 56px; border-radius: 6px;
    background: #f3f4f6; border: 1px dashed #d1d5db;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.2rem; color: #9ca3af;
}
.status-draft     { background: #fef3c7; color: #92400e; }
.status-published { background: #e8f5e9; color: #2e7d32; }
.status-archived  { background: #f3f4f6; color: #6b7280; }
.excerpt-text {
    font-size: .82rem; color: #6b7280; max-width: 280px;
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis; display: block;
}
.char-counter {
    font-size: .75rem; color: #9ca3af; text-align: right; margin-top: 2px;
}
.char-counter.warn { color: #f57c00; }
.char-counter.over { color: #c62828; }
.seo-section {
    border: 1px solid #e5e7eb; border-radius: 8px; margin-top: 16px;
    overflow: hidden;
}
.seo-toggle {
    display: flex; align-items: center; justify-content: space-between;
    padding: 12px 16px; background: #f9fafb; cursor: pointer;
    font-size: .88rem; font-weight: 600; color: #374151;
    border: none; width: 100%; text-align: left; font-family: inherit;
}
.seo-toggle:hover { background: #f3f4f6; }
.seo-body { padding: 16px; display: none; }
.seo-body.open { display: block; }
.upload-zone {
    border: 2px dashed #d1d5db; border-radius: 8px; padding: 20px;
    text-align: center; cursor: pointer; transition: all .2s;
    background: #fafafa; position: relative;
}
.upload-zone:hover, .upload-zone.dragover {
    border-color: #2e7d32; background: #f0fdf4;
}
.upload-zone input[type=file] {
    position: absolute; inset: 0; opacity: 0; cursor: pointer;
}
.upload-zone .icon { font-size: 2rem; margin-bottom: 6px; }
.upload-zone .text { font-size: .84rem; color: #6b7280; }
.upload-preview { margin-top: 10px; position: relative; display: inline-block; }
.upload-preview img {
    max-width: 200px; max-height: 120px; border-radius: 8px;
    border: 1px solid #e0e0e0; object-fit: cover;
}
.upload-preview .remove-btn {
    position: absolute; top: -8px; right: -8px;
    background: #c62828; color: #fff; border: none;
    width: 22px; height: 22px; border-radius: 50%;
    cursor: pointer; font-size: .7rem; display: flex;
    align-items: center; justify-content: center;
}
</style>

<?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>
<?php if ($error):   ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

<?php if (empty($blogCategories)): ?>
    <div class="alert alert-error">No blog categories found. <a href="blog-categories.php">Create a category first.</a></div>
<?php else: ?>

<div style="margin-bottom:16px;">
    <button class="btn btn-primary" onclick="openModal()">+ New Blog Post</button>
</div>

<div class="card">
    <div class="card-header">
        <h2>Blog Posts</h2>
        <form method="GET" style="display:flex;gap:8px;align-items:center;">
            <select name="category_id" class="form-control" style="width:auto;">
                <option value="">All Categories</option>
                <?php foreach ($blogCategories as $c): ?>
                    <option value="<?= $c['id'] ?>" <?= $c['id'] === $filterCat ? 'selected' : '' ?>><?= htmlspecialchars($c['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn btn-secondary btn-sm">Filter</button>
        </form>
    </div>
    <div class="tbl-wrap">
        <table>
            <thead><tr><th style="width:70px;">Image</th><th>Title</th><th>Category</th><th>Author</th><th>Status</th><th>Published</th><th style="white-space:nowrap; width:130px;">Actions</th></tr></thead>
            <tbody>
            <?php foreach ($posts as $p): ?>
                <tr>
                    <td>
                        <?php if ($p['featured_image']): ?>
                            <img src="<?= htmlspecialchars(asset_url($p['featured_image'])) ?>" alt="<?= htmlspecialchars($p['featured_image_alt'] ?? '') ?>" class="img-preview">
                        <?php else: ?>
                            <div class="img-placeholder">📝</div>
                        <?php endif; ?>
                    </td>
                    <td>
                        <strong><?= htmlspecialchars($p['title']) ?></strong>
                        <?php if ($p['is_featured']): ?>&nbsp;<span class="badge badge-gold">★ Featured</span><?php endif; ?>
                        <?php if ($p['excerpt']): ?><br><span class="excerpt-text"><?= htmlspecialchars($p['excerpt']) ?></span><?php endif; ?>
                    </td>
                    <td><small><?= htmlspecialchars($p['category_name'] ?? '—') ?></small></td>
                    <td><small><?= htmlspecialchars($p['author_name'] ?? '—') ?></small></td>
                    <td><span class="badge status-<?= $p['status'] ?>"><?= ucfirst($p['status']) ?></span></td>
                    <td><small><?= $p['published_at'] ? date('M j, Y', strtotime($p['published_at'])) : '—' ?></small></td>
                    <td style="white-space:nowrap;">
                        <button class="btn btn-secondary btn-sm" onclick='openModal(<?= htmlspecialchars(json_encode($p), ENT_QUOTES) ?>)'>Edit</button>
                        <form method="POST" style="display:inline;" onsubmit="return confirmDelete('Delete post &quot;<?= htmlspecialchars(addslashes($p['title'])) ?>&quot;?')">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= $p['id'] ?>">
                            <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($posts)): ?>
                <tr><td colspan="7" style="color:#6b7280;text-align:center;">No blog posts yet. Click "+ New Blog Post" to create one.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Mobile cards -->
<div class="m-cards">
    <?php foreach ($posts as $p): ?>
    <div class="m-card" style="display:flex;gap:12px;">
        <?php if ($p['featured_image']): ?>
            <img src="<?= htmlspecialchars(asset_url($p['featured_image'])) ?>" alt="" style="width:60px;height:60px;object-fit:cover;border-radius:6px;flex-shrink:0;">
        <?php else: ?>
            <div class="img-placeholder" style="flex-shrink:0;">📝</div>
        <?php endif; ?>
        <div style="flex:1;">
            <div class="m-title">
                <?= htmlspecialchars($p['title']) ?>
                <?php if ($p['is_featured']): ?><span class="badge badge-gold">★</span><?php endif; ?>
            </div>
            <div class="m-row"><span>Category</span><span><?= htmlspecialchars($p['category_name'] ?? '—') ?></span></div>
            <div class="m-row"><span>Status</span><span><span class="badge status-<?= $p['status'] ?>"><?= ucfirst($p['status']) ?></span></span></div>
            <div class="m-row"><span>Published</span><span><?= $p['published_at'] ? date('M j, Y', strtotime($p['published_at'])) : '—' ?></span></div>
            <div class="m-actions">
                <button class="btn btn-secondary btn-sm" onclick='openModal(<?= htmlspecialchars(json_encode($p), ENT_QUOTES) ?>)'>Edit</button>
                <form method="POST" onsubmit="return confirmDelete()">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="<?= $p['id'] ?>">
                    <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                </form>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Add/Edit Modal -->
<div class="modal-backdrop" id="postModal">
    <div class="modal" style="width:min(680px, 96vw);">
        <h3 id="modalTitle">New Blog Post</h3>
        <form method="POST" id="postForm" enctype="multipart/form-data">
            <input type="hidden" name="action" id="fAction" value="create">
            <input type="hidden" name="id" id="fId" value="">

            <div class="form-group">
                <label for="fCat">Category <span style="color:red">*</span></label>
                <select class="form-control" id="fCat" name="category_id" required>
                    <option value="">— Select category —</option>
                    <?php foreach ($blogCategories as $c): ?>
                        <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="fTitle">Title <span style="color:red">*</span></label>
                <input type="text" class="form-control" id="fTitle" name="title" required placeholder="Your blog post title">
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="fSlug">Slug</label>
                    <input type="text" class="form-control" id="fSlug" name="slug" placeholder="Auto-generated from title">
                    <small style="color:#6b7280;font-size:.78rem;">URL path: /blog/<span id="slugPreview">your-post-slug</span></small>
                </div>
                <div class="form-group">
                    <label for="fAuthor">Author Name</label>
                    <input type="text" class="form-control" id="fAuthor" name="author_name" placeholder="Bardiya Eco Team">
                </div>
            </div>

            <!-- Featured Image -->
            <div class="form-group">
                <label>Featured Image</label>
                <div class="upload-zone" id="uploadZone">
                    <input type="file" name="featured_image" id="fImage" accept="image/jpeg,image/png,image/webp">
                    <div class="icon">🖼️</div>
                    <div class="text">Click or drag & drop an image here<br><small>JPG, PNG, WebP — max 5MB</small></div>
                </div>
                <div class="upload-preview" id="imagePreview" style="display:none;">
                    <img id="previewImg" src="" alt="Preview">
                    <button type="button" class="remove-btn" onclick="removeImage()" title="Remove image">✕</button>
                </div>
                <div class="form-group" style="margin-top:8px;">
                    <label for="fImgAlt">Image Alt Text</label>
                    <input type="text" class="form-control" id="fImgAlt" name="featured_image_alt" placeholder="Describe the image for accessibility & SEO">
                </div>
            </div>

            <div class="form-group">
                <label for="fExcerpt">Excerpt</label>
                <textarea class="form-control" id="fExcerpt" name="excerpt" rows="2" maxlength="500" placeholder="A short summary for listing pages and search results..."></textarea>
                <div class="char-counter" id="excerptCounter">0 / 500</div>
            </div>

            <div class="form-group">
                <label for="fContent">Content (HTML)</label>
                <textarea class="form-control" id="fContent" name="content" rows="10" placeholder="Write your blog post content here. HTML is supported."></textarea>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="fStatus">Status</label>
                    <select class="form-control" id="fStatus" name="status">
                        <?php foreach ($statusOptions as $val => $label): ?>
                            <option value="<?= $val ?>"><?= $label ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="fOrder">Display Order</label>
                    <input type="number" class="form-control" id="fOrder" name="display_order" value="0" min="0">
                </div>
            </div>

            <div style="display:flex;gap:20px;flex-wrap:wrap;margin-bottom:12px;">
                <div class="check-group">
                    <input type="checkbox" id="fFeatured" name="is_featured" value="1">
                    <label for="fFeatured">★ Featured Post</label>
                </div>
                <div class="check-group">
                    <input type="checkbox" id="fActive" name="is_active" value="1" checked>
                    <label for="fActive">Active</label>
                </div>
            </div>

            <!-- SEO Section (collapsible) -->
            <div class="seo-section">
                <button type="button" class="seo-toggle" onclick="toggleSeo()">
                    <span>🔎 SEO Settings</span>
                    <span id="seoArrow">▼</span>
                </button>
                <div class="seo-body" id="seoBody">
                    <div class="form-group">
                        <label for="fMetaTitle">Meta Title</label>
                        <input type="text" class="form-control" id="fMetaTitle" name="meta_title" placeholder="Custom title for search engines (max 60 chars)">
                        <div class="char-counter" id="metaTitleCounter">0 / 60</div>
                    </div>
                    <div class="form-group">
                        <label for="fMetaDesc">Meta Description</label>
                        <textarea class="form-control" id="fMetaDesc" name="meta_description" rows="2" placeholder="Compelling description for search results (max 160 chars)"></textarea>
                        <div class="char-counter" id="metaDescCounter">0 / 160</div>
                    </div>
                    <div class="form-group">
                        <label for="fMetaKeywords">Meta Keywords</label>
                        <textarea class="form-control" id="fMetaKeywords" name="meta_keywords" rows="2" placeholder="Comma-separated keywords"></textarea>
                    </div>
                    <div class="form-group">
                        <label for="fMetaImage">OG Image URL</label>
                        <input type="text" class="form-control" id="fMetaImage" name="meta_image" placeholder="/storage/blog/og-image.jpg">
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="fCanonical">Canonical URL</label>
                            <input type="text" class="form-control" id="fCanonical" name="canonical_url" placeholder="https://...">
                        </div>
                        <div class="form-group">
                            <label for="fRobots">Robots Directive</label>
                            <select class="form-control" id="fRobots" name="robots">
                                <?php foreach ($robotsOptions as $val => $label): ?>
                                    <option value="<?= htmlspecialchars($val) ?>"><?= htmlspecialchars($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                <button type="submit" class="btn btn-primary" id="submitBtn">Publish Post</button>
            </div>
        </form>
    </div>
</div>

<script>
// ── Auto-generate slug ──────────────────────────────────────
document.getElementById('fTitle').addEventListener('input', function() {
    const slugField = document.getElementById('fSlug');
    if (document.getElementById('fAction').value === 'create' && !slugField.dataset.manual) {
        const slug = this.value.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '');
        slugField.value = slug;
        document.getElementById('slugPreview').textContent = slug || 'your-post-slug';
    }
});
document.getElementById('fSlug').addEventListener('input', function() {
    this.dataset.manual = this.value ? 'true' : '';
    document.getElementById('slugPreview').textContent = this.value || 'your-post-slug';
});

// ── Character counters ──────────────────────────────────────
function setupCounter(inputId, counterId, max) {
    const input = document.getElementById(inputId);
    const counter = document.getElementById(counterId);
    input.addEventListener('input', function() {
        const len = this.value.length;
        counter.textContent = len + ' / ' + max;
        counter.className = 'char-counter' + (len > max ? ' over' : (len > max * 0.9 ? ' warn' : ''));
    });
}
setupCounter('fExcerpt', 'excerptCounter', 500);
setupCounter('fMetaTitle', 'metaTitleCounter', 60);
setupCounter('fMetaDesc', 'metaDescCounter', 160);

// ── Image upload preview ────────────────────────────────────
const imageInput = document.getElementById('fImage');
const uploadZone = document.getElementById('uploadZone');
const imagePreview = document.getElementById('imagePreview');
const previewImg = document.getElementById('previewImg');

imageInput.addEventListener('change', function() {
    if (this.files && this.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            previewImg.src = e.target.result;
            imagePreview.style.display = 'inline-block';
            uploadZone.style.display = 'none';
        };
        reader.readAsDataURL(this.files[0]);
    }
});

['dragenter', 'dragover'].forEach(ev => {
    uploadZone.addEventListener(ev, e => { e.preventDefault(); uploadZone.classList.add('dragover'); });
});
['dragleave', 'drop'].forEach(ev => {
    uploadZone.addEventListener(ev, e => { e.preventDefault(); uploadZone.classList.remove('dragover'); });
});

function removeImage() {
    imageInput.value = '';
    imagePreview.style.display = 'none';
    uploadZone.style.display = '';
}

// ── SEO section toggle ──────────────────────────────────────
function toggleSeo() {
    const body = document.getElementById('seoBody');
    const arrow = document.getElementById('seoArrow');
    body.classList.toggle('open');
    arrow.textContent = body.classList.contains('open') ? '▲' : '▼';
}

// ── Modal open/close ────────────────────────────────────────
function openModal(post) {
    document.getElementById('postModal').classList.add('active');
    const slugField = document.getElementById('fSlug');
    slugField.dataset.manual = '';

    // Reset image preview
    removeImage();

    if (post) {
        document.getElementById('modalTitle').textContent = 'Edit Blog Post';
        document.getElementById('fAction').value   = 'update';
        document.getElementById('fId').value        = post.id;
        document.getElementById('fCat').value       = post.category_id;
        document.getElementById('fTitle').value     = post.title || '';
        slugField.value = post.slug || '';
        slugField.dataset.manual = 'true';
        document.getElementById('slugPreview').textContent = post.slug || 'your-post-slug';
        document.getElementById('fAuthor').value    = post.author_name || '';
        document.getElementById('fExcerpt').value   = post.excerpt || '';
        document.getElementById('fContent').value   = post.content || '';
        document.getElementById('fStatus').value    = post.status || 'draft';
        document.getElementById('fOrder').value     = post.display_order || 0;
        document.getElementById('fFeatured').checked = !!post.is_featured;
        document.getElementById('fActive').checked   = !!post.is_active;
        document.getElementById('fImgAlt').value     = post.featured_image_alt || '';

        // SEO fields
        document.getElementById('fMetaTitle').value = post.meta_title || '';
        document.getElementById('fMetaDesc').value  = post.meta_description || '';
        document.getElementById('fMetaKeywords').value = post.meta_keywords || '';
        document.getElementById('fMetaImage').value = post.meta_image || '';
        document.getElementById('fCanonical').value = post.canonical_url || '';
        document.getElementById('fRobots').value    = post.robots || 'index,follow';

        // Show existing featured image
        if (post.featured_image) {
            previewImg.src = '<?= rtrim($baseUrl, "/") ?>' + post.featured_image;
            imagePreview.style.display = 'inline-block';
            uploadZone.style.display = 'none';
        }

        document.getElementById('submitBtn').textContent = 'Update Post';

        // Trigger counters
        ['fExcerpt', 'fMetaTitle', 'fMetaDesc'].forEach(id => {
            document.getElementById(id).dispatchEvent(new Event('input'));
        });
    } else {
        document.getElementById('modalTitle').textContent = 'New Blog Post';
        document.getElementById('fAction').value = 'create';
        document.getElementById('postForm').reset();
        document.getElementById('fActive').checked = true;
        document.getElementById('slugPreview').textContent = 'your-post-slug';
        document.getElementById('submitBtn').textContent = 'Publish Post';

        // Reset counters
        ['excerptCounter', 'metaTitleCounter', 'metaDescCounter'].forEach(id => {
            const el = document.getElementById(id);
            el.textContent = '0 / ' + el.textContent.split('/')[1].trim();
            el.className = 'char-counter';
        });
    }

    // Close SEO section by default
    document.getElementById('seoBody').classList.remove('open');
    document.getElementById('seoArrow').textContent = '▼';
}

function closeModal() {
    document.getElementById('postModal').classList.remove('active');
}

document.getElementById('postModal').addEventListener('click', function(e) {
    if (e.target === this) closeModal();
});
</script>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
