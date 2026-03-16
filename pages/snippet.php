<?php
/**
 * Single Snippet View
 * 
 * Shows a single snippet with full syntax highlighting,
 * star button, fork button, and embed code.
 * $snippetId is set by the router.
 */

$pdo = Database::connect();

// Fetch the snippet with author info
$stmt = $pdo->prepare('
    SELECT s.*, u.username, u.id as author_id
    FROM snippets s
    JOIN users u ON s.user_id = u.id
    WHERE s.id = :id
');
$stmt->execute([':id' => $snippetId]);
$snippet = $stmt->fetch();

if (!$snippet) {
    http_response_code(404);
    $pageTitle = 'Snippet Not Found';
    require BASE_PATH . '/includes/header.php';
    echo '<div class="container"><div class="empty-state">';
    echo '<h3>Snippet not found</h3>';
    echo '<p>This snippet may have been deleted or made private.</p>';
    echo '<a href="' . BASE_URL . '/explore" class="btn btn-primary">Browse Snippets</a>';
    echo '</div></div>';
    require BASE_PATH . '/includes/footer.php';
    exit;
}

// Check access: private snippets only visible to owner
if (!$snippet['is_public'] && currentUserId() !== $snippet['user_id']) {
    http_response_code(403);
    $pageTitle = 'Private Snippet';
    require BASE_PATH . '/includes/header.php';
    echo '<div class="container"><div class="empty-state">';
    echo '<h3>This snippet is private</h3>';
    echo '<p>Only the owner can view private snippets.</p>';
    echo '<a href="' . BASE_URL . '/explore" class="btn btn-primary">Browse Snippets</a>';
    echo '</div></div>';
    require BASE_PATH . '/includes/footer.php';
    exit;
}

// Increment view count only on GET requests and only for non-owners
if ($_SERVER['REQUEST_METHOD'] === 'GET' && currentUserId() !== $snippet['user_id']) {
    $stmt = $pdo->prepare('UPDATE snippets SET view_count = view_count + 1 WHERE id = :id');
    $stmt->execute([':id' => $snippetId]);
}

// ── Embed mode ────────────────────────────
if (isset($_GET['embed']) && $_GET['embed'] === '1' && $snippet['is_public']) {
    $prismLang = prismLanguage($snippet['language']);
    header('X-Frame-Options: ALLOWALL');
    ?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= sanitize($snippet['title']) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;500&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/themes/prism-tomorrow.min.css" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Inter', sans-serif;
            background: #0d1117;
            color: #e6edf3;
            height: 100vh;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }
        .embed-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0.5rem 0.875rem;
            background: #161b22;
            border-bottom: 1px solid #30363d;
            flex-shrink: 0;
            gap: 0.5rem;
        }
        .embed-title {
            font-size: 0.8rem;
            font-weight: 600;
            color: #e6edf3;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .embed-meta {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            flex-shrink: 0;
        }
        .embed-badge {
            font-size: 0.7rem;
            font-weight: 500;
            padding: 0.15rem 0.5rem;
            border-radius: 9999px;
            background: rgba(88,166,255,0.1);
            color: #58a6ff;
            border: 1px solid rgba(88,166,255,0.3);
            white-space: nowrap;
        }
        .embed-link {
            font-size: 0.7rem;
            color: #58a6ff;
            text-decoration: none;
            white-space: nowrap;
        }
        .embed-link:hover { text-decoration: underline; }
        .embed-code {
            flex: 1;
            overflow: auto;
            padding: 0;
        }
        pre[class*="language-"] {
            margin: 0;
            border-radius: 0;
            height: 100%;
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.82rem;
            line-height: 1.6;
        }
        code[class*="language-"] {
            font-family: 'JetBrains Mono', monospace;
        }
    </style>
</head>
<body>
    <div class="embed-header">
        <span class="embed-title"><?= sanitize($snippet['title']) ?></span>
        <div class="embed-meta">
            <span class="embed-badge"><?= sanitize($snippet['language']) ?></span>
            <a href="<?= BASE_URL ?>/snippet/<?= sanitize($snippetId) ?>" target="_blank" class="embed-link">View on CodeVault ↗</a>
        </div>
    </div>
    <div class="embed-code">
        <pre><code class="language-<?= sanitize($prismLang) ?>"><?= sanitize($snippet['code']) ?></code></pre>
    </div>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/prism.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/plugins/autoloader/prism-autoloader.min.js"></script>
</body>
</html>
    <?php
    exit;
}

// Get star count
$stmt = $pdo->prepare('SELECT COUNT(*) as total FROM stars WHERE snippet_id = :id');
$stmt->execute([':id' => $snippetId]);
$starCount = $stmt->fetch()['total'] ?? 0;

// Check if current user has starred this snippet
$hasStarred = false;
if (isLoggedIn()) {
    $stmt = $pdo->prepare('SELECT id FROM stars WHERE user_id = :uid AND snippet_id = :sid');
    $stmt->execute([':uid' => currentUserId(), ':sid' => $snippetId]);
    $hasStarred = (bool)$stmt->fetch();
}

