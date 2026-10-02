<?php
require_once __DIR__ . '/includes/auth.php';
admin_require_login();
require_once __DIR__ . '/../functions.php';

$pageTitle = 'Dashboard — Seat Outlet Admin';
$currentPage = 'dashboard';

$allRules = listPageRules();
$totalRules = count($allRules);
$redirectCount = count(array_filter($allRules, fn($r) => !empty($r['redirect_to'])));
$activeCount = count(array_filter($allRules, fn($r) => (int) $r['is_active'] === 1));
$imgStats = imageQueueStats();

include __DIR__ . '/includes/app-header.php';
?>
        <div class="row mb-3">
            <div class="col">
                <h2 class="page-title">Welcome, <?php echo htmlspecialchars($_SESSION['admin_name'], ENT_QUOTES, 'UTF-8'); ?></h2>
                <div class="text-secondary">You're signed in to the Seat Outlet admin panel.</div>
            </div>
        </div>

        <div class="row row-deck row-cards">
            <div class="col-sm-6 col-lg-4">
                <div class="card">
                    <div class="card-body">
                        <div class="subheader">Page Rules</div>
                        <div class="h1 mb-0"><?php echo (int) $totalRules; ?></div>
                        <div class="text-secondary">Total SEO / redirect overrides</div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-4">
                <div class="card">
                    <div class="card-body">
                        <div class="subheader">Active</div>
                        <div class="h1 mb-0"><?php echo (int) $activeCount; ?></div>
                        <div class="text-secondary">Rules currently applied</div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-4">
                <div class="card">
                    <div class="card-body">
                        <div class="subheader">Redirects</div>
                        <div class="h1 mb-0"><?php echo (int) $redirectCount; ?></div>
                        <div class="text-secondary">301 / 302 rules configured</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row row-cards mt-3">
            <div class="col-12">
                <a class="card card-sm text-decoration-none" href="images">
                    <div class="card-body d-flex flex-wrap align-items-center gap-3">
                        <div class="subheader mb-0">Images</div>
                        <div><strong><?php echo (int) $imgStats['resolved_pct']; ?>%</strong> have a picture (<?php echo (int) $imgStats['ok'] + (int) $imgStats['manual']; ?> of <?php echo (int) $imgStats['total']; ?>)</div>
                        <div class="text-secondary"><?php echo (int) $imgStats['pending']; ?> queued, <?php echo (int) $imgStats['fallback']; ?> not found<?php if ($imgStats['oldest_pending_age'] !== null) { echo ', oldest queued ' . htmlspecialchars(imageHumanAge($imgStats['oldest_pending_age']), ENT_QUOTES, 'UTF-8') . ' ago'; } ?></div>
                    </div>
                </a>
            </div>
        </div>

        <div class="mt-4">
            <a href="page-rules" class="btn btn-primary">
                <i class="ti ti-file-search me-1"></i> Manage Page SEO &amp; Redirects
            </a>
        </div>
<?php include __DIR__ . '/includes/app-footer.php'; ?>
