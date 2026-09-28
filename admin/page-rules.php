<?php
require_once __DIR__ . '/includes/auth.php';
admin_require_login();
require_once __DIR__ . '/../functions.php';

$search = trim((string) ($_GET['q'] ?? ''));
$rules = listPageRules($search);

$pageTitle = 'Page SEO & Redirects — Seat Outlet Admin';
$currentPage = 'page-rules';
include __DIR__ . '/includes/app-header.php';
?>
        <div class="row mb-3 align-items-center">
            <div class="col">
                <h2 class="page-title">Page SEO &amp; Redirects</h2>
                <div class="text-secondary">Per-URL overrides for title, meta description, canonical tag, robots, schema, and 301/302 redirects.</div>
            </div>
            <div class="col-auto">
                <a href="page-rule-form" class="btn btn-primary">
                    <i class="ti ti-plus me-1"></i> Add page rule
                </a>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <form method="get" class="d-flex w-100" role="search">
                    <input type="text" name="q" class="form-control" placeholder="Search by URL path…"
                        value="<?php echo htmlspecialchars($search, ENT_QUOTES, 'UTF-8'); ?>">
                    <button class="btn btn-outline-secondary ms-2" type="submit">Search</button>
                </form>
            </div>
            <div class="table-responsive">
                <table class="table card-table table-vcenter">
                    <thead>
                        <tr>
                            <th>URL path</th>
                            <th>Meta title</th>
                            <th>Redirect</th>
                            <th>Robots</th>
                            <th>Status</th>
                            <th class="w-1"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($rules)): ?>
                            <tr>
                                <td colspan="6" class="text-center text-secondary py-4">
                                    No page rules yet. Add one to override SEO metadata or set up a redirect for a specific URL.
                                </td>
                            </tr>
                        <?php else: foreach ($rules as $rule): ?>
                            <tr>
                                <td><code><?php echo htmlspecialchars($rule['url_path'], ENT_QUOTES, 'UTF-8'); ?></code></td>
                                <td><?php echo htmlspecialchars($rule['meta_title'] ?? '—', ENT_QUOTES, 'UTF-8'); ?></td>
                                <td>
                                    <?php if (!empty($rule['redirect_to'])): ?>
                                        <span class="badge bg-blue-lt"><?php echo (int) $rule['redirect_code']; ?></span>
                                        <?php echo htmlspecialchars($rule['redirect_to'], ENT_QUOTES, 'UTF-8'); ?>
                                    <?php else: ?>
                                        <span class="text-secondary">—</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($rule['robots'] ?? '—', ENT_QUOTES, 'UTF-8'); ?></td>
                                <td>
                                    <?php if ((int) $rule['is_active'] === 1): ?>
                                        <span class="badge bg-green-lt">Active</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary-lt">Disabled</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <a href="page-rule-form?id=<?php echo (int) $rule['ID']; ?>" class="btn btn-sm btn-outline-secondary">Edit</a>
                                    <form method="post" action="page-rule-delete" class="d-inline"
                                        onsubmit="return confirm('Delete this page rule?');">
                                        <input type="hidden" name="csrf_token" value="<?php echo admin_csrf_token(); ?>">
                                        <input type="hidden" name="id" value="<?php echo (int) $rule['ID']; ?>">
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
