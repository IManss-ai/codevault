<?php
/**
 * Dashboard Page
 */

$pageTitle = 'Dashboard';
$page      = 'dashboard';

$pdo    = Database::connect();
$userId = currentUserId();

// Stats
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
$perPage     = 20;
$currentPage = max(1, (int)($_GET['page'] ?? 1));
$offset      = ($currentPage - 1) * $perPage;
$totalPages  = (int)ceil($totalSnippets / $perPage);

// Language / tag filters from sidebar links
$langFilter = trim($_GET['lang'] ?? '');
$tagFilter  = trim($_GET['tag'] ?? '');
$where  = 'WHERE s.user_id = :uid';
$params = [':uid' => $userId];
if ($langFilter) { $where .= ' AND s.language = :lang'; $params[':lang'] = $langFilter; }
if ($tagFilter)  { $where .= ' AND s.tags ILIKE :tag';  $params[':tag']  = '%' . $tagFilter . '%'; }

// Re-count with filters
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM snippets s $where");
$countStmt->execute($params);
$filteredTotal = (int)$countStmt->fetchColumn();
$totalPages = (int)ceil($filteredTotal / $perPage);

$stmt = $pdo->prepare("
    SELECT s.*,
           (SELECT COUNT(*) FROM stars WHERE snippet_id = s.id) as star_count
    FROM snippets s
    $where
    ORDER BY s.updated_at DESC
    LIMIT :limit OFFSET :offset
");
foreach ($params as $k => $v) $stmt->bindValue($k, $v);
$stmt->bindValue(':limit',  $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset,  PDO::PARAM_INT);
$stmt->execute();
$snippets = $stmt->fetchAll();

require BASE_PATH . '/includes/header.php';
?>

<!-- Page Header -->
<div class="page-header">
    <h1>Your vault
        <?php if ($langFilter): ?>
            <span style="font-size:0.9rem; font-weight:400; color:var(--text-muted); margin-left:0.5rem;"><?= sanitize($langFilter) ?></span>
        <?php elseif ($tagFilter): ?>
            <span style="font-size:0.9rem; font-weight:400; color:var(--text-muted); margin-left:0.5rem;">#<?= sanitize($tagFilter) ?></span>
        <?php endif; ?>
    </h1>
</div>

<!-- Stats Row -->
<div class="stats-bar" style="margin-bottom: var(--space-lg);">
    <div class="stat-item">
        <div class="stat-value"><?= (int)$totalSnippets ?></div>
        <div class="stat-label">Total</div>
    </div>
    <div class="stat-item">
        <div class="stat-value"><?= (int)$publicSnippets ?></div>
        <div class="stat-label">Public</div>
    </div>
    <div class="stat-item">
        <div class="stat-value"><?= (int)$totalStars ?></div>
        <div class="stat-label">Stars</div>
    </div>
</div>

<?php if (!empty($snippets)): ?>

    <!-- Search -->
    <div class="search-bar" style="margin-bottom: var(--space-lg);">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="11" cy="11" r="8"></circle>
            <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
        </svg>
        <input type="text" id="snippet-search" placeholder="Search your snippets...">
    </div>

    <!-- Snippet List (vertical) -->
    <div style="display: flex; flex-direction: column; gap: 0.5rem;">
        <?php foreach ($snippets as $snippet): ?>
            <div class="card snippet-card"
                 data-title="<?= sanitize($snippet['title']) ?>"
                 data-tags="<?= sanitize($snippet['tags'] ?? '') ?>"
                 data-language="<?= sanitize($snippet['language']) ?>">

                <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 1rem;">
                    <div style="min-width: 0;">
                        <h3 class="card-title" style="margin-bottom: 0.375rem;">
                            <a href="<?= BASE_URL ?>/snippet/<?= sanitize($snippet['id']) ?>">
                                <?= sanitize($snippet['title']) ?>
                            </a>
                        </h3>
                        <div style="display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap;">
                            <span class="badge badge-language"><?= sanitize($snippet['language']) ?></span>
                            <?php if ($snippet['is_public']): ?>
                                <span class="badge badge-public">Public</span>
                            <?php else: ?>
                                <span class="badge badge-private">Private</span>
                            <?php endif; ?>
                            <span style="font-size:0.73rem; color:var(--text-hint);">★ <?= (int)$snippet['star_count'] ?></span>
                            <span style="font-size:0.73rem; color:var(--text-hint);">·</span>
                            <span style="font-size:0.73rem; color:var(--text-hint);"><?= timeAgo($snippet['updated_at']) ?></span>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div style="display: flex; gap: 1rem; flex-shrink: 0; padding-top: 2px;">
                        <a href="<?= BASE_URL ?>/edit/<?= sanitize($snippet['id']) ?>"
                           style="font-size:0.78rem; color:var(--text-muted); text-decoration:none; transition:color var(--transition);"
                           onmouseover="this.style.color='var(--text-secondary)'"
                           onmouseout="this.style.color='var(--text-muted)'">Edit</a>
                        <form method="POST" action="<?= BASE_URL ?>/edit/<?= sanitize($snippet['id']) ?>" style="display:inline; margin:0;">
                            <input type="hidden" name="csrf_token" value="<?= generateCSRF() ?>">
                            <input type="hidden" name="action" value="delete">
                            <button type="submit"
                                    data-confirm="Delete this snippet?"
                                    style="font-size:0.78rem; color:var(--text-muted); background:none; border:none; cursor:pointer; padding:0; font-family:inherit; transition:color var(--transition);"
                                    onmouseover="this.style.color='var(--danger)'"
                                    onmouseout="this.style.color='var(--text-muted)'">Delete</button>
                        </form>
                    </div>
                </div>

                <?php if (!empty($snippet['code'])): ?>
                    <div class="snippet-preview" style="max-height: 26px; margin-top: 0.5rem;">
                        <?= sanitize(truncate($snippet['code'], 120)) ?>
                    </div>
                <?php endif; ?>

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
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="16 18 22 12 16 6"></polyline>
            <polyline points="8 6 2 12 8 18"></polyline>
        </svg>
        <h3>Your vault is empty</h3>
        <p>Start building your personal code library. Save snippets you'll want to find later.</p>
        <a href="<?= BASE_URL ?>/new" class="btn btn-primary btn-lg">Create your first snippet</a>
    </div>
<?php endif; ?>

<?php require BASE_PATH . '/includes/footer.php'; ?>
