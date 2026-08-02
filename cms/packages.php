<?php
/**
 * CMS — Packages Management (with Category support)
 */

$pageTitle = 'Packages';
require_once __DIR__ . '/includes/header.php';

$allowedCurrencies = ['₹', '$', '€', '£'];
$success = '';
$error   = '';

// ---------- Fetch categories for dropdown ----------
$pkgCategories = [];
try {
    $catRes = db_query($conn, "SELECT id, name FROM package_categories WHERE is_active = 1 ORDER BY display_order ASC, id ASC");
    while ($c = $catRes->fetch_assoc()) { $c['id'] = (int) $c['id']; $pkgCategories[] = $c; }
} catch (RuntimeException $e) {
    error_log('[packages] categories fetch: ' . $e->getMessage());
    $error = 'Could not load categories. Please try again.';
}

// ---------- Handle POST ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);
        $stmt = $conn->prepare("DELETE FROM packages WHERE id = ?");
        $stmt->bind_param('i', $id);
        $success = $stmt->execute() ? 'Package deleted.' : '';
        if (!$success) $error = 'Delete failed: ' . $stmt->error;
        $stmt->close();

    } elseif (in_array($action, ['create', 'update'], true)) {
        $id           = (int)    ($_POST['id']           ?? 0);
        $categoryId   = (int)    ($_POST['category_id']  ?? 0);
        $name         = trim($_POST['name']          ?? '');
        $icon         = trim($_POST['icon']          ?? '');
        $duration     = trim($_POST['duration']      ?? '');
        $price        = (float)  ($_POST['price']        ?? 0);
        $currency     =           $_POST['currency']     ?? '₹';
        $priceNote    = trim($_POST['price_note']    ?? '');
        $description  = trim($_POST['description']   ?? '');
        $slug         = trim($_POST['slug']          ?? '');
        if (empty($slug)) { $slug = strtolower(preg_replace('/[^a-z0-9-]/', '-', $name)); }
        $metaTitle    = trim($_POST['meta_title']    ?? '');
        $metaDesc     = trim($_POST['meta_description'] ?? '');
        $metaKeywords = trim($_POST['meta_keywords'] ?? '');
        $metaImage    = trim($_POST['meta_image']    ?? '');
        $canonicalUrl = trim($_POST['canonical_url'] ?? '');
        $robots       = trim($_POST['robots']        ?? 'index,follow');
        $isFeatured   = isset($_POST['is_featured'])  ? 1 : 0;
        $displayOrder = (int)    ($_POST['display_order'] ?? 0);
        $isActive     = isset($_POST['is_active'])    ? 1 : 0;

        if ($categoryId <= 0)                               { $error = 'Please select a category.'; }
        elseif (empty($name))                               { $error = 'Package name is required.'; }
        elseif ($price < 0)                                 { $error = 'Price must be 0 or greater.'; }
        elseif (!in_array($currency, $allowedCurrencies, true)) { $error = 'Invalid currency.'; }
        else {
            if ($action === 'create') {
                $stmt = $conn->prepare(
                    "INSERT INTO packages (category_id, icon, name, slug, duration, price, currency, price_note, description, meta_title, meta_description, meta_keywords, meta_image, canonical_url, robots, is_featured, display_order, is_active)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
                );
                $stmt->bind_param('issssdsssssssssiii', $categoryId, $icon, $name, $slug, $duration, $price, $currency, $priceNote, $description, $metaTitle, $metaDesc, $metaKeywords, $metaImage, $canonicalUrl, $robots, $isFeatured, $displayOrder, $isActive);
                $success = $stmt->execute() ? 'Package created.' : '';
                if (!$success) $error = 'Create failed: ' . $stmt->error;
                $stmt->close();
            } else {
                if ($id <= 0) { $error = 'Invalid ID.'; }
                else {
                    $stmt = $conn->prepare(
                        "UPDATE packages SET category_id=?, icon=?, name=?, slug=?, duration=?, price=?, currency=?, price_note=?, description=?, meta_title=?, meta_description=?, meta_keywords=?, meta_image=?, canonical_url=?, robots=?, is_featured=?, display_order=?, is_active=? WHERE id=?"
                    );
                    $stmt->bind_param('issssdsssssssssiiii', $categoryId, $icon, $name, $slug, $duration, $price, $currency, $priceNote, $description, $metaTitle, $metaDesc, $metaKeywords, $metaImage, $canonicalUrl, $robots, $isFeatured, $displayOrder, $isActive, $id);
                    $success = $stmt->execute() ? 'Package updated.' : '';
                    if (!$success) $error = 'Update failed: ' . $stmt->error;
                    $stmt->close();
                }
            }
        }
    }
}

