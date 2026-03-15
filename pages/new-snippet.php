<?php
/**
 * New Snippet Page
 * 
 * Form to create a new code snippet.
 */

$pageTitle = 'New Snippet';
$errors = [];
$old = [
    'title' => '', 'description' => '', 'code' => '',
    'language' => 'javascript', 'tags' => '', 'is_public' => true,
];

// Handle form submission
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

        // Validate
        if (empty($title))    $errors[] = 'Title is required.';
        if (strlen($title) > 255) $errors[] = 'Title must be 255 characters or less.';
        if (empty($code))     $errors[] = 'Code content is required.';
        if (empty($language)) $errors[] = 'Please select a language.';

        if (empty($errors)) {
            $pdo = Database::connect();

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

                setFlash('flash_success', 'Snippet created successfully!');
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

<div class="container" style="max-width: 800px;">
    <div class="page-header">
        <h1>Create New Snippet</h1>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-error"><?= sanitize($errors[0]) ?></div>
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
            <textarea id="description" name="description" class="form-textarea" rows="3"
                      placeholder="What does this code do?"><?= sanitize($old['description']) ?></textarea>
        </div>

        <div class="flex gap-md" style="flex-wrap:wrap;">
            <div class="form-group" style="flex:1; min-width:200px;">
                <label class="form-label" for="language">Language</label>
                <select id="language" name="language" class="form-select" required>
                    <?php foreach ($languages as $key => $name): ?>
                        <option value="<?= sanitize($key) ?>" <?= $old['language'] === $key ? 'selected' : '' ?>>
                            <?= sanitize($name) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group" style="flex:1; min-width:200px;">
                <label class="form-label" for="tags">Tags <span class="text-muted">(comma separated)</span></label>
                <input type="text" id="tags" name="tags" class="form-input"
                       value="<?= sanitize($old['tags']) ?>"
                       placeholder="e.g. react, hooks, debounce">
            </div>
        </div>

        <div class="form-group">
            <label class="form-label" for="code-editor">Code</label>
            <textarea id="code-editor" name="code" class="form-textarea form-code-textarea"
                      placeholder="Paste your code here..." required><?= sanitize($old['code']) ?></textarea>
            <div class="flex justify-between mt-sm" style="font-size: 0.8rem; color: var(--text-muted);">
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
            <button type="submit" class="btn btn-primary btn-lg">Save Snippet</button>
            <a href="<?= BASE_URL ?>/dashboard" class="btn btn-secondary btn-lg">Cancel</a>
        </div>
    </form>
</div>

<?php require BASE_PATH . '/includes/footer.php'; ?>
