-- County pages (US only). The ticket API knows cities and states but not counties, so each city is placed in a county once, from the
-- city's coordinates, using the free U.S. Census Bureau geocoder (cron/resolve-counties.php). One row per city.
--   status   pending (waiting), ok (county found), miss (no county found; retried after 30 days)
--   events_n      upcoming events seen in the last sitemap build (busiest cities come first on a county page)
--   county_fips   the 5 digit state + county FIPS code as a number (Travis County, TX = 48453)
CREATE TABLE IF NOT EXISTS `city_counties` (
  `city_id` int NOT NULL,
  `city_name` varchar(120) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `state_abbr` char(2) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `county_fips` int DEFAULT NULL,
  `county_name` varchar(120) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `events_n` int NOT NULL DEFAULT 0,
  `status` varchar(8) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'pending',
  `checked_at` datetime DEFAULT NULL,
  PRIMARY KEY (`city_id`),
  KEY `city_counties_county` (`county_fips`),
  KEY `city_counties_status` (`status`, `checked_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
