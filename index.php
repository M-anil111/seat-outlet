<?php
$soHomeHero = true;   // header.php hands the search form back in $soHeaderSearchHtml: the hero below prints it
include 'header.php';
$festivalNames = getTopFestivalPerformers();

// "Browse by Categories": top subcategories by tickets on sale, refreshed by
// cron/home-categories.php. The lists below are only the fallback for a
// missing/empty cache so the section never renders blank (all ids verified
// against the catalog's category tree).
$topCategories = cache_get('top_categories', 7 * 86400);
if (!is_array($topCategories)) {
    $topCategories = [];
}
$fallbackCategories = [
    'concerts' => [
        ['slug' => 'pop-rock-1903', 'name' => 'Pop / Rock'],
        ['slug' => 'comedy-1872', 'name' => 'Comedy'],
        ['slug' => 'country-folk-1873', 'name' => 'Country / Folk'],
        ['slug' => 'alternative-1862', 'name' => 'Alternative'],
        ['slug' => 'classical-1871', 'name' => 'Classical'],
        ['slug' => 'las-vegas-shows-1888', 'name' => 'Las Vegas Shows'],
        ['slug' => 'jazz-blues-1885', 'name' => 'Jazz / Blues'],
        ['slug' => 'rap-hip-hop-1906', 'name' => 'Rap / Hip Hop'],
    ],
    'sports' => [
        ['slug' => 'football-1879', 'name' => 'Football'],
        ['slug' => 'basketball-1865', 'name' => 'Basketball'],
        ['slug' => 'baseball-1864', 'name' => 'Baseball'],
        ['slug' => 'hockey-1883', 'name' => 'Hockey'],
        ['slug' => 'soccer-1913', 'name' => 'Soccer'],
        ['slug' => 'boxing-1867', 'name' => 'Boxing'],
        ['slug' => 'golf-1880', 'name' => 'Golf'],
        ['slug' => 'tennis-1916', 'name' => 'Tennis'],
    ],
    'theater' => [
        ['slug' => 'broadway-1868', 'name' => 'Broadway'],
        ['slug' => 'musical-play-1894', 'name' => 'Musical / Play'],
        ['slug' => 'west-end-2060', 'name' => 'West End'],
        ['slug' => 'las-vegas-1887', 'name' => 'Las Vegas'],
        ['slug' => 'off-broadway-1896', 'name' => 'Off-broadway'],
        ['slug' => 'children-family-1869', 'name' => 'Children / Family'],
        ['slug' => 'ballet-1863', 'name' => 'Ballet'],
        ['slug' => 'opera-1898', 'name' => 'Opera'],
    ],
];
foreach ($fallbackCategories as $key => $list) {
    if (empty($topCategories[$key])) {
        $topCategories[$key] = $list;
    }
}
?>
<section class="so-hero2" aria-label="Find tickets">
  <img class="so-hero2__bg" src="/images/home-slider-1440.webp" srcset="/images/home-slider-640.webp 640w, /images/home-slider-1024.webp 1024w, /images/home-slider-1440.webp 1440w, /images/home-slider.webp 1920w" sizes="100vw" width="1440" height="825" alt="" fetchpriority="high" decoding="async">
  <div class="so-hero2__shade" aria-hidden="true"></div>
  <div class="container so-hero2__inner">
    <p class="so-hero2__pill"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 8a2 2 0 0 0 0 4v0a2 2 0 0 0 0 4v1.5h18V16a2 2 0 0 0 0-4v0a2 2 0 0 0 0-4V6.5H3V8zM14 6.5v11"/></svg>Live events. Better seats.</p>
    <h1 class="so-hero2__title">Experience <span>live events</span>,<br class="d-none d-md-block"> made easy.</h1>
    <p class="so-hero2__sub">Concerts, sports, theater and more. Compare seats and prices from sellers across our marketplace.</p>
    <div class="so-hero2__search"><?php echo $soHeaderSearchHtml ?? ''; ?></div>
    <ul class="so-hero2__perks">
      <li><svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 8a2 2 0 0 0 0 4v0a2 2 0 0 0 0 4v1.5h18V16a2 2 0 0 0 0-4v0a2 2 0 0 0 0-4V6.5H3V8zM14 6.5v11"/></svg><span>Wide selection<br>of seats</span></li>
      <li><svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M14.8 9.2c-.4-.9-1.4-1.4-2.8-1.4-1.6 0-2.7.8-2.7 2 0 3 5.6 1.2 5.6 4.2 0 1.2-1.2 2-2.9 2-1.5 0-2.6-.6-3-1.6M12 6.5v1.3M12 16.2v1.3"/></svg><span>Compare prices<br>across sellers</span></li>
      <li><svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3l7.5 2.8v5.6c0 4.5-3 8.2-7.5 9.6-4.5-1.4-7.5-5.1-7.5-9.6V5.8L12 3z"/><path d="M8.8 12l2.3 2.3 4.2-4.6"/></svg><span>Trusted marketplace<br>and secure checkout</span></li>
      <li><svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 14v-2a8 8 0 0 1 16 0v2"/><rect x="3" y="14" width="4" height="6" rx="1.5"/><rect x="17" y="14" width="4" height="6" rx="1.5"/><path d="M19 20c0 1.2-1.4 2-4 2h-2"/></svg><span>100% Worry-Free<br>Guarantee</span></li>
    </ul>
    <nav class="so-hero2__cats" aria-label="Browse tickets by category">
      <?php foreach ([['Concerts', '/concert-tickets-for-sale', 'concerts'], ['Sports', '/game-day-tickets', 'sports'], ['Theater', '/buy-broadway-tickets', 'theater'],
                      ['Festivals', '/upcoming-music-festivals', 'festivals'], ['Artists &amp; Teams', '/all-artists-and-teams', 'artists'], ['Cities', '/city-events', 'cities']] as [$cl, $ch, $ck]) { ?>
      <a class="so-hero2__cat" href="<?php echo $ch; ?>">
        <img src="/images/home-cat-<?php echo $ck; ?>.webp" alt="" width="480" height="270" loading="lazy" decoding="async">
        <span class="so-hero2__cat-name"><?php echo $cl; ?></span>
        <span class="so-hero2__cat-go" aria-hidden="true"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg></span>
      </a>
      <?php } ?>
    </nav>
  </div>
