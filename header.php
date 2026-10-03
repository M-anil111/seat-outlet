<?php
// Include-only file: answer 404 if it is requested directly over the web (it would render a fragment or an error).
if (PHP_SAPI !== 'cli' && isset($_SERVER['SCRIPT_FILENAME']) && realpath($_SERVER['SCRIPT_FILENAME']) === __FILE__) { http_response_code(404); exit; }
// Was a plain include (not include_once). Harmless as long as every page
// included header.php as its very first statement (the original,
// universal pattern), but a real fatal "Cannot redeclare function" bug for
// every page built during this engagement that does require_once
// 'functions.php' BEFORE include 'header.php' - guarantee.php, bbb.php,
// city.php, venue.php, category.php, performer.php, state.php, country.php,
// the partner pages, and more (~20 files). Caught by actually executing
// these pages end to end against a live local PHP server + MariaDB
// instance, not by php -l or the static checkers, which don't catch
// runtime double-inclusion.
include_once 'functions.php';
    include_once __DIR__ . '/inc/consent.php';
    soRedirectLegacyUrl();
    sendSecurityHeaders();
    // The header search form now submits via GET so a results page has a
    // shareable/bookmarkable URL and the browser back button works (a POST
    // results page re-prompted "resubmit form?"). Old POST submissions and
    // any external links still work: both sources are merged here and
    // search.php reads the same array. Values are only ever echoed through
    // htmlspecialchars() below - the hidden lat/lng/date inputs previously
    // reflected raw request data into value="" attributes.
    $searchInput = $searchInput ?? soStringParams(array_merge($_GET, $_POST));
    if(!empty($searchInput['startInputHeader']) && !empty($searchInput['endInputHeader'])) {
        $dateTitle = $searchInput['startInputHeader'] . ' to ' . $searchInput['endInputHeader'];
    }else{
        $dateTitle = '';
    }

    // Admin-configured per-URL overrides (page_rules). Must run before any
    // HTML output: resolvePageRule() sends a redirect and exits immediately
    // when one is configured for this exact path. Otherwise it returns the
    // rule row (or null, the default - unchanged behavior) for the <head>
    // block below to use in place of the hardcoded/per-page-type SEO tags.
    $pageRule = resolvePageRule();

    // Static pages that never set their own title/description get theirs from
    // inc/page-meta.php (title, description, canonical, og:/twitter: tags and
    // baseline schema all come from the $pageMeta* branch below).
    $soReqPath = rtrim((string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH), '/') ?: '/';
    if (empty($pageMetaTitle)) {
        $soStaticMeta = require __DIR__ . '/inc/page-meta.php';
        if (isset($soStaticMeta[$soReqPath])) {
            $pageMetaTitle       = $soStaticMeta[$soReqPath][0] . ' | Seat Outlet';
            $pageMetaDescription = $soStaticMeta[$soReqPath][1];
            $pageCanonicalUrl    = rtrim(HOME_URL, '/') . $soReqPath;
        }
    }
    // The focus keyword plan (inc/seo-keywords.php) sets the keyword, title, description and canonical of the pages it lists.
    // An admin page rule for the same path (resolved above) still overrides the tags later in <head>.
    $soPlan = soSeoPlan($soReqPath);
    if ($soPlan !== null) {
        if (empty($pageRule['focus_keyword'])) { $pageFocusKeyword = $soPlan['keyword']; }
        if ($soPlan['title'] !== null)       { $pageMetaTitle       = $soPlan['title'] . ' | Seat Outlet'; }
        if ($soPlan['description'] !== null) { $pageMetaDescription = $soPlan['description']; }
        $pageCanonicalUrl = rtrim(HOME_URL, '/') . ($soReqPath === '/' ? '' : $soReqPath);
    }
    // Unknown event ids must answer 404 (they used to be a 200 page with a junk title). The status has
    // to be sent before any output; the event is cached by tnRequest, so inc/seo-event.php reuses it.
    if (strpos($soReqPath, '/event/') === 0) {
        $soEvId = (int) ($_GET['id'] ?? 0);
        if ($soEvId <= 0) { $soEvParts = explode('-', (string) ($_GET['slug'] ?? '')); $soEvId = (int) end($soEvParts); }
        $soEvCheck = $soEvId > 0 ? getTnEventById($soEvId) : null;
        if ($soEvCheck !== null && tnEntityUnavailable($soEvCheck)) {
            // The API failed (throttled, timeout, circuit open): a retryable 503, never a 404 that deindexes a live event.
            http_response_code(503);
            header('Retry-After: 30');
            $pageRobots = 'noindex, follow';
        } elseif ($soEvCheck === null || tnEntityMissing($soEvCheck) || empty($soEvCheck['text']['name'])) {
            // The event is gone from the catalog: send the visitor (and search engines) to the performer's page we
            // remembered for it, or the matching category, instead of a 404.
            if ($soEvId > 0 && tnEntityDefinitelyMissing($soEvCheck)) {
                $soTo = soEventRedirectTarget(null, $soEvId);
                if ($soTo !== '/buy-tickets-online') {
                    // A removed event with a known performer or category: say so on the page we send the visitor to.
                    header('Location: ' . $soTo . '?so_notice=event-gone', true, strpos($soTo, '/artist/') === 0 ? 301 : 302);   // permanent only when we know the performer
                    exit;
                }
                // Nothing remembered about it: a real 404 (the page below offers search and browse links), not a silent redirect.
            }
            http_response_code(404);
            $pageRobots = 'noindex, follow';
        } elseif (soEventIsOver($soEvCheck)) {
            // Over: the performer's page lists what is still on sale. Temporary (302): the next date of a tour may reuse the performer page, the event page itself is gone.
            $soTo = soEventRedirectTarget($soEvCheck, $soEvId);
            header('Location: ' . $soTo . '?so_notice=event-past', true, 302);
            exit;
        } else {
            // One canonical URL per event: lowercase "name-id" slug. The id is what identifies the event; any other spelling of the name 301s here.
            $soEvCanon = '/event/' . createSlug((string) $soEvCheck['text']['name'], (int) $soEvCheck['id']);
            if (in_array($_SERVER['REQUEST_METHOD'] ?? 'GET', ['GET', 'HEAD'], true) && PHP_SAPI !== 'cli' && $soReqPath !== $soEvCanon) {
                $soEvQs = (string) parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_QUERY);
                header('Location: ' . $soEvCanon . ($soEvQs !== '' ? '?' . $soEvQs : ''), true, 301);
                exit;
            }
            soEventRemember($soEvCheck);
        }
    }
    sendPageCacheHeaders();   // after the 404 check above: the status decides the policy
    // Keep titles and descriptions inside what a search result shows.
    if (!empty($pageMetaTitle))       { $pageMetaTitle       = seoClampTitle($pageMetaTitle); }
    if (!empty($pageMetaDescription)) { $pageMetaDescription = seoClampDescription($pageMetaDescription); }
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <script>window.dataLayer = window.dataLayer || [];</script>
    <?php /* Google Tag Manager, gated on the visitor's privacy choice (Global Privacy Control, or Decline in the privacy bar): see inc/consent.php */ ?>
    <?php if (GTM_ID !== '') { echo soConsentHeadScript() . "\n"; } ?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php echo function_exists('soAdsenseMetaTag') ? soAdsenseMetaTag() . "\n" : ''; ?>
    <meta name="robots" content="<?php echo htmlspecialchars($pageRule['robots'] ?? ($pageRobots ?? (SITE_INDEXABLE ? 'index, follow' : 'noindex, nofollow')), ENT_QUOTES, 'UTF-8'); ?>">
    <link rel="icon" type="image/png" href="/images/favicon-new.webp">
    <link rel="manifest" href="/manifest.webmanifest">
    <link rel="apple-touch-icon" href="/images/app/apple-touch-icon.png">
    <meta name="theme-color" content="#2556e0">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-title" content="Seat Outlet">
    
        <?php if (strpos($soReqPath, '/event/') === 0) { ?>
    <!-- The seat-map widget (loaded at the end of the page) and its static assets: start those connections now. -->
    <link rel="preconnect" href="https://mapwidget3.seatics.com" crossorigin>
    <link rel="preconnect" href="https://d1s8091zjpj5vh.cloudfront.net" crossorigin>
    <?php } ?>
    <?php if (!empty($pagePreloadImage)) { ?>
    <!-- LCP image that is only referenced from CSS (hero backgrounds): fetch it early. -->
    <link rel="preload" as="image" href="<?php echo htmlspecialchars($pagePreloadImage, ENT_QUOTES, 'UTF-8'); ?>" fetchpriority="high">
    <?php } ?>
    <!-- Critical CSS -->
    <?php /* css/bootstrap.min.css = Bootstrap trimmed to the classes this site uses (tools/build-assets.sh); the full file is the fallback. */ ?>
    <link rel="stylesheet" href="<?php echo is_file(__DIR__ . '/css/bootstrap.min.css') ? htmlspecialchars(soAsset('css/bootstrap.css'), ENT_QUOTES, 'UTF-8') : '/lib/bootstrap/5.3.8/bootstrap.min.css'; ?>">
    <?php if (is_file(__DIR__ . '/css/style.min.css')) { ?>
    <link rel="stylesheet" href="<?php echo htmlspecialchars(soAsset('css/style.css'), ENT_QUOTES, 'UTF-8'); ?>">
    <?php } else { ?>
    <link rel="stylesheet" href="<?php echo htmlspecialchars(soAsset('css/style.css'), ENT_QUOTES, 'UTF-8'); ?>">
    <link rel="stylesheet" href="<?php echo htmlspecialchars(soAsset('css/skeleton.css'), ENT_QUOTES, 'UTF-8'); ?>">
    <?php } ?>
    

    <?php /* Same content hash tools/build-icons.py writes into css/icons.css, so the preload is the request the stylesheet uses. */ ?>
    <link rel="preload" href="/fonts/bootstrap-icons-subset.woff2?v=<?php echo substr((string) @md5_file(__DIR__ . '/fonts/bootstrap-icons-subset.woff2'), 0, 10); ?>" as="font" type="font/woff2" crossorigin>
    <?php $soNeedsSlick = in_array($soReqPath, ['/', '/index.php', '/search', '/about-seat-outlet'], true) || strpos($soReqPath, '/event/') === 0; // carousel CSS: pages with a carousel, plus event pages (the Seatics seat-map widget uses slick classes) ?>
    <?php if ($soNeedsSlick) { ?>
    <link rel="preload" href="/lib/slick-carousel/1.8.1/slick.css" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <link rel="preload" href="/lib/slick-carousel/1.8.1/slick-theme.css" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <?php } ?>

    <!-- Inter is self-hosted (css/fonts.css, folded into style.min.css). Deliberately NOT preloaded: on a slow connection the 47 KB preload
         competes with the render-blocking CSS and delays first paint (measured: artist LCP 2.8 s -> 4.1 s); the metric-matched fallback font
         means the swap does not move anything. -->
    

    <noscript>
        <?php if ($soNeedsSlick) { ?>
        <link rel="stylesheet" href="/lib/slick-carousel/1.8.1/slick.css">
        <link rel="stylesheet" href="/lib/slick-carousel/1.8.1/slick-theme.css">
        <?php } ?>
    </noscript>
    <?php if ($pageRule && !empty($pageRule['meta_title'])) { ?>
        <title><?php echo htmlspecialchars($pageRule['meta_title'], ENT_QUOTES, 'UTF-8'); ?></title>
        <?php if (!empty($pageRule['meta_description'])) { ?>
        <meta name="description" content="<?php echo htmlspecialchars($pageRule['meta_description'], ENT_QUOTES, 'UTF-8'); ?>">
        <?php } ?>
        <?php if (!empty($pageRule['canonical_url'])) { ?>
        <link rel="canonical" href="<?php echo htmlspecialchars($pageRule['canonical_url'], ENT_QUOTES, 'UTF-8'); ?>">
        <?php } ?>
        <?php if (!empty($pageRule['schema_json'])) { ?>
        <script type="application/ld+json"><?php echo $pageRule['schema_json']; ?></script>
        <?php } ?>
    <?php } elseif (!empty($pageMetaTitle)) { ?>
        <?php
        // Set by the including page (before `include 'header.php'`) when it
        // already knows its own real title/description/canonical/schema -
        // e.g. the location-filtered performer/category pages, which need
        // to look up a performer and/or city/state/country/venue before
        // they can say anything real about themselves. Takes precedence
        // over the hardcoded per-page-type includes below, but not over an
        // explicit admin page_rules override above.
        ?>
        <title><?php echo htmlspecialchars($pageMetaTitle, ENT_QUOTES, 'UTF-8'); ?></title>
        <?php if (!empty($pageMetaDescription)) { ?>
        <meta name="description" content="<?php echo htmlspecialchars($pageMetaDescription, ENT_QUOTES, 'UTF-8'); ?>">
        <?php } ?>
        <?php if (!empty($pageCanonicalUrl)) { ?>
        <link rel="canonical" href="<?php echo htmlspecialchars($pageCanonicalUrl, ENT_QUOTES, 'UTF-8'); ?>">
        <?php } ?>
        <meta property="og:title" content="<?php echo htmlspecialchars($pageMetaTitle, ENT_QUOTES, 'UTF-8'); ?>">
        <?php if (!empty($pageMetaDescription)) { ?>
        <meta property="og:description" content="<?php echo htmlspecialchars($pageMetaDescription, ENT_QUOTES, 'UTF-8'); ?>">
        <?php } ?>
        <?php if (!empty($pageCanonicalUrl)) { ?>
        <meta property="og:url" content="<?php echo htmlspecialchars($pageCanonicalUrl, ENT_QUOTES, 'UTF-8'); ?>">
        <?php } ?>
        <meta property="og:type" content="<?php echo htmlspecialchars($pageOgType ?? 'website', ENT_QUOTES, 'UTF-8'); ?>">
        <meta property="og:image" content="<?php echo htmlspecialchars($pageOgImage ?? (HOME_URL . '/images/seatoutlet-logo.webp'), ENT_QUOTES, 'UTF-8'); ?>">
        <meta name="twitter:card" content="summary_large_image">
        <meta name="twitter:image" content="<?php echo htmlspecialchars($pageOgImage ?? (HOME_URL . '/images/seatoutlet-logo.webp'), ENT_QUOTES, 'UTF-8'); ?>">
        <meta name="twitter:title" content="<?php echo htmlspecialchars($pageMetaTitle, ENT_QUOTES, 'UTF-8'); ?>">
        <?php if (!empty($pageMetaDescription)) { ?>
        <meta name="twitter:description" content="<?php echo htmlspecialchars($pageMetaDescription, ENT_QUOTES, 'UTF-8'); ?>">
        <?php } ?>
        <?php
        // Was gated behind !empty($pageJsonLdNodes) - meaning any page that
        // set $pageMetaTitle without also building its own schema nodes
        // (guarantee.php, bbb.php, testimonials.php, and 40+ others)
        // got ZERO structured data, not even the baseline Organization/
        // WebSite graph every other path on the site has. Always emit that
        // baseline here; merge in page-specific nodes when present.
        outputJsonLdGraph(array_merge([buildOrganizationSchema(), buildWebsiteSchema()], $pageJsonLdNodes ?? []));
        ?>
    <?php } elseif ($soReqPath === '/' || $soReqPath === '/index.php') { ?>
        <?php include 'inc/seo.php'; ?>
    <?php }elseif ($soReqPath === '/buy-tickets-online' || $soReqPath === '/buy-tickets-online.php') { ?>
        <?php include 'inc/seo-tickets.php'; ?>
    <?php }elseif (strpos($soReqPath, '/event/') === 0) { ?>
        <?php include 'inc/seo-event.php'; ?>
    <?php } else { ?>
        <?php
        // Fallback for every page that hasn't set $pageMetaTitle and isn't
        // one of the hardcoded special cases above (audited: this used to
        // be true of the large majority of pages on the site - concerts.php,
        // performer.php, city.php, venue.php, category.php, search.php, and
        // every static marketing page - meaning they rendered with no
        // <title> tag and no canonical link at all). A generic, mechanical
        // title/self-referencing canonical beats none; it's not a
        // replacement for a page setting its own real $pageMetaTitle/
        // $pageCanonicalUrl the way the location pages do, just a safety net
        // so nothing ships with a blank <title> or a missing canonical.
        $fallbackPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
        $fallbackSlug = trim($fallbackPath, '/');
        $fallbackName = $fallbackSlug === '' ? 'Home' : ucwords(str_replace(['-', '_'], ' ', basename($fallbackSlug)));
        $fallbackTitle = $fallbackName . ' | Seat Outlet';
        $fallbackCanonical = rtrim(HOME_URL, '/') . $fallbackPath;
        ?>
        <title><?php echo htmlspecialchars($fallbackTitle, ENT_QUOTES, 'UTF-8'); ?></title>
        <link rel="canonical" href="<?php echo htmlspecialchars($fallbackCanonical, ENT_QUOTES, 'UTF-8'); ?>">
        <meta property="og:title" content="<?php echo htmlspecialchars($fallbackTitle, ENT_QUOTES, 'UTF-8'); ?>">
        <meta property="og:url" content="<?php echo htmlspecialchars($fallbackCanonical, ENT_QUOTES, 'UTF-8'); ?>">
        <meta property="og:type" content="website">
        <meta property="og:image" content="<?php echo HOME_URL; ?>/images/seatoutlet-logo.webp">
        <?php outputJsonLdGraph([buildOrganizationSchema(), buildWebsiteSchema()]); ?>
    <?php } ?>