// ---------- Fetch packages ----------
$packages = [];
try {
    $res = db_query($conn,
        "SELECT p.id, p.category_id, p.icon, p.name, p.slug, p.duration, p.price, p.currency, p.price_note, p.description, p.meta_title, p.meta_description, p.meta_keywords, p.meta_image, p.canonical_url, p.robots, p.is_featured, p.display_order, p.is_active, pc.name AS category_name
         FROM packages p LEFT JOIN package_categories pc ON p.category_id = pc.id
         ORDER BY pc.display_order ASC, p.display_order ASC, p.id ASC"
    );
    while ($r = $res->fetch_assoc()) {
        $r['id'] = (int) $r['id']; $r['category_id'] = (int) $r['category_id'];
        $r['price'] = (float) $r['price']; $r['is_featured'] = (bool) $r['is_featured'];
        $r['display_order'] = (int) $r['display_order']; $r['is_active'] = (bool) $r['is_active'];
        $packages[] = $r;
    }
} catch (RuntimeException $e) {
    error_log('[packages] fetch: ' . $e->getMessage());
    $error = 'Could not load packages. Please try again.';
}

$currencyOptions = ['₹' => '₹ (NPR)', '$' => '$ (USD)', '€' => '€ (EUR)', '£' => '£ (GBP)'];
?>

