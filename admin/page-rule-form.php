<?php
require_once __DIR__ . '/includes/auth.php';
admin_require_login();
require_once __DIR__ . '/../functions.php';

$id = isset($_GET['id']) ? (int) $_GET['id'] : (isset($_POST['id']) ? (int) $_POST['id'] : 0);
$existing = $id > 0 ? getPageRuleById($id) : null;
if ($id > 0 && !$existing) {
    admin_flash_set('error', 'That page rule no longer exists.');
    header('Location: page-rules');
    exit;
}

$errors = [];
$values = $existing ?: [
    'url_path' => '', 'meta_title' => '', 'meta_description' => '', 'canonical_url' => '',
    'robots' => '', 'schema_json' => '', 'redirect_to' => '', 'redirect_code' => '', 'is_active' => 1,
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!admin_csrf_verify($_POST['csrf_token'] ?? null)) {
        $errors[] = 'Your session expired. Please try again.';
    } else {
        $values = array_merge($values, $_POST);
        try {
            savePageRule(array_merge($values, ['id' => $id]));
            admin_flash_set('success', 'Page rule saved.');
            header('Location: page-rules');
            exit;
        } catch (Throwable $e) {
            $errors[] = $e->getMessage();
        }
    }
}

if (!empty($errors)) {
    admin_flash_set('error', implode(' ', $errors));
}

$pageTitle = ($id > 0 ? 'Edit' : 'Add') . ' Page Rule — Seat Outlet Admin';
$currentPage = 'page-rules';
include __DIR__ . '/includes/app-header.php';
?>
        <div class="row mb-3">
            <div class="col">
                <h2 class="page-title"><?php echo $id > 0 ? 'Edit' : 'Add'; ?> Page Rule</h2>
            </div>
        </div>

        <form method="post" action="page-rule-form<?php echo $id > 0 ? '?id=' . $id : ''; ?>">
            <input type="hidden" name="csrf_token" value="<?php echo admin_csrf_token(); ?>">
            <input type="hidden" name="id" value="<?php echo (int) $id; ?>">

            <div class="card mb-3">
                <div class="card-header"><h3 class="card-title">URL &amp; status</h3></div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">URL path</label>
                        <input type="text" name="url_path" class="form-control" required
                            placeholder="/concerts-city/austin-tx-123"
                            value="<?php echo htmlspecialchars($values['url_path'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                        <small class="form-hint">Path only - no domain, no query string. Must match the page's REQUEST_URI exactly.</small>
                    </div>
                    <label class="form-check">
                        <input class="form-check-input" type="checkbox" name="is_active" value="1"
                            <?php echo !empty($values['is_active']) ? 'checked' : ''; ?>>
                        <span class="form-check-label">Active</span>
                    </label>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header"><h3 class="card-title">SEO metadata</h3></div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Meta title</label>
                        <input type="text" name="meta_title" class="form-control"
                            value="<?php echo htmlspecialchars($values['meta_title'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Meta description</label>
                        <textarea name="meta_description" class="form-control" rows="2"><?php echo htmlspecialchars($values['meta_description'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Canonical URL</label>
                        <input type="text" name="canonical_url" class="form-control"
                            value="<?php echo htmlspecialchars($values['canonical_url'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Robots</label>
                        <select name="robots" class="form-select">
                            <option value="">Use page default</option>
                            <?php foreach (['index,follow', 'noindex,follow', 'noindex,nofollow'] as $opt): ?>
                                <option value="<?php echo $opt; ?>" <?php echo ($values['robots'] ?? '') === $opt ? 'selected' : ''; ?>>
                                    <?php echo $opt; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-0">
                        <label class="form-label">Schema JSON-LD (optional)</label>
                        <textarea name="schema_json" class="form-control font-monospace" rows="5"
                            placeholder='{"@context": "https://schema.org", ...}'><?php echo htmlspecialchars($values['schema_json'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
                    </div>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header"><h3 class="card-title">Redirect (optional)</h3></div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Redirect to</label>
                        <input type="text" name="redirect_to" class="form-control" placeholder="/new-path or https://…"
                            value="<?php echo htmlspecialchars($values['redirect_to'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                    </div>
                    <div class="mb-0">
                        <label class="form-label">Redirect code</label>
                        <select name="redirect_code" class="form-select">
                            <option value="">No redirect</option>
                            <option value="301" <?php echo (string) ($values['redirect_code'] ?? '') === '301' ? 'selected' : ''; ?>>301 (permanent)</option>
                            <option value="302" <?php echo (string) ($values['redirect_code'] ?? '') === '302' ? 'selected' : ''; ?>>302 (temporary)</option>
                        </select>
                    </div>
                </div>
            </div>

            <button type="submit" class="btn btn-primary">Save</button>
            <a href="page-rules" class="btn btn-link">Cancel</a>
        </form>
<?php include __DIR__ . '/includes/app-footer.php'; ?>
