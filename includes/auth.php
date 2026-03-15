<?php
/**
 * CodeVault Authentication Functions
 * 
 * Handles user registration, login, and logout.
 * All functions return an array: ['success' => bool, 'error' => string|null]
 */

/**
 * Register a new user.
 * 
 * Validates input, checks for duplicates, hashes password, inserts into DB.
 * On success, logs the user in automatically.
 */
function registerUser(string $username, string $email, string $password, string $confirmPassword): array
{
    // Validate username
    $username = trim($username);
    if (empty($username)) {
        return ['success' => false, 'error' => 'Username is required.'];
    }
    if (strlen($username) < 3 || strlen($username) > 30) {
        return ['success' => false, 'error' => 'Username must be 3–30 characters.'];
    }
    if (!preg_match('/^[a-zA-Z0-9_-]+$/', $username)) {
        return ['success' => false, 'error' => 'Username can only contain letters, numbers, hyphens, and underscores.'];
    }

    // Validate email
    $email = trim(strtolower($email));
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'error' => 'Please enter a valid email address.'];
    }

    // Validate password
    if (strlen($password) < 8) {
        return ['success' => false, 'error' => 'Password must be at least 8 characters.'];
    }
    if ($password !== $confirmPassword) {
        return ['success' => false, 'error' => 'Passwords do not match.'];
    }

    $pdo = Database::connect();

    // Check for existing username
    $stmt = $pdo->prepare('SELECT id FROM users WHERE username = :username');
    $stmt->execute([':username' => $username]);
    if ($stmt->fetch()) {
        return ['success' => false, 'error' => 'That username is already taken.'];
    }

    // Check for existing email
    $stmt = $pdo->prepare('SELECT id FROM users WHERE email = :email');
    $stmt->execute([':email' => $email]);
    if ($stmt->fetch()) {
        return ['success' => false, 'error' => 'An account with that email already exists.'];
    }

    // Hash password and insert user
    $passwordHash = password_hash($password, PASSWORD_BCRYPT);

    $stmt = $pdo->prepare('
        INSERT INTO users (username, email, password_hash, created_at)
        VALUES (:username, :email, :password_hash, NOW())
        RETURNING id
    ');

    try {
        $stmt->execute([
            ':username'      => $username,
            ':email'         => $email,
            ':password_hash' => $passwordHash,
        ]);
        $id = $stmt->fetchColumn();
    } catch (PDOException $e) {
        error_log('Registration error: ' . $e->getMessage());
        return ['success' => false, 'error' => 'Something went wrong. Please try again.'];
    }

    // Auto-login after registration
    session_regenerate_id(true);
    $_SESSION['user_id'] = $id;
    $_SESSION['username'] = $username;

    return ['success' => true, 'error' => null];
}

/**
 * Log a user in.
 * 
 * Validates credentials and creates a session.
 */
function loginUser(string $email, string $password, bool $remember = false): array
{
    $email = trim(strtolower($email));

    if (empty($email) || empty($password)) {
        return ['success' => false, 'error' => 'Email and password are required.'];
    }

    $pdo = Database::connect();

    $stmt = $pdo->prepare('SELECT id, username, password_hash FROM users WHERE email = :email');
    $stmt->execute([':email' => $email]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password_hash'])) {
        return ['success' => false, 'error' => 'Invalid email or password.'];
    }

    // Regenerate session ID to prevent session fixation
    session_regenerate_id(true);
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];

    // Extend session lifetime if "remember me" was checked
    // session_set_cookie_params() has no effect after session_start(), so we
    // reissue the session cookie directly with the extended expiry.
    if ($remember) {
        $lifetime = 60 * 60 * 24 * 30; // 30 days
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            session_id(),
            time() + $lifetime,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }

    return ['success' => true, 'error' => null];
}

/**
 * Log the current user out.
 * 
 * Destroys the session completely.
 */
function logoutUser(): void
{
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 3600,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }

    session_destroy();
}
