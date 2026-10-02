<?php
// Include-only file: answer 404 if it is requested directly over the web (it would render a fragment or an error).
if (PHP_SAPI !== 'cli' && isset($_SERVER['SCRIPT_FILENAME']) && realpath($_SERVER['SCRIPT_FILENAME']) === __FILE__) { http_response_code(404); exit; }
/*
|--------------------------------------------------------------------------
| Listing pages: one row renderer, festival grouping, empty states, price filter
|--------------------------------------------------------------------------
| Every listing page (category / genre, the five hubs, search) used to carry its own copy of the event-row markup, which
| printed API strings unescaped and drifted from the rows the "More Events" button adds in the browser. The row is now built
| here once, escaped, and ajax/load-more-events.php returns the same HTML for the next pages, so server rows and appended
| rows are identical (same data attributes, same button text).
*/

/** "Under $X" choices for the listing price filter. TicketNetwork filters on pricingInfo/lowPrice/value (verified in the sandbox). */
const LISTING_PRICE = [25 => 'Under $25', 50 => 'Under $50', 100 => 'Under $100', 200 => 'Under $200'];

/** The visitor's price cap from ?max=, limited to the offered choices (anything else means no cap). */
function soListingMaxPrice(): int {
    $v = isset($_GET['max']) ? (int) $_GET['max'] : 0;
    return isset(LISTING_PRICE[$v]) ? $v : 0;
}

function soListingH($v): string {
    return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
}

/** Acronyms and brand spellings that must keep their capitals when a label is lowered into a sentence. */
function soListingKeepCase(string $label): bool {
    return (bool) preg_match('/\b(NBA|NFL|MLB|NHL|MLS|WNBA|NCAA|EDM|UFC|WWE|R&B|DJ|AC\/DC)\b/', $label);
}

/** A label for use inside a sentence: lower case, except acronyms like NBA, MLB and R&B which keep their capitals. */
function soListingInline(string $label): string {
    if (soListingKeepCase($label)) {
        // Lower-case only the words that are not acronyms ("Hip Hop" -> "hip hop", "NBA" stays, "R&B and Soul" -> "R&B and soul").
        return implode(' ', array_map(function ($w) { return preg_match('/^(NBA|NFL|MLB|NHL|MLS|WNBA|NCAA|EDM|UFC|WWE|R&B|DJ|AC\/DC)$/', $w) ? $w : strtolower($w); }, explode(' ', $label)));
    }
    return strtolower($label);
}

/* ---------- Search ranking ---------- */

function soListingNorm(string $s): string {
    $s = strtolower($s);
    $s = str_replace('&', ' and ', $s);
    return trim(preg_replace('/[^a-z0-9]+/', ' ', $s));
}

/** Tribute, cover and "music of" acts: real, but not what someone who typed the artist name wants first. */
function soEventLooksLikeTribute(array $event): bool {
    $name = (string) ($event['text']['name'] ?? '');
    return (bool) preg_match('/\b(tribute|tributes|cover band|salute|the music of|music of|a night of|experience|revisited|celebrating|candlelight|impersonator|story of|songs of)\b/i', $name);
}

/**
 * Search order for the relevance sort: events a visitor can buy first, then the exact performer or event name they typed,
 * then everything else, with tribute and similar acts last. A stable sort, so the API's own order breaks every tie.
 */
function soRankSearchEvents(array $events, string $query): array {
    $q = soListingNorm($query);
    $keyed = [];
    foreach (array_values($events) as $i => $ev) {
        $stock = (int) ($ev['_metadata']['ticketCount'] ?? 0) > 0 ? 0 : 1;
        $exact = 1;
        if ($q !== '') {
            $names = [(string) ($ev['text']['name'] ?? '')];
            foreach ($ev['performers'] ?? [] as $p) $names[] = (string) ($p['name'] ?? '');
            foreach ($names as $n) {
                $nn = soListingNorm($n);
                if ($nn === $q) { $exact = 0; break; }
                if ($nn !== '' && strpos($nn, $q . ' ') === 0) $exact = min($exact, 1);
            }
        }
        $tribute = $exact === 0 || $q === '' ? 0 : (soEventLooksLikeTribute($ev) && !preg_match('/tribute|cover|music of/', $q) ? 1 : 0);
        $keyed[] = [[$stock, $exact, $tribute, $i], $ev];
    }
    usort($keyed, function ($a, $b) { return $a[0] <=> $b[0]; });
    return array_map(function ($k) { return $k[1]; }, $keyed);
}

