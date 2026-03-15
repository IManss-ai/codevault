<?php
/**
 * Landing Page
 * 
 * The public home page for CodeVault. Shows a hero section,
 * feature highlights, and recent public snippets.
 */

$pageTitle = 'Your Personal Code Library';

// Fetch stats and recent snippets
$pdo = Database::connect();

// Total public snippets count
$stmt = $pdo->query('SELECT COUNT(*) as total FROM snippets WHERE is_public = true');
$totalSnippets = $stmt->fetch()['total'] ?? 0;

// Total users count
$stmt = $pdo->query('SELECT COUNT(*) as total FROM users');
$totalUsers = $stmt->fetch()['total'] ?? 0;

// 6 most recent public snippets with author info
$stmt = $pdo->query('
    SELECT s.id, s.title, s.language, s.tags, s.created_at,
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

<!-- Hero Section -->
<section class="hero">
    <div class="container">
        <h1>Save your code.<br><span>Find it instantly.</span></h1>
        <p>
            CodeVault is your personal, searchable library of every useful piece of code 
            you've ever written. Organize with tags, share with the world, and access 
            from anywhere.
        </p>
        <div class="hero-actions">
            <?php if (isLoggedIn()): ?>
                <a href="<?= BASE_URL ?>/dashboard" class="btn btn-primary btn-lg">Go to Dashboard</a>
            <?php else: ?>
                <a href="<?= BASE_URL ?>/register" class="btn btn-primary btn-lg">Get Started — Free</a>
            <?php endif; ?>
            <a href="<?= BASE_URL ?>/explore" class="btn btn-secondary btn-lg">Explore Snippets</a>
        </div>
        <?php if ($totalSnippets > 0 || $totalUsers > 0): ?>
            <p class="text-muted mt-lg" style="font-size: 0.9rem;">
                <?= number_format($totalSnippets) ?> public snippets from <?= number_format($totalUsers) ?> developers
            </p>
        <?php endif; ?>
    </div>
</section>

<!-- Features Section -->
<section class="container">
    <div class="features">
        <div class="feature-card">
            <svg class="feature-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="16 18 22 12 16 6"></polyline>
                <polyline points="8 6 2 12 8 18"></polyline>
            </svg>
            <h3>20+ Languages</h3>
            <p>Syntax highlighting for JavaScript, Python, PHP, Go, Rust, and many more. Your code looks beautiful.</p>
        </div>

        <div class="feature-card">
            <svg class="feature-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="11" cy="11" r="8"></circle>
                <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
            </svg>
            <h3>Instant Search</h3>
            <p>Full-text search across titles, tags, and code content. Find that snippet in seconds, not minutes.</p>
        </div>

        <div class="feature-card">
            <svg class="feature-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M4 12v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8"></path>
                <polyline points="16 6 12 2 8 6"></polyline>
                <line x1="12" y1="2" x2="12" y2="15"></line>
            </svg>
            <h3>Share &amp; Embed</h3>
            <p>Public profiles, an explore page, and embeddable widgets. Share your best code on blogs and Stack Overflow.</p>
        </div>

        <div class="feature-card">
            <svg class="feature-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
            </svg>
            <h3>REST API</h3>
            <p>Full API access with key-based auth. Create, read, update, and delete snippets programmatically.</p>
        </div>
    </div>
</section>

<!-- Recent Public Snippets -->
<?php if (!empty($recentSnippets)): ?>
<section class="container" style="padding-bottom: var(--space-3xl);">
    <div class="section-header">
        <h2>Recently Added</h2>
        <a href="<?= BASE_URL ?>/explore" class="btn btn-secondary btn-sm">View All</a>
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

                <?php if (!empty($snippet['tags'])): ?>
                    <div class="tags">
                        <?php foreach (explode(',', $snippet['tags']) as $tag): ?>
                            <span class="badge badge-tag"><?= sanitize(trim($tag)) ?></span>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <div class="card-meta">
                    <span>
                        <a href="<?= BASE_URL ?>/u/<?= sanitize($snippet['username']) ?>"><?= sanitize($snippet['username']) ?></a>
                    </span>
                    <span>★ <?= (int)$snippet['star_count'] ?></span>
                    <span><?= timeAgo($snippet['created_at']) ?></span>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<?php require BASE_PATH . '/includes/footer.php'; ?>
