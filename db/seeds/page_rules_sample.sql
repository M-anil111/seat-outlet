-- Sample page_rules rows for the two pages the front-end wiring already
-- supports overrides for (see header.php + inc/seo.php / inc/seo-event.php).
-- Not applied automatically - run this against the beta database once,
-- the same way db/admin-schema.sql was applied (there's no DB access from
-- this session to do it directly). Safe to run more than once: both rows
-- use INSERT ... ON DUPLICATE KEY UPDATE against url_path's unique key.
--
-- Row 1 (homepage) intentionally mirrors the text already hardcoded in
-- inc/seo.php byte-for-byte, so applying this seed does not change what
-- visitors or crawlers see - it just moves that same content under admin
-- control at /admin/page-rules. Edit it there once you want it to say
-- something different.
--
-- Row 2 (event page) is a real sandbox event ("& Juliet" at the Royal
-- Alexandra Theatre, Toronto - TicketNetwork sandbox event ID 5223347,
-- looked up live against https://sandbox.tn-apis.com), not a fabricated
-- URL, so it's an example you can actually click through to on beta and
-- see the override take effect. Its title/description are deliberately
-- written differently from what inc/seo-event.php would auto-generate,
-- so the override is obviously visible in view-source rather than looking
-- like a coincidence.
--
-- Both rows leave `robots` NULL (inherits the site-wide "noindex,nofollow"
-- default from header.php) on purpose - this seed does not change what's
-- indexable. Flip that to 'index,follow' per-row only once you're
-- actually ready for that URL to be crawled.

INSERT INTO `page_rules`
    (`url_path`, `meta_title`, `meta_description`, `canonical_url`, `robots`, `schema_json`,
     `redirect_to`, `redirect_code`, `is_active`, `created_at`, `updated_at`)
VALUES
    (
        '/',
        'Seat Outlet – Verified Ticket Marketplace Network for Concerts & Sports Tickets',
        'Seat Outlet is a verified ticket marketplace network to buy concert, sports, and event tickets online. Compare prices, find deals, and book securely.',
        'https://beta.seatoutlet.com/',
        NULL,
        NULL,
        NULL,
        NULL,
        1,
        NOW(),
        NOW()
    ),
    (
        '/event/-Juliet-5223347',
        '& Juliet Tickets – Royal Alexandra Theatre, Toronto | Seat Outlet',
        'Get verified tickets to & Juliet at the Royal Alexandra Theatre in Toronto, ON. Compare seat prices, view showtimes, and book securely on Seat Outlet.',
        'https://beta.seatoutlet.com/event/-Juliet-5223347',
        NULL,
        NULL,
        NULL,
        NULL,
        1,
        NOW(),
        NOW()
    )
ON DUPLICATE KEY UPDATE
    `meta_title`       = VALUES(`meta_title`),
    `meta_description` = VALUES(`meta_description`),
    `canonical_url`    = VALUES(`canonical_url`),
    `robots`           = VALUES(`robots`),
    `schema_json`      = VALUES(`schema_json`),
    `redirect_to`      = VALUES(`redirect_to`),
    `redirect_code`    = VALUES(`redirect_code`),
    `is_active`        = VALUES(`is_active`),
    `updated_at`       = NOW();
