<?php
/**
 * New Snippet Page
 */

$pageTitle = 'New Snippet';
$page      = 'new';
$errors    = [];
$old       = [
    'title' => '', 'description' => '', 'code' => '',
    'language' => 'javascript', 'tags' => '', 'is_public' => true,
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRF($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid form submission. Please try again.';
    } else {
        $title       = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $code        = $_POST['code'] ?? '';
        $language    = trim($_POST['language'] ?? '');
        $tags        = trim($_POST['tags'] ?? '');
        $isPublic    = isset($_POST['is_public']);

        $old = compact('title', 'description', 'code', 'language', 'tags');
        $old['is_public'] = $isPublic;

        if (empty($title))        $errors[] = 'Title is required.';
        if (strlen($title) > 255) $errors[] = 'Title must be 255 characters or less.';
        if (empty($code))         $errors[] = 'Code content is required.';
        if (empty($language))     $errors[] = 'Please select a language.';

        if (empty($errors)) {
            $pdo  = Database::connect();
            $stmt = $pdo->prepare('
                INSERT INTO snippets (user_id, title, description, code, language, tags, is_public, view_count, created_at, updated_at)
                VALUES (:user_id, :title, :description, :code, :language, :tags, :is_public, 0, NOW(), NOW())
                RETURNING id
            ');
            try {
                $stmt->execute([
                    ':user_id'     => currentUserId(),
                    ':title'       => $title,
                    ':description' => $description,
                    ':code'        => $code,
                    ':language'    => $language,
                    ':tags'        => $tags,
                    ':is_public'   => $isPublic ? 'true' : 'false',
                ]);
                $id = $stmt->fetchColumn();
                setFlash('flash_success', 'Snippet created!');
                redirect(BASE_URL . '/snippet/' . $id);
            } catch (PDOException $e) {
                error_log('Create snippet error: ' . $e->getMessage());
                $errors[] = 'Something went wrong. Please try again.';
            }
        }
    }
}

$languages = getSupportedLanguages();
require BASE_PATH . '/includes/header.php';
?>

<div style="max-width: 560px;">

    <div class="page-header">
        <h1>New snippet</h1>
    </div>

    <!-- GitHub Gist Import -->
    <div class="card mb-xl" id="gist-import-card">
        <h2 style="font-size: 0.875rem; font-weight: 600; color: var(--text-secondary); margin-bottom: var(--space-md);">
            Import from GitHub Gist
        </h2>
        <div class="flex gap-md" style="align-items: flex-end; flex-wrap: wrap;">
            <div class="form-group" style="flex: 1; min-width: 200px; margin-bottom: 0;">
                <input type="url" id="gist-url" class="form-input"
                       placeholder="https://gist.github.com/user/abc123">
            </div>
            <button type="button" id="gist-import-btn" class="btn btn-secondary" style="margin-bottom: 1px;">Import</button>
        </div>
        <p id="gist-status" class="form-hint mt-sm" style="display:none;"></p>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-error mb-lg"><?= sanitize($errors[0]) ?></div>
    <?php endif; ?>

    <form method="POST" action="<?= BASE_URL ?>/new" novalidate>
        <input type="hidden" name="csrf_token" value="<?= generateCSRF() ?>">

        <div class="form-group">
            <label class="form-label" for="title">Title</label>
            <input type="text" id="title" name="title" class="form-input"
                   value="<?= sanitize($old['title']) ?>"
                   placeholder="e.g. React useDebounce hook" maxlength="255" required autofocus>
        </div>

        <div class="form-group">
            <label class="form-label" for="description">Description <span class="text-muted">(optional)</span></label>
            <textarea id="description" name="description" class="form-textarea" rows="2"
                      placeholder="What does this code do?"><?= sanitize($old['description']) ?></textarea>
        </div>

        <div class="flex gap-md" style="flex-wrap: wrap;">
            <div class="form-group" style="flex: 1; min-width: 160px;">
                <label class="form-label" for="language">Language</label>
                <select id="language" name="language" class="form-select" required>
                    <?php foreach ($languages as $key => $name): ?>
                        <option value="<?= sanitize($key) ?>" <?= $old['language'] === $key ? 'selected' : '' ?>>
                            <?= sanitize($name) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group" style="flex: 1; min-width: 160px;">
                <label class="form-label" for="tags">Tags <span class="text-muted">(comma separated)</span></label>
                <input type="text" id="tags" name="tags" class="form-input"
                       value="<?= sanitize($old['tags']) ?>"
                       placeholder="react, hooks, api">
            </div>
        </div>

        <div class="form-group">
            <label class="form-label" for="code-editor">Code</label>
            <textarea id="code-editor" name="code" class="form-textarea form-code-textarea"
                      placeholder="Paste your code here..." required><?= sanitize($old['code']) ?></textarea>
            <div class="flex justify-between mt-sm" style="font-size: 0.75rem; color: var(--text-hint);">
                <span id="line-count">0 lines</span>
                <span id="char-count">0 chars</span>
            </div>
        </div>

        <div class="form-group">
            <label class="form-checkbox">
                <input type="checkbox" name="is_public" value="1" <?= $old['is_public'] ? 'checked' : '' ?>>
                Make this snippet public
            </label>
            <p class="form-hint">Public snippets appear on your profile and the explore page.</p>
        </div>

        <div class="flex gap-md">
            <button type="submit" class="btn btn-primary">Save Snippet</button>
            <a href="<?= BASE_URL ?>/dashboard" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>

<script>
(function () {
    var langMap = {
        'JavaScript': 'javascript', 'TypeScript': 'typescript',
        'Python': 'python', 'PHP': 'php', 'HTML': 'html', 'CSS': 'css',
        'SQL': 'sql', 'Shell': 'bash', 'Bash': 'bash',
        'Java': 'java', 'C': 'c', 'C++': 'cpp', 'C#': 'csharp',
        'Ruby': 'ruby', 'Go': 'go', 'Rust': 'rust', 'Swift': 'swift',
        'Kotlin': 'kotlin', 'R': 'r', 'Dart': 'dart',
        'YAML': 'yaml', 'JSON': 'json', 'XML': 'xml',
        'Markdown': 'markdown', 'Lua': 'lua', 'Perl': 'perl',
    };

    var btn    = document.getElementById('gist-import-btn');
    var urlEl  = document.getElementById('gist-url');
    var status = document.getElementById('gist-status');

    function showStatus(msg, isError) {
        status.textContent = msg;
        status.style.display = 'block';
        status.style.color = isError ? 'var(--danger)' : 'var(--success)';
    }

    btn.addEventListener('click', function () {
        var raw = urlEl.value.trim();
        if (!raw) { showStatus('Please enter a Gist URL.', true); return; }

        var match = raw.match(/gist\.github\.com\/[^\/]+\/([a-f0-9]+)/i);
        if (!match) { showStatus('Invalid Gist URL. Expected: https://gist.github.com/user/id', true); return; }

        var gistId = match[1];
        btn.disabled = true;
        btn.textContent = 'Importing…';
        showStatus('Fetching Gist…', false);

        fetch('https://api.github.com/gists/' + gistId, {
            headers: { 'Accept': 'application/vnd.github.v3+json' }
        })
        .then(function (res) {
            if (!res.ok) throw new Error('Gist not found or is private (HTTP ' + res.status + ').');
            return res.json();
        })
        .then(function (data) {
            var files     = data.files;
            var fileNames = Object.keys(files);
            if (!fileNames.length) throw new Error('This Gist has no files.');

            var file       = files[fileNames[0]];
            var content    = file.content || '';
            var gistLang   = file.language || '';
            var mappedLang = langMap[gistLang] || 'javascript';

            document.getElementById('title').value       = file.filename || '';
            document.getElementById('description').value = (data.description || '').trim();
            document.getElementById('code-editor').value = content;

            var langSelect = document.getElementById('language');
            for (var i = 0; i < langSelect.options.length; i++) {
                if (langSelect.options[i].value === mappedLang) {
                    langSelect.selectedIndex = i;
                    break;
                }
            }

            document.getElementById('code-editor').dispatchEvent(new Event('input'));
            showStatus('Imported "' + file.filename + '" successfully!', false);
        })
        .catch(function (err) {
            showStatus(err.message || 'Failed to fetch Gist.', true);
        })
        .finally(function () {
            btn.disabled = false;
            btn.textContent = 'Import';
        });
    });
})();
</script>

<?php require BASE_PATH . '/includes/footer.php'; ?>