/** Capitalization for the search heading: what the visitor typed, unless it was all lower case (then the performer's own spelling, else Title Case). */
function soSearchDisplayName(string $typed, array $events): string {
    $typed = trim(preg_replace('/\s+/', ' ', $typed));
    if ($typed === '') return '';
    $n = soListingNorm($typed);
    foreach ($events as $ev) {
        foreach ($ev['performers'] ?? [] as $p) {
            $pn = (string) ($p['name'] ?? '');
            if ($pn !== '' && soListingNorm($pn) === $n) return $pn;
        }
    }
    return $typed === strtolower($typed) ? ucwords($typed) : $typed;
}

/* ---------- One event row ---------- */

/** Slug of a performer for /artist/<slug>, '' if it has no id. */
function soListingPerformerSlug(array $p): string {
    $name = trim((string) ($p['name'] ?? ''));
    $id = $p['id'] ?? '';
    $slug = trim(preg_replace('/[^a-z0-9]+/i', '-', strtolower($name)), '-');
    return $slug !== '' && $id ? $slug . '-' . $id : '';
}

/** "Austin City Limits Music Festival: Weekend One: ... - Friday" -> the festival's own name. */
function soFestivalTitle(string $name): string {
    $t = $name;
    if (($p = strpos($t, ':')) !== false && $p > 3) $t = substr($t, 0, $p);
    $t = preg_replace('/\s*\([^)]*\)\s*$/', '', $t);
    $t = preg_replace('/\s+-\s+(mon|tues|wednes|thurs|fri|satur|sun)day\b.*$/i', '', $t);
    return trim($t);
}

function soFestivalKey(string $name): string {
    return soListingNorm(soFestivalTitle($name));
}

/**
 * Rows for a list of events. Festival days at one venue (same festival, dates close together) become one card with a day
 * list; everything else is a normal row. $o['group'] = false turns the grouping off.
 */
function soRenderListingRows(array $events, array $o = []): string {
    $o += ['group' => true];
    $groups = [];   // key => list of events
    if ($o['group']) {
        foreach ($events as $ev) {
            if (strpos((string) ($ev['defaultCategory']['path'] ?? ''), '.1877.') === false) continue;
            $key = ($ev['venue']['id'] ?? '0') . '|' . soFestivalKey((string) ($ev['text']['name'] ?? ''));
            if (soFestivalKey((string) ($ev['text']['name'] ?? '')) === '') continue;
            $groups[$key][] = $ev;
        }
        // Dates of one festival can sit up to a week apart (two weekends); farther apart than that is a different run.
        foreach ($groups as $key => $list) {
            usort($list, function ($a, $b) { return strcmp((string) ($a['date']['datetime'] ?? $a['date']['date'] ?? ''), (string) ($b['date']['datetime'] ?? $b['date']['date'] ?? '')); });
            $run = []; $runs = [];
            foreach ($list as $ev) {
                $d = substr((string) ($ev['date']['date'] ?? ''), 0, 10);
                $last = $run ? substr((string) ($run[count($run) - 1]['date']['date'] ?? ''), 0, 10) : null;
                if ($run && ($last === '' || $d === '' || (new DateTimeImmutable($last))->diff(new DateTimeImmutable($d))->days > 8)) { $runs[] = $run; $run = []; }
                $run[] = $ev;
            }
            if ($run) $runs[] = $run;
            $groups[$key] = array_values(array_filter($runs, function ($r) { return count($r) >= 2; }));
        }
    }
    // event id => [group index key]; the first event of a group (in page order) carries the card.
    $byEvent = []; $cards = [];
    foreach ($groups as $key => $runs) {
        foreach ($runs as $ri => $run) {
            $cid = $key . '#' . $ri;
            $cards[$cid] = $run;
            foreach ($run as $ev) $byEvent[(int) ($ev['id'] ?? 0)] = $cid;
        }
    }
    $out = ''; $done = [];
    foreach ($events as $ev) {
        $id = (int) ($ev['id'] ?? 0);
        if ($id && isset($byEvent[$id])) {
            $cid = $byEvent[$id];
            if (isset($done[$cid])) continue;
            $done[$cid] = true;
            $out .= soRenderFestivalCard($cards[$cid]);
            continue;
        }
        $out .= soRenderListingRow($ev);
    }
    return $out;
}

