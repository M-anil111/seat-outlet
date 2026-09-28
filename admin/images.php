<?php
require_once __DIR__ . '/includes/auth.php';
admin_require_login();
require_once __DIR__ . '/../functions.php';

$search = trim((string) ($_GET['q'] ?? ''));
$status = trim((string) ($_GET['status'] ?? ''));
$type   = trim((string) ($_GET['type'] ?? ''));
$page   = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 50;

$where = ['entity_type IS NOT NULL'];
$args = []; $types = '';
if ($search !== '') { $where[] = 'entity_name LIKE CONCAT(\'%\', ?, \'%\')'; $args[] = $search; $types .= 's'; }
if (in_array($status, ['ok', 'manual', 'fallback', 'pending'], true)) { $where[] = 'status = ?'; $args[] = $status; $types .= 's'; }
if (in_array($type, ['artist', 'team', 'venue', 'festival', 'city'], true)) { $where[] = 'entity_type = ?'; $args[] = $type; $types .= 's'; }
$whereSql = implode(' AND ', $where);

$mysqli = MYSQLI;
$countStmt = $mysqli->prepare("SELECT COUNT(*) AS c FROM images WHERE $whereSql");
if ($types !== '') $countStmt->bind_param($types, ...$args);
$countStmt->execute();
$total = (int) ($countStmt->get_result()->fetch_assoc()['c'] ?? 0);
$countStmt->close();

$offset = ($page - 1) * $perPage;
$stmt = $mysqli->prepare("SELECT * FROM images WHERE $whereSql ORDER BY updated_at DESC LIMIT $perPage OFFSET $offset");
if ($types !== '') $stmt->bind_param($types, ...$args);
$stmt->execute();
$rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$summary = [];
$sumRes = $mysqli->query("SELECT status, COUNT(*) AS c FROM images WHERE entity_type IS NOT NULL GROUP BY status");
while ($sumRes && ($r = $sumRes->fetch_assoc())) { $summary[$r['status']] = (int) $r['c']; }

