<?php
require_once __DIR__ . '/includes/auth.php';
admin_require_login();
require_once __DIR__ . '/../functions.php';

$search = trim((string) ($_GET['q'] ?? ''));
$posts = listBlogPosts($search);

$pageTitle = 'Blog Posts — Seat Outlet Admin';
$currentPage = 'blog-posts';
include __DIR__ . '/includes/app-header.php';
?>
        <div class="row mb-3 align-items-center">
            <div class="col">
                <h2 class="page-title">Blog Posts</h2>
                <div class="text-secondary">Create and manage Seat Outlet blog content.</div>
            </div>
            <div class="col-auto">
                <a href="blog-post-form" class="btn btn-primary">
                    <i class="ti ti-plus me-1"></i> Add post
                </a>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <form method="get" class="d-flex w-100" role="search">
                    <input type="text" name="q" class="form-control" placeholder="Search by title…"
                        value="<?php echo htmlspecialchars($search, ENT_QUOTES, 'UTF-8'); ?>">
                    <button class="btn btn-outline-secondary ms-2" type="submit">Search</button>
                </form>
            </div>
            <div class="table-responsive">
                <table class="table card-table table-vcenter">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Slug</th>
                            <th>Status</th>
                            <th>Published</th>
                            <th class="w-1"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($posts)): ?>
                            <tr>
                                <td colspan="5" class="text-center text-secondary py-4">
                                    No blog posts yet. Add one to get started.
                                </td>
                            </tr>
                        <?php else: foreach ($posts as $post): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($post['title'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><code>/blog/<?php echo htmlspecialchars($post['slug'], ENT_QUOTES, 'UTF-8'); ?></code></td>
                                <td>
                                    <?php if ($post['status'] === 'published'): ?>
                                        <span class="badge bg-green-lt">Published</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary-lt">Draft</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($post['published_at'] ? date('M j, Y', strtotime($post['published_at'])) : '—', ENT_QUOTES, 'UTF-8'); ?></td>
                                <td class="text-end">
                                    <a href="blog-post-form?id=<?php echo (int) $post['ID']; ?>" class="btn btn-sm btn-outline-secondary">Edit</a>
                                    <form method="post" action="blog-post-delete" class="d-inline"
                                        onsubmit="return confirm('Delete this blog post?');">
                                        <input type="hidden" name="csrf_token" value="<?php echo admin_csrf_token(); ?>">
                                        <input type="hidden" name="id" value="<?php echo (int) $post['ID']; ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
<?php include __DIR__ . '/includes/app-footer.php'; ?>
