<?php
/**
 * CMS Header — included at top of every CMS page.
 * Starts session, checks auth, outputs <head> + sidebar nav.
 *
 * Expects calling page to define $pageTitle before including this file.
 */

// Load project config (DB connection via $conn)
require_once __DIR__ . '/../../includes/config.php';

session_start();

// Determine active page for nav highlight — must happen BEFORE the auth check
$currentFile = basename($_SERVER['PHP_SELF']);

// Build a reliable CMS-root URL regardless of include depth
// e.g. /bardiya-eco-friendly/cms/
$scriptPath = str_replace('\\', '/', $_SERVER['PHP_SELF']);
$cmsPos     = strpos($scriptPath, '/cms/');
$cmsBase    = ($cmsPos !== false)
    ? substr($scriptPath, 0, $cmsPos + 5)   // keeps the trailing slash after /cms/
    : '/bardiya-eco-friendly/cms/';

$pageTitle  = $pageTitle ?? 'CMS Dashboard';

// Redirect to login if not authenticated
if (empty($_SESSION['cms_user_id'])) {
    header('Location: ' . $cmsBase . 'login.php');
    exit;
}

// Reject forged cross-site form posts (token is injected into every POST form by footer.php)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !csrf_valid($_POST['csrf_token'] ?? null)) {
    // An upload over post_max_size arrives with an empty $_POST, so the token looks missing
    $tooLarge = empty($_POST) && (int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 0;
    http_response_code($tooLarge ? 413 : 403);
    exit($tooLarge
        ? 'The upload is too large for the server. Go back and choose a smaller file.'
        : 'Your session expired or the request was invalid. Go back, refresh the page and try again.');
}

$adminUsername = htmlspecialchars($_SESSION['cms_username'] ?? 'Admin');
$adminInitial  = strtoupper(substr($adminUsername, 0, 1));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="csrf-token" content="<?= htmlspecialchars(csrf_token()) ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> — Bardiya Eco CMS</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= $cmsBase ?>assets/css/admin.css">
    <script>
        // Use PHP's detected or configured base URL for API requests
        const API_BASE_URL = '<?= rtrim($baseUrl, "/") ?>/public/index.php';
    </script>
</head>
<body>

<div class="overlay" id="overlay" onclick="toggleSidebar()"></div>

<aside class="cms-sidebar" id="sidebar">
    <div class="brand">
        <div class="brand-icon">🌿</div>
        <div class="brand-text">
            <span class="brand-title">Bardiya Eco</span>
            <span class="brand-sub">CMS Portal v2.0</span>
        </div>
    </div>
    
    <nav>
        <div class="nav-section">Packages</div>
        <a href="package-categories.php" class="<?= $currentFile === 'package-categories.php' ? 'active' : '' ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path></svg>
            Categories
        </a>
        <a href="packages.php" class="<?= $currentFile === 'packages.php' ? 'active' : '' ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path></svg>
            Packages
        </a>
        <a href="package-features.php" class="<?= $currentFile === 'package-features.php' ? 'active' : '' ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 11 12 14 22 4"></polyline><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path></svg>
            Features
        </a>

        <div class="nav-section">Gallery</div>
        <a href="gallery-categories.php" class="<?= $currentFile === 'gallery-categories.php' ? 'active' : '' ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><polyline points="21 15 16 10 5 21"></polyline></svg>
            Categories
        </a>
        <a href="gallery-images.php" class="<?= $currentFile === 'gallery-images.php' ? 'active' : '' ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><polyline points="21 15 16 10 5 21"></polyline></svg>
            Images
        </a>

        <div class="nav-section">Blog</div>
        <a href="blog-categories.php" class="<?= $currentFile === 'blog-categories.php' ? 'active' : '' ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path></svg>
            Categories
        </a>
        <a href="blog-posts.php" class="<?= $currentFile === 'blog-posts.php' ? 'active' : '' ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
            Posts
        </a>

        <div class="nav-section">Activities & Wildlife</div>
        <a href="activities.php" class="<?= $currentFile === 'activities.php' ? 'active' : '' ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>
            Wildlife Experiences
        </a>

        <div class="nav-section">Static Pages & SEO</div>
        <a href="about.php" class="<?= $currentFile === 'about.php' ? 'active' : '' ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="4"></circle><path d="M4 21v-1a7 7 0 0 1 14 0v1"></path></svg>
            About Page
        </a>
        <a href="pages.php" class="<?= $currentFile === 'pages.php' ? 'active' : '' ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline></svg>
            Pages
        </a>
        <a href="seo-settings.php" class="<?= $currentFile === 'seo-settings.php' ? 'active' : '' ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
            Global SEO
        </a>
        <a href="page-seo.php" class="<?= $currentFile === 'page-seo.php' ? 'active' : '' ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line></svg>
            Page SEO Meta
        </a>

        <div class="nav-section">Contact & Messaging</div>
        <a href="contact-methods.php" class="<?= $currentFile === 'contact-methods.php' ? 'active' : '' ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path></svg>
            Contact Methods
        </a>
        <a href="contact-submissions.php" class="<?= $currentFile === 'contact-submissions.php' ? 'active' : '' ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg>
            Submissions
        </a>
        <a href="social-links.php" class="<?= $currentFile === 'social-links.php' ? 'active' : '' ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"></path><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"></path></svg>
            Social Links
        </a>

        <div class="nav-section">Account</div>
        <a href="profile.php" class="<?= $currentFile === 'profile.php' ? 'active' : '' ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
            My Profile
        </a>
    </nav>

    <div class="user-profile">
        <div class="user-info">
            <div class="user-avatar"><?= $adminInitial ?></div>
            <div class="user-details">
                <span class="user-name"><?= $adminUsername ?></span>
                <span class="user-role">Administrator</span>
            </div>
        </div>
        <a href="logout.php" class="btn-logout" title="Sign Out">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
        </a>
    </div>
</aside>

<main class="cms-main">
    <div class="cms-topbar">
        <div class="left-group">
            <button class="hamburger" onclick="toggleSidebar()" aria-label="Toggle Sidebar Navigation">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="12" x2="21" y2="12"></line><line x1="3" y1="6" x2="21" y2="6"></line><line x1="3" y1="18" x2="21" y2="18"></line></svg>
            </button>
            <div class="page-title-wrap">
                <h1><?= htmlspecialchars($pageTitle) ?></h1>
                <div class="breadcrumbs">
                    <span>Admin</span> &rsaquo; <span><?= htmlspecialchars($pageTitle) ?></span>
                </div>
            </div>
        </div>

        <div class="right-group">
            <div class="search-bar">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                <input type="text" placeholder="Search records..." id="cmsQuickSearch">
            </div>
        </div>
    </div>
    <div class="cms-content">
