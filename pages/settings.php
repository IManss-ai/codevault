<?php
/**
 * Settings Page
 */

$pageTitle = 'Settings';
$page      = 'settings';
$pdo       = Database::connect();
$userId    = currentUserId();
$errors    = [];
$newApiKey = null;

$stmt = $pdo->prepare('SELECT username, email, bio, website, api_key FROM users WHERE id = :id');
$stmt->execute([':id' => $userId]);
$user = $stmt->fetch();

// JSON export
if (($_GET['export'] ?? '') === 'json') {
    $stmt = $pdo->prepare('
        SELECT id, title, description, code, language, tags, is_public,
               view_count, forked_from, created_at, updated_at
        FROM snippets
        WHERE user_id = :uid
        ORDER BY created_at DESC
    ');
    $stmt->execute([':uid' => $userId]);
    $snippets = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $export   = ['exported_at' => date('c'), 'username' => $user['username'], 'snippets' => $snippets];
    $filename = 'codevault-export-' . date('Y-m-d') . '.json';
    header('Content-Type: application/json');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    echo json_encode($export, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if (!validateCSRF($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid form submission.';
    } else {
        switch ($action) {
            case 'update_profile':
                $bio     = trim($_POST['bio'] ?? '');
                $website = trim($_POST['website'] ?? '');
                if (!empty($website) && (!filter_var($website, FILTER_VALIDATE_URL) || !preg_match('/^https?:\/\//i', $website))) {
                    $errors[] = 'Please enter a valid URL starting with http:// or https://.';
                    break;
                }
                $pdo->prepare('UPDATE users SET bio = :bio, website = :website WHERE id = :id')
                    ->execute([':bio' => $bio, ':website' => $website, ':id' => $userId]);
                $user['bio'] = $bio;
                $user['website'] = $website;
                setFlash('flash_success', 'Profile updated!');
                redirect(BASE_URL . '/settings');
                break;

            case 'generate_api_key':
                $rawKey    = generateApiKey();
                $hashedKey = hashApiKey($rawKey);
                $pdo->prepare('UPDATE users SET api_key = :key WHERE id = :id')
                    ->execute([':key' => $hashedKey, ':id' => $userId]);
                $user['api_key'] = $hashedKey;
                $newApiKey = $rawKey;
                break;

            case 'change_password':
                $currentPass = $_POST['current_password'] ?? '';
                $newPass     = $_POST['new_password'] ?? '';
                $confirmPass = $_POST['confirm_password'] ?? '';

                $row = $pdo->prepare('SELECT password_hash FROM users WHERE id = :id');
                $row->execute([':id' => $userId]);
                $row = $row->fetch();

                if (!password_verify($currentPass, $row['password_hash'])) {
                    $errors[] = 'Current password is incorrect.';
                    break;
                }
                if (strlen($newPass) < 8) {
                    $errors[] = 'New password must be at least 8 characters.';
                    break;
                }
                if ($newPass !== $confirmPass) {
                    $errors[] = 'New passwords do not match.';
                    break;
                }
                $hash = password_hash($newPass, PASSWORD_BCRYPT);
                $pdo->prepare('UPDATE users SET password_hash = :hash WHERE id = :id')
                    ->execute([':hash' => $hash, ':id' => $userId]);
                setFlash('flash_success', 'Password changed successfully!');
                redirect(BASE_URL . '/settings');
                break;
        }
    }
}

require BASE_PATH . '/includes/header.php';
?>

<div style="max-width: 640px;">

    <div class="page-header">
        <h1>Settings</h1>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-error mb-lg"><?= sanitize($errors[0]) ?></div>
    <?php endif; ?>

    <!-- Profile -->
    <div class="card" style="padding: 24px; margin-bottom: 16px;">
        <h2 style="font-size: 1rem; font-weight: 600; color: var(--text-secondary); margin-bottom: var(--space-lg);">Profile</h2>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= generateCSRF() ?>">
            <input type="hidden" name="action" value="update_profile">

            <div class="form-group">
                <label class="form-label">Username</label>
                <input type="text" class="form-input" value="<?= sanitize($user['username']) ?>" disabled>
                <p class="form-hint">Usernames cannot be changed.</p>
            </div>

            <div class="form-group">
                <label class="form-label">Email</label>
                <input type="email" class="form-input" value="<?= sanitize($user['email']) ?>" disabled>
            </div>

            <div class="form-group">
                <label class="form-label" for="bio">Bio</label>
                <textarea id="bio" name="bio" class="form-textarea" rows="2"
                          placeholder="Tell other developers about yourself"><?= sanitize($user['bio'] ?? '') ?></textarea>
            </div>

            <div class="form-group">
                <label class="form-label" for="website">Website</label>
                <input type="url" id="website" name="website" class="form-input"
                       value="<?= sanitize($user['website'] ?? '') ?>"
                       placeholder="https://yoursite.com">
            </div>

            <button type="submit" class="btn btn-primary">Save Profile</button>
        </form>
    </div>

    <!-- API Key -->
    <div class="card" style="padding: 24px; margin-bottom: 16px;">
        <h2 style="font-size: 1rem; font-weight: 600; color: var(--text-secondary); margin-bottom: var(--space-lg);">API Key</h2>

        <?php if ($newApiKey): ?>
            <div class="alert alert-success mb-md">Your new API key — copy it now, it won't be shown again.</div>
            <div class="code-block mb-lg">
                <pre style="padding: var(--space-md);"><code><?= sanitize($newApiKey) ?></code></pre>
            </div>
        <?php endif; ?>

        <p style="font-size: 0.875rem; color: var(--text-muted); margin-bottom: var(--space-lg);">
            <?php if (!empty($user['api_key'])): ?>
                You have an active API key. Generate a new one to replace it.
            <?php else: ?>
                Generate an API key to access the CodeVault REST API.
            <?php endif; ?>
        </p>

        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= generateCSRF() ?>">
            <input type="hidden" name="action" value="generate_api_key">
            <button type="submit" class="btn btn-secondary"
                    <?= !empty($user['api_key']) ? 'data-confirm="This will replace your current API key. Continue?"' : '' ?>>
                <?= !empty($user['api_key']) ? 'Regenerate API Key' : 'Generate API Key' ?>
            </button>
        </form>
    </div>

    <!-- Export -->
    <div class="card" style="padding: 24px; margin-bottom: 16px;">
        <h2 style="font-size: 1rem; font-weight: 600; color: var(--text-secondary); margin-bottom: var(--space-sm);">Export Your Vault</h2>
        <p style="font-size: 0.875rem; color: var(--text-muted); margin-bottom: var(--space-lg);">
            Download all your snippets as a JSON file including titles, descriptions, code, tags, and metadata.
        </p>
        <a href="<?= BASE_URL ?>/settings?export=json" class="btn btn-secondary">Download JSON</a>
    </div>

    <!-- Change Password -->
    <div class="card" style="padding: 24px; margin-bottom: 16px;">
        <h2 style="font-size: 1rem; font-weight: 600; color: var(--text-secondary); margin-bottom: var(--space-lg);">Change Password</h2>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= generateCSRF() ?>">
            <input type="hidden" name="action" value="change_password">

            <div class="form-group">
                <label class="form-label" for="current_password">Current Password</label>
                <input type="password" id="current_password" name="current_password" class="form-input" required>
            </div>

            <div class="form-group">
                <label class="form-label" for="new_password">New Password</label>
                <input type="password" id="new_password" name="new_password" class="form-input" minlength="8" required>
            </div>

            <div class="form-group">
                <label class="form-label" for="confirm_password">Confirm New Password</label>
                <input type="password" id="confirm_password" name="confirm_password" class="form-input" minlength="8" required>
            </div>

            <button type="submit" class="btn btn-primary">Change Password</button>
        </form>
    </div>

</div>

<?php require BASE_PATH . '/includes/footer.php'; ?>
