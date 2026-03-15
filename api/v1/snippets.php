<?php
/**
 * CodeVault REST API — /api/v1/snippets
 * 
 * Endpoints:
 *   GET    /api/v1/snippets        → List user's snippets
 *   GET    /api/v1/snippets/{id}   → Get a single snippet
 *   POST   /api/v1/snippets        → Create a snippet
 *   PUT    /api/v1/snippets/{id}   → Update a snippet
 *   DELETE /api/v1/snippets/{id}   → Delete a snippet
 * 
 * Authentication: Bearer token in Authorization header
 * Rate limit: 100 requests per hour per API key
 */

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];

// Also handle star toggle for AJAX (session-based, not API-key)
if ($method === 'POST' && !isset($_SERVER['HTTP_AUTHORIZATION'])) {
    $input = json_decode(file_get_contents('php://input'), true);
    if (($input['action'] ?? '') === 'toggle_star' && isLoggedIn()) {
        handleStarToggle($input['snippet_id'] ?? '');
        exit;
    }
}

// ── API Key Authentication ────────────────
$apiKey = null;
$authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';

if (preg_match('/^Bearer\s+(.+)$/i', $authHeader, $matches)) {
    $apiKey = $matches[1];
}

if (!$apiKey) {
    jsonResponse(401, ['error' => 'Missing API key. Use Authorization: Bearer YOUR_KEY']);
}

$pdo = Database::connect();
$hashedKey = hashApiKey($apiKey);

// Look up user by hashed API key
$stmt = $pdo->prepare('SELECT id, username FROM users WHERE api_key = :key');
$stmt->execute([':key' => $hashedKey]);
$apiUser = $stmt->fetch();

if (!$apiUser) {
    jsonResponse(401, ['error' => 'Invalid API key.']);
}

// ── Rate Limiting (100 req/hour) ──────────
$hourKey = date('Y-m-d-H');
$rateLimitKey = 'rate:' . $hashedKey . ':' . $hourKey;

// Simple in-memory rate limiting using a DB table approach
// For simplicity, we'll track in PHP session-like storage
// In production, you'd use Redis or a rate_limits table
// For now, using a simple file-based counter
$rateLimitDir = sys_get_temp_dir() . '/codevault_rate_limits';
if (!is_dir($rateLimitDir)) mkdir($rateLimitDir, 0755, true);

$rateLimitFile = $rateLimitDir . '/' . md5($rateLimitKey);
$requestCount = 0;

if (file_exists($rateLimitFile)) {
    $data = json_decode(file_get_contents($rateLimitFile), true);
    if ($data && ($data['hour'] ?? '') === $hourKey) {
        $requestCount = $data['count'];
    }
}

if ($requestCount >= 100) {
    jsonResponse(429, ['error' => 'Rate limit exceeded. Maximum 100 requests per hour.']);
}

// Increment counter
file_put_contents($rateLimitFile, json_encode(['hour' => $hourKey, 'count' => $requestCount + 1]));

// ── Route to handler ──────────────────────
$snippetId = $apiSnippetId ?? null; // Set by router

switch ($method) {
    case 'GET':
        if ($snippetId) {
            getSnippet($pdo, $apiUser, $snippetId);
        } else {
            listSnippets($pdo, $apiUser);
        }
        break;

    case 'POST':
        createSnippet($pdo, $apiUser);
        break;

    case 'PUT':
        if (!$snippetId) jsonResponse(400, ['error' => 'Snippet ID required for update.']);
        updateSnippet($pdo, $apiUser, $snippetId);
        break;

    case 'DELETE':
        if (!$snippetId) jsonResponse(400, ['error' => 'Snippet ID required for delete.']);
        deleteSnippet($pdo, $apiUser, $snippetId);
        break;

    default:
        jsonResponse(405, ['error' => 'Method not allowed.']);
}

// ── Handler Functions ─────────────────────

