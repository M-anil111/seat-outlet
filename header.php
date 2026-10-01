<?php
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
    sendSecurityHeaders();
    // The header search form now submits via GET so a results page has a
    // shareable/bookmarkable URL and the browser back button works (a POST
    // results page re-prompted "resubmit form?"). Old POST submissions and
    // any external links still work: both sources are merged here and
    // search.php reads the same array. Values are only ever echoed through
    // htmlspecialchars() below - the hidden lat/lng/date inputs previously
    // reflected raw request data into value="" attributes.
    $searchInput = $searchInput ?? array_merge($_GET, $_POST);
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
            http_response_code(404);
            $pageRobots = 'noindex, follow';
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
    <?php if (GTM_ID !== '') { ?>
    <script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src='https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);})(window,document,'script','dataLayer','<?php echo htmlspecialchars(GTM_ID, ENT_QUOTES, 'UTF-8'); ?>');</script>
    <?php } ?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="<?php echo htmlspecialchars($pageRule['robots'] ?? ($pageRobots ?? (SITE_INDEXABLE ? 'index, follow' : 'noindex, nofollow')), ENT_QUOTES, 'UTF-8'); ?>">
    <link rel="icon" type="image/png" href="/images/favicon-new.webp">
    
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
    

    <link rel="preload" href="/fonts/bootstrap-icons-subset.woff2?v=1.13.1" as="font" type="font/woff2" crossorigin>
    <?php $soNeedsSlick = in_array($soReqPath, ['/', '/index.php', '/search', '/about-us'], true) || strpos($soReqPath, '/event/') === 0; // carousel CSS: pages with a carousel, plus event pages (the Seatics seat-map widget uses slick classes) ?>
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
        <meta property="og:type" content="website">
        <meta property="og:image" content="<?php echo htmlspecialchars($pageOgImage ?? (HOME_URL . '/images/seatoutlet-logo.webp'), ENT_QUOTES, 'UTF-8'); ?>">
        <meta name="twitter:card" content="summary_large_image">
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
    <?php }elseif ($soReqPath === '/tickets' || $soReqPath === '/tickets.php') { ?>
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

<body>
    <div class="header-top-section">
        <!-- Top Utility Bar -->
        <div class="keyword-topbar">
            <div class="text-white text-center">
                <p>Buy Concert Tickets</p>
            </div>
        </div>
        <div class="tm-topbar">
            <div class="container-fluid">
                <div class="top-ticker">
                    <div class="ticker-track">
                        <div class="ticker-content">
                            <span class="ticker-item">
                                <span class="ticker-icon"></span>
                                Trusted marketplace for buying and selling live event tickets. Prices may vary from face value.
                            </span>

                            <span class="ticker-item">
                                <span class="ticker-icon"></span>
                                A trusted marketplace for live event tickets, connecting buyers and sellers worldwide.
                            </span>
                            <span class="ticker-item">
                                <span class="ticker-icon"></span>
                                Your reliable destination to buy and sell tickets for live events.
                            </span>
                            <span class="ticker-item">
                                <span class="ticker-icon"></span>
                                A secure platform for fans to buy and sell live event tickets.
                            </span>
                            <span class="ticker-item">
                                <span class="ticker-icon"></span>
                                The trusted hub for buying and selling tickets to concerts, sports, and live events.
                            </span>
                            <span class="ticker-item">
                                <span class="ticker-icon"></span>
                                A dependable marketplace for discovering and trading live event tickets.
                            </span>
                            <span class="ticker-item">
                                <span class="ticker-icon"></span>
                                Your Reliable Source for Live Event Tickets.
                            </span>
                            <span class="ticker-item">
                                <span class="ticker-icon"></span>
                                Trusted marketplace for buying and selling live event tickets. Prices may vary from face value.
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- MAIN BLUE HEADER -->
        <header class="tm-header">
            <div class="container-fluid p-0 px-md-4 px-lg-5 pb-md-4 pb-0">
                <div class="d-flex align-items-center justify-content-between py-md-4 py-3 px-md-0 px-2">
                    <!-- LEFT -->
                    <div class="d-flex align-items-center gap-4">
                        <!-- Logo -->
                        <a href="/" class="tm-logo"><img src="/images/seatoutlet-logo.webp" alt="Seat Outlet" width="256" height="38" loading="eager"></a>
                    </div>
                    <!-- RIGHT -->
                    <div class="d-flex align-items-center gap-3">
                        <nav class="tm-nav-wrapper d-none d-sm-none d-md-none d-lg-block d-xl-block d-xxl-block">
                            <ul class="tm-nav" id="mainMenu">
                                <li class="menu-item"><a href="/concerts">Concerts</a></li>
                                <li class="menu-item"><a href="/sports">Sports</a></li>
                                <li class="menu-item"><a href="/theater">Theater</a></li>
                                <li class="menu-item"><a href="/festival">Festivals</a></li>
                                <li class="menu-item"><a href="/cities">Cities</a></li>
                            </ul>
                        </nav>
                        <div class="tm-top-links d-flex d-sm-flex d-md-flex align-items-center">
                            <div class="header-phone d-lg-none d-xl-none d-xxl-none"><a href="tel:+1512-621-8822" aria-label="Call us at (512) 621-8822"><i class="bi bi-telephone-fill"></i></a></div>
                            <button class="btn mobile-menu-btn d-sm-block d-md-block d-lg-none d-xl-none d-xxl-none p-0" data-bs-toggle="offcanvas" data-bs-target="#mobileMenu" aria-label="menu">
                                <i class="bi bi-list fs-3 text-white"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <form method="get" action="/search" class="search-bar-form">
                    <div class="search-bar-container d-flex flex-md-row p-md-1">
                        <div class="city-location search-item d-flex align-items-center gap-md-2 gap-1 px-3 py-2 flex-fill header-location-close locationInputFieldWrapper">
                            <svg class="icon" style="color: rgb(50 85 223);" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            </svg>
                            <input type="text" placeholder="City or Zip Code" class="w-100" autocomplete="off" class="locationInputField" id="locationInputHeader" name="locationInputHeader" role="combobox" aria-autocomplete="list" aria-expanded="false" aria-controls="location-suggestions" aria-activedescendant="" aria-label="Search by city or zip code" value="<?php echo !empty($searchInput['locationInputHeader']) ? htmlspecialchars($searchInput['locationInputHeader'], ENT_QUOTES, 'UTF-8') : ''; ?>" />
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
                            <input type="text" id="customDatePicker" placeholder="Select Date Range" autocomplete="off" readonly role="combobox" aria-haspopup="dialog" aria-expanded="false" aria-controls="date-picker-dialog" aria-label="Select date range" value="<?php echo !empty($dateTitle) ? htmlspecialchars($dateTitle) : ''; ?>"> 
                            <div class="filter-arrow"><i id="dateArrowHeader" class="bi bi-chevron-down"></i></div>
                        </div>
                        <div class="performer-city-venue search-item d-flex align-items-center gap-md-2 gap-1 px-3 py-2 flex-fill border-start header-venus-close" style="border-color: rgba(0,0,0,0.1);">
                            <svg class="icon" style="color: rgb(50 85 223);" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                            <input type="text" placeholder="Performer, City or Venue" class="w-100" autocomplete="off" id="keywordHeader" name="keywordHeader" role="combobox" aria-autocomplete="list" aria-expanded="false" aria-controls="search-suggestions" aria-activedescendant="" aria-label="Search for performers, cities or venues" value="<?php echo !empty($searchInput['keywordHeader']) ? htmlspecialchars($searchInput['keywordHeader'], ENT_QUOTES, 'UTF-8') : ''; ?>" />
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
    <!-- Mobile Header -->


    <div class="offcanvas offcanvas-start header-menu-mobile-logo" tabindex="-1" id="mobileMenu">
        <div class="offcanvas-header">
            <a href="/" class="tm-logo" style="width:200px; height:auto;"><img src="/images/blue-logo.webp" alt="Seat Outlet" width="200" height="40"></a>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close menu"></button>
        </div>
        
        <div class="offcanvas-body header-menu-mobile">
            <nav class="mobile-nav">
                <ul class="mobile-main-menu">
                    <li class="has-submenu">

                        <!-- ✅ ADD class + data-target -->
                        <a href="/concerts" class="open-submenu" data-target="submenu-concerts">
                            Concerts <span class="arrow">
                                <svg xmlns="http://www.w3.org/2000/svg" version="1.1" xmlns:xlink="http://www.w3.org/1999/xlink" width="16" height="16" x="0" y="0" viewBox="0 0 492.004 492.004" style="enable-background:new 0 0 512 512" xml:space="preserve" class=""><g><path d="M382.678 226.804 163.73 7.86C158.666 2.792 151.906 0 144.698 0s-13.968 2.792-19.032 7.86l-16.124 16.12c-10.492 10.504-10.492 27.576 0 38.064L293.398 245.9l-184.06 184.06c-5.064 5.068-7.86 11.824-7.86 19.028 0 7.212 2.796 13.968 7.86 19.04l16.124 16.116c5.068 5.068 11.824 7.86 19.032 7.86s13.968-2.792 19.032-7.86L382.678 265c5.076-5.084 7.864-11.872 7.848-19.088.016-7.244-2.772-14.028-7.848-19.108z" fill="#000000" opacity="1" data-original="#000000" class=""></path></g></svg>
                            </span>
                        </a>
                        
                        <!-- Submenu Panel -->
                        <div class="submenu-panel" id="submenu-concerts">
                            <div class="submenu-header">
                                <span class="back-btn me-3">
                                    <svg xmlns="http://www.w3.org/2000/svg" version="1.1" xmlns:xlink="http://www.w3.org/1999/xlink" width="16" height="16" x="0" y="0" viewBox="0 0 492.004 492.004" style="enable-background:new 0 0 512 512" xml:space="preserve" class=""><g transform="matrix(-1,1.2246467991473532e-16,-1.2246467991473532e-16,-1,497.00405883789074,492.0039672851562)"><path d="M382.678 226.804 163.73 7.86C158.666 2.792 151.906 0 144.698 0s-13.968 2.792-19.032 7.86l-16.124 16.12c-10.492 10.504-10.492 27.576 0 38.064L293.398 245.9l-184.06 184.06c-5.064 5.068-7.86 11.824-7.86 19.028 0 7.212 2.796 13.968 7.86 19.04l16.124 16.116c5.068 5.068 11.824 7.86 19.032 7.86s13.968-2.792 19.032-7.86L382.678 265c5.076-5.084 7.864-11.872 7.848-19.088.016-7.244-2.772-14.028-7.848-19.108z" fill="#ffffff" opacity="1" data-original="#000000" class=""></path></g></svg>
                                </span>
                                <span>Concerts</span>
                                
                            </div>

                            <ul>
                                <li>
                                    <h3 class="sub-menu-heading">Popular</h3>
                                </li>
                                <li><a href="/category/rap-hip-hop-1906">Rap / Hip Hop</a></li>
                                <li><a href="/category/country-folk-1873">Country / Folk</a></li>
                                <li><a href="/category/latin-1890">Latin</a></li>
                                <li><a href="/category/alternative-1862">Alternative</a></li>
                                <!-- second -->
                                <hr class="line">
                                <li>
                                    <h3 class="sub-menu-heading">Discover More</h3>
                                </li>
                                <li><a href="/concerts" class="view-all">All Concerts <i class="bi bi-arrow-right"></i></a></li>
                                <li><a href="/category/50s-60s-era-1860">50s / 60s Era</a></li>
                                <li><a href="/category/alternative-1862">Alternative</a></li>
                                <li><a href="/category/bluegrass-1866">Bluegrass</a></li>
                                <li><a href="/category/children-family-2094">Children / Family</a></li>
                                <li><a href="/category/classical-1871">Classical</a></li>
                                <li><a href="/category/comedy-1872">Comedy</a></li>
                                <li><a href="/category/country-folk-1873">Country / Folk</a></li>                                
                                <li><a href="/category/festival-tour-1877">Festival / Tour</a></li>
                                <li><a href="/category/hard-rock-metal-1882">Hard Rock / Metal</a></li>
                                <li><a href="/category/holiday-1884">Holiday</a></li>
                                <li><a href="/category/jazz-blues-1885">Jazz / Blues</a></li>
                                <li><a href="/category/las-vegas-shows-1888">Las Vegas Shows</a></li>
                                <li><a href="/category/latin-1890">Latin</a></li>
                                <li><a href="/category/new-age-1895">New Age</a></li>
                                <li><a href="/category/other-1900">Other</a></li>
                                <li><a href="/category/performance-series-2062">Performance Series</a></li>
                                <li><a href="/category/pop-rock-1903">Pop / Rock</a></li>
                                <li><a href="/category/rb-soul-1904">R&b / Soul</a></li>
                                <li><a href="/category/rap-hip-hop-1906">Rap / Hip Hop</a></li>
                                <li><a href="/category/reggae-reggaeton-1907">Reggae / Reggaeton</a></li>
                                <li><a href="/category/religious-1908">Religious</a></li>
                                <li><a href="/category/techno-electronic-1915">Techno / Electronic</a></li>
                                <li><a href="/category/world-1918">World</a></li>
                            </ul>
                        </div>

                    </li>
                    <li class="has-submenu">

                        <!-- ✅ ADD class + data-target -->
                        <a href="/sports" class="open-submenu" data-target="submenu-sports">
                        Sports <span class="arrow">
                                <svg xmlns="http://www.w3.org/2000/svg" version="1.1" xmlns:xlink="http://www.w3.org/1999/xlink" width="16" height="16" x="0" y="0" viewBox="0 0 492.004 492.004" style="enable-background:new 0 0 512 512" xml:space="preserve" class=""><g><path d="M382.678 226.804 163.73 7.86C158.666 2.792 151.906 0 144.698 0s-13.968 2.792-19.032 7.86l-16.124 16.12c-10.492 10.504-10.492 27.576 0 38.064L293.398 245.9l-184.06 184.06c-5.064 5.068-7.86 11.824-7.86 19.028 0 7.212 2.796 13.968 7.86 19.04l16.124 16.116c5.068 5.068 11.824 7.86 19.032 7.86s13.968-2.792 19.032-7.86L382.678 265c5.076-5.084 7.864-11.872 7.848-19.088.016-7.244-2.772-14.028-7.848-19.108z" fill="#000000" opacity="1" data-original="#000000" class=""></path></g></svg>
                            </span>
                        </a>
                        
                        <!-- Submenu Panel -->
                        <div class="submenu-panel" id="submenu-sports">
                            <div class="submenu-header">
                                <span class="back-btn me-3">
                                    <svg xmlns="http://www.w3.org/2000/svg" version="1.1" xmlns:xlink="http://www.w3.org/1999/xlink" width="16" height="16" x="0" y="0" viewBox="0 0 492.004 492.004" style="enable-background:new 0 0 512 512" xml:space="preserve" class=""><g transform="matrix(-1,1.2246467991473532e-16,-1.2246467991473532e-16,-1,497.00405883789074,492.0039672851562)"><path d="M382.678 226.804 163.73 7.86C158.666 2.792 151.906 0 144.698 0s-13.968 2.792-19.032 7.86l-16.124 16.12c-10.492 10.504-10.492 27.576 0 38.064L293.398 245.9l-184.06 184.06c-5.064 5.068-7.86 11.824-7.86 19.028 0 7.212 2.796 13.968 7.86 19.04l16.124 16.116c5.068 5.068 11.824 7.86 19.032 7.86s13.968-2.792 19.032-7.86L382.678 265c5.076-5.084 7.864-11.872 7.848-19.088.016-7.244-2.772-14.028-7.848-19.108z" fill="#ffffff" opacity="1" data-original="#000000" class=""></path></g></svg>
                                </span>
                                <span>Sports</span>
                                
                            </div>

                            <ul>
                                <li>
                                    <h3 class="sub-menu-heading">Popular</h3>
                                </li>
                                <li><a href="/category/mlb-1969">MLB</a></li>
                                <li><a href="/category/nba-1971">NBA</a></li>
                                <li><a href="/category/nhl-1972">NHL</a></li>
                                <li><a href="/category/mls-1970">MLS</a></li>
                                <!-- second -->
                                <hr class="line">
                                <li>
                                    <h3 class="sub-menu-heading">Discover More</h3>
                                </li>
                                <li><a href="/sports" class="view-all">All Sports <i class="bi bi-arrow-right"></i></a></li>
                                <li><a href="/category/baseball-1864">Baseball</a></li>
                                <li><a href="/category/basketball-1865">Basketball</a></li>
                                <li><a href="/category/boxing-1867">Boxing</a></li>
                                <li><a href="/category/cricket-1874">Cricket</a></li>
                                <li><a href="/category/football-1879">Football</a></li>
                                <li><a href="/category/golf-1880">Golf</a></li>
                                <li><a href="/category/gymnastics-1881">Gymnastics</a></li>
                                <li><a href="/category/hockey-1883">Hockey</a></li>
                                <li><a href="/category/lacrosse-1886">Lacrosse</a></li>
                                <li><a href="/category/mixed-martial-arts-2027">Mixed Martial Arts</a></li>
                                <li><a href="/category/olympics-1897">Olympics</a></li>
                                <li><a href="/category/other-1901">Other</a></li>
                                <li><a href="/category/racing-1905">Racing</a></li>
                                <li><a href="/category/rodeo-1910">Rodeo</a></li>
                                <li><a href="/category/rugby-1911">Rugby</a></li>
                                <li><a href="/category/skating-1912">Skating</a></li>
                                <li><a href="/category/soccer-1913">Soccer</a></li>
                                <li><a href="/category/softball-2059">Softball</a></li>
                                <li><a href="/category/tennis-1916">Tennis</a></li>
                                <li><a href="/category/volleyball-1917">Volleyball</a></li>
                                <li><a href="/category/wrestling-1919">Wrestling</a></li>
                            </ul>
                        </div>

                    </li>
                    <li class="has-submenu">

                        <!-- ✅ ADD class + data-target -->
                        <a href="/theater" class="open-submenu" data-target="submenu-theater">
                            Theater <span class="arrow">
                                <svg xmlns="http://www.w3.org/2000/svg" version="1.1" xmlns:xlink="http://www.w3.org/1999/xlink" width="16" height="16" x="0" y="0" viewBox="0 0 492.004 492.004" style="enable-background:new 0 0 512 512" xml:space="preserve" class=""><g><path d="M382.678 226.804 163.73 7.86C158.666 2.792 151.906 0 144.698 0s-13.968 2.792-19.032 7.86l-16.124 16.12c-10.492 10.504-10.492 27.576 0 38.064L293.398 245.9l-184.06 184.06c-5.064 5.068-7.86 11.824-7.86 19.028 0 7.212 2.796 13.968 7.86 19.04l16.124 16.116c5.068 5.068 11.824 7.86 19.032 7.86s13.968-2.792 19.032-7.86L382.678 265c5.076-5.084 7.864-11.872 7.848-19.088.016-7.244-2.772-14.028-7.848-19.108z" fill="#000000" opacity="1" data-original="#000000" class=""></path></g></svg>
                            </span>
                        </a>
                        
                        <!-- Submenu Panel -->
                        <div class="submenu-panel" id="submenu-theater">
                            <div class="submenu-header">
                                <span class="back-btn me-3">
                                    <svg xmlns="http://www.w3.org/2000/svg" version="1.1" xmlns:xlink="http://www.w3.org/1999/xlink" width="16" height="16" x="0" y="0" viewBox="0 0 492.004 492.004" style="enable-background:new 0 0 512 512" xml:space="preserve" class=""><g transform="matrix(-1,1.2246467991473532e-16,-1.2246467991473532e-16,-1,497.00405883789074,492.0039672851562)"><path d="M382.678 226.804 163.73 7.86C158.666 2.792 151.906 0 144.698 0s-13.968 2.792-19.032 7.86l-16.124 16.12c-10.492 10.504-10.492 27.576 0 38.064L293.398 245.9l-184.06 184.06c-5.064 5.068-7.86 11.824-7.86 19.028 0 7.212 2.796 13.968 7.86 19.04l16.124 16.116c5.068 5.068 11.824 7.86 19.032 7.86s13.968-2.792 19.032-7.86L382.678 265c5.076-5.084 7.864-11.872 7.848-19.088.016-7.244-2.772-14.028-7.848-19.108z" fill="#ffffff" opacity="1" data-original="#000000" class=""></path></g></svg>
                                </span>
                                <span>Theater</span>
                                
                            </div>

                            <ul>
                                <li>
                                    <h3 class="sub-menu-heading">Popular</h3>
                                </li>
                                <li><a href="/category/broadway-1868">Broadway</a></li>
                                <!-- second -->
                                 <hr class="line">
                                <li>
                                    <h3 class="sub-menu-heading">Discover More</h3>
                                </li>
                                <li><a href="/theater" class="view-all">All Theater <i class="bi bi-arrow-right"></i></a></li>
                                <li><a href="/category/ballet-1863">Ballet</a></li>
                                <li><a href="/category/broadway-1868">Broadway</a></li>
                                <li><a href="/category/children-family-1869">Children / Family</a></li>
                                <li><a href="/category/cirque-du-soleil-2031">Cirque Du Soleil</a></li>
                                <li><a href="/category/dance-1875">Dance</a></li>
                                <li><a href="/category/festival-2065">Festival</a></li>
                                <li><a href="/category/las-vegas-1887">Las Vegas</a></li>
                                <li><a href="/category/musical-play-1894">Musical / Play</a></li>
                                <li><a href="/category/off-broadway-1896">Off-broadway</a></li>
                                <li><a href="/category/opera-1898">Opera</a></li>
                                <li><a href="/category/other-1902">Other</a></li>
                                <li><a href="/category/west-end-2060">West End</a></li>
                            </ul>
                        </div>

                    </li>
                    <li>
                        <a href="/festival" title="Festivals" class="mobile-menu">Festivals</a>
                    </li>
                    <li>
                        <a href="/cities" title="Cities" class="mobile-menu">Cities</a>
                    </li>
                </ul>
                <ul class="mobile-main-menu-new">
                    <li>
                        <a href="/contact" title="Contact Us" class="mobile-menu">Contact Us</a>
                    </li>
                    <li>
                        <a href="tel:+1512-621-8822" title="Call Us (512)-621-8822" class="mobile-menu">Call Us (512)-621-8822</a>
                    </li>                    
                    <li>
                        <a href="/about-us" title="About Us" class="mobile-menu">About Us</a>
                    </li>                    
                    <li>
                        <a href="/faq" title="Faqs" class="mobile-menu">Faqs</a>
                    </li>
                    <li>
                        <a href="/privacy-policy" title="Privacy Policy" class="mobile-menu">Privacy Policy</a>
                    </li>
                    <li>
                        <a href="/terms-and-conditions" title="Terms of Use" class="mobile-menu">Terms of Use</a>
                    </li>
                    <li>
                        <a href="/cookie-policy" title="Cookie Policy" class="mobile-menu">Cookie Policy</a>
                    </li>
                </ul>     
            </nav>
        </div>
    </div>
