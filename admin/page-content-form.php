<?php
require_once __DIR__ . '/includes/auth.php';
admin_require_login();
require_once __DIR__ . '/../functions.php';

$id = isset($_GET['id']) ? (int) $_GET['id'] : (isset($_POST['id']) ? (int) $_POST['id'] : 0);
$existing = $id > 0 ? getPageContentBlockById($id) : null;
if ($id > 0 && !$existing) {
    admin_flash_set('error', 'That content block no longer exists.');
    header('Location: page-content');
    exit;
}

$errors = [];
$values = $existing ?: [
    'page_path' => trim((string) ($_GET['page_path'] ?? '')),
    'block_key' => trim((string) ($_GET['block_key'] ?? '')),
    'label'     => trim((string) ($_GET['label'] ?? '')),
    'content'   => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!admin_csrf_verify($_POST['csrf_token'] ?? null)) {
        $errors[] = 'Your session expired. Please try again.';
    } else {
        $values = array_merge($values, $_POST);
        try {
            savePageContentBlock(array_merge($values, ['id' => $id]));
            admin_flash_set('success', 'Content block saved.');
            header('Location: page-content');
            exit;
        } catch (Throwable $e) {
            $errors[] = $e->getMessage();
        }
    }
}

if (!empty($errors)) {
    admin_flash_set('error', implode(' ', $errors));
}

$pageTitle = ($id > 0 ? 'Edit' : 'Add') . ' Content Block — Seat Outlet Admin';
$currentPage = 'page-content';
include __DIR__ . '/includes/app-header.php';
?>
        <div class="row mb-3">
            <div class="col">
                <h2 class="page-title"><?php echo $id > 0 ? 'Edit' : 'Add'; ?> Content Block</h2>
            </div>
        </div>

        <form method="post" action="page-content-form<?php echo $id > 0 ? '?id=' . $id : ''; ?>">
            <input type="hidden" name="csrf_token" value="<?php echo admin_csrf_token(); ?>">
            <input type="hidden" name="id" value="<?php echo (int) $id; ?>">

            <div class="card mb-3">
                <div class="card-header"><h3 class="card-title">Which block</h3></div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Page path</label>
                        <input type="text" name="page_path" class="form-control" required
                            placeholder="/about-us"
                            value="<?php echo htmlspecialchars($values['page_path'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                        <small class="form-hint">Path only - no domain, no query string.</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Block key</label>
                        <input type="text" name="block_key" class="form-control" required
                            placeholder="hero-story"
                            value="<?php echo htmlspecialchars($values['block_key'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                        <small class="form-hint">Must match the identifier the page's code was built to look up - see the Page Content list for the known ones.</small>
                    </div>
                    <div class="mb-0">
                        <label class="form-label">Label</label>
                        <input type="text" name="label" class="form-control" required
                            placeholder="About Us — Hero intro"
                            value="<?php echo htmlspecialchars($values['label'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                    </div>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header"><h3 class="card-title">Content</h3></div>
                <div class="card-body">
                    <div class="mb-0">
                        <label class="form-label">HTML</label>
                        <textarea name="content" class="form-control font-monospace" rows="12"
                            placeholder="Leave blank to use the page's own default text."><?php echo htmlspecialchars($values['content'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
                        <small class="form-hint">Raw HTML, same as the Blog editor. Leaving this empty removes the override and the page falls back to its default copy.</small>
                    </div>
                </div>
            </div>

            <button type="submit" class="btn btn-primary">Save</button>
            <a href="page-content" class="btn btn-link">Cancel</a>
        </form>
<?php include __DIR__ . '/includes/app-footer.php'; ?>