</head>

<body<?php echo !empty($soHomeHero) ? ' class="so-home"' : ''; ?>>
    <?php
    // The strip above the header is the page's one <h1>: its focus keyword (admin > Page rules, or the page's own).
    // The same keyword closes the footer. Any other <h1> in a page template is turned into an <h2> that looks the same.
    // Pages rendered from inside a function keep their SEO variables local: hand them to soFocusKeyword().
    $GLOBALS['pageMetaTitle'] = $pageMetaTitle ?? ($GLOBALS['pageMetaTitle'] ?? '');
    $GLOBALS['pageFocusKeyword'] = $pageFocusKeyword ?? ($GLOBALS['pageFocusKeyword'] ?? '');
    $GLOBALS['pageRule'] = $pageRule ?? ($GLOBALS['pageRule'] ?? null);
    $soFocusKw = soFocusKeyword();
    $soKeywordH1 = getenv('KEYWORD_H1') !== '0';
    if ($soKeywordH1) { ob_start('soSingleH1'); }
    ?>
    <a class="so-skip" href="#main">Skip to main content</a>
    <?php
    // The search bar is folded away behind the Search button (an icon on phones), except on the search page and
    // when the visitor arrived with a search (filled fields).
    $soSearchOpen = in_array($soReqPath, ['/search'], true)
        || !empty($searchInput['locationInputHeader']) || !empty($searchInput['keywordHeader']) || !empty($searchInput['startInputHeader']);
    ?>
    <div class="header-top-section">
        <div class="so-topstrip">
        <!-- Top keyword strip -->
        <div class="keyword-topbar">
            <svg class="so-strip-ic" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 8a2 2 0 0 0 0 4v0a2 2 0 0 0 0 4v1.5h18V16a2 2 0 0 0 0-4v0a2 2 0 0 0 0-4V6.5H3V8zM14 6.5v11"/></svg>
            <?php if ($soKeywordH1) { ?>
            <h1 class="so-keyword-h1"><?php echo htmlspecialchars(soKeywordLabel($soFocusKw), ENT_QUOTES, 'UTF-8'); ?></h1>
            <?php } else { ?>
            <p class="so-keyword-h1"><?php echo htmlspecialchars(soKeywordLabel($soFocusKw), ENT_QUOTES, 'UTF-8'); ?></p>
            <?php } ?>
            <a href="/worry-free-guarantee" class="so-strip-guarantee d-lg-none">100% Guarantee</a>
        </div>
        <!-- Trust bar: static (it used to scroll), keeps the resale disclosure in view -->
        <div class="tm-topbar">
            <ul class="so-trustbar">
                <li><svg class="so-strip-ic" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3l7.5 3v5.5c0 4.6-3.1 8.1-7.5 9.5-4.4-1.4-7.5-4.9-7.5-9.5V6L12 3z"/><path d="m9 12 2.2 2.2L15.5 10"/></svg>Trusted resale marketplace</li>
                <li><svg class="so-strip-ic" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 20V10M10 20V4M16 20v-7M22 20H2"/></svg>Prices may be above or below face value</li>
                <li><svg class="so-strip-ic" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 14v-2a8 8 0 0 1 16 0v2"/><rect x="2.5" y="13" width="4" height="6" rx="1.5"/><rect x="17.5" y="13" width="4" height="6" rx="1.5"/><path d="M19.5 19c0 1.5-1.5 2.5-4 2.5h-2"/></svg><a href="/worry-free-guarantee">100% Worry-Free Guarantee</a></li>
            </ul>
        </div>
        </div>
        <!-- MAIN BLUE HEADER -->
        <header class="tm-header">
            <div class="container-fluid p-0 px-md-4 px-lg-5 pb-md-4 pb-0">
                <div class="d-flex align-items-center justify-content-between py-md-4 py-3 px-md-0 px-2">
                    <!-- LEFT -->
                    <div class="d-flex align-items-center gap-4 so-header-left">
                        <!-- Logo -->
                        <a href="/" class="tm-logo"><img src="/images/seatoutlet-logo.webp" alt="Seat Outlet" width="256" height="38" loading="eager"></a>
                    </div>
                    <!-- RIGHT -->
                    <div class="d-flex align-items-center gap-3">
                        <nav class="tm-nav-wrapper d-none d-lg-block" aria-label="Main">
                            <?php $soMenu = require __DIR__ . '/inc/menu.php'; $soIc = function ($path) { return '<svg class="so-ic" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $path . '</svg>'; }; ?>
                            <ul class="tm-nav so-mega-nav" id="mainMenu">
                                <?php foreach ($soMenu as $mi => $m) { ?>
                                <li class="menu-item so-mega-item">
                                    <a href="<?php echo $m['href']; ?>" class="so-mega-top" data-so-mega="<?php echo $m['key']; ?>" aria-haspopup="true" aria-expanded="false"><?php echo htmlspecialchars($m['label'], ENT_QUOTES, 'UTF-8'); ?></a>
                                    <div class="so-mega" id="so-mega-<?php echo $m['key']; ?>" hidden>
                                        <div class="so-mega__inner">
                                            <a class="so-mega__lead" href="<?php echo $m['href']; ?>">
                                                <span class="so-mega__icon"><?php echo $soIc($m['icon']); ?></span>
                                                <strong><?php echo htmlspecialchars($m['label'], ENT_QUOTES, 'UTF-8'); ?></strong>
                                                <span><?php echo htmlspecialchars($m['tag'], ENT_QUOTES, 'UTF-8'); ?></span>
                                                <em><?php echo htmlspecialchars($m['all'], ENT_QUOTES, 'UTF-8'); ?> &rsaquo;</em>
                                            </a>
                                            <?php foreach ($m['groups'] as $g) { ?>
                                            <div class="so-mega__col">
                                                <p class="so-mega__title"><?php echo htmlspecialchars($g['title'], ENT_QUOTES, 'UTF-8'); ?></p>
                                                <ul>
                                                    <?php foreach ($g['links'] as [$label, $href]) { ?>
                                                    <li><a href="<?php echo $href; ?>"><?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?></a></li>
                                                    <?php } ?>
                                                </ul>
                                            </div>
                                            <?php } ?>
                                        </div>
                                    </div>
                                </li>
                                <?php } ?>
                            </ul>
                        </nav>
                        <div class="tm-top-links so-header-actions d-flex d-sm-flex d-md-flex align-items-center">
                            <button type="button" class="btn so-icon-btn so-search-toggle p-0" aria-label="Search" aria-expanded="<?php echo $soSearchOpen ? 'true' : 'false'; ?>" aria-controls="soSearch">
                                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="6.5"/><path d="m20 20-4.2-4.2"/></svg><span class="so-search-toggle__label">Search</span>
                            </button>
                            <button type="button" class="btn mobile-menu-btn so-icon-btn d-lg-none p-0" data-bs-toggle="offcanvas" data-bs-target="#mobileMenu" aria-label="Open menu">
                                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M4 8h16M4 16h16"/></svg>
                            </button>
                        </div>
                    </div>
                </div>
                <form method="get" action="/search" class="search-bar-form" id="soSearch"<?php echo $soSearchOpen ? '' : ' data-so-collapsed'; ?>>
                    <div class="search-bar-container d-flex flex-md-row p-md-1">
                        <div class="city-location search-item d-flex align-items-center gap-md-2 gap-1 px-3 py-2 flex-fill header-location-close locationInputFieldWrapper">
                            <svg class="icon" style="color: rgb(50 85 223);" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            </svg>
                            <input type="text" placeholder="City or Zip Code" class="w-100 locationInputField" autocomplete="off" id="locationInputHeader" name="locationInputHeader" role="combobox" aria-autocomplete="list" aria-expanded="false" aria-controls="location-suggestions" aria-activedescendant="" aria-label="Search by city or zip code" value="<?php echo !empty($searchInput['locationInputHeader']) ? htmlspecialchars($searchInput['locationInputHeader'], ENT_QUOTES, 'UTF-8') : ''; ?>" />
                            <button type="button" id="locationHeaderReset" class="location-close<?php echo !empty($searchInput['locationInputHeader']) ? '' : ' d-none'; ?>" aria-label="Clear location">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-x-octagon" viewBox="0 0 16 16">
                                    <path d="M4.54.146A.5.5 0 0 1 4.893 0h6.214a.5.5 0 0 1 .353.146l4.394 4.394a.5.5 0 0 1 .146.353v6.214a.5.5 0 0 1-.146.353l-4.394 4.394a.5.5 0 0 1-.353.146H4.893a.5.5 0 0 1-.353-.146L.146 11.46A.5.5 0 0 1 0 11.107V4.893a.5.5 0 0 1 .146-.353zM5.1 1 1 5.1v5.8L5.1 15h5.8l4.1-4.1V5.1L10.9 1z"/>
                                    <path d="M4.646 4.646a.5.5 0 0 1 .708 0L8 7.293l2.646-2.647a.5.5 0 0 1 .708.708L8.707 8l2.647 2.646a.5.5 0 0 1-.708.708L8 8.707l-2.646 2.647a.5.5 0 0 1-.708-.708L7.293 8 4.646 5.354a.5.5 0 0 1 0-.708"/>
                                </svg>
                            </button>
                            <div id="locationResultsHeader" class="tn-dropdown-menu dropdown"></div>                           
                        </div>
                        <div class="date-range search-item d-flex align-items-center so-date-picker-wrapper gap-md-2 gap-1 px-md-3 px-2 py-2 flex-fill border-start" style="border-color: rgba(0,0,0,0.1);">
                            <svg class="icon" style="color: rgb(50 85 223);" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                            <input type="text" id="customDatePicker" placeholder="Select Date Range" autocomplete="off" readonly role="combobox" aria-haspopup="dialog" aria-expanded="false" aria-label="Select date range" value="<?php echo !empty($dateTitle) ? htmlspecialchars($dateTitle) : ''; ?>"> 
                            <div class="filter-arrow"><i id="dateArrowHeader" class="bi bi-chevron-down"></i></div>
                        </div>
                        <div class="performer-city-venue search-item d-flex align-items-center gap-md-2 gap-1 px-3 py-2 flex-fill border-start header-venus-close" style="border-color: rgba(0,0,0,0.1);">
                            <svg class="icon" style="color: rgb(50 85 223);" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                            <input type="text" placeholder="<?php echo htmlspecialchars($pageSearchPlaceholder ?? 'Performer, City or Venue', ENT_QUOTES, 'UTF-8'); ?>" class="w-100" autocomplete="off" id="keywordHeader" name="keywordHeader" role="combobox" aria-autocomplete="list" aria-expanded="false" aria-controls="search-suggestions" aria-activedescendant="" aria-label="Search for performers, cities or venues" value="<?php echo !empty($searchInput['keywordHeader']) ? htmlspecialchars($searchInput['keywordHeader'], ENT_QUOTES, 'UTF-8') : ''; ?>" />
                            <button type="button" id="keywordHeaderReset" class="d-none location-close" aria-label="Clear search">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-x-octagon" viewBox="0 0 16 16">
                                    <path d="M4.54.146A.5.5 0 0 1 4.893 0h6.214a.5.5 0 0 1 .353.146l4.394 4.394a.5.5 0 0 1 .146.353v6.214a.5.5 0 0 1-.146.353l-4.394 4.394a.5.5 0 0 1-.353.146H4.893a.5.5 0 0 1-.353-.146L.146 11.46A.5.5 0 0 1 0 11.107V4.893a.5.5 0 0 1 .146-.353zM5.1 1 1 5.1v5.8L5.1 15h5.8l4.1-4.1V5.1L10.9 1z"/>
                                    <path d="M4.646 4.646a.5.5 0 0 1 .708 0L8 7.293l2.646-2.647a.5.5 0 0 1 .708.708L8.707 8l2.647 2.646a.5.5 0 0 1-.708.708L8 8.707l-2.646 2.647a.5.5 0 0 1-.708-.708L7.293 8 4.646 5.354a.5.5 0 0 1 0-.708"/>
                                </svg>
                            </button>
                            <div id="search-loader" class="search-loader" style="display:none;">
                                <svg width="16" height="16" viewBox="0 0 50 50">
                                    <circle cx="25" cy="25" r="20" fill="none" stroke="#666" stroke-width="4" stroke-linecap="round" stroke-dasharray="90,150" stroke-dashoffset="0">
                                        <animateTransform attributeName="transform" type="rotate" repeatCount="indefinite" dur="1s" values="0 25 25;360 25 25" />
                                    </circle>
                                </svg>
                            </div>
                            <div id="keywordResultsHeader" class="tn-dropdown-menu dropdown"></div>
                            <button class="btn btn-stub-primary px-4 py-2 small fw-semibold rounded-pill d-md-none d-block" aria-label="Search">
                                <svg viewBox="0 0 23 24" width="1.5em" height="1.5em" aria-hidden="true" focusable="false" class="BaseSvg-sc-yh8lnd-0 MagnifyingGlassIcon___StyledBaseSvg-sc-1pooy9n-0 hNajXU"><path d="M3.78 4.78 1.62 10l2.16 5.22L9 17.38l5.22-2.16L16.38 10l-2.16-5.22L9 2.62zM9 1l6.36 2.64L18 10l-2.33 5.61 6.11 6.11-1.06 1.06-6.1-6.1L9 19l-6.36-2.64L0 10l2.64-6.36z"></path></svg>
                            </button>
                        </div>
                        <input type="hidden" id="latHeader" name="latHeader" value="<?php echo !empty($searchInput['latHeader']) ? htmlspecialchars($searchInput['latHeader'], ENT_QUOTES, 'UTF-8') : ''; ?>">
                        <input type="hidden" id="lngHeader" name="lngHeader" value="<?php echo !empty($searchInput['lngHeader']) ? htmlspecialchars($searchInput['lngHeader'], ENT_QUOTES, 'UTF-8') : ''; ?>">
                        <input type="hidden" id="startInputHeader" name="startInputHeader" value="<?php echo !empty($searchInput['startInputHeader']) ? htmlspecialchars($searchInput['startInputHeader'], ENT_QUOTES, 'UTF-8') : ''; ?>">
                        <input type="hidden" id="endInputHeader" name="endInputHeader" value="<?php echo !empty($searchInput['endInputHeader']) ? htmlspecialchars($searchInput['endInputHeader'], ENT_QUOTES, 'UTF-8') : ''; ?>">
                        <button class="btn btn-stub-primary px-4 py-2 small fw-semibold rounded-pill d-md-block d-none">Search</button>
                    </div>
                </form>
            </div>
        </header>
    </div>
    <?php require_once __DIR__ . '/inc/event-notice.php'; echo soEventNoticeHtml(); ?>
    <!-- Mobile Header -->


    <div class="offcanvas offcanvas-start so-menu" tabindex="-1" id="mobileMenu" aria-label="Menu">
        <div class="so-menu__head">
            <a href="/" class="so-menu__logo"><img src="/images/seatoutlet-logo.webp" alt="Seat Outlet" width="180" height="27"></a>
            <button type="button" class="so-menu__close" data-bs-dismiss="offcanvas" aria-label="Close menu"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" aria-hidden="true"><path d="m6 6 12 12M18 6 6 18"/></svg></button>
        </div>
        <div class="so-menu__body" data-so-menu>
            <div class="so-menu__rail" role="tablist" aria-label="Sections">
                <?php foreach ($soMenu as $mi => $m) { ?>
                <button type="button" class="so-menu__tab<?php echo $mi === 0 ? ' is-active' : ''; ?>" role="tab" id="so-tab-<?php echo $m['key']; ?>" aria-controls="so-panel-<?php echo $m['key']; ?>" tabindex="<?php echo $mi === 0 ? '0' : '-1'; ?>" data-tab="<?php echo $m['key']; ?>" aria-selected="<?php echo $mi === 0 ? 'true' : 'false'; ?>">
                    <?php echo $soIc($m['icon']); ?>
                    <span><?php echo htmlspecialchars($m['label'], ENT_QUOTES, 'UTF-8'); ?></span>
                </button>
                <?php } ?>
                <button type="button" class="so-menu__tab" role="tab" id="so-tab-help" aria-controls="so-panel-help" tabindex="-1" data-tab="help" aria-selected="false">
                    <?php echo $soIc('<circle cx="12" cy="12" r="9"/><path d="M9.5 9.5a2.5 2.5 0 1 1 3.6 2.2c-.7.4-1.1.9-1.1 1.8M12 17h.01"/>'); ?>
                    <span>Help</span>
                </button>
            </div>
            <div class="so-menu__pane">
                <?php foreach ($soMenu as $mi => $m) { ?>
                <div class="so-menu__panel" role="tabpanel" id="so-panel-<?php echo $m['key']; ?>" aria-labelledby="so-tab-<?php echo $m['key']; ?>" data-panel="<?php echo $m['key']; ?>"<?php echo $mi === 0 ? '' : ' hidden'; ?>>
                    <a class="so-menu__all" href="<?php echo $m['href']; ?>"><?php echo htmlspecialchars($m['all'], ENT_QUOTES, 'UTF-8'); ?> <span aria-hidden="true">&rsaquo;</span></a>
                    <?php foreach ($m['groups'] as $g) { ?>
                    <p class="so-menu__title"><?php echo htmlspecialchars($g['title'], ENT_QUOTES, 'UTF-8'); ?></p>
                    <ul>
                        <?php foreach ($g['links'] as [$label, $href]) { ?>
                        <li><a href="<?php echo $href; ?>"><?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?></a></li>
                        <?php } ?>
                    </ul>
                    <?php } ?>
                </div>
                <?php } ?>
                <div class="so-menu__panel" role="tabpanel" id="so-panel-help" aria-labelledby="so-tab-help" data-panel="help" hidden>
                    <ul>
                        <li><a href="/how-to-buy-tickets-online">How to buy tickets</a></li>
                        <li><a href="/worry-free-guarantee">Our 100% guarantee</a></li>
                        <li><a href="/ticket-buyer-protection">Buyer protection</a></li>
                        <li><a href="/ticket-faq">Ticket FAQ</a></li>
                        <li><a href="/ticket-customer-service">Contact us</a></li>
                        <li><a href="/about-seat-outlet">About Seat Outlet</a></li>
                        <li><a href="/blog">Blog</a></li>
                    </ul>
                </div>
            </div>
        </div>
        <div class="so-menu__foot">
            <a class="so-menu__search" href="/search"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="11" r="6.5"/><path d="m20 20-4.2-4.2"/></svg> Search events</a>
        </div>
    </div>
    <?php // The page's one landmark: skip link target. Pages that print their own <main> get a <div> instead (soSingleMain in inc/consent.php); footer.php closes this one. ?>
    <?php ob_start('soSingleMain'); ?>
    <main id="main" tabindex="-1" data-so-main>
