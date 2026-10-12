<?php include 'header.php'; ?>

<style>
/* SECTION 1 — HERO: light banner, copy left, photo right */
.about-us-section #hero.hero-section {
  position: relative;
  overflow: hidden;
  background: #f6f8fc !important;   /* style.css gives this hero a centred grey look with !important */
  color: #0f1b3d;
  min-height: 0 !important;
  padding: 72px 0 !important;
  text-align: left !important;
}
.about-us-section #hero.hero-section .left-content {
  flex: 0 0 auto;
  max-width: none;
  margin: 0;
}
.about-us-section #hero.hero-section::before,
.about-us-section #hero.hero-section::after {
  content: "";
  position: absolute;
  z-index: 0;
  border-radius: 50%;
  pointer-events: none;
}
.about-us-section #hero.hero-section::before {
  width: 62%;
  aspect-ratio: 1;
  right: -14%;
  top: -38%;
  background: radial-gradient(circle at 40% 40%, #dfe8ff 0, #e9efff 55%, rgba(233, 239, 255, 0) 72%);
}
.about-us-section #hero.hero-section::after {
  width: 34%;
  aspect-ratio: 1;
  left: -12%;
  bottom: -40%;
  background: radial-gradient(circle, #edf2ff 0, rgba(237, 242, 255, 0) 70%);
}
.about-us-section #hero.hero-section > .container { position: relative; z-index: 1; }
.about-us-section #hero.hero-section .main-title {
  font-size: clamp(30px, 3.4vw, 44px) !important;
  font-weight: 800;
  line-height: 1.15 !important;
  letter-spacing: -.02em;
  color: #0f1b3d !important;
}
.about-us-section #hero.hero-section .left-content p,
.about-us-section #hero.hero-section .left-content p.text-white {
  max-width: 520px;
  font-size: 16px !important;
  line-height: 1.65 !important;
  color: #3f4a5e !important;
}
.about-us-section #hero.hero-section .left-content p a,
.about-us-section #hero.hero-section .left-content p a.text-white {
  color: #2556e0 !important;
  text-decoration: underline;
  text-underline-offset: 2px;
}
.about-us-section #hero .hero-cta {
  display: inline-flex;
  align-items: center;
  gap: 10px;
  min-height: 48px;
  padding: 0 26px;
  border-radius: 10px;
  font-weight: 700;
  box-shadow: 0 8px 20px rgba(37, 86, 224, .25);
}
.about-us-section #hero .hero-cta svg { transition: transform .2s ease; }
.about-us-section #hero .hero-cta:hover svg { transform: translateX(3px); }
.about-us-section #hero .hero-photo {
  margin: 0;
  border-radius: 18px;
  overflow: hidden;
  box-shadow: 0 20px 45px rgba(15, 27, 61, .18);
}
.about-us-section #hero .hero-photo img {
  display: block;
  width: 100%;
  height: auto;
  aspect-ratio: 16 / 11;
  object-fit: cover;
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

