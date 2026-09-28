<?php
require_once __DIR__ . '/includes/auth.php';
admin_require_login();
require_once __DIR__ . '/../functions.php';

$mode = $_GET['mode'] ?? '';
$title = '';
$subtitle = '';
$editUrl = null;
$score = null;

if ($mode === 'page') {
    $row = getPageRuleById((int) ($_GET['id'] ?? 0));
    if (!$row) {
        admin_flash_set('error', 'That page rule no longer exists.');
        header('Location: seo-scores');
        exit;
    }
    $title = $row['url_path'];
    $subtitle = 'Focus keyword: ' . $row['focus_keyword'];
    $editUrl = 'page-rule-form?id=' . (int) $row['ID'];
    $score = scoreStaticPage($row['url_path'], $row['focus_keyword']);
} elseif ($mode === 'blog') {
    $post = getBlogPostById((int) ($_GET['id'] ?? 0));
    if (!$post) {
        admin_flash_set('error', 'That blog post no longer exists.');
        header('Location: seo-scores');
        exit;
    }
    $title = $post['title'];
    $subtitle = 'Focus keyword: ' . $post['focus_keyword'];
    $editUrl = 'blog-post-form?id=' . (int) $post['ID'];
    $score = scoreBlogPost($post);
} elseif ($mode === 'adhoc') {
    $url = trim((string) ($_GET['url'] ?? ''));
    $keyword = trim((string) ($_GET['keyword'] ?? ''));
    if ($url === '' || $keyword === '') {
        admin_flash_set('error', 'A page path and focus keyword are both required.');
        header('Location: seo-scores');
        exit;
    }
    $title = $url;
    $subtitle = 'Focus keyword: ' . $keyword . ' (ad-hoc check, nothing saved)';
    $score = scoreStaticPage($url, $keyword);
} else {
    header('Location: seo-scores');
    exit;
}

$pageTitle = 'SEO Score: ' . $title . ' — Seat Outlet Admin';
$currentPage = 'seo-scores';
include __DIR__ . '/includes/app-header.php';

$statusIcon = [
    'pass' => '<i class="ti ti-circle-check text-success"></i>',
    'fail' => '<i class="ti ti-circle-x text-danger"></i>',
    'warning' => '<i class="ti ti-alert-circle text-warning"></i>',
    'na' => '<i class="ti ti-minus text-secondary"></i>',
];
?>
        <div class="row mb-3 align-items-center">
            <div class="col">
                <a href="seo-scores" class="text-secondary">&larr; Back to SEO Scores</a>
                <h2 class="page-title mt-1"><?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?></h2>
                <div class="text-secondary"><?php echo htmlspecialchars($subtitle, ENT_QUOTES, 'UTF-8'); ?></div>
            </div>
            <div class="col-auto text-center">
                <div class="display-6">
                    <span class="badge <?php echo seoScoreBadgeClass($score['score']); ?> fs-2 px-3 py-2">
                        <?php echo $score['score'] === null ? 'N/A' : $score['score'] . '/100'; ?>
                    </span>
                </div>
                <?php if ($editUrl): ?>
                    <a href="<?php echo htmlspecialchars($editUrl, ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-sm btn-outline-secondary mt-2">Edit</a>
                <?php endif; ?>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h3 class="card-title">Checklist</h3></div>
            <div class="list-group list-group-flush">
                <?php foreach ($score['checks'] as $check): ?>
                    <div class="list-group-item">
                        <div class="d-flex align-items-start gap-2">
                            <div><?php echo $statusIcon[$check['status']] ?? ''; ?></div>
                            <div class="flex-fill">
                                <div class="fw-semibold">
                                    <?php echo htmlspecialchars($check['label'], ENT_QUOTES, 'UTF-8'); ?>
                                    <?php if ($check['weight'] > 0): ?>
                                        <span class="text-secondary small">(<?php echo $check['earned']; ?>/<?php echo $check['weight']; ?> pts)</span>
                                    <?php endif; ?>
                                </div>
                                <div class="text-secondary small"><?php echo htmlspecialchars($check['message'], ENT_QUOTES, 'UTF-8'); ?></div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
<?php include __DIR__ . '/includes/app-footer.php'; ?>
