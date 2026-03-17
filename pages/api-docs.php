<?php
/**
 * API Documentation Page
 */

$pageTitle = 'API Docs';
$page      = 'docs';

require BASE_PATH . '/includes/header.php';
?>

<div style="max-width: 860px;">

    <!-- Page Header -->
    <div class="page-header">
        <div>
            <h1>API Documentation</h1>
            <p style="font-size: 0.875rem; color: var(--text-muted); margin-top: 0.375rem; margin-bottom: 0;">
                Programmatic access to your CodeVault snippets.
            </p>
        </div>
        <?php if (isLoggedIn()): ?>
        <a href="<?= BASE_URL ?>/settings" class="btn btn-secondary btn-sm">Get API Key</a>
        <?php else: ?>
        <a href="<?= BASE_URL ?>/register" class="btn btn-primary btn-sm">Sign up for access</a>
        <?php endif; ?>
    </div>

    <!-- Introduction -->
    <div class="api-section">
        <div class="card">
            <h2 style="font-size: 1rem; font-weight: 600; margin-bottom: var(--space-md);">Introduction</h2>
            <p style="font-size: 0.875rem; color: var(--text-muted); line-height: 1.7; margin-bottom: var(--space-md);">
                The CodeVault REST API lets you read, create, update, and delete snippets programmatically.
                All responses are JSON. All requests that modify data require authentication via an API key.
            </p>
            <p style="font-size: 0.875rem; color: var(--text-muted); line-height: 1.7;">
                <strong style="color: var(--text-secondary);">Base URL:</strong>
            </p>
            <div class="code-block" style="margin-top: var(--space-sm);">
                <pre style="padding: 0.75rem 1rem;"><code><?= BASE_URL ?>/api/v1</code></pre>
            </div>
        </div>
    </div>

    <!-- Authentication -->
    <div class="api-section">
        <h2 style="font-size: 1.05rem; font-weight: 700; margin-bottom: var(--space-md);">Authentication</h2>
        <div class="card">
            <p style="font-size: 0.875rem; color: var(--text-muted); line-height: 1.7; margin-bottom: var(--space-md);">
                Authenticate by including your API key in the <code style="font-size: 0.82rem; background: var(--bg-tertiary); padding: 0.1rem 0.4rem; border-radius: 4px;">Authorization</code> header of every request.
                You can generate or regenerate your API key from your
                <a href="<?= BASE_URL ?>/settings">Settings page</a>.
            </p>
            <div class="api-detail">
                <h3>Header format</h3>
                <div class="code-block">
                    <pre style="padding: 0.75rem 1rem;"><code>Authorization: Bearer YOUR_API_KEY</code></pre>
                </div>
            </div>
            <div class="api-detail" style="margin-bottom: 0;">
                <h3>Example with curl</h3>
                <div class="code-block">
                    <pre style="padding: 0.75rem 1rem;"><code>curl -H "Authorization: Bearer YOUR_API_KEY" \
  <?= BASE_URL ?>/api/v1/snippets</code></pre>
                </div>
            </div>
        </div>
    </div>

    <!-- Rate Limits -->
    <div class="api-section">
        <h2 style="font-size: 1.05rem; font-weight: 700; margin-bottom: var(--space-md);">Rate Limits</h2>
        <div class="card">
            <p style="font-size: 0.875rem; color: var(--text-muted); line-height: 1.7; margin-bottom: var(--space-md);">
                The API is rate limited to <strong style="color: var(--text-secondary);">100 requests per hour</strong> per API key.
                When the limit is exceeded, the API returns HTTP <code style="font-size: 0.82rem; background: var(--bg-tertiary); padding: 0.1rem 0.4rem; border-radius: 4px;">429 Too Many Requests</code>.
            </p>
            <div class="code-block">
                <div class="code-block-header">
                    <span>429 response</span>
                </div>
                <pre><code class="language-json">{
  "error": "Rate limit exceeded. Max 100 requests per hour."
}</code></pre>
            </div>
        </div>
    </div>

    <!-- Endpoints Overview -->
    <div class="api-section">
        <h2 style="font-size: 1.05rem; font-weight: 700; margin-bottom: var(--space-md);">Endpoints</h2>

        <div class="api-endpoint">
            <span class="api-method method-get">GET</span>
            <span class="api-path">/api/v1/snippets</span>
            <span class="api-desc">List your snippets</span>
        </div>
        <div class="api-endpoint">
            <span class="api-method method-get">GET</span>
            <span class="api-path">/api/v1/snippets/{id}</span>
            <span class="api-desc">Get a single snippet</span>
        </div>
        <div class="api-endpoint">
            <span class="api-method method-post">POST</span>
            <span class="api-path">/api/v1/snippets</span>
            <span class="api-desc">Create a snippet</span>
        </div>
        <div class="api-endpoint">
            <span class="api-method method-put">PUT</span>
            <span class="api-path">/api/v1/snippets/{id}</span>
            <span class="api-desc">Update a snippet</span>
        </div>
        <div class="api-endpoint" style="margin-bottom: 0;">
            <span class="api-method method-delete">DELETE</span>
            <span class="api-path">/api/v1/snippets/{id}</span>
            <span class="api-desc">Delete a snippet</span>
        </div>
    </div>

    <!-- GET /snippets -->
    <div class="api-section">
        <div class="api-endpoint" style="margin-bottom: var(--space-lg);">
            <span class="api-method method-get">GET</span>
            <span class="api-path">/api/v1/snippets</span>
        </div>
        <div class="card api-detail">
            <p>Returns a paginated list of snippets belonging to the authenticated user.</p>
            <h3>Request headers</h3>
            <div class="code-block">
                <pre style="padding: 0.75rem 1rem;"><code>Authorization: Bearer YOUR_API_KEY</code></pre>
            </div>
            <h3>Query parameters</h3>
            <div class="code-block">
                <div class="code-block-header"><span>Optional parameters</span></div>
                <pre><code>page      integer   Page number (default: 1)