/* SECTION 2 — ABOUT: redesign (photo left, copy right, last paragraph as a highlighted card) */
.about-us-section #about.about-section {
  background: linear-gradient(180deg, #fff 0%, #f8faff 100%);
  color: #0f1b3d;
  padding: 80px 0;
}
.about-us-section #about .section-label {
  padding-top: 0;
  font-size: 13px;
  letter-spacing: .2em;
  color: #2556e0;
}
.about-us-section #about .section-label::before { display: none; }
.about-us-section #about .section-title {
  margin-bottom: 0;
  font-size: clamp(24px, 2.8vw, 34px);
  font-weight: 800;
  letter-spacing: .01em;
  line-height: 1.2;
  color: #0f1b3d;
}
.about-us-section #about .about-image-wrapper {
  max-width: none;
  border-radius: 16px;
  overflow: hidden;
  box-shadow: 0 18px 40px rgba(15, 27, 61, .16);
}
.about-us-section #about .about-image-wrapper img {
  display: block;
  width: 100%;
  height: auto;
  aspect-ratio: 4 / 3;
  object-fit: cover;
  border-radius: 0 !important;
}
.about-us-section #about .about-content { text-align: left !important; }
.about-us-section #about .about-content p {
  margin-bottom: 16px;
  font-size: 15.5px;
  line-height: 1.65;
  color: #3f4a5e;
}
.about-us-section #about .about-content a { color: #2556e0; }
.about-us-section #about .about-content p:last-child {
  position: relative;
  margin: 24px 0 0;
  padding: 20px 22px 20px 66px;
  background: #eef3ff;
  border: 1px solid #dde6fb;
  border-radius: 14px;
  color: #33405a;
}
.about-us-section #about .about-content p:last-child::before {
  content: "";
  position: absolute;
  left: 22px;
  top: 20px;
  width: 28px;
  height: 28px;
  background: #2556e0;
  -webkit-mask: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath d='M2 12.6V4a2 2 0 0 1 2-2h8.6a2 2 0 0 1 1.4.6l8.4 8.4a2 2 0 0 1 0 2.8l-8.6 8.6a2 2 0 0 1-2.8 0L2.6 14A2 2 0 0 1 2 12.6zM7.5 9a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3z'/%3E%3C/svg%3E") center / contain no-repeat;
          mask: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath d='M2 12.6V4a2 2 0 0 1 2-2h8.6a2 2 0 0 1 1.4.6l8.4 8.4a2 2 0 0 1 0 2.8l-8.6 8.6a2 2 0 0 1-2.8 0L2.6 14A2 2 0 0 1 2 12.6zM7.5 9a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3z'/%3E%3C/svg%3E") center / contain no-repeat;
}
@media (max-width: 767.98px) {
  .about-us-section #about.about-section { padding: 50px 0; }
  .about-us-section #about .about-content { padding-left: calc(var(--bs-gutter-x, 1.5rem) * .5) !important; }
  .about-us-section #about .about-content p:last-child { padding: 18px 18px 18px 58px; }
  .about-us-section #about .about-content p:last-child::before { left: 18px; top: 18px; width: 26px; height: 26px; }
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

/* SECTION 4 — FIND EVENTS: dark band with stage photo on the right and outlined link buttons */
.about-us-section #global.global-section {
  position: relative;
  overflow: hidden;
  background: #0a1430;
  color: #fff;
  padding: 64px 0;
}
.about-us-section #global.global-section::before,
.about-us-section #global.global-section::after {
  content: "";
  position: absolute;
  top: 0;
  bottom: 0;
  right: 0;
  pointer-events: none;
}
.about-us-section #global.global-section::before {
  width: 58%;
  background: url("/images/stage.webp") center 72% / cover no-repeat;
}
.about-us-section #global.global-section::after {
  left: 0;
  background: linear-gradient(90deg, #0a1430 0%, #0a1430 42%, rgba(10, 20, 48, .82) 62%, rgba(10, 20, 48, .25) 100%);
}
.about-us-section #global .container { position: relative; z-index: 1; }
.about-us-section #global .so-near__title {
  position: relative;
  margin: 0;
  padding-top: 18px;
  font-size: clamp(24px, 2.6vw, 32px);
  font-weight: 800;
  line-height: 1.2;
  letter-spacing: .08em;
  text-transform: uppercase;
  color: #fff;
}
.about-us-section #global .so-near__title::before {
  content: "";
  position: absolute;
  left: 0;
  top: 0;
  width: 36px;
  height: 3px;
  background: #fff;
}
.about-us-section #global .so-near__text {
  max-width: 520px;
  margin: 0;
  font-size: 15px;
  line-height: 1.6;
  color: #c9d3ea;
}
.about-us-section #global .so-near__links {
  display: flex;
  flex-wrap: wrap;
  gap: 14px;
  margin-top: 30px;
}
.about-us-section #global .so-near__btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  min-height: 46px;
  min-width: 150px;
  padding: 0 22px;
  border: 1.5px solid #2556e0;
  border-radius: 8px;
  background: rgba(37, 86, 224, .12);
  color: #fff;
  font-size: 14px;
  font-weight: 600;
  text-decoration: none;
  transition: background-color .2s ease, border-color .2s ease;
}
.about-us-section #global .so-near__btn svg { flex: none; color: #6f93ff; transition: transform .2s ease, color .2s ease; }
.about-us-section #global .so-near__btn:hover,
.about-us-section #global .so-near__btn:focus-visible { background: #2556e0; border-color: #2556e0; color: #fff; }
.about-us-section #global .so-near__btn:hover svg,
.about-us-section #global .so-near__btn:focus-visible svg { color: #fff; transform: translateX(3px); }
@media (max-width: 991.98px) {
  .about-us-section #global.global-section { padding: 52px 0; }
  .about-us-section #global.global-section::before { width: 100%; opacity: .45; }
  .about-us-section #global.global-section::after { background: linear-gradient(180deg, rgba(10, 20, 48, .92) 0%, rgba(10, 20, 48, .85) 100%); }
  .about-us-section #global .so-near__text { margin-top: 14px; max-width: none; }
}
@media (max-width: 575.98px) {
  .about-us-section #global.global-section { padding: 44px 0; }
  .about-us-section #global .so-near__links { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
  .about-us-section #global .so-near__btn { min-width: 0; padding: 0 14px; }
  .about-us-section #global .so-near__btn:last-child { grid-column: 1 / -1; }
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

  .about-us-section #hero.hero-section { padding: 56px 0 !important; }
  .about-us-section #hero.hero-section .left-content { max-width: 640px; }
  .about-us-section #hero .hero-photo {
    max-width: 640px;
    margin-top: 32px;
  }
}

@media (max-width:767.98px) {
  .about-us-section .about-us-section .main-title {
    font-size: 32px;
  }

  .about-us-section .about-image-wrapper {
    margin-inline: auto;
  }

  .about-us-section #hero.hero-section { padding: 40px 0 !important; }
  .about-us-section #hero .hero-photo { margin-top: 26px; border-radius: 14px; }

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
    <section id="hero" class="hero-section">
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
            <a href="/ticket-partner-program" class="btn-primary btn hero-cta">
              Work With Us
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
            </a>
          </div>

          <!-- Right Column -->
          <div class="col-lg-6">
            <figure class="hero-photo">
              <img src="/images/crowd-at-concert-or-event.webp" alt="Fans cheering at a live concert" width="442" height="442" fetchpriority="high" decoding="async">
            </figure>
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
        <div class="row align-items-center">
            <div class="col-lg-5">
                <h2 class="so-near__title">Find events near you</h2>
            </div>
            <div class="col-lg-7">
                <p class="so-near__text">
                  Pick a category or a city to see what is on sale right now. Listings change as sellers add and remove tickets.
                </p>
            </div>
        </div>
        <nav class="so-near__links" aria-label="Browse events">
          <a class="so-near__btn" href="/city-events">Events by city <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg></a>
          <a class="so-near__btn" href="/concert-tickets-for-sale">Concerts <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg></a>
          <a class="so-near__btn" href="/game-day-tickets">Sports <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg></a>
          <a class="so-near__btn" href="/buy-broadway-tickets">Theater <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg></a>
          <a class="so-near__btn" href="/upcoming-music-festivals">Festivals <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg></a>
        </nav>
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