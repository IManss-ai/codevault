<?php
/**
 * CodeVault Helper Functions
 * 
 * Utility functions used across the entire application.
 * These are stateless helpers — they don't depend on global state.
 */

/**
 * Redirect to a URL and stop execution.
 */
function redirect(string $url): void
{
    header("Location: $url");
    exit;
}

/**
 * Check if the current user is logged in.
 */
function isLoggedIn(): bool
{
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Require the user to be logged in. Redirects to login page if not.
 */
function requireLogin(): void
{
    if (!isLoggedIn()) {
        $_SESSION['flash_error'] = 'Please log in to access that page.';
        redirect(BASE_URL . '/login');
    }
}

/**
 * Get the current logged-in user's ID, or null.
 */
function currentUserId(): ?string
{
    return $_SESSION['user_id'] ?? null;
}

/**
 * Get the current logged-in user's username, or null.
 */
function currentUsername(): ?string
{
    return $_SESSION['username'] ?? null;
}

/**
 * Sanitize user input for safe HTML output.
 */
function sanitize(?string $input): string
{
    if ($input === null) return '';
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

/**
 * Generate a CSRF token and store it in the session.
 * Returns the token for embedding in forms.
 */
function generateCSRF(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Validate a submitted CSRF token against the session token.
 */
function validateCSRF(?string $token): bool
{
    if (empty($token) || empty($_SESSION['csrf_token'])) {
        return false;
    }
    $valid = hash_equals($_SESSION['csrf_token'], $token);
    // Regenerate token after validation to prevent reuse
    unset($_SESSION['csrf_token']);
    return $valid;
}

/**
 * Generate a random API key (64 hex characters = 32 bytes of entropy).
 * Returns the raw key — hash it with hashApiKey() before storing.
 */
function generateApiKey(): string
{
    return bin2hex(random_bytes(32));
}

/**
 * Hash an API key for safe storage in the database.
 */
function hashApiKey(string $key): string
{
    return hash('sha256', $key);
}

/**
 * Get a flash message from the session and clear it.
 */
function getFlash(string $key): ?string
{
    $message = $_SESSION[$key] ?? null;
    unset($_SESSION[$key]);
    return $message;
}

/**
 * Set a flash message in the session.
 */
function setFlash(string $key, string $message): void
{
    $_SESSION[$key] = $message;
}

/**
 * Format a timestamp as a relative time string (e.g., "2 hours ago").
 */
function timeAgo(string $datetime): string
{
    $now = new DateTime();
    $past = new DateTime($datetime);
    $diff = $now->diff($past);

    if ($diff->y > 0) return $diff->y . ' year' . ($diff->y > 1 ? 's' : '') . ' ago';
    if ($diff->m > 0) return $diff->m . ' month' . ($diff->m > 1 ? 's' : '') . ' ago';
    if ($diff->d > 0) return $diff->d . ' day' . ($diff->d > 1 ? 's' : '') . ' ago';
    if ($diff->h > 0) return $diff->h . ' hour' . ($diff->h > 1 ? 's' : '') . ' ago';
    if ($diff->i > 0) return $diff->i . ' minute' . ($diff->i > 1 ? 's' : '') . ' ago';
    return 'just now';
}

/**
 * Get the Prism.js language class for a given language name.
 */
function prismLanguage(string $language): string
{
    $map = [
        'javascript' => 'javascript',
        'js'         => 'javascript',
        'python'     => 'python',
        'py'         => 'python',
        'php'        => 'php',
        'html'       => 'markup',
        'css'        => 'css',
        'sql'        => 'sql',
        'bash'       => 'bash',
        'shell'      => 'bash',
        'java'       => 'java',
        'c'          => 'c',
        'cpp'        => 'cpp',
        'c++'        => 'cpp',
        'csharp'     => 'csharp',
        'c#'         => 'csharp',
        'ruby'       => 'ruby',
        'go'         => 'go',
        'rust'       => 'rust',
        'typescript' => 'typescript',
        'ts'         => 'typescript',
        'swift'      => 'swift',
        'kotlin'     => 'kotlin',
        'r'          => 'r',
        'dart'       => 'dart',
        'yaml'       => 'yaml',
        'json'       => 'json',
        'xml'        => 'markup',
        'markdown'   => 'markdown',
        'lua'        => 'lua',
        'perl'       => 'perl',
    ];

    $key = strtolower(trim($language));
    return $map[$key] ?? 'plaintext';
}

/**
 * Get the supported languages list for dropdowns.
 */
function getSupportedLanguages(): array
{
    return [
        'javascript' => 'JavaScript',
        'typescript' => 'TypeScript',
        'python'     => 'Python',
        'php'        => 'PHP',
        'html'       => 'HTML',
        'css'        => 'CSS',
        'sql'        => 'SQL',
        'bash'       => 'Bash / Shell',
        'java'       => 'Java',
        'c'          => 'C',
        'cpp'        => 'C++',
        'csharp'     => 'C#',
        'ruby'       => 'Ruby',
        'go'         => 'Go',
        'rust'       => 'Rust',
        'swift'      => 'Swift',
        'kotlin'     => 'Kotlin',
        'r'          => 'R',
        'dart'       => 'Dart',
        'yaml'       => 'YAML',
        'json'       => 'JSON',
        'xml'        => 'XML',
        'markdown'   => 'Markdown',
        'lua'        => 'Lua',
        'perl'       => 'Perl',
    ];
}

/**
 * Truncate a string to a max length with ellipsis.
 */
function truncate(string $text, int $length = 100): string
{
    if (mb_strlen($text) <= $length) return $text;
    return mb_substr($text, 0, $length) . '...';
}
