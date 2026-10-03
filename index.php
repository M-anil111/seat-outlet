<?php
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
<section class="top-hero-slider">


  <div class="hero_slider">

    <!-- Slide 1 – Concert / Event -->
    <div class="slide">
      <img src="/images/home-slider-1024.webp" srcset="/images/home-slider-640.webp 640w, /images/home-slider-1024.webp 1024w, /images/home-slider-1440.webp 1440w, /images/home-slider.webp 1920w" sizes="100vw" width="1920" height="1100" alt="Ticket Marketplace - Live Concert Event" loading="eager" fetchpriority="high" />
      <div class="slide-overlay"></div>
      <div class="slide-caption">
        <h1>Live events,<br>made easy.</h1>
        <p>Buy event tickets for sold-out concerts, must-see sports and theater shows. Compare seats and prices from sellers across our ticket marketplace network.</p>
        <div class="so-hero-cta"><a href="/buy-tickets-online" class="btn-slide">Explore events</a><a href="/city-events" class="so-hero-link">Browse by city</a></div>
      </div>
    </div>



  </div><!-- /.hero-slider -->
</section>

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

<section class="section top_performers bg-white categories teams-nearby py-5" id="topPerformersSection">
  <div class="container so-tabs" data-so-tabs data-so-tab-labels="Concerts|Sports|Theater">
    <div class="categories__grid">

      <div class="categories__col">
        <h3 class="categories__heading">Top Concert Performers</h3>
        <ul class="categories__list" id="concerts-list"></ul>
        <a href="/concert-tickets-for-sale" class="common-btn">View All Concerts</a>
      </div>

      <div class="categories__col">
        <h3 class="categories__heading">Top Sports Performers</h3>
        <ul class="categories__list" id="sports-list"></ul>
        <a href="/game-day-tickets" class="common-btn">View All Sports</a>
      </div>

      <div class="categories__col">
        <h3 class="categories__heading">Top Theater Performers</h3>
        <ul class="categories__list" id="theater-list"></ul>
        <a href="/buy-broadway-tickets" class="common-btn">View All Theatre</a>
      </div>

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
