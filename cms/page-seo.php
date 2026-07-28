<?php
/**
 * CMS — Page-by-Page SEO Management
 * Similar to Global SEO settings, allows editing per-page SEO tags for every static/dynamic page.
 */

$pageTitle = 'Page SEO Management';
require_once __DIR__ . '/includes/header.php';

$success = '';
$error   = '';

// ---------- Handle POST ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'update';
    
    $slug         = strtolower(trim($_POST['slug']         ?? ''));
    $title        = trim($_POST['title']        ?? ucfirst(str_replace('-', ' ', $slug)));
    $metaTitle    = trim($_POST['meta_title']   ?? '');
    $metaDesc     = trim($_POST['meta_description'] ?? '');
    $metaKeywords = trim($_POST['meta_keywords'] ?? '');
    $metaImage    = trim($_POST['meta_image']   ?? '');
    $canonicalUrl = trim($_POST['canonical_url']?? '');
    $robots       = trim($_POST['robots']       ?? 'index,follow');
    $languageCode = trim($_POST['language_code']?? 'en');
    $hreflangUrl  = trim($_POST['hreflang_url'] ?? '');
    $status       = trim($_POST['status']       ?? 'published');

    if (empty($slug)) {
        $error = 'Page slug is required.';
    } else {
        // Upsert into pages table
        $stmt = $conn->prepare(
            "INSERT INTO pages (title, slug, content, meta_title, meta_description, meta_keywords, meta_image, canonical_url, robots, language_code, hreflang_url, status)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE 
                title = VALUES(title),
                meta_title = VALUES(meta_title),
                meta_description = VALUES(meta_description),
                meta_keywords = VALUES(meta_keywords),
                meta_image = VALUES(meta_image),
                canonical_url = VALUES(canonical_url),
                robots = VALUES(robots),
                language_code = VALUES(language_code),
                hreflang_url = VALUES(hreflang_url),
                status = VALUES(status)"
        );
        $dummyContent = '<h2>' . htmlspecialchars($title) . '</h2>';
        $stmt->bind_param('ssssssssssss', $title, $slug, $dummyContent, $metaTitle, $metaDesc, $metaKeywords, $metaImage, $canonicalUrl, $robots, $languageCode, $hreflangUrl, $status);
        if ($stmt->execute()) {
            $success = 'SEO settings updated for page: ' . htmlspecialchars($slug);
        } else {
            $error = 'Failed to save Page SEO: ' . $stmt->error;
        }
        $stmt->close();
    }
}

// ---------- Fetch all pages with SEO info ----------
$pages = [];
$res = $conn->query("SELECT id, title, slug, meta_title, meta_description, meta_keywords, meta_image, canonical_url, robots, language_code, hreflang_url, status, updated_at FROM pages ORDER BY title ASC");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $pages[] = $row;
    }
}

// Pre-defined default key pages if not already in DB
$defaultPages = [
    'home'       => 'Home Page',
    'about-us'   => 'About Us',
    'contact'    => 'Contact Page',
    'contact-us' => 'Contact Us (Alias)',
    'activities' => 'Activities & Wildlife Experiences',
    'packages'   => 'Packages Listing',
    'gallery'    => 'Gallery Page',
    'blog'       => 'Blog Index'
];

$existingSlugs = array_column($pages, 'slug');
$selectedSlug = $_GET['slug'] ?? ($pages[0]['slug'] ?? 'home');

// Find active selected page
$activePage = null;
foreach ($pages as $p) {
    if ($p['slug'] === $selectedSlug) {
        $activePage = $p;
        break;
    }
}

if (!$activePage) {
    $activePage = [
        'id'               => 0,
        'title'            => $defaultPages[$selectedSlug] ?? ucfirst(str_replace('-', ' ', $selectedSlug)),
        'slug'             => $selectedSlug,
        'meta_title'       => '',
        'meta_description' => '',
        'meta_keywords'    => '',
        'meta_image'       => '/logo.png',
        'canonical_url'    => 'https://www.bardiaecofriendlyhomestay.com/' . ltrim($selectedSlug, '/'),
        'robots'           => 'index,follow',
        'language_code'    => 'en',
        'hreflang_url'     => '',
        'status'           => 'published'
    ];
}
?>

<?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>
<?php if ($error):   ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

