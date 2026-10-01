<?php

$pageTitle = "Performers | SeatOutlet";
$metaDescription = "Browse your favorite artists and performers on SeatOutlet and find upcoming events near you.";
include 'header.php';

$performers = [
	['name' => 'The Midnight Vows', 'genre' => 'Alternative Rock', 'image' => 'indie-rock-night.webp'],
	['name' => 'Luna Harbor',       'genre' => 'Indie Pop',        'image' => 'festival-1.webp'],
	['name' => 'The Broken Arrows', 'genre' => 'Alternative',      'image' => 'lollapalooza.webp'],
	['name' => 'Neon Pines',        'genre' => 'Electronic',       'image' => 'stage.webp'],
	['name' => 'Velvet Compass',    'genre' => 'Pop Rock',         'image' => 'bonnaroo.webp'],
	['name' => 'The Northern Lights','genre' => 'Rock',            'image' => 'new-event.webp'],
	['name' => 'Riverside Avenue',  'genre' => 'Folk / Americana', 'image' => 'venue.webp'],
	['name' => 'Echo & The Nine',   'genre' => 'Alternative',      'image' => 'crowd-at-concert-or-event.webp'],
];

$morePerformers = [
	['name' => 'Wildflower Radio',  'genre' => 'Indie Folk',       'image' => 'loews-theatre.webp'],
	['name' => 'Static Parade',     'genre' => 'Punk Rock',        'image' => 'pru-hall.webp'],
	['name' => 'Coastal Static',    'genre' => 'Dream Pop',        'image' => 'white-eagle-hall.webp'],
	['name' => 'The Amber Room',    'genre' => 'Soul / R&B',       'image' => 'event-concert.jpg'],
	['name' => 'Iron Horizon',      'genre' => 'Hard Rock',        'image' => 'home-slider-one.webp'],
	['name' => 'Paper Moon Society','genre' => 'Jazz Fusion',      'image' => 'austin.webp'],
	['name' => 'Gravity Well',      'genre' => 'Electronic',       'image' => 'home-slider.webp'],
	['name' => 'Sable & Sons',      'genre' => 'Country',          'image' => 'city.webp'],
];

function firstLetter($name) {
	$clean = preg_replace('/[^A-Za-z]/', '', $name);
	return $clean !== '' ? strtoupper($clean[0]) : '#';
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
	.performer-filter-wrap {
		display: flex;
		flex-wrap: nowrap;
		overflow-x: auto;
		gap: 10px;
		padding: 22px 0;
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
		padding: 16px 18px;
		display: flex;
		align-items: center;
		justify-content: space-between;
		gap: 12px;
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
		flex-shrink: 0;
		background: #2556E0;
		color: #ffffff;
		font-weight: 600;
		font-size: 13.5px;
		padding: 9px 18px;
		border-radius: 999px;
		border: none;
		text-decoration: none;
		white-space: nowrap;
		transition: background .2s, transform .15s;
	}
	.btn-view-performer:hover { background: #1a4fd6; color: #fff; transform: translateY(-1px); }

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

<section class="py-4">
	<div class="container">

		<div class="performer-filter-wrap" id="performerFilterBar">
			<button type="button" class="performer-filter-btn is-all active" data-letter="ALL">All</button>
			<?php foreach (range('A', 'Z') as $letter) { ?>
				<button type="button" class="performer-filter-btn" data-letter="<?php echo $letter; ?>"><?php echo $letter; ?></button>
			<?php } ?>
		</div>

		<div class="row g-4" id="performerGrid">
			<?php foreach ($performers as $performer) { ?>
				<div class="col-12 col-sm-6 col-lg-3 performer-col" data-letter="<?php echo firstLetter($performer['name']); ?>">
					<div class="performer-card">
						<div class="performer-img-wrap">
							<img src="<?php echo HOME_URL; ?>/images/<?php echo $performer['image']; ?>" alt="<?php echo htmlspecialchars($performer['name'], ENT_QUOTES, 'UTF-8'); ?>" loading="lazy">
						</div>
						<div class="performer-body">
							<div>
								<div class="performer-name"><?php echo htmlspecialchars($performer['name'], ENT_QUOTES, 'UTF-8'); ?></div>
								<div class="performer-genre"><?php echo htmlspecialchars($performer['genre'], ENT_QUOTES, 'UTF-8'); ?></div>
							</div>
							<a href="#" class="btn-view-performer">View Performer</a>
						</div>
					</div>
				</div>
			<?php } ?>

			<?php foreach ($morePerformers as $performer) { ?>
				<div class="col-12 col-sm-6 col-lg-3 performer-col performer-extra d-none" data-letter="<?php echo firstLetter($performer['name']); ?>">
					<div class="performer-card">
						<div class="performer-img-wrap">
							<img src="<?php echo HOME_URL; ?>/images/<?php echo $performer['image']; ?>" alt="<?php echo htmlspecialchars($performer['name'], ENT_QUOTES, 'UTF-8'); ?>" loading="lazy">
						</div>
						<div class="performer-body">
							<div>
								<div class="performer-name"><?php echo htmlspecialchars($performer['name'], ENT_QUOTES, 'UTF-8'); ?></div>
								<div class="performer-genre"><?php echo htmlspecialchars($performer['genre'], ENT_QUOTES, 'UTF-8'); ?></div>
							</div>
							<a href="#" class="btn-view-performer">View Performer</a>
						</div>
					</div>
				</div>
			<?php } ?>
		</div>

		<div class="text-center mt-4" id="loadMorePerformersWrap">
			<button type="button" class="btn-load-more-performers" id="loadMorePerformersBtn">Load More Performers</button>
		</div>

	</div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function () {
	var filterBar   = document.getElementById('performerFilterBar');
	var loadMoreBtn = document.getElementById('loadMorePerformersBtn');
	var loadMoreWrap = document.getElementById('loadMorePerformersWrap');
	var allCols = Array.prototype.slice.call(document.querySelectorAll('.performer-col'));

	function showAllDefault() {
		allCols.forEach(function (col) {
			col.classList.toggle('d-none', col.classList.contains('performer-extra'));
		});
		loadMoreWrap.classList.remove('d-none');
		loadMoreBtn.setAttribute('data-expanded', 'false');
		loadMoreBtn.textContent = 'Load More Performers';
	}

	filterBar.addEventListener('click', function (e) {
		var btn = e.target.closest('.performer-filter-btn');
		if (!btn) return;

		filterBar.querySelectorAll('.performer-filter-btn').forEach(function (b) { b.classList.remove('active'); });
		btn.classList.add('active');

		var letter = btn.getAttribute('data-letter');

		if (letter === 'ALL') {
			showAllDefault();
			return;
		}

		loadMoreWrap.classList.add('d-none');
		allCols.forEach(function (col) {
			col.classList.toggle('d-none', col.getAttribute('data-letter') !== letter);
		});
	});

	loadMoreBtn.addEventListener('click', function () {
		var expanded = loadMoreBtn.getAttribute('data-expanded') === 'true';
		document.querySelectorAll('.performer-extra').forEach(function (col) {
			col.classList.toggle('d-none', expanded);
		});
		loadMoreBtn.setAttribute('data-expanded', expanded ? 'false' : 'true');
		loadMoreBtn.textContent = expanded ? 'Load More Performers' : 'Show Less Performers';
	});
});
</script>

<?php include 'footer.php'; ?>