/** Number of events an HTML chunk from soRenderListingRows stands for is carried in data-so-count (1 when absent). */
function soRenderListingRow(array $event): string {
    $h = 'soListingH';
    $id = (int) ($event['id'] ?? 0);
    $name = (string) ($event['text']['name'] ?? '');
    if ($id <= 0 || $name === '') return '';
    $raw = (string) ($event['date']['date'] ?? '');
    $ts = $raw !== '' ? strtotime($raw) : false;
    $year = (int) date('Y');
    $venueName = (string) ($event['venue']['text']['name'] ?? '');
    $venueId = $event['venue']['id'] ?? 0;
    $cityName = (string) ($event['city']['text']['name'] ?? '');
    $place = trim($cityName . ', ' . (string) ($event['stateProvince']['text']['abbr'] ?? ''), ', ');
    $slug = createSlug($name, $id);
    $venueSlug = $venueId ? createSlug($venueName, $venueId) : '';
    $citySlug = !empty($event['city']['id']) ? createSlug($place, $event['city']['id']) : '';
    $names = []; $slugs = [];
    foreach ($event['performers'] ?? [] as $p) {
        $pn = trim((string) ($p['name'] ?? ''));
        if ($pn === '') continue;
        $names[] = $pn;
        $slugs[] = soListingPerformerSlug($p);
    }
    $hasTickets = eventFromPrice($event) !== '';
    $btnText = $hasTickets ? 'Buy Tickets' : 'View Event';
    $time = (string) ($event['date']['text']['time'] ?? '');

    ob_start(); ?>
<div class="d-flex align-items-center justify-content-between performer-event-item">
	<div class="date-box text-center me-3">
		<div class="month"><?php echo $ts ? strtoupper(date('M', $ts)) : ''; ?></div>
		<div class="day"><?php echo $ts ? date('d', $ts) : ''; ?></div>
		<?php if ($ts && (int) date('Y', $ts) > $year) { ?><div class="month"><?php echo date('Y', $ts); ?></div><?php } ?>
	</div>
	<div class="flex-grow-1 w-50">
		<div class="d-flex align-items-center gap-2">
			<span class="fw-semibold day-weeks"><?php echo $ts ? date('D', $ts) : ''; ?></span>
			<span class="dot">&middot;</span>
			<span class="time-clock"><?php echo $h($time); ?></span>
			<button type="button" class="bi bi-info-circle text-muted icon-i" aria-label="Details for <?php echo $h($name); ?>"
				data-bs-toggle="offcanvas" data-bs-target="#offcanvasRight" aria-controls="offcanvasRight"
				data-id="<?php echo $id; ?>"
				data-date="<?php echo $ts ? $h(date('D, M d', $ts)) : ''; ?>"
				data-venue="<?php echo $h($venueName); ?>"
				data-venue-slug="<?php echo $h($venueSlug); ?>"
				data-location="<?php echo $h($place); ?>"
				data-title="<?php echo $h($name); ?>"
				data-performers="<?php echo $h(implode('|', $names)); ?>"
				data-performer-slugs="<?php echo $h(implode('|', $slugs)); ?>"></button>
		</div>
		<?php if ($venueName !== '') { ?><div class="ev-venue"><?php echo $venueSlug !== '' ? '<a href="/venue/' . $h($venueSlug) . '">' . $h($venueName) . '</a>' : $h($venueName); ?></div><?php } ?>
		<?php if ($place !== '') { ?><div class="ev-place"><?php echo $citySlug !== '' ? '<a href="/city/' . $h($citySlug) . '">' . $h($place) . '</a>' : $h($place); ?></div><?php } ?>
		<div class="ev-name"><a href="/event/<?php echo $h($slug); ?>"><?php echo $h($name); ?><span class="visually-hidden"> tickets<?php echo $ts ? ', ' . $h(date('M j', $ts)) : ''; ?><?php echo $venueName !== '' ? ' at ' . $h($venueName) : ''; ?><?php echo $place !== '' ? ', ' . $h($place) : ''; ?></span></a></div>
	</div>
	<div class="ms-3">
		<?php renderEventPriceTag($event); ?>
		<a href="/event/<?php echo $h($slug); ?>" class="btn <?php echo $hasTickets ? 'btn-primary' : 'btn-outline-primary'; ?> d-flex align-items-center gap-2" aria-label="<?php echo $hasTickets ? 'Buy tickets for ' : 'View '; ?><?php echo $h($name); ?>">
			<span><?php echo $btnText; ?></span>
			<i class="bi bi-chevron-right"></i>
		</a>
	</div>
</div>
<?php
    return (string) ob_get_clean();
}

