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
        <p>From sold-out concerts to must-see sports and theater shows discover verified tickets at competitive prices across our trusted ticket marketplace network.</p>
        <a href="/tickets" class="btn-slide">Explore Events</a>
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
      <div class="row align-items-center g-xxl-4 g-xl-3 g-lg-2 g-3">

        <!-- Left Heading -->
        <div class="col-lg-12">
          <h3 class="fw-bold mb-lg-0 mb-3 so-experieance">Your Ticket Marketplace for Live Events, with Confidence</h3>
        </div>
        <div class="row g-3">
        <!-- Feature 1 -->
        <div class="col-lg-4 col-md-4 col-sm-6">
          <div class="feature-box d-flex">
            <i class="bi bi-star-fill text-white feature-icon"></i>
            <div>
              <div class="fw-bold">Rated Excellent by Fans</div>
              <small>24K+ verified reviews from real ticket buyers</small>
            </div>
          </div>
        </div>

        <!-- Feature 2 -->
        <div class="col-lg-4 col-md-4 col-sm-6">
          <div class="feature-box d-flex">
            <i class="bi bi-shield-check text-white feature-icon"></i>
            <div>
              <div class="fw-bold">Millions of Tickets Sold</div>
              <small>Trusted ticket marketplace connecting fans since day one</small>
            </div>
          </div>
        </div>

        <!-- Feature 3 -->
        <div class="col-lg-4 col-md-4 col-sm-6">
          <div class="feature-box d-flex">
            <i class="bi bi-gift-fill text-white feature-icon"></i>
            <div>
              <div class="fw-bold">Exclusive Deals & Rewards</div>
              <small>Save more with special offers on event tickets</small>
            </div>
          </div>
        </div>
      </div>

      </div>
    </div>
  </div>
</section>

<section id="recentlyViewed" class="recently-viewed bg-white d-none" aria-labelledby="recentlyViewedHeading">
  <div class="container">
    <h2 id="recentlyViewedHeading" class="fw-bold mb-3">Pick up where you left off</h2>
    <div class="row g-3 recent-row"></div>
  </div>
</section>

<section class="section top_performers bg-white categories teams-nearby py-5" id="topPerformersSection">
  <div class="container">
    <div class="categories__grid">

      <div class="categories__col">
        <h3 class="categories__heading">Top Concert Performers</h3>
        <ul class="categories__list" id="concerts-list"></ul>
        <a href="/concerts" class="common-btn">View All Concerts</a>
      </div>

      <div class="categories__col">
        <h3 class="categories__heading">Top Sports Performers</h3>
        <ul class="categories__list" id="sports-list"></ul>
        <a href="/sports" class="common-btn">View All Sports</a>
      </div>

      <div class="categories__col">
        <h3 class="categories__heading">Top Theater Performers</h3>
        <ul class="categories__list" id="theater-list"></ul>
        <a href="/theater" class="common-btn">View All Theatre</a>
      </div>

    </div>
  </div>
</section>

<section class="section categories teams-nearby py-5">
  <div class="container">
    <h2 class="section__title section__title--center fw-bold fs-4 mb-4">
      Browse by Categories
    </h2>
    <p class="text-center mb-4">
      Our ticket marketplace covers concerts, sports, theater, and festivals across the country, so
      whether you're after front-row seats for a stadium tour or last-minute tickets to a local show,
      there's a category for it below. Every listing on our ticket marketplace is sourced from
      verified sellers, so you can compare pricing and seating options with confidence before you buy.
    </p>
    <div class="categories__grid">
      <div class="categories__col">
        <h3 class="categories__heading">Concerts</h3>
        <ul class="categories__list">
          <?php foreach ($topCategories['concerts'] as $topCat) { ?>
            <li><a href="/category/<?php echo htmlspecialchars($topCat['slug'], ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($topCat['name'], ENT_QUOTES, 'UTF-8'); ?></a></li>
          <?php } ?>
        </ul>
        <a href="/concerts" class="common-btn">View All Concerts</a>
        
      </div>
      <div class="categories__col">
        <h3 class="categories__heading">Sports</h3>
        <ul class="categories__list">
          <?php foreach ($topCategories['sports'] as $topCat) { ?>
            <li><a href="/category/<?php echo htmlspecialchars($topCat['slug'], ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($topCat['name'], ENT_QUOTES, 'UTF-8'); ?></a></li>
          <?php } ?>
        </ul>
        <a href="/sports" class="common-btn">View All Sports</a>
      </div>
      <div class="categories__col">
        <h3 class="categories__heading">Theatre</h3>
        <ul class="categories__list">
          <?php foreach ($topCategories['theater'] as $topCat) { ?>
            <li><a href="/category/<?php echo htmlspecialchars($topCat['slug'], ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($topCat['name'], ENT_QUOTES, 'UTF-8'); ?></a></li>
          <?php } ?>
        </ul>
        <a href="/theater" class="common-btn">View All Theatre</a>
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
        <a href="/festival" class="common-btn">View All Festivals</a>
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
        <h2 class="section-title fs-4">Your Tickets, Confidently Sourced.</h2>
        <div class="section-divider"></div>
        <p class="text-muted">Trusted sources, better prices, zero stress.
        Everything you need for a smooth ticket buying experience.</p>
        <a href="/why-us" class="btn common-btn mt-3">Get Your Tickets</a>
      </div>
      <div class="col-lg-8">
        <div class="row g-4">
          <div class="col-sm-6">
            <div class="feature-card">
              <div class="feature-icon"><i class="bi bi-patch-check-fill"></i></div>
              <h4>Trusted providers</h4>
              <p>Every seller verified before listing a single ticket.</p>
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
              <p>Best deals across platforms, side by side.</p>
            </div>
          </div>
          <div class="col-sm-6">
            <div class="feature-card">
              <div class="feature-icon"><i class="bi bi-calendar-event"></i></div>
              <h4>Wide event selection</h4>
              <p>Sports, concerts, theatre all venues covered.</p>
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
        <h3 class="reason-card__title">Guarantee seat</h3>
        <p class="reason-card__desc">Your tickets are guaranteed, every order is confirmed and fulfilled on time via our secured delivery options.</p>
      </article>
      <article class="reason-card">
        <div class="reason-card__icon">
          <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="var(--clr-primary)" stroke-width="1.5">
            <path d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 002 2 2 2 0 010 4 2 2 0 00-2 2v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 00-2-2 2 2 0 010-4 2 2 0 002-2V7a2 2 0 00-2-2H5z" />
          </svg>
        </div>
        <h3 class="reason-card__title">VIP Ticket</h3>
        <p class="reason-card__desc">VIP Ticket gives the owner to you with premium seating, exclusive access, and phenomenal hospitality at every event.</p>
      </article>
    </div>
  </div>
