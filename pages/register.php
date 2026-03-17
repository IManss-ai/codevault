<?php
/**
 * Register Page
 */

$pageTitle = 'Sign Up';
$authPage  = 'register';
$errors    = [];
$old       = ['username' => '', 'email' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRF($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid form submission. Please try again.';
    } else {
        $username        = trim($_POST['username'] ?? '');
        $email           = trim($_POST['email'] ?? '');
        $password        = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        $old['username'] = $username;
        $old['email']    = $email;

        $result = registerUser($username, $email, $password, $confirmPassword);

        if ($result['success']) {
            setFlash('flash_success', 'Welcome to CodeVault! Your account has been created.');
            redirect(BASE_URL . '/dashboard');
        } else {
            $errors[] = $result['error'];
        }
    }
}

require BASE_PATH . '/includes/header.php';
?>

<div class="auth-page">
    <div class="auth-card">
        <h1>Create your account</h1>
        <p class="auth-subtitle">Start building your personal code library.</p>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-error"><?= sanitize($errors[0]) ?></div>
        <?php endif; ?>

        <form method="POST" action="<?= BASE_URL ?>/register" novalidate>
            <input type="hidden" name="csrf_token" value="<?= generateCSRF() ?>">

            <div class="form-group">
                <label class="form-label" for="username">Username</label>
                <input
                    type="text"
                    id="username"
                    name="username"
                    class="form-input"
                    value="<?= sanitize($old['username']) ?>"
                    placeholder="e.g. devmaster"
                    maxlength="30"
                    required
                    autofocus
                >
                <p class="form-hint">3–30 characters. Letters, numbers, hyphens, underscores only.</p>
            </div>

            <div class="form-group">
                <label class="form-label" for="email">Email</label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    class="form-input"
                    value="<?= sanitize($old['email']) ?>"
                    placeholder="you@example.com"
                    required
                >
            </div>

            <div class="form-group">
                <label class="form-label" for="password">Password</label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    class="form-input"
                    placeholder="Minimum 8 characters"
                    minlength="8"
                    required
                >
            </div>

            <div class="form-group">
                <label class="form-label" for="confirm_password">Confirm Password</label>
                <input
                    type="password"
                    id="confirm_password"
                    name="confirm_password"
                    class="form-input"
                    placeholder="Type your password again"
                    minlength="8"
                    required
                >
            </div>

            <button type="submit" class="btn btn-primary btn-block btn-lg">Create Account</button>
        </form>

        <div class="auth-footer">
            Already have an account? <a href="<?= BASE_URL ?>/login">Log in</a>
        </div>
    </div>
</div>

<?php require BASE_PATH . '/includes/footer.php'; ?>
