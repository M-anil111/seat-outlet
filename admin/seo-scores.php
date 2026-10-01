<?php
require_once __DIR__ . '/includes/auth.php';
admin_require_login();
require_once __DIR__ . '/../functions.php';

// Static pages: only ones an admin has actually given a focus keyword to -
// scoring every page_rules row (most of which just hold a redirect or a
// one-off schema override, no keyword at all) would mean a lot of pointless
// self-fetches on every load of this screen.
$mysqli = MYSQLI;
$pageRows = [];
$result = $mysqli->query("SELECT * FROM page_rules WHERE focus_keyword IS NOT NULL AND focus_keyword != '' ORDER BY url_path");
$ruleKeywords = [];
while ($row = $result->fetch_assoc()) {
    $row['_score'] = scoreStaticPage($row['url_path'], $row['focus_keyword']);
    $row['_source'] = 'Page rule';
    $pageRows[] = $row;
    $ruleKeywords[$row['url_path']] = true;
}
// Keywords assigned in the code plan (inc/seo-keywords.php) that no page rule has replaced.
foreach (soSeoPlan() as $planPath => $planRow) {
    if (empty($planRow[5]) || isset($ruleKeywords[$planPath])) { continue; }
    $planned = soSeoPlan($planPath);
    $pageRows[] = [
        'url_path' => $planPath, 'focus_keyword' => $planned['keyword'], 'meta_title' => $planned['title'],
        '_score' => scoreStaticPage($planPath, $planned['keyword']), '_source' => 'Keyword plan',
        '_volume' => $planned['volume'], '_difficulty' => $planned['difficulty'],
    ];
}
usort($pageRows, fn($a, $b) => strcmp($a['url_path'], $b['url_path']));

$blogRows = [];
foreach (listBlogPosts() as $post) {
    $post['_score'] = !empty($post['focus_keyword']) ? scoreBlogPost($post) : null;
    $blogRows[] = $post;
}

$pageTitle = 'SEO Scores — Seat Outlet Admin';
$currentPage = 'seo-scores';
include __DIR__ . '/includes/app-header.php';
?>
        <div class="row mb-3 align-items-center">
            <div class="col">
                <h2 class="page-title">Page Content &amp; SEO Scores</h2>
                <div class="text-secondary">
                    A 0-100 on-page SEO score per Focus Keyword, modeled on Rank Math's own scoring checks
                    (title/description/URL/content keyword usage, density, length, links, images, readability) -
                    computed live against each page's actual rendered content, not a static rule.
                </div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header"><h3 class="card-title">Check any URL</h3></div>
            <div class="card-body">
                <form method="get" action="seo-score-detail" class="row g-2 align-items-end">
                    <input type="hidden" name="mode" value="adhoc">
                    <div class="col-md-5">
                        <label class="form-label">Page path</label>
                        <input type="text" name="url" class="form-control" required
                            placeholder="/concerts-city/austin-tx-123 or any live URL path">
                    </div>
                    <div class="col-md-5">
                        <label class="form-label">Focus keyword</label>
                        <input type="text" name="keyword" class="form-control" required
                            placeholder="e.g. Austin concert tickets">
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">Analyze</button>
                    </div>
                </form>
                <small class="form-hint d-block mt-2">
                    Use this for pages built from live data with no stored row - a specific artist-city page,
                    a category listing, etc. Nothing is saved; it just fetches and scores that URL right now.
                </small>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header"><h3 class="card-title">Static pages with a Focus Keyword set</h3></div>
            <div class="table-responsive">
                <table class="table card-table table-vcenter">
                    <thead>
                        <tr>
                            <th>Page</th>
                            <th>Focus keyword</th>
                            <th>Searches / mo</th>
                            <th>Difficulty</th>
                            <th>Score</th>
                            <th class="w-1"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($pageRows)): ?>
                            <tr><td colspan="6" class="text-center text-secondary py-4">
                                No static pages have a Focus Keyword set yet. Add one from
                                <a href="page-rules">Page SEO &amp; Redirects</a>.
                            </td></tr>
                        <?php else: foreach ($pageRows as $row): $score = $row['_score']; ?>
                            <tr>
                                <td><code><?php echo htmlspecialchars($row['url_path'], ENT_QUOTES, 'UTF-8'); ?></code></td>
                                <td>
                                    <?php echo htmlspecialchars($row['focus_keyword'], ENT_QUOTES, 'UTF-8'); ?>
                                    <?php if (($row['_source'] ?? '') === 'Keyword plan'): ?><span class="badge bg-blue-lt ms-1" title="Set in inc/seo-keywords.php. Add a page rule for this path to override it.">plan</span><?php endif; ?>
                                </td>
                                <td><?php echo isset($row['_volume']) ? number_format((int) $row['_volume']) : '<span class="text-secondary">n/a</span>'; ?></td>
                                <td><?php echo isset($row['_difficulty']) ? (int) $row['_difficulty'] : '<span class="text-secondary">n/a</span>'; ?></td>
                                <td>
                                    <span class="badge <?php echo seoScoreBadgeClass($score['score']); ?>">
                                        <?php echo $score['score'] === null ? 'Unreachable' : $score['score'] . '/100'; ?>
                                    </span>
                                </td>
                                <td class="text-end">
                                    <?php if (isset($row['ID'])): ?>
                                    <a href="seo-score-detail?mode=page&amp;id=<?php echo (int) $row['ID']; ?>" class="btn btn-sm btn-outline-secondary">View breakdown</a>
                                    <?php else: ?>
                                    <a href="seo-score-detail?mode=adhoc&amp;url=<?php echo urlencode($row['url_path']); ?>&amp;keyword=<?php echo urlencode($row['focus_keyword']); ?>" class="btn btn-sm btn-outline-secondary">View breakdown</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h3 class="card-title">Blog posts</h3></div>
            <div class="table-responsive">
                <table class="table card-table table-vcenter">
                    <thead>
                        <tr>
                            <th>Post</th>
                            <th>Focus keyword</th>
                            <th>Score</th>
                            <th class="w-1"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($blogRows)): ?>
                            <tr><td colspan="4" class="text-center text-secondary py-4">No blog posts yet.</td></tr>
                        <?php else: foreach ($blogRows as $post): $score = $post['_score']; ?>
                            <tr>
                                <td><?php echo htmlspecialchars($post['title'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td>
                                    <?php if (!empty($post['focus_keyword'])): ?>
                                        <?php echo htmlspecialchars($post['focus_keyword'], ENT_QUOTES, 'UTF-8'); ?>
                                    <?php else: ?>
                                        <span class="text-secondary">— not set —</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($score !== null): ?>
                                        <span class="badge <?php echo seoScoreBadgeClass($score['score']); ?>"><?php echo $score['score']; ?>/100</span>
                                    <?php else: ?>
                                        <span class="text-secondary">—</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <?php if (!empty($post['focus_keyword'])): ?>
                                        <a href="seo-score-detail?mode=blog&amp;id=<?php echo (int) $post['ID']; ?>" class="btn btn-sm btn-outline-secondary">View breakdown</a>
                                    <?php else: ?>
                                        <a href="blog-post-form?id=<?php echo (int) $post['ID']; ?>" class="btn btn-sm btn-outline-primary">Set keyword</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
<?php include __DIR__ . '/includes/app-footer.php'; ?>
