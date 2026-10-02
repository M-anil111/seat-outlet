<?php // Include-only file: answer 404 if it is requested directly over the web (it would render a fragment or an error).
if (PHP_SAPI !== 'cli' && isset($_SERVER['SCRIPT_FILENAME']) && realpath($_SERVER['SCRIPT_FILENAME']) === __FILE__) { http_response_code(404); exit; } ?>
<footer class="tm-footer">

  <div class="tm-footer-top">

    <!-- Column 1 -->
    <div class="tm-footer-col brand">
      <div class="logo fs-2" style="width:256px; height:auto;"> <a href="/" class="tm-logo"><img src="/images/seatoutlet-logo.webp" alt="Seat Outlet" width="256" height="38"></a></div>

      <p class="section-title">Let’s connect</p>
      <div class="social-icons">
        <a href="https://www.facebook.com/profile.php?id=61588886945534" aria-label="Facebook" target="_blank"><i class="bi bi-facebook fs-4"></i></a>
        <a href="https://www.youtube.com/@SeatOutlet" aria-label="Youtube" target="_blank"><i class="bi bi-youtube fs-4"></i></a>
        <a href="https://www.instagram.com/seatoutlet/" aria-label="Instagram" target="_blank"><i class="bi bi-instagram fs-4"></i></a>
        <a href="https://linktr.ee/seatoutlet" aria-label="Linktree" class="google-icon" target="_blank"><svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1" width="30" height="25" id="Layer_1" x="0px" y="0px" viewBox="0 0 80 97.7" style="fill: #e0e0e0;" xml:space="preserve">
 <path d="M0.2,33.1h24.2L7.1,16.7l9.5-9.6L33,23.8V0h14.2v23.8L63.6,7.1l9.5,9.6L55.8,33H80v13.5H55.7l17.3,16.7l-9.5,9.4L40,49.1  L16.5,72.7L7,63.2l17.3-16.7H0V33.1H0.2z M33.1,65.8h14.2v32H33.1V65.8z">
 </path>
</svg></a>
      </div>
     

     

      <p class="terms">
        By continuing past this page, you agree to our
        <a href="/terms-and-conditions" aria-label="Terms of use">terms of use</a>
      </p>
    </div>

    <!-- Column 2 -->
    <div class="tm-footer-col">
  <h3 class="footer-heading">Trust</h3>
  <div class="section-divider"></div>
  <ul>
    <li><a href="/worry-free-guarantee">Guarantee</a></li>
    <li><a href="/customer-testimonials">Testimonials</a></li>
    <li><a href="/seat-outlet-reviews">Reviews</a></li>
    <li><a href="/seat-outlet-bbb">BBB</a></li>
    <li><a href="/ticket-partner-program">Why Us</a></li>
  </ul>
</div>

    <!-- Column 3 -->
    <div class="tm-footer-col">
  <h3 class="footer-heading">Our Network</h3>
  <div class="section-divider"></div>
  <ul>
    <li><a href="/grab-tickets-now">Grab Tickets Now</a></li>
    <li><a href="/ticket-deals">Ticket Deals</a></li>
    <li><a href="/hunt-tickets">Hunt Tickets</a></li>
    <li><a href="/ticket-scanner">Ticket Scanner</a></li>
    <li><a href="/ticket-buyer-protection">Buyer Protection</a></li>
  </ul>
</div>

    <!-- Column 4 -->
    <div class="tm-footer-col">
  <h3 class="footer-heading">About Us</h3>
  <div class="section-divider"></div>
  <ul>
    <li><a href="/about-seat-outlet">Who we are</a></li>
    <li><a href="/how-to-buy-tickets-online">What we do</a></li>
    <li><a href="/ticket-faq">FAQ's</a></li>
    <li><a href="/ticket-customer-service">Contact</a></li>
    <li><a href="/blog">Blog</a></li>
  </ul>
