# Launch checklist

## WS5: content, trust, blog, legal, compliance

For the owner
- Contact details: the old page showed a placeholder phone (1-800-123-4567) and street address (123 Ticket Plaza). Both were removed. Add real ones only if they exist (phone, hours, mailing address). Who: owner. Why: nothing may be invented.
- Company facts for the About page: legal entity name, founding year, address, real team names. Not on the site now because none could be verified.
- Real reviews/testimonials: none are shown. Collect them after real orders (post-purchase email) before showing any rating or AggregateRating.
- BBB: say so only if a BBB profile or accreditation exists; the page currently says it shows no rating.
- Promo codes TAKE5 and TAKE10 (shown on /tickets-promo-code and several listing pages): confirm with TicketNetwork that they exist and their minimums ($199 / $349), otherwise remove them everywhere.
- Guarantee: confirm with TicketNetwork that the four points in inc/guarantee.php match its current policy, and whether a rescheduled or postponed event is refundable.
- Fees: confirm what TicketNetwork's checkout adds (service, delivery, taxes) and when it shows them. Counsel: check "review the full cost at checkout" against the FTC rule on unfair or deceptive fees (all-in pricing).
- Email sending: set SMTP_USER and SMTP_PASS (Brevo) on the server, and optionally CONTACT_TO / CONTACT_TO_PARTNERSHIP. Without them messages are saved (see Admin > Contact Messages) but no email is sent. Make sure support@seatoutlet.com is a verified Brevo sender. Do NOT set SO_RECAPTCHA_SKIP outside local development.
- Someone must read Admin > Contact Messages (or the support inbox) daily.
- Footer: the "Sitemap" link still points to the XML file (/sitemap.php). Point it to /sitemap-page. Footer and header text still say "Trusted resale marketplace", "100% Worry-Free Guarantee", "verified tickets", and "By continuing past this page, you agree" (WS6 area): align with the guarantee wording.
- Production robots: serve robots.php at /robots.txt (docs/server-rewrites.md). The static robots.txt has no Sitemap line on purpose.
- Blog: the seven launch posts repeat their focus phrase 34 to 51 times (SEO tool now warns). Have the author vary the wording. Review dates and facts before re-publishing.
- Blog post image alt texts were written from the pictures; replace generic stock images with relevant ones when available.
- The old iContact page /newsletter and newsletter-email.php: retire or noindex (WS1 area).

For counsel (not drafted, deliberately)
- Privacy policy: US state privacy rights section (CCPA/CPRA and other states): categories, sale/share, "Do Not Sell or Share", Global Privacy Control, request methods, response times. Section on targeted advertising/retargeting: confirm whether it applies; GTM is only loaded when GTM_ID is set.
- Cookie policy: no consent banner exists; decide if one is required and which tags must wait for consent (GTM, reCAPTCHA). Name actual vendors (Google reCAPTCHA, GTM, Brevo, TicketNetwork/Seatics).
- Terms: add a guarantee clause and name TicketNetwork as fulfilment partner (terms say sales are final, the guarantee page is broader); governing law and disputes (section 19 keeps the existing Austin, Texas wording); arbitration/class waiver decision; registered postal address (placeholder line removed); replace "By continuing... you agree" browsewrap.
- Affiliation disclaimer added to legal pages and key pages; counsel to confirm wording and decide on a footer line.
- Photo/image licensing for stock pictures used on the site.
