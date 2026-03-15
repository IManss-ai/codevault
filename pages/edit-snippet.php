<?php
/**
 * Edit Snippet Page
 * 
 * Allows the snippet owner to edit or delete a snippet.
 * $snippetId is set by the router from the URL.
 */

$pageTitle = 'Edit Snippet';
$errors = [];
$pdo = Database::connect();

// Fetch the snippet and verify ownership
$stmt = $pdo->prepare('SELECT * FROM snippets WHERE id = :id AND user_id = :uid');
$stmt->execute([':id' => $snippetId, ':uid' => currentUserId()]);
$snippet = $stmt->fetch();

if (!$snippet) {
    setFlash('flash_error', 'Snippet not found or you do not have permission to edit it.');
    redirect(BASE_URL . '/dashboard');
}

// Handle DELETE action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    if (!validateCSRF($_POST['csrf_token'] ?? '')) {
        setFlash('flash_error', 'Invalid form submission.');
        redirect(BASE_URL . '/dashboard');
    }

    // Delete stars first (foreign key), then the snippet — wrapped in a transaction
    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare('DELETE FROM stars WHERE snippet_id = :id');
        $stmt->execute([':id' => $snippetId]);

        $stmt = $pdo->prepare('DELETE FROM snippets WHERE id = :id AND user_id = :uid');
        $stmt->execute([':id' => $snippetId, ':uid' => currentUserId()]);

        $pdo->commit();
    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log('Delete snippet error: ' . $e->getMessage());
        setFlash('flash_error', 'Something went wrong. Please try again.');
        redirect(BASE_URL . '/dashboard');
    }

    setFlash('flash_success', 'Snippet deleted.');
    redirect(BASE_URL . '/dashboard');
}

// Handle UPDATE
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') !== 'delete') {
    if (!validateCSRF($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid form submission. Please try again.';
    } else {
        $title       = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $code        = $_POST['code'] ?? '';
        $language    = trim($_POST['language'] ?? '');
        $tags        = trim($_POST['tags'] ?? '');
        $isPublic    = isset($_POST['is_public']);

        if (empty($title))    $errors[] = 'Title is required.';
        if (strlen($title) > 255) $errors[] = 'Title must be 255 characters or less.';
        if (empty($code))     $errors[] = 'Code content is required.';
        if (empty($language)) $errors[] = 'Please select a language.';

        if (empty($errors)) {
            $stmt = $pdo->prepare('
                UPDATE snippets 
                SET title = :title, description = :description, code = :code, 
                    language = :language, tags = :tags, is_public = :is_public, updated_at = NOW()
                WHERE id = :id AND user_id = :uid
            ');

            try {
                $stmt->execute([
                    ':title'       => $title,
                    ':description' => $description,
                    ':code'        => $code,
                    ':language'    => $language,
                    ':tags'        => $tags,
                    ':is_public'   => $isPublic ? 'true' : 'false',
                    ':id'          => $snippetId,
                    ':uid'         => currentUserId(),
                ]);

                setFlash('flash_success', 'Snippet updated!');
                redirect(BASE_URL . '/snippet/' . $snippetId);
            } catch (PDOException $e) {
                error_log('Edit snippet error: ' . $e->getMessage());
                $errors[] = 'Something went wrong. Please try again.';
            }
        }

        // Update snippet array with submitted values for form repopulation
        $snippet['title']       = $title;
        $snippet['description'] = $description;
        $snippet['code']        = $code;
        $snippet['language']    = $language;
        $snippet['tags']        = $tags;
        $snippet['is_public']   = $isPublic;
    }
}

$languages = getSupportedLanguages();
require BASE_PATH . '/includes/header.php';
?>

<div class="container" style="max-width: 800px;">
    <div class="page-header">
        <h1>Edit Snippet</h1>
        <form method="POST" style="display:inline;">
            <input type="hidden" name="csrf_token" value="<?= generateCSRF() ?>">
            <input type="hidden" name="action" value="delete">
            <button type="submit" class="btn btn-danger btn-sm"
                    data-confirm="Are you sure you want to delete this snippet? This cannot be undone.">
                Delete Snippet
            </button>
        </form>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-error"><?= sanitize($errors[0]) ?></div>
    <?php endif; ?>

    <form method="POST" novalidate>
        <input type="hidden" name="csrf_token" value="<?= generateCSRF() ?>">

        <div class="form-group">
            <label class="form-label" for="title">Title</label>
            <input type="text" id="title" name="title" class="form-input"
                   value="<?= sanitize($snippet['title']) ?>" maxlength="255" required autofocus>
        </div>

        <div class="form-group">
            <label class="form-label" for="description">Description <span class="text-muted">(optional)</span></label>
            <textarea id="description" name="description" class="form-textarea" rows="3"><?= sanitize($snippet['description'] ?? '') ?></textarea>
        </div>

        <div class="flex gap-md" style="flex-wrap:wrap;">
            <div class="form-group" style="flex:1; min-width:200px;">
                <label class="form-label" for="language">Language</label>
                <select id="language" name="language" class="form-select" required>
                    <?php foreach ($languages as $key => $name): ?>
                        <option value="<?= sanitize($key) ?>" <?= $snippet['language'] === $key ? 'selected' : '' ?>>
                            <?= sanitize($name) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group" style="flex:1; min-width:200px;">
                <label class="form-label" for="tags">Tags</label>
                <input type="text" id="tags" name="tags" class="form-input"
                       value="<?= sanitize($snippet['tags'] ?? '') ?>">
            </div>
        </div>

        <div class="form-group">
            <label class="form-label" for="code-editor">Code</label>
            <textarea id="code-editor" name="code" class="form-textarea form-code-textarea" required><?= sanitize($snippet['code']) ?></textarea>
            <div class="flex justify-between mt-sm" style="font-size: 0.8rem; color: var(--text-muted);">
                <span id="line-count">0 lines</span>
                <span id="char-count">0 chars</span>
            </div>
        </div>

        <div class="form-group">
            <label class="form-checkbox">
                <input type="checkbox" name="is_public" value="1" <?= $snippet['is_public'] ? 'checked' : '' ?>>
                Make this snippet public
            </label>
        </div>

        <div class="flex gap-md">
            <button type="submit" class="btn btn-primary btn-lg">Save Changes</button>
            <a href="<?= BASE_URL ?>/snippet/<?= sanitize($snippetId) ?>" class="btn btn-secondary btn-lg">Cancel</a>
        </div>
    </form>
</div>

<?php require BASE_PATH . '/includes/footer.php'; ?>