</section>

<?php echo soAdSlot('home'); ?>

<section class="so-feed so-popweek" id="soFeedPopWeekend" data-so-feed="popweekend" aria-labelledby="soFeedPopWeekendTitle" hidden>
  <div class="container">
    <div class="so-feed__head">
      <div>
        <h2 id="soFeedPopWeekendTitle" class="so-feed__title">Popular this weekend</h2>
      </div>
      <a class="so-feed__all" href="/buy-tickets-online?when=weekend">See all</a>
    </div>
    <div class="so-feed__track" data-so-feed-track></div>
  </div>
</section>

<section class="so-feed" id="soFeedLastMinute" data-so-feed="lastminute" aria-labelledby="soFeedLastMinuteTitle">
  <div class="container">
    <div class="so-feed__head">
      <div>
        <h2 id="soFeedLastMinuteTitle" class="so-feed__title">Last-minute tickets</h2>
        <p class="so-feed__sub" data-so-feed-sub>Events in the next 7 days with tickets listed</p>
      </div>
      <a class="so-feed__all" href="/buy-tickets-online">See all</a>
    </div>
    <div class="so-feed__track" data-so-feed-track><?php for ($i = 0; $i < 4; $i++) { ?><div class="so-feed-card so-feed-card--skeleton" aria-hidden="true"><div class="so-feed-card__img"></div><div class="so-feed-card__line"></div><div class="so-feed-card__line so-feed-card__line--short"></div></div><?php } ?></div>
  </div>
</section>

<section class="so-feed" id="soFeedWeekend" data-so-feed="weekend" aria-labelledby="soFeedWeekendTitle" hidden>
  <div class="container">
    <div class="so-feed__head">
      <div>
        <h2 id="soFeedWeekendTitle" class="so-feed__title">This weekend near you</h2>
        <p class="so-feed__sub" data-so-feed-sub>Events within 50 miles, Friday to Sunday</p>
      </div>
      <a class="so-feed__all" href="/buy-tickets-online?when=weekend">See all</a>
    </div>
    <div class="so-feed__track" data-so-feed-track></div>
  </div>
</section>