function listSnippets(PDO $pdo, array $user): void
{
    $stmt = $pdo->prepare('
        SELECT id, title, description, language, tags, is_public, view_count, created_at, updated_at
        FROM snippets WHERE user_id = :uid ORDER BY updated_at DESC
    ');
    $stmt->execute([':uid' => $user['id']]);
    jsonResponse(200, ['snippets' => $stmt->fetchAll()]);
}

function getSnippet(PDO $pdo, array $user, string $id): void
{
    $stmt = $pdo->prepare('SELECT * FROM snippets WHERE id = :id AND user_id = :uid');
    $stmt->execute([':id' => $id, ':uid' => $user['id']]);
    $snippet = $stmt->fetch();

    if (!$snippet) jsonResponse(404, ['error' => 'Snippet not found.']);
    jsonResponse(200, ['snippet' => $snippet]);
}

function createSnippet(PDO $pdo, array $user): void
{
    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input) jsonResponse(400, ['error' => 'Invalid JSON body.']);

    $title    = trim($input['title'] ?? '');
    $code     = $input['code'] ?? '';
    $language = trim($input['language'] ?? '');

    if (empty($title))    jsonResponse(400, ['error' => 'Title is required.']);
    if (empty($code))     jsonResponse(400, ['error' => 'Code is required.']);
    if (empty($language)) jsonResponse(400, ['error' => 'Language is required.']);

    $stmt = $pdo->prepare('
        INSERT INTO snippets (user_id, title, description, code, language, tags, is_public, view_count, created_at, updated_at)
        VALUES (:uid, :title, :desc, :code, :lang, :tags, :public, 0, NOW(), NOW())
        RETURNING id
    ');

    $stmt->execute([
        ':uid'    => $user['id'],
        ':title'  => $title,
        ':desc'   => trim($input['description'] ?? ''),
        ':code'   => $code,
        ':lang'   => $language,
        ':tags'   => trim($input['tags'] ?? ''),
        ':public' => !empty($input['is_public']) ? 'true' : 'false',
    ]);
    $id = $stmt->fetchColumn();

    jsonResponse(201, ['snippet' => ['id' => $id, 'title' => $title]]);
}

function updateSnippet(PDO $pdo, array $user, string $id): void
{
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) jsonResponse(400, ['error' => 'Invalid JSON body.']);

    // Verify ownership
    $stmt = $pdo->prepare('SELECT id FROM snippets WHERE id = :id AND user_id = :uid');
    $stmt->execute([':id' => $id, ':uid' => $user['id']]);
    if (!$stmt->fetch()) jsonResponse(404, ['error' => 'Snippet not found.']);

    // Build dynamic update
    $fields = [];
    $params = [':id' => $id, ':uid' => $user['id']];

    foreach (['title', 'description', 'code', 'language', 'tags'] as $field) {
        if (isset($input[$field])) {
            $fields[] = "$field = :$field";
            $params[":$field"] = $input[$field];
        }
    }
    if (isset($input['is_public'])) {
        $fields[] = "is_public = :is_public";
        $params[':is_public'] = $input['is_public'] ? 'true' : 'false';
    }

    if (empty($fields)) jsonResponse(400, ['error' => 'No fields to update.']);

    $fields[] = "updated_at = NOW()";
    $sql = "UPDATE snippets SET " . implode(', ', $fields) . " WHERE id = :id AND user_id = :uid";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    jsonResponse(200, ['message' => 'Snippet updated.']);
}

function deleteSnippet(PDO $pdo, array $user, string $id): void
{
    // Check ownership before touching any related data
    $stmt = $pdo->prepare('SELECT id FROM snippets WHERE id = :id AND user_id = :uid');
    $stmt->execute([':id' => $id, ':uid' => $user['id']]);
    if (!$stmt->fetch()) jsonResponse(404, ['error' => 'Snippet not found.']);

    // Delete stars first (foreign key), then the snippet
    $stmt = $pdo->prepare('DELETE FROM stars WHERE snippet_id = :id');
    $stmt->execute([':id' => $id]);

    $stmt = $pdo->prepare('DELETE FROM snippets WHERE id = :id AND user_id = :uid');
    $stmt->execute([':id' => $id, ':uid' => $user['id']]);

    jsonResponse(200, ['message' => 'Snippet deleted.']);
}

function handleStarToggle(string $snippetId): void
{
    if (empty($snippetId)) {
        jsonResponse(400, ['error' => 'Snippet ID required.']);
    }

    $pdo = Database::connect();
    $userId = currentUserId();

    // Check if already starred
    $stmt = $pdo->prepare('SELECT id FROM stars WHERE user_id = :uid AND snippet_id = :sid');
    $stmt->execute([':uid' => $userId, ':sid' => $snippetId]);
    $existing = $stmt->fetch();

    if ($existing) {
        $stmt = $pdo->prepare('DELETE FROM stars WHERE user_id = :uid AND snippet_id = :sid');
        $stmt->execute([':uid' => $userId, ':sid' => $snippetId]);
        $starred = false;
    } else {
        $stmt = $pdo->prepare('INSERT INTO stars (user_id, snippet_id, created_at) VALUES (:uid, :sid, NOW())');
        $stmt->execute([':uid' => $userId, ':sid' => $snippetId]);
        $starred = true;
    }

    // Get updated count
    $stmt = $pdo->prepare('SELECT COUNT(*) as total FROM stars WHERE snippet_id = :sid');
    $stmt->execute([':sid' => $snippetId]);
    $count = $stmt->fetch()['total'];

    jsonResponse(200, ['success' => true, 'starred' => $starred, 'count' => (int)$count]);
}

function jsonResponse(int $status, array $data): void
{
    http_response_code($status);
    echo json_encode($data, JSON_PRETTY_PRINT);
    exit;
}
