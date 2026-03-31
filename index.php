<?php
include 'header.php';
?>


<!-- Top Picks Section -->
<section class="container pt-5">
  <div class="mb-3">
    <div class="location-selector-wrapper d-flex flex-wrap align-items-center justify-content-between">
      <h2 class="fw-bold fs-4 mb-0">Our Top Picks Near </h2>
      <div class="so-right-searchbar">
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
              class="location-input"
              id="cityLocationInput"
              autocomplete="off"
              placeholder="Austin, TX" />
            <button type="button" class="location-input-clear" id="locationClearBtn" aria-label="Clear location">
              ✕
            </button>
            <div id="cityLocationDd" class="cityLocationDd w-100" style="display:none;"></div>
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

  <div class="category-scroll-wrapper">

    <!-- Nav Pills -->
    <ul class="nav category-scroll nav-pills mb-3" id="pills-tab" role="tablist">

      <li class="nav-item">
        <button class="category-pill active" data-bs-toggle="pill" data-bs-target="#concerts" type="button">Concerts</button>
      </li>
      <li class="nav-item">
        <button class="category-pill" data-bs-toggle="pill" data-bs-target="#sports" type="button">Sports</button>
      </li>

      <li class="nav-item">
        <button class="category-pill" data-bs-toggle="pill" data-bs-target="#theatre" type="button">Theatre</button>
      </li>

      <li class="nav-item">
        <button class="category-pill" data-bs-toggle="pill" data-bs-target="#festival" type="button">Festival</button>
      </li>

    </ul>
    </div>

    <div class="tab-content">
      <div class="tab-pane show active" id="concerts">
        <div class="custom-slider new-left-right"><?php renderSkeletonCardsEvents(4); ?></div>
      </div>

      <div class="tab-pane" id="sports">
        <div class="custom-slider new-left-right"></div>
      </div>

      <div class="tab-pane" id="theatre">
        <div class="custom-slider new-left-right"></div>
      </div>

      <div class="tab-pane" id="festival">
        <div class="custom-slider new-left-right"></div>
      </div>
    </div>



</section>

<section class="py-3">
  <div class="container my-5">
    <div class="experience-section">
      <div class="row align-items-center g-4">

        <!-- Left Heading -->
        <div class="col-lg-3">
          <h3 class="fw-bold mb-0 so-experieance">Experience<br class="so-nobrake"> it live.</h3>
        </div>

        <!-- Feature 1 -->
        <div class="col-lg-3 col-md-6">
          <div class="feature-box d-flex align-items-center">
            <i class="bi bi-star-fill text-white feature-icon"></i>
            <div>
              <div class="fw-bold">Rated Great</div>
              <small>24K+ Trustpilot reviews</small>
            </div>
          </div>
        </div>

        <!-- Feature 2 -->
        <div class="col-lg-3 col-md-6">
          <div class="feature-box d-flex align-items-center">
            <i class="bi bi-shield-check text-white feature-icon"></i>
            <div>
              <div class="fw-bold">Over 140 million tickets</div>
              <small>sold since 2001</small>
            </div>
          </div>
        </div>

        <!-- Feature 3 -->
        <div class="col-lg-3 col-md-6">
          <div class="feature-box d-flex align-items-center">
            <i class="bi bi-gift-fill text-white feature-icon"></i>
            <div>
              <div class="fw-bold">Rewarding your loyalty</div>
              <small>with free tickets</small>
            </div>
          </div>
        </div>

      </div>
    </div>
  </div>
</section>


<section class="teams-nearby bg-white py-3 teams-section">
  <div class="container py-5 slider-bg text-white">
    <div class="d-flex justify-content-between align-items-center">
      <h2 class="section__title section__title--center fw-bold fs-4 mb-4 text-black">
        Top Teams
      </h2>
      <div class="slider-arrows"></div>
    </div>
   
    <div class="category-scroll-wrapper">
      <ul class="nav nav-pills mb-3 category-scroll" role="tablist">
        <li class="nav-item" role="presentation">
          <button class="sport-cat active" data-bs-toggle="pill" data-bs-target="#tab-NFL" type="button" data-slug="NFL">NFL</button>
        </li>
        <li class="nav-item" role="presentation">
          <button class="sport-cat" data-bs-toggle="pill" data-bs-target="#tab-NBA" type="button" data-slug="NBA">NBA</button>
        </li>
        <li class="nav-item" role="presentation">
          <button class="sport-cat" data-bs-toggle="pill" data-bs-target="#tab-MLB" type="button" data-slug="MLB">MLB</button>
        </li>
        <li class="nav-item" role="presentation">
          <button class="sport-cat" data-bs-toggle="pill" data-bs-target="#tab-NHL" type="button" data-slug="NHL">NHL</button>
        </li>
        <li class="nav-item" role="presentation">
          <button class="sport-cat" data-bs-toggle="pill" data-bs-target="#tab-MLS" type="button" data-slug="MLS">MLS</button>
        </li>
      </ul>
    </div>
    <div class="tab-content tab-pane show active" id="sportsTabContent">
      <?php echo generateTeamSkeleton(4); ?>
    </div>
  </div>
