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

$pageTitle  = $pageTitle ?? 'CMS';

// Redirect to login if not authenticated
if (empty($_SESSION['cms_user_id'])) {
    header('Location: ' . $cmsBase . 'login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> — Bardiya Eco CMS</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script>
        // Use PHP's detected or configured base URL for API requests
        const API_BASE_URL = '<?= rtrim($baseUrl, "/") ?>/public/index.php';
    </script>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --brand:    #2e7d32;
            --brand-dk: #1b5e20;
            --brand-lt: #e8f5e9;
            --danger:   #c62828;
            --warn:     #f57c00;
            --text:     #1a1a1a;
            --muted:    #6b7280;
            --border:   #d1d5db;
            --bg:       #f9fafb;
            --white:    #ffffff;
            --sidebar-w: 220px;
            --radius:   8px;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--bg);
            color: var(--text);
            display: flex;
            min-height: 100vh;
        }

        /* ── Sidebar ── */
        .cms-sidebar {
            width: var(--sidebar-w);
            background: var(--brand-dk);
            display: flex;
            flex-direction: column;
            position: fixed;
            top: 0; left: 0;
            height: 100vh;
            z-index: 100;
            transition: transform .25s;
        }
        .cms-sidebar .brand {
            padding: 20px 16px;
            color: #fff;
            font-size: 1rem;
            font-weight: 700;
            border-bottom: 1px solid rgba(255,255,255,.15);
            line-height: 1.3;
        }
        .cms-sidebar .brand small { font-size: .7rem; font-weight: 400; opacity: .7; display: block; }
        .cms-sidebar nav { flex: 1; padding: 12px 0; overflow-y: auto; }
        .cms-sidebar nav a {
            display: flex; align-items: center; gap: 10px;
            padding: 10px 16px;
            color: rgba(255,255,255,.8);
            text-decoration: none;
            font-size: .88rem;
            transition: background .15s;
            border-left: 3px solid transparent;
        }
        .cms-sidebar nav a:hover, .cms-sidebar nav a.active {
            background: rgba(255,255,255,.12);
            color: #fff;
            border-left-color: #a5d6a7;
        }
        .cms-sidebar nav .nav-section {
            font-size: .68rem; font-weight: 600; letter-spacing: .08em;
            color: rgba(255,255,255,.4); text-transform: uppercase;
            padding: 14px 16px 4px;
        }
        .cms-sidebar .logout {
            padding: 12px 16px;
            border-top: 1px solid rgba(255,255,255,.15);
        }
        .cms-sidebar .logout a {
            color: rgba(255,255,255,.7); font-size: .84rem; text-decoration: none;
        }
        .cms-sidebar .logout a:hover { color: #fff; }

        /* ── Mobile hamburger ── */
        .hamburger {
            display: none;
            background: var(--brand-dk); color: #fff;
            border: none; border-radius: var(--radius);
            padding: 8px 12px; cursor: pointer; font-size: 1.2rem;
            align-items: center; justify-content: center;
            transition: background .2s;
        }
        .hamburger:hover { background: var(--brand); }
        .overlay {
            display: none;
            position: fixed; inset: 0; background: rgba(0,0,0,.4); z-index: 99;
        }

        /* ── Main content ── */
        .cms-main {
            margin-left: var(--sidebar-w);
            flex: 1;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }
        .cms-topbar {
            background: var(--white);
            border-bottom: 1px solid var(--border);
            padding: 12px 24px;
            display: flex;
            align-items: center;
            gap: 16px;
            position: sticky;
            top: 0;
            z-index: 90;
        }
        .cms-topbar h1 { font-size: 1.05rem; font-weight: 600; flex: 1; }
        .cms-content { padding: 24px; flex: 1; }

        /* ── Alerts ── */
        .alert {
            padding: 12px 16px; border-radius: var(--radius);
            margin-bottom: 16px; font-size: .9rem;
            border: 1px solid transparent;
        }
        .alert-success { background:#e8f5e9; border-color:#a5d6a7; color:#1b5e20; }
        .alert-error   { background:#ffebee; border-color:#ef9a9a; color:#b71c1c; }

        /* ── Cards / Table ── */
        .card {
            background: var(--white); border-radius: var(--radius);
            border: 1px solid var(--border); overflow: hidden;
        }
        .card-header {
            padding: 16px 20px;
            display: flex; align-items: center; justify-content: space-between;
            border-bottom: 1px solid var(--border);
        }
        .card-header h2 { font-size: 1rem; font-weight: 600; }
        .card-body { padding: 20px; }

        /* ── Table ── */
        .tbl-wrap { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; font-size: .88rem; }
        th { background: var(--bg); text-align: left; padding: 10px 14px;
             font-weight: 600; border-bottom: 1px solid var(--border); }
        td { padding: 10px 14px; border-bottom: 1px solid #f0f0f0; vertical-align: middle; }
        tr:last-child td { border-bottom: none; }
        tr:hover td { background: #fafffe; }

        /* ── Mobile card list ── */
        .m-cards { display: none; gap: 12px; flex-direction: column; }
        .m-card {
            background: var(--white); border: 1px solid var(--border);
            border-radius: var(--radius); padding: 14px 16px;
        }
        .m-card .m-title { font-weight: 600; margin-bottom: 6px; font-size: .95rem; }
        .m-card .m-row { display: flex; justify-content: space-between;
            font-size: .84rem; padding: 2px 0; color: var(--muted); }
        .m-card .m-row span:last-child { color: var(--text); text-align: right; }
        .m-card .m-actions { margin-top: 10px; display: flex; gap: 8px; flex-wrap: wrap; }

        /* ── Responsive breakpoint ── */
        @media (max-width: 768px) {
            .cms-sidebar { transform: translateX(-100%); }
            .cms-sidebar.open { transform: translateX(0); }
            .hamburger { display: flex; }
            .overlay.active { display: block; }
            .cms-main { margin-left: 0; }
            .cms-topbar { padding: 10px 16px; }
            .tbl-wrap table { display: none; }
            .m-cards { display: flex; }
        }

        /* ── Buttons ── */
        .btn {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 8px 14px; border-radius: var(--radius);
            border: 1px solid transparent; cursor: pointer;
            font-size: .84rem; font-weight: 500; text-decoration: none;
            transition: opacity .15s, box-shadow .15s;
            font-family: inherit;
        }
        .btn:hover { opacity: .88; box-shadow: 0 1px 4px rgba(0,0,0,.12); }
        .btn-primary { background: var(--brand); color: #fff; border-color: var(--brand); }
        .btn-secondary { background: var(--white); color: var(--text); border-color: var(--border); }
        .btn-danger  { background: var(--danger); color: #fff; border-color: var(--danger); }
        .btn-sm { padding: 5px 10px; font-size: .8rem; }

        /* ── Forms ── */
        .form-group { margin-bottom: 16px; }
        .form-group label { display: block; font-size: .85rem; font-weight: 500; margin-bottom: 5px; }
        .form-control {
            width: 100%; padding: 9px 12px;
            border: 1px solid var(--border); border-radius: var(--radius);
            font-family: inherit; font-size: .9rem;
            transition: border-color .15s;
        }
        .form-control:focus { outline: none; border-color: var(--brand); }
        select.form-control { background: var(--white); }
        .form-row { display: flex; gap: 16px; flex-wrap: wrap; }
        .form-row .form-group { flex: 1; min-width: 200px; }
        .check-group { display: flex; align-items: center; gap: 8px; margin-top: 4px; }
        .check-group input[type=checkbox] { width: 16px; height: 16px; accent-color: var(--brand); }

        /* ── Modal ── */
        .modal-backdrop {
            display: none; position: fixed; inset: 0;
            background: rgba(0,0,0,.45); z-index: 300;
            align-items: center; justify-content: center;
        }
        .modal-backdrop.active { display: flex; }
        .modal {
            background: var(--white); border-radius: var(--radius);
            width: min(560px, 96vw); max-height: 90vh; overflow-y: auto;
            padding: 24px; box-shadow: 0 8px 32px rgba(0,0,0,.18);
        }
        .modal h3 { font-size: 1.05rem; margin-bottom: 16px; }
        .modal-footer { margin-top: 16px; display: flex; gap: 10px; justify-content: flex-end; }

        /* ── Badge ── */
        .badge {
            display: inline-block; font-size: .72rem; font-weight: 600;
            padding: 2px 8px; border-radius: 99px;
        }
        .badge-green { background: #e8f5e9; color: #2e7d32; }
        .badge-red   { background: #ffebee; color: #c62828; }
        .badge-gold  { background: #fff8e1; color: #f57c00; }
    </style>
</head>
<body>

<div class="overlay" id="overlay" onclick="toggleSidebar()"></div>

<aside class="cms-sidebar" id="sidebar">
    <div class="brand">
        🌿 Bardiya Eco
        <small>Admin Panel</small>
    </div>
    <nav>
        <div class="nav-section">Packages</div>
        <a href="package-categories.php" class="<?= $currentFile === 'package-categories.php' ? 'active' : '' ?>">🗂️ Categories</a>
        <a href="packages.php" class="<?= $currentFile === 'packages.php' ? 'active' : '' ?>">📦 Packages</a>
        <a href="package-features.php" class="<?= $currentFile === 'package-features.php' ? 'active' : '' ?>">✅ Features</a>
        <div class="nav-section">Gallery</div>
        <a href="gallery-categories.php" class="<?= $currentFile === 'gallery-categories.php' ? 'active' : '' ?>">🗂️ Categories</a>
        <a href="gallery-images.php" class="<?= $currentFile === 'gallery-images.php' ? 'active' : '' ?>">🖼️ Images</a>
        <div class="nav-section">SEO</div>
        <a href="seo-settings.php" class="<?= $currentFile === 'seo-settings.php' ? 'active' : '' ?>">🔎 Global SEO</a>
        <div class="nav-section">Contact</div>
        <a href="contact-methods.php" class="<?= $currentFile === 'contact-methods.php' ? 'active' : '' ?>">📞 Methods</a>
        <a href="contact-submissions.php" class="<?= $currentFile === 'contact-submissions.php' ? 'active' : '' ?>">📬 Submissions</a>
        <a href="social-links.php" class="<?= $currentFile === 'social-links.php' ? 'active' : '' ?>">🔗 Social Links</a>
        <div class="nav-section">Account</div>
        <a href="profile.php" class="<?= $currentFile === 'profile.php' ? 'active' : '' ?>">👤 My Profile</a>
    </nav>
    <div class="logout">
        <a href="logout.php">⎋ Logout (<?= htmlspecialchars($_SESSION['cms_username'] ?? 'Admin') ?>)</a>
    </div>
</aside>

<main class="cms-main">
    <div class="cms-topbar">
        <button class="hamburger" onclick="toggleSidebar()" aria-label="Menu">☰</button>
        <h1><?= htmlspecialchars($pageTitle) ?></h1>
    </div>
    <div class="cms-content">