<!-- Top Picks Section -->
<section class="container pt-md-5 pt-4">
  <div class="mb-lg-4 mb-3 pb-2">
    <div class="location-selector-wrapper d-flex flex-wrap align-items-center">
      <h2 class="fw-bold fs-4 mb-0" id="topPicksTitle">Top picks across the US</h2>
      <div class="so-right-searchbar ps-md-0 ps-2">
      <button type="button" class="location-selector" id="locationToggleBtn" aria-expanded="false" aria-controls="locationPanel">
        <span class="location-selector-link" id="locationSelectorText">Set location <i class="bi bi-chevron-down" aria-hidden="true"></i></span>
      </button>

      <!-- Location dropdown panel -->
      <div class="location-panel" id="locationPanel">
        <div class="location-panel-header">Change Location</div>

        <div class="location-panel-input-row">
          <div class="location-input-shell">
            <input type="text" class="location-input locationInputField" id="cityLocationInput" autocomplete="off" placeholder="Austin, TX" aria-label="City or zip code" />
            <button type="button" class="location-input-clear" id="locationClearBtn" aria-label="Clear location">&#10005;</button>
            <div id="cityLocationDd" class="cityLocationDd w-100 locationInputFieldWrapper" style="display:none;"></div>
          </div>
        </div>

        <button type="button" class="location-panel-option" id="useCurrentLocationCity">
          <svg class="icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10l9-7 9 7-9 11-9-11z" />
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 10l3 3 3-3" />
          </svg>
          <span>Current location</span>
        </button>
      </div>
      </div>
      <a class="so-picks-all" href="/buy-tickets-online">See all events <span aria-hidden="true">&rsaquo;</span></a>
    </div>
  </div>

  <div class="category-scroll-wrapper mb-2">

    <!-- Nav Pills -->
    <ul class="nav category-scroll nav-pills" id="pills-tab" role="tablist">

      <li class="nav-item">
        <button class="category-pill active" data-bs-toggle="pill" data-bs-target="#concerts" type="button">Concerts</button>
      </li>
      <li class="nav-item">
        <button class="category-pill" data-bs-toggle="pill" data-bs-target="#sports" type="button">Sports</button>
      </li>

      <li class="nav-item">
        <button class="category-pill" data-bs-toggle="pill" data-bs-target="#theatre" type="button">Theater</button>
      </li>

      <li class="nav-item">
        <button class="category-pill" data-bs-toggle="pill" data-bs-target="#festival" type="button">Festival</button>
      </li>

    </ul>
    </div>

    <div class="tab-content top-picks">
      <div class="tab-pane show active" id="concerts">
        <div class="custom-slider new-left-right new-slider "><?php renderSkeletonCardsEvents(4); ?></div>
      </div>

      <div class="tab-pane" id="sports">
        <div class="custom-slider new-left-right new-slider"></div>
      </div>

      <div class="tab-pane" id="theatre">
        <div class="custom-slider new-left-right new-slider"></div>
      </div>

      <div class="tab-pane" id="festival">
        <div class="custom-slider new-left-right new-slider"></div>
      </div>
    </div>



</section>

<section class="so-feed" id="soFeedTrending" data-so-feed="trending" aria-labelledby="soFeedTrendingTitle">
  <div class="container">
    <div class="so-feed__head">
      <div>
        <h2 id="soFeedTrendingTitle" class="so-feed__title">Trending events</h2>
        <p class="so-feed__sub" data-so-feed-sub>What fans are buying right now</p>
      </div>
      <a class="so-feed__all" href="/concert-tickets-for-sale">See all</a>
    </div>
    <div class="so-feed__track" data-so-feed-track><?php for ($i = 0; $i < 4; $i++) { ?><div class="so-feed-card so-feed-card--skeleton" aria-hidden="true"><div class="so-feed-card__img"></div><div class="so-feed-card__line"></div><div class="so-feed-card__line so-feed-card__line--short"></div></div><?php } ?></div>
  </div>
</section>

<section id="recentlyViewed" class="so-feed so-recent d-none" aria-labelledby="recentlyViewedHeading">
  <div class="container">
    <div class="so-feed__head">
      <div>
        <h2 id="recentlyViewedHeading" class="so-feed__title">Pick up where you left off</h2>
        <p class="so-feed__sub">Artists and events you looked at on this device</p>
      </div>
      <button type="button" class="so-feed__all so-recent__clear" data-so-recent-clear>Clear</button>
    </div>
    <div class="so-feed__track" data-so-recent-track></div>
  </div>
</section>

<section class="so-topp" id="topPerformersSection" aria-labelledby="soToppTitle">
  <div class="container">
    <div class="so-topp__head">
      <span class="so-topp__pill"><svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2.8l2.9 6 6.6.8-4.9 4.5 1.3 6.5L12 17.4 6.1 20.6l1.3-6.5L2.5 9.6l6.6-.8L12 2.8z"/></svg>Top performers</span>
      <h2 id="soToppTitle" class="so-topp__title">Who fans are buying right now</h2>
      <p class="so-topp__sub">The acts, teams and shows with the most recent ticket sales on the marketplace.</p>
    </div>
    <div class="so-topp__grid">
      <?php foreach ([
          ['concerts', 'Top Concert Performers', 'Live music. Unforgettable nights.', '/concert-tickets-for-sale', 'View all concerts', 'music'],
          ['sports', 'Top Sports Performers', 'Big games. Bigger moments.', '/game-day-tickets', 'View all sports', 'trophy'],
          ['theater', 'Top Theater Performers', 'Broadway. Classics. Family favorites.', '/buy-broadway-tickets', 'View all theater', 'masks'],
      ] as [$tk, $tt, $tg, $th, $tc, $ti]) { ?>
      <article class="so-topc so-topc--<?php echo $tk; ?>">
        <header class="so-topc__hero">
          <img class="so-topc__bg" src="/images/home-top-<?php echo $tk; ?>.webp" alt="" width="720" height="300" loading="lazy" decoding="async">
          <div class="so-topc__shade" aria-hidden="true"></div>
          <span class="so-topc__icon"><?php echo soCategoryTileIcon($ti); ?></span>
          <h3 class="so-topc__title"><?php echo $tt; ?></h3>
          <span class="so-topc__from" data-so-from="<?php echo $tk; ?>" hidden></span>
          <p class="so-topc__tag"><?php echo $tg; ?></p>
        </header>
        <ul class="so-topc__list" id="<?php echo $tk; ?>-list" aria-label="<?php echo $tt; ?>">
          <?php for ($i = 0; $i < 5; $i++) { ?><li class="so-topc__row so-topc__row--skeleton" aria-hidden="true"><span class="so-topc__avatar"></span><span class="so-topc__name"></span></li><?php } ?>
        </ul>
        <a href="<?php echo $th; ?>" class="so-topc__cta"><?php echo $tc; ?><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 5l7 7-7 7"/></svg></a>
      </article>
      <?php } ?>
    </div>
  </div>
</section>

<?php echo soRenderCategoryTiles(['class' => 'so-cattiles--home', 'intro' => 'Concerts, sports, theater and festivals across the country. Pick a category to compare seats and prices before you buy.']); ?>

<section class="section categories bg-white teams-nearby py-md-5 py-4" aria-labelledby="cities-heading">
  <div class="container">
    <h2 id="cities-heading" class="section__title section__title--center fw-bold fs-4 mb-4">
      Popular Cities
    </h2>
    <div id="browseCitiesWrapper">
      <ul class="so-city-pills list-unstyled">
        <?php
        $soCities = [['new-york-ny-3027', 'New York, NY'], ['los-angeles-ca-2551', 'Los Angeles, CA'], ['chicago-il-915', 'Chicago, IL'], ['houston-tx-2013', 'Houston, TX'],
            ['phoenix-az-3396', 'Phoenix, AZ'], ['philadelphia-pa-3394', 'Philadelphia, PA'], ['san-antonio-tx-3846', 'San Antonio, TX'], ['san-diego-ca-3854', 'San Diego, CA'],
            ['dallas-tx-1121', 'Dallas, TX'], ['jacksonville-fl-2108', 'Jacksonville, FL'], ['fort-worth-tx-1558', 'Fort Worth, TX'], ['san-jose-ca-3862', 'San Jose, CA'],
            ['austin-tx-247', 'Austin, TX'], ['charlotte-nc-880', 'Charlotte, NC'], ['columbus-oh-1025', 'Columbus, OH'], ['indianapolis-in-2061', 'Indianapolis, IN']];
        foreach ($soCities as $ci => [$soCitySlug, $soCityName]) { ?>
        <li<?php echo $ci >= 8 ? ' class="so-city-extra"' : ''; ?>><a href="/city/<?php echo htmlspecialchars($soCitySlug, ENT_QUOTES, 'UTF-8'); ?>" class="city-pill"><?php echo htmlspecialchars($soCityName, ENT_QUOTES, 'UTF-8'); ?></a></li>
        <?php } ?>
        <li><a href="/city-events" class="city-pill city-pill--all">All cities &rsaquo;</a></li>
      </ul>
    </div>
  </div>
</section>

<section class="container new-slider venue-section left-right-btn py-md-5 py-4">
  <h2 class="fw-bold fs-4 mb-4">Top Venues</h2>
  <div class="venue-slider">    
    <?php echo buildVenueSkeleton(4); ?>
  </div>
</section>

<section class="so-trust" aria-labelledby="soTrustTitle">
  <div class="container">
    <div class="so-trust__head">
      <h2 id="soTrustTitle" class="so-trust__title">Why buy on Seat Outlet</h2>
      <a href="/buy-tickets-online" class="btn common-btn so-trust__cta">Browse events</a>
    </div>
    <ul class="so-trust__grid">
      <li><i class="bi bi-currency-exchange" aria-hidden="true"></i><div><strong>Compare seats and prices</strong><span>See listings from many sellers side by side before you buy.</span></div></li>
      <li><i class="bi bi-shield-check" aria-hidden="true"></i><div><strong>Worry-free guarantee</strong><span>Every order is backed by our guarantee. <a href="/worry-free-guarantee">Read the terms</a> before you buy.</span></div></li>
      <li><i class="bi bi-calendar-event" aria-hidden="true"></i><div><strong>Live events in one place</strong><span>Concerts, sports, theater and festivals across the country.</span></div></li>
      <li><i class="bi bi-telephone" aria-hidden="true"></i><div><strong>Real people to help</strong><span>Reach our team by phone or email if plans change. <a href="/ticket-customer-service">Contact us</a></span></div></li>
    </ul>
    <p class="so-trust__note">Seat Outlet is a resale marketplace: sellers set the prices, which can be above or below face value. Review the full price, including any fees and taxes, before you pay.</p>
  </div>
</section>

<section class="so-home-lead" aria-label="Ticket alerts">
  <div class="container">
    <?php echo soLeadForm([
        'source' => 'home', 'names' => false, 'id' => 'homeAlerts', 'class' => 'so-nl--home',
        'title' => 'Get alerts for tours and on-sales',
        'text' => 'Join the Seat Outlet list for tour announcements, on-sale news and ticket tips, sent to your inbox. Free, and you can unsubscribe any time.',
        'button' => 'Get alerts',
    ]); ?>
  </div>
</section>

<section class="partners-section teams-nearby py-5">
  <div class="container">
    <h2 class="mb-4 fw-bold fs-4 mb-md-5 mb-4">Partners</h2>

    <div class="row g-3 g-md-4 justify-content-md-center partners-grid">

    <div class="col-6 col-md-3">
  <div class="partner-card text-center">
    <a href="https://www.hunttickets.us/" target="_blank" rel="noopener">
      <img src="/images/hunt-tickets.webp" class="img-fluid" alt="Hunt Tickets" width="115" height="115" loading="lazy">
    </a>
  </div>
</div>

<div class="col-6 col-md-3">
  <div class="partner-card text-center">
    <a href="https://www.millerlite.com/" target="_blank" rel="noopener">
      <img src="/images/lite.webp" class="img-fluid" alt="Lite" width="115" height="115" loading="lazy">
    </a>
  </div>
</div>

<div class="col-6 col-md-3">
  <div class="partner-card text-center">
    <a href="https://mindshare.consulting/" target="_blank" rel="noopener">
      <img src="/images/mindshare-logo-230.webp" class="img-fluid partner-network-img" alt="mindshare.consulting" width="115" height="115" loading="lazy">
    </a>
  </div>
</div>

<div class="col-6 col-md-3">
  <div class="partner-card text-center">
    <a href="https://www.viralpep.com/" target="_blank" rel="noopener">
      <img src="/images/viralpep.webp" class="img-fluid" alt="viralpep" width="115" height="115" loading="lazy">
    </a>
  </div>
</div>

<div class="col-6 col-md-3">
  <div class="partner-card text-center">
    <a href="https://www.jimbeam.com/" target="_blank" rel="noopener">
      <img src="/images/jimbeam.webp" class="img-fluid" alt="Jimbeam" width="115" height="115" loading="lazy">
    </a>
  </div>
</div>

<div class="col-6 col-md-3">
  <div class="partner-card text-center">
    <a href="http://grabticketsnow.com/" target="_blank" rel="noopener">
      <img src="/images/gtn.webp" class="img-fluid" alt="Grab Tickets Now" width="115" height="115" loading="lazy">
    </a>
  </div>
</div>

<div class="col-6 col-md-3">
  <div class="partner-card text-center">
    <a href="https://www.ticketscanner.ca/" target="_blank" rel="noopener">
      <img src="/images/ticket-scanner.webp" class="img-fluid partner-img" alt="Ticket Scanner" width="150" height="150" loading="lazy">
    </a>
  </div>
</div>

<div class="col-6 col-md-3">
  <div class="partner-card text-center">
    <a href="https://www.ticketnetwork.com/" target="_blank" rel="noopener">
      <img src="/images/ticketnetwork.webp" class="img-fluid" alt="Ticket Network" width="115" height="115" loading="lazy">
    </a>
  </div>
</div>

    </div>
  </div>
</section>

<?php
// The long text for search engines and curious readers: all of it stays in the HTML, shown clamped behind "Read more" (js/home.js).
ob_start();
soSeoCopy('home');
echo str_replace('<section class="so-seo-copy"', '<section class="so-seo-copy so-readmore" data-so-readmore', ob_get_clean());
?>
<?php include 'footer.php'; ?>
