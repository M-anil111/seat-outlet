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
const SO_SITEMAP_CITYPAGE_MIN = 3;   // upcoming events a city needs, per kind of event, for its page to be listed in the sitemap
const SO_SITEMAP_FORMAT    = 7;      // bump to rebuild every file once: 2 = full W3C datetimes in <lastmod> and the browser stylesheet in every file
const SO_SITEMAP_STYLE_PI  = '<?xml-stylesheet type="text/xsl" href="/sitemap.xsl"?>';   // a static file; the web server sends it as text/xsl (docs/server-rewrites.md)

/** Paths robots.txt disallows (robots.php prints them); the sitemap never lists a page under one of them. */
const SO_ROBOTS_DISALLOW = ['/admin/', '/ajax/', '/cache/', '/vendor/', '/db/', '/tools/', '/cron/', '/deploy/', '/docs/', '/inc/', '/search', '/checkout', '/newsletter', '/unsubscribe', '/thank-you', '/order-confirmation'];

/** Script names in the web root that are not pages of their own (templates behind a slug, the frame, endpoints, utility pages). */
const SO_SITEMAP_NOT_PAGES = ['404', 'header', 'footer', 'functions', 'robots', 'sitemap', 'sitemap-page', 'search', 'checkout', 'order-confirmation', 'thank-you', 'unsubscribe', 'newsletter-email'];

/** Pages that are real but that the scan cannot tell from a template behind a slug: the home page and the blog index. */
const SO_SITEMAP_EXTRA_PATHS = ['/', '/blog'];

/**
 * The site's own pages, found by looking at the web root instead of keeping a list: a script is a page when it prints the page frame
 * (header.php) or is a small stub that sets which list or genre to show ($soDirKey, $soGenreSlug), and it is not a template that reads a
 * slug from the address (performer, venue, city, the *-city and *-state families, the discovery pages), not a redirect, not a page that sets
 * "noindex" for itself and not on the robots.txt disallow list. A new page file therefore appears in the sitemap with the next rebuild.
 * tools/check-sitemap-pages.php (CI) fails when the scan returns something that is not a page.
 *
 * @return string[] paths such as /about-seat-outlet, the home page first
 */