</div>
    

    <!-- Column 5 -->
    <div class="tm-footer-col">
  <h3 class="footer-heading">Tickets</h3>
  <div class="section-divider"></div>
  <ul>
    <li><a href="/game-day-tickets">Sports</a></li>
    <li><a href="/concert-tickets-for-sale">Concerts</a></li>
    <li><a href="/buy-broadway-tickets">Theater</a></li>
    <li><a href="/upcoming-music-festivals">Festivals</a></li>
    <li><a href="/all-artists-and-teams">Artists &amp; Teams</a></li>
    <li><a href="/city-events">Cities</a></li>
    <li><a href="/tickets-promo-code">Deals & Promotions</a></li>
    <li><a href="/our-network">Our Network</a></li>
  </ul>
</div>

  </div>
  <div class="policies">
  <ul>
    <li><a href="/privacy-policy">Privacy Policy</a></li>
    <li><a href="/terms-and-conditions">Terms of Use</a></li>
    <li><a href="/cookie-policy">Cookie Policy</a></li>
    <li><a href="/sitemap.php">Sitemap</a></li>
  </ul>
  </div>
  <!-- Divider -->
  <div class="tm-footer-divider"></div>
  <!-- Bottom bar -->
  <div class="tm-footer-bottom d-flex align-items-center">
      <div class="d-flex align-items-center">
        <div class="copyright me-2">
            <span class="link-tag"> © <?php echo date('Y'); ?> SeatOutlet. All rights reserved.</span>
            <span class="link-tag geo-attribution d-block small">This product includes GeoLite2 data created by MaxMind, available from <a href="https://www.maxmind.com" rel="nofollow noopener" target="_blank">https://www.maxmind.com</a>.</span>
        </div>
        <div class="tm-country">
            <button type="button" class="btn btn-link">
              <svg fill="none" viewBox="0 0 512 512" width="1.5em" height="1.5em" aria-hidden="true" class="sc-fc75cc60-5 lbOkgz me-1">
                  <path fill="#FFF" d="M503.2 322.8c5.7-21.3 8.8-43.7 8.8-66.8l-8.8-66.8a254.6 254.6 0 0 0-28.8-66.8l-59-66.7A255 255 0 0 0 256 0h-.2A255 255 0 0 0 96.6 55.7l-59 66.7a254.6 254.6 0 0 0-28.8 66.8L0 256v.1c0 23 3 45.4 8.8 66.7l28.8 66.8a257.3 257.3 0 0 0 59 66.7L256 512l159.4-55.7a257.3 257.3 0 0 0 59-66.7z"></path>
                  <path fill="#D80027" d="M503.2 189.2c5.7 21.3 8.8 43.7 8.8 66.8H0c0-23.1 3-45.5 8.8-66.8zM415.4 55.7a257.3 257.3 0 0 1 59 66.7H37.6a257.3 257.3 0 0 1 59-66.7zm59 333.9c12.6-20.6 22.4-43 28.8-66.8H8.8a254.6 254.6 0 0 0 28.8 66.8zm-59 66.7H96.6A255 255 0 0 0 255.8 512h.4a255 255 0 0 0 159.2-55.7"></path>
                  <path fill="#0052B4" d="M0 245.6A256 256 0 0 1 256 0v256H0z"></path>
                  <path fill="#FFF" fill-rule="evenodd" d="M109.5 46a256 256 0 0 1 26.2-16l1 3h27.8L142 49.2l8.7 26.6L128 59.5l-22.6 16.4 8.6-26.6zm-80 90.4c6-11.1 12.7-21.8 20.1-32l3.8 11.7h28l-22.7 16.4 8.7 26.6-22.6-16.4L22.2 159l7.4-22.7Zm181.7-130 8.6 26.5h28L225 49.3l8.7 26.6-22.6-16.4-22.6 16.4 8.7-26.6L174.7 33h27.9l8.6-26.5ZM128 89.6l8.6 26.5h28l-22.7 16.4 8.7 26.6-22.6-16.4-22.6 16.4 8.6-26.6-22.5-16.4h27.9zm91.8 26.5-8.6-26.5-8.6 26.5h-28l22.7 16.4-8.7 26.6 22.6-16.4 22.6 16.4-8.7-26.6 22.6-16.4zm-175 56.7 8.6 26.5h28l-22.7 16.4 8.7 26.6-22.6-16.4-22.6 16.4 8.7-26.6-22.6-16.4h27.9zm91.8 26.5-8.6-26.5-8.6 26.5h-28l22.6 16.4-8.6 26.6 22.6-16.4 22.6 16.4-8.7-26.6 22.6-16.4zm74.6-26.5 8.6 26.5h28L225 215.7l8.7 26.6-22.6-16.4-22.6 16.4 8.7-26.6-22.6-16.4h27.9l8.6-26.5Z" clip-rule="evenodd"></path>
              </svg>
              US
            </button>
        </div>
      </div>
      <div class="keyword-bottombar">
        <div class="text-white text-center">
            <p><?php echo htmlspecialchars(soKeywordLabel($GLOBALS['soFocusKw'] ?? soFocusKeyword()), ENT_QUOTES, 'UTF-8'); ?></p>
        </div>
      </div>
      <div class="d-flex align-items-center flex-wrap creater">
        <div class="space-between d-flex pe-2">
          Website Designed by 
          <a class="px-2 footer-bottom-logo" style="color: #e1c24e;" href="https://www.jaymehta.co/" target="_blank" title="Jay Mehta Digital">
            <img src="/images/jm.webp" alt="Website Design Service by Jay Mehta Digital" style="max-width:100px;" width="100" height="19">
          </a> | 
        </div> 
        <div class="space-between d-flex">
          Developed & Maintained by 
          <a class="px-2 footer-bottom-logo" title="Mindshare Consulting" href="https://www.mindshare.consulting/" target="_blank" > 
            <img src="/images/mindshare-logo-230.webp" alt="Mindshare Consulting" style="max-width:100px;" width="100" height="22" loading="lazy">
          </a>
        </div>
      </div>
  </div>

    

