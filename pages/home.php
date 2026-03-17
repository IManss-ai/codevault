<?php
/**
 * Landing Page
 */

$pageTitle = 'Your Personal Code Library';
$pdo = Database::connect();

$stmt = $pdo->query('SELECT COUNT(*) as total FROM snippets WHERE is_public = true');
$totalSnippets = $stmt->fetch()['total'] ?? 0;

$stmt = $pdo->query('SELECT COUNT(*) as total FROM users');
$totalUsers = $stmt->fetch()['total'] ?? 0;

// 6 most recent public snippets with author info + code preview
$stmt = $pdo->query('
    SELECT s.id, s.title, s.language, s.tags, s.code, s.created_at,
           u.username,
           (SELECT COUNT(*) FROM stars WHERE snippet_id = s.id) as star_count
    FROM snippets s
    JOIN users u ON s.user_id = u.id
    WHERE s.is_public = true
    ORDER BY s.created_at DESC
    LIMIT 6
');
$recentSnippets = $stmt->fetchAll();

require BASE_PATH . '/includes/header.php';
?>

<div id="wave-container"></div>
<div class="wave-overlay">

<!-- Hero Section -->
<section class="hero">
    <div class="container" style="max-width: 640px;">

        <!-- Pill badge -->
        <span class="hero-pill">Open source &middot; Self-hostable &middot; Free</span>

        <h1 style="font-weight: 600; letter-spacing: -0.8px;">Your personal<br>code library</h1>
        <p style="color: #4a4f63; font-size: 1rem; max-width: 460px; margin: 0 auto var(--space-xl);">
            Save snippets you'll actually find again. Search by language, tag, or title.
            Share what's useful, keep the rest private.
        </p>

        <div class="hero-actions">
            <?php if (isLoggedIn()): ?>
                <a href="<?= BASE_URL ?>/dashboard" class="btn btn-primary btn-lg">Go to Dashboard</a>
            <?php else: ?>
                <a href="<?= BASE_URL ?>/register" class="btn btn-primary btn-lg">Get started</a>
            <?php endif; ?>
            <a href="https://github.com/IManss-ai/codevault" target="_blank" rel="noopener"
               class="btn btn-secondary btn-lg">View on GitHub</a>
        </div>

        <!-- Stats strip -->
        <?php if ($totalSnippets > 0 || $totalUsers > 0): ?>
        <div style="display: flex; justify-content: center; gap: 2.5rem; margin-top: 2rem; flex-wrap: wrap;">
            <div style="text-align: center;">
                <div style="font-size: 1.25rem; font-weight: 700; color: var(--text-primary); letter-spacing: -0.02em;">
                    <?= number_format($totalSnippets) ?>
                </div>
                <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 2px;">public snippets</div>
            </div>
            <div style="text-align: center;">
                <div style="font-size: 1.25rem; font-weight: 700; color: var(--text-primary); letter-spacing: -0.02em;">
                    <?= number_format($totalUsers) ?>
                </div>
                <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 2px;">developers</div>
            </div>
            <div style="text-align: center;">
                <div style="font-size: 1.25rem; font-weight: 700; color: var(--text-primary); letter-spacing: -0.02em;">25+</div>
                <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 2px;">languages</div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</section>

<!-- Features Section -->
<section class="container" style="padding-bottom: var(--space-2xl);">
    <div class="features">
        <div class="feature-card">
            <div class="feature-icon-wrap">
                <svg style="width:15px;height:15px;color:#555a6e;"
                     xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                     stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="16 18 22 12 16 6"></polyline>
                    <polyline points="8 6 2 12 8 18"></polyline>
                </svg>
            </div>
            <h3>25+ Languages</h3>
            <p>Syntax highlighting for JavaScript, Python, PHP, Go, Rust, and more.</p>
        </div>

        <div class="feature-card">
            <div class="feature-icon-wrap">
                <svg style="width:15px;height:15px;color:#555a6e;"
                     xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                     stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="11" cy="11" r="8"></circle>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                </svg>
            </div>
            <h3>Instant Search</h3>
            <p>Search by title, tags, or language. Find any snippet in seconds.</p>
        </div>

        <div class="feature-card">
            <div class="feature-icon-wrap">
                <svg style="width:15px;height:15px;color:#555a6e;"
                     xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                     stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M4 12v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8"></path>
                    <polyline points="16 6 12 2 8 6"></polyline>
                    <line x1="12" y1="2" x2="12" y2="15"></line>
                </svg>
            </div>
            <h3>Share &amp; Embed</h3>
            <p>Public profiles and embeddable widgets. Share your work anywhere.</p>
        </div>

        <div class="feature-card">
            <div class="feature-icon-wrap">
                <svg style="width:15px;height:15px;color:#555a6e;"
                     xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                     stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                    <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                </svg>
            </div>
            <h3>REST API</h3>
            <p>Key-based API access. Automate your snippet workflow programmatically.</p>
        </div>
    </div>
</section>

<!-- Recently Shared -->
<?php if (!empty($recentSnippets)): ?>
<section class="container" style="padding-bottom: var(--space-3xl);">
    <div class="section-header">
        <div>
            <p class="section-label">Community</p>
            <h2>Recently shared</h2>
        </div>
        <a href="<?= BASE_URL ?>/explore" class="btn btn-secondary btn-sm">View all</a>
    </div>

    <div class="snippet-grid">
        <?php foreach ($recentSnippets as $snippet): ?>
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <a href="<?= BASE_URL ?>/snippet/<?= sanitize($snippet['id']) ?>">
                            <?= sanitize($snippet['title']) ?>
                        </a>
                    </h3>
                    <span class="badge badge-language"><?= sanitize($snippet['language']) ?></span>
                </div>

                <?php if (!empty($snippet['code'])): ?>
                    <div class="snippet-preview"><?= sanitize(truncate($snippet['code'], 100)) ?></div>
                <?php endif; ?>

                <?php if (!empty($snippet['tags'])): ?>
                    <div class="tags mt-sm">
                        <?php foreach (array_slice(explode(',', $snippet['tags']), 0, 3) as $tag): ?>
                            <span class="badge badge-tag"><?= sanitize(trim($tag)) ?></span>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <div class="card-meta">
                    <a href="<?= BASE_URL ?>/u/<?= sanitize($snippet['username']) ?>">
                        <?= sanitize($snippet['username']) ?>
                    </a>
                    <span>★ <?= (int)$snippet['star_count'] ?></span>
                    <span><?= timeAgo($snippet['created_at']) ?></span>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<?php if (!isLoggedIn()): ?>
<section class="cta-section">
    <div class="container" style="max-width: 480px;">
        <h2>Ready to build your code library?</h2>
        <p>Stop losing snippets in Slack threads and browser bookmarks.</p>
        <a href="<?= BASE_URL ?>/register" class="btn btn-primary btn-lg">Get started free</a>
    </div>
</section>
<?php endif; ?>

</div><!-- /.wave-overlay -->

<script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
<script src="<?= BASE_URL ?>/assets/js/wave-bg.js"></script>

<?php require BASE_PATH . '/includes/footer.php'; ?>