// Handle fork action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'fork' && isLoggedIn()) {
    if (validateCSRF($_POST['csrf_token'] ?? '')) {
        $stmt = $pdo->prepare('
            INSERT INTO snippets (user_id, title, description, code, language, tags, is_public, view_count, forked_from, created_at, updated_at)
            VALUES (:uid, :title, :desc, :code, :lang, :tags, false, 0, :forked_from, NOW(), NOW())
            RETURNING id
        ');
        $stmt->execute([
            ':uid'         => currentUserId(),
            ':title'       => $snippet['title'],
            ':desc'        => $snippet['description'],
            ':code'        => $snippet['code'],
            ':lang'        => $snippet['language'],
            ':tags'        => $snippet['tags'],
            ':forked_from' => $snippetId,
        ]);
        $forkId = $stmt->fetchColumn();
        setFlash('flash_success', 'Snippet forked to your vault!');
        redirect(BASE_URL . '/snippet/' . $forkId);
    }
}

// Handle star toggle via POST (non-JS fallback)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'star' && isLoggedIn()) {
    if (validateCSRF($_POST['csrf_token'] ?? '')) {
        if ($hasStarred) {
            $stmt = $pdo->prepare('DELETE FROM stars WHERE user_id = :uid AND snippet_id = :sid');
            $stmt->execute([':uid' => currentUserId(), ':sid' => $snippetId]);
        } else {
            $stmt = $pdo->prepare('INSERT INTO stars (user_id, snippet_id, created_at) VALUES (:uid, :sid, NOW())');
            $stmt->execute([':uid' => currentUserId(), ':sid' => $snippetId]);
        }
        redirect(BASE_URL . '/snippet/' . $snippetId);
    }
}

$pageTitle = $snippet['title'];
$prismLang = prismLanguage($snippet['language']);
require BASE_PATH . '/includes/header.php';
?>

<div class="container" style="max-width: 900px;">

    <!-- Snippet Header -->
    <div class="page-header">
        <div>
            <h1 style="font-size: 1.5rem; margin-bottom: var(--space-sm);"><?= sanitize($snippet['title']) ?></h1>
            <div class="flex items-center gap-md text-secondary" style="font-size: 0.875rem;">
                <a href="<?= BASE_URL ?>/u/<?= sanitize($snippet['username']) ?>"><?= sanitize($snippet['username']) ?></a>
                <span><?= timeAgo($snippet['created_at']) ?></span>
                <span><?= (int)$snippet['view_count'] ?> views</span>
                <?php if ($snippet['forked_from']): ?>
                    <span>Forked from <a href="<?= BASE_URL ?>/snippet/<?= sanitize($snippet['forked_from']) ?>">original</a></span>
                <?php endif; ?>
            </div>
        </div>

        <div class="flex gap-sm">
            <?php if (isLoggedIn()): ?>
                <!-- Star button -->
                <form method="POST" style="display:inline;">
                    <input type="hidden" name="csrf_token" value="<?= generateCSRF() ?>">
                    <input type="hidden" name="action" value="star">
                    <button type="submit" class="star-btn <?= $hasStarred ? 'starred' : '' ?>" data-snippet-id="<?= sanitize($snippetId) ?>">
                        <?= $hasStarred ? '★' : '☆' ?>
                        <span class="star-count"><?= (int)$starCount ?></span>
                    </button>
                </form>

                <?php if (currentUserId() !== $snippet['user_id']): ?>
                    <!-- Fork button -->
                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="csrf_token" value="<?= generateCSRF() ?>">
                        <input type="hidden" name="action" value="fork">
                        <button type="submit" class="btn btn-secondary btn-sm">Fork</button>
                    </form>
                <?php endif; ?>

                <?php if (currentUserId() === $snippet['user_id']): ?>
                    <a href="<?= BASE_URL ?>/edit/<?= sanitize($snippetId) ?>" class="btn btn-secondary btn-sm">Edit</a>
                <?php endif; ?>
            <?php else: ?>
                <span class="star-btn" style="cursor: default;">★ <?= (int)$starCount ?></span>
            <?php endif; ?>
        </div>
    </div>

    <!-- Language & Tags -->
    <div class="flex items-center gap-sm flex-wrap mb-lg">
        <span class="badge badge-language"><?= sanitize($snippet['language']) ?></span>
        <?php if ($snippet['is_public']): ?>
            <span class="badge badge-public">Public</span>
        <?php else: ?>
            <span class="badge badge-private">Private</span>
        <?php endif; ?>
        <?php if (!empty($snippet['tags'])): ?>
            <?php foreach (explode(',', $snippet['tags']) as $tag): ?>
                <span class="badge badge-tag"><?= sanitize(trim($tag)) ?></span>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Description -->
    <?php if (!empty($snippet['description'])): ?>
        <p class="text-secondary mb-lg"><?= nl2br(sanitize($snippet['description'])) ?></p>
    <?php endif; ?>

    <!-- Code Block -->
    <div class="code-block mb-lg">
        <div class="code-block-header">
            <span><?= sanitize($snippet['language']) ?></span>
            <button class="copy-btn">Copy</button>
        </div>
        <pre><code class="language-<?= sanitize($prismLang) ?>"><?= sanitize($snippet['code']) ?></code></pre>
    </div>

    <!-- Embed Code (for public snippets) -->
    <?php if ($snippet['is_public']): ?>
        <div class="card" style="margin-top: var(--space-xl);">
            <h3 class="card-title mb-sm" style="font-size: 0.9rem;">Embed this snippet</h3>
            <div class="code-block">
                <pre style="padding: var(--space-sm) var(--space-md); font-size: 0.8rem;"><code>&lt;iframe src="<?= BASE_URL ?>/snippet/<?= sanitize($snippetId) ?>?embed=1" width="100%" height="300" frameborder="0"&gt;&lt;/iframe&gt;</code></pre>
            </div>
        </div>
    <?php endif; ?>

</div>

<?php require BASE_PATH . '/includes/footer.php'; ?>