</footer>

<?php
// Decide which scripts a page needs from its PATH. These checks used to compare
// the whole REQUEST_URI, so any query string (/search?q=adele, /tickets?when=week)
// silently dropped search.js and events-listing.js, and /concerts, /sports,
// /theater, /festival, /state/* and /country/* never loaded events-listing.js at
// all, so their "More Events" button did nothing.
$soPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$soIsHome = ($soPath === '/' || $soPath === '/index.php');
$soIsSearch = ($soPath === '/search');
$soHasEventList = (bool) preg_match('#^/(search|buy-tickets-online|concert-tickets-for-sale|game-day-tickets|buy-broadway-tickets|upcoming-music-festivals)$|^/(artist|category|venue|city|state|country)/|^/[a-z]+-(city|state|country|venue)/|^/artist-(city|state|country|venue)/#', $soPath);
?>
<script src="/lib/jquery/3.7.1/jquery.min.js" defer></script>
<script src="/lib/bootstrap/5.3.8/bootstrap.bundle.min.js" defer></script>
<script>window.SO_ASSETS = { flatpickrJs: "/lib/flatpickr/4.6.13/flatpickr.min.js", flatpickrCss: "/lib/flatpickr/4.6.13/flatpickr.min.css" };</script>
<?php if ($soIsHome || $soIsSearch || $soPath === '/about-seat-outlet') { ?>
  <script src="/lib/slick-carousel/1.8.1/slick.min.js" defer></script>
<?php } ?>
<script src="<?php echo htmlspecialchars(soAsset('js/main.js'), ENT_QUOTES, 'UTF-8'); ?>" defer></script>
<script>
// Real-user speed measurement (js/vitals.js) is loaded after the page has finished loading and the browser is idle, so it can never
// delay first paint or add blocking time (a deferred script in the <head> measurably did). The browser buffers the early entries
// (paint, LCP, layout shifts), so nothing is lost. Visitors who leave before the load event are not measured.
window.addEventListener('load', function () {
    var go = function () { var s = document.createElement('script'); s.src = <?php echo json_encode(soAsset('js/vitals.js')); ?>; s.async = true; document.head.appendChild(s); };
    if ('requestIdleCallback' in window) { requestIdleCallback(go, { timeout: 4000 }); } else { setTimeout(go, 2000); }
});
</script>
<?php if ($soIsHome) { ?>
    <script src="<?php echo htmlspecialchars(soAsset('js/home.js'), ENT_QUOTES, 'UTF-8'); ?>" defer></script>
<?php } ?>
<?php if (strpos($soPath, '/event/') === 0) { ?>
    <script src="<?php echo htmlspecialchars(soAsset('js/event-actions.js'), ENT_QUOTES, 'UTF-8'); ?>" defer></script>
<?php } ?>
<?php if (preg_match('#^/(event|artist)/#', $soPath)) { ?>
    <script src="<?php echo htmlspecialchars(soAsset('js/idle-nudge.js'), ENT_QUOTES, 'UTF-8'); ?>" defer></script>
<?php } ?>
<?php if ($soHasEventList) { ?>
    <script src="<?php echo htmlspecialchars(soAsset('js/events-listing.js'), ENT_QUOTES, 'UTF-8'); ?>" defer></script>
    <script src="<?php echo htmlspecialchars(soAsset('js/near-you.js'), ENT_QUOTES, 'UTF-8'); ?>" defer></script>
<?php } ?>
<?php if ($soIsSearch) { ?>
    <script src="<?php echo htmlspecialchars(soAsset('js/search.js'), ENT_QUOTES, 'UTF-8'); ?>" defer></script>
<?php } ?>
<?php if (strpos($soPath, '/artist/') === 0) { ?>
    <script src="<?php echo htmlspecialchars(soAsset('js/performer.js'), ENT_QUOTES, 'UTF-8'); ?>" defer></script>
<?php } ?>

