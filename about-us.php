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
            <h1 class="main-title mb-lg-4 mb-3">Our Story</h1>
            <p class="text-white mb-3">
              SeatOutlet is a trusted ticket marketplace created to make live events easier to
              access for fans everywhere. We focus on simplifying ticket discovery while
              delivering a secure and reliable buying experience. Our platform connects fans
              with verified ticket sources so they can enjoy concerts, sports, theatre, and
              live entertainment without stress.
            </p>
            <p class="text-white mb-4">
              We believe unforgettable moments should be easy to reach, which is why SeatOutlet
              continues building smarter tools and trusted partnerships that bring fans closer
              to the events they love - backed by our <a class="text-white" href="/guarantee">100% guarantee</a>.
            </p>'); ?>
            <button type="button" class="btn-primary btn px-4">
              Work With Us
            </button>
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
          <h2 class="section-title mt-2">A Trusted Ticket Marketplace Built for Fans, Built for Live</h2>
        </div>

        <div class="row align-items-center">
          <!-- Left: Image -->
          <div class="col-md-5">
            <div class="about-image-wrapper">
              <img src="/images/team-event.webp" class="img-fluid rounded" alt="The trusted ticket marketplace team at a live event" loading="lazy" width="250" height="250" decoding="async">
            </div>
          </div>

          <!-- Right: Text -->
          <div class="col-md-7 mt-md-0 mt-4 about-content text-md-start text-center ps-lg-5 ps-md-4 ps-0">
            <?php echo getContentBlock('/about-us', 'about-body', '
            <p>
              SeatOutlet began with a simple goal — helping fans find great seats quickly and safely.
              Over time, we have grown into a trusted ticket marketplace that prioritizes transparency,
              customer protection, and convenience.
            </p>
            <p>
              Today, SeatOutlet supports thousands of events across multiple categories, including concerts,
              sports games, festivals, and theatre performances. We combine modern technology with
              customer-first service to make ticket purchasing simple and dependable.
            </p>
            <p>
              As a trusted ticket marketplace, our team focuses on creating intuitive tools that help
              customers compare seating options, understand pricing clearly, and secure tickets
              confidently. We are continuously improving our platform to support fans, event
              organizers, and ticket providers.
            </p>
            <p>
              SeatOutlet is committed to delivering smooth event experiences from search to checkout
              and beyond, in an industry that has grown alongside the modern
              <a href="https://en.wikipedia.org/wiki/Concert" target="_blank" rel="noopener">live concert</a> scene.
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
                    Our industry<br> never stops.<br>
                    Neither do we.
                    </h2>
                    <p >
                    Our industry moves at a fast pace, constantly evolving with new technologies, trends, and customer expectations. We stay ahead by continuously adapting, innovating, and improving our services to deliver reliable and forward-thinking solutions for our clients.
                    </p>
                </div>
        <div class="d-flex flex-wrap gap-3 px-4 px-xxl-5 mb-4">
          <img src="/images/stage.webp" class="img-fluid rounded" style="max-width:200px;" alt="Live stage performance at an event booked through our trusted ticket marketplace" loading="lazy" width="750" height="843" decoding="async">
          <img src="/images/event-concert.jpg" class="img-fluid rounded" style="max-width:200px;" alt="Concert crowd, part of our trusted ticket marketplace network" loading="lazy" width="800" height="512" decoding="async">
          <img src="/images/crowd-at-concert-or-event.webp" class="img-fluid rounded" style="max-width:200px;" alt="Fans at a live event on our trusted ticket marketplace" loading="lazy" width="442" height="442" decoding="async">
        </div>
        <div class="industry-slider">
          <!-- 2022 -->
          <div class="industry-slide">
            <article
              class="industry-card"
              style="background-image: url('https://images.pexels.com/photos/1763075/pexels-photo-1763075.jpeg?auto=compress&cs=tinysrgb&w=1200');"
            >
              <div class="industry-overlay"></div>
              <div class="industry-content">
                <span class="industry-year">2022</span>
                <h3 class="industry-title">Mobile First Experience</h3>
                <p class="industry-text">
                  SeatOutlet introduced a fully optimized mobile platform, allowing customers to
                  browse and purchase tickets easily on any device.
                </p>
              </div>
            </article>
          </div>

          <!-- 2023 -->
          <div class="industry-slide">
            <article
              class="industry-card"
              style="background-image: url('https://images.pexels.com/photos/1047442/pexels-photo-1047442.jpeg?auto=compress&cs=tinysrgb&w=1200');"
            >
              <div class="industry-overlay"></div>
              <div class="industry-content">
                <span class="industry-year">2023</span>
                <h3 class="industry-title">Smart Seat Discovery</h3>
                <p class="industry-text">
                  We launched enhanced filtering and seat comparison tools to help customers find
                  the best view and value for every event.
                </p>
              </div>
            </article>
          </div>

          <!-- 2024 -->
          <div class="industry-slide">
            <article
              class="industry-card"
              style="background-image: url('https://images.pexels.com/photos/1190297/pexels-photo-1190297.jpeg?auto=compress&cs=tinysrgb&w=1200');"
            >
              <div class="industry-overlay"></div>
              <div class="industry-content">
                <span class="industry-year">2024</span>
                <h3 class="industry-title">Secure Checkout Expansion</h3>
                <p class="industry-text">
                  SeatOutlet upgraded payment protection and fraud prevention systems to deliver
                  safer and faster transactions.
                </p>
              </div>
            </article>
          </div>

          <!-- 2025 -->
          <div class="industry-slide">
            <article
              class="industry-card"
              style="background-image: url('https://images.pexels.com/photos/2102568/pexels-photo-2102568.jpeg?auto=compress&cs=tinysrgb&w=1200');"
            >
              <div class="industry-overlay"></div>
              <div class="industry-content">
                <span class="industry-year">2025</span>
                <h3 class="industry-title">Expanded Event Marketplace</h3>
                <p class="industry-text">
                  We expanded partnerships with trusted ticket providers, increasing available
                  events and seating options across North America and beyond.
                </p>
              </div>
            </article>
          </div>
        </div>
      </div>
      <div class="container px-4 px-xxl-5 mt-4">
        <p class="text-white">
          Every improvement we make to this trusted ticket marketplace starts with the same question:
          does this make it easier for a fan to find, compare, and book the tickets they actually want?
          That's why we've focused on the fundamentals - clear pricing with no surprise fees at
          checkout, a seat map that shows exactly what you're buying, and customer support that
          answers real questions instead of routing you through a maze of automated replies. As a
          trusted ticket marketplace, we work directly with venues, promoters, and verified resellers
          so the inventory on our platform reflects real availability, not placeholder listings.
        </p>
      </div>
    </section>

    <!-- SECTION 4 — GLOBAL PRESENCE -->
    <section id="global" class="global-section scroll-fade">
      <div class="container">
        <div class="row mb-lg-5 mb-4">
            <div class="col-lg-5">
                <h2 class="section-title mb-3 mt-0 section-label">We connect fans worldwide</h2>
            </div>
            <div class="col-lg-7">
                <p class="text-dark mb-3">
                  SeatOutlet helps customers access events across major cities and venues around the world.
                  Our growing network ensures fans can discover live entertainment wherever they travel or live.
                  As a trusted ticket marketplace, we continue adding new markets, venues, and event
                  categories so that whether you're planning a trip abroad or looking for something to do
                  close to home, there's a real, verified event waiting for you to book.
                </p>
            </div>
        </div>

        <div class="row g-lg-4 g-3">
          <!-- Keep a representative grid of key markets -->

            <!-- Argentina -->
            <div class="col-6 col-md-3 col-lg-2">
            <div class="flag-card">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 513 342"><path fill="#FFF" d="M0 0h512v342H0z"/><path fill="#338AF3" d="M0 0h512v114H0zM0 228h512v114H0z"/><circle fill="#FFDA44" stroke="#d6ab00" stroke-width="5" cx="256.5" cy="171" r="40"/></svg>
                <span class="flag-name">Argentina</span>
            </div>
            </div>

            <!-- Australia -->
            <div class="col-6 col-md-3 col-lg-2">
            <div class="flag-card">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 513 342"><path fill="#10338c" d="M0 0h513v342H0z"></path><g fill="#FFF"><path d="M222.2 170.7c.3-.3.5-.6.8-.9-.2.3-.5.6-.8.9zM188 212.6l11 22.9 24.7-5.7-11 22.8 19.9 15.8-24.8 5.6.1 25.4-19.9-15.9-19.8 15.9.1-25.4-24.8-5.6 19.9-15.8-11.1-22.8 24.8 5.7zM385.9 241.1l5.2 10.9 11.8-2.7-5.3 10.9 9.5 7.5-11.8 2.6v12.2l-9.4-7.6-9.5 7.6.1-12.2-11.8-2.6 9.5-7.5-5.3-10.9 11.8 2.7zM337.3 125.1l5.2 10.9 11.8-2.7-5.3 10.9 9.5 7.5-11.8 2.7v12.1l-9.4-7.6-9.5 7.6.1-12.1-11.9-2.7 9.5-7.5-5.3-10.9L332 136zM385.9 58.9l5.2 10.9 11.8-2.7-5.3 10.9 9.5 7.5-11.8 2.7v12.1l-9.4-7.6-9.5 7.6.1-12.1-11.8-2.7 9.5-7.5-5.3-10.9 11.8 2.7zM428.4 108.6l5.2 10.9 11.8-2.7-5.3 10.9 9.5 7.5-11.8 2.6V150l-9.4-7.6-9.5 7.6v-12.2l-11.8-2.6 9.5-7.5-5.3-10.9 11.8 2.7zM398 166.5l4.1 12.7h13.3l-10.8 7.8 4.2 12.7-10.8-7.9-10.8 7.9 4.1-12.7-10.7-7.8h13.3z"></path><path d="M254.8 0v30.6l-45.1 25.1h45.1V115h-59.1l59.1 32.8v22.9h-26.7l-73.5-40.9v40.9H99v-48.6l-87.4 48.6H-1.2v-30.6L44 115H-1.2V55.7h59.1L-1.2 22.8V0h26.7L99 40.8V0h55.6v48.6L242.1 0z"></path></g><path fill="#D80027" d="M142.8 0h-32v69.3h-112v32h112v69.4h32v-69.4h112v-32h-112z"></path><path fill="#0052B4" d="m154.6 115 100.2 55.7v-15.8L183 115z"></path><path fill="#FFF" d="m154.6 115 100.2 55.7v-15.8L183 115z"></path><g fill="#D80027"><path d="m154.6 115 100.2 55.7v-15.8L183 115zM70.7 115l-71.9 39.9v15.8L99 115z"></path></g><path fill="#0052B4" d="M99 55.7-1.2 0v15.7l71.9 40z"></path><path fill="#FFF" d="M99 55.7-1.2 0v15.7l71.9 40z"></path><g fill="#D80027"><path d="M99 55.7-1.2 0v15.7l71.9 40zM183 55.7l71.8-40V0L154.6 55.7z"></path></g></svg>
                <span class="flag-name">Australia</span>
            </div>
            </div>

            <!-- Austria -->
            <div class="col-6 col-md-3 col-lg-2">
            <div class="flag-card">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 513 342"><path fill="#FFF" d="M0 114h513v114H0z"/><path fill="#D80027" d="M0 0h513v114H0zM0 228h513v114H0z"/></svg>
                <span class="flag-name">Austria</span>
            </div>
            </div>

            <!-- Belgium -->
            <div class="col-6 col-md-3 col-lg-2">
            <div class="flag-card">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 513 342"><path fill="#fdda25" d="M0 0h513v342H0z"/><path d="M0 0h171v342H0z"/><path fill="#ef3340" d="M342 0h171v342H342z"/></svg>
                <span class="flag-name">Belgium</span>
            </div>
            </div>

            <!-- Brazil -->
            <div class="col-6 col-md-3 col-lg-2">
            <div class="flag-card">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 513 342"><path fill="#009b3a" d="M0 0h513v342H0z"></path><path fill="#fedf00" d="m256.5 19.3 204.9 151.4L256.5 322 50.6 170.7z"></path><circle fill="#FFF" cx="256.5" cy="171" r="80.4"></circle><path fill="#002776" d="M215.9 165.7c-13.9 0-27.4 2.1-40.1 6 .6 43.9 36.3 79.3 80.3 79.3 27.2 0 51.3-13.6 65.8-34.3-24.9-31-63.2-51-106-51zM334.9 186c.9-5 1.5-10.1 1.5-15.4 0-44.4-36-80.4-80.4-80.4-33.1 0-61.5 20.1-73.9 48.6 10.9-2.2 22.1-3.4 33.6-3.4 46.8.1 89 19.5 119.2 50.6z"></path></svg>
                <span class="flag-name">Brazil</span>
            </div>
            </div>

            <!-- Canada -->
            <div class="col-6 col-md-3 col-lg-2">
            <div class="flag-card">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 513 342"><path fill="#FFF" d="M0 0h513v342H0z"></path><g fill="red"><path d="M0 0h142v342H0zM371 0h142v342H371zM306.5 206l50.4-25.2-25.2-12.6V143l-50.4 25.2 25.2-50.4h-25.2L256.1 80l-25.2 37.8h-25.2l25.2 50.4-50.4-25.2v25.2l-25.2 12.6 50.4 25.2-12.6 25.2h50.4V269h25.2v-37.8h50.4z"></path></g></svg>
                <span class="flag-name">Canada</span>
            </div>
            </div>

            <!-- Chile -->
            <div class="col-6 col-md-3 col-lg-2">
            <div class="flag-card">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 513 342"><path fill="#D80027" d="M0 0h513v342H0z"></path><path fill="#FFF" d="M196 0h317v171H196z"></path><path fill="#0037A1" d="M0 0h196v171H0z"></path><path fill="#FFF" d="M98 24.5 113.1 71H162l-39.6 28.7 15.2 46.5L98 117.5l-39.6 28.7 15.2-46.5L34 71h48.9z"></path></svg>
                <span class="flag-name">Chile</span>
            </div>
            </div>

            <!-- Cyprus -->
            <div class="col-6 col-md-3 col-lg-2">
            <div class="flag-card">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 513 342"><path fill="#FFF" d="M0 0h513v342H0z"></path><path fill="#DB7D00" d="M141.7 154.7s.2 67.1 74.7 65.3l4.5 13.9h8.9s-7.4-41.1 60.1-41.5c0 0 0-27.6 27.6-27.6H359s-66-51.8 58.9-118l1.8-13.1s-129.9 71-198.9 57.2c0 0 10.7 42.5-10.8 42.5-10.8 0-9.7-8.1-32.3-8.1-18.7 0-17.3 19.7-26.3 19.5-8.9-.2-18.8-12.3-19.6-10.2-.7 2.1 9.9 20.1 9.9 20.1z"></path><g fill="#006651"><path d="M237.2 308.1c6.9-5 13-6.6 22.4-8.3s19.4-4.4 24.6-5.8-17.7 6.6-23.5 8.3c-5.8 1.6-23.5 5.8-23.5 5.8zM275.1 293.4c-1.9-11.9 2.8-24.3 13.5-29.3 2.5 8.6-5.2 23.2-13.5 29.3zM293.3 287.2c-5.8-9.8 4-22.6 11.1-28.8 3.3 6-2.5 23.7-11.1 28.8zM310.2 279.6c-6.2-8.4 1.1-23.2 8.8-29 3.1 8.2.1 23.2-8.8 29zM327.1 269c-5.6-8-1.7-20.4 6.3-28.4 5.8 6.6.9 21-6.3 28.4zM340.6 258.3c-4.7-7.5 1.1-25.4 8.6-30.4 3.3 6.6.8 25.4-8.6 30.4zM351.4 255.5c-1.4-10.8 17.4-22.7 25.2-22.4-.9 8.9-8.9 18.6-25.2 22.4zM340.9 267.7c8.8-9.1 26-9.1 32.1-7.2-1.7 5.3-21.9 16.9-32.1 7.2z"></path><path d="M328.7 276.8c12.4-3.3 20.5-6.1 27.9 1.7-5.2 6.6-25.4 4.7-27.9-1.7zM311 284.8c11.9-6.4 26.3 3 28.5 8.6-13.3 5.5-28.7-7.2-28.5-8.6zM294.7 294c10.8-4.1 23.2 1.4 28.2 7.5-5.8 2.7-21 5.7-28.2-7.5zM279.8 298.7c12.4-1.4 24.4 8 27 13.4-15.9 1.5-22-3.2-27-13.4zM275.8 308.1c-6.9-5-13-6.6-22.4-8.3-9.4-1.7-19.4-4.4-24.6-5.8-5.3-1.4 17.7 6.6 23.5 8.3 5.8 1.6 23.5 5.8 23.5 5.8zM237.9 293.4c1.9-11.9-2.8-24.3-13.5-29.3-2.5 8.6 5.2 23.2 13.5 29.3zM219.7 287.2c5.8-9.8-4-22.6-11.1-28.8-3.3 6 2.5 23.7 11.1 28.8zM202.8 279.6c6.2-8.4-1.1-23.2-8.8-29-3.1 8.2-.1 23.2 8.8 29zM185.9 269c5.6-8 1.7-20.4-6.3-28.4-5.8 6.6-.9 21 6.3 28.4zM172.4 258.3c4.7-7.5-1.1-25.4-8.6-30.4-3.3 6.6-.8 25.4 8.6 30.4zM161.6 255.5c1.4-10.8-17.4-22.7-25.2-22.4.9 8.9 8.9 18.6 25.2 22.4zM172.1 267.7c-8.8-9.1-26-9.1-32.1-7.2 1.7 5.3 21.9 16.9 32.1 7.2z"></path><path d="M184.3 276.8c-12.4-3.3-20.5-6.1-27.9 1.7 5.2 6.6 25.4 4.7 27.9-1.7zM202 284.8c-11.9-6.4-26.3 3-28.5 8.6 13.3 5.5 28.7-7.2 28.5-8.6zM218.3 294c-10.8-4.1-23.2 1.4-28.2 7.5 5.8 2.7 21 5.7 28.2-7.5zM233.2 298.7c-12.4-1.4-24.4 8-27 13.4 15.9 1.5 22-3.2 27-13.4z"></path></g></svg>
                <span class="flag-name">Cyprus</span>
            </div>
            </div>

            <!-- Czech Republic -->
            <div class="col-6 col-md-3 col-lg-2">
            <div class="flag-card">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 513 342"><path fill="#11457e" d="M0 0h513v342H0z"></path><path fill="#d7141a" d="M513 171v171H0l215-171z"></path><path fill="#FFF" d="M513 0v171H215.185L0 0z"></path></svg>
                <span class="flag-name">Czech Republic</span>
            </div>
            </div>

            <!-- Denmark -->
            <div class="col-6 col-md-3 col-lg-2">
                <div class="flag-card">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 513 342"><path fill="#c60c30" d="M0 0h513v342H0z"></path><path fill="#FFF" d="M190 0h-60v140H0v60h130v142h60V200h323v-60H190z"></path></svg>
                    <span class="flag-name">Denmark</span>
                </div>
            </div>
            <!-- Finland -->
            <div class="col-6 col-md-3 col-lg-2">
                <div class="flag-card">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 513 342"><path fill="#FFF" d="M0 0h513v342H0z"></path><path fill="#2E52B2" d="M513 129.3V212H203.7v130H121V212H0v-82.7h121V0h82.7v129.3z"></path></svg>
                    <span class="flag-name">Finland</span>
                </div>
            </div>

            <!-- France -->
            <div class="col-6 col-md-3 col-lg-2">
                <div class="flag-card">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 513 342">
                    <path fill="#FFF" d="M0 0h513v342H0z"/>
                    <path fill="#0052B4" d="M0 0h171v342H0z"/>
                    <path fill="#D80027" d="M342 0h171v342H342z"/>
                    </svg>
                    <span class="flag-name">France</span>
                </div>
            </div>

            <!-- Germany -->
            <div class="col-6 col-md-3 col-lg-2">
                <div class="flag-card">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 513 342">
                    <path fill="#D80027" d="M0 0h513v342H0z"/>
                    <path d="M0 0h513v114H0z"/>
                    <path fill="#FFDA44" d="M0 228h513v114H0z"/>
                    </svg>
                    <span class="flag-name">Germany</span>
                </div>
            </div>

            <!-- Greece -->
            <div class="col-6 col-md-3 col-lg-2">
                <div class="flag-card">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 513 342">
                    <path fill="#FFF" d="M0 0h513v342H0z"/>
                    <g fill="#0d5eaf">
                        <path d="M0 0h513v38H0zM0 76h513v38H0zM0 152h513v38H0zM0 228h513v38H0zM0 304h513v38H0z"/>
                        <path d="M0 0h190v190H0z"/>
                    </g>
                    <g fill="#FFF">
                        <path d="M0 76h190v38H0z"/>
                        <path d="M76 0h38v190H76z"/>
                    </g>
                    </svg>
                    <span class="flag-name">Greece</span>
                </div>
            </div>

            <!-- India -->
            <div class="col-6 col-md-3 col-lg-2">
            <div class="flag-card">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 513 342"><path fill="#181A93" d="M17.3 0h478.4v342H17.3V0z"></path><path fill="#FFA44A" d="M0 0h513v114H0V0z"></path><path fill="#1A9F0B" d="M0 228h513v114H0V228z"></path><path fill="#FFF" d="M0 114h513v114H0V114z"></path><circle fill="#FFF" cx="256.5" cy="171" r="34.2"></circle><path fill="#181A93" d="M256.5 216.6c-25.1 0-45.6-20.5-45.6-45.6s20.5-45.6 45.6-45.6 45.6 20.5 45.6 45.6-20.5 45.6-45.6 45.6zm0-11.4c18.2 0 34.2-16 34.2-34.2s-15.9-34.2-34.2-34.2-34.2 16-34.2 34.2 16 34.2 34.2 34.2z"></path><circle fill="#181A93" cx="256.5" cy="171" r="22.8"></circle></svg>
                <span class="flag-name">India</span>
            </div>
            </div>

            <!-- Ireland -->
            <div class="col-6 col-md-3 col-lg-2">
            <div class="flag-card">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 513 342">
                <path fill="#FFF" d="M0 0h513v342H0z"/>
                <path fill="#6DA544" d="M0 0h171v342H0z"/>
                <path fill="#FF9811" d="M342 0h171v342H342z"/>
                </svg>
                <span class="flag-name">Ireland</span>
            </div>
            </div>

            <!-- Israel -->
            <div class="col-6 col-md-3 col-lg-2">
            <div class="flag-card">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 513 342"><path fill="#FFF" d="M0 0h513v342H0z"></path><g fill="#2E52B2"><path d="M340.6 122.4h-56.1l-28-48.6-28 48.6h-56.1l28 48.6-28 48.6h56.1l28 48.6 28-48.6h56.1l-28-48.6 28-48.6zM293.2 171 276 204.2h-38.9L219.8 171l17.2-33.2h38.9l17.3 33.2zm-36.7-71.8 11.9 23.3h-23.9l12-23.3zm-58.3 38.6h23.9l-10.8 21-13.1-21zm0 66.4 13-22.1 11.9 22.1h-24.9zm58.3 37.5-11.9-22.1h23.9l-12 22.1zm59.4-37.5h-25l11.9-22.1 13.1 22.1zm-26.1-66.4h26.1l-13 22.1-13.1-22.1zM0 21.3h512V64H0zM0 277.3h512V320H0z"></path></g></svg>
                <span class="flag-name">Israel</span>
            </div>
            </div>

            <!-- Italy -->
            <div class="col-6 col-md-3 col-lg-2">
            <div class="flag-card">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 513 342">
                <path fill="#FFF" d="M342 0H0v341.3h512V0z"/>
                <path fill="#6DA544" d="M0 0h171v342H0z"/>
                <path fill="#D80027" d="M342 0h171v342H342z"/>
                </svg>
                <span class="flag-name">Italy</span>
            </div>
            </div>
            <!-- Mexico -->
            <div class="col-6 col-md-3 col-lg-2">
            <div class="flag-card">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 513 342"><path fill="#dc2339" d="M342 0H0v341.3h513V0z"></path><path fill="#11865d" d="M0 0h171v342H0z"></path><path fill="#FFF" d="M171 0h171v342H171z"></path><path fill="#8C9157" d="M195.8 171.2c0 21.6 11.5 41.7 30.3 52.5 5.8 3.4 13.2 1.4 16.6-4.4 3.4-5.8 1.4-13.2-4.4-16.6-11.3-6.5-18.2-18.5-18.2-31.5 0-6.7-5.4-12.1-12.1-12.1-6.7 0-12.2 5.4-12.2 12.1zm93.4 51.1c17.5-11.1 28-30.4 28-51.1 0-6.7-5.4-12.1-12.1-12.1s-12.1 5.4-12.1 12.1c0 12.4-6.3 24-16.8 30.7-5.7 3.5-7.5 10.9-4.1 16.7s10.9 7.5 16.7 4.1c0-.2.2-.3.4-.4z"></path><ellipse fill="#C59262" cx="256.5" cy="159.1" rx="24.3" ry="36.4"></ellipse></svg>
                <span class="flag-name">Mexico</span>
            </div>
            </div>

            <!-- Netherlands -->
            <div class="col-6 col-md-3 col-lg-2">
            <div class="flag-card">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 85.5 513 342"><path fill="#FFF" d="M0 85.5h513v342H0z"></path><path fill="#cd1f2a" d="M0 85.5h513v114H0z"></path><path fill="#1d4185" d="M0 312h513v114H0z"></path></svg>
                <span class="flag-name">Netherlands</span>
            </div>
            </div>

            <!-- New Zealand -->
          <div class="col-6 col-md-3 col-lg-2">
              <div class="flag-card">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 85.333 512 341.333"><path fill="#0052B4" d="M0 85.334h512v341.337H0z"></path><g fill="#D80027"><path d="m425.301 233.745 3.388 10.428h10.963l-8.87 6.444 3.388 10.427-8.869-6.444-8.871 6.444 3.388-10.427-8.87-6.444h10.963zM386.107 308.817l5.083 15.642h16.445l-13.305 9.667 5.082 15.64-13.305-9.667-13.305 9.667 5.083-15.64-13.305-9.667h16.445zM387.588 185.971l4.236 13.036h13.704l-11.088 8.054 4.235 13.034-11.087-8.056-11.088 8.056 4.235-13.034-11.087-8.054h13.704zM349.876 233.291l5.082 15.641h16.446l-13.306 9.666 5.084 15.641-13.306-9.666-13.305 9.666 5.082-15.641-13.305-9.666h16.445z"></path></g><path fill="#FFF" d="M256.003 85.329v30.564l-45.178 25.088h45.178v59.359H196.89l59.113 32.846v22.806h-26.69l-73.484-40.826v40.826h-55.652v-48.573l-87.429 48.573H.003v-30.553l45.168-25.099H.003v-59.359h59.103L.003 108.147V85.329h26.68l73.494 40.838V85.329h55.652v48.573l87.43-48.573z"></path><path fill="#D80027" d="M144 85.33h-32v69.334H0v32h112v69.334h32v-69.334h112v-32H144z"></path><path fill="#0052B4" d="M155.826 200.344 256 255.998v-15.739l-71.847-39.915z"></path><path fill="#FFF" d="M155.826 200.344 256 255.998v-15.739l-71.847-39.915z"></path><g fill="#D80027"><path d="M155.826 200.344 256 255.998v-15.739l-71.847-39.915zM71.846 200.344 0 240.259v15.739l100.174-55.654z"></path></g><path fill="#0052B4" d="M100.174 140.983 0 85.33v15.738l71.847 39.915z"></path><path fill="#FFF" d="M100.174 140.983 0 85.33v15.738l71.847 39.915z"></path><g fill="#D80027"><path d="M100.174 140.983 0 85.33v15.738l71.847 39.915zM184.154 140.983 256 101.068V85.33l-100.174 55.653z"></path></g></svg>
                  <span class="flag-name">New Zealand</span>
              </div>
          </div>

          <!-- Norway -->
          <div class="col-6 col-md-3 col-lg-2">
              <div class="flag-card">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 85.333 512 341.333"><path fill="#D80027" d="M0 85.334h512v341.337H0z"></path><path fill="#FFF" d="M512 295.883H202.195v130.783H122.435V295.883H0V216.111h122.435V85.329H202.195v130.782H512V277.329z"></path><path fill="#2E52B2" d="M512 234.666v42.663H183.652v149.337h-42.674V277.329H0v-42.663h140.978V85.329h42.674v149.337z"></path></svg>
                  <span class="flag-name">Norway</span>
              </div>
          </div>

          <!-- Poland -->
          <div class="col-6 col-md-3 col-lg-2">
              <div class="flag-card">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 85.333 512 341.333"><g fill="#FFF"><path d="M0 85.337h512v341.326H0z"></path><path d="M0 85.337h512V256H0z"></path></g><path fill="#D80027" d="M0 256h512v170.663H0z"></path></svg>
                  <span class="flag-name">Poland</span>
              </div>
          </div>

          <!-- Qatar -->
          <div class="col-6 col-md-3 col-lg-2">
              <div class="flag-card">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 513 342"><path fill="#751A46" d="M0 0h512v342H0z"></path><path fill="#FFF" d="M0 0v342h150.3l37.7-19.6-37.7-18.9 37.7-19-37.7-18.9 37.7-19-37.7-19 37.7-18.9-37.7-19 37.7-19-37.7-18.9 37.7-19-37.7-18.9 37.7-19-37.7-19L188 57l-37.7-19L188 19.1 150.3 0z"></path></svg>
                  <span class="flag-name">Qatar</span>
              </div>
          </div>


            <!-- Saudi Arabia -->
            <div class="col-6 col-md-3 col-lg-2">
            <div class="flag-card">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 85.333 512 341.333"><path fill="#055e1c" d="M0 85.333h512v341.333H0z"></path><g fill="#FFF"><path d="M183.548 289.386c0 12.295 9.731 22.261 21.736 22.261h65.208c0 10.244 8.11 18.551 18.114 18.551h21.736c10.004 0 18.114-8.306 18.114-18.551v-22.261H183.548zM330.264 181.791v51.942c0 8.183-6.5 14.84-14.491 14.84v22.261c19.976 0 36.226-16.643 36.226-37.101v-51.942h-21.735zM174.491 233.734c0 8.183-6.5 14.84-14.491 14.84v22.261c19.976 0 36.226-16.643 36.226-37.101v-51.942H174.49v51.942z"></path><path d="M297.661 181.788h21.736v51.942h-21.736zM265.057 211.473c0 2.046-1.625 3.71-3.623 3.71-1.998 0-3.623-1.664-3.623-3.71v-29.682h-21.736v29.682c0 2.046-1.625 3.71-3.623 3.71s-3.623-1.664-3.623-3.71v-29.682h-21.736v29.682c0 14.32 11.376 25.971 25.358 25.971 5.385 0 10.38-1.733 14.491-4.677 4.11 2.944 9.106 4.677 14.491 4.677 1.084 0 2.15-.078 3.2-.215-1.54 6.499-7.255 11.345-14.068 11.345v22.261c19.976 0 36.226-16.643 36.226-37.101v-51.943h-21.736l.002 29.682z"></path><path d="M207.093 248.57h32.601v22.261h-32.601z"></path></g></svg>
                <span class="flag-name">Saudi Arabia</span>
            </div>
            </div>

            <!-- Singapore -->
            <div class="col-6 col-md-3 col-lg-2">
            <div class="flag-card">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 85.333 512 341.333"><path fill="#FFF" d="M0 85.337h512v341.326H0z"></path><path fill="#D80027" d="M0 85.337h512V256H0z"></path><g fill="#FFF"><path d="M83.478 170.666c0-24.865 17.476-45.637 40.812-50.734a52.059 52.059 0 0 0-11.13-1.208c-28.688 0-51.942 23.254-51.942 51.941s23.255 51.942 51.942 51.942c3.822 0 7.543-.425 11.13-1.208-23.336-5.095-40.812-25.867-40.812-50.733zM150.261 122.435l3.684 11.337h11.921l-9.645 7.007 3.684 11.337-9.644-7.006-9.645 7.006 3.685-11.337-9.645-7.007h11.921z"></path><path d="m121.344 144.696 3.683 11.337h11.921l-9.645 7.007 3.684 11.337-9.643-7.006-9.645 7.006 3.685-11.337-9.645-7.007h11.921zM179.178 144.696l3.684 11.337h11.921l-9.645 7.007 3.684 11.337-9.644-7.006-9.644 7.006 3.685-11.337-9.645-7.007h11.921zM168.047 178.087l3.684 11.337h11.921l-9.644 7.007 3.684 11.337-9.645-7.006-9.643 7.006 3.684-11.337-9.644-7.007h11.92zM132.474 178.087l3.683 11.337h11.921l-9.644 7.007 3.684 11.337-9.644-7.006-9.644 7.006 3.684-11.337-9.644-7.007h11.92z"></path></g></svg>
                <span class="flag-name">Singapore</span>
            </div>
            </div>

            <!-- South Africa -->
            <div class="col-6 col-md-3 col-lg-2">
            <div class="flag-card">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 85.333 512 341.333"><path fill="#FFF" d="M0 85.337h512v341.326H0z"></path><path d="M114.024 256.001 0 141.926v228.17z"></path><path fill="#ffb915" d="M161.192 256 0 94.7v47.226l114.024 114.075L0 370.096v47.138z"></path><path fill="#007847" d="M509.833 289.391c.058-.44.804-.878 2.167-1.318v-65.464H222.602L85.33 85.337H0V94.7L161.192 256 0 417.234v9.429h85.33l137.272-137.272h287.231z"></path><path fill="#000c8a" d="M503.181 322.783H236.433l-103.881 103.88H512v-103.88z"></path><path fill="#e1392d" d="M503.181 189.217H512V85.337H132.552l103.881 103.88z"></path></svg>
                <span class="flag-name">South Africa</span>
            </div>
            </div>

            <!-- Spain -->
            <div class="col-6 col-md-3 col-lg-2">
            <div class="flag-card">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 22.5 15"><path fill="#FFF" d="M0 0h22.5v15H0V0z"></path><path fill="#D03433" d="M0 0h22.5v4H0V0zm0 11h22.5v4H0v-4z"></path><path fill="#FBCA46" d="M0 4h22.5v7H0V4z"></path><path fill="#FFF" d="M7.8 7h1v.5h-1V7z"></path><path fill="#A41517" d="M7.2 8.5c0 .3.3.5.6.5s.6-.2.6-.5L8.5 7H7.1l.1 1.5zM6.6 7c0-.3.2-.5.4-.5h1.5c.3 0 .5.2.5.4V7l-.1 1.5c-.1.6-.5 1-1.1 1-.6 0-1-.4-1.1-1L6.6 7z"></path><path fill="#A41517" d="M6.8 7.5h2V8h-.5l-.5 1-.5-1h-.5v-.5zM5.3 6h1v3.5h-1V6zm4 0h1v3.5h-1V6zm-2.5-.5c0-.3.2-.5.5-.5h1c.3 0 .5.2.5.5v.2c0 .2-.1.3-.3.3H7c-.1 0-.2-.1-.2-.2v-.3z"></path></svg>
                <span class="flag-name">Spain</span>
            </div>
            </div>

            <!-- Sweden -->
            <div class="col-6 col-md-3 col-lg-2">
            <div class="flag-card">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 85.333 513 342"><path fill="red" d="M0 85.337h513v342H0z"></path><path fill="#FFF" d="M356.174 222.609h-66.783v-66.783h-66.782v66.783h-66.783v66.782h66.783v66.783h66.782v-66.783h66.783z"></path></svg>
                <span class="flag-name">Sweden</span>
            </div>
            </div>

            <!-- Switzerland -->
            <div class="col-6 col-md-3 col-lg-2">
            <div class="flag-card">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 85.333 513 342"><path fill="red" d="M0 85.337h513v342H0z"></path><path fill="#FFF" d="M356.174 222.609h-66.783v-66.783h-66.782v66.783h-66.783v66.782h66.783v66.783h66.782v-66.783h66.783z"></path></svg>
                <span class="flag-name">Switzerland</span>
            </div>
            </div>

            <!-- Taiwan -->
            <div class="col-6 col-md-3 col-lg-2">
            <div class="flag-card">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 85.333 512 341.333"><path fill="#D80027" d="M0 85.337h512v341.326H0z"></path><path fill="#0052B4" d="M0 85.337h256V256H0z"></path><path fill="#FFF" d="M186.435 170.669 162.558 181.9l12.714 23.125-25.927-4.961-3.286 26.192L128 206.993l-18.06 19.263-3.285-26.192-25.927 4.96 12.714-23.125-23.877-11.23 23.877-11.231-12.714-23.125 25.927 4.96 3.286-26.192L128 134.344l18.06-19.263 3.285 26.192 25.928-4.96-12.715 23.125z"></path><circle fill="#0052B4" cx="128" cy="170.674" r="29.006"></circle><path fill="#FFF" d="M128 190.06c-10.692 0-19.391-8.7-19.391-19.391 0-10.692 8.7-19.391 19.391-19.391 10.692 0 19.391 8.7 19.391 19.391 0 10.691-8.699 19.391-19.391 19.391z"></path></svg>
                <span class="flag-name">Taiwan</span>
            </div>
            </div>

            <!-- Thailand -->
            <div class="col-6 col-md-3 col-lg-2">
            <div class="flag-card">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 85.333 512 341.333"><path fill="#FFF" d="M0 85.334h512V426.66H0z"></path><path fill="#0052B4" d="M0 194.056h512v123.882H0z"></path><g fill="#D80027"><path d="M0 85.334h512v54.522H0zM0 372.143h512v54.522H0z"></path></g></svg>
                <span class="flag-name">Thailand</span>
            </div>
            </div>

            <!-- Turkey -->
            <div class="col-6 col-md-3 col-lg-2">
            <div class="flag-card">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 513 342"><path fill="#E30A17" d="M0 0h513v342H0z"></path><path fill="#FFF" d="M259.7 118.6c-13.1-9.5-29-14.6-45.3-14.5-40.8 0-73.8 30.8-73.8 68.9s33.1 68.9 73.8 68.9c17.1 0 32.9-5.4 45.3-14.5-30 38.6-85.7 45.6-124.3 15.5s-45.6-85.7-15.5-124.3 85.7-45.6 124.3-15.5c5.8 4.5 11 9.8 15.5 15.5zm39.9 65.8-18.1 21.9 1.2-28.4-26.4-10.4 27.3-7.6 1.8-28.3 15.6 23.7 27.5-7.1-17.5 22 15.3 23.9-26.7-9.7z"></path></svg>
                <span class="flag-name">Turkey</span>
            </div>
            </div>

            <!-- United Arab Emirates -->
            <div class="col-6 col-md-3 col-lg-2">
            <div class="flag-card">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 513 342"><path fill="#FFF" d="M0 0h513v342H0z"></path><path fill="#009e49" d="M0 0h513v114H0z"></path><path d="M0 228h513v114H0z"></path><path fill="#ce1126" d="M0 0h171v342H0z"></path></svg>
                <span class="flag-name">United Arab Emirates</span>
            </div>
            </div>

            <!-- United Kingdom -->
            <div class="col-6 col-md-3 col-lg-2">
            <div class="flag-card">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 513 342"><g fill="#FFF"><path d="M0 0h513v341.3H0V0z"></path><path d="M311.7 230 513 341.3v-31.5L369.3 230h-57.6zM200.3 111.3 0 0v31.5l143.7 79.8h56.6z"></path></g><g fill="#0052B4"><path d="M393.8 230 513 295.7V230H393.8zm-82.1 0L513 341.3v-31.5L369.3 230h-57.6zm146.9 111.3-147-81.7v81.7h147zM90.3 230 0 280.2V230h90.3zm110 14.2v97.2H25.5l174.8-97.2zM118.2 111.3 0 45.6v65.7h118.2zm82.1 0L0 0v31.5l143.7 79.8h56.6zM53.4 0l147 81.7V0h-147zM421.7 111.3 513 61.1v50.2h-91.3zm-110-14.2V0h174.9L311.7 97.1z"></path></g><g fill="#D80027"><path d="M288 0h-64v138.7H0v64h224v138.7h64V202.7h224v-64H288V0z"></path><path d="M311.7 230 513 341.3v-31.5L369.3 230h-57.6zM143.7 230 0 309.9v31.5L200.3 230h-56.6zM200.3 111.3 0 0v31.5l143.7 79.8h56.6zM368.3 111.3 513 31.5V0L311.7 111.3h56.6z"></path></g></svg>
                <span class="flag-name">United Kingdom</span>
            </div>
            </div>

            <!-- United States -->
            <div class="col-6 col-md-3 col-lg-2">
            <div class="flag-card">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 513 342"><path fill="#FFF" d="M0 0h513v342H0z"></path><g fill="#D80027"><path d="M0 0h513v26.3H0zM0 52.6h513v26.3H0zM0 105.2h513v26.3H0zM0 157.8h513v26.3H0zM0 210.5h513v26.3H0zM0 263.1h513v26.3H0zM0 315.7h513V342H0z"></path></g><path fill="#2E52B2" d="M0 0h256.5v184.1H0z"></path><g fill="#FFF"><path d="m47.8 138.9-4-12.8-4.4 12.8H26.2l10.7 7.7-4 12.8 10.9-7.9 10.6 7.9-4.1-12.8 10.9-7.7zM104.1 138.9l-4.1-12.8-4.2 12.8H82.6l10.7 7.7-4 12.8 10.7-7.9 10.8 7.9-4-12.8 10.7-7.7zM160.6 138.9l-4.3-12.8-4 12.8h-13.5l11 7.7-4.2 12.8 10.7-7.9 11 7.9-4.2-12.8 10.7-7.7zM216.8 138.9l-4-12.8-4.2 12.8h-13.3l10.8 7.7-4 12.8 10.7-7.9 10.8 7.9-4.3-12.8 11-7.7zM100 75.3l-4.2 12.8H82.6L93.3 96l-4 12.6 10.7-7.8 10.8 7.8-4-12.6 10.7-7.9h-13.4zM43.8 75.3l-4.4 12.8H26.2L36.9 96l-4 12.6 10.9-7.8 10.6 7.8L50.3 96l10.9-7.9H47.8zM156.3 75.3l-4 12.8h-13.5l11 7.9-4.2 12.6 10.7-7.8 11 7.8-4.2-12.6 10.7-7.9h-13.2zM212.8 75.3l-4.2 12.8h-13.3l10.8 7.9-4 12.6 10.7-7.8 10.8 7.8-4.3-12.6 11-7.9h-13.5zM43.8 24.7l-4.4 12.6H26.2l10.7 7.9-4 12.7L43.8 50l10.6 7.9-4.1-12.7 10.9-7.9H47.8zM100 24.7l-4.2 12.6H82.6l10.7 7.9-4 12.7L100 50l10.8 7.9-4-12.7 10.7-7.9h-13.4zM156.3 24.7l-4 12.6h-13.5l11 7.9-4.2 12.7 10.7-7.9 11 7.9-4.2-12.7 10.7-7.9h-13.2zM212.8 24.7l-4.2 12.6h-13.3l10.8 7.9-4 12.7 10.7-7.9 10.8 7.9-4.3-12.7 11-7.9h-13.5z"></path></g></svg>
                <span class="flag-name">United States</span>
            </div>
        </div>
      </div>
    </section>
  </main>
</div>

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