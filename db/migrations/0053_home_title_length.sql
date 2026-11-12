-- The home page title was over the 70-character limit in the site's title rule (QA retest, issue 83). The new title keeps
-- the primary query and the brand. Only the row that still has the old title changes, so an edit made in admin is left alone.
UPDATE `page_rules`
SET `meta_title` = 'Seat Outlet – Verified Ticket Marketplace for Concert & Sports Tickets'
WHERE `url_path` = '/'
  AND `meta_title` = 'Seat Outlet – Verified Ticket Marketplace Network for Concerts & Sports Tickets';
