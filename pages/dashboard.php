<?php
/**
 * Dashboard Page
 * 
 * Shows the logged-in user's snippet library with stats,
 * search, and snippet cards.
 */

$pageTitle = 'Dashboard';

$pdo = Database::connect();
$userId = currentUserId();

// Fetch user stats
$stmt = $pdo->prepare('SELECT COUNT(*) as total FROM snippets WHERE user_id = :uid');
$stmt->execute([':uid' => $userId]);
$totalSnippets = $stmt->fetch()['total'] ?? 0;

$stmt = $pdo->prepare('SELECT COUNT(*) as total FROM snippets WHERE user_id = :uid AND is_public = true');
$stmt->execute([':uid' => $userId]);
$publicSnippets = $stmt->fetch()['total'] ?? 0;

$stmt = $pdo->prepare('
    SELECT COUNT(*) as total FROM stars 
    WHERE snippet_id IN (SELECT id FROM snippets WHERE user_id = :uid)
');
$stmt->execute([':uid' => $userId]);
$totalStars = $stmt->fetch()['total'] ?? 0;

// Pagination
$perPage = 20;
$currentPage = max(1, (int)($_GET['page'] ?? 1));
$offset = ($currentPage - 1) * $perPage;
$totalPages = (int)ceil($totalSnippets / $perPage);

// Fetch paginated user snippets
$stmt = $pdo->prepare('
    SELECT s.*,
           (SELECT COUNT(*) FROM stars WHERE snippet_id = s.id) as star_count
    FROM snippets s
    WHERE s.user_id = :uid
    ORDER BY s.updated_at DESC
    LIMIT :limit OFFSET :offset
');
$stmt->bindValue(':uid', $userId);
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$snippets = $stmt->fetchAll();

require BASE_PATH . '/includes/header.php';
?>

<div class="container">
    <!-- Page Header -->
    <div class="page-header">
        <h1>Welcome back, <?= sanitize(currentUsername()) ?></h1>
        <a href="<?= BASE_URL ?>/new" class="btn btn-primary">+ New Snippet</a>
    </div>

    <!-- Stats Bar -->
    <div class="stats-bar">
        <div class="stat-item">
            <div class="stat-value"><?= (int)$totalSnippets ?></div>
            <div class="stat-label">Total Snippets</div>
        </div>
        <div class="stat-item">
            <div class="stat-value"><?= (int)$publicSnippets ?></div>
            <div class="stat-label">Public Snippets</div>
        </div>
        <div class="stat-item">
            <div class="stat-value"><?= (int)$totalStars ?></div>
            <div class="stat-label">Stars Received</div>
        </div>
    </div>

    <?php if (!empty($snippets)): ?>
        <!-- Search Bar -->
        <div class="search-bar">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="11" cy="11" r="8"></circle>
                <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
            </svg>
            <input 
                type="text" 
                id="snippet-search" 
                placeholder="Search your snippets by title, tag, or language..."
            >
        </div>

        <!-- Snippet Grid -->
        <div class="snippet-grid">
            <?php foreach ($snippets as $snippet): ?>
                <div class="card snippet-card" 
                     data-title="<?= sanitize($snippet['title']) ?>"
                     data-tags="<?= sanitize($snippet['tags'] ?? '') ?>"
                     data-language="<?= sanitize($snippet['language']) ?>">
                    
                    <div class="card-header">
                        <h3 class="card-title">
                            <a href="<?= BASE_URL ?>/snippet/<?= sanitize($snippet['id']) ?>">
                                <?= sanitize($snippet['title']) ?>
                            </a>
                        </h3>
                        <div class="card-actions">
                            <a href="<?= BASE_URL ?>/edit/<?= sanitize($snippet['id']) ?>" 
                               class="btn btn-ghost btn-sm" title="Edit">
                                ✏️
                            </a>
                            <form method="POST" action="<?= BASE_URL ?>/edit/<?= sanitize($snippet['id']) ?>" style="display:inline;">
                                <input type="hidden" name="csrf_token" value="<?= generateCSRF() ?>">
                                <input type="hidden" name="action" value="delete">
                                <button type="submit" class="btn btn-ghost btn-sm" 
                                        data-confirm="Are you sure you want to delete this snippet?"
                                        title="Delete">
                                    🗑️
                                </button>
                            </form>
                        </div>
                    </div>

                    <div class="flex items-center gap-sm flex-wrap">
                        <span class="badge badge-language"><?= sanitize($snippet['language']) ?></span>
                        <?php if ($snippet['is_public']): ?>
                            <span class="badge badge-public">Public</span>
                        <?php else: ?>
                            <span class="badge badge-private">Private</span>
                        <?php endif; ?>
                    </div>

                    <?php if (!empty($snippet['tags'])): ?>
                        <div class="tags mt-sm">
                            <?php foreach (explode(',', $snippet['tags']) as $tag): ?>
                                <span class="badge badge-tag"><?= sanitize(trim($tag)) ?></span>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($snippet['code'])): ?>
                        <div class="snippet-preview"><?= sanitize(truncate($snippet['code'], 120)) ?></div>
                    <?php endif; ?>

                    <div class="card-meta">
                        <span>★ <?= (int)$snippet['star_count'] ?></span>
                        <span><?= (int)$snippet['view_count'] ?> views</span>
                        <span><?= timeAgo($snippet['updated_at']) ?></span>
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
        <!-- Empty State -->
        <div class="empty-state">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="16 18 22 12 16 6"></polyline>
                <polyline points="8 6 2 12 8 18"></polyline>
            </svg>
            <h3>Your vault is empty</h3>
            <p>Start building your personal code library. Save snippets you'll want to find later.</p>
            <a href="<?= BASE_URL ?>/new" class="btn btn-primary btn-lg">Create Your First Snippet</a>
        </div>
    <?php endif; ?>
</div>

<?php require BASE_PATH . '/includes/footer.php'; ?>