/** One card for a festival that runs on several days at one venue: name, venue, and a tappable chip per day. */
function soRenderFestivalCard(array $run): string {
    $h = 'soListingH';
    $first = $run[0];
    $title = soFestivalTitle((string) ($first['text']['name'] ?? ''));
    $ts0 = strtotime((string) ($first['date']['date'] ?? ''));
    $tsN = strtotime((string) ($run[count($run) - 1]['date']['date'] ?? ''));
    $year = (int) date('Y');
    $venueName = (string) ($first['venue']['text']['name'] ?? '');
    $venueSlug = !empty($first['venue']['id']) ? createSlug($venueName, $first['venue']['id']) : '';
    $place = trim((string) ($first['city']['text']['name'] ?? '') . ', ' . (string) ($first['stateProvince']['text']['abbr'] ?? ''), ', ');
    $citySlug = !empty($first['city']['id']) ? createSlug($place, $first['city']['id']) : '';
    $min = null; $minText = '';
    $weekends = 1; $prev = null;
    foreach ($run as $ev) {
        $v = $ev['pricingInfo']['lowPrice']['value'] ?? null;
        if (eventFromPrice($ev) !== '' && ($min === null || (float) $v < $min)) { $min = (float) $v; $minText = eventFromPrice($ev); }
        $d = substr((string) ($ev['date']['date'] ?? ''), 0, 10);
        if ($prev !== null && $d !== '' && (new DateTimeImmutable($prev))->diff(new DateTimeImmutable($d))->days > 3) $weekends++;
        if ($d !== '') $prev = $d;
    }
    $n = count($run);
    $span = $weekends > 1 ? $weekends . ' weekends' : $n . ' days';
    $range = $ts0 && $tsN ? (date('M j', $ts0) === date('M j', $tsN) ? date('M j', $ts0) : date('M j', $ts0) . ' to ' . date('M j', $tsN)) : '';
    ob_start(); ?>
<div class="d-flex align-items-center justify-content-between performer-event-item so-festgroup" data-so-count="<?php echo $n; ?>" data-so-date="1">
	<div class="date-box text-center me-3">
		<div class="month"><?php echo $ts0 ? strtoupper(date('M', $ts0)) : ''; ?></div>
		<div class="day"><?php echo $ts0 ? date('d', $ts0) : ''; ?></div>
		<?php if ($ts0 && (int) date('Y', $ts0) > $year) { ?><div class="month"><?php echo date('Y', $ts0); ?></div><?php } ?>
	</div>
	<div class="flex-grow-1 w-50">
		<div class="d-flex align-items-center gap-2">
			<span class="fw-semibold day-weeks"><?php echo $h($span); ?></span>
			<span class="dot">&middot;</span>
			<span class="time-clock"><?php echo $h($range); ?></span>
		</div>
		<?php if ($venueName !== '') { ?><div class="ev-venue"><?php echo $venueSlug !== '' ? '<a href="/venue/' . $h($venueSlug) . '">' . $h($venueName) . '</a>' : $h($venueName); ?></div><?php } ?>
		<?php if ($place !== '') { ?><div class="ev-place"><?php echo $citySlug !== '' ? '<a href="/city/' . $h($citySlug) . '">' . $h($place) . '</a>' : $h($place); ?></div><?php } ?>
		<div class="ev-name"><span class="so-festgroup__title"><?php echo $h($title); ?></span></div>
		<ul class="so-festdays" aria-label="Days of <?php echo $h($title); ?>">
			<?php foreach ($run as $ev) {
				$ets = strtotime((string) ($ev['date']['date'] ?? ''));
				$from = eventFromPrice($ev);
				?>
				<li><a href="/event/<?php echo $h(createSlug((string) ($ev['text']['name'] ?? ''), $ev['id'])); ?>"><span><?php echo $ets ? $h(date('D, M j', $ets)) : ''; ?></span><small><?php echo $from !== '' ? 'from ' . $h($from) : 'no tickets yet'; ?></small></a></li>
			<?php } ?>
		</ul>
	</div>
	<div class="ms-3">
		<?php if ($minText !== '') { ?><div class="event-price-tag">From <strong><?php echo $h($minText); ?></strong></div><?php } else { ?><div class="event-price-tag event-price-tag--none">No tickets listed yet</div><?php } ?>
	</div>
</div>
<?php
    return (string) ob_get_clean();
}

/** How many events a rendered-rows chunk stands for (a festival card counts its days). */
function soListingCountFor(array $events): int {
    return count($events);
}

/* ---------- Empty states ---------- */

/**
 * One message, the next date that has events, nearest alternatives, and the "tell me when events are added" form.
 *
 * $o: basePath, noun ("NBA games"), when, max (price cap), fragment (OData location/category fragment, for the next-date lookup),
 *     kind ('category' | 'city'), id, name, alts (list of [label, href]).
 */
function soRenderListingEmpty(array $o): string {
    $h = 'soListingH';
    $o += ['basePath' => '', 'noun' => 'events', 'when' => '', 'max' => 0, 'fragment' => '', 'kind' => 'category', 'id' => 0, 'name' => '', 'alts' => [], 'lead' => true];
    $filtered = $o['when'] !== '' || $o['max'] > 0;
    $next = null;
    if ($o['fragment'] !== '') {
        try {
            $r = tnRequest('/catalog/v2/events/', locationListingParams($o['fragment'], 1, 1, '', 'soonest', $o['max']), 'GET', 900);
            $e = $r['results'][0] ?? null;
            if ($e && ($ts = strtotime((string) ($e['date']['date'] ?? '')))) {
                $next = ['ts' => $ts, 'name' => (string) ($e['text']['name'] ?? ''), 'slug' => createSlug((string) ($e['text']['name'] ?? ''), $e['id']), 'place' => trim((string) ($e['city']['text']['name'] ?? '') . ', ' . (string) ($e['stateProvince']['text']['abbr'] ?? ''), ', ')];
            }
        } catch (Throwable $ex) { $next = null; }
    }
    $whenText = $o['when'] !== '' && isset(LISTING_WHEN[$o['when']]) ? strtolower(LISTING_WHEN[$o['when']]) : '';
    if ($o['when'] === 'today') $whenText = 'today';
    $msg = $filtered
        ? 'No ' . $o['noun'] . ' match your filters' . ($whenText !== '' ? ' for ' . $whenText : '') . ($o['max'] > 0 ? ' under $' . (int) $o['max'] : '') . '.'
        : 'No ' . $o['noun'] . ' are on sale right now.';
    ob_start(); ?>
<div class="so-empty" role="status">
	<h3 class="so-empty__title"><?php echo $h($msg); ?></h3>
	<?php if ($next) { ?>
		<p class="so-empty__next">The next one is <a href="/event/<?php echo $h($next['slug']); ?>"><?php echo $h($next['name']); ?></a> on <strong><?php echo $h(date('l, F j', $next['ts'])); ?></strong><?php echo $next['place'] !== '' ? ' in ' . $h($next['place']) : ''; ?>.</p>
	<?php } ?>
	<?php if ($filtered) { ?><p><a class="so-empty__clear" href="<?php echo $h($o['basePath']); ?>">Clear filters and show everything</a></p><?php } ?>
	<?php if ($o['alts']) { ?>
		<p class="so-empty__altlabel">Try instead</p>
		<div class="so-empty__alts">
			<?php foreach ($o['alts'] as $alt) { ?><a class="so-linkchip" href="<?php echo $h($alt[1]); ?>"><?php echo $h($alt[0]); ?></a><?php } ?>
		</div>
	<?php } ?>
	<?php if ($o['lead'] && function_exists('soLeadForm')) {
		echo soLeadForm([
			'source' => 'listing-empty',
			'title' => 'Tell me when events are added',
			'text' => $o['name'] !== '' ? 'We will email you when new ' . $o['name'] . ' events go on sale. No spam.' : 'We will email you when new events go on sale. No spam.',
			'button' => 'Notify me',
			'interest_type' => $o['kind'] === 'city' ? 'city' : 'category',
			'interest_id' => (int) $o['id'],
			'interest_name' => $o['name'],
			'names' => false,
			'class' => 'so-nl--empty',
		]);
	} ?>
</div>
<?php
    return (string) ob_get_clean();
}

/** Alternative genre / league pages of the same family as a category, for an empty or thin listing. */
function soListingAltCategories(int $categoryId, int $max = 5): array {
    $kind = null;
    foreach (SO_GENRE_PAGES as $slug => $g) { if ($g[0] === $categoryId) { $kind = $g[3]; break; } }
    $alts = [];
    foreach (SO_GENRE_PAGES as $slug => $g) {
        if ($g[0] === $categoryId || ($kind !== null && $g[3] !== $kind)) continue;
        $alts[] = [$g[1] . ' tickets', '/' . $slug];
        if (count($alts) >= $max) break;
    }
    return $alts;
}

/**
 * The results block under the list heading: rows plus the load-more footer, or the empty state. Shared by every listing
 * page so they all look and behave the same. $o: events, total, count, perPage, params, type ('' | 'search'), empty (array for soRenderListingEmpty), group.
 */
function soRenderListingBody(array $o): void {
    $o += ['events' => [], 'total' => 0, 'perPage' => 20, 'params' => [], 'type' => '', 'empty' => null, 'group' => true, 'ranked' => null];
    $events = $o['events'];
    $total = (int) $o['total'];
    $perPage = (int) $o['perPage'];
    $pages = $total > 0 ? (int) ceil($total / $perPage) : 0;
    $count = count($events);
    $percent = $total > 0 ? min(100, ($count / $total) * 100) : 0;
    if (!empty($events)) { ?>
		<div id="eventsSection" class="section-artist-content event-row-all"><?php echo soRenderListingRows($events, ['group' => $o['group']]); ?></div>
		<?php if ($pages > 1) { ?>
			<div class="load-more-wrapper text-center mt-5">
				<div class="load-progress mx-auto mb-3">
					<div class="small mb-2">Loaded <strong id="loadedCount"><?php echo $count; ?></strong> out of <strong id="totalCount"><?php echo $total; ?></strong> events</div>
					<div class="progress progress-thin"><div class="progress-bar" id="progressBar" style="width: <?php echo $percent; ?>%;"></div></div>
				</div>
				<p class="so-more-error" id="loadMoreError" role="alert" hidden>Could not load more events. <button type="button" class="so-more-retry" id="loadMoreRetry">Try again</button></p>
				<button class="btn more-events-btn d-inline-flex align-items-center gap-2" id="loadMoreBtn"
					data-total="<?php echo $total; ?>"<?php echo $o['type'] !== '' ? ' data-type="' . soListingH($o['type']) . '"' : ''; ?>
					data-page="2"
					data-params="<?php echo soListingH(json_encode($o['params'])); ?>"
					data-perpage="<?php echo $perPage; ?>">
					<span class="btn-text">More Events</span>
					<span class="btnSpinner spinner-border spinner-border-sm d-none" id="btnSpinner" role="status" aria-hidden="true"></span>
					<i class="bi bi-chevron-down"></i>
				</button>
				<button class="btn more-events-btn d-inline-flex align-items-center gap-2 d-none" id="backToTopJs">
					<span class="btn-text">Back to Top</span>
					<i class="bi bi-chevron-up"></i>
				</button>
			</div>
		<?php } ?>
	<?php } elseif (is_array($o['empty'])) {
		echo soRenderListingEmpty($o['empty']);
	}
}

/**
 * A whole listing page body: result heading, filters, the list (or the empty state), the email-capture module and the
 * guarantee sidebar. Every listing page used to carry its own ~150 lines of this markup.
 *
 * $o: h1 (heading text, already upper-cased by the caller if wanted), tag ('h1' | 'h2'), total, basePath, when, sort, max,
 *     defaultSort, explore (renderListingFilters options), body (soRenderListingBody options), lead (soLeadForm options or null),
 *     afterRow / afterSection (optional callables that echo extra blocks).
 */
function soRenderListingPage(array $o): void {
    $o += ['tag' => 'h1', 'defaultSort' => 'popular', 'explore' => [], 'body' => [], 'lead' => null, 'afterRow' => null, 'afterSection' => null, 'when' => '', 'sort' => 'popular', 'max' => 0];
    $total = (int) $o['total'];
    $tag = $o['tag'] === 'h2' ? 'h2' : 'h1';
    ?>
<section>
	<div class="container">
		<div class="tab-section section-performer-content" id="default">
			<div class="row mt-3 gap-5 gap-md-2 gap-lg-4 gap-xl-5 gap-xxl-5">
				<div class="col-sm-12 col-md-8 left-bar">
					<div class="mb-3 mb-md-4 mb-lg-4">
						<div class="d-flex justify-content-between align-items-center results-header">
							<div class="results-title">
								<span class="active-indicator"></span>
								<<?php echo $tag; ?>>
									<?php echo soListingH($o['h1']); ?> <span class="dot">&middot;</span>
									<span class="count" id="results_count"><?php echo $total; ?> <?php echo $total === 1 ? 'RESULT' : 'RESULTS'; ?></span>
								</<?php echo $tag; ?>>
							</div>
						</div>
					</div>
					<?php renderListingFilters($o['basePath'], $o['when'], $o['sort'], $total, $o['defaultSort'], $o['explore'] + ['max' => (int) $o['max']]); ?>
					<div class="list-category-bg pb-3">
						<?php soRenderListingBody($o['body'] + ['total' => $total]); ?>
					</div>
					<?php if ($o['lead'] && !empty($o['body']['events']) && function_exists('soLeadForm')) { ?>
						<div class="tab-section content-section-detail mb-0" id="alerts"><?php echo soLeadForm($o['lead']); ?></div>
					<?php } ?>
				</div>
				<div id="secondary" class="sidebar col-sm-12 col-md-4">
					<div class="sticky-top sidebar-inner">
						<div class="guarantee-card d-flex align-items-center justify-content-between" data-bs-toggle="modal" data-bs-target="#staticBackdrop">
							<div class="guarantee">
								<strong>Shop Tickets Worry Free</strong><br>
								<span>With Our 100% Guarantee</span>
							</div>
							<div class="guarantee-icon">
								<i class="bi bi-shield-check"></i>
							</div>
						</div>
					</div>
				</div>
			</div>
			<?php if (is_callable($o['afterRow'])) { ($o['afterRow'])(); } ?>
		</div>
		<?php if (is_callable($o['afterSection'])) { ($o['afterSection'])(); } ?>
	</div>
</section>
<?php
}

/** OData fragment for a category path hub ('' = everything in the US), as categoryListingParams() builds it. */
function soCategoryFragment(string $categoryPath = ''): string {
    return $categoryPath !== '' ? "startswith(defaultCategory/path, '" . tnEscapeFilterValue($categoryPath) . "')" : "country/alphaCode eq 'US'";
}

/** The other listing hubs, as "try instead" links for an empty hub. */
function soListingHubAlts(string $basePath): array {
    $hubs = ['/buy-tickets-online' => 'All events', '/concert-tickets-for-sale' => 'Concerts', '/game-day-tickets' => 'Sports', '/buy-broadway-tickets' => 'Theater', '/upcoming-music-festivals' => 'Festivals'];
    $alts = [];
    foreach ($hubs as $href => $label) { if ($href !== $basePath) $alts[] = [$label, $href]; }
    return $alts;
}

/**
 * A compact row of filter chips made of plain links (each state has a URL, no JavaScript needed), reusing the listing chip
 * and menu styles. $groups: list of ['label', 'icon' (svg), 'param', 'options' => [value => text], 'current' => value];
 * $hrefFor(param, value) returns the address for choosing a value.
 */
function soRenderFilterBar(array $groups, callable $hrefFor, string $ariaLabel = 'Filter and sort events'): void {
    $caret = '<svg class="so-chip__caret" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>';
    echo '<div class="so-filterbar so-filterbar--compact" role="group" aria-label="' . soListingH($ariaLabel) . '">';
    foreach ($groups as $g) {
        $cur = (string) $g['current'];
        $label = $g['options'][$cur] ?? reset($g['options']);
        $isOn = $cur !== '' && $cur !== (string) array_key_first($g['options']);
        echo '<details class="so-dd"><summary class="so-chip' . ($isOn ? ' so-chip--on' : '') . '" aria-label="' . soListingH($g['label']) . '">' . $g['icon'] . '<span>' . soListingH($label) . '</span>' . $caret . '</summary><div class="so-dd__menu">';
        foreach ($g['options'] as $v => $text) {
            $active = (string) $v === $cur;
            echo '<a class="so-pop__row' . ($active ? ' is-active' : '') . '" href="' . soListingH($hrefFor($g['param'], (string) $v)) . '"' . ($active ? ' aria-current="true"' : '') . '>' . soListingH($text) . '</a>';
        }
        echo '</div></details>';
    }
    echo '</div>';
}
