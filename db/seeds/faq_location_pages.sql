-- Generic FAQ content for the Track B category/location pages
-- (concerts-city, sports-state, theater-venue, theatre-country,
-- festivals-*, event-city/events-state - see renderCategoryLocationPage()
-- in functions.php). Not applied automatically - see
-- db/seeds/page_rules_sample.sql for how to run this against beta.
-- Safe to run more than once: rows key on (type, question) via
-- faq_type_question_unique (added in db/migrations/0003_faq.sql).
--
-- [category] and [location] are substituted at render time with the
-- real category label ("Concert", "Sports", ...) and location label
-- ("Austin, TX", "Texas", "United States", venue name). No 'performer'
-- rows here - those already exist independently and are untouched.

INSERT INTO `faq` (`type`, `question`, `answer`, `created_at`, `updated_at`)
VALUES
('concerts', 'Are concert tickets in [location] guaranteed authentic?', 'Yes. Every ticket listed on Seat Outlet is verified before it goes on sale, and every order for [category] tickets in [location] is backed by our Buyer Protection Guarantee.', NOW(), NOW()),
('concerts', 'How will I receive my [category] tickets for events in [location]?', 'Most tickets are delivered electronically or by mobile transfer, so they arrive well before the event. The exact delivery method is shown on the listing before you check out.', NOW(), NOW()),
('concerts', 'What happens if a [category] event in [location] is canceled?', 'If the event is canceled and not rescheduled, you''ll receive a full refund under our Buyer Protection Guarantee.', NOW(), NOW()),
('sports', 'Are sports tickets in [location] guaranteed authentic?', 'Yes. Every ticket listed on Seat Outlet is verified before it goes on sale, and every order for [category] tickets in [location] is backed by our Buyer Protection Guarantee.', NOW(), NOW()),
('sports', 'How will I receive my [category] tickets for events in [location]?', 'Most tickets are delivered electronically or by mobile transfer, so they arrive well before the event. The exact delivery method is shown on the listing before you check out.', NOW(), NOW()),
('sports', 'What happens if a [category] event in [location] is canceled?', 'If the event is canceled and not rescheduled, you''ll receive a full refund under our Buyer Protection Guarantee.', NOW(), NOW()),
('theater', 'Are theater tickets in [location] guaranteed authentic?', 'Yes. Every ticket listed on Seat Outlet is verified before it goes on sale, and every order for [category] tickets in [location] is backed by our Buyer Protection Guarantee.', NOW(), NOW()),
('theater', 'How will I receive my [category] tickets for events in [location]?', 'Most tickets are delivered electronically or by mobile transfer, so they arrive well before the event. The exact delivery method is shown on the listing before you check out.', NOW(), NOW()),
('theater', 'What happens if a [category] event in [location] is canceled?', 'If the event is canceled and not rescheduled, you''ll receive a full refund under our Buyer Protection Guarantee.', NOW(), NOW()),
('theatre', 'Are theatre tickets in [location] guaranteed authentic?', 'Yes. Every ticket listed on Seat Outlet is verified before it goes on sale, and every order for [category] tickets in [location] is backed by our Buyer Protection Guarantee.', NOW(), NOW()),
('theatre', 'How will I receive my [category] tickets for events in [location]?', 'Most tickets are delivered electronically or by mobile transfer, so they arrive well before the event. The exact delivery method is shown on the listing before you check out.', NOW(), NOW()),
('theatre', 'What happens if a [category] event in [location] is canceled?', 'If the event is canceled and not rescheduled, you''ll receive a full refund under our Buyer Protection Guarantee.', NOW(), NOW()),
('festivals', 'Are festival tickets in [location] guaranteed authentic?', 'Yes. Every ticket listed on Seat Outlet is verified before it goes on sale, and every order for [category] tickets in [location] is backed by our Buyer Protection Guarantee.', NOW(), NOW()),
('festivals', 'How will I receive my [category] tickets for events in [location]?', 'Most tickets are delivered electronically or by mobile transfer, so they arrive well before the event. The exact delivery method is shown on the listing before you check out.', NOW(), NOW()),
('festivals', 'What happens if a [category] event in [location] is canceled?', 'If the event is canceled and not rescheduled, you''ll receive a full refund under our Buyer Protection Guarantee.', NOW(), NOW()),
('events', 'Are event tickets in [location] guaranteed authentic?', 'Yes. Every ticket listed on Seat Outlet is verified before it goes on sale, and every order for [category] tickets in [location] is backed by our Buyer Protection Guarantee.', NOW(), NOW()),
('events', 'How will I receive my [category] tickets for events in [location]?', 'Most tickets are delivered electronically or by mobile transfer, so they arrive well before the event. The exact delivery method is shown on the listing before you check out.', NOW(), NOW()),
('events', 'What happens if a [category] event in [location] is canceled?', 'If the event is canceled and not rescheduled, you''ll receive a full refund under our Buyer Protection Guarantee.', NOW(), NOW())
ON DUPLICATE KEY UPDATE
    `answer`     = VALUES(`answer`),
    `updated_at` = NOW();
