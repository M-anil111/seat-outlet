<?php
require_once 'functions.php';

// Page name: this is the A-Z directory of every artist, team and show, so it is called "Artists, Teams & Shows" (nav label: "Artists & Teams").
$perPage = 48;
$letterIn = strtoupper(trim((string) ($_GET['letter'] ?? '')));
$letter = (preg_match('/^[A-Z]$/', $letterIn) || $letterIn === '0-9') ? $letterIn : '';
$page = max(1, min(500, (int) ($_GET['page'] ?? 1)));

// Page name: this is the A-Z directory of every artist, team and show, so it is called "Artists, Teams & Shows" (nav label: "Artists & Teams").
// Every letter is its own crawlable address (?letter=A, ?letter=0-9) rendered on the server, with numbered pages.
$letterTitle = $letter === '' ? 'Artists, Teams & Shows A-Z' : ($letter === '0-9' ? 'Artists, Teams & Shows starting with a number' : 'Artists, Teams & Shows starting with ' . $letter);
$pageMetaTitle       = $letterTitle . ($page > 1 ? ' - Page ' . $page : '') . ' | Seat Outlet';
$pageMetaDescription = $letter === ''
	? 'Browse every artist, team and show on Seat Outlet, A to Z. Find upcoming events, compare prices and buy tickets with a 100% Worry-Free Guarantee.'
	: 'Artists, teams and shows ' . ($letter === '0-9' ? 'starting with a number' : 'starting with ' . $letter) . ' with tickets on sale at Seat Outlet. Compare prices and buy with a 100% Worry-Free Guarantee.';
$soDirBase = '/all-artists-and-teams';
$soDirUrl = function ($l, $pg = 1) use ($soDirBase) {
	$q = [];
	if ($l !== '') { $q['letter'] = $l; }
	if ($pg > 1) { $q['page'] = $pg; }
	return $soDirBase . ($q ? '?' . http_build_query($q) : '');
};
$pageCanonicalUrl    = HOME_URL . $soDirUrl($letter, $page);
$pageSearchPlaceholder = 'Artists, teams or shows';
include 'header.php';

$filters = [];
if ($letter === '0-9') {
	$filters[] = '(' . implode(' or ', array_map(function ($d) { return "startswith(text/name,'$d')"; }, range(0, 9))) . ')';
} elseif ($letter !== '') {
	$filters[] = "startswith(text/name,'" . tnEscapeFilterValue($letter) . "')";
}
$params = ['page' => $page, 'perPage' => $perPage, 'includeTotalCount' => 'true', 'sort' => 'text/name'];
if ($filters) { $params['filter'] = implode(' and ', $filters); }

$performers  = [];
$totalCount  = 0;
$totalPages  = 0;
$apiError    = false;

// Test and placeholder entries ("000000_TL", names with no letters or with an underscore) are not artists, teams or shows.
$soIsJunk = function ($name) {
	return !preg_match('/\p{L}/u', $name) || strpos($name, '_') !== false || preg_match('/^0{3,}/', $name);
};

try {
	$response   = getTnPerformers($params);
	$totalCount = (int) ($response['totalCount'] ?? 0);
	$totalPages = (int) ceil($totalCount / $perPage);
	foreach ($response['results'] ?? [] as $performer) {
		$name = trim((string) ($performer['text']['name'] ?? ''));
		$uriComponent = $performer['uriComponent'] ?? '';
		if ($name === '' || $uriComponent === '' || $soIsJunk($name)) {
			continue;
		}
		$defaultCategory = $performer['defaultCategory'] ?? [];
		$image = getPerformerImage($name, $defaultCategory);
		if (!$image) {
			$image = getCategoryFallbackImage($defaultCategory, 'performer');
		}
		$performers[] = [
			'name'         => $name,
			'uriComponent' => rawurlencode($uriComponent),
			'genre'        => getPerformerGenreLabel($defaultCategory),
			'image'        => $image,
		];
	}
} catch (Throwable $e) {
	\Sentry\captureException($e);
	$apiError = true;
}
?>

<style>
	.performers-hero-section {
		position: relative;
		overflow: hidden;
		color: #ffffff;
		background: linear-gradient(135deg, #2556e0 0%, #1b3fb3 100%);
	}
	.performers-hero-section::before {
		content: "";
		position: absolute;
		inset: 0;
		background: radial-gradient(60% 130% at 88% 0%, rgba(255, 255, 255, .24) 0%, rgba(255, 255, 255, 0) 62%);
		pointer-events: none;
	}
	.performers-hero-section .hero-bg-photo {
		position: absolute;
		inset: 0;
		background-image: url('<?php echo HOME_URL; ?>/images/crowd-at-concert-or-event.webp');
		background-size: cover;
		background-position: center;
		opacity: .07;
		mix-blend-mode: luminosity;
	}
	.performers-hero-section .container { position: relative; z-index: 1; }
	.performers-hero-section .hero-inner { padding: 56px 0 60px; max-width: 640px; }
	.performers-hero-section .hero-eyebrow {
		display: inline-block;
		margin-bottom: 12px;
		font-size: 12px;
		font-weight: 600;
		letter-spacing: .14em;
		text-transform: uppercase;
		color: rgba(255, 255, 255, .82);
	}
	.performers-hero-section .hero-title {
		margin: 0 0 14px;
		font-weight: 800;
		font-size: clamp(34px, 5vw, 56px);
		line-height: 1.08;
		letter-spacing: -.02em;
		color: #ffffff;
	}
	.performers-hero-section .hero-subtitle {
		max-width: 520px;
		margin: 0;
		font-size: clamp(15px, 1.6vw, 18px);
		line-height: 1.5;
		color: rgba(255, 255, 255, .9);
	}
	@media (max-width: 767.98px) {
		.performers-hero-section .hero-inner { padding: 36px 0 40px; }
	}

	/* A-Z bar: one soft rounded control. Swipes on a phone (edges fade where there is more), wraps on a larger screen. */
	.performer-filter-row { display: block; padding: 0 0 26px; }
	.performer-filter-wrap {
		--fade-l: 0px;
		--fade-r: 0px;
		display: flex;
		flex-wrap: nowrap;
		gap: 4px;
		padding: 6px;
		overflow-x: auto;
		background: #ffffff;
		border: 1px solid #e6eaf3;
		border-radius: 999px;
		box-shadow: 0 6px 20px rgba(17, 24, 39, .06);
		scroll-snap-type: x proximity;
		-webkit-overflow-scrolling: touch;
		-ms-overflow-style: none;
		scrollbar-width: none;
		-webkit-mask-image: linear-gradient(90deg, transparent 0, #000 var(--fade-l), #000 calc(100% - var(--fade-r)), transparent 100%);
		mask-image: linear-gradient(90deg, transparent 0, #000 var(--fade-l), #000 calc(100% - var(--fade-r)), transparent 100%);
	}
	.performer-filter-wrap::-webkit-scrollbar { display: none; }
	.performer-filter-btn {
		flex: 0 0 auto;
		scroll-snap-align: center;
		min-width: 44px;
		height: 44px;
		padding: 0 4px;
		display: flex;
		align-items: center;
		justify-content: center;
		border: 0;
		border-radius: 999px;
		background: transparent;
		color: #374151;
		font-weight: 600;
		font-size: 15px;
		cursor: pointer;
		transition: background .2s ease, color .2s ease, box-shadow .2s ease;
	}
	.performer-filter-btn.is-all { padding: 0 20px; }
	.performer-filter-btn:hover { background: #eef3ff; color: #2556E0; }
	.performer-filter-btn:focus-visible { outline: 3px solid #2556E0; outline-offset: 2px; }
	.performer-filter-btn.active {
		background: #2556E0;
		color: #ffffff;
		box-shadow: 0 4px 12px rgba(37, 86, 224, .35);
	}
	@media (min-width: 768px) {
		.performer-filter-wrap {
			flex-wrap: wrap;
			justify-content: center;
			overflow: visible;
			border-radius: 26px;
			-webkit-mask-image: none;
			mask-image: none;
		}
		.performer-filter-btn { min-width: 40px; height: 40px; font-size: 14px; }
	}

	/* Performer cards */
	.performer-card {
		background: #ffffff;
		border-radius: 14px;
		overflow: hidden;
		box-shadow: 0 4px 18px rgba(17, 24, 39, .07);
		height: 100%;
		display: flex;
		flex-direction: column;
	}
	.performer-card .performer-img-wrap {
		aspect-ratio: 4/3;
		overflow: hidden;
	}
	.performer-card .performer-img-wrap img {
		width: 100%;
		height: 100%;
		object-fit: cover;
	}
	.performer-card .performer-body {
		padding: 16px 18px 18px;
		display: flex;
		flex-direction: column;
		align-items: flex-start;
		gap: 14px;
		flex: 1;
	}
	.performer-card .performer-name {
		font-weight: 700;
		color: #111827;
		font-size: 16px;
		margin-bottom: 2px;
	}
	.performer-card .performer-genre {
		color: #6b7280;
		font-size: 13.5px;
	}
	.btn-view-performer {
		display: inline-block;
		background: #ffffff;
		color: #2556E0;
		font-weight: 600;
		font-size: 13.5px;
		padding: 8px 18px;
		border-radius: 999px;
		border: 1.5px solid #2556E0;
		text-decoration: none;
		white-space: nowrap;
		transition: background .2s, color .2s, transform .15s;
	}
	.btn-view-performer:hover,
	.btn-view-performer:focus-visible { background: #2556e0; border-color: #2556e0; color: #fff; transform: translateY(-1px); }

	.btn-load-more-performers {
		background: #ffffff;
		color: #2556E0;
		border: 1.5px solid #2556E0;
		font-weight: 600;
		font-size: 15px;
		padding: 12px 30px;
		border-radius: 999px;
		transition: background .2s, color .2s, transform .15s;
	}
	.btn-load-more-performers:hover { background: #2556E0; color: #fff; transform: translateY(-1px); }
	.btn-load-more-performers:disabled { opacity: .6; cursor: default; transform: none; }

	.performers-empty-state, .performers-error-state {
		text-align: center;
		padding: 40px 20px;
		color: #6b7280;
	}
</style>

<section class="performers-hero-section">
	<div class="hero-bg-photo"></div>
	<div class="container">
		<div class="hero-inner">
			<span class="hero-eyebrow">A to Z</span>
			<h1 class="hero-title">Artists, Teams &amp; Shows</h1>
			<p class="hero-subtitle">Browse all artists, teams and shows on Seat Outlet, from A to Z. Pick a letter to jump straight in.</p>
		</div>
	</div>
</section>

<section class="py-4 py-lg-5">
	<div class="container">

		<nav class="performer-filter-row" id="performerFilterRow" aria-label="Artists, teams and shows starting with">
			<div class="performer-filter-wrap" id="performerFilterBar">
				<a class="performer-filter-btn is-all<?php echo $letter === '' ? ' active' : ''; ?>" href="<?php echo $soDirBase; ?>"<?php echo $letter === '' ? ' aria-current="page"' : ''; ?>>All</a>
				<a class="performer-filter-btn<?php echo $letter === '0-9' ? ' active' : ''; ?>" href="<?php echo htmlspecialchars($soDirUrl('0-9'), ENT_QUOTES, 'UTF-8'); ?>"<?php echo $letter === '0-9' ? ' aria-current="page"' : ''; ?>>0-9</a>
				<?php foreach (range('A', 'Z') as $l) { ?>
					<a class="performer-filter-btn<?php echo $letter === $l ? ' active' : ''; ?>" href="<?php echo htmlspecialchars($soDirUrl($l), ENT_QUOTES, 'UTF-8'); ?>"<?php echo $letter === $l ? ' aria-current="page"' : ''; ?>><?php echo $l; ?></a>
				<?php } ?>
			</div>
		</nav>

		<?php if ($letter !== '') { ?><h2 class="so-dir__heading"><?php echo $letter === '0-9' ? 'Starting with a number' : 'Starting with ' . htmlspecialchars($letter, ENT_QUOTES, 'UTF-8'); ?> <span><?php echo number_format($totalCount); ?> with tickets on sale</span></h2><?php } ?>

		<?php if ($apiError) { ?>
			<div class="performers-error-state">We couldn't load the list right now. Please try again shortly.</div>
		<?php } elseif (empty($performers)) { ?>
			<div class="performers-empty-state">Nothing with tickets on sale starts with that letter yet. <a href="<?php echo $soDirBase; ?>">Browse all</a>.</div>
		<?php } else { ?>
			<div class="row g-4" id="performerGrid">
				<?php foreach ($performers as $performer) { ?>
					<div class="col-12 col-sm-6 col-md-4 col-lg-3 performer-col">
						<div class="performer-card so-dircard">
							<div class="performer-img-wrap">
								<img src="<?php echo htmlspecialchars($performer['image'], ENT_QUOTES, 'UTF-8'); ?>" alt="" loading="lazy" onerror="this.src='<?php echo HOME_URL; ?>/images/placeholder.webp'">
							</div>
							<div class="performer-body">
								<div>
									<div class="performer-name"><a class="so-dircard__link" href="/artist/<?php echo htmlspecialchars(strtolower($performer['uriComponent']), ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($performer['name'], ENT_QUOTES, 'UTF-8'); ?><span class="visually-hidden"> tickets</span></a></div>
									<?php if ($performer['genre'] !== '') { ?>
										<div class="performer-genre"><?php echo htmlspecialchars($performer['genre'], ENT_QUOTES, 'UTF-8'); ?></div>
									<?php } ?>
								</div>
								<span class="btn-view-performer" aria-hidden="true">View tickets</span>
							</div>
						</div>
					</div>
				<?php } ?>
			</div>

			<?php if ($totalPages > 1) {
				$from = max(1, $page - 2); $to = min($totalPages, $page + 2); ?>
				<nav class="so-dir__pager" aria-label="Pages">
					<?php if ($page > 1) { ?><a rel="prev" href="<?php echo htmlspecialchars($soDirUrl($letter, $page - 1), ENT_QUOTES, 'UTF-8'); ?>">Previous</a><?php } ?>
					<?php if ($from > 1) { ?><a href="<?php echo htmlspecialchars($soDirUrl($letter, 1), ENT_QUOTES, 'UTF-8'); ?>">1</a><?php if ($from > 2) { ?><span>...</span><?php } } ?>
					<?php for ($i = $from; $i <= $to; $i++) { ?><a href="<?php echo htmlspecialchars($soDirUrl($letter, $i), ENT_QUOTES, 'UTF-8'); ?>"<?php echo $i === $page ? ' class="is-current" aria-current="page"' : ''; ?>><?php echo $i; ?></a><?php } ?>
					<?php if ($to < $totalPages) { if ($to < $totalPages - 1) { ?><span>...</span><?php } ?><a href="<?php echo htmlspecialchars($soDirUrl($letter, $totalPages), ENT_QUOTES, 'UTF-8'); ?>"><?php echo $totalPages; ?></a><?php } ?>
					<?php if ($page < $totalPages) { ?><a rel="next" href="<?php echo htmlspecialchars($soDirUrl($letter, $page + 1), ENT_QUOTES, 'UTF-8'); ?>">Next</a><?php } ?>
				</nav>
			<?php } ?>
		<?php } ?>

	</div>
</section>


<?php soSeoCopy('all-artists-and-teams'); ?>
<?php include 'footer.php'; ?>
