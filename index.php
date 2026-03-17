<?php
/**
 * CodeVault — Main Router
 * 
 * Every request goes through this file. It loads config,
 * starts the session, reads the URL, and includes the right page.
 */

// Load environment variables and database config
require_once __DIR__ . '/config/env.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

// Start session with secure settings
session_start([
    'cookie_httponly' => true,
    'cookie_samesite' => 'Lax',
    'use_strict_mode' => true,
]);

// Get the requested URL path, default to empty string (home)
$url = isset($_GET['url']) ? trim($_GET['url'], '/') : '';

// Split the URL into segments: "edit/abc-123" → ["edit", "abc-123"]
$segments = $url ? explode('/', $url) : [];
$page = $segments[0] ?? '';
$param = $segments[1] ?? null;

// Base path for includes (so pages can reference project root)
define('BASE_PATH', __DIR__);
define('BASE_URL', '/codevault');

// Route to the correct page
switch ($page) {
    case '':
        require BASE_PATH . '/pages/home.php';
        break;

    case 'register':
        if (isLoggedIn()) { redirect(BASE_URL . '/dashboard'); }
        require BASE_PATH . '/pages/register.php';
        break;

    case 'login':
        if (isLoggedIn()) { redirect(BASE_URL . '/dashboard'); }
        require BASE_PATH . '/pages/login.php';
        break;

    case 'logout':
        logoutUser();
        redirect(BASE_URL . '/');
        break;

    case 'dashboard':
        requireLogin();
        require BASE_PATH . '/pages/dashboard.php';
        break;

    case 'new':
        requireLogin();
        require BASE_PATH . '/pages/new-snippet.php';
        break;

    case 'edit':
        requireLogin();
        if (!$param) { redirect(BASE_URL . '/dashboard'); }
        $snippetId = $param;
        require BASE_PATH . '/pages/edit-snippet.php';
        break;

    case 'snippet':
        if (!$param) { redirect(BASE_URL . '/explore'); }
        $snippetId = $param;
        require BASE_PATH . '/pages/snippet.php';
        break;

    case 'u':
        if (!$param) { redirect(BASE_URL . '/explore'); }
        $profileUsername = $param;
        require BASE_PATH . '/pages/profile.php';
        break;

    case 'explore':
        require BASE_PATH . '/pages/explore.php';
        break;

    case 'docs':
        require BASE_PATH . '/pages/api-docs.php';
        break;

    case 'settings':
        requireLogin();
        require BASE_PATH . '/pages/settings.php';
        break;

    case 'api':
        // Route API requests: /api/v1/snippets or /api/v1/snippets/{id}
        if (($segments[1] ?? '') === 'v1' && ($segments[2] ?? '') === 'snippets') {
            $apiSnippetId = $segments[3] ?? null;
            require BASE_PATH . '/api/v1/snippets.php';
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'API endpoint not found']);
        }
        break;

    default:
        // 404 page
        http_response_code(404);
        $pageTitle = 'Page Not Found';
        require BASE_PATH . '/includes/header.php';
        echo '<div class="container" style="text-align:center; padding: 4rem 1rem;">';
        echo '<h1 style="font-size: 3rem; margin-bottom: 1rem;">404</h1>';
        echo '<p class="text-secondary" style="margin-bottom: 2rem;">The page you\'re looking for doesn\'t exist.</p>';
        echo '<a href="' . BASE_URL . '/" class="btn btn-primary">Go Home</a>';
        echo '</div>';
        require BASE_PATH . '/includes/footer.php';
        break;
}
