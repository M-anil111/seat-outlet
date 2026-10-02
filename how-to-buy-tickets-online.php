<?php
require_once 'functions.php';
// SEO: this page previously relied on header.php's generic fallback
// title/canonical.
$pageMetaTitle       = 'What We Do at Seat Outlet | Seat Outlet';
$pageMetaDescription = 'Seat Outlet connects fans with live events across concerts, sports, and theater - making it easy to discover, compare, and securely book tickets.';
$pageCanonicalUrl    = HOME_URL . '/how-to-buy-tickets-online';
include 'header.php';
?>

<style>
    :root {
      --brand-blue:   #2563ff;
      --brand-blue-hover: #1a4fd6;
      --brand-blue-soft: rgba(37,99,255,.10);
      --brand-blue-border: rgba(37,99,255,.25);
      --brand-dark:   #111827;
      --brand-mid:    #F3F6FF;
      --brand-card:   #ffffff;
      --brand-border: #e5e9f2;
      --brand-muted:  #6b7280;
      --brand-text:   #1f2937;
    }

    *, *::before, *::after { box-sizing: border-box; }

    body {
      
      background: #ffffff;
      color: var(--brand-text);
      font-size: 1rem;
      line-height: 1.7;
      overflow-x: hidden;
    }

    /* ── TYPOGRAPHY ── */
    h1, h2, h3, .display-font,
h2.h1 { letter-spacing: .04em; }
    h1,
h2.h1 { font-size: clamp(3rem, 8vw, 6.5rem); line-height: 1; }
    /* h2  { font-size: clamp(2rem, 4vw, 3rem); line-height: 1.1; } */

  

    /* ── HERO ── */
    .hero {
      min-height: 92vh;
      display: flex;
      align-items: center;
      position: relative;
      overflow: hidden;
      background: var(--brand-dark);
      padding-top: 80px;
    }
    .hero::before {
      content: '';
      position: absolute; inset: 0;
      background:
        radial-gradient(ellipse 60% 60% at 70% 40%, rgba(37,99,255,.22) 0%, transparent 70%),
        radial-gradient(ellipse 40% 50% at 20% 80%, rgba(37,99,255,.12) 0%, transparent 60%);
      pointer-events: none;
    }
    .hero-tag {
      display: inline-block;
      background: rgba(37,99,255,.18);
      color: #7aa3ff;
      border: 1px solid rgba(37,99,255,.35);
      font-size: .78rem;
      font-weight: 600;
      letter-spacing: .12em;
      text-transform: uppercase;
      padding: 5px 14px;
      border-radius: 2px;
      margin-bottom: 1.2rem;
    }
    .hero h1,
.hero h2.h1 {
    margin: 6px 0 20px 0px;
    font-size: 50px;
}
    .hero h1 em,
