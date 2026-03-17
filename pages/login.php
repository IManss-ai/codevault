<?php
/**
 * Login Page
 */

$pageTitle = 'Log In';
$authPage  = 'login';
$errors    = [];
$old       = ['email' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRF($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid form submission. Please try again.';
    } else {
        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $remember = isset($_POST['remember']);

        $old['email'] = $email;

        $result = loginUser($email, $password, $remember);

        if ($result['success']) {
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
        <h1>Welcome back</h1>
        <p class="auth-subtitle">Log in to your code vault.</p>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-error"><?= sanitize($errors[0]) ?></div>
        <?php endif; ?>

        <form method="POST" action="<?= BASE_URL ?>/login" novalidate>
            <input type="hidden" name="csrf_token" value="<?= generateCSRF() ?>">

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
                    autofocus
                >
            </div>

            <div class="form-group">
                <label class="form-label" for="password">Password</label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    class="form-input"
                    placeholder="Your password"
                    required
                >
            </div>

            <div class="form-group">
                <label class="form-checkbox">
                    <input type="checkbox" name="remember" value="1">
                    Remember me for 30 days
                </label>
            </div>

            <button type="submit" class="btn btn-primary btn-block btn-lg">Log In</button>
        </form>

        <div class="auth-footer">
            Don't have an account? <a href="<?= BASE_URL ?>/register">Sign up</a>
        </div>
    </div>
</div>

<?php require BASE_PATH . '/includes/footer.php'; ?>
