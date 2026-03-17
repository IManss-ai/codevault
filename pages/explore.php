<?php
/**
 * Explore Page
 */

$pageTitle = 'Explore';
$page      = 'explore';
$pdo       = Database::connect();

$languageFilter = trim($_GET['lang'] ?? '');
$searchQuery    = trim($_GET['q'] ?? '');
$perPage        = 24;
$currentPage    = max(1, (int)($_GET['page'] ?? 1));
$offset         = ($currentPage - 1) * $perPage;

$where  = 'WHERE s.is_public = true';
$params = [];

if (!empty($languageFilter)) {
    $where .= ' AND s.language = :lang';
    $params[':lang'] = $languageFilter;
}
if (!empty($searchQuery)) {
    $where .= ' AND (s.title ILIKE :q OR s.tags ILIKE :q OR s.code ILIKE :q)';
    $params[':q'] = '%' . $searchQuery . '%';
}

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM snippets s JOIN users u ON s.user_id = u.id $where");
$countStmt->execute($params);
$totalSnippets = (int)$countStmt->fetchColumn();
$totalPages    = (int)ceil($totalSnippets / $perPage);

$sql = "
    SELECT s.id, s.title, s.language, s.tags, s.code, s.created_at, s.view_count,
           u.username,
           (SELECT COUNT(*) FROM stars WHERE snippet_id = s.id) as star_count
    FROM snippets s
    JOIN users u ON s.user_id = u.id
    $where
    ORDER BY s.created_at DESC
    LIMIT :limit OFFSET :offset
";

$stmt = $pdo->prepare($sql);
foreach ($params as $key => $val) $stmt->bindValue($key, $val);
$stmt->bindValue(':limit',  $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset,  PDO::PARAM_INT);
$stmt->execute();
$snippets = $stmt->fetchAll();

$langStmt = $pdo->query('SELECT DISTINCT language FROM snippets WHERE is_public = true ORDER BY language');
$availableLanguages = $langStmt->fetchAll(PDO::FETCH_COLUMN);

require BASE_PATH . '/includes/header.php';
?>

<div class="page-header">
    <h1>Explore</h1>
</div>

<!-- Search Bar -->
<form method="GET" action="<?= BASE_URL ?>/explore" class="search-bar" style="margin-bottom: var(--space-lg);">
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <circle cx="11" cy="11" r="8"></circle>
        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
    </svg>
    <input type="text" name="q" value="<?= sanitize($searchQuery) ?>" placeholder="Search public snippets...">
    <?php if ($languageFilter): ?>
        <input type="hidden" name="lang" value="<?= sanitize($languageFilter) ?>">
    <?php endif; ?>
</form>

<!-- Language Filter Pills -->
<?php if (!empty($availableLanguages)): ?>
<div style="display: flex; gap: 0.375rem; flex-wrap: wrap; margin-bottom: var(--space-xl);">
    <a href="<?= BASE_URL ?>/explore<?= !empty($searchQuery) ? '?q=' . urlencode($searchQuery) : '' ?>"
       class="btn btn-sm <?= empty($languageFilter) ? 'btn-primary' : 'btn-secondary' ?>">All</a>
    <?php foreach (array_slice($availableLanguages, 0, 10) as $lang): ?>
        <a href="<?= BASE_URL ?>/explore?lang=<?= urlencode($lang) ?><?= !empty($searchQuery) ? '&q=' . urlencode($searchQuery) : '' ?>"
           class="btn btn-sm <?= $languageFilter === $lang ? 'btn-primary' : 'btn-secondary' ?>">
            <?= sanitize($lang) ?>
        </a>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php if (!empty($snippets)): ?>
    <!-- 2-column grid -->
    <div class="snippet-grid" style="grid-template-columns: repeat(2, 1fr); gap: 12px;">
        <?php foreach ($snippets as $snippet): ?>
            <div class="card snippet-card" data-language="<?= sanitize($snippet['language']) ?>" style="display:flex; flex-direction:column; gap:0.5rem;">
                <div style="display:flex; align-items:flex-start; justify-content:space-between; gap:0.5rem;">
                    <h3 class="card-title" style="min-width:0;">
                        <a href="<?= BASE_URL ?>/snippet/<?= sanitize($snippet['id']) ?>">
                            <?= sanitize($snippet['title']) ?>
                        </a>
                    </h3>
                    <span style="font-size:0.73rem; color:var(--text-hint); flex-shrink:0;">★ <?= (int)$snippet['star_count'] ?></span>
                </div>

                <?php if (!empty($snippet['code'])): ?>
                    <div class="snippet-preview"><?= sanitize(truncate($snippet['code'], 100)) ?></div>
                <?php endif; ?>

                <div style="display:flex; align-items:center; justify-content:space-between; margin-top:auto; padding-top:0.25rem;">
                    <span class="badge badge-language"><?= sanitize($snippet['language']) ?></span>
                    <a href="<?= BASE_URL ?>/u/<?= sanitize($snippet['username']) ?>"
                       style="font-size:0.73rem; color:var(--text-muted); text-decoration:none;">
                        <div style="display:flex; align-items:center; gap:6px;">
                            <div style="width:18px; height:18px; border-radius:50%; background:var(--bg-tertiary); border:1px solid var(--border); display:flex; align-items:center; justify-content:center; font-size:0.6rem; color:var(--text-muted); font-weight:600; flex-shrink:0;"><?= strtoupper(substr(sanitize($snippet['username']), 0, 1)) ?></div>
                            <span><?= sanitize($snippet['username']) ?></span>
                        </div>
                    </a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <?php if ($totalPages > 1):
        $pageParams = array_filter(['q' => $searchQuery, 'lang' => $languageFilter]);
    ?>
        <div class="flex justify-center gap-sm mt-xl">
            <?php if ($currentPage > 1): ?>
                <a href="?<?= http_build_query(array_merge($pageParams, ['page' => $currentPage - 1])) ?>"
                   class="btn btn-secondary btn-sm">← Prev</a>
            <?php endif; ?>
            <span class="btn btn-ghost btn-sm" style="cursor:default;">
                Page <?= $currentPage ?> of <?= $totalPages ?>
            </span>
            <?php if ($currentPage < $totalPages): ?>
                <a href="?<?= http_build_query(array_merge($pageParams, ['page' => $currentPage + 1])) ?>"
                   class="btn btn-secondary btn-sm">Next →</a>
            <?php endif; ?>
        </div>
    <?php endif; ?>

<?php else: ?>
    <div class="empty-state">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="11" cy="11" r="8"></circle>
            <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
        </svg>
        <h3>No snippets found</h3>
        <p>
            <?php if (!empty($searchQuery) || !empty($languageFilter)): ?>
                Try adjusting your search or filter.
            <?php else: ?>
                Be the first to share a snippet!
            <?php endif; ?>
        </p>
        <?php if (isLoggedIn()): ?>
            <a href="<?= BASE_URL ?>/new" class="btn btn-primary">Create a Snippet</a>
        <?php else: ?>
            <a href="<?= BASE_URL ?>/register" class="btn btn-primary">Sign Up to Share</a>
        <?php endif; ?>
    </div>
<?php endif; ?>

<?php require BASE_PATH . '/includes/footer.php'; ?>