function soSitemapStaticPaths(): array {
    static $paths = null;
    if ($paths !== null) return $paths;
    $found = [];
    foreach (glob(dirname(__DIR__) . '/*.php') ?: [] as $file) {
        $base = basename($file, '.php');
        if (in_array($base, SO_SITEMAP_NOT_PAGES, true)) continue;
        $src = (string) @file_get_contents($file);
        $framed = preg_match('/header\.php/', $src) || preg_match('/\$soDirKey\s*=|\$soGenreSlug\s*=/', $src);
        if (!$framed) continue;                                                                                          // a redirect, an endpoint or an include
        $stub = preg_match('/\$_GET\[[\'"]slug[\'"]\]\s*=\s*[\'"]/', $src);                                              // sets its own slug: a genre page
        if (!$stub && preg_match('/\$_GET\[[\'"](slug|id|loc)[\'"]\]\s*(\?\?|\)|;|,|\.|\])/', $src)) continue;              // reads a slug from the address
        if (preg_match('/render(Category|Artist)LocationPage|renderCityDiscoveryPage|renderCityHolidayPage/', $src)) continue;
        if (preg_match('/^\s*\$pageRobots\s*=\s*[\'"]noindex/m', $src)) continue;                                           // noindex for itself
        $found[] = $base === 'index' ? '/' : '/' . $base;
    }
    $all = array_unique(array_merge($found, SO_SITEMAP_EXTRA_PATHS));
    $all = array_values(array_filter($all, function ($p) {
        foreach (SO_ROBOTS_DISALLOW as $d) { if ($p !== '/' && strpos($p, rtrim($d, '/')) === 0) return false; }
        return true;
    }));
    sort($all);
    array_unshift($all, '/');
    return $paths = array_values(array_unique($all));
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
            foreach (['events', 'performers', 'venues', 'cities', 'citypages'] as $k) { @unlink($tmp . '/' . $k . '.tsv'); }
            $s = ['phase' => 'crawl', 'startedAt' => time(), 'page' => 1, 'pages' => null, 'fails' => 0, 'builtAt' => (int) ($s['builtAt'] ?? 0), 'counts' => $s['counts'] ?? [], 'fmt' => (int) ($s['fmt'] ?? 0)];
            soSitemapSaveState($s);
        }
        if (($s['phase'] ?? '') !== 'crawl') return 'fresh';

        $today = date('Y-m-d');
        $clean = function ($v) { return str_replace(["\t", "\r", "\n"], ' ', (string) $v); };
        while (microtime(true) < $deadline && $s['page'] <= ($s['pages'] ?? SO_SITEMAP_MAX_PAGES)) {
            if (function_exists('tnBreakerOpen') && tnBreakerOpen()) { $s['note'] = 'api paused (rate limited)'; break; }
            $params = ['filter' => "date/date ge $today and _metadata/hasTickets eq true and (country/alphaCode eq 'US' or country/alphaCode eq 'CA')",
                'sort' => 'date/date', 'perPage' => SO_SITEMAP_PER_PAGE, 'page' => $s['page']];
            if ($s['page'] === 1) $params['includeTotalCount'] = 'true';
            $r = tnRequest('/catalog/v2/events/', $params, 'GET', 0);   // ttl 0: a crawl page is read once, never cached
            if (!is_array($r) || !isset($r['results']) || !is_array($r['results'])) {
                if (++$s['fails'] >= 12) { $s['phase'] = 'crawl'; $s['page'] = 1; $s['fails'] = 0; $s['note'] = 'restarted after repeated API failures'; foreach (['events', 'performers', 'venues', 'cities', 'citypages'] as $k) { @unlink($tmp . '/' . $k . '.tsv'); } }
                break;
            }
            $s['fails'] = 0;
            if ($s['page'] === 1) $s['pages'] = min(SO_SITEMAP_MAX_PAGES, max(1, (int) ceil(((int) ($r['totalCount'] ?? 0)) / SO_SITEMAP_PER_PAGE)));
            $ev = $per = $ven = $cit = $cpg = '';
            soSlugWarmEvents($r['results']);   // one query per kind for the whole page, not one per link
            foreach ($r['results'] as $e) {
                $id = (int) ($e['id'] ?? 0);
                $name = (string) ($e['text']['name'] ?? '');
                if ($id <= 0 || $name === '') continue;
                $lm = soSitemapDate($e['metadataInclusiveUpdatedAt'] ?? ($e['updatedAt'] ?? '')) ?? '';
                $ev .= soEventSlug($e) . "\t" . $lm . "\n";
                foreach ($e['performers'] ?? [] as $p) {
                    if (!empty($p['id']) && !empty($p['name'])) $per .= (int) $p['id'] . "\t" . $clean($p['name']) . "\t" . $lm . "\n";
                }
                if (!empty($e['venue']['id']) && !empty($e['venue']['text']['name'])) $ven .= (int) $e['venue']['id'] . "\t" . $clean($e['venue']['text']['name']) . "\t" . $lm . "\n";
                if (!empty($e['city']['id']) && !empty($e['city']['text']['name'])) {
                    $cityLabel = $clean(trim($e['city']['text']['name'] . ', ' . ($e['stateProvince']['text']['abbr'] ?? ''), ', '));
                    $cit .= (int) $e['city']['id'] . "\t" . $cityLabel . "\t" . $lm . "\n";
                    // The city's page for each kind of event it has: all events, plus the concert, festival, sports and theater pages
                    // this event belongs to (festivals sit under concerts, the same nesting the pages themselves use).
                    $cPath = (string) ($e['defaultCategory']['path'] ?? '');
                    $kinds = [];   // the all-events page is the /city/ page itself; /event-city/ and /best-events/ redirect to it
                    if (strpos($cPath, TN_CATEGORY_PATH_CONCERTS) === 0) $kinds[] = 'concerts-city';
                    if (strpos($cPath, TN_CATEGORY_PATH_FESTIVAL) === 0) $kinds[] = 'festivals-city';
                    if (strpos($cPath, TN_CATEGORY_PATH_SPORTS) === 0) $kinds[] = 'sports-city';
                    if (strpos($cPath, TN_CATEGORY_PATH_THEATER) === 0) $kinds[] = 'theater-city';
                    // The city discovery pages (inc/discovery-pages.php): best and cheap are views of all the city's events; last minute and
                    // weekend only count events inside their date window (measured when the crawl runs).
                    $kinds[] = 'cheap-tickets';
                    $evDay = (string) ($e['date']['date'] ?? '');
                    $evDay = $evDay !== '' ? substr($evDay, 0, 10) : '';
                    if ($evDay !== '') {
                        $wk = listingDateRange('week'); $we = listingDateRange('weekend');
                        if ($wk && $evDay >= $wk[0] && $evDay <= $wk[1]) $kinds[] = 'last-minute-tickets';
                        if ($we && $evDay >= $we[0] && $evDay <= $we[1]) $kinds[] = 'weekend-events';
                    }
                    foreach (soHolidayKindsForEvent($e) as $hk) $kinds[] = 'holiday:' . $hk;   // /<holiday>-in-<city> (inc/holidays.php)
                    foreach ($kinds as $kind) $cpg .= (int) $e['city']['id'] . "\t" . $cityLabel . "\t" . $kind . "\t" . $lm . "\n";
                }
            }
            file_put_contents($tmp . '/events.tsv', $ev, FILE_APPEND);
            file_put_contents($tmp . '/performers.tsv', $per, FILE_APPEND);
            file_put_contents($tmp . '/venues.tsv', $ven, FILE_APPEND);
            file_put_contents($tmp . '/cities.tsv', $cit, FILE_APPEND);
            file_put_contents($tmp . '/citypages.tsv', $cpg, FILE_APPEND);
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
    foreach ((cache_get('top_categories', 30 * 86400) ?: []) as $bucket) {
        foreach ((array) $bucket as $cat) {   // the final address, from id and name: the cached slug can be an old name-and-id form that redirects
            if (empty($cat['id']) || empty($cat['name']) || (int) $cat['id'] === 2094) continue;
            $loc = $base . soCategoryHref((int) $cat['id'], (string) $cat['name']); $pages[$loc] = [$loc, null];
        }
    }
    // Every sub-category the ticket API lists under concerts, sports and theater that has tickets on sale (the same list as the sub-category pills).
    if (function_exists('soHubSubcategories')) {
        foreach (array_keys(SO_HUB_ROOTS) as $hub) {
            foreach (soHubSubcategories($hub) as $sub) { if ((int) ($sub['id'] ?? 0) === 2094) continue; $loc = $base . $sub['href']; $pages[$loc] = [$loc, null]; }
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
    $cityN = [];
    foreach (['performers' => ['/artist/', 'performer'], 'venues' => ['/venue/', 'venue'], 'cities' => ['/city/', 'city']] as $group => [$prefix, $slugType]) {
        $ents = [];
        foreach (@file($tmp . '/' . $group . '.tsv', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
            [$id, $name, $lm] = array_pad(explode("\t", $line), 3, '');
            $id = (int) $id;
            if ($id <= 0 || $name === '') continue;
            if (!isset($ents[$id])) $ents[$id] = [$name, $lm];
            elseif ($lm > $ents[$id][1]) $ents[$id][1] = $lm;
            if ($group === 'cities') $cityN[$id] = ($cityN[$id] ?? 0) + 1;   // upcoming events per city, for the county pages
        }
        $list = [];
        soSlugWarm(array_map(fn($i) => [$slugType, $i], array_keys($ents)));
        foreach ($ents as $id => [$name, $lm]) {
            $loc = $base . $prefix . soSlug($slugType, $name, $id);
            if ($keep($loc)) $list[] = [$loc, $lm !== '' ? $lm : null];
        }
        // A city's all-events, concert, festival, sports and theater pages join its /city/ page, but only where the city has at least
        // SO_SITEMAP_CITYPAGE_MIN upcoming events of that kind: the page works for any city, a one-event page is not worth a crawl.
        $holidayList = [];   // the holiday pages get a sitemap of their own: holiday-events-N.xml
        if ($group === 'cities') {
            $cp = [];
            foreach (@file($tmp . '/citypages.tsv', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
                [$id, $name, $kind, $lm] = array_pad(explode("\t", $line), 4, '');
                $id = (int) $id;
                if ($id <= 0 || $name === '' || $kind === '') continue;
                $k = $kind . "\t" . $id;
                if (!isset($cp[$k])) $cp[$k] = [$name, $lm, 0];
                $cp[$k][2]++;
                if ($lm > $cp[$k][1]) $cp[$k][1] = $lm;
            }
            soSlugWarm(array_map(fn($k) => ['city', (int) explode("\t", $k)[1]], array_keys($cp)));
            ksort($cp);
            foreach ($cp as $k => [$name, $lm, $n]) {
                if ($n < SO_SITEMAP_CITYPAGE_MIN) continue;
                [$kind, $id] = explode("\t", $k);
                $slug = soSlug('city', $name, (int) $id);
                $loc = strpos($kind, 'holiday:') === 0 ? $base . '/' . substr($kind, 8) . '-in-' . $slug : $base . '/' . $kind . '/' . $slug;
                if (!$keep($loc)) continue;
                if (strpos($kind, 'holiday:') === 0) $holidayList[] = [$loc, $lm !== '' ? $lm : null]; else $list[] = [$loc, $lm !== '' ? $lm : null];
            }
        }
        $countyList = [];
        if ($group === 'cities') {
            // US counties: queue every US city for a county lookup (cron/resolve-counties.php), then list the counties that are known and
            // have at least SO_SITEMAP_CITYPAGE_MIN upcoming events across their cities. Empty until the lookups have run.
            try {
                $q = []; foreach ($ents as $cid => [$cname]) $q[] = [$cid, $cname];
                soCountyEnqueue($q); soCountySetCounts($cityN);
                $tot = []; $lab = [];
                $cr = MYSQLI->query("SELECT city_id, county_fips, county_name, state_abbr FROM city_counties WHERE status = 'ok'");
                while ($cr && ($row = $cr->fetch_assoc())) { $f = (int) $row['county_fips']; $tot[$f] = ($tot[$f] ?? 0) + ($cityN[(int) $row['city_id']] ?? 0); $lab[$f] = $row['county_name'] . ', ' . $row['state_abbr']; }
                ksort($tot);
                foreach ($tot as $f => $n) {
                    if ($n < SO_SITEMAP_CITYPAGE_MIN) continue;
                    $loc = $base . '/county/' . soSlug('county', $lab[$f], $f);
                    if ($keep($loc)) $countyList[] = [$loc, null];
                }
            } catch (Throwable $e) { /* the county table is missing until migration 0044 has run */ }
        }
        $emit($group, $list);
        if ($group === 'cities') { $emit('holiday-events', $holidayList); $emit('counties', $countyList); }
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