limit     integer   Results per page, max 100 (default: 20)
language  string    Filter by programming language
tag       string    Filter by tag</code></pre>
            </div>
            <h3>Example response</h3>
            <div class="code-block">
                <div class="code-block-header"><span>200 OK</span></div>
                <pre><code class="language-json">{
  "data": [
    {
      "id": "550e8400-e29b-41d4-a716-446655440000",
      "title": "Debounce function",
      "description": "Limits how often a function can fire.",
      "language": "javascript",
      "tags": "utils,performance",
      "is_public": true,
      "view_count": 42,
      "star_count": 7,
      "created_at": "2026-03-10T14:22:00Z",
      "updated_at": "2026-03-10T14:22:00Z"
    }
  ],
  "meta": {
    "total": 1,
    "page": 1,
    "limit": 20
  }
}</code></pre>
            </div>
        </div>
    </div>

    <!-- GET /snippets/{id} -->
    <div class="api-section">
        <div class="api-endpoint" style="margin-bottom: var(--space-lg);">
            <span class="api-method method-get">GET</span>
            <span class="api-path">/api/v1/snippets/{id}</span>
        </div>
        <div class="card api-detail">
            <p>Returns a single snippet by ID. You can only retrieve snippets you own, or public snippets.</p>
            <h3>Request headers</h3>
            <div class="code-block">
                <pre style="padding: 0.75rem 1rem;"><code>Authorization: Bearer YOUR_API_KEY</code></pre>
            </div>
            <h3>Example response</h3>
            <div class="code-block">
                <div class="code-block-header"><span>200 OK</span></div>
                <pre><code class="language-json">{
  "id": "550e8400-e29b-41d4-a716-446655440000",
  "title": "Debounce function",
  "description": "Limits how often a function can fire.",
  "code": "function debounce(fn, delay) {\n  let t;\n  return (...args) => {\n    clearTimeout(t);\n    t = setTimeout(() => fn(...args), delay);\n  };\n}",
  "language": "javascript",
  "tags": "utils,performance",
  "is_public": true,
  "view_count": 42,
  "star_count": 7,
  "created_at": "2026-03-10T14:22:00Z",
  "updated_at": "2026-03-10T14:22:00Z"
}</code></pre>
            </div>
            <h3>Error response</h3>
            <div class="code-block">
                <div class="code-block-header"><span>404 Not Found</span></div>
                <pre><code class="language-json">{ "error": "Snippet not found" }</code></pre>
            </div>
        </div>
    </div>

    <!-- POST /snippets -->
    <div class="api-section">
        <div class="api-endpoint" style="margin-bottom: var(--space-lg);">
            <span class="api-method method-post">POST</span>
            <span class="api-path">/api/v1/snippets</span>
        </div>
        <div class="card api-detail">
            <p>Creates a new snippet owned by the authenticated user.</p>
            <h3>Request headers</h3>
            <div class="code-block">
                <pre style="padding: 0.75rem 1rem;"><code>Authorization: Bearer YOUR_API_KEY
