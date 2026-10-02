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
        <span class="tag">Live Events</span>
        <h1>Experience Live Events<br>Like Never Before</h1>
        <p>Buy event tickets for sold-out concerts, must-see sports and theater shows. Compare seats and prices from sellers across our ticket marketplace network.</p>
        <a href="/buy-tickets-online" class="btn-slide">Explore Events</a>
      </div>
    </div>



  </div><!-- /.hero-slider -->
</section>

<!-- Top Picks Section -->
<section class="container pt-md-5 pt-4">
  <div class="mb-lg-4 mb-3 pb-2">
    <div class="location-selector-wrapper d-flex flex-wrap align-items-center">
      <h2 class="fw-bold fs-4 mb-0">Our Top Picks Near </h2>
      <div class="so-right-searchbar ps-md-0 ps-2">
      <button type="button" class="location-selector" id="locationToggleBtn">
        <span class="location-selector-link" id="locationSelectorText">Select your location <i class="bi bi-chevron-down"></i></span>
      </button>

      <!-- Location dropdown panel -->
      <div class="location-panel" id="locationPanel">
        <div class="location-panel-header">Change Location</div>

        <div class="location-panel-input-row">
          <div class="location-input-shell">
            <input type="text" class="location-input locationInputField" id="cityLocationInput" autocomplete="off" placeholder="Austin, TX" />
            <button type="button" class="location-input-clear" id="locationClearBtn" aria-label="Clear location">✕</button>
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

<section class="py-3">
  <div class="container my-lg-5 my-4">
    <div class="experience-section">
      <h3 class="fw-bold mb-lg-4 mb-3 so-experieance">Your Ticket Marketplace for Live Events, with Confidence</h3>
      <?php
      $soPanelItems = [
        ['bi-currency-exchange', 'Compare seats and prices', 'See listings from many sellers side by side before you buy.'],
        ['bi-shield-check', 'Worry-free guarantee', 'Every order is backed by our guarantee. Read the terms before you buy.'],
        ['bi-calendar-event', 'Live events in one place', 'Concerts, sports, theater and festivals across the country.'],
        ['bi-telephone-fill', 'Real people to help', 'Reach our team by phone or email if plans change.'],
      ];
      ?>
      <div class="so-marquee" role="region" aria-label="Why shop on Seat Outlet">
        <div class="so-marquee__track">
          <?php foreach ([false, true] as $soDup) { foreach ($soPanelItems as $soItem) { ?>
          <div class="feature-box d-flex"<?php echo $soDup ? ' aria-hidden="true"' : ''; ?>>
            <i class="bi <?php echo $soItem[0]; ?> text-white feature-icon" aria-hidden="true"></i>
            <div>
              <div class="fw-bold"><?php echo $soItem[1]; ?></div>
              <small><?php echo $soItem[2]; ?></small>
            </div>
          </div>
          <?php } } ?>
        </div>
      </div>
    </div>
  </div>
</section>

<section id="recentlyViewed" class="recently-viewed bg-white d-none" aria-labelledby="recentlyViewedHeading">
  <div class="container">
    <h2 id="recentlyViewedHeading" class="fw-bold mb-3">Pick up where you left off</h2>
    <div class="row g-3 recent-row"></div>
    <div class="row g-3 recent-events-row mt-1"></div>
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

<section class="section categories teams-nearby py-5">
  <div class="container so-tabs" data-so-tabs data-so-tab-labels="Concerts|Sports|Theater|Festivals">
    <h2 class="section__title section__title--center fw-bold fs-4 mb-3">
      Browse by Categories
    </h2>
    <p class="text-center mb-4 so-section-intro">
      Concerts, sports, theater and festivals across the country. Pick a category to compare seats and prices before you buy.
    </p>
    <div class="categories__grid">
      <div class="categories__col">
        <h3 class="categories__heading">Concerts</h3>
        <ul class="categories__list">
          <?php foreach ($topCategories['concerts'] as $topCat) { ?>
            <li><a href="/category/<?php echo htmlspecialchars($topCat['slug'], ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($topCat['name'], ENT_QUOTES, 'UTF-8'); ?></a></li>
          <?php } ?>
        </ul>
        <a href="/concert-tickets-for-sale" class="common-btn">View All Concerts</a>
        
      </div>
      <div class="categories__col">
        <h3 class="categories__heading">Sports</h3>
        <ul class="categories__list">
          <?php foreach ($topCategories['sports'] as $topCat) { ?>
            <li><a href="/category/<?php echo htmlspecialchars($topCat['slug'], ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($topCat['name'], ENT_QUOTES, 'UTF-8'); ?></a></li>
          <?php } ?>
        </ul>
        <a href="/game-day-tickets" class="common-btn">View All Sports</a>
      </div>
      <div class="categories__col">
        <h3 class="categories__heading">Theatre</h3>
        <ul class="categories__list">
          <?php foreach ($topCategories['theater'] as $topCat) { ?>
            <li><a href="/category/<?php echo htmlspecialchars($topCat['slug'], ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($topCat['name'], ENT_QUOTES, 'UTF-8'); ?></a></li>
          <?php } ?>
        </ul>
        <a href="/buy-broadway-tickets" class="common-btn">View All Theatre</a>
      </div>
      <div class="categories__col">
        <h3 class="categories__heading">Festivals</h3>
        <?php if(!empty($festivalNames)) { ?>
          <ul class="categories__list">
            <?php foreach($festivalNames as $festivalName) { ?>
              <li><a href="/artist/<?php echo createSlug($festivalName['name'],$festivalName['id']); ?>"><?php echo $festivalName['name']; ?></a></li>
            <?php } ?>
          </ul>   
        <?php } ?>  
        <a href="/upcoming-music-festivals" class="common-btn">View All Festivals</a>
      </div>
    </div>
  </div>
