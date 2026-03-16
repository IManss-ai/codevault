<?php
// $pageTitle should be set by the page before including header.php
$pageTitle = isset($pageTitle) ? $pageTitle . ' — CodeVault' : 'CodeVault';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= sanitize($pageTitle) ?></title>
    <meta name="description" content="CodeVault — Your personal code snippet library. Save, organize, search, and share code.">
    <meta name="base-url" content="<?= BASE_URL ?>">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">

    <!-- Prism.js Theme (Tomorrow Night) -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/themes/prism-tomorrow.min.css" rel="stylesheet">

    <!-- CodeVault Styles -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>

<!-- Navigation -->
<nav class="navbar">
    <div class="navbar-inner">
        <a href="<?= BASE_URL ?>/" class="navbar-brand">
            <!-- Code bracket icon -->
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="16 18 22 12 16 6"></polyline>
                <polyline points="8 6 2 12 8 18"></polyline>
            </svg>
            CodeVault
        </a>

        <!-- Mobile hamburger -->
        <button class="navbar-toggle" onclick="document.querySelector('.navbar-links').classList.toggle('active')" aria-label="Toggle navigation">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="3" y1="12" x2="21" y2="12"></line>
                <line x1="3" y1="6" x2="21" y2="6"></line>
                <line x1="3" y1="18" x2="21" y2="18"></line>
            </svg>
        </button>

        <ul class="navbar-links">
            <li><a href="<?= BASE_URL ?>/explore">Explore</a></li>
            <?php if (isLoggedIn()): ?>
                <li><a href="<?= BASE_URL ?>/dashboard">Dashboard</a></li>
                <li><a href="<?= BASE_URL ?>/new" class="btn btn-primary btn-sm">+ New Snippet</a></li>
                <li><a href="<?= BASE_URL ?>/u/<?= sanitize(currentUsername()) ?>"><?= sanitize(currentUsername()) ?></a></li>
                <li><a href="<?= BASE_URL ?>/settings">Settings</a></li>
                <li><a href="<?= BASE_URL ?>/logout">Logout</a></li>
            <?php else: ?>
                <li><a href="<?= BASE_URL ?>/login">Log In</a></li>
                <li><a href="<?= BASE_URL ?>/register" class="btn btn-primary btn-sm">Sign Up</a></li>
            <?php endif; ?>
        </ul>
    </div>
</nav>

<main>
<?php
// Show flash messages if any
$flashError = getFlash('flash_error');
$flashSuccess = getFlash('flash_success');
if ($flashError): ?>
    <div class="container mt-md">
        <div class="alert alert-error"><?= sanitize($flashError) ?></div>
    </div>
<?php endif;
if ($flashSuccess): ?>
    <div class="container mt-md">
        <div class="alert alert-success"><?= sanitize($flashSuccess) ?></div>
    </div>
<?php endif; ?>