<?php 
  include 'inc/modals.php'; 
?>

<script>
	const RECAPTCHA_SITE_KEY = "<?php echo RECAPTCHA_SITE_KEY; ?>";
	let mapsPromise = null;
	function loadGoogleMapsApi() {
		if (mapsPromise) return mapsPromise;

		mapsPromise = new Promise((resolve, reject) => {
			if (window.google && google.maps && google.maps.places) {
				resolve(window.google);
				return;
			}

			const script = document.createElement('script');
			script.src = `https://maps.googleapis.com/maps/api/js?key=<?php echo GAPI_KEY; ?>&libraries=places`;
			script.async = true;
			script.defer = true;
			script.onload = () => resolve(window.google);
			script.onerror = reject;
			document.head.appendChild(script);
		});

		return mapsPromise;
	}        
  <?php if (strpos($_SERVER['REQUEST_URI'], '/event/') === 0) { ?>
    Seatics.config.checkoutUrl = <?php echo json_encode(TN_CHECKOUT_URL); ?>;
    Seatics.config.enableLegalDisclosureMobile = true;
    Seatics.config.preCheckoutButtonHtml = 'Continue to Payment';
    Seatics.config.buyButtonContentHtml = '<div class="buy-btn">' + 'Buy Now' + '</div>';
    Seatics.config.defaultSort = Seatics.SortOptions.PriceAsc;
    Seatics.config.tgMarkTooltipText = 'We recommend this seller&#039;s tickets.';
    Seatics.config.enableMyList = true;
    Seatics.config.showCents = false;
    Seatics.config.skipPrecheckoutMobile = true;
    Seatics.config.showZoomControls = true;
    Seatics.config.ticketListOnRight = true;
    Seatics.config.legendExpanded = true;
    Seatics.config.skipPrecheckoutDesktop = true;
  <?php } ?>
</script>
    
  </body>
</html>