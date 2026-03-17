<?php
/**
 * Single Snippet View
 * $snippetId is set by the router.
 */

$pdo = Database::connect();

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
    echo '<div style="max-width:600px;"><div class="empty-state"><h3>Snippet not found</h3>';
    echo '<p>This snippet may have been deleted or made private.</p>';
    echo '<a href="' . BASE_URL . '/explore" class="btn btn-primary">Browse Snippets</a></div></div>';
    require BASE_PATH . '/includes/footer.php';
    exit;
}

if (!$snippet['is_public'] && currentUserId() !== $snippet['user_id']) {
    http_response_code(403);
    $pageTitle = 'Private Snippet';
    require BASE_PATH . '/includes/header.php';
    echo '<div style="max-width:600px;"><div class="empty-state"><h3>This snippet is private</h3>';
    echo '<p>Only the owner can view private snippets.</p>';
    echo '<a href="' . BASE_URL . '/explore" class="btn btn-primary">Browse Snippets</a></div></div>';
    require BASE_PATH . '/includes/footer.php';
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && currentUserId() !== $snippet['user_id']) {
    $stmt = $pdo->prepare('UPDATE snippets SET view_count = view_count + 1 WHERE id = :id');
    $stmt->execute([':id' => $snippetId]);
}

// Embed mode
if (isset($_GET['embed']) && $_GET['embed'] === '1' && $snippet['is_public']) {
    $prismLang = prismLanguage($snippet['language']);
    header('X-Frame-Options: ALLOWALL');
    ?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= sanitize($snippet['title']) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/themes/prism-tomorrow.min.css" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: #0b0d14;
            color: #e2e4ea;
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
            background: #111420;
            border-bottom: 1px solid #1e2330;
            flex-shrink: 0;
            gap: 0.5rem;
        }
        .embed-title { font-size: 0.8rem; font-weight: 600; color: #e2e4ea; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .embed-meta { display: flex; align-items: center; gap: 0.5rem; flex-shrink: 0; }
        .embed-badge { font-size: 0.7rem; font-weight: 500; padding: 0.15rem 0.5rem; border-radius: 4px; background: #1a1d2a; color: #6b7080; white-space: nowrap; }
        .embed-link { font-size: 0.7rem; color: #6b8fc4; text-decoration: none; white-space: nowrap; }
        .embed-link:hover { text-decoration: underline; }
        .embed-code { flex: 1; overflow: auto; }
        pre[class*="language-"] { margin: 0; border-radius: 0; height: 100%; font-family: 'JetBrains Mono', monospace; font-size: 0.82rem; line-height: 1.6; }
        code[class*="language-"] { font-family: 'JetBrains Mono', monospace; }
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

$stmt = $pdo->prepare('SELECT COUNT(*) as total FROM stars WHERE snippet_id = :id');
$stmt->execute([':id' => $snippetId]);
$starCount = $stmt->fetch()['total'] ?? 0;

$hasStarred = false;
if (isLoggedIn()) {
    $stmt = $pdo->prepare('SELECT id FROM stars WHERE user_id = :uid AND snippet_id = :sid');
    $stmt->execute([':uid' => currentUserId(), ':sid' => $snippetId]);
    $hasStarred = (bool)$stmt->fetch();
}

// Fork action
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

// Star toggle (non-JS fallback)
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

<div style="max-width: 860px;">

    <!-- Title + actions row -->
    <div class="page-header">
        <h1 style="font-size: 1.35rem; letter-spacing: -0.02em;"><?= sanitize($snippet['title']) ?></h1>

        <div class="flex gap-sm" style="flex-shrink: 0;">
            <?php if (isLoggedIn()): ?>
                <!-- Star -->
                <form method="POST" style="display:inline; margin:0;">
                    <input type="hidden" name="csrf_token" value="<?= generateCSRF() ?>">
                    <input type="hidden" name="action" value="star">
                    <button type="submit" class="star-btn <?= $hasStarred ? 'starred' : '' ?>"
                            data-snippet-id="<?= sanitize($snippetId) ?>">
                        ★ <span class="star-count"><?= (int)$starCount ?></span>
                    </button>
                </form>

                <?php if (currentUserId() !== $snippet['user_id']): ?>
                    <!-- Fork -->
                    <form method="POST" style="display:inline; margin:0;">
                        <input type="hidden" name="csrf_token" value="<?= generateCSRF() ?>">
                        <input type="hidden" name="action" value="fork">
                        <button type="submit" class="btn btn-secondary btn-sm">Fork</button>
                    </form>
                <?php endif; ?>

                <?php if (currentUserId() === $snippet['user_id']): ?>
                    <a href="<?= BASE_URL ?>/edit/<?= sanitize($snippetId) ?>" class="btn btn-secondary btn-sm">Edit</a>
                <?php endif; ?>
            <?php else: ?>
                <span class="star-btn" style="cursor:default;">★ <?= (int)$starCount ?></span>
            <?php endif; ?>
        </div>
    </div>

    <!-- Author · time · views -->
    <div style="display: flex; align-items: center; gap: 0.75rem; font-size: 0.8rem; color: var(--text-muted); margin-top: -0.75rem; margin-bottom: var(--space-lg); flex-wrap: wrap;">
        <a href="<?= BASE_URL ?>/u/<?= sanitize($snippet['username']) ?>" style="color: var(--text-muted);">
            <?= sanitize($snippet['username']) ?>
        </a>
        <span>·</span>
        <span><?= timeAgo($snippet['created_at']) ?></span>
        <span>·</span>
        <span><?= (int)$snippet['view_count'] ?> views</span>
        <?php if ($snippet['forked_from']): ?>
            <span>· Forked from <a href="<?= BASE_URL ?>/snippet/<?= sanitize($snippet['forked_from']) ?>" style="color:var(--text-muted);">original</a></span>
        <?php endif; ?>
    </div>

    <!-- Language + visibility + tag badges -->
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
        <p class="text-secondary mb-lg" style="font-size: 0.875rem; line-height: 1.7;">
            <?= nl2br(sanitize($snippet['description'])) ?>
        </p>
    <?php endif; ?>

    <!-- Code Block -->
    <div class="code-block mb-lg">
        <div class="code-block-header">
            <span><?= sanitize($snippet['language']) ?></span>
            <button class="copy-btn">Copy</button>
        </div>
        <pre><code class="language-<?= sanitize($prismLang) ?>"><?= sanitize($snippet['code']) ?></code></pre>
    </div>

    <!-- Embed code -->
    <?php if ($snippet['is_public']): ?>
        <div class="card" style="margin-top: var(--space-xl);">
            <h3 class="card-title mb-sm" style="font-size: 0.85rem;">Embed this snippet</h3>
            <div class="code-block">
                <pre style="padding: var(--space-sm) var(--space-md); font-size: 0.78rem;"><code>&lt;iframe src="<?= BASE_URL ?>/snippet/<?= sanitize($snippetId) ?>?embed=1" width="100%" height="300" frameborder="0"&gt;&lt;/iframe&gt;</code></pre>
            </div>
        </div>
    <?php endif; ?>

</div>

<?php require BASE_PATH . '/includes/footer.php'; ?>
