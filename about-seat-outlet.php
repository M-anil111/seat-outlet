<?php include 'header.php'; ?>

<style>
/* SECTION 1 — HERO */
.about-us-section .hero-section {
  background: radial-gradient(circle at 10% 0%, #2e335a 0, #05060a 45%, #000 100%);
  min-height: 80vh;
  display: flex;
  align-items: center;
}

/* Hero circles */
.about-us-section .hero-visual {
  width: min(420px, 80vw);
  aspect-ratio: 1 / 1;
}

.about-us-section .hero-circle {
  position: absolute;
  border-radius: 999px;
  transition: transform 0.5s ease, box-shadow 0.5s ease;
}

.about-us-section .hero-circle-lg {
  width: 80%;
  height: 80%;
  right: 0;
  top: 10%;
  background: radial-gradient(circle at 20% 20%, #ffffff, #d2d5e0);
}

.about-us-section .hero-circle-sm {
  width: 60%;
  height: 60%;
  left: 0;
  bottom: 0;
  background: radial-gradient(circle at 20% 20%, #4d74ff, var(--primary));
  box-shadow: 0 24px 60px rgba(37, 86, 224, 0.5);
}

.about-us-section .hero-visual:hover .hero-circle-lg {
  transform: translate(-10px, 10px);
}

.about-us-section .hero-visual:hover .hero-circle-sm {
  transform: translate(12px, -10px);
  box-shadow: 0 32px 80px rgba(37, 86, 224, 0.7);
}

/* SECTION 2 — ABOUT */
.about-us-section .about-section {
  background-color: var(--light-bg);
  color: #151623;
  padding: 100px 0;
}

.about-us-section .section-label {
  display: inline-block;
  font-size: 15px;
  letter-spacing: 0.18em;
  text-transform: uppercase;
  color: #2556e0;
  position: relative;
  padding-top: 10px;
  font-weight: bold;
}

.about-us-section .section-label::before {
  content: "";
  position: absolute;
  width: 40px;
  height: 6px;
  background-color: #000000;
  top: 0;
  left: 50%;
  transform: translateX(-50%);
}

.about-us-section .section-title {
  font-size: clamp(2rem, 3vw, 2.6rem);
  font-weight: 600;
  text-transform: uppercase;
}

/* About image */
.about-us-section .about-image-wrapper {
  max-width: 500px;
}

.about-us-section .about-image-placeholder {
  background-color: #05060a;
  border-radius: 0;
  min-height: 420px;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 26px 60px rgba(0, 0, 0, 0.25);
}

.about-us-section .about-logo-initial {
  font-size: 5rem;
  font-weight: 600;
  color: #fff;
}

/* SECTION 3 — INDUSTRY */
.about-us-section .industry-section {
  background: radial-gradient(circle at 0 0, #283060 0, #05060a 55%, #000 100%);
  padding: 100px 0;
}

.about-us-section .industry-head p {
  max-width: 850px;
}

.about-us-section .industry-heading {
  font-size: clamp(2rem, 3.1vw, 2.8rem);
  text-transform: uppercase;
  font-weight: 700;
  letter-spacing: 0.14em;
}

.about-us-section .industry-heading.section-label::before {
  background-color: #fff;
  left: 0;
  transform: unset;
}

/* Slick slider layout */
.about-us-section .industry-slider {
  position: relative;
}

.about-us-section .industry-slide {
  padding: 0 0.75rem;
}

.about-us-section .industry-card {
  position: relative;
  border-radius: 0;
  overflow: hidden;
  min-height: 600px;
  background-position: center;
  background-size: cover;
  background-repeat: no-repeat;
  display: flex !important;
  align-items: flex-end;
  color: #fff;
}

.about-us-section .industry-overlay {
  position: absolute;
  inset: 0;
  background: linear-gradient(to top, rgba(0, 0, 0, 0.85), rgba(0, 0, 0, 0.1));
}

.about-us-section .industry-content {
  transform: translate(-50%, -50%);
  position: absolute;
  top: 70%;
  left: 50%;
  display: inline-block;
  background-color: #000000d4;
  padding: 30px;
  width: 450px;
}

.about-us-section .industry-content {
  width: 450px; /* ← Change to: */
  width: calc(100% - 40px); /* fits any slide width */
  max-width: 450px;          /* keeps your original cap */
}

.about-us-section .industry-year {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 0.2rem 0.7rem;
  border-radius: 999px;
  font-size: 0.75rem;
  letter-spacing: 0.16em;
  text-transform: uppercase;
  background-color: rgba(255, 255, 255, 0.08);
  border: 1px solid rgba(255, 255, 255, 0.16);
  backdrop-filter: blur(12px);
}

.about-us-section .industry-title {
  margin-top: 1rem;
  font-size: 22px;
  font-weight: 600;
}

.about-us-section .industry-text {
  margin-top: 0.5rem;
  color: #d7d8e0;
  font-size: 0.95rem;
}

/* Slick arrows */
.about-us-section .custom-arrow {
  position: absolute;
  top: -60px;
  right: 0;
  width: 44px;
  height: 44px;
  border-radius: 999px;
  border: 1px solid rgba(255, 255, 255, 0.28);
  background: rgba(10, 12, 24, 0.7);
  color: #fff;
  display: inline-flex !important;
  align-items: center;
  justify-content: center;
  transition: all 0.25s ease;
  z-index: 5;
}

.about-us-section .custom-arrow::before {
  display: none;
}

.about-us-section .custom-arrow.slick-prev {
  bottom: -70px;
  top: unset;
  left: 60px;
}

.about-us-section .custom-arrow.slick-next {
  bottom: -70px;
  top: unset;
  right: 50px;
}

.about-us-section .custom-arrow span {
  font-size: 1.3rem;
  transition: transform 0.25s ease;
}

.about-us-section .custom-arrow:hover {
  background-color: var(--primary);
  border-color: var(--primary);
  box-shadow: 0 18px 40px rgba(37, 86, 224, 0.6);
}

.about-us-section .custom-arrow.slick-prev:hover span {
  transform: translateX(-3px);
}

.about-us-section .custom-arrow.slick-next:hover span {
  transform: translateX(3px);
}

.about-us-section .custom-arrow:hover span {
  color: #fff;
}

/* Slick dots */
.about-us-section .slick-dots li button:before {
  color: #777b90;
}

/* SECTION 4 — GLOBAL */
.about-us-section .global-section {
  background-color: #ffffff;
  padding: 100px 0;
}

.about-us-section .global-section .section-title.section-label {
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.10em;
  color: #000;
}

.about-us-section .global-section .section-title.section-label::before {
  left: 0;
  transform: translateX(0);
}

.about-us-section .flag-card {
  display: flex;
  gap: 15px;
  align-items: center;
  background-color: #fff;
  margin-bottom: 15px;
  transition: 0.2s ease;
}

.about-us-section .flag-card svg {
  flex: 0 0 75px;
}

.about-us-section .flag-name {
  font-size: 15px;
  font-weight: 500;
}

/* Scroll reveal */
.about-us-section .scroll-fade {
  opacity: 1;
  transform: translateY(30px);
  transition: opacity 0.6s ease-out, transform 0.6s ease-out;
}

.about-us-section .scroll-fade.in-view {
  opacity: 1;
  transform: translateY(0);
}

.about-us-section .about-us-section .main-title {
  font-size: 70px;
  font-weight: 600;
  color: #fff;
}

.about-us-section .about-content p {
  margin-bottom: 15px;
}

.about-us-section .about-content p:last-child {
  margin-bottom: 0;
}

/* ================= RESPONSIVE ================= */

@media (max-width:1365px) {
  .about-us-section .about-us-section .main-title {
    font-size: 52px;
  }

  .about-us-section .hero-section {
    min-height: auto;
    padding: 80px 0;
  }

  .about-us-section .industry-heading {
    font-size: 40px;
  }

  .about-us-section .industry-head p {
    max-width: 650px;
  }
}

@media (max-width:1199px) {
  .about-us-section .about-us-section .main-title {
    font-size: 46px;
  }

  .about-us-section .hero-section,
  .about-us-section .about-section,
  .about-us-section .industry-section,
  .about-us-section .global-section {
    padding: 80px 0;
  }

  .about-us-section .industry-heading {
    font-size: 32px;
  }
}

@media (max-width:991.98px) {
  .about-us-section .about-us-section .main-title {
    font-size: 42px;
  }

  .about-us-section .industry-heading {
    text-align: left;
    font-size: 28px;
  }

  .about-us-section .custom-arrow {
    top: auto;
    bottom: -70px;
  }

  .about-us-section .hero-section,
  .about-us-section .about-section,
  .about-us-section .industry-section,
  .about-us-section .global-section {
    padding: 60px 0;
  }

  .about-us-section .hero-section .left-content {
    max-width: 750px;
    margin: 0 auto;
    text-align: center;
  }
}

@media (max-width:767.98px) {
  .about-us-section .about-us-section .main-title {
    font-size: 32px;
  }

  .about-us-section .about-image-wrapper {
    margin-inline: auto;
  }

  .about-us-section .industry-card {
    min-height: 480px;
  }

  .about-us-section .flag-card {
    gap: 8px;
  }

  .about-us-section .hero-section,
  .about-us-section .about-section,
  .about-us-section .industry-section,
  .about-us-section .global-section {
    padding: 50px 0;
  }

  .about-us-section .industry-content {
    left: 0;
    transform: translate(0%, -50%);
    max-width: 350px;
    padding: 20px;
  }

  .about-us-section .custom-arrow.slick-prev,
  .about-us-section .custom-arrow.slick-next {
    bottom: -20px;
  }

  .about-us-section .industry-slider {
    padding-bottom: 50px;
  }

  .about-us-section .global-section .section-title.section-label {
    font-size: 26px;
  }

  .about-us-section .flag-card svg {
    flex: 0 0 55px;
  }
}



</style>

<div class="about-us-section">

    <main>
    <!-- SECTION 1 — HERO / OUR STORY -->
    <section id="hero" class="hero-section text-light">
      <div class="container">
        <div class="row align-items-center">
          <!-- Left Column -->
          <div class="col-lg-6 left-content">
            <?php echo getContentBlock('/about-us', 'hero-story', '
            <h1 class="main-title mb-lg-4 mb-3">About Seat Outlet: Our Story</h1>
            <p class="text-white mb-3">
              Seat Outlet is an online resale marketplace for concert, sports, theater and festival tickets.
              Listings, seats and prices come from the TicketNetwork marketplace, and we show them side by
              side so you can compare before you buy.
            </p>
            <p class="text-white mb-4">
              Orders are fulfilled through TicketNetwork and covered by its <a class="text-white" href="/worry-free-guarantee">100% guarantee</a>.
              Seat Outlet is an independent resale marketplace and is not affiliated with any venue, team or artist.
            </p>'); ?>
            <a href="/ticket-partner-program" class="btn-primary btn px-4">
              Work With Us
            </a>
          </div>

          <!-- Right Column -->
          <div class="col-lg-6">
            <div class="hero-visual position-relative mx-auto">
              <div class="hero-circle hero-circle-lg"></div>
              <div class="hero-circle hero-circle-sm"></div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- SECTION 2 — ABOUT US -->
    <section id="about" class="about-section scroll-fade">
      <div class="container">
        <div class="text-center mb-lg-5 mb-4">
          <span class="section-label">About Us</span>
          <h2 class="section-title mt-2">A Resale Marketplace Built for Fans</h2>
        </div>

        <div class="row align-items-center">
          <!-- Left: Image -->
          <div class="col-md-5">
            <div class="about-image-wrapper">
              <img src="/images/crowd-at-concert-or-event.webp" class="img-fluid rounded" alt="Fans cheering at a live event" loading="lazy" width="442" height="442" decoding="async">
            </div>
          </div>

          <!-- Right: Text -->
          <div class="col-md-7 mt-md-0 mt-4 about-content text-md-start text-center ps-lg-5 ps-md-4 ps-0">
            <?php echo getContentBlock('/about-us', 'about-body', '
            <p>
              Seat Outlet helps fans find seats and compare prices for live events. You pick a category, narrow
              down by city or performer, compare sections and prices, and continue to checkout.
            </p>
            <p>
              We are a resale marketplace, so sellers set the prices. A ticket can cost more or less than its face
              value, and the full cost is shown at checkout before you pay.
            </p>
            <p>
              Checkout and fulfillment are handled by TicketNetwork. If something goes wrong with an order, you can
              <a href="/ticket-customer-service" class="text-decoration-underline">contact our support team</a> and we will help you work it out.
            </p>'); ?>
          </div>
        </div>
      </div>
    </section>

    <!-- SECTION 3 — INDUSTRY / TIMELINE SLIDER -->
    <section id="industry" class="industry-section scroll-fade">
      <div class="text-light">
        <div class="industry-head d-flex ms-xxl-5 px-xxl-5 mb-lg-5 mb-4 justify-content-between align-items-center mx-4 flex-wrap">
                    <h2 class="industry-heading section-label text-white">
                    How Seat Outlet<br> works.
                    </h2>
                    <p>
                    Four things to know before you buy a ticket from a resale marketplace.
                    </p>
                </div>
        <div class="d-flex flex-wrap gap-3 px-4 px-xxl-5 mb-4">
          <img src="/images/stage.webp" class="img-fluid rounded" style="max-width:200px;height:auto;" alt="Musicians performing on a stage lit by beams of light" loading="lazy" width="750" height="843" decoding="async">
          <img src="/images/event-concert.jpg" class="img-fluid rounded" style="max-width:200px;height:auto;" alt="Singer on stage under red and blue lights in front of a crowd" loading="lazy" width="800" height="512" decoding="async">
          <img src="/images/crowd-at-concert-or-event.webp" class="img-fluid rounded" style="max-width:200px;height:auto;" alt="Fans cheering at a live event" loading="lazy" width="442" height="442" decoding="async">
        </div>
        <div class="industry-slider">
          <!-- 2022 -->
          <div class="industry-slide">
            <article
              class="industry-card"
              style="background-image: url('/images/about-slide-1.webp');"
            >
              <div class="industry-overlay"></div>
              <div class="industry-content">
                <span class="industry-year">01</span>
                <h3 class="industry-title">Compare</h3>
                <p class="industry-text">
                  Browse by category, city or performer and compare sections and prices for the same event side by side.
                </p>
              </div>
            </article>
          </div>

          <!-- 2023 -->
          <div class="industry-slide">
            <article
              class="industry-card"
              style="background-image: url('/images/about-slide-2.webp');"
            >
              <div class="industry-overlay"></div>
              <div class="industry-content">
                <span class="industry-year">02</span>
                <h3 class="industry-title">Seller-set prices</h3>
                <p class="industry-text">
                  Sellers set resale prices, so a ticket can cost more or less than face value. Review the full cost at checkout before you pay.
                </p>
              </div>
            </article>
          </div>

          <!-- 2024 -->
          <div class="industry-slide">
            <article
              class="industry-card"
              style="background-image: url('/images/about-slide-3.webp');"
            >
              <div class="industry-overlay"></div>
              <div class="industry-content">
                <span class="industry-year">03</span>
                <h3 class="industry-title">Hosted checkout</h3>
                <p class="industry-text">
                  Checkout is hosted by TicketNetwork, which also fulfills the order.
                </p>
              </div>
            </article>
          </div>

          <!-- 2025 -->
          <div class="industry-slide">
            <article
              class="industry-card"
              style="background-image: url('/images/about-slide-4.webp');"
            >
              <div class="industry-overlay"></div>
              <div class="industry-content">
                <span class="industry-year">04</span>
                <h3 class="industry-title">Guarantee</h3>
                <p class="industry-text">
                  Orders are covered by the TicketNetwork 100% guarantee. Read what it covers on the guarantee page.
                </p>
              </div>
            </article>
          </div>
        </div>
      </div>
      <div class="container px-4 px-xxl-5 mt-4">
        <p class="text-white">
          Seat Outlet is not the event organizer or the venue. Organizers and venues control the event itself, and their rules on entry, re-entry and refunds for rescheduled events apply. Read the <a class="text-white" href="/terms-and-conditions">terms and conditions</a> for details.
        </p>
      </div>
    </section>

    <!-- SECTION 4 - FIND EVENTS -->
    <section id="global" class="global-section scroll-fade">
      <div class="container">
        <div class="row mb-lg-4 mb-3">
            <div class="col-lg-5">
                <h2 class="section-title mb-3 mt-0 section-label">Find events near you</h2>
            </div>
            <div class="col-lg-7">
                <p class="text-dark mb-3">
                  Pick a category or a city to see what is on sale right now. Listings change as sellers add and remove tickets.
                </p>
                <p class="mb-0">
                  <a href="/city-events">Events by city</a> &middot;
                  <a href="/concert-tickets-for-sale">Concerts</a> &middot;
                  <a href="/game-day-tickets">Sports</a> &middot;
                  <a href="/buy-broadway-tickets">Theater</a> &middot;
                  <a href="/upcoming-music-festivals">Festivals</a>
                </p>
            </div>
        </div>
      </div>
    </section>
  </main>
</div>

<?php soSeoCopy('about-seat-outlet'); ?>
<?php include 'footer.php'; ?>

<!-- jQuery FIRST -->



<script>
    // Initialize interactions once DOM is ready (jQuery and slick are deferred scripts: wait for them)
document.addEventListener('DOMContentLoaded', function () {
  // Dynamic year in footer
  const yearSpan = document.getElementById("year");
  if (yearSpan) {
    yearSpan.textContent = new Date().getFullYear();
  }


// ✅ Use jQuery() or $() — not jquery()
jQuery(document).ready(function ($) {
  $('.industry-slider').slick({
    slidesToShow: 3,
    slidesToScroll: 1,
    autoplay: true,
    autoplaySpeed: 4000,
    speed: 500,
    arrows: true,
    dots: false,
    pauseOnHover: true,
    cssEase: "ease-out",
    nextArrow: '<button type="button" class="slick-next custom-arrow"><span>&rarr;</span></button>',
    prevArrow: '<button type="button" class="slick-prev custom-arrow"><span>&larr;</span></button>',
    responsive: [
      { breakpoint: 1365, settings: { slidesToShow: 2.5 } },
      { breakpoint: 1199, settings: { slidesToShow: 2 } },
      { breakpoint: 991, settings: { slidesToShow: 1.5 } },
      { breakpoint: 768, settings: { slidesToShow: 1 } }
    ]
  });
});
  // Scroll-triggered fade-in animations
  const revealElements = document.querySelectorAll(".scroll-fade");

  if ("IntersectionObserver" in window) {
    const observer = new IntersectionObserver(
      (entries, obs) => {
        entries.forEach((entry) => {
          if (entry.isIntersecting) {
            entry.target.classList.add("in-view");
            obs.unobserve(entry.target);
          }
        });
      },
      { threshold: 0.18 }
    );

    revealElements.forEach((el) => observer.observe(el));
  } else {
    // Fallback for older browsers: simple on-scroll check
    const onScroll = () => {
      const triggerBottom = window.innerHeight * 0.82;
      revealElements.forEach((el) => {
        const rect = el.getBoundingClientRect();
        if (rect.top < triggerBottom) {
          el.classList.add("in-view");
        }
      });
    };

    window.addEventListener("scroll", onScroll);
    onScroll();
  }
});
</script>