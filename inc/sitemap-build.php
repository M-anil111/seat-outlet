<?php
// Include-only file: answer 404 if it is requested directly over the web.
if (PHP_SAPI !== 'cli' && isset($_SERVER['SCRIPT_FILENAME']) && realpath($_SERVER['SCRIPT_FILENAME']) === __FILE__) { http_response_code(404); exit; }
/**
 * The XML sitemap: a sitemap INDEX that points at small, typed sitemap files (pages, events-1..N, performers-1..N, venues-1..N,
 * cities-1..N), the same shape as the big ticket marketplaces use. Built from the live TicketNetwork catalog and kept current on its own.
 *
 *   How it is built   A crawl walks every upcoming US event that has tickets (200 per API request, soonest first), collecting the event
 *                     URLs and, from the same responses, every performer, venue and city on them. Nothing is guessed: every URL is a page
 *                     with something on sale right now. Entity pages the site has seen come up empty are dropped (soZeroPages).
 *   Why it is chunked The crawl is a few hundred API calls, too much for one request. soSitemapStep() does a time-boxed slice, saves where it
 *                     got to (cache/sitemap_state.json) and carries on at the next opportunity. When the last page is read it writes the
 *                     files and the index in one go (each file is replaced atomically, so crawlers never see half a file).
 *   What runs it      1. soSitemapMaybeRun(): after any public page has been sent to the visitor, at most once a minute (like the picture worker);
 *                        no cron needed. 2. cron/build-sitemaps.php for a server cron or by hand. Both use the same lock.
 *   Freshness         A new cycle starts every 6 hours on the live site (24 on beta), so a new event, performer, venue or city is listed within
 *                     about 6 hours; <lastmod> is TicketNetwork's own update time for the event (and the newest of an entity's events).
 *   Where files go    <docroot>/sitemaps/NAME.xml (the folder must be writable by PHP; the web server serves the files as static files).
 *                     The one index is /sitemaps/sitemap.xml.
 */

const SO_SITEMAP_CHUNK     = 5000;   // URLs per file (the protocol allows 50,000; small files are easier on crawlers)
const SO_SITEMAP_PER_PAGE  = 200;
const SO_SITEMAP_MAX_PAGES = 250;    // 50,000 events; the catalog is far below this
const SO_SITEMAP_PAUSE_US  = 700000; // between API requests, so the crawl never competes with visitors for the API
const SO_SITEMAP_FORMAT    = 2;      // bump to rebuild every file once: 2 = full W3C datetimes in <lastmod> and the browser stylesheet in every file
const SO_SITEMAP_STYLE_PI  = '<?xml-stylesheet type="text/xsl" href="/sitemap-style.php"?>';

function soSitemapStaticPaths(): array {
    return [
    '/',
    '/about-seat-outlet',
    '/ticket-partner-program',
    '/how-to-buy-tickets-online',
    '/ticket-buyer-protection',
    '/worry-free-guarantee',
    '/customer-testimonials',
    '/seat-outlet-reviews',
    '/seat-outlet-bbb',
    '/why-are-concert-tickets-so-expensive',
    '/ticket-faq',
    '/ticket-customer-service',
    '/city-events',
    '/buy-tickets-online',
    '/all-artists-and-teams',
    '/blog',
    '/concert-tickets-for-sale',
    '/hip-hop-tickets',
    '/country-music-tickets',
    '/pop-rock-concert-tickets',
    '/rnb-soul-concert-tickets',
    '/latin-music-tickets',
    '/alternative-concert-tickets',
    '/metal-concert-tickets',
    '/jazz-and-blues-tickets',
    '/electronic-music-tickets',
    '/comedy-show-tickets',
    '/classical-music-tickets',
    '/nba-tickets',
    '/nfl-tickets',
    '/mlb-tickets',
    '/nhl-tickets',
    '/mls-tickets',
    '/soccer-tickets',
    '/tennis-tickets',
    '/racing-tickets',
    '/boxing-tickets',
    '/las-vegas-shows-tickets',
    '/game-day-tickets',
    '/concert-artists',
    '/sports-teams',
    '/nfl-teams',
    '/nba-teams',
    '/mlb-teams',
    '/nhl-teams',
    '/mls-teams',
    '/broadway-shows',
    '/comedians-on-tour',
    '/music-festivals-list',
    '/buy-broadway-tickets',
    '/upcoming-music-festivals',
    '/tickets-promo-code',
    '/ticket-deals',
    '/hunt-tickets',
    '/ticket-scanner',
    '/grab-tickets-now',
    '/our-network',
    '/dotbooker',
    '/wingcms',
    '/salespeep',
    '/signs-n-more',
    '/it-sprinkles',
    '/austin-sign-masters',
    '/viralpep',
    '/mindshare-consulting',
    '/terms-and-conditions',
    '/privacy-policy',
    '/cookie-policy',
    ];
}

function soSitemapCacheDir(): string { return dirname(__DIR__) . '/cache'; }

/** Where the files live: <web root>/sitemaps, served as plain static files (the folder must be writable by PHP). */
function soSitemapTarget(): array {
    $dir = dirname(__DIR__) . '/sitemaps';
    if (!is_dir($dir)) @mkdir($dir, 0755);
    return ['static' => true, 'dir' => $dir];
}

function soSitemapChildUrl(string $name, bool $static): string {
    return rtrim(HOME_URL, '/') . '/sitemaps/' . $name . '.xml';
}

/** The one address of the index: /sitemaps/sitemap.xml (robots.txt, Search Console). */
function soSitemapIndexUrl(): string {
    return rtrim(HOME_URL, '/') . '/sitemaps/sitemap.xml';
}

function soSitemapEsc(string $v): string { return htmlspecialchars($v, ENT_QUOTES | ENT_XML1, 'UTF-8'); }

function soSitemapWriteAtomic(string $file, string $body): bool {
    $tmp = $file . '.tmp' . getmypid();
    if (file_put_contents($tmp, $body) === false) return false;
    return @rename($tmp, $file);
}

/** @param array<int,array{0:string,1:?string}> $entries [loc, lastmod] */
function soSitemapUrlsetXml(array $entries): string {
    $x = '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . SO_SITEMAP_STYLE_PI . "\n" . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
    foreach ($entries as [$loc, $lm]) {
        $x .= '<url><loc>' . soSitemapEsc($loc) . '</loc>' . ($lm ? '<lastmod>' . soSitemapEsc($lm) . '</lastmod>' : '') . "</url>\n";
    }
    return $x . '</urlset>' . "\n";
}

function soSitemapState(): array {
    $f = soSitemapCacheDir() . '/sitemap_state.json';
    $d = is_file($f) ? json_decode((string) @file_get_contents($f), true) : null;
    return is_array($d) ? $d : [];
}
function soSitemapSaveState(array $s): void {
    soSitemapWriteAtomic(soSitemapCacheDir() . '/sitemap_state.json', json_encode($s));
}
function soSitemapTmpDir(): string {
    $d = soSitemapCacheDir() . '/sitemap_tmp';
    if (!is_dir($d)) @mkdir($d, 0755, true);
    return $d;
}
function soSitemapRebuildAfter(): int { return SITE_INDEXABLE ? 6 * 3600 : 24 * 3600; }

/** True when a step has something to do: no build yet, a crawl in progress, or the last build is old. */
function soSitemapNeedsWork(): bool {
    $s = soSitemapState();
    if (!$s) return true;
    if (($s['phase'] ?? '') === 'crawl') return true;
    if ((int) ($s['fmt'] ?? 0) !== SO_SITEMAP_FORMAT) return true;
    return time() - (int) ($s['builtAt'] ?? 0) >= soSitemapRebuildAfter();
}

/** W3C datetime with the time of day and a UTC offset ("2026-10-04T17:49:46+00:00"), the form Google's sitemap guidance recommends for <lastmod>. */
function soSitemapDate($ts): ?string {
    if (!is_string($ts) || trim($ts) === '') return null;
    try {
        $d = new DateTimeImmutable($ts, new DateTimeZone('UTC'));   // a stamp with no zone is read as UTC, never as the server's local time
    } catch (Exception $e) {
        return null;
    }
    return $d->setTimezone(new DateTimeZone('UTC'))->format('c');
}

/**
 * Add the browser stylesheet to files built before it existed, so every sitemap is readable right away instead of after the next
 * full crawl. Only the header line changes; the URLs and dates are untouched. Returns how many files were updated.
 */
function soSitemapRestyleExisting(): int {
    $n = 0;
    foreach ([dirname(__DIR__) . '/sitemaps', soSitemapCacheDir() . '/sitemaps'] as $dir) {
        foreach (glob($dir . '/*.xml') ?: [] as $f) {
            $head = (string) @file_get_contents($f, false, null, 0, 400);
            if ($head === '' || strpos($head, '<?xml-stylesheet') !== false) continue;
            $xml = (string) @file_get_contents($f);
            $new = preg_replace('/^(<\?xml[^>]*\?>\s*)/', '$1' . SO_SITEMAP_STYLE_PI . "\n", $xml, 1);
            if ($new !== null && $new !== $xml && soSitemapWriteAtomic($f, $new)) $n++;
        }
    }
    return $n;
}

/**
 * One time-boxed slice of the crawl (and, when the last page has been read, the build). Safe to call from several places: a lock makes
 * a second caller return at once. Returns a short status line.
 */
function soSitemapStep(float $budgetSeconds = 25.0, bool $force = false): string {
    $lock = @fopen(soSitemapCacheDir() . '/sitemap.lock', 'c');
    if (!$lock || !flock($lock, LOCK_EX | LOCK_NB)) return 'busy';
    try {
        $deadline = microtime(true) + $budgetSeconds;
        $tmp = soSitemapTmpDir();
        $s = soSitemapState();
        $stale = ($s['phase'] ?? '') !== 'crawl' && ((int) ($s['fmt'] ?? 0) !== SO_SITEMAP_FORMAT || time() - (int) ($s['builtAt'] ?? 0) >= soSitemapRebuildAfter());
        $rebuild = $force || !$s || $stale;
        if ($rebuild) {
            if ((int) ($s['fmt'] ?? 0) !== SO_SITEMAP_FORMAT) soSitemapRestyleExisting();   // readable now, rebuilt with full timestamps below
            foreach (['events', 'performers', 'venues', 'cities'] as $k) { @unlink($tmp . '/' . $k . '.tsv'); }
            $s = ['phase' => 'crawl', 'startedAt' => time(), 'page' => 1, 'pages' => null, 'fails' => 0, 'builtAt' => (int) ($s['builtAt'] ?? 0), 'counts' => $s['counts'] ?? [], 'fmt' => (int) ($s['fmt'] ?? 0)];
            soSitemapSaveState($s);
        }
        if (($s['phase'] ?? '') !== 'crawl') return 'fresh';

        $today = date('Y-m-d');
        $clean = function ($v) { return str_replace(["\t", "\r", "\n"], ' ', (string) $v); };
        while (microtime(true) < $deadline && $s['page'] <= ($s['pages'] ?? SO_SITEMAP_MAX_PAGES)) {
            if (function_exists('tnBreakerOpen') && tnBreakerOpen()) { $s['note'] = 'api paused (rate limited)'; break; }
            $params = ['filter' => "date/date ge $today and _metadata/hasTickets eq true and country/alphaCode eq 'US'",
                'sort' => 'date/date', 'perPage' => SO_SITEMAP_PER_PAGE, 'page' => $s['page']];
            if ($s['page'] === 1) $params['includeTotalCount'] = 'true';
            $r = tnRequest('/catalog/v2/events/', $params, 'GET', 0);   // ttl 0: a crawl page is read once, never cached
            if (!is_array($r) || !isset($r['results']) || !is_array($r['results'])) {
                if (++$s['fails'] >= 12) { $s['phase'] = 'crawl'; $s['page'] = 1; $s['fails'] = 0; $s['note'] = 'restarted after repeated API failures'; foreach (['events', 'performers', 'venues', 'cities'] as $k) { @unlink($tmp . '/' . $k . '.tsv'); } }
                break;
            }
            $s['fails'] = 0;
            if ($s['page'] === 1) $s['pages'] = min(SO_SITEMAP_MAX_PAGES, max(1, (int) ceil(((int) ($r['totalCount'] ?? 0)) / SO_SITEMAP_PER_PAGE)));
            $ev = $per = $ven = $cit = '';
            foreach ($r['results'] as $e) {
                $id = (int) ($e['id'] ?? 0);
                $name = (string) ($e['text']['name'] ?? '');
                if ($id <= 0 || $name === '') continue;
                $lm = soSitemapDate($e['metadataInclusiveUpdatedAt'] ?? ($e['updatedAt'] ?? '')) ?? '';
                $ev .= createSlug($name, $id) . "\t" . $lm . "\n";
                foreach ($e['performers'] ?? [] as $p) {
                    if (!empty($p['id']) && !empty($p['name'])) $per .= (int) $p['id'] . "\t" . $clean($p['name']) . "\t" . $lm . "\n";
                }
                if (!empty($e['venue']['id']) && !empty($e['venue']['text']['name'])) $ven .= (int) $e['venue']['id'] . "\t" . $clean($e['venue']['text']['name']) . "\t" . $lm . "\n";
                if (!empty($e['city']['id']) && !empty($e['city']['text']['name'])) {
                    $cit .= (int) $e['city']['id'] . "\t" . $clean(trim($e['city']['text']['name'] . ', ' . ($e['stateProvince']['text']['abbr'] ?? ''), ', ')) . "\t" . $lm . "\n";
                }
            }
            file_put_contents($tmp . '/events.tsv', $ev, FILE_APPEND);
            file_put_contents($tmp . '/performers.tsv', $per, FILE_APPEND);
            file_put_contents($tmp . '/venues.tsv', $ven, FILE_APPEND);
            file_put_contents($tmp . '/cities.tsv', $cit, FILE_APPEND);
            $s['page']++;
            $last = count($r['results']) < SO_SITEMAP_PER_PAGE;
            if ($last) $s['pages'] = $s['page'] - 1;
            soSitemapSaveState($s);
            if ($s['page'] <= ($s['pages'] ?? SO_SITEMAP_MAX_PAGES)) usleep(SO_SITEMAP_PAUSE_US);
        }
        if ($s['page'] > ($s['pages'] ?? SO_SITEMAP_MAX_PAGES) && ($s['pages'] ?? null) !== null) {
            $counts = soSitemapBuildFiles($tmp);
            $s = ['phase' => 'done', 'builtAt' => time(), 'startedAt' => $s['startedAt'] ?? time(), 'counts' => $counts, 'fails' => 0, 'fmt' => SO_SITEMAP_FORMAT];
            soSitemapSaveState($s);
            return 'built ' . json_encode($counts);
        }
        soSitemapSaveState($s);
        return 'crawling page ' . $s['page'] . ' of ' . ($s['pages'] ?? '?');
    } finally {
        flock($lock, LOCK_UN);
    }
}

/** Read the crawl output, write every sitemap file and then the index. @return array<string,int> URLs per group */
function soSitemapBuildFiles(string $tmp): array {
    $target = soSitemapTarget();
    $static = $target['static'];
    $dir = $target['dir'];
    $base = rtrim(HOME_URL, '/');
    $zero = array_flip(soZeroPages());
    $write = function (string $name, array $entries) use ($dir) {
        return soSitemapWriteAtomic($dir . '/' . $name . '.xml', soSitemapUrlsetXml($entries));
    };
    $files = [];   // name => lastmod
    $counts = [];
    $maxLm = function (array $entries) { $m = ''; foreach ($entries as $e) { if (($e[1] ?? '') > $m) $m = $e[1]; } return $m !== '' ? $m : gmdate('c'); };
    $emit = function (string $group, array $entries) use (&$files, &$counts, $write, $maxLm, $dir) {
        $counts[$group] = count($entries);
        $chunks = array_chunk($entries, SO_SITEMAP_CHUNK);
        foreach ($chunks as $i => $chunk) {
            $name = $group . '-' . ($i + 1);
            if ($write($name, $chunk)) $files[$name] = $maxLm($chunk);
        }
        foreach (glob($dir . '/' . $group . '-*.xml') ?: [] as $old) {   // chunks that no longer exist
            if (!isset($files[basename($old, '.xml')])) @unlink($old);
        }
    };

    // Pages: the site's own pages, published blog posts, the top cities with their category pages, and the homepage category pages.
    $pages = [];
    foreach (soSitemapStaticPaths() as $p) { $pages[$base . $p] = [$base . $p, null]; }
    foreach (listBlogPosts() as $post) {
        if (($post['status'] ?? '') !== 'published' || empty($post['published_at']) || strtotime($post['published_at']) > time()) continue;
        $loc = $base . '/blog/' . $post['slug'];
        $pages[$loc] = [$loc, gmdate('c', strtotime($post['updated_at'] ?? $post['published_at']))];
    }
    foreach (getTopCities(60) as $i => $city) {
        $slug = createSlug($city['label'], $city['id']);
        foreach (($i < 30 ? ['concerts-city', 'sports-city', 'theater-city'] : []) as $prefix) { $pages[$base . '/' . $prefix . '/' . $slug] = [$base . '/' . $prefix . '/' . $slug, null]; }
    }
    foreach ((cache_get('top_categories', 30 * 86400) ?: []) as $bucket) {
        foreach ((array) $bucket as $cat) { if (!empty($cat['slug'])) { $loc = $base . '/category/' . $cat['slug']; $pages[$loc] = [$loc, null]; } }
    }
    // Every sub-category the ticket API lists under concerts, sports and theater that has tickets on sale (the same list as the sub-category pills).
    if (function_exists('soHubSubcategories')) {
        foreach (array_keys(SO_HUB_ROOTS) as $hub) {
            foreach (soHubSubcategories($hub) as $sub) { $loc = $base . $sub['href']; $pages[$loc] = [$loc, null]; }
        }
    }
    $emit('pages', array_values($pages));

    $keep = function (string $loc) use ($zero, $base) { return !isset($zero[substr($loc, strlen($base))]); };

    // Events, soonest first, one entry per event.
    $entries = []; $seen = [];
    foreach (@file($tmp . '/events.tsv', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
        [$slug, $lm] = array_pad(explode("\t", $line), 2, '');
        if ($slug === '' || isset($seen[$slug])) continue;
        $seen[$slug] = true;
        $entries[] = [$base . '/event/' . $slug, $lm !== '' ? $lm : null];
    }
    $emit('events', $entries);

    // Performers, venues and cities: every one on those events, with the newest update among its events as <lastmod>.
    foreach (['performers' => '/artist/', 'venues' => '/venue/', 'cities' => '/city/'] as $group => $prefix) {
        $ents = [];
        foreach (@file($tmp . '/' . $group . '.tsv', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
            [$id, $name, $lm] = array_pad(explode("\t", $line), 3, '');
            $id = (int) $id;
            if ($id <= 0 || $name === '') continue;
            if (!isset($ents[$id])) $ents[$id] = [$name, $lm];
            elseif ($lm > $ents[$id][1]) $ents[$id][1] = $lm;
        }
        $list = [];
        foreach ($ents as $id => [$name, $lm]) {
            $loc = $base . $prefix . createSlug($name, $id);
            if ($keep($loc)) $list[] = [$loc, $lm !== '' ? $lm : null];
        }
        $emit($group, $list);
    }

    $x = '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . SO_SITEMAP_STYLE_PI . "\n" . '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
    foreach ($files as $name => $lm) {
        $x .= '<sitemap><loc>' . soSitemapEsc(soSitemapChildUrl($name, $static)) . '</loc><lastmod>' . soSitemapEsc($lm) . '</lastmod></sitemap>' . "\n";
    }
    $x .= '</sitemapindex>' . "\n";
    soSitemapWriteAtomic($dir . '/sitemap.xml', $x);
    // Leftovers from earlier layouts: the old index name and any copy in the cache folder.
    @unlink($dir . '/sitemap-index.xml');
    @unlink(soSitemapCacheDir() . '/sitemaps/sitemap-index.xml');
    @unlink(soSitemapCacheDir() . '/sitemap_xml.json');
    return $counts;
}

/** Shutdown hook for public page requests: a time-boxed slice after the visitor has the whole page, at most once a minute. */
function soSitemapMaybeRun(): void {
    if (PHP_SAPI === 'cli' || getenv('SITEMAP_WEB_WORKER') === '0' || !function_exists('fastcgi_finish_request')) return;
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') return;
    $dir = soSitemapCacheDir();
    if (!is_dir($dir) || !is_writable($dir)) return;
    $stamp = $dir . '/sitemap.stamp';
    if (is_file($stamp) && time() - (int) @filemtime($stamp) < 60) return;
    @touch($stamp);
    try {
        if (!soSitemapNeedsWork()) return;
        ignore_user_abort(true);
        @set_time_limit(60);
        fastcgi_finish_request();
        soSitemapStep(25.0);
    } catch (Throwable $e) {
        error_log('sitemap worker: ' . $e->getMessage());
    }
}
