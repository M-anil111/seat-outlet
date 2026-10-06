-- "Children / Family" exists twice in the ticket API: under Theater (id 1869, 54 events) and under Concerts (id 2094, 2 events).
-- The busy one takes the clean address /category/children-family; the small one now redirects to it (category.php). Re-runnable: each
-- step only changes the row it expects.
UPDATE url_slugs SET slug = 'children-family-swap' WHERE type = 'category' AND ext_id = '2094' AND slug = 'children-family';
UPDATE url_slugs SET slug = 'children-family' WHERE type = 'category' AND ext_id = '1869' AND slug = 'children-family-2';
UPDATE url_slugs SET slug = 'children-family-2' WHERE type = 'category' AND ext_id = '2094' AND slug = 'children-family-swap';
