<?php
require_once __DIR__ . '/includes/auth.php';
admin_require_login();
require_once __DIR__ . '/../functions.php';

$id = isset($_GET['id']) ? (int) $_GET['id'] : (isset($_POST['id']) ? (int) $_POST['id'] : 0);
$existing = $id > 0 ? getBlogPostById($id) : null;
if ($id > 0 && !$existing) {
    admin_flash_set('error', 'That blog post no longer exists.');
    header('Location: blog-posts');
    exit;
}

$errors = [];
$values = $existing ?: [
    'title' => '', 'focus_keyword' => '', 'slug' => '', 'excerpt' => '', 'content' => '', 'featured_image' => '',
    'author_name' => '', 'meta_title' => '', 'meta_description' => '', 'status' => 'draft',
    'published_at' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!admin_csrf_verify($_POST['csrf_token'] ?? null)) {
        $errors[] = 'Your session expired. Please try again.';
    } else {
        $values = array_merge($values, $_POST);
        try {
            saveBlogPost(array_merge($values, ['id' => $id]));
            admin_flash_set('success', 'Blog post saved.');
            header('Location: blog-posts');
            exit;
        } catch (Throwable $e) {
            $errors[] = $e->getMessage();
        }
    }
}

if (!empty($errors)) {
    admin_flash_set('error', implode(' ', $errors));
}

$pageTitle = ($id > 0 ? 'Edit' : 'Add') . ' Blog Post — Seat Outlet Admin';
$currentPage = 'blog-posts';
include __DIR__ . '/includes/app-header.php';
?>
        <div class="row mb-3">
            <div class="col">
                <h2 class="page-title"><?php echo $id > 0 ? 'Edit' : 'Add'; ?> Blog Post</h2>
            </div>
        </div>

        <form method="post" action="blog-post-form<?php echo $id > 0 ? '?id=' . $id : ''; ?>">
            <input type="hidden" name="csrf_token" value="<?php echo admin_csrf_token(); ?>">
            <input type="hidden" name="id" value="<?php echo (int) $id; ?>">

            <div class="card mb-3">
                <div class="card-header"><h3 class="card-title">Content</h3></div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Title</label>
                        <input type="text" name="title" class="form-control" required
                            value="<?php echo htmlspecialchars($values['title'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Slug</label>
                        <input type="text" name="slug" class="form-control" placeholder="Leave blank to auto-generate from the title"
                            value="<?php echo htmlspecialchars($values['slug'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                        <small class="form-hint">Public URL will be /blog/{slug}.</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Focus keyword</label>
                        <input type="text" name="focus_keyword" class="form-control"
                            placeholder="e.g. best concert venues in austin"
                            value="<?php echo htmlspecialchars($values['focus_keyword'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                        <small class="form-hint">
                            Save this post, then check its score on the
                            <a href="seo-scores">Page Content &amp; SEO Scores</a> screen.
                        </small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Excerpt</label>
                        <textarea name="excerpt" class="form-control" rows="2"><?php echo htmlspecialchars($values['excerpt'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
                        <small class="form-hint">Shown on the blog listing page and used as the default meta description.</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Featured image URL</label>
                        <input type="text" name="featured_image" class="form-control" placeholder="https://…"
                            value="<?php echo htmlspecialchars($values['featured_image'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Author name</label>
                        <input type="text" name="author_name" class="form-control"
                            value="<?php echo htmlspecialchars($values['author_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                    </div>
                    <div class="mb-0">
                        <label class="form-label">Content (HTML)</label>
                        <textarea name="content" class="form-control font-monospace" rows="16" required><?php echo htmlspecialchars($values['content'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
                        <small class="form-hint">Rendered as raw HTML on the blog post page - write real HTML (paragraphs, headings, links), not Markdown.</small>
                    </div>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header"><h3 class="card-title">SEO metadata (optional)</h3></div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Meta title</label>
                        <input type="text" name="meta_title" class="form-control" placeholder="Defaults to the title"
                            value="<?php echo htmlspecialchars($values['meta_title'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                    </div>
                    <div class="mb-0">
                        <label class="form-label">Meta description</label>
                        <textarea name="meta_description" class="form-control" rows="2" placeholder="Defaults to the excerpt"><?php echo htmlspecialchars($values['meta_description'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
                    </div>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header"><h3 class="card-title">Publishing</h3></div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="draft" <?php echo ($values['status'] ?? 'draft') === 'draft' ? 'selected' : ''; ?>>Draft</option>
                            <option value="published" <?php echo ($values['status'] ?? '') === 'published' ? 'selected' : ''; ?>>Published</option>
                        </select>
                    </div>
                    <div class="mb-0">
                        <label class="form-label">Published date</label>
                        <input type="datetime-local" name="published_at" class="form-control"
                            value="<?php echo !empty($values['published_at']) ? htmlspecialchars(date('Y-m-d\TH:i', strtotime($values['published_at'])), ENT_QUOTES, 'UTF-8') : ''; ?>">
                        <small class="form-hint">Leave blank to publish immediately (right now) the first time this post is marked Published.</small>
                    </div>
                </div>
            </div>

            <button type="submit" class="btn btn-primary">Save</button>
            <a href="blog-posts" class="btn btn-link">Cancel</a>
        </form>
<?php include __DIR__ . '/includes/app-footer.php'; ?>