</section>

<!-- Personalized Picks -->
<?php /*<section class="container py-5">
    <h2 class="fw-bold fs-4 mb-4">Personalized Picks</h2>
    
    <div class="event-slider new-left-right" aria-label="Top picks carousel">
      <a href="#" class="team-link px-3">
        <article class="event-card">
          <div class="event-card__img" style="background-image:url('assets/event-basketball.jpg')">
            
          </div>
          <div class="event-card__body">
            <h3 class="event-card__title venu-name-hide">Rockets at Lakers</h3>
            <div class="mb-1">
             <span class="mb-1 venu-date">Jan 14</span>
             
             <span class="mb-1 venu-name">OVO Hydro</span>
            </div>
            <p class="event-card__price mb-0">from <strong>$124</strong></p>
          </div>
        </article>
      
    </a>
    <a href="#" class="team-link px-3">
      <article class="event-card">
        <div class="event-card__img" style="background-image:url('assets/event-football.jpg')">
          
        </div>
        <div class="event-card__body">
          <h3 class="event-card__title">Thunder at Cavaliers</h3>
          <div class="mb-1">
            <span class="mb-1 venu-date">Jan 14</span>
            
            <span class="mb-1 venu-name">OVO Hydro</span>
           </div>
          <p class="event-card__price mb-0">from <strong>$124</strong></p>
        </div>
      </article>
    </a>
    <a href="#" class="team-link px-3">
      <article class="event-card">
        <div class="event-card__img" style="background-image:url('assets/event-concert.jpg')">
        
        </div>
        <div class="event-card__body">
          <h3 class="event-card__title">Thunder at Cavaliers</h3>
          <div class="mb-1">
            <span class="mb-1 venu-date">Jan 14</span>
      
            <span class="mb-1 venu-name">OVO Hydro</span>
           </div>
          <p class="event-card__price mb-0">from <strong>$124</strong></p>
        </div>
      </article>
      
    </a>
    <a href="#" class="team-link px-3">
      <article class="event-card">
        <div class="event-card__img" style="background-image:url('assets/event-concert.jpg')">
          
        </div>
        <div class="event-card__body">
          <h3 class="event-card__title">Thunder at Cavaliers</h3>
          <div class="mb-1">
            <span class="mb-1 venu-date">Jan 14</span>
           
            <span class="mb-1 venu-name">OVO Hydro</span>
           </div>
          <p class="event-card__price mb-0">from <strong>$124</strong></p>
        </div>
      </article>
    </a>
    <a href="#" class="team-link px-3">
      <article class="event-card">
        <div class="event-card__img" style="background-image:url('assets/event-concert.jpg')">
        
        </div>
        <div class="event-card__body">
          <h3 class="event-card__title">Thunder at Cavaliers</h3>
          <div class="mb-1">
            <span class="mb-1 venu-date">Jan 14</span>
            
            <span class="mb-1 venu-name">OVO Hydro</span>
           </div>
          <p class="event-card__price mb-0">from <strong>$124</strong></p>
        </div>
      </article>
      </a>
      <a href="#" class="team-link px-3">
        <article class="event-card">
          <div class="event-card__img" style="background-image:url('assets/event-concert.jpg')">
       
          </div>
          <div class="event-card__body">
            <h3 class="event-card__title">Thunder at Cavaliers</h3>
            <div class="mb-1">
              <span class="mb-1 venu-date">Jan 14</span>
              
              <span class="mb-1 venu-name">OVO Hydro</span>
             </div>
            <p class="event-card__price mb-0">from <strong>$124</strong></p>
          </div>
        </article>
      </a>
      <a href="#" class="team-link px-3">
        <article class="event-card">
          <div class="event-card__img" style="background-image:url('assets/event-concert.jpg')">
            
          </div>
          <div class="event-card__body">
            <h3 class="event-card__title">Thunder at Cavaliers</h3>
            <div class="mb-1">
              <span class="mb-1 venu-date">Jan 14</span>
            
              <span class="mb-1 venu-name">OVO Hydro</span>
             </div>
            <p class="event-card__price mb-0">from <strong>$124</strong></p>
          </div>
        </article>
      </a>
      <a href="#" class="team-link px-3">
        <article class="event-card">
          <div class="event-card__img" style="background-image:url('assets/event-concert.jpg')">
          
          </div>
          <div class="event-card__body">
            <h3 class="event-card__title">Thunder at Cavaliers</h3>
            <div class="mb-1">
              <span class="mb-1 venu-date">Jan 14</span>
             
              <span class="mb-1 venu-name">OVO Hydro</span>
             </div>
            <p class="event-card__price mb-0">from <strong>$124</strong></p>
          </div>
        </article>
      </a>
    </div>
  </section>*/ ?>

