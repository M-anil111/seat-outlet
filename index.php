<?php
include 'header.php';
$festivalNames = getTopFestivalPerformers();
?>
<section class="top-hero-slider">


  <div class="hero-slider">

    <!-- Slide 1 – Concert / Event -->
    <div class="slide">
      <img src="<?php echo HOME_URL; ?>/assets/home-slider.webp" alt="Live Concert Event" laoding="eager" fetchpriority="high" />
      <div class="slide-overlay"></div>
      <div class="slide-caption">
        <span class="tag">Live Events</span>
        <h2>Experience Live Events<br>Like Never Before</h2>
        <p>From sold-out concerts to must-see sports and theater shows discover verified tickets at competitive prices across our trusted marketplace network.</p>
        <a href="#" class="btn-slide">Explore Events</a>
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
            <input
              type="text"
              class="location-input locationInputField"
              id="cityLocationInput"
              autocomplete="off"
              placeholder="Austin, TX" />
            <button type="button" class="location-input-clear" id="locationClearBtn" aria-label="Clear location">
              ✕
            </button>
            <div id="cityLocationDd" class="cityLocationDd w-100 locationInputFieldWrapper" style="display:none;"></div>
          </div>
        </div>

        <button type="button" class="location-panel-option" id="useCurrentLocationCity">
          <svg class="icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M3 10l9-7 9 7-9 11-9-11z" />
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M9 10l3 3 3-3" />
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
          <h3 class="fw-bold mb-lg-0 mb-3 so-experieance">Experience Live Events with Confidence</h3>
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
              <small>Trusted marketplace connecting fans since day one</small>
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
    <div class="categories__grid">
      <div class="categories__col">
        <h3 class="categories__heading">Concerts</h3>
        <ul class="categories__list">
          <li><a href="/category/reggae-reggaeton-1907">Reggae / Reggaeton</a></li>
          <li><a href="/category/religious-1908">Religious</a></li>
          <li><a href="/category/50s-60s-era-1860">50s / 60s Era</a></li>
          <li><a href="/category/children-family-2094">Children / Family</a></li>
          <li><a href="/category/new-age-1895">New Age</a></li>
          <li><a href="/category/bluegrass-1866">Bluegrass</a></li>
          <li><a href="/category/performance-series-2062">Performance Series</a></li>
          <li><a href="/category/holiday-1884">Holiday</a></li>
        </ul>
        <a href="#" class="common-btn">View All Concerts</a>
        
      </div>
      <div class="categories__col">
        <h3 class="categories__heading">Sports</h3>
        <ul class="categories__list">
          <li><a href="/category/golf-1880">Golf</a></li>
          <li><a href="/category/baseball-1864">Baseball</a></li>
          <li><a href="/category/olympics-1897">Olympics</a></li>
          <li><a href="/category/cricket-1874">Cricket</a></li>
          <li><a href="/category/gymnastics-1881">Gymnastics</a></li>
          <li><a href="/category/rugby-1911">Rugby</a></li>
          <li><a href="/category/tennis-1916">Tennis</a></li>
          <li><a href="/category/mixed-martial-arts-2027">Mixed Martial Arts</a></li>
        </ul>
        <a href="#" class="common-btn">View All Sports</a>
      </div>
      <div class="categories__col">
        <h3 class="categories__heading">Theatre</h3>
        <ul class="categories__list">
          <li><a href="/category/musical-play-1894">Musical / Play</a></li>
          <li><a href="/category/broadway-1868">Broadway</a></li>
          <li><a href="/category/children-family-1869">Children / Family</a></li>
          <li><a href="/category/off-broadway-1896">Off-broadway</a></li>
          <li><a href="/category/ballet-1863">Ballet</a></li>
          <li><a href="/category/opera-1898">Opera</a></li>
          <li><a href="/category/cirque-du-soleil-2031">Cirque Du Soleil</a></li>
          <li><a href="/category/dance-1875">Dance</a></li>
        </ul>
        <a href="#" class="common-btn">View All Theatre</a>
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
        <a href="#" class="common-btn">View All Festivals</a>   
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
              <a href="/city/new-york-3027" class="city-pill">New York, NY</a>
          </div>
          <div class="col-auto">
              <a href="/city/los-angeles-2551" class="city-pill">Los Angeles, CA</a>
          </div>
          <div class="col-auto">
              <a href="/city/chicago-915" class="city-pill">Chicago, IL</a>
          </div>
          <div class="col-auto">
              <a href="/city/houston-2013" class="city-pill">Houston, TX</a>
          </div>
          <div class="col-auto">
              <a href="/city/phoenix-3396" class="city-pill">Phoenix, AZ</a>
          </div>
          <div class="col-auto">
              <a href="/city/philadelphia-3394" class="city-pill">Philadelphia, PA</a>
          </div>
          <div class="col-auto">
              <a href="/city/san-antonio-3846" class="city-pill">San Antonio, TX</a>
          </div>
          <div class="col-auto">
              <a href="/city/san-diego-3854" class="city-pill">San Diego, CA</a>
          </div>
          <div class="col-auto">
              <a href="/city/dallas-1121" class="city-pill">Dallas, TX</a>
          </div>
          <div class="col-auto">
              <a href="/city/jacksonville-2108" class="city-pill">Jacksonville, FL</a>
          </div>
          <div class="col-auto">
              <a href="/city/fort-worth-1558" class="city-pill">Fort Worth, TX</a>
          </div>
          <div class="col-auto">
              <a href="/city/san-jose-3862" class="city-pill">San Jose, CA</a>
          </div>
          <div class="col-auto">
              <a href="/city/austin-247" class="city-pill">Austin, TX</a>
          </div>
          <div class="col-auto">
              <a href="/city/charlotte-880" class="city-pill">Charlotte, NC</a>
          </div>
          <div class="col-auto">
              <a href="/city/columbus-1025" class="city-pill">Columbus, OH</a>
          </div>
          <div class="col-auto">
              <a href="/city/indianapolis-2061" class="city-pill">Indianapolis, IN</a>
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
        <a href="/why-us.php" class="btn common-btn mt-3">Get Your Tickets</a>
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
    <!-- <p class="section__subtitle text-center">Great seats, amazing prices.</p> -->
    <div class="reasons__grid">
      <article class="reason-card">
        <div class="reason-card__icon">
          <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="var(--clr-primary)" stroke-width="1.5">
            <path d="M12 2v20M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6" />
          </svg>
        </div>
        <h3 class="reason-card__title">No Hidden Fees</h3>
        <p class="reason-card__desc">See the total cost upfront, which means best prices guaranteed for your ticket from the start of a signing or listing.</p>
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
		<div class="row g-3 align-items-center justify-content-between">
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
				<form method="POST" action="/contact-submit.php">
					<div class="newsletter-icontact">						
						<div class="col-12 col-md-4 col-lg-3">
							<input maxlength="50" name="fname" type="text" class="form-control newsletter-input" placeholder="First Name" required />
						</div>
						<div class="col-12 col-md-4 col-lg-3">
							<input maxlength="50" name="lname" type="text" class="form-control newsletter-input" placeholder="Last Name" required />
						</div>
						<div class="col-12 col-md-4 col-lg-3">
							<input maxlength="150" name="email" type="text" class="form-control newsletter-input" placeholder="Email" required />               
						</div>							
						<div class="col-12 col-md-4 col-lg-3"> 
							<button type="submit" class="btn newsletter-btn px-4">Submit</button>
						</div>						
					</div>
				</form>
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
      <img src="<?php echo HOME_URL; ?>/assets/hunt-tickets.webp" class="img-fluid" alt="Hunt Tickets" width="115" height="115" loading="lazy">
    </a>
  </div>
</div>

<div class="col-6 col-md-3">
  <div class="partner-card text-center">
    <a href="https://www.millerlite.com/" target="_blank" rel="noopener">
      <img src="<?php echo HOME_URL; ?>/assets/lite.webp" class="img-fluid" alt="Lite" width="115" height="115" loading="lazy">
    </a>
  </div>
</div>

<div class="col-6 col-md-3">
  <div class="partner-card text-center">
    <a href="https://mindshare.consulting/" target="_blank" rel="noopener">
      <img src="<?php echo HOME_URL; ?>/assets/mindshare-logo.webp" class="img-fluid partner-network-img" alt="mindshare.consulting" width="115" height="115" loading="lazy">
    </a>
  </div>
</div>

<div class="col-6 col-md-3">
  <div class="partner-card text-center">
    <a href="https://www.viralpep.com/" target="_blank" rel="noopener">
      <img src="<?php echo HOME_URL; ?>/assets/viralpep.webp" class="img-fluid" alt="viralpep" width="115" height="115" loading="lazy">
    </a>
  </div>
</div>

<div class="col-6 col-md-3">
  <div class="partner-card text-center">
    <a href="https://www.jimbeam.com/" target="_blank" rel="noopener">
      <img src="<?php echo AWS_CDN_URL; ?>images/jimbeam.webp" class="img-fluid" alt="Jimbeam" width="115" height="115" loading="lazy">
    </a>
  </div>
</div>

<div class="col-6 col-md-3">
  <div class="partner-card text-center">
    <a href="http://grabticketsnow.com/" target="_blank" rel="noopener">
      <img src="<?php echo HOME_URL; ?>/assets/gtn.webp" class="img-fluid" alt="Grab Tickets Now" width="115" height="115" loading="lazy">
    </a>
  </div>
</div>

<div class="col-6 col-md-3">
  <div class="partner-card text-center">
    <a href="https://www.ticketscanner.ca/" target="_blank" rel="noopener">
      <img src="<?php echo HOME_URL; ?>/assets/ticket-scanner.webp" class="img-fluid partner-img" alt="Ticket Scanner" width="150" height="150" loading="lazy">
    </a>
  </div>
</div>

<div class="col-6 col-md-3">
  <div class="partner-card text-center">
    <a href="https://www.ticketnetwork.com/" target="_blank" rel="noopener">
      <img src="<?php echo HOME_URL; ?>/assets/ticketnetwork.webp" class="img-fluid" alt="Ticket Network" width="115" height="115" loading="lazy">
    </a>
  </div>
</div>

    </div>
  </div>
</section>

<?php include 'footer.php'; ?>
