<?php
/**
 * Public Profile Page
 * 
 * Shows a user's public profile with their snippets.
 * $profileUsername is set by the router.
 */

$pdo = Database::connect();

// Fetch user by username
$stmt = $pdo->prepare('SELECT id, username, bio, website, created_at FROM users WHERE username = :username');
$stmt->execute([':username' => $profileUsername]);
$profileUser = $stmt->fetch();

if (!$profileUser) {
    http_response_code(404);
    $pageTitle = 'User Not Found';
    require BASE_PATH . '/includes/header.php';
    echo '<div class="container"><div class="empty-state">';
    echo '<h3>User not found</h3>';
    echo '<p>No user with that username exists.</p>';
    echo '<a href="' . BASE_URL . '/explore" class="btn btn-primary">Browse Snippets</a>';
    echo '</div></div>';
    require BASE_PATH . '/includes/footer.php';
    exit;
}

$pageTitle = $profileUser['username'] . "'s Profile";

// Fetch stats
$stmt = $pdo->prepare('SELECT COUNT(*) as total FROM snippets WHERE user_id = :uid AND is_public = true');
$stmt->execute([':uid' => $profileUser['id']]);
$publicCount = $stmt->fetch()['total'] ?? 0;

$stmt = $pdo->prepare('
    SELECT COUNT(*) as total FROM stars 
    WHERE snippet_id IN (SELECT id FROM snippets WHERE user_id = :uid)
');
$stmt->execute([':uid' => $profileUser['id']]);
$totalStars = $stmt->fetch()['total'] ?? 0;

// Fetch public snippets with pagination
$perPage     = 20;
$currentPage = max(1, (int)($_GET['page'] ?? 1));
$offset      = ($currentPage - 1) * $perPage;

$countStmt = $pdo->prepare('SELECT COUNT(*) FROM snippets WHERE user_id = :uid AND is_public = true');
$countStmt->execute([':uid' => $profileUser['id']]);
$totalSnippets = (int)$countStmt->fetchColumn();
$totalPages    = (int)ceil($totalSnippets / $perPage);

$stmt = $pdo->prepare('
    SELECT s.id, s.title, s.language, s.tags, s.view_count, s.created_at,
           (SELECT COUNT(*) FROM stars WHERE snippet_id = s.id) as star_count
    FROM snippets s
    WHERE s.user_id = :uid AND s.is_public = true
    ORDER BY s.created_at DESC
    LIMIT :limit OFFSET :offset
');
$stmt->bindValue(':uid',    $profileUser['id']);
$stmt->bindValue(':limit',  $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset,  PDO::PARAM_INT);
$stmt->execute();
$snippets = $stmt->fetchAll();

require BASE_PATH . '/includes/header.php';
?>

<div class="container">
    <!-- Profile Header -->
    <div class="card mb-xl" style="display: flex; align-items: center; gap: var(--space-xl); flex-wrap: wrap;">
        <!-- Avatar placeholder -->
        <div style="width: 80px; height: 80px; border-radius: var(--radius-full); background: var(--accent-bg); display: flex; align-items: center; justify-content: center; font-size: 2rem; font-weight: 700; color: var(--accent); flex-shrink: 0;">
            <?= strtoupper(substr($profileUser['username'], 0, 1)) ?>
        </div>
        <div style="flex: 1;">
            <h1 style="font-size: 1.5rem; margin-bottom: var(--space-xs);"><?= sanitize($profileUser['username']) ?></h1>
            <?php if (!empty($profileUser['bio'])): ?>
                <p class="text-secondary mb-sm"><?= nl2br(sanitize($profileUser['bio'])) ?></p>
            <?php endif; ?>
            <div class="flex gap-md text-muted" style="font-size: 0.85rem;">
                <span><?= (int)$publicCount ?> public snippets</span>
                <span>★ <?= (int)$totalStars ?> stars</span>
                <span>Joined <?= timeAgo($profileUser['created_at']) ?></span>
                <?php if (!empty($profileUser['website'])): ?>
                    <a href="<?= sanitize($profileUser['website']) ?>" target="_blank" rel="noopener"><?= sanitize(parse_url($profileUser['website'], PHP_URL_HOST) ?: $profileUser['website']) ?></a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Public Snippets -->
    <?php if (!empty($snippets)): ?>
        <div class="section-header">
            <h2>Public Snippets</h2>
        </div>
        <div class="snippet-grid">
            <?php foreach ($snippets as $snippet): ?>
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <a href="<?= BASE_URL ?>/snippet/<?= sanitize($snippet['id']) ?>">
                                <?= sanitize($snippet['title']) ?>
                            </a>
                        </h3>
                        <span class="badge badge-language"><?= sanitize($snippet['language']) ?></span>
                    </div>

                    <?php if (!empty($snippet['tags'])): ?>
                        <div class="tags">
                            <?php foreach (array_slice(explode(',', $snippet['tags']), 0, 4) as $tag): ?>
                                <span class="badge badge-tag"><?= sanitize(trim($tag)) ?></span>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <div class="card-meta">
                        <span>★ <?= (int)$snippet['star_count'] ?></span>
                        <span><?= (int)$snippet['view_count'] ?> views</span>
                        <span><?= timeAgo($snippet['created_at']) ?></span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php if ($totalPages > 1): ?>
            <div class="flex justify-center gap-sm mt-xl">
                <?php if ($currentPage > 1): ?>
                    <a href="?page=<?= $currentPage - 1 ?>" class="btn btn-secondary btn-sm">← Prev</a>
                <?php endif; ?>
                <span class="btn btn-ghost btn-sm" style="cursor:default;">
                    Page <?= $currentPage ?> of <?= $totalPages ?>
                </span>
                <?php if ($currentPage < $totalPages): ?>
                    <a href="?page=<?= $currentPage + 1 ?>" class="btn btn-secondary btn-sm">Next →</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    <?php else: ?>
        <div class="empty-state">
            <h3>No public snippets yet</h3>
            <p>This user hasn't shared any public snippets.</p>
        </div>
    <?php endif; ?>
</div>

<?php require BASE_PATH . '/includes/footer.php'; ?>
