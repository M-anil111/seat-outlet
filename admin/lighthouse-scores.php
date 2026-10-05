<?php
require_once __DIR__ . '/includes/auth.php';
admin_require_login();
require_once __DIR__ . '/../functions.php';

$dataFile = __DIR__ . '/../data/lighthouse-scores.json';
$scores = [];
$fileExists = file_exists($dataFile);
if ($fileExists) {
    $decoded = json_decode((string) file_get_contents($dataFile), true);
    if (is_array($decoded)) {
        $scores = $decoded;
    }
}

$lastRun = null;
foreach ($scores as $row) {
    if (!empty($row['fetched_at']) && ($lastRun === null || $row['fetched_at'] > $lastRun)) {
        $lastRun = $row['fetched_at'];
    }
}

$pageTitle = 'Lighthouse Scores — Seat Outlet Admin';
$currentPage = 'lighthouse-scores';
include __DIR__ . '/includes/app-header.php';
?>
        <div class="row mb-3 align-items-center">
            <div class="col">
                <h2 class="page-title">Lighthouse Scores</h2>
                <div class="text-secondary">
                    Real Google Lighthouse results (<a href="https://github.com/GoogleChrome/lighthouse" target="_blank" rel="noopener">github.com/GoogleChrome/lighthouse</a>) -
                    Performance, Accessibility, Best Practices, and SEO - for every static page and blog post in the live sitemap.
                </div>
            </div>
        </div>

        <div class="alert alert-info">
            This runs in GitHub Actions (<code>.github/workflows/lighthouse.yml</code>), not on this server - real Lighthouse needs
            a real headless Chrome to measure actual page-load performance, which this PHP host doesn't run. The workflow fetches
            <code>/sitemaps/sitemap.xml</code>, runs Lighthouse against every URL in it except individual <code>/event/...</code> pages
            (too many, and constantly changing - not a stable set to track), and commits the results here. It runs weekly on its
            own, or trigger it manually from the repo's Actions tab ("Lighthouse scores" → "Run workflow"). Requires the
            <code>LIGHTHOUSE_SITE_URL</code> repository variable to be set to this site's real public URL - see CONTRIBUTING.md.
            <?php if ($lastRun): ?>
                <br>Last run: <strong><?php echo htmlspecialchars($lastRun, ENT_QUOTES, 'UTF-8'); ?></strong>
            <?php endif; ?>
        </div>

        <div class="card">
            <div class="table-responsive">
                <table class="table card-table table-vcenter">
                    <thead>
                        <tr>
                            <th>URL</th>
                            <th>Performance</th>
                            <th>Accessibility</th>
                            <th>Best Practices</th>
                            <th>SEO</th>
                            <th>Last run</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($scores)): ?>
                            <tr>
                                <td colspan="6" class="text-center text-secondary py-4">
                                    <?php if (!$fileExists): ?>
                                        No Lighthouse run has completed yet. Trigger the workflow from the Actions tab, or wait for
                                        its weekly schedule.
                                    <?php else: ?>
                                        The last run produced no results - check the workflow's logs.
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php else: foreach ($scores as $row): ?>
                            <tr>
                                <td><code><?php echo htmlspecialchars($row['url'] ?? '', ENT_QUOTES, 'UTF-8'); ?></code></td>
                                <td><span class="badge <?php echo seoScoreBadgeClass($row['performance'] ?? null); ?>"><?php echo $row['performance'] ?? '—'; ?></span></td>
                                <td><span class="badge <?php echo seoScoreBadgeClass($row['accessibility'] ?? null); ?>"><?php echo $row['accessibility'] ?? '—'; ?></span></td>
                                <td><span class="badge <?php echo seoScoreBadgeClass($row['best_practices'] ?? null); ?>"><?php echo $row['best_practices'] ?? '—'; ?></span></td>
                                <td><span class="badge <?php echo seoScoreBadgeClass($row['seo'] ?? null); ?>"><?php echo $row['seo'] ?? '—'; ?></span></td>
                                <td class="text-secondary small"><?php echo htmlspecialchars($row['fetched_at'] ?? '—', ENT_QUOTES, 'UTF-8'); ?></td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
<?php include __DIR__ . '/includes/app-footer.php'; ?>