$pageTitle = 'Images — Seat Outlet Admin';
$currentPage = 'images';
include __DIR__ . '/includes/app-header.php';
$badge = ['ok' => 'bg-green-lt', 'manual' => 'bg-blue-lt', 'fallback' => 'bg-yellow-lt', 'pending' => 'bg-secondary-lt'];
$qs = function (array $over) use ($search, $status, $type, $page) {
    return htmlspecialchars(http_build_query(array_merge(['q' => $search, 'status' => $status, 'type' => $type, 'page' => $page], $over)), ENT_QUOTES, 'UTF-8');
};
?>
        <div class="row mb-3 align-items-center">
            <div class="col">
                <h2 class="page-title">Images</h2>
                <div class="text-secondary">
                    Performer, team, venue, festival and city images resolved by <code>cron/resolve-images.php</code>, with their source and license.
                    Override any image here; a manual image is never replaced automatically.
                </div>
            </div>
            <div class="col-auto">
                <a href="images-form" class="btn btn-primary"><i class="ti ti-photo-plus me-1"></i> Set an image</a>
            </div>
        </div>

        <div class="row row-cards mb-3">
            <?php foreach (['ok' => 'Resolved', 'manual' => 'Manual', 'fallback' => 'No image found', 'pending' => 'Queued'] as $k => $label): ?>
            <div class="col-6 col-md-3">
                <a class="card card-sm text-decoration-none" href="images?<?php echo $qs(['status' => $k, 'page' => 1]); ?>">
                    <div class="card-body">
                        <div class="text-secondary"><?php echo $label; ?></div>
                        <div class="h2 mb-0"><?php echo (int) ($summary[$k] ?? 0); ?></div>
                    </div>
                </a>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="card">
            <div class="card-header">
                <form method="get" class="d-flex w-100 gap-2" role="search">
                    <input type="text" name="q" class="form-control" placeholder="Search by name…" value="<?php echo htmlspecialchars($search, ENT_QUOTES, 'UTF-8'); ?>">
                    <select name="type" class="form-select w-auto">
                        <option value="">All types</option>
                        <?php foreach (['artist', 'team', 'venue', 'festival', 'city'] as $t): ?>
                            <option value="<?php echo $t; ?>" <?php echo $type === $t ? 'selected' : ''; ?>><?php echo ucfirst($t); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <select name="status" class="form-select w-auto">
                        <option value="">All statuses</option>
                        <?php foreach (['ok', 'manual', 'fallback', 'pending'] as $s): ?>
                            <option value="<?php echo $s; ?>" <?php echo $status === $s ? 'selected' : ''; ?>><?php echo ucfirst($s); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button class="btn btn-outline-secondary" type="submit">Filter</button>
                </form>
            </div>
            <div class="table-responsive">
                <table class="table card-table table-vcenter">
                    <thead>
                        <tr>
                            <th class="w-1"></th>
                            <th>Entity</th>
                            <th>Status</th>
                            <th>Source / license</th>
                            <th>Updated</th>
                            <th class="w-1"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($rows)): ?>
                            <tr><td colspan="6" class="text-center text-secondary py-4">No images match.</td></tr>
                        <?php else: foreach ($rows as $row): ?>
                            <tr>
                                <td>
                                    <?php if (!empty($row['url'])): ?>
                                        <a href="<?php echo htmlspecialchars($row['url'], ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener">
                                            <span class="avatar avatar-md" style="background-image:url('<?php echo htmlspecialchars($row['url'], ENT_QUOTES, 'UTF-8'); ?>')"></span>
                                        </a>
                                    <?php else: ?>
                                        <span class="avatar avatar-md">—</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars($row['entity_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
                                    <div class="text-secondary small"><?php echo htmlspecialchars($row['entity_type'] ?? '', ENT_QUOTES, 'UTF-8'); ?></div>
                                </td>
                                <td>
                                    <span class="badge <?php echo $badge[$row['status']] ?? ''; ?>"><?php echo htmlspecialchars($row['status'], ENT_QUOTES, 'UTF-8'); ?></span>
                                    <?php if ($row['status'] === 'fallback' && !empty($row['expires_at'])): ?>
                                        <div class="text-secondary small">retry <?php echo htmlspecialchars(date('M j', strtotime($row['expires_at'])), ENT_QUOTES, 'UTF-8'); ?> · <?php echo (int) $row['attempts']; ?> attempt(s)</div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($row['source'])): ?>
                                        <?php if (!empty($row['source_url'])): ?><a href="<?php echo htmlspecialchars($row['source_url'], ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener"><?php echo htmlspecialchars($row['source'], ENT_QUOTES, 'UTF-8'); ?></a><?php else: echo htmlspecialchars($row['source'], ENT_QUOTES, 'UTF-8'); endif; ?>
                                        <div class="text-secondary small"><?php echo htmlspecialchars($row['license'] ?? '', ENT_QUOTES, 'UTF-8'); ?></div>
                                        <div class="text-secondary small"><?php echo htmlspecialchars(mb_substr($row['attribution'] ?? '', 0, 80), ENT_QUOTES, 'UTF-8'); ?></div>
                                    <?php else: ?>
                                        <span class="text-secondary">—</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-secondary"><?php echo htmlspecialchars($row['updated_at'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td>
                                    <div class="btn-list flex-nowrap">
                                        <a class="btn btn-sm" href="images-form?id=<?php echo (int) $row['ID']; ?>">Set image</a>
                                        <?php if ($row['status'] !== 'pending'): ?>
                                        <form method="post" action="images-action" class="d-inline">
                                            <input type="hidden" name="csrf_token" value="<?php echo admin_csrf_token(); ?>">
                                            <input type="hidden" name="id" value="<?php echo (int) $row['ID']; ?>">
                                            <input type="hidden" name="do" value="retry">
                                            <button class="btn btn-sm btn-outline-secondary" type="submit" title="Clear and let the cron resolve it again">Re-resolve</button>
                                        </form>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
            <?php if ($total > $perPage): ?>
            <div class="card-footer d-flex align-items-center">
                <p class="m-0 text-secondary">Showing <?php echo $offset + 1; ?>–<?php echo min($offset + $perPage, $total); ?> of <?php echo $total; ?></p>
                <ul class="pagination m-0 ms-auto">
                    <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>"><a class="page-link" href="images?<?php echo $qs(['page' => $page - 1]); ?>">prev</a></li>
                    <li class="page-item <?php echo $offset + $perPage >= $total ? 'disabled' : ''; ?>"><a class="page-link" href="images?<?php echo $qs(['page' => $page + 1]); ?>">next</a></li>
                </ul>
            </div>
            <?php endif; ?>
        </div>
<?php include __DIR__ . '/includes/app-footer.php'; ?>
