<?php
/**
 * Explore Page
 * 
 * Browse trending and recent public snippets.
 * Supports filtering by language via query string.
 */

$pageTitle = 'Explore';
$pdo = Database::connect();

$languageFilter = trim($_GET['lang'] ?? '');
$searchQuery    = trim($_GET['q'] ?? '');
$perPage        = 24;
$currentPage    = max(1, (int)($_GET['page'] ?? 1));
$offset         = ($currentPage - 1) * $perPage;

// Base WHERE conditions (shared by count + data queries)
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

// Count total for pagination
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM snippets s JOIN users u ON s.user_id = u.id $where");
$countStmt->execute($params);
$totalSnippets = (int)$countStmt->fetchColumn();
$totalPages    = (int)ceil($totalSnippets / $perPage);

// Fetch paginated snippets
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
foreach ($params as $key => $val) {
    $stmt->bindValue($key, $val);
}
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$snippets = $stmt->fetchAll();

// Get language list for filter
$langStmt = $pdo->query('SELECT DISTINCT language FROM snippets WHERE is_public = true ORDER BY language');
$availableLanguages = $langStmt->fetchAll(PDO::FETCH_COLUMN);

require BASE_PATH . '/includes/header.php';
?>

<div class="container">
    <div class="page-header">
        <h1>Explore Snippets</h1>
    </div>

    <!-- Search & Filter Bar -->
    <div class="flex gap-md mb-xl" style="flex-wrap: wrap;">
        <form method="GET" action="<?= BASE_URL ?>/explore" class="search-bar" style="flex: 1; min-width: 250px; margin-bottom: 0;">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="11" cy="11" r="8"></circle>
                <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
            </svg>
            <input type="text" name="q" value="<?= sanitize($searchQuery) ?>"
                   placeholder="Search public snippets...">
            <?php if ($languageFilter): ?>
                <input type="hidden" name="lang" value="<?= sanitize($languageFilter) ?>">
            <?php endif; ?>
        </form>

        <?php if (!empty($availableLanguages)): ?>
            <div class="flex gap-sm items-center" style="flex-wrap: wrap;">
                <a href="<?= BASE_URL ?>/explore" class="btn btn-sm <?= empty($languageFilter) ? 'btn-primary' : 'btn-secondary' ?>">All</a>
                <?php foreach (array_slice($availableLanguages, 0, 8) as $lang): ?>
                    <a href="<?= BASE_URL ?>/explore?lang=<?= urlencode($lang) ?>" 
                       class="btn btn-sm <?= $languageFilter === $lang ? 'btn-primary' : 'btn-secondary' ?>">
                        <?= sanitize($lang) ?>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <?php if (!empty($snippets)): ?>
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

                    <?php if (!empty($snippet['code'])): ?>
                        <div class="snippet-preview"><?= sanitize(truncate($snippet['code'], 100)) ?></div>
                    <?php endif; ?>

                    <div class="card-meta">
                        <span><a href="<?= BASE_URL ?>/u/<?= sanitize($snippet['username']) ?>"><?= sanitize($snippet['username']) ?></a></span>
                        <span>★ <?= (int)$snippet['star_count'] ?></span>
                        <span><?= (int)$snippet['view_count'] ?> views</span>
                        <span><?= timeAgo($snippet['created_at']) ?></span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <?php if ($totalPages > 1):
            // Preserve existing query params in pagination links
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
</div>

<?php require BASE_PATH . '/includes/footer.php'; ?>
