<?php
/**
 * Settings Page
 * 
 * Manage account settings and API keys.
 */

$pageTitle = 'Settings';
$pdo = Database::connect();
$userId = currentUserId();
$errors = [];
$newApiKey = null;

// Fetch current user data
$stmt = $pdo->prepare('SELECT username, email, bio, website, api_key FROM users WHERE id = :id');
$stmt->execute([':id' => $userId]);
$user = $stmt->fetch();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if (!validateCSRF($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid form submission.';
    } else {
        switch ($action) {
            case 'update_profile':
                $bio     = trim($_POST['bio'] ?? '');
                $website = trim($_POST['website'] ?? '');

                if (!empty($website) && !filter_var($website, FILTER_VALIDATE_URL)) {
                    $errors[] = 'Please enter a valid URL for your website.';
                    break;
                }

                $stmt = $pdo->prepare('UPDATE users SET bio = :bio, website = :website WHERE id = :id');
                $stmt->execute([':bio' => $bio, ':website' => $website, ':id' => $userId]);
                $user['bio'] = $bio;
                $user['website'] = $website;
                setFlash('flash_success', 'Profile updated!');
                redirect(BASE_URL . '/settings');
                break;

            case 'generate_api_key':
                $rawKey = generateApiKey();
                $hashedKey = hashApiKey($rawKey);

                $stmt = $pdo->prepare('UPDATE users SET api_key = :key WHERE id = :id');
                $stmt->execute([':key' => $hashedKey, ':id' => $userId]);
                $user['api_key'] = $hashedKey;
                $newApiKey = $rawKey; // Show once to user
                break;

            case 'change_password':
                $currentPass = $_POST['current_password'] ?? '';
                $newPass     = $_POST['new_password'] ?? '';
                $confirmPass = $_POST['confirm_password'] ?? '';

                // Verify current password
                $stmt = $pdo->prepare('SELECT password_hash FROM users WHERE id = :id');
                $stmt->execute([':id' => $userId]);
                $row = $stmt->fetch();

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
                $stmt = $pdo->prepare('UPDATE users SET password_hash = :hash WHERE id = :id');
                $stmt->execute([':hash' => $hash, ':id' => $userId]);
                setFlash('flash_success', 'Password changed successfully!');
                redirect(BASE_URL . '/settings');
                break;
        }
    }
}

require BASE_PATH . '/includes/header.php';
?>

<div class="container" style="max-width: 700px;">
    <h1 class="mb-xl">Settings</h1>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-error"><?= sanitize($errors[0]) ?></div>
    <?php endif; ?>

    <!-- Profile Section -->
    <div class="card mb-xl">
        <h2 style="font-size: 1.15rem; margin-bottom: var(--space-lg);">Profile</h2>
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
                <textarea id="bio" name="bio" class="form-textarea" rows="3"
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

    <!-- API Key Section -->
    <div class="card mb-xl">
        <h2 style="font-size: 1.15rem; margin-bottom: var(--space-lg);">API Key</h2>

        <?php if ($newApiKey): ?>
            <div class="alert alert-success">
                Your new API key (shown once — copy it now!):
            </div>
            <div class="code-block mb-lg">
                <pre style="padding: var(--space-md);"><code><?= sanitize($newApiKey) ?></code></pre>
            </div>
        <?php endif; ?>

        <p class="text-secondary mb-md" style="font-size: 0.9rem;">
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

    <!-- Change Password Section -->
    <div class="card mb-xl">
        <h2 style="font-size: 1.15rem; margin-bottom: var(--space-lg);">Change Password</h2>
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
