
## WS6: home page, header/footer, privacy, 404

- **Counsel review (owner to arrange):** the privacy bar copy ("We use cookies for analytics and ads ... Accept / Decline / Manage") and the "Your privacy choices" link are working defaults, not legal text. Counsel must review the wording, the US opt-out model (tags load unless Global Privacy Control or Decline), the cookie policy page, and whether California or other states need an extra "Do not sell or share" wording. The bar and footer link appear only when GTM_ID is set.
- **Global Privacy Control:** honored in the browser (navigator.globalPrivacyControl, the same signal as the Sec-GPC header): GTM is not loaded. The server cannot vary the HTML (pages are CDN-cached). Optional, after counsel agrees: publish /.well-known/gpc.json {"gpc": true}.
- **Third-party tags kept:** Google Maps loads only when a visitor uses a location field; reCAPTCHA only when a form is submitted. Both are treated as functional. Counsel to confirm.
- **Pricing claim (B8):** the "No hidden fees" claim was removed from the home page. Someone should place a test order to confirm what checkout adds, then decide on all-in price wording (FTC rule for live-event tickets: counsel).
- **404 for unknown URLs (tech support):** the web server prints a bare "File not found." Add the nginx rule in docs/server-rewrites.md (error_page 404 /404.php).
- **Other findings not done here:** B1/B3/B4 (newsletter-email.php) are replaced on the home page by the shared lead form (WS1 owns the endpoint; newsletter-email.php can be retired). B17 (/blog 500 on beta) and B18 (composer.json and /cache files readable on beta): tech support, server config. S3/S4 (event price alerts, service worker) and S5 (idle nudge) not done.
- **Slick:** already loaded only on home, search and about pages (deferred); jQuery and Bootstrap JS untouched.