<section class="section categories teams-nearby py-5">
  <div class="container">
    <h2 class="section__title section__title--center fw-bold fs-4 mb-4">
      Browse by Category
    </h2>
    <div class="categories__grid">
      <div class="categories__col">
        <h3 class="categories__heading">Concerts</h3>
        <ul class="categories__list">
          <li><a href="#">Reggae / Reggaeton</a></li>
          <li><a href="#">Religious</a></li>
          <li><a href="#">50s / 60s Era</a></li>
          <li><a href="#">Children / Family</a></li>
          <li><a href="#">New Age</a></li>
          <li><a href="#">Bluegrass</a></li>
          <li><a href="#">Performance Series</a></li>
          <li><a href="#">Holiday</a></li>
        </ul>
      </div>
      <div class="categories__col">
        <h3 class="categories__heading">Sports</h3>
        <ul class="categories__list">
          <li><a href="#">Golf</a></li>
          <li><a href="#">Baseball</a></li>
          <li><a href="#">Olympics</a></li>
          <li><a href="#">Cricket</a></li>
          <li><a href="#">Gymnastics</a></li>
          <li><a href="#">Rugby</a></li>
          <li><a href="#">Tennis</a></li>
          <li><a href="#">Mixed Martial Arts</a></li>
        </ul>
      </div>
      <div class="categories__col">
        <h3 class="categories__heading">Theatre</h3>
        <ul class="categories__list">
          <li><a href="#">Musical / Play</a></li>
          <li><a href="#">Broadway</a></li>
          <li><a href="#">Children / Family</a></li>
          <li><a href="#">Off-broadway</a></li>
          <li><a href="#">Ballet</a></li>
          <li><a href="#">Opera</a></li>
          <li><a href="#">Cirque Du Soleil</a></li>
          <li><a href="#">Dance</a></li>
        </ul>
      </div>
      <div class="categories__col">
        <h3 class="categories__heading">Festivals</h3>
        <ul class="categories__list">
          <li><a href="#">Adult</a></li>
          <li><a href="#">Circus</a></li>
          <li><a href="#">Lecture</a></li>
          <li><a href="#">Taped Program (tv / Radio)</a></li>
          <li><a href="#">Film</a></li>
          <li><a href="#">Museum / Exhibit</a></li>
          <li><a href="#">Magic Shows</a></li>
          <li><a href="#">Fairs / Festivals</a></li>
        </ul>        
      </div>
    </div>
  </div>
</section>

<section class="section categories bg-white teams-nearby py-5" aria-labelledby="cities-heading">
  <div class="container">
    <h2 id="cities-heading" class="section__title section__title--center fw-bold fs-4 mb-4">
      Popular Cities
    </h2>
    <div id="browseCitiesWrapper">
      <div class="row g-3">
          <div class="col-auto">
              <a href="#" class="city-pill">New York, NY</a>
          </div>
          <div class="col-auto">
              <a href="#" class="city-pill">Los Angeles, CA</a>
          </div>
          <div class="col-auto">
              <a href="#" class="city-pill">Chicago, IL</a>
          </div>
          <div class="col-auto">
              <a href="#" class="city-pill">Houston, TX</a>
          </div>
          <div class="col-auto">
              <a href="#" class="city-pill">Phoenix, AZ</a>
          </div>
          <div class="col-auto">
              <a href="#" class="city-pill">Philadelphia, PA</a>
          </div>
          <div class="col-auto">
              <a href="#" class="city-pill">San Antonio, TX</a>
          </div>
          <div class="col-auto">
              <a href="#" class="city-pill">San Diego, CA</a>
          </div>
          <div class="col-auto">
              <a href="#" class="city-pill">Dallas, TX</a>
          </div>
          <div class="col-auto">
              <a href="#" class="city-pill">Jacksonville, FL</a>
          </div>
          <div class="col-auto">
              <a href="#" class="city-pill">Fort Worth, TX</a>
          </div>
          <div class="col-auto">
              <a href="#" class="city-pill">San Jose, CA</a>
          </div>
          <div class="col-auto">
              <a href="#" class="city-pill">Austin, TX</a>
          </div>
          <div class="col-auto">
              <a href="#" class="city-pill">Charlotte, NC</a>
          </div>
          <div class="col-auto">
              <a href="#" class="city-pill">Columbus, OH</a>
          </div>
          <div class="col-auto">
              <a href="#" class="city-pill">Indianapolis, IN</a>
          </div>
      </div>
    </div>
  </div>
</section>

<section class="container new-slider venue-section left-right-btn py-5">
  <h2 class="fw-bold fs-4 mb-4">Top Venues</h2>
  <div class="venue-slider px-4">    
    <?php echo buildVenueSkeleton(4); ?>
  </div>
</section>

<section class="section reasons teams-nearby py-5 mt-3" aria-labelledby="reasons-heading">
  <div class="container">
    <h2 id="reasons-heading" class="section__title section__title--center fw-bold fs-4 mb-5">The Seat Outlet Advantage</h2>
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
      <h2 id="testimonials-heading" class="section__title section__title--center fw-bold fs-4 mb-5">Trusted by Thousands of Fans</h2>
      <div class="testimonials-grid">
        <div class="testimonial-card">


          <p class="testimonial-text">
            I've been using SeatOutlet for concert tickets for over a year now, and I'm always impressed! The <span class="highlight">easy booking process</span> and <span class="highlight">instant ticket delivery</span> make it so convenient. I got front-row tickets to my favorite artist's show last month - the experience was unforgettable!
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
            SeatOutlet saved our corporate event planning! We needed <span class="highlight">50 tickets</span> for a team-building conference, and their bulk booking feature was seamless. The customer support team was incredibly helpful, and we received all tickets instantly via email. Highly recommend for business events!
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
            As someone who attends multiple sports events throughout the season, SeatOutlet has become my go-to platform. The <span class="highlight">seat selection feature</span> is fantastic - I can see exactly where I'll be sitting before purchasing. The prices are competitive, and I've never had any issues with ticket validity!
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
            The mobile app is absolutely brilliant! I booked last-minute tickets to a comedy show while on the train, and the <span class="highlight">QR code entry</span> made everything so smooth. No printing, no hassle - just scan and enjoy. SeatOutlet has revolutionized how I experience live events!
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
            We organized a charity fundraiser and needed to sell tickets online. SeatOutlet's <span class="highlight">event management tools</span> made it incredibly easy. The platform handled everything from ticket sales to attendee check-ins. Our event was a huge success, and we'll definitely use SeatOutlet again!
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
            I was skeptical about buying tickets online, but SeatOutlet proved me wrong! When a show I wanted to see was sold out elsewhere, I found tickets here at a <span class="highlight">fair price</span>. The <span class="highlight">secure payment system</span> and instant confirmation gave me peace of mind. I'm now a loyal customer!
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

<?php include "newsletter.php"; ?>

<section class="partners-section teams-nearby py-5">
  <div class="container">
    <h2 class="mb-4 fw-bold fs-4 mb-5">Partners</h2>

    <div class="row g-3 g-md-4 justify-content-center">

      <div class="col-6 col-md-3">
        <div class="partner-card text-center">
          <img src="<?php echo HOME_URL; ?>/assets/hunt-tickets.webp" class="img-fluid" alt="Hunt Tickets" width="115" height="115">
        </div>
      </div>

      <div class="col-6 col-md-3">
        <div class="partner-card text-center">
          <img src="<?php echo HOME_URL; ?>/assets/lite.webp" class="img-fluid" alt="Lite" width="115" height="115">
        </div>
      </div>

      <div class="col-6 col-md-3">
        <div class="partner-card text-center">
          <img src="<?php echo HOME_URL; ?>/assets/seat-geek.webp" class="img-fluid" alt="Seat Geek" width="115" height="115">
        </div>
      </div>

      <div class="col-6 col-md-3">
        <div class="partner-card text-center">
          <img src="<?php echo HOME_URL; ?>/assets/viralpep.webp" class="img-fluid" alt="Electolit" width="115" height="115">
        </div>
      </div>

      <div class="col-6 col-md-3">
        <div class="partner-card text-center">
          <img src="<?php echo AWS_CDN_URL; ?>images/jimbeam.webp" class="img-fluid" alt="Jimbeam" width="115" height="115">
        </div>
      </div>

      <div class="col-6 col-md-3">
        <div class="partner-card text-center">
          <img src="<?php echo HOME_URL; ?>/assets/gtn.webp" class="img-fluid" alt="Grab Tickets Now" width="115" height="115">
        </div>
      </div>

      <div class="col-6 col-md-3">
        <div class="partner-card text-center">
          <img src="<?php echo HOME_URL; ?>/assets/ticket-scanner.webp" class="img-fluid partner-img" alt="Ticket Scanner" width="150" height="150">
        </div>
      </div>

      <div class="col-6 col-md-3">
        <div class="partner-card text-center">
          <img src="<?php echo AWS_CDN_URL; ?>images/lyft.webp" class="img-fluid" alt="Lyft" width="115" height="115">
        </div>
      </div>

    </div>
  </div>
</section>


<?php include 'footer.php'; ?>

