<?php
require_once __DIR__ . '/includes/auth.php';
admin_require_login();
require_once __DIR__ . '/../functions.php';

$search = trim((string) ($_GET['q'] ?? ''));
$blocks = listPageContentBlocks($search);

$pageTitle = 'Page Content — Seat Outlet Admin';
$currentPage = 'page-content';
include __DIR__ . '/includes/app-header.php';
?>
        <div class="row mb-3 align-items-center">
            <div class="col">
                <h2 class="page-title">Page Content</h2>
                <div class="text-secondary">Edit specific copy blocks on static pages without a code deploy. Blocks with no override yet show the page's current live text.</div>
            </div>
            <div class="col-auto">
                <a href="page-content-form" class="btn btn-primary">
                    <i class="ti ti-plus me-1"></i> Add content block
                </a>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <form method="get" class="d-flex w-100" role="search">
                    <input type="text" name="q" class="form-control" placeholder="Search by page, block key, or label…"
                        value="<?php echo htmlspecialchars($search, ENT_QUOTES, 'UTF-8'); ?>">
                    <button class="btn btn-outline-secondary ms-2" type="submit">Search</button>
                </form>
            </div>
            <div class="table-responsive">
                <table class="table card-table table-vcenter">
                    <thead>
                        <tr>
                            <th>Page</th>
                            <th>Block</th>
                            <th>Status</th>
                            <th>Last updated</th>
                            <th class="w-1"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($blocks)): ?>
                            <tr>
                                <td colspan="5" class="text-center text-secondary py-4">
                                    No content blocks found.
                                </td>
                            </tr>
                        <?php else: foreach ($blocks as $block): ?>
                            <tr>
                                <td><code><?php echo htmlspecialchars($block['page_path'], ENT_QUOTES, 'UTF-8'); ?></code></td>
                                <td>
                                    <?php echo htmlspecialchars($block['label'], ENT_QUOTES, 'UTF-8'); ?>
                                    <div class="text-secondary small"><?php echo htmlspecialchars($block['block_key'], ENT_QUOTES, 'UTF-8'); ?></div>
                                </td>
                                <td>
                                    <?php if (!empty($block['content'])): ?>
                                        <span class="badge bg-blue-lt">Customized</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary-lt">Using page default</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo $block['updated_at'] ? htmlspecialchars($block['updated_at'], ENT_QUOTES, 'UTF-8') : '—'; ?></td>
                                <td class="text-end">
                                    <?php if (!empty($block['ID'])): ?>
                                        <a href="page-content-form?id=<?php echo (int) $block['ID']; ?>" class="btn btn-sm btn-outline-secondary">Edit</a>
                                        <form method="post" action="page-content-delete" class="d-inline"
                                            onsubmit="return confirm('Delete this override? The page will fall back to its own default text.');">
                                            <input type="hidden" name="csrf_token" value="<?php echo admin_csrf_token(); ?>">
                                            <input type="hidden" name="id" value="<?php echo (int) $block['ID']; ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                        </form>
                                    <?php else: ?>
                                        <a href="page-content-form?page_path=<?php echo urlencode($block['page_path']); ?>&amp;block_key=<?php echo urlencode($block['block_key']); ?>&amp;label=<?php echo urlencode($block['label']); ?>"
                                            class="btn btn-sm btn-outline-primary">Customize</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
<?php include __DIR__ . '/includes/app-footer.php'; ?>