.hero h2.h1 em { font-style: normal; color: #7aa3ff; }
    .hero-sub { font-size: 1.15rem; color: #9ca3af; max-width: 520px; line-height: 1.75; margin-bottom: 2rem; }
    .btn-primary-brand {
      background: var(--brand-blue); color: #fff; border: none;
      font-weight: 600; font-size: 1rem; letter-spacing: .04em;
      padding: 14px 34px; border-radius: 4px;
      transition: background .2s, transform .15s, box-shadow .2s;
      box-shadow: 0 4px 24px rgba(37,99,255,.40);
    }
    .btn-primary-brand:hover { background: var(--brand-blue-hover); transform: translateY(-2px); box-shadow: 0 8px 32px rgba(37,99,255,.50); color: #fff; }
    .btn-outline-brand {
      border: 1px solid rgba(255,255,255,.25); color: #fff;
      background: transparent; font-weight: 500; font-size: 1rem;
      padding: 14px 30px; border-radius: 4px;
      transition: border-color .2s, background .2s;
    }
    .btn-outline-brand:hover { border-color: #fff; background: rgba(255,255,255,.08); color: #fff; }

    .hero-stat-row { margin-top: 3.5rem; gap: 2rem; }
    .hero-stat strong {  font-size: 2.2rem; color: #fff; }
    .hero-stat span { font-size: .8rem; color: #9ca3af; text-transform: uppercase; letter-spacing: .08em; }

    /* ticket grid decoration */
    .hero-visual {
      position: relative;
      display: flex; align-items: center; justify-content: center;
    }
    .ticket-stack { position: relative; width: 340px; height: 420px; }
    .ticket-card {
      position: absolute;
      width: 300px;
      background: #1e2a45;
      border: 1px solid rgba(255,255,255,.10);
      border-radius: 12px;
      padding: 24px 28px;
      box-shadow: 0 12px 48px rgba(0,0,0,.5);
    }
    .ticket-card:nth-child(1) { top: 0; left: 40px; transform: rotate(6deg); opacity: .5; }
    .ticket-card:nth-child(2) { top: 30px; left: 20px; transform: rotate(2deg); opacity: .75; }
    .ticket-card:nth-child(3) { top: 60px; left: 0; transform: rotate(-1deg); z-index: 3; opacity: 1; }
    .ticket-badge {
      display: inline-block; background: var(--brand-blue);
      color: #fff; font-size: .7rem; font-weight: 700;
      text-transform: uppercase; letter-spacing: .1em;
      padding: 3px 10px; border-radius: 2px; margin-bottom: 12px;
    }
    .ticket-event-name {  font-size: 1.5rem; color: #fff; margin: 0 0 6px; }
    .ticket-meta { font-size: .82rem; color: #9ca3af; }
    .ticket-divider {
      border: none; border-top: 1px dashed rgba(255,255,255,.12);
      margin: 16px 0;
    }
    .ticket-price {  font-size: 2rem; color: #7aa3ff; }
    .ticket-price-label { font-size: .75rem; color: #9ca3af; }

    /* ── SECTION BASE ── */
    section { padding: 90px 0; }
    .section-eyebrow {
      font-size: .78rem; font-weight: 700; letter-spacing: .14em;
      text-transform: uppercase; color: var(--brand-blue); margin-bottom: .6rem;
    }
    .section-divider {
      width: 48px; height: 3px; background: var(--brand-blue); margin-bottom: 1.6rem;
    }

    /* ── SIMPLIFY SECTION ── */
    .simplify-bg { background: var(--brand-mid); }
    .process-step {
      display: flex; gap: 1.2rem; align-items: flex-start;
    }
    .step-num {
      flex-shrink: 0;
      width: 42px; height: 42px;
      background: var(--brand-blue-soft); border: 1px solid var(--brand-blue-border);
      border-radius: 6px; display: flex; align-items: center; justify-content: center;
       font-size: 1.2rem; color: var(--brand-blue);
    }
    .process-step p { font-size: .95rem; color: var(--brand-muted); margin: 0; }
    .process-step h5 { font-size: 1rem; font-weight: 600; color: var(--brand-text); margin-bottom: 4px; }

    /* ── EVENT CATEGORIES ── */
    .category-card {
      background: var(--brand-card);
      border: 1px solid var(--brand-border);
      border-radius: 10px; padding: 28px 24px;
      transition: border-color .25s, transform .25s, box-shadow .25s;
      height: 100%;
    }
    .category-card:hover { border-color: var(--brand-blue); transform: translateY(-4px); box-shadow: 0 12px 40px rgba(37,99,255,.12); }
    .category-icon {
      width: 52px; height: 52px;
      background: var(--brand-blue-soft); border-radius: 8px;
      display: flex; align-items: center; justify-content: center;
      font-size: 1.5rem; color: var(--brand-blue); margin-bottom: 16px;
    }
    .category-card h5 { font-size: 1.05rem; font-weight: 600; color: var(--brand-text); margin-bottom: 6px; }
    .category-card p { font-size: .9rem; color: var(--brand-muted); margin: 0; }

    /* ── TRUST PILLARS ── */
    .trust-bg { background: var(--brand-mid); }
    .trust-item {
      background: var(--brand-card); border: 1px solid var(--brand-border);
      border-radius: 10px; padding: 32px 28px; height: 100%;
      transition: border-color .25s, box-shadow .25s;
    }
    .trust-item:hover { border-color: var(--brand-blue-border); box-shadow: 0 8px 32px rgba(37,99,255,.10); }
    .trust-icon { font-size: 2rem; color: var(--brand-blue); margin-bottom: 14px; }
    .trust-item h5 { font-size: 1rem; font-weight: 600; color: var(--brand-text); margin-bottom: 8px; }
    .trust-item ul { list-style: none; padding: 0; margin: 0; }
    .trust-item ul li { font-size: .9rem; color: var(--brand-muted); padding: 4px 0; display: flex; align-items: center; gap: 8px; }
    .trust-item ul li::before { content: ''; width: 6px; height: 6px; background: var(--brand-blue); border-radius: 50%; flex-shrink: 0; }

    /* ── WHY STAND OUT ── */
    .standout-item { display: flex; gap: 1rem; align-items: flex-start; margin-bottom: 1.5rem; }
    .standout-num {
      flex-shrink: 0; width: 36px; height: 36px;
      border: 1px solid var(--brand-blue-border); border-radius: 50%;
      background: var(--brand-blue-soft);
      display: flex; align-items: center; justify-content: center;
       font-size: 1rem; color: var(--brand-blue);
    }
    .standout-item h5 { font-size: 1rem; font-weight: 600; color: var(--brand-text); margin: 0 0 3px; }
    .standout-item p { font-size: .9rem; color: var(--brand-muted); margin: 0; }

    /* ── CTA STRIP ── */
    .cta-strip {
      background: var(--brand-blue);
      position: relative; overflow: hidden;
    }
    .cta-strip::before {
      content: 'SEAT OUTLET SEAT OUTLET SEAT OUTLET SEAT OUTLET SEAT OUTLET SEAT OUTLET ';
      position: absolute; top: 50%; left: 0; transform: translateY(-50%);
       font-size: 5rem; white-space: nowrap;
      color: rgba(255,255,255,.07); letter-spacing: .06em; pointer-events: none;
    }
    .cta-strip h2 { color: #fff; }
    .cta-strip p { color: rgba(255,255,255,.85); font-size: 1.1rem; }
    .btn-white {
      background: #fff; color: var(--brand-blue); font-weight: 700;
      border: none; padding: 14px 36px; border-radius: 4px; font-size: 1rem;
      transition: background .2s, transform .15s;
    }
    .btn-white:hover { background: #eef2ff; transform: translateY(-2px); color: var(--brand-blue-hover); }



    /* ── ANIMATION ── */
    @keyframes fadeUp {
      from { opacity: 0; transform: translateY(28px); }
      to   { opacity: 1; transform: translateY(0); }
    }
    .fade-up { animation: fadeUp .7s ease both; }
    .delay-1 { animation-delay: .12s; }
    .delay-2 { animation-delay: .24s; }
    .delay-3 { animation-delay: .36s; }
    .delay-4 { animation-delay: .48s; }

    /* scrollbar */
    ::-webkit-scrollbar { width: 6px; }
    ::-webkit-scrollbar-track { background: #f1f5f9; }
    ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 3px; }
    @media (max-width: 575.98px){
    .hero{padding:50px 10px 50px 10px !important;}}
  </style>


<!-- ═══════════════ HERO ═══════════════ -->
<section class="hero">
  <div class="container">
    <div class="row align-items-center g-5">
      <!-- copy -->
      <div class="col-lg-6">
        <div class="hero-tag fade-up"><i class="bi bi-ticket-perforated me-1"></i> Resale Ticket Marketplace</div>
        <h1 class="fade-up delay-1">How to Buy Tickets Online: Your Seat <em>Awaits</em></h1>
        <p class="hero-sub fade-up delay-2">
          <?php echo getContentBlock('/what-we-do', 'hero-sub', 'We make ticket buying simple: Seat Outlet connects fans with live events across concerts, sports, theater, and more. Find, compare, and book tickets securely in just a few clicks.'); ?>
        </p>
        <div class="d-flex flex-wrap gap-3 fade-up delay-3">
          <a href="/" class="btn btn-primary-brand">Find Tickets Near You <i class="bi bi-arrow-right ms-1"></i></a>
          <a href="/buy-tickets-online" class="btn btn-outline-brand">Browse Events</a>
        </div>
        <div class="d-flex flex-wrap hero-stat-row fade-up delay-4">
          <div class="hero-stat">
            <strong>Live</strong><br><span>Concerts, Sports, Theater</span>
          </div>
          <div class="hero-stat">
            <strong>Compare</strong><br><span>Seats and Prices</span>
          </div>
          <div class="hero-stat">
            <strong>Email</strong><br><span>Support by form or email</span>
          </div>
        </div>
      </div>
      <!-- visual -->
      <div class="col-lg-6 d-none d-lg-flex justify-content-center">
        <div class="hero-visual fade-up delay-2">
          <div class="ticket-stack">
            <!-- card 1 (back) -->
            <div class="ticket-card">
              <span class="ticket-badge">Sports</span>
              <div class="ticket-event-name">Find a game</div>
              <div class="ticket-meta"><i class="bi bi-geo-alt me-1"></i>Search by team or city</div>
              <hr class="ticket-divider">
              <div class="ticket-price">Sports</div>
            </div>
            <!-- card 2 (mid) -->
            <div class="ticket-card">
              <span class="ticket-badge">Theater</span>
              <div class="ticket-event-name">Pick a show</div>
              <div class="ticket-meta"><i class="bi bi-geo-alt me-1"></i>Search by title or city</div>
              <hr class="ticket-divider">
              <div class="ticket-price">Theater</div>
            </div>
            <!-- card 3 (front) -->
            <div class="ticket-card">
              <span class="ticket-badge">Concert</span>
              <div class="ticket-event-name">Choose a concert</div>
              <div class="ticket-meta"><i class="bi bi-geo-alt me-1"></i>Search by artist or venue</div>
              <hr class="ticket-divider">
              <div class="d-flex justify-content-between align-items-end">
                <div>
                  <div class="ticket-price">Concerts</div>
                  <div class="ticket-price-label">compare prices</div>
                </div>
                
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ═══════════════ SIMPLIFY ═══════════════ -->
<section class="simplify-bg" id="how">
  <div class="container">
    <div class="row g-5 align-items-center">
      <div class="col-lg-5">
        <div class="section-eyebrow">How It Works</div>
        <h2 class="mb-3">Making Event Ticket Buying Simple</h2>
        <img src="/images/stage.webp" class="img-fluid rounded mb-3" style="max-width:200px;" alt="Live shows made ticket buying simple" loading="lazy" width="750" height="843" decoding="async">
        <div class="section-divider"></div>
        <p class="text-secondary">Finding tickets should not be complicated. At Seat Outlet, we simplify the entire process from search to checkout so you can spend less time searching and more time enjoying your
        <a href="https://en.wikipedia.org/wiki/Live_event" target="_blank" rel="noopener">live event</a>.</p>
        <img src="/images/event-ticket-buying.webp" class="img-fluid rounded mt-3" alt="Making ticket buying simple on Seat Outlet" loading="lazy" width="1536" height="1024" decoding="async">
      </div>
      <div class="col-lg-6 offset-lg-1">
        <div class="d-flex flex-column gap-4">
          <div class="process-step">
            <div class="step-num">01</div>
            <div>
              <h5>Browse Live Events</h5>
              <p>Explore concerts, sports, theater and festivals listed on the TicketNetwork marketplace, all in one place.</p>
            </div>
          </div>
          <div class="process-step">
            <div class="step-num">02</div>
            <div>
              <h5>Search by City, Category, or Performer</h5>
              <p>Powerful filters help you zero in on exactly the event you're looking for.</p>
            </div>
          </div>
          <div class="process-step">
            <div class="step-num">03</div>
            <div>
              <h5>Compare Ticket Availability & Pricing</h5>
              <p>Side-by-side views make it easy to choose the best seats at the best price.</p>
            </div>
          </div>
          <div class="process-step">
            <div class="step-num">04</div>
            <div>
              <h5>Book in Just a Few Clicks</h5>
              <p>Pick your seats and continue to checkout, which is hosted by TicketNetwork.</p>
            </div>
          </div>
        </div>
        <p class="text-secondary mt-4">
          You can browse without signing up for anything, and there is no newsletter sign-up before checkout.
          Prices are set by sellers and may be above or below face value. Review the full cost at checkout before you pay.
        </p>
      </div>
    </div>
  </div>
</section>

<!-- ═══════════════ EVENTS ═══════════════ -->
<section id="events">
  <div class="container">
    <div class="text-center mb-5">
      <div class="section-eyebrow">Event Categories</div>
      <h2>Connecting You to Live Events</h2>
      <div class="section-divider mx-auto"></div>
      <p class="text-secondary mx-auto" style="max-width:560px;">Whether you're planning ahead or looking for last-minute tickets, Seat Outlet gives you access to events happening near you and across the country.</p>
    </div>
    <div class="row g-4">
      <div class="col-sm-6 col-lg-3">
        <div class="category-card">
          <div class="category-icon"><i class="bi bi-music-note-beamed"></i></div>
          <h5>Concerts & Music Tours</h5>
          <p>From intimate venues to stadium spectacles — every genre, every city.</p>
        </div>
      </div>
      <div class="col-sm-6 col-lg-3">
        <div class="category-card">
          <div class="category-icon"><i class="bi bi-trophy"></i></div>
          <h5>Sports & Championships</h5>
          <p>NFL, NBA, MLB, NHL, and more — be in the stands when it matters most.</p>
        </div>
      </div>
      <div class="col-sm-6 col-lg-3">
        <div class="category-card">
          <div class="category-icon"><i class="bi bi-camera-reels"></i></div>
          <h5>Theater & Live Performances</h5>
          <p>Broadway hits, touring productions, and local theater experiences.</p>
        </div>
      </div>
      <div class="col-sm-6 col-lg-3">
        <div class="category-card">
          <div class="category-icon"><i class="bi bi-emoji-laughing"></i></div>
          <h5>Comedy & Special Events</h5>
          <p>Stand-up specials, festivals, and unique live experiences you won't forget.</p>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ═══════════════ TRUST PILLARS ═══════════════ -->
<section class="trust-bg" id="verified">
  <div class="container">
    <div class="text-center mb-5">
      <div class="section-eyebrow">Trust & Security</div>
      <h2>Every Step. Protected.</h2>
      <div class="section-divider mx-auto"></div>
    </div>
    <div class="row g-4">
      <!-- Verified Tickets -->
      <div class="col-md-6 col-lg-4">
        <div class="trust-item">
          <div class="trust-icon"><i class="bi bi-patch-check-fill"></i></div>
          <h5>Tickets From the TicketNetwork Marketplace</h5>
          <p class="text-secondary small mb-3">Listings come from sellers on the TicketNetwork marketplace, and orders are covered by its guarantee.</p>
          <ul>
            <li>Listings from TicketNetwork sellers</li>
            <li>Orders covered by the 100% guarantee</li>
            <li><a href="/worry-free-guarantee">See what is covered</a></li>
          </ul>
        </div>
      </div>
      <!-- Secure Booking -->
      <div class="col-md-6 col-lg-4" id="security">
        <div class="trust-item">
          <div class="trust-icon"><i class="bi bi-shield-lock-fill"></i></div>
          <h5>Secure Booking Experience</h5>
          <p class="text-secondary small mb-3">Checkout is hosted by TicketNetwork.</p>
          <ul>
            <li>Hosted checkout</li>
            <li>Payment is entered on the checkout page</li>
            <li><a href="/privacy-policy">How we handle data</a></li>
          </ul>
        </div>
      </div>
      <!-- Transparent -->
      <div class="col-md-6 col-lg-4">
        <div class="trust-item">
          <div class="trust-icon"><i class="bi bi-eye-fill"></i></div>
          <h5>Full Transparency</h5>
          <p class="text-secondary small mb-3">Know exactly what to expect before you purchase.</p>
          <ul>
            <li>Seller-set prices, above or below face value</li>
            <li>Clear ticket information</li>
            <li>Easy-to-understand policies</li>
          </ul>
        </div>
      </div>
      <!-- Support -->
      <div class="col-md-6 col-lg-4" id="support">
        <div class="trust-item">
          <div class="trust-icon"><i class="bi bi-headset"></i></div>
          <h5>Customer Support</h5>
          <p class="text-secondary small mb-3">Email us before or after you buy.</p>
          <ul>
            <li>Questions before you book</li>
            <li>Delivery details in your confirmation email</li>
            <li><a href="/ticket-customer-service">Contact support</a></li>
          </ul>
        </div>
      </div>
      <!-- Delivery -->
      <div class="col-md-6 col-lg-4">
        <div class="trust-item">
          <div class="trust-icon"><i class="bi bi-lightning-charge-fill"></i></div>
          <h5>Reliable Ticket Delivery</h5>
          <p class="text-secondary small mb-3">Delivery options are shown at checkout.</p>
          <ul>
            <li>Digital & mobile delivery</li>
            <li>Order confirmation by email</li>
            <li>Delivery details in your order</li>
          </ul>
        </div>
      </div>
      <!-- Easy UX -->
      <div class="col-md-6 col-lg-4">
        <div class="trust-item">
          <div class="trust-icon"><i class="bi bi-hand-thumbs-up-fill"></i></div>
          <h5>Fast & Easy Experience</h5>
          <p class="text-secondary small mb-3">Speed and simplicity at every step of your journey.</p>
          <ul>
            <li>Secure hosted checkout</li>
            <li>Mobile-friendly platform</li>
            <li>Review the full cost at checkout</li>
          </ul>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ═══════════════ WHY STAND OUT ═══════════════ -->
<section>
  <div class="container">
    <div class="row g-5 align-items-center">
      <div class="col-lg-5">
        <div class="section-eyebrow">Why Choose Us</div>
        <h2 class="mb-3">Why Seat Outlet Stands Out</h2>
        <div class="section-divider"></div>
        <p class="text-secondary">We are focused on making event ticket purchasing smooth, safe, and dependable — so you can focus on the experience, not the process.</p>
        <a href="#cta" class="btn btn-primary-brand mt-3">Get Started <i class="bi bi-arrow-right ms-1"></i></a>
      </div>
      <div class="col-lg-6 offset-lg-1">
        <div class="standout-item">
          <div class="standout-num">1</div>
          <div>
            <h5>Wide Selection of Event Tickets</h5>
            <p>Concerts, sports, theater and festivals, with listings that change as sellers add and remove tickets.</p>
          </div>
        </div>
        <div class="standout-item">
          <div class="standout-num">2</div>
          <div>
            <h5>Backed by TicketNetwork</h5>
            <p>Listings come from the TicketNetwork marketplace and orders are covered by its 100% guarantee.</p>
          </div>
        </div>
        <div class="standout-item">
          <div class="standout-num">3</div>
          <div>
            <h5>Secure & Reliable Booking Process</h5>
            <p>Checkout is hosted by TicketNetwork, so you enter payment details on its checkout page.</p>
          </div>
        </div>
        <div class="standout-item">
          <div class="standout-num">4</div>
          <div>
            <h5>Email Support</h5>
            <p>Write to us before or after your purchase and a member of our team will reply by email.</p>
          </div>
        </div>
        <div class="standout-item">
          <div class="standout-num">5</div>
          <div>
            <h5>Easy & Fast User Experience</h5>
            <p>From search to checkout in minutes, on any device.</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ═══════════════ CTA STRIP ═══════════════ -->
<section class="cta-strip py-5" id="cta">
  <div class="container position-relative" style="z-index:1">
    <div class="row align-items-center g-4">
      <div class="col-lg-8">
        <h2 class="mb-2">Book Your Next Event with Confidence</h2>
        <p class="mb-0">Ready to find your seats? Browse events, compare sections and prices, and review the full cost at checkout before you pay.</p>
        <div class="d-flex flex-wrap gap-3 mt-3">
          <img src="/images/event-concert.jpg" class="img-fluid rounded" style="max-width:180px;" alt="Concert tickets made ticket buying simple" loading="lazy" width="800" height="512" decoding="async">
          <img src="/images/crowd-at-concert-or-event.webp" class="img-fluid rounded" style="max-width:180px;" alt="Fans who found ticket buying simple with Seat Outlet" loading="lazy" width="442" height="442" decoding="async">
        </div>
      </div>
      <div class="col-lg-4 text-lg-end">
        <a href="/" class="btn btn-white btn-lg">
          <i class="bi bi-ticket-perforated me-2"></i>Find Tickets Near You
        </a>
      </div>
    </div>
  </div>
</section>

<?php soSeoCopy('how-to-buy-tickets-online'); ?>
<?php include 'footer.php'; ?>