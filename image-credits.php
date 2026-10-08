<?php
require_once 'functions.php';

// Photo credits for every stored picture that carries a licence (CC BY / BY-SA need a visible notice; cards are too small
// to hold one, so each card links here). Read-only list built from the images table.
$rows = [];
$res = MYSQLI->query("SELECT entity_type, entity_name, url, source, source_url, license, attribution FROM images
                       WHERE status IN ('ok', 'manual') AND url <> '' AND entity_name IS NOT NULL AND (license IS NOT NULL AND license <> '')
                       ORDER BY entity_name ASC LIMIT 3000");
while ($res && ($r = $res->fetch_assoc())) { $rows[] = $r; }

$pageRobots = 'noindex, follow';   // a utility page for attribution, not something to rank
$pageMetaTitle = 'Photo credits | Seat Outlet';
$pageMetaDescription = 'Who took the photos on Seat Outlet, and under which licence they are used.';
$pageCanonicalUrl = HOME_URL . '/image-credits';
include 'header.php';
$h = fn($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
?>
<section>
	<div class="container py-4">
		<h1 class="fs-2 fw-bold mb-2">Photo credits</h1>
		<p class="text-muted mb-4">Pictures of performers, teams, venues and cities come from Wikimedia Commons, Pixabay, Unsplash and similar sources under open licences. Photos are resized and cropped for our pages; Unsplash photos are loaded directly from Unsplash and credited to the photographer. Logos and posters are never used. Where no licensed photo exists we show an initials tile instead.</p>
		<?php if (!$rows) { ?>
			<p>No licensed photos are stored yet.</p>
		<?php } else { ?>
			<div class="table-responsive">
				<table class="table align-middle">
					<thead><tr><th scope="col">Photo of</th><th scope="col">Credit</th><th scope="col">Licence</th><th scope="col">Source</th></tr></thead>
					<tbody>
					<?php foreach ($rows as $r) { $lu = imageLicenseUrl($r['license']); ?>
						<tr>
							<td><?php echo $h($r['entity_name']); ?></td>
							<td><?php echo $h(($r['source'] ?? '') === 'unsplash' ? trim(explode('|', (string) ($r['attribution'] ?? ''))[0]) . ' (Unsplash)' : ($r['attribution'] ?? '')); ?></td>
							<td><?php echo $lu !== '' ? '<a href="' . $h($lu) . '" rel="license noopener nofollow" target="_blank">' . $h($r['license']) . '</a>' : $h($r['license']); ?></td>
							<td><?php echo preg_match('#^https?://#i', (string) $r['source_url']) ? '<a href="' . $h($r['source_url']) . '" rel="noopener nofollow" target="_blank">' . $h($r['source'] ?: 'Source') . '</a>' : $h($r['source'] ?? ''); ?></td>
						</tr>
					<?php } ?>
					</tbody>
				</table>
			</div>
		<?php } ?>
	</div>
</section>
<?php include 'footer.php'; ?>