Content-Type: application/json</code></pre>
            </div>
            <h3>Request body</h3>
            <div class="code-block">
                <div class="code-block-header"><span>JSON body</span></div>
                <pre><code class="language-json">{
  "title":       "string  (required, max 255 chars)",
  "code":        "string  (required)",
  "language":    "string  (required, e.g. javascript)",
  "description": "string  (optional)",
  "tags":        "string  (optional, comma-separated)",
  "is_public":   "boolean (optional, default false)"
}</code></pre>
            </div>
            <h3>Example response</h3>
            <div class="code-block">
                <div class="code-block-header"><span>201 Created</span></div>
                <pre><code class="language-json">{
  "id": "550e8400-e29b-41d4-a716-446655440001",
  "title": "My new snippet",
  "language": "python",
  "created_at": "2026-03-17T09:00:00Z"
}</code></pre>
            </div>
            <h3>Error response</h3>
            <div class="code-block">
                <div class="code-block-header"><span>422 Unprocessable Entity</span></div>
                <pre><code class="language-json">{ "error": "title and code are required" }</code></pre>
            </div>
        </div>
    </div>

    <!-- PUT /snippets/{id} -->
    <div class="api-section">
        <div class="api-endpoint" style="margin-bottom: var(--space-lg);">
            <span class="api-method method-put">PUT</span>
            <span class="api-path">/api/v1/snippets/{id}</span>
        </div>
        <div class="card api-detail">
            <p>Updates an existing snippet. You may only update snippets you own. Send only the fields you want to change.</p>
            <h3>Request headers</h3>
            <div class="code-block">
                <pre style="padding: 0.75rem 1rem;"><code>Authorization: Bearer YOUR_API_KEY
Content-Type: application/json</code></pre>
            </div>
            <h3>Request body</h3>
            <div class="code-block">
                <div class="code-block-header"><span>JSON body (all fields optional)</span></div>
                <pre><code class="language-json">{
  "title":       "string",
  "code":        "string",
  "language":    "string",
  "description": "string",
  "tags":        "string",
  "is_public":   "boolean"
}</code></pre>
            </div>
            <h3>Example response</h3>
            <div class="code-block">
                <div class="code-block-header"><span>200 OK</span></div>
                <pre><code class="language-json">{ "success": true, "id": "550e8400-e29b-41d4-a716-446655440000" }</code></pre>
            </div>
        </div>
    </div>

    <!-- DELETE /snippets/{id} -->
    <div class="api-section">
        <div class="api-endpoint" style="margin-bottom: var(--space-lg);">
            <span class="api-method method-delete">DELETE</span>
            <span class="api-path">/api/v1/snippets/{id}</span>
        </div>
        <div class="card api-detail" style="margin-bottom: 0;">
            <p>Permanently deletes a snippet and all associated stars. You may only delete snippets you own.</p>
            <h3>Request headers</h3>
            <div class="code-block">
                <pre style="padding: 0.75rem 1rem;"><code>Authorization: Bearer YOUR_API_KEY</code></pre>
            </div>
            <h3>Example response</h3>
            <div class="code-block">
                <div class="code-block-header"><span>200 OK</span></div>
                <pre><code class="language-json">{ "success": true }</code></pre>
            </div>
            <h3>Error response</h3>
            <div class="code-block">
                <div class="code-block-header"><span>403 Forbidden</span></div>
                <pre><code class="language-json">{ "error": "Forbidden" }</code></pre>
            </div>
        </div>
    </div>

</div>

<?php require BASE_PATH . '/includes/footer.php'; ?>