<!-- Page Selection Bar -->
<div class="card" style="margin-bottom: 20px;">
    <div class="card-header">
        <h2>Select Page to Edit SEO</h2>
    </div>
    <div class="card-body">
        <div style="display:flex; gap:12px; align-items:center; flex-wrap:wrap;">
            <label for="pageSelect" style="font-weight:600;">Page:</label>
            <select id="pageSelect" class="form-control" style="max-width:320px;" onchange="location.href='page-seo.php?slug=' + this.value;">
                <optgroup label="Standard Site Pages">
                    <?php foreach ($defaultPages as $slugKey => $nameLabel): ?>
                        <option value="<?= htmlspecialchars($slugKey) ?>" <?= $selectedSlug === $slugKey ? 'selected' : '' ?>>
                            <?= htmlspecialchars($nameLabel) ?> (<?= htmlspecialchars($slugKey) ?>)
                        </option>
                    <?php endforeach; ?>
                </optgroup>
                <optgroup label="Custom / DB Pages">
                    <?php foreach ($pages as $p): ?>
                        <?php if (!array_key_exists($p['slug'], $defaultPages)): ?>
                            <option value="<?= htmlspecialchars($p['slug']) ?>" <?= $selectedSlug === $p['slug'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($p['title']) ?> (<?= htmlspecialchars($p['slug']) ?>)
                            </option>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </optgroup>
            </select>
        </div>
    </div>
</div>

<!-- Edit SEO Form -->
<form method="POST" class="card">
    <div class="card-header">
        <h2>SEO Settings for "<?= htmlspecialchars($activePage['title']) ?>" (<code><?= htmlspecialchars($activePage['slug']) ?></code>)</h2>
    </div>
    <div class="card-body">
        <input type="hidden" name="slug" value="<?= htmlspecialchars($activePage['slug']) ?>">
        
        <div class="form-row">
            <div class="form-group">
                <label for="pageTitleInput">Page Title / Headline</label>
                <input id="pageTitleInput" class="form-control" name="title" value="<?= htmlspecialchars($activePage['title']) ?>" required>
            </div>
            <div class="form-group">
                <label for="statusSelect">Page Status</label>
                <select id="statusSelect" class="form-control" name="status">
                    <option value="published" <?= $activePage['status'] === 'published' ? 'selected' : '' ?>>Published</option>
                    <option value="draft" <?= $activePage['status'] === 'draft' ? 'selected' : '' ?>>Draft</option>
                    <option value="archived" <?= $activePage['status'] === 'archived' ? 'selected' : '' ?>>Archived</option>
                </select>
            </div>
        </div>

        <div class="form-group">
            <label for="metaTitle">Meta Title (SEO Title)</label>
            <input id="metaTitle" class="form-control" name="meta_title" value="<?= htmlspecialchars($activePage['meta_title']) ?>" placeholder="e.g. Contact Bardia Eco-Friendly Homestay | Safari & Stay">
            <small style="color:var(--muted); font-size:0.8rem;">Recommended length: 50–60 characters</small>
        </div>

        <div class="form-group">
            <label for="metaDescription">Meta Description</label>
            <textarea id="metaDescription" class="form-control" name="meta_description" rows="3" placeholder="Page meta description for search engine snippet..."><?= htmlspecialchars($activePage['meta_description']) ?></textarea>
            <small style="color:var(--muted); font-size:0.8rem;">Recommended length: 140–160 characters</small>
        </div>

        <div class="form-group">
            <label for="metaKeywords">Meta Keywords</label>
            <textarea id="metaKeywords" class="form-control" name="meta_keywords" rows="2" placeholder="Comma-separated keywords (e.g. Bardia, safari, homestay, contact)"><?= htmlspecialchars($activePage['meta_keywords'] ?? '') ?></textarea>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="metaImage">Social / OpenGraph Image URL</label>
                <input id="metaImage" class="form-control" name="meta_image" value="<?= htmlspecialchars($activePage['meta_image'] ?? '/logo.png') ?>" placeholder="/logo.png or full image URL">
            </div>
            <div class="form-group">
                <label for="canonicalUrl">Canonical URL</label>
                <input id="canonicalUrl" class="form-control" name="canonical_url" value="<?= htmlspecialchars($activePage['canonical_url'] ?? '') ?>" placeholder="https://www.bardiaecofriendlyhomestay.com/page">
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="robotsSelect">Robots Meta Directive</label>
                <select id="robotsSelect" class="form-control" name="robots">
                    <option value="index,follow" <?= ($activePage['robots'] ?? 'index,follow') === 'index,follow' ? 'selected' : '' ?>>index, follow (Default)</option>
                    <option value="noindex,follow" <?= ($activePage['robots'] ?? '') === 'noindex,follow' ? 'selected' : '' ?>>noindex, follow</option>
                    <option value="index,nofollow" <?= ($activePage['robots'] ?? '') === 'index,nofollow' ? 'selected' : '' ?>>index, nofollow</option>
                    <option value="noindex,nofollow" <?= ($activePage['robots'] ?? '') === 'noindex,nofollow' ? 'selected' : '' ?>>noindex, nofollow</option>
                </select>
            </div>
            <div class="form-group">
                <label for="langCode">Language Code</label>
                <input id="langCode" class="form-control" name="language_code" value="<?= htmlspecialchars($activePage['language_code'] ?? 'en') ?>" placeholder="en">
            </div>
            <div class="form-group">
                <label for="hreflangUrl">Alternate Hreflang URL</label>
                <input id="hreflangUrl" class="form-control" name="hreflang_url" value="<?= htmlspecialchars($activePage['hreflang_url'] ?? '') ?>" placeholder="https://www.bardiaecofriendlyhomestay.com/ne/contact">
            </div>
        </div>

        <div style="margin-top: 16px;">
            <button class="btn btn-primary" type="submit">💾 Save Page SEO Settings</button>
        </div>
    </div>
</form>

<!-- Page SEO Overview Table -->
<div class="card" style="margin-top: 24px;">
    <div class="card-header">
        <h2>All Pages SEO Status Overview</h2>
    </div>
    <div class="tbl-wrap">
        <table>
            <thead>
                <tr>
                    <th>Page Title</th>
                    <th>Slug</th>
                    <th>Meta Title</th>
                    <th>Robots</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($pages as $p): ?>
                    <tr style="<?= $p['slug'] === $selectedSlug ? 'background:#f0f9ff;' : '' ?>">
                        <td><strong><?= htmlspecialchars($p['title']) ?></strong></td>
                        <td><code style="background:#f0f0f0;padding:2px 6px;border-radius:4px;"><?= htmlspecialchars($p['slug']) ?></code></td>
                        <td><?= htmlspecialchars($p['meta_title'] ?: '(Using Global Default)') ?></td>
                        <td><span class="badge badge-green"><?= htmlspecialchars($p['robots'] ?? 'index,follow') ?></span></td>
                        <td>
                            <?php if ($p['status'] === 'published'): ?>
                                <span class="badge badge-green">Published</span>
                            <?php else: ?>
                                <span class="badge badge-gold"><?= htmlspecialchars($p['status']) ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="page-seo.php?slug=<?= urlencode($p['slug']) ?>" class="btn btn-secondary btn-sm">Edit SEO</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