<style>
.icon-picker-wrapper { position: relative; }
.icon-picker-btn {
    display: flex; align-items: center; justify-content: center;
    width: 100%; height: 42px;
    background: #fff; border: 1px solid #d1d5db; border-radius: 8px;
    cursor: pointer; font-size: 1.4rem; transition: all .2s;
    gap: 4px; padding: 0 10px;
}
.icon-picker-btn:hover { border-color: #2e7d32; background: #f0fdf4; }
.icon-preview-empty { color: #9ca3af; font-size: .85rem; font-family: 'Inter', sans-serif; }
.icon-preview-selected { font-size: 1.5rem; line-height: 1; }
.icon-picker-dropdown {
    display: none; position: absolute; top: calc(100% + 6px); left: 0;
    z-index: 1000; background: #fff; border: 1px solid #e5e7eb;
    border-radius: 10px; box-shadow: 0 8px 30px rgba(0,0,0,.15);
    padding: 10px; width: 280px;
}
.icon-picker-dropdown.open { display: block; }
.icon-picker-section { font-size: .7rem; font-weight: 600; color: #6b7280;
    text-transform: uppercase; letter-spacing: .5px; padding: 6px 4px 4px; }
.icon-picker-grid { display: grid; grid-template-columns: repeat(6, 1fr); gap: 2px; }
.icon-picker-item {
    display: flex; align-items: center; justify-content: center;
    width: 40px; height: 40px; font-size: 1.4rem; border-radius: 8px;
    cursor: pointer; border: 2px solid transparent; transition: all .15s;
    background: transparent;
}
.icon-picker-item:hover { background: #f0fdf4; border-color: #a7d7ab; transform: scale(1.15); }
.icon-picker-item.selected { background: #e8f5e9; border-color: #2e7d32; }
.icon-picker-footer {
    margin-top: 10px; padding: 8px; background: #f9fafb; border-radius: 6px;
    font-size: .8rem; color: #374151; text-align: center; border-top: 1px solid #e5e7eb;
    font-weight: 500; min-height: 34px; display: flex; align-items: center; justify-content: center;
}
</style>

<?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>
<?php if ($error):   ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

<?php if (empty($pkgCategories)): ?>
    <div class="alert alert-error">No categories found. <a href="package-categories.php">Create a category first.</a></div>
<?php else: ?>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon">📦</div>
        <div class="stat-info">
            <span class="stat-val"><?= count($packages) ?></span>
            <span class="stat-lbl">Total Packages</span>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon">📂</div>
        <div class="stat-info">
            <span class="stat-val"><?= count($pkgCategories) ?></span>
            <span class="stat-lbl">Active Categories</span>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon">⭐</div>
        <div class="stat-info">
            <span class="stat-val"><?= count(array_filter($packages, fn($p) => !empty($p['is_featured']))) ?></span>
            <span class="stat-lbl">Featured Packages</span>
        </div>
    </div>
</div>

<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
    <h2 style="font-size:1.1rem; font-weight:700;">Package List</h2>
    <button class="btn btn-primary" onclick="openModal()">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
        Add New Package
    </button>
</div>

<div class="card">
    <div class="tbl-wrap">
        <table>
            <thead><tr><th>Icon</th><th>Name</th><th>Category</th><th>Duration</th><th>Price</th><th>Active</th><th>Actions</th></tr></thead>
            <tbody>
            <?php foreach ($packages as $p): ?>
                <tr>
                    <td style="font-size:1.4rem;"><?= htmlspecialchars($p['icon']) ?></td>
                    <td>
                        <strong><?= htmlspecialchars($p['name']) ?></strong>
                        <?php if ($p['is_featured']): ?>&nbsp;<span class="badge badge-gold">★</span><?php endif; ?>
                        <?php if ($p['price_note']): ?><br><small style="color:#6b7280;"><?= htmlspecialchars($p['price_note']) ?></small><?php endif; ?>
                    </td>
                    <td><small><?= htmlspecialchars($p['category_name'] ?? '—') ?></small></td>
                    <td><?= htmlspecialchars($p['duration']) ?></td>
                    <td><?= htmlspecialchars($p['currency']) ?> <?= number_format($p['price'], 2) ?></td>
                    <td><?= $p['is_active'] ? '<span class="badge badge-green">Active</span>' : '<span class="badge badge-red">Off</span>' ?></td>
                    <td>
                        <button class="btn btn-secondary btn-sm" onclick='openModal(<?= htmlspecialchars(json_encode($p), ENT_QUOTES) ?>)'>Edit</button>
                        <form method="POST" style="display:inline;" onsubmit="return confirmDelete('Delete package &quot;<?= htmlspecialchars(addslashes($p['name'])) ?>&quot;?')">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= $p['id'] ?>">
                            <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($packages)): ?>
                <tr><td colspan="7" style="color:#6b7280;text-align:center;">No packages yet.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="m-cards">
    <?php foreach ($packages as $p): ?>
    <div class="m-card">
        <div class="m-title"><?= htmlspecialchars($p['icon']) ?> <?= htmlspecialchars($p['name']) ?></div>
        <div class="m-row"><span>Category</span><span><?= htmlspecialchars($p['category_name'] ?? '—') ?></span></div>
        <div class="m-row"><span>Price</span><span><?= htmlspecialchars($p['currency']) ?> <?= number_format($p['price'], 2) ?></span></div>
        <div class="m-row"><span>Duration</span><span><?= htmlspecialchars($p['duration']) ?></span></div>
        <div class="m-row"><span>Active</span><span><?= $p['is_active'] ? 'Yes' : 'No' ?></span></div>
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

<!-- Add/Edit Modal -->
<div class="modal-backdrop" id="pkgModal">
    <div class="modal">
        <h3 id="modalTitle">Add Package</h3>
        <form method="POST" id="pkgForm">
            <input type="hidden" name="action" id="fAction" value="create">
            <input type="hidden" name="id" id="fId" value="">
            <div class="form-group">
                <label for="fCat">Category <span style="color:red">*</span></label>
                <select class="form-control" id="fCat" name="category_id" required>
                    <option value="">— Select category —</option>
                    <?php foreach ($pkgCategories as $c): ?>
                        <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-row">
                <div class="form-group" style="flex:0 0 140px;">
                    <label>Icon</label>
                    <input type="hidden" id="fIcon" name="icon" value="">
                    <div class="icon-picker-wrapper" id="iconPickerWrapper">
                        <button type="button" class="icon-picker-btn" id="iconPickerBtn" onclick="toggleIconPicker()">
                            <span id="iconPreview" class="icon-preview-empty">?</span>
                            <svg width="12" height="12" viewBox="0 0 12 12" fill="none" style="margin-left:4px;"><path d="M3 4.5L6 7.5L9 4.5" stroke="#6b7280" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </button>
                        <div class="icon-picker-dropdown" id="iconPickerDropdown"></div>
                    </div>
                </div>
                <div class="form-group">
                    <label for="fName">Name <span style="color:red">*</span></label>
                    <input type="text" class="form-control" id="fName" name="name" required>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label for="fDuration">Duration</label>
                    <input type="text" class="form-control" id="fDuration" name="duration" placeholder="2 Nights · 3 Days">
                </div>
                <div class="form-group">
                    <label for="fPriceNote">Price Note</label>
                    <input type="text" class="form-control" id="fPriceNote" name="price_note" placeholder="Twin sharing">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label for="fPrice">Price <span style="color:red">*</span></label>
                    <input type="number" class="form-control" id="fPrice" name="price" min="0" step="0.01" required>
                </div>
                <div class="form-group">
                    <label for="fCurrency">Currency</label>
                    <select class="form-control" id="fCurrency" name="currency">
                        <?php foreach ($currencyOptions as $val => $label): ?>
                            <option value="<?= htmlspecialchars($val) ?>"><?= htmlspecialchars($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="fOrder">Order</label>
                    <input type="number" class="form-control" id="fOrder" name="display_order" value="0" min="0">
                </div>
            </div>
            <div class="form-group">
                <label for="fDesc">Description</label>
                <textarea class="form-control" id="fDesc" name="description" rows="3"></textarea>
            </div>
            <hr style="margin:20px 0; border:0; border-top:1px solid var(--border);">
            <h4 style="margin-bottom:12px; font-size:0.95rem; color:var(--brand-dk);">SEO Settings</h4>
            <div class="form-row">
                <div class="form-group">
                    <label for="fSlug">URL Slug (Auto-generated if empty)</label>
                    <input type="text" class="form-control" id="fSlug" name="slug" pattern="[a-z0-9\-]+">
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
                    <input type="text" class="form-control" id="fMetaImage" name="meta_image" placeholder="/uploads/package.jpg">
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
            <div style="display:flex;gap:20px;flex-wrap:wrap;">
                <div class="check-group">
                    <input type="checkbox" id="fFeatured" name="is_featured" value="1">
                    <label for="fFeatured">Mark as Featured</label>
                </div>
                <div class="check-group">
                    <input type="checkbox" id="fActive" name="is_active" value="1" checked>
                    <label for="fActive">Active</label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                <button type="submit" class="btn btn-primary" id="submitBtn">Save Package</button>
            </div>
        </form>
    </div>
</div>

<script>
// ── Icon Picker ──────────────────────────────────────────────────
const iconSections = [
    { label: 'Wildlife & Safari', icons: [
        { char: '🐅', name: 'Tiger' }, { char: '🦏', name: 'Rhino' }, { char: '🐘', name: 'Elephant' }, 
        { char: '🐊', name: 'Crocodile' }, { char: '🐬', name: 'Dolphin' }, { char: '🦌', name: 'Deer' }, 
        { char: '🐒', name: 'Monkey' }, { char: '🦜', name: 'Parrot' }, { char: '🦅', name: 'Eagle' }, 
        { char: '🦉', name: 'Owl' }, { char: '🐾', name: 'Paws' }
    ]},
    { label: 'River & Adventure', icons: [
        { char: '🚙', name: 'Jeep Safari' }, { char: '🛶', name: 'Canoe' }, { char: '🚶‍♂️', name: 'Jungle Walk' }, 
        { char: '🥾', name: 'Trekking' }, { char: '🔭', name: 'Spotting' }, { char: '📸', name: 'Photography' }, 
        { char: '🎣', name: 'Fishing' }, { char: '🎒', name: 'Backpack' }, { char: '🚣', name: 'Boating' }, { char: '🌊', name: 'River' }
    ]},
    { label: 'Bardia Hospitality', icons: [
        { char: '🏡', name: 'Homestay' }, { char: '🏠', name: 'Lodge' }, { char: '🍛', name: 'Thali' }, 
        { char: '🍲', name: 'Organic Food' }, { char: '🍵', name: 'Milk Tea' }, { char: '🛌', name: 'Bed' }, 
        { char: '🏮', name: 'Lantern' }, { char: '🥁', name: 'Tharu Dance' }, { char: '🔥', name: 'Campfire' }, 
        { char: '⛺', name: 'Camping' }, { char: '🧘', name: 'Yoga' }
    ]},
    { label: 'Nature & Labels', icons: [
        { char: '🌳', name: 'Sal Tree' }, { char: '🌴', name: 'Palm Tree' }, { char: '🌅', name: 'Sunrise' }, 
        { char: '🌌', name: 'Night Sky' }, { char: '⭐', name: 'Rating' }, { char: '✅', name: 'Included' }, 
        { char: '⚡', name: 'Quick' }, { char: '✨', name: 'Featured' }
    ]}
];

const dropdown = document.getElementById('iconPickerDropdown');
iconSections.forEach(section => {
    const lbl = document.createElement('div');
    lbl.className = 'icon-picker-section';
    lbl.textContent = section.label;
    dropdown.appendChild(lbl);
    const grid = document.createElement('div');
    grid.className = 'icon-picker-grid';
    section.icons.forEach(ico => {
        const item = document.createElement('span');
        item.className = 'icon-picker-item';
        item.textContent = ico.char;
        item.title = ico.name; 
        item.addEventListener('click', () => selectIcon(ico.char));
        
        // Dynamic hover name
        item.addEventListener('mouseenter', () => {
            pickerFooter.textContent = ico.name;
            pickerFooter.style.color = 'var(--primary)';
        });
        item.addEventListener('mouseleave', () => {
            pickerFooter.textContent = 'Select an icon';
            pickerFooter.style.color = '#374151';
        });
        
        grid.appendChild(item);
    });
    dropdown.appendChild(grid);
});

// Append a footer for hover names
const pickerFooter = document.createElement('div');
pickerFooter.id = 'iconPickerFooter';
pickerFooter.className = 'icon-picker-footer';
pickerFooter.textContent = 'Select an icon';
dropdown.appendChild(pickerFooter);

function selectIcon(icon) {
    document.getElementById('fIcon').value = icon;
    const preview = document.getElementById('iconPreview');
    preview.textContent = icon;
    preview.className = 'icon-preview-selected';
    dropdown.querySelectorAll('.icon-picker-item').forEach(el => {
        el.classList.toggle('selected', el.textContent === icon);
    });
    dropdown.classList.remove('open');
}

function setIconPreview(icon) {
    const preview = document.getElementById('iconPreview');
    if (icon) {
        preview.textContent = icon;
        preview.className = 'icon-preview-selected';
        dropdown.querySelectorAll('.icon-picker-item').forEach(el => {
            el.classList.toggle('selected', el.textContent === icon);
        });
    } else {
        preview.textContent = '?';
        preview.className = 'icon-preview-empty';
        dropdown.querySelectorAll('.icon-picker-item').forEach(el => el.classList.remove('selected'));
    }
}

function toggleIconPicker() {
    dropdown.classList.toggle('open');
}

document.addEventListener('click', e => {
    if (!document.getElementById('iconPickerWrapper').contains(e.target)) {
        dropdown.classList.remove('open');
    }
});

function openModal(pkg) {
    document.getElementById('pkgModal').classList.add('active');
    if (pkg) {
        document.getElementById('modalTitle').textContent = 'Edit Package';
        document.getElementById('fAction').value    = 'update';
        document.getElementById('fId').value         = pkg.id;
        document.getElementById('fCat').value        = pkg.category_id;
        document.getElementById('fIcon').value       = pkg.icon || '';
        setIconPreview(pkg.icon || '');
        document.getElementById('fName').value       = pkg.name || '';
        document.getElementById('fDuration').value   = pkg.duration || '';
        document.getElementById('fPrice').value      = pkg.price || '';
        document.getElementById('fCurrency').value   = pkg.currency || '₹';
        document.getElementById('fPriceNote').value  = pkg.price_note || '';
        document.getElementById('fDesc').value       = pkg.description || '';
        document.getElementById('fSlug').value       = pkg.slug || '';
        document.getElementById('fMetaTitle').value  = pkg.meta_title || '';
        document.getElementById('fMetaDesc').value   = pkg.meta_description || '';
        document.getElementById('fMetaKeywords').value= pkg.meta_keywords || '';
        document.getElementById('fMetaImage').value  = pkg.meta_image || '';
        document.getElementById('fCanonical').value  = pkg.canonical_url || '';
        document.getElementById('fRobots').value     = pkg.robots || 'index,follow';
        document.getElementById('fOrder').value      = pkg.display_order || 0;
        document.getElementById('fFeatured').checked = !!pkg.is_featured;
        document.getElementById('fActive').checked   = !!pkg.is_active;
        document.getElementById('submitBtn').textContent = 'Update Package';
    } else {
        document.getElementById('modalTitle').textContent = 'Add Package';
        document.getElementById('fAction').value = 'create';
        document.getElementById('pkgForm').reset();
        document.getElementById('fIcon').value = '';
        setIconPreview('');
        document.getElementById('fActive').checked = true;
        document.getElementById('submitBtn').textContent = 'Save Package';
    }
}
function closeModal() {
    document.getElementById('pkgModal').classList.remove('active');
    dropdown.classList.remove('open');
}
document.getElementById('pkgModal').addEventListener('click', function(e) { if (e.target === this) closeModal(); });
</script>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
