<?php
$pageTitle = 'Global SEO Settings';
require_once __DIR__ . '/includes/header.php';

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $allowed = [
        'site_name',
        'default_title',
        'default_description',
        'default_keywords',
        'default_image',
        'robots_txt',
        'google_reviews_enabled',
        'google_reviews_provider',
        'aggregate_rating_value',
        'aggregate_rating_count',
    ];

    $stmt = $conn->prepare(
        "INSERT INTO seo_settings (setting_key, setting_value) VALUES (?, ?)
         ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)"
    );

    if (!$stmt) {
        $error = 'Could not prepare settings update.';
    } else {
        foreach ($allowed as $key) {
            $value = (string) ($_POST[$key] ?? '');
            if ($key === 'google_reviews_enabled') {
                $value = isset($_POST[$key]) ? '1' : '0';
            }
            $stmt->bind_param('ss', $key, $value);
            if (!$stmt->execute()) {
                $error = 'Failed to update setting: ' . htmlspecialchars($key);
                break;
            }
        }
        $stmt->close();
        if (!$error) {
            $success = 'SEO settings updated.';
        }
    }
}

$settings = [];
$result = $conn->query("SELECT setting_key, setting_value FROM seo_settings");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
}

function seo_value(array $settings, string $key, string $fallback = ''): string
{
    return htmlspecialchars($settings[$key] ?? $fallback, ENT_QUOTES, 'UTF-8');
}
?>

<?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

<form method="POST" class="card">
    <div class="card-header"><h2>Site Defaults</h2></div>
    <div class="card-body">
        <div class="form-row">
            <div class="form-group">
                <label for="siteName">Site Name</label>
                <input id="siteName" class="form-control" name="site_name" value="<?= seo_value($settings, 'site_name', 'Bardia Eco-Friendly Homestay') ?>">
            </div>
            <div class="form-group">
                <label for="defaultImage">Default Social Image</label>
                <input id="defaultImage" class="form-control" name="default_image" value="<?= seo_value($settings, 'default_image', '/logo.png') ?>">
            </div>
        </div>
        <div class="form-group">
            <label for="defaultTitle">Default SEO Title</label>
            <input id="defaultTitle" class="form-control" name="default_title" value="<?= seo_value($settings, 'default_title') ?>">
        </div>
        <div class="form-group">
            <label for="defaultDescription">Default Meta Description</label>
            <textarea id="defaultDescription" class="form-control" name="default_description" rows="3"><?= seo_value($settings, 'default_description') ?></textarea>
        </div>
        <div class="form-group">
            <label for="defaultKeywords">Default Meta Keywords</label>
            <textarea id="defaultKeywords" class="form-control" name="default_keywords" rows="2" placeholder="Comma-separated keywords"><?= seo_value($settings, 'default_keywords') ?></textarea>
        </div>
        <div class="form-group">
            <label for="robotsTxt">robots.txt</label>
            <textarea id="robotsTxt" class="form-control" name="robots_txt" rows="8"><?= seo_value($settings, 'robots_txt', "User-agent: *\nAllow: /\n\nSitemap: https://www.bardiaecofriendlyhomestay.com/sitemap.xml") ?></textarea>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label for="reviewsProvider">Google Reviews Provider</label>
                <input id="reviewsProvider" class="form-control" name="google_reviews_provider" value="<?= seo_value($settings, 'google_reviews_provider', 'elfsight') ?>">
            </div>
            <div class="form-group">
                <label for="ratingValue">Aggregate Rating Value</label>
                <input id="ratingValue" class="form-control" name="aggregate_rating_value" value="<?= seo_value($settings, 'aggregate_rating_value', '5.0') ?>">
            </div>
            <div class="form-group">
                <label for="ratingCount">Aggregate Rating Count</label>
                <input id="ratingCount" class="form-control" name="aggregate_rating_count" value="<?= seo_value($settings, 'aggregate_rating_count', '120') ?>">
            </div>
        </div>
        <div class="check-group" style="margin-bottom:16px;">
            <input type="checkbox" id="reviewsEnabled" name="google_reviews_enabled" value="1" <?= ($settings['google_reviews_enabled'] ?? '1') === '1' ? 'checked' : '' ?>>
            <label for="reviewsEnabled">Enable Google Reviews integration</label>
        </div>
        <button class="btn btn-primary" type="submit">Save SEO Settings</button>
    </div>
</form>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