</section>

<div class="testimonial-page">
  <section class="section-padding">
    <div class="container">
      <h2 id="testimonials-heading" class="section__title section__title--center fw-bold fs-4 mb-lg-5 mb-4">Trusted by Thousands of Fans</h2>
      <!--
          Same finding as testimonials.php/reviews.php: these are
          illustrative sample testimonials, not real collected customer
          feedback. Unlike those two (noindex by default), this is the
          homepage - real visitors and crawlers see this content, so this
          is the highest-priority one of the three to replace with real
          testimonials.
      -->
      <div class="alert alert-warning mb-4" role="alert">
          <strong>Note:</strong> The testimonials below are illustrative examples while we build out real customer review collection.
      </div>
      <div class="testimonials-grid">
        <div class="testimonial-card">


          <p class="testimonial-text">
            <strong>Super easy and reliable experience</strong> I’ve used Seat Outlet multiple times for concert tickets, and the process is always smooth. I was able to compare prices and find the best deal quickly. <span class="highlight">Highly recommend!</span>
          </p>
          <div class="rating">★★★★★</div>
          <div class="testimonial-author">
            <div class="author-avatar">SM</div>
            <div class="author-info">
              <h3>Sarah Mitchell</h3>
              <p>Music Enthusiast</p>
            </div>
          </div>
        </div>

        <div class="testimonial-card">


          <p class="testimonial-text">
            <strong>Best place to compare ticket prices</strong> What I love most is being able to see different ticket options in one place. It saved me both time and money. Definitely my go-to ticket marketplace now.
          </p>
          <div class="rating">★★★★★</div>
          <div class="testimonial-author">
            <div class="author-avatar">JD</div>
            <div class="author-info">
              <h3>James Davidson</h3>
              <p>Event Coordinator, TechCorp</p>
            </div>
          </div>
        </div>

        <div class="testimonial-card">


          <p class="testimonial-text">
            <strong>Got great seats at a great price</strong>  I was looking for last-minute tickets and Seat Outlet helped me find amazing seats without overpaying. The checkout process was simple and secure.
          </p>
          <div class="rating">★★★★★</div>
          <div class="testimonial-author">
            <div class="author-avatar">EL</div>
            <div class="author-info">
              <h3>Emily Lopez</h3>
              <p>Sports Fan</p>
            </div>
          </div>
        </div>

        <div class="testimonial-card">


          <p class="testimonial-text">
            <strong>Perfect for sports fans like me</strong> I regularly attend games, and this platform makes it easy to find tickets across different sellers. The price comparison feature is a big plus.
          </p>
          <div class="rating">★★★★★</div>
          <div class="testimonial-author">
            <div class="author-avatar">MR</div>
            <div class="author-info">
              <h3>Michael Rodriguez</h3>
              <p>Frequent Event Goer</p>
            </div>
          </div>
        </div>

        <div class="testimonial-card">


          <p class="testimonial-text">
            <strong>Trusted and convenient ticket platform</strong> Everything from browsing to booking felt safe and straightforward. I like that it connects to trusted ticket providers instead of just one source.
          </p>
          <div class="rating">★★★★★</div>
          <div class="testimonial-author">
            <div class="author-avatar">AW</div>
            <div class="author-info">
              <h3>Amanda Wilson</h3>
              <p>Non-Profit Director</p>
            </div>
          </div>
        </div>

        <div class="testimonial-card">
           <p class="testimonial-text">
            <strong>Great experience for sports events</strong> As a regular sports fan, I use this platform often. It’s easy to find tickets across different sellers, and the pricing is very competitive.
          </p>
          <div class="rating">★★★★★</div>
          <div class="testimonial-author">
            <div class="author-avatar">RT</div>
            <div class="author-info">
              <h3>Robert Thompson</h3>
              <p>Theater Enthusiast</p>
            </div>
          </div>
        </div>
      </div>

    </div>
  </section>
</div>

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

<?php include 'footer.php'; ?>