</section>

<section class="section categories bg-white teams-nearby py-md-5 py-4" aria-labelledby="cities-heading">
  <div class="container">
    <h2 id="cities-heading" class="section__title section__title--center fw-bold fs-4 mb-4">
      Popular Cities
    </h2>
    <div id="browseCitiesWrapper">
      <div class="row g-3 cities-row">
          <div class="col-auto">
              <a href="/city/new-york-ny-3027" class="city-pill">New York, NY</a>
          </div>
          <div class="col-auto">
              <a href="/city/los-angeles-ca-2551" class="city-pill">Los Angeles, CA</a>
          </div>
          <div class="col-auto">
              <a href="/city/chicago-il-915" class="city-pill">Chicago, IL</a>
          </div>
          <div class="col-auto">
              <a href="/city/houston-tx-2013" class="city-pill">Houston, TX</a>
          </div>
          <div class="col-auto">
              <a href="/city/phoenix-az-3396" class="city-pill">Phoenix, AZ</a>
          </div>
          <div class="col-auto">
              <a href="/city/philadelphia-pa-3394" class="city-pill">Philadelphia, PA</a>
          </div>
          <div class="col-auto">
              <a href="/city/san-antonio-tx-3846" class="city-pill">San Antonio, TX</a>
          </div>
          <div class="col-auto">
              <a href="/city/san-diego-ca-3854" class="city-pill">San Diego, CA</a>
          </div>
          <div class="col-auto">
              <a href="/city/dallas-tx-1121" class="city-pill">Dallas, TX</a>
          </div>
          <div class="col-auto">
              <a href="/city/jacksonville-fl-2108" class="city-pill">Jacksonville, FL</a>
          </div>
          <div class="col-auto">
              <a href="/city/fort-worth-tx-1558" class="city-pill">Fort Worth, TX</a>
          </div>
          <div class="col-auto">
              <a href="/city/san-jose-ca-3862" class="city-pill">San Jose, CA</a>
          </div>
          <div class="col-auto">
              <a href="/city/austin-tx-247" class="city-pill">Austin, TX</a>
          </div>
          <div class="col-auto">
              <a href="/city/charlotte-nc-880" class="city-pill">Charlotte, NC</a>
          </div>
          <div class="col-auto">
              <a href="/city/columbus-oh-1025" class="city-pill">Columbus, OH</a>
          </div>
          <div class="col-auto">
              <a href="/city/indianapolis-in-2061" class="city-pill">Indianapolis, IN</a>
          </div>
      </div>
    </div>
  </div>
</section>

<section class="container new-slider venue-section left-right-btn py-md-5 py-4">
  <h2 class="fw-bold fs-4 mb-4">Top Venues</h2>
  <div class="venue-slider">    
    <?php echo buildVenueSkeleton(4); ?>
  </div>
</section>

