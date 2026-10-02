<?php
require_once __DIR__ . '/inc/contact.php';
soContactRoute();   // handles the enquiry POST before any output
include 'header.php';
?>

<div class="why-us-page">
<section class="hero-so-why">
    <div class="container">
        <div class="row align-items-center">
  <div class="hero-left col-lg-6 col-xl-6 col-xxl-6">
    <h1 class="main-title mb-lg-4 mb-3 text-white">Partner With Seat Outlet</h1>
    <p class="text-white mb-4">Seat Outlet is an online resale ticket marketplace. If you run a venue, festival, team or business and want to talk about working together, send us a note and we will read it.</p>
    <a href="#partner-form" class="btn btn-primary h-auto px-3 py-2">Contact Us</a>
  </div>

  <div class="hero-right col-lg-6 col-xl-6 col-xxl-6">
    <div class="hero-visual position-relative mx-auto">
              <div class="hero-circle hero-circle-lg"></div>
              <div class="hero-circle hero-circle-sm"></div>
            </div>
  </div>
  </div>
    </div>
</section>

<!-- WHAT WE ARE -->
<section class="partnership container mt-5">
  <div class="partnership-left">
    <h2>What Seat Outlet is</h2>
  </div>
  <div class="partnership-right">
    <p>Seat Outlet is a resale marketplace for concerts, sports, theater and festivals. The events, seats and prices on the site come from the TicketNetwork marketplace, and orders are fulfilled through TicketNetwork and covered by its 100% guarantee (see our <a href="/worry-free-guarantee">guarantee page</a>).</p>
    <p>Because it is a resale marketplace, prices are set by sellers and may be above or below face value. We also publish plain-English buying guides on our <a href="/blog">blog</a>.</p>
  </div>
</section>

<!-- WHAT WE ARE NOT -->
<div class="grid-section container mb-5">
  <div class="card-text">
    <div class="section-tag text-white fw-bolder">Before you write</div>
    <h3>We are an independent marketplace, not a box office.</h3>
    <p class="text-white mb-3">
      Seat Outlet is not affiliated with any venue, team or artist, and we do not issue tickets for events or run box offices.
      We do not publish fixed fees, rates or exclusivity terms for partners. If you think a conversation makes sense, tell us who you are,
      what you run and what you have in mind, and we will reply by email.
    </p>
    <a href="/about-seat-outlet">About Seat Outlet <span class="arrow">&rarr;</span></a>
  </div>
  <div class="card-stat">
    <img src="/images/crowd-at-concert-or-event.webp" class="img-fluid rounded" alt="Fans cheering at a live event" loading="lazy" width="442" height="442" decoding="async">
  </div>
</div>

<!-- ENQUIRY FORM -->
<section class="hero-so-why-bottom" id="partner-form">
<div class="container">
 <div class="text-center mb-4">
    <h2 class="text-white">Tell us about your idea</h2>
    <p class="text-white mb-3" style="max-width:640px; margin-left:auto; margin-right:auto;">
      This goes to the same inbox as our customer support. Please do not include card numbers or passwords.
    </p>
 </div>
 <?php echo soContactForm(['subject' => 'partnership', 'idp' => 'pf', 'class' => 'so-cf--card', 'title' => 'Partnership enquiry', 'intro' => 'Tell us who you are and what you have in mind.', 'button' => 'Send enquiry']); ?>
</div>
</section>

</div>

<script src="<?php echo soContactH(soAsset('js/contact-form.js')); ?>" defer></script>
<?php soSeoCopy('ticket-partner-program'); ?>
<?php include 'footer.php'; ?>
