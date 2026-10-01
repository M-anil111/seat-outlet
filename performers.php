<?php

$pageTitle = "Performers | SeatOutlet";
$metaDescription = "Browse your favorite artists and performers on SeatOutlet and find upcoming events near you.";
include 'header.php';

$perPage = 24;
$initialParams = [
	'page'              => 1,
	'perPage'           => $perPage,
	'includeTotalCount' => 'true',
	'sort'              => 'text/name',
];

$performers  = [];
$totalCount  = 0;
$hasMore     = false;
$apiError    = false;

try {
	$response   = getTnPerformers($initialParams);
	$totalCount = (int) ($response['totalCount'] ?? 0);
	$totalPages = $perPage > 0 ? (int) ceil($totalCount / $perPage) : 0;
	$results    = $response['results'] ?? [];
	$hasMore    = (1 < $totalPages);

	foreach ($results as $performer) {
		$name = $performer['text']['name'] ?? '';
		$uriComponent = $performer['uriComponent'] ?? '';
		if ($name === '' || $uriComponent === '') {
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
		background-color: #05070b;
		color: #ffffff;
		overflow: hidden;
	}
	.performers-hero-section .hero-bg-photo {
		position: absolute;
		inset: 0;
		background-image: url('<?php echo HOME_URL; ?>/images/crowd-at-concert-or-event.webp');
		background-size: cover;
		background-position: center right;
	}
	.performers-hero-section .hero-bg-overlay {
		position: absolute;
		inset: 0;
		background: linear-gradient(90deg, #05070b 0%, #05070bd9 30%, #0b1a4dcc 55%, #2556E066 100%);
	}
	.performers-hero-section .hero-accent {
		position: absolute;
		top: 0;
		right: -6%;
		width: 55%;
		height: 100%;
		background: linear-gradient(135deg, #2556E0 0%, #1a3fae 100%);
		clip-path: polygon(35% 0, 100% 0, 100% 100%, 10% 100%);
		opacity: .55;
		z-index: 0;
	}
	.performers-hero-section .container { position: relative; z-index: 1; }
	.performers-hero-section .hero-inner { padding: 70px 0; max-width: 620px; }
	.performers-hero-section .hero-title {
		font-weight: 800;
		font-size: 65px;
		line-height: 1.05;
		margin-bottom: 16px;
	}
	.performers-hero-section .hero-subtitle {
		color: #d1d5db;
		font-size: 18px;
		margin-bottom: 0;
	}
	@media (max-width: 991px) {
		.performers-hero-section .hero-title { font-size: 46px; }
	}
	@media (max-width: 767.98px) {
		.performers-hero-section .hero-inner { padding: 44px 0; }
		.performers-hero-section .hero-title { font-size: 34px; }
		.performers-hero-section .hero-subtitle { font-size: 15px; }
		.performers-hero-section .hero-bg-overlay { background: linear-gradient(180deg, #05070bf2 55%, #05070b 100%); }
	}

	/* Alphabet filter */
	.performer-filter-row {
		display: flex;
		align-items: center;
		gap: 8px;
		padding: 22px 0;
	}
	.performer-filter-wrap {
		flex: 1 1 auto;
		min-width: 0;
		display: flex;
		flex-wrap: nowrap;
		overflow-x: auto;
		gap: 10px;
		scroll-behavior: smooth;
		-ms-overflow-style: none;
		scrollbar-width: none;
	}
	.performer-filter-wrap::-webkit-scrollbar { display: none; }
	.performer-filter-btn {
		flex: 0 0 auto;
		width: 42px;
		height: 42px;
		display: flex;
		align-items: center;
		justify-content: center;
		border-radius: 999px;
		background: #ffffff;
		border: 1px solid #e5e9f2;
		box-shadow: 0 2px 6px rgba(17, 24, 39, .06);
		color: #1f2937;
		font-weight: 600;
		font-size: 14px;
		cursor: pointer;
		transition: background .2s, color .2s, border-color .2s, transform .15s;
	}
	.performer-filter-btn.is-all { width: auto; padding: 0 22px; }
	.performer-filter-btn:hover { transform: translateY(-2px); border-color: #2556E0; }
	.performer-filter-btn.active {
		background: #2556E0;
		border-color: #2556E0;
		color: #ffffff;
	}
	.performer-filter-nav {
		flex: 0 0 auto;
		width: 36px;
		height: 36px;
		border-radius: 999px;
		background: #ffffff;
		border: 1px solid #e5e9f2;
		box-shadow: 0 2px 6px rgba(17, 24, 39, .06);
		color: #2556E0;
		padding: 0;
		line-height: 1;
		display: none;
		align-items: center;
		justify-content: center;
		cursor: pointer;
	}
	.performer-filter-row.is-overflowing .performer-filter-nav { display: flex; }
	.performer-filter-nav:disabled { opacity: .4; cursor: default; }

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
	<div class="hero-accent"></div>
	<div class="hero-bg-overlay"></div>
	<div class="container">
		<div class="hero-inner">
			<h1 class="hero-title">Performers</h1>
			<p class="hero-subtitle">Discover your favorite artists, performers, and upcoming events.</p>
		</div>
	</div>
</section>

<section class="py-4 py-lg-5">
	<div class="container">

		<div class="performer-filter-row" id="performerFilterRow">
			<button type="button" class="performer-filter-nav" id="filterPrev" aria-label="Previous letters"><i class="bi bi-chevron-left"></i></button>
			<div class="performer-filter-wrap" id="performerFilterBar">
				<button type="button" class="performer-filter-btn is-all active" data-letter="ALL">All</button>
				<?php foreach (range('A', 'Z') as $letter) { ?>
					<button type="button" class="performer-filter-btn" data-letter="<?php echo $letter; ?>"><?php echo $letter; ?></button>
				<?php } ?>
			</div>
			<button type="button" class="performer-filter-nav" id="filterNext" aria-label="Next letters"><i class="bi bi-chevron-right"></i></button>
		</div>

		<div class="performers-error-state<?php echo $apiError ? '' : ' d-none'; ?>" id="performersErrorState">
			We couldn't load performers right now. Please try again shortly.
		</div>
		<div class="performers-empty-state<?php echo ($apiError || !empty($performers)) ? ' d-none' : ''; ?>" id="performersEmptyState">
			No performers found.
		</div>
			<div class="row g-4" id="performerGrid">
				<?php foreach ($performers as $performer) { ?>
					<div class="col-12 col-sm-6 col-md-4 col-lg-3 performer-col">
						<div class="performer-card">
							<div class="performer-img-wrap">
								<img src="<?php echo htmlspecialchars($performer['image'], ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($performer['name'], ENT_QUOTES, 'UTF-8'); ?>" loading="lazy" onerror="this.src='<?php echo HOME_URL; ?>/images/placeholder.webp'">
							</div>
							<div class="performer-body">
								<div>
									<div class="performer-name"><?php echo htmlspecialchars($performer['name'], ENT_QUOTES, 'UTF-8'); ?></div>
									<?php if ($performer['genre'] !== '') { ?>
										<div class="performer-genre"><?php echo htmlspecialchars($performer['genre'], ENT_QUOTES, 'UTF-8'); ?></div>
									<?php } ?>
								</div>
								<a href="/artist/<?php echo strtolower($performer['uriComponent']); ?>" class="btn-view-performer">View Performer</a>
							</div>
						</div>
					</div>
				<?php } ?>
			</div>

		<div class="text-center mt-4 <?php echo (!$hasMore || $apiError) ? 'd-none' : ''; ?>" id="loadMorePerformersWrap">
			<button type="button" class="btn-load-more-performers" id="loadMorePerformersBtn"
				data-page="1" data-perpage="<?php echo (int) $perPage; ?>" data-letter="ALL">Load More Performers</button>
		</div>

	</div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function () {
	var filterBar    = document.getElementById('performerFilterBar');
	var filterPrev    = document.getElementById('filterPrev');
	var filterNext    = document.getElementById('filterNext');
	var grid          = document.getElementById('performerGrid');
	var emptyState    = document.getElementById('performersEmptyState');
	var errorState    = document.getElementById('performersErrorState');
	var loadMoreBtn   = document.getElementById('loadMorePerformersBtn');
	var loadMoreWrap  = document.getElementById('loadMorePerformersWrap');

	function performerCardHtml(p) {
		var genreHtml = p.genre ? '<div class="performer-genre">' + escapeHtml(p.genre) + '</div>' : '';
		return '' +
			'<div class="col-12 col-sm-6 col-md-4 col-lg-3 performer-col">' +
				'<div class="performer-card">' +
					'<div class="performer-img-wrap">' +
						'<img src="' + escapeHtml(p.image) + '" alt="' + escapeHtml(p.name) + '" loading="lazy" onerror="this.src=\'<?php echo HOME_URL; ?>/images/placeholder.webp\'">' +
					'</div>' +
					'<div class="performer-body">' +
						'<div>' +
							'<div class="performer-name">' + escapeHtml(p.name) + '</div>' +
							genreHtml +
						'</div>' +
						'<a href="/artist/' + String(p.uriComponent).toLowerCase() + '" class="btn-view-performer">View Performer</a>' +
					'</div>' +
				'</div>' +
			'</div>';
	}

	function escapeHtml(str) {
		return String(str == null ? '' : str)
			.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
			.replace(/"/g, '&quot;').replace(/'/g, '&#39;');
	}

	var requestSeq = 0;

	function fetchPerformers(letter, page, append) {
		var seq = ++requestSeq;
		var perPage = loadMoreBtn.dataset.perpage || 24;
		var url = '/ajax/get-performers.php?letter=' + encodeURIComponent(letter) + '&page=' + page + '&perPage=' + perPage;

		return fetch(url).then(function (res) {
			if (!res.ok) throw new Error('HTTP ' + res.status);
			return res.json();
		}).then(function (data) {
			if (seq !== requestSeq) return data; // a newer letter was clicked meanwhile
			errorState.classList.add('d-none');
			if (!append) {
				grid.innerHTML = '';
			}
			(data.performers || []).forEach(function (p) {
				grid.insertAdjacentHTML('beforeend', performerCardHtml(p));
			});

			emptyState.classList.toggle('d-none', grid.children.length > 0);

			loadMoreBtn.dataset.letter = letter;
			if (data.hasMore) {
				loadMoreBtn.dataset.page = data.nextPage;
				loadMoreWrap.classList.remove('d-none');
			} else {
				loadMoreWrap.classList.add('d-none');
			}
			loadMoreBtn.disabled = false;
			return data;
		}).catch(function () {
			if (seq !== requestSeq) return;
			if (!append) {
				grid.innerHTML = '';
				errorState.classList.remove('d-none');
				emptyState.classList.add('d-none');
				loadMoreWrap.classList.add('d-none');
			}
			loadMoreBtn.disabled = false;
		});
	}

	filterBar.addEventListener('click', function (e) {
		var btn = e.target.closest('.performer-filter-btn');
		if (!btn) return;

		filterBar.querySelectorAll('.performer-filter-btn').forEach(function (b) { b.classList.remove('active'); });
		btn.classList.add('active');

		var letter = btn.getAttribute('data-letter');
		loadMoreBtn.disabled = true;
		fetchPerformers(letter, 1, false);
	});

	loadMoreBtn.addEventListener('click', function () {
		loadMoreBtn.disabled = true;
		var letter = loadMoreBtn.dataset.letter || 'ALL';
		var page = parseInt(loadMoreBtn.dataset.page, 10) || 2;
		fetchPerformers(letter, page, true);
	});

	var filterRow = document.getElementById('performerFilterRow');

	function updateFilterNav() {
		var maxScroll = filterBar.scrollWidth - filterBar.clientWidth;
		filterRow.classList.toggle('is-overflowing', maxScroll > 1);
		filterPrev.disabled = filterBar.scrollLeft <= 1;
		filterNext.disabled = filterBar.scrollLeft >= maxScroll - 1;
	}

	function scrollFilter(direction) {
		filterBar.scrollBy({ left: direction * Math.max(120, filterBar.clientWidth * 0.8), behavior: 'smooth' });
	}

	filterPrev.addEventListener('click', function () { scrollFilter(-1); });
	filterNext.addEventListener('click', function () { scrollFilter(1); });
	filterBar.addEventListener('scroll', updateFilterNav, { passive: true });
	window.addEventListener('resize', updateFilterNav);
	updateFilterNav();
});
</script>

<?php include 'footer.php'; ?>