<section id="policies" class="py-5" style="background-color: #fff;">
  <div class="container">
    <div class="row g-5 align-items-start">
      <div class="col-lg-4">
        <span class="section-label">Why Choose Seat Outlet</span>
        <h2 class="section-title fs-4">Your Tickets, Confidently Bought.</h2>
        <div class="section-divider"></div>
        <p class="text-muted">Compare prices, check out securely and shop with our worry-free guarantee.
        Everything you need for a smooth ticket buying experience.</p>
        <a href="/ticket-partner-program" class="btn common-btn mt-3">Get Your Tickets</a>
      </div>
      <div class="col-lg-8">
        <div class="row g-4">
          <div class="col-sm-6">
            <div class="feature-card">
              <div class="feature-icon"><i class="bi bi-patch-check-fill"></i></div>
              <h4>Trusted providers</h4>
              <p>Listings come from our network of ticket partners.</p>
            </div>
          </div>
          <div class="col-sm-6">
            <div class="feature-card">
              <div class="feature-icon"> <i class="bi bi-shield-lock-fill"></i></div>
              <h4>Secure checkout</h4>
              <p>Reliable payment options, no hidden surprises.</p>
            </div>
          </div>
          <div class="col-sm-6">
            <div class="feature-card">
              <div class="feature-icon"><i class="bi bi-currency-exchange"></i></div>
              <h4>Easy price compare</h4>
              <p>Compare prices and seats side by side.</p>
            </div>
          </div>
          <div class="col-sm-6">
            <div class="feature-card">
              <div class="feature-icon"><i class="bi bi-calendar-event"></i></div>
              <h4>Wide event selection</h4>
              <p>Sports, concerts, theater and festivals in one place.</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<section class="section reasons teams-nearby py-md-5 py-4 aria-labelledby="reasons-heading">
  <div class="container">
    <h2 id="reasons-heading" class="section__title section__title--center fw-bold fs-4 mb-lg-5 mb-4">The Seat Outlet Advantage</h2>
    <p class="text-center mb-4">Here's why fans choose our ticket marketplace for every concert, game, and show.</p>
    <div class="reasons__grid">
      <article class="reason-card">
        <div class="reason-card__icon">
          <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="var(--clr-primary)" stroke-width="1.5">
            <path d="M12 2v20M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6" />
          </svg>
        </div>
        <h3 class="reason-card__title">No Hidden Fees</h3>
        <p class="reason-card__desc">See the total cost upfront, with no hidden fees added at checkout.</p>
      </article>
      <article class="reason-card">
        <div class="reason-card__icon">
          <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="var(--clr-primary)" stroke-width="1.5">
            <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
        </div>
        <h3 class="reason-card__title">Backed by our guarantee</h3>
        <p class="reason-card__desc">Every order is covered by our worry-free guarantee, with delivery options shown at checkout.</p>
      </article>
      <article class="reason-card">
        <div class="reason-card__icon">
          <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="var(--clr-primary)" stroke-width="1.5">
            <path d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 002 2 2 2 0 010 4 2 2 0 00-2 2v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 00-2-2 2 2 0 010-4 2 2 0 002-2V7a2 2 0 00-2-2H5z" />
          </svg>
        </div>
        <h3 class="reason-card__title">Premium seating</h3>
        <p class="reason-card__desc">Browse premium and VIP listings for select events, where sellers offer them.</p>
      </article>
    </div>
  </div>
</section>

<section class="newsletter-section py-5">
    <div class="container"> 
		<div class="row g-3 justify-content-between">
			<div class="col-lg-4">
				<div class="col-12 d-flex align-items-center gap-3 justify-content-md-center">
					<div class="newsletter-icon">
						<i class="bi bi-send-fill fs-5"></i>
					</div>
					<div class="text-uppercase fw-bold text-white fs-5">
						Newsletter Sign Up!
					</div>
				</div>
			</div>
			<div class="col-lg-8">
				<form method="POST" action="/newsletter-email.php" id="newsletterForm">
          <!-- Honeypot -->
          <input type="text" name="company" value="" style="display:none" autocomplete="off">
          <input type="hidden" name="token" id="recaptchaToken">	
					<div class="newsletter-icontact">						
						<div class="col-12 col-md-4 col-lg-3">
							<input name="fname" type="text" class="form-control newsletter-input" placeholder="First Name" maxlength="70" oninput="this.value = this.value.replace(/\s/g, '')" required />
						</div>
						<div class="col-12 col-md-4 col-lg-3">
							<input name="lname" type="text" class="form-control newsletter-input" placeholder="Last Name" maxlength="70" oninput="this.value = this.value.replace(/\s/g, '')" required />
						</div>
						<div class="col-12 col-md-4 col-lg-3">
							<input name="email" type="email" class="form-control newsletter-input" placeholder="Email" maxlength="70" oninput="this.value = this.value.replace(/\s/g, '')" required />               
						</div>
            <div class="col-12 col-md-4 col-lg-3"> 
							<button type="submit" class="newsletter-btn px-4">Submit</button>
						</div>	            				
					</div>
				</form>
        <div class="text-white mt-3 d-none" id="form_error"></div>
			</div>
		</div>
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

<?php soSeoCopy('home'); ?>
<?php include 'footer.php'; ?>
