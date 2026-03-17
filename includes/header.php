<?php
// $pageTitle should be set by the page before including header.php
$pageTitle = isset($pageTitle) ? $pageTitle . ' — CodeVault' : 'CodeVault';
$_isLoggedIn = isLoggedIn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= sanitize($pageTitle) ?></title>
    <meta name="description" content="CodeVault — Your personal code snippet library. Save, organize, search, and share code.">
    <meta name="base-url" content="<?= BASE_URL ?>">

    <!-- Fonts: JetBrains Mono for code only; UI uses system stack -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">

    <!-- Prism.js Theme (Tomorrow Night) -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/themes/prism-tomorrow.min.css" rel="stylesheet">

    <!-- Prism.js Line Numbers Plugin -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/plugins/line-numbers/prism-line-numbers.min.css" rel="stylesheet">

    <!-- CodeVault Styles -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>

<!-- ── Navbar ──────────────────────────────────────────────── -->
<nav class="navbar">

    <?php if ($_isLoggedIn): ?>
    <button class="navbar-hamburger" id="sidebar-toggle" aria-label="Toggle sidebar">
        <div class="navbar-hamburger-lines">
            <span></span><span></span><span></span>
        </div>
    </button>
    <?php endif; ?>

    <!-- Left: brand -->
    <a href="<?= BASE_URL ?>/" class="navbar-brand">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
             stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="16 18 22 12 16 6"></polyline>
            <polyline points="8 6 2 12 8 18"></polyline>
        </svg>
        CodeVault
    </a>

    <?php if (!$_isLoggedIn): ?>
    <!-- Guest links shown left-side after brand -->
    <ul class="navbar-guest-links">
        <li><a href="<?= BASE_URL ?>/explore">Explore</a></li>
        <li><a href="<?= BASE_URL ?>/docs">Docs</a></li>
    </ul>
    <?php endif; ?>

    <div class="navbar-spacer"></div>

    <?php if ($_isLoggedIn): ?>
        <!-- Search placeholder -->
        <div class="navbar-search">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                 stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="11" cy="11" r="8"></circle>
                <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
            </svg>
            <span class="navbar-search-text">Search...</span>
            <span class="navbar-search-kbd">⌘K</span>
        </div>

        <!-- User avatar -->
        <a href="<?= BASE_URL ?>/u/<?= sanitize(currentUsername()) ?>" class="navbar-avatar">
            <?= strtoupper(substr(currentUsername(), 0, 1)) ?>
        </a>

    <?php else: ?>
        <!-- Guest auth buttons (or page-specific hint on login/register pages) -->
        <div class="navbar-auth">
            <?php if (($authPage ?? '') === 'login'): ?>
                <span style="font-size:0.8rem; color:var(--text-muted);">Don't have an account?</span>
                <a href="<?= BASE_URL ?>/register" class="btn btn-primary btn-sm">Sign up</a>
            <?php elseif (($authPage ?? '') === 'register'): ?>
                <span style="font-size:0.8rem; color:var(--text-muted);">Already have an account?</span>
                <a href="<?= BASE_URL ?>/login" class="btn btn-primary btn-sm">Log in</a>
            <?php else: ?>
                <a href="<?= BASE_URL ?>/login" class="btn-login">Log in</a>
                <a href="<?= BASE_URL ?>/register" class="btn btn-primary btn-sm">Sign up</a>
            <?php endif; ?>
        </div>
    <?php endif; ?>

</nav>

<div class="sidebar-overlay" id="sidebar-overlay"></div>

<?php
// Collect flash messages once — used below in both layout branches
$_flashError   = getFlash('flash_error');
$_flashSuccess = getFlash('flash_success');
?>

<?php if ($_isLoggedIn): ?>
<!-- ── App layout: sidebar + main ──────────────────────────── -->
<div class="app-layout">
    <?php require BASE_PATH . '/includes/sidebar.php'; ?>
    <div class="app-main">
        <?php if ($_flashError): ?>
            <div class="alert alert-error mb-md"><?= sanitize($_flashError) ?></div>
        <?php endif; ?>
        <?php if ($_flashSuccess): ?>
            <div class="alert alert-success mb-md"><?= sanitize($_flashSuccess) ?></div>
        <?php endif; ?>

<?php else: ?>
<!-- ── Public layout: full-width main ──────────────────────── -->
<main>
    <?php if ($_flashError): ?>
        <div class="container mt-md">
            <div class="alert alert-error"><?= sanitize($_flashError) ?></div>
        </div>
    <?php endif; ?>
    <?php if ($_flashSuccess): ?>
        <div class="container mt-md">
            <div class="alert alert-success"><?= sanitize($_flashSuccess) ?></div>
        </div>
    <?php endif; ?>
<?php endif; ?>
