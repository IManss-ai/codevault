<?php
/**
 * Sidebar Component
 *
 * Only rendered for logged-in users. Requires $pdo to be available.
 * Uses $page (set by router) to highlight the active nav item.
 */

$_sidebarPdo = isset($pdo) ? $pdo : Database::connect();
$_sidebarUid = currentUserId();

// User's languages with counts
$_langStmt = $_sidebarPdo->prepare(
    'SELECT language, COUNT(*) as cnt FROM snippets WHERE user_id = :uid GROUP BY language ORDER BY cnt DESC LIMIT 8'
);
$_langStmt->execute([':uid' => $_sidebarUid]);
$_sidebarLangs = $_langStmt->fetchAll();

// User's tags (parse comma-separated, count occurrences)
$_tagStmt = $_sidebarPdo->prepare(
    "SELECT tags FROM snippets WHERE user_id = :uid AND tags <> ''"
);
$_tagStmt->execute([':uid' => $_sidebarUid]);
$_tagCounts = [];
foreach ($_tagStmt->fetchAll(PDO::FETCH_COLUMN) as $_tagRow) {
    foreach (explode(',', $_tagRow) as $_t) {
        $_t = trim($_t);
        if ($_t !== '') $_tagCounts[$_t] = ($_tagCounts[$_t] ?? 0) + 1;
    }
}
arsort($_tagCounts);
$_topTags = array_slice($_tagCounts, 0, 8, true);

// Current route segment (set by router as $page)
$_activePage = $page ?? '';
?>
<aside class="app-sidebar" id="app-sidebar">

    <!-- + New Snippet -->
    <div class="sidebar-new-btn">
        <a href="<?= BASE_URL ?>/new">
            <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none"
                 stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
            </svg>
            New snippet
        </a>
    </div>

    <!-- Main nav -->
    <nav class="sidebar-nav">
        <a href="<?= BASE_URL ?>/dashboard"
           class="sidebar-nav-item <?= $_activePage === 'dashboard' ? 'active' : '' ?>">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                 stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                <rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/>
                <rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/>
            </svg>
            Dashboard
        </a>
        <a href="<?= BASE_URL ?>/explore"
           class="sidebar-nav-item <?= $_activePage === 'explore' ? 'active' : '' ?>">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                 stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"/>
                <polygon points="16.24 7.76 14.12 14.12 7.76 16.24 9.88 9.88 16.24 7.76"/>
            </svg>
            Explore
        </a>
        <a href="<?= BASE_URL ?>/docs"
           class="sidebar-nav-item <?= $_activePage === 'docs' ? 'active' : '' ?>">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                 stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                <polyline points="14 2 14 8 20 8"/>
                <line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/>
                <polyline points="10 9 9 9 8 9"/>
            </svg>
            API Docs
        </a>
    </nav>

    <div class="sidebar-divider"></div>

    <!-- Languages -->
    <?php if (!empty($_sidebarLangs)): ?>
        <div class="sidebar-section">
            <div class="sidebar-section-title">Languages</div>
            <?php foreach ($_sidebarLangs as $_lang): ?>
                <a href="<?= BASE_URL ?>/dashboard?lang=<?= urlencode($_lang['language']) ?>"
                   class="sidebar-section-item">
                    <span><?= sanitize($_lang['language']) ?></span>
                    <span class="sidebar-section-item-count"><?= (int)$_lang['cnt'] ?></span>
                </a>
            <?php endforeach; ?>
        </div>
        <div class="sidebar-divider"></div>
    <?php endif; ?>

    <!-- Tags -->
    <?php if (!empty($_topTags)): ?>
        <div class="sidebar-section">
            <div class="sidebar-section-title">Tags</div>
            <?php foreach ($_topTags as $_tag => $_cnt): ?>
                <a href="<?= BASE_URL ?>/dashboard?tag=<?= urlencode($_tag) ?>"
                   class="sidebar-section-item">
                    <span><?= sanitize($_tag) ?></span>
                    <span class="sidebar-section-item-count"><?= (int)$_cnt ?></span>
                </a>
            <?php endforeach; ?>
        </div>
        <div class="sidebar-divider"></div>
    <?php endif; ?>

    <!-- Bottom links -->
    <div class="sidebar-bottom">
        <a href="<?= BASE_URL ?>/settings"
           class="sidebar-bottom-item <?= $_activePage === 'settings' ? 'active' : '' ?>">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                 stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="3"/>
                <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33
                         1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33
                         l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4
                         h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06
                         A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51
                         a1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9
                         a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/>
            </svg>
            Settings
        </a>
        <a href="<?= BASE_URL ?>/logout" class="sidebar-bottom-item">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                 stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                <polyline points="16 17 21 12 16 7"/>
                <line x1="21" y1="12" x2="9" y2="12"/>
            </svg>
            Log out
        </a>
    </div>

</aside>
