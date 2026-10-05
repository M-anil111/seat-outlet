<?php // Include-only file: answer 404 if it is requested directly over the web (it would render a fragment or an error).
if (PHP_SAPI !== 'cli' && isset($_SERVER['SCRIPT_FILENAME']) && realpath($_SERVER['SCRIPT_FILENAME']) === __FILE__) { http_response_code(404); exit; } ?>
<title>Buy Event Tickets&#x2014;Concerts, Sports &amp; Theater&#x2014;Seat Outlet</title>
<meta name="description" content="Buy event tickets for concerts, sports, theater and festivals. Compare seats and prices side by side, check out securely and get our 100% guarantee.">
<meta name="keywords" content="Buy event tickets online, concert tickets online, sports tickets marketplace, compare ticket prices online">
<link rel="canonical" href="<?php echo HOME_URL; ?>/">

<meta property="og:title" content="Buy Event Tickets&#x2014;Concerts, Sports &amp; Theater&#x2014;Seat Outlet">
<meta property="og:description" content="Buy event tickets for concerts, sports, theater and festivals. Compare seats and prices side by side, check out securely and get our 100% guarantee.">
<meta property="og:url" content="<?php echo HOME_URL; ?>/">
<meta property="og:type" content="website">
<meta property="og:image" content="<?php echo HOME_URL; ?>/images/seatoutlet-logo.webp">

<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="Buy Event Tickets&#x2014;Concerts, Sports &amp; Theater&#x2014;Seat Outlet">
<meta name="twitter:description" content="Buy event tickets for concerts, sports, theater and festivals. Compare seats and prices side by side, check out securely and get our 100% guarantee.">
<meta name="twitter:image" content="<?php echo HOME_URL; ?>/images/seatoutlet-logo.webp">

<?php
$images = [
  HOME_URL . "/images/home-slider.webp",
  HOME_URL . "/images/hunt-tickets.webp",
  HOME_URL . "/images/lite.webp",
  HOME_URL . "/images/mindshare-logo.webp",
  HOME_URL . "/images/viralpep.webp",
  HOME_URL . "/images/jimbeam.webp",
  HOME_URL . "/images/gtn.webp",
  HOME_URL . "/images/ticket-scanner.webp",
  HOME_URL . "/images/ticketnetwork.webp"
];

$imageSchema = [];

foreach ($images as $img) {
    $imageSchema[] = [
        "@type" => "ImageObject",
        "url" => $img
    ];
}

$venues = json_decode(file_get_contents('cache/top_venues.json'), true);

$itemListVenues = [];

foreach ($venues as $index => $venue) {
    $itemListVenues[] = [
        "@type" => "ListItem",
        "position" => $index + 1,
        "item" => [
            "@type" => "Place",
            "name" => $venue['name'],
            "image" => HOME_URL . $venue['image'],
            "address" => [
                "@type" => "PostalAddress",
                "addressLocality" => $venue['city'],
                "addressRegion" => $venue['state'],
                "addressCountry" => "US"
            ],
            "url" => HOME_URL . "/venue/" . $venue['slug']
        ]
    ];

    $imageCacheKey = 'so_img_' . md5('venue|' . $venue['name']);
    $cachedImage = get_image($imageCacheKey);
    if ($cachedImage) {
        $imageSchema[] = [
            "@type" => "ImageObject",
            "url" => $cachedImage
        ];
    }
}

$venuesSchema = [
    "@type" => "ItemList",
    "name" => "Top Venues",
    "itemListElement" => $itemListVenues
];

$eventFiles = [
  'cache/home_events_concerts.json',
  'cache/home_events_sports.json',
  'cache/home_events_theatre.json',
  'cache/home_events_festival.json'
];

$eventsSchema = [];

foreach ($eventFiles as $file) {

  if (!file_exists($file)) continue;

  $tab = str_replace(['cache/home_events_', '.json'], '', $file);

  $data = json_decode(file_get_contents($file), true);

  if (!is_array($data)) continue;

  foreach ($data as $event) {

      // Skip bad/stale dates.
      if (empty($event['edate']) || $event['edate'] < time()) continue;

      $locParts = explode(',', $event['loc'] ?? '');
      $city = trim($locParts[0] ?? '');
      $state = trim($locParts[1] ?? '');

      $startDate = date('Y-m-d', $event['edate']);
      $price = $event['price'] ?? null;   // "$10" style text from the cached feed; seoOffer() parses it, no invented default

      $eventsSchema[] = [
          "@type" => "Event",
          "name" => $event['name'],
          "startDate" => $startDate,
          "endDate" => $startDate,   // the feed has the day only: an event with no time of day ends on its day
          "description" => trim($event['name'] . ' tickets on ' . date('l, F j, Y', $event['edate']) . (!empty($event['venue']) ? ' at ' . $event['venue'] : '') . ($city !== '' ? ' in ' . $city . ($state !== '' ? ', ' . $state : '') : '') . '. Compare seats and prices from many sellers on Seat Outlet.'),
          "image" => [HOME_URL . (['concerts' => '/images/event-concert.jpg', 'sports' => '/images/team-event.webp', 'theatre' => '/images/loews-theatre.webp', 'festival' => '/images/festival-1.webp'][$tab] ?? '/images/crowd-at-concert-or-event.webp')],
          "eventStatus" => "https://schema.org/EventScheduled",
          "organizer" => ["@type" => "Organization", "name" => (string) ($event['venue'] ?? 'Venue')],

          "location" => [
              "@type" => "Place",
              "name" => $event['venue'],
              "address" => [
                  "@type" => "PostalAddress",
                  "addressLocality" => $city,
                  "addressRegion" => $state,
                  "addressCountry" => "US"
              ]
          ],


          "performer" => [
              "@type" => "PerformingGroup",
              "name" => $event['performer'] ?? $event['name']
          ],

      ];
      $offer = seoOffer(HOME_URL . "/event/" . soEventSlug($event), $price);
      if ($offer) {
          $eventsSchema[array_key_last($eventsSchema)]["offers"] = $offer;
      }

      $imageCacheKey = 'so_img_' . md5($tab . '|' . $event['name'] . '|' . $event['performer']);
      $cachedImage = get_image($imageCacheKey);
      if ($cachedImage) {
          $imageSchema[] = [
              "@type" => "ImageObject",
              "url" => $cachedImage
          ];
          $eventsSchema[array_key_last($eventsSchema)]["image"] = $cachedImage;
      }
  }
}

$topPerformersData = json_decode(file_get_contents('cache/top_performers.json'), true);
$base = HOME_URL;

function buildItemList($items, $title, $base) {
    $list = [];

    foreach ($items as $index => $item) {
        $list[] = [
            "@type" => "ListItem",
            "position" => $index + 1,
            "item" => [
                "@type" => "PerformingGroup",
                "name" => $item['name'],
                "url" => $base . "/performer/" . $item['slug']
            ]
        ];
    }

    return [
        "@type" => "ItemList",
        "name" => $title,
        "itemListElement" => $list
    ];
}

$performerLists = [];

if (!empty($topPerformersData['concerts'])) {
    $performerLists[] = buildItemList($topPerformersData['concerts'], "Top Concert Performers", $base);
}

if (!empty($topPerformersData['sports'])) {
    $performerLists[] = buildItemList($topPerformersData['sports'], "Top Sports Performers", $base);
}

if (!empty($topPerformersData['theater'])) {
    $performerLists[] = buildItemList($topPerformersData['theater'], "Top Theater Performers", $base);
}

$popularCities = [
    "@type" => "ItemList",
    "name" => "Popular Cities",
    "itemListElement" => [
        // Clean registry slugs (no ids in URLs): [label, ticket API city id]
        ...array_map(fn($c, $i) => ["@type" => "ListItem", "position" => $i + 1, "item" => ["@type" => "Place", "name" => $c[0], "url" => HOME_URL . "/city/" . soSlug('city', $c[0], $c[1])]],
            $soPopularCityDefs = [["New York, NY", 3027], ["Los Angeles, CA", 2551], ["Chicago, IL", 915], ["Houston, TX", 2013], ["Phoenix, AZ", 3396], ["Philadelphia, PA", 3394], ["San Antonio, TX", 3846], ["San Diego, CA", 3854], ["Dallas, TX", 1121], ["Jacksonville, FL", 2108], ["Fort Worth, TX", 1558], ["San Jose, CA", 3862], ["Austin, TX", 247], ["Charlotte, NC", 880], ["Columbus, OH", 1025], ["Indianapolis, IN", 2061]], array_keys($soPopularCityDefs)),
    ],
];

$categoryLists = [
    [
        "@type" => "ItemList",
        "name" => "Concert Categories",
        "itemListElement" => array_map(fn($n, $i) => ["@type" => "ListItem", "position" => $i + 1, "item" => ["@type" => "Thing", "name" => $n]],
            ["Reggae / Reggaeton", "Religious", "50s / 60s Era", "Children / Family", "New Age", "Bluegrass", "Performance Series", "Holiday"],
            array_keys(["Reggae / Reggaeton", "Religious", "50s / 60s Era", "Children / Family", "New Age", "Bluegrass", "Performance Series", "Holiday"])),
    ],
    [
        "@type" => "ItemList",
        "name" => "Sports Categories",
        "itemListElement" => array_map(fn($n, $i) => ["@type" => "ListItem", "position" => $i + 1, "item" => ["@type" => "Thing", "name" => $n]],
            ["Golf", "Baseball", "Olympics", "Cricket", "Gymnastics", "Rugby", "Tennis", "Mixed Martial Arts"],
            array_keys(["Golf", "Baseball", "Olympics", "Cricket", "Gymnastics", "Rugby", "Tennis", "Mixed Martial Arts"])),
    ],
    [
        "@type" => "ItemList",
        "name" => "Theatre Categories",
        "itemListElement" => array_map(fn($n, $i) => ["@type" => "ListItem", "position" => $i + 1, "item" => ["@type" => "Thing", "name" => $n]],
            ["Musical / Play", "Broadway", "Children / Family", "Off-Broadway", "Ballet", "Opera", "Cirque Du Soleil", "Dance"],
            array_keys(["Musical / Play", "Broadway", "Children / Family", "Off-Broadway", "Ballet", "Opera", "Cirque Du Soleil", "Dance"])),
    ],
    [
        "@type" => "ItemList",
        "name" => "Festivals",
        "itemListElement" => array_map(fn($n, $i) => ["@type" => "ListItem", "position" => $i + 1, "item" => ["@type" => "Thing", "name" => $n]],
            ["Austin City Limits Festival", "Festival of Monologues", "Festival of Laughs", "Festival Ballet Providence", "Festival of Dance", "Festival Chorale Oregon", "Festival Orchestra Pops", "Festival of Seasons"],
            array_keys(["Austin City Limits Festival", "Festival of Monologues", "Festival of Laughs", "Festival Ballet Providence", "Festival of Dance", "Festival Chorale Oregon", "Festival Orchestra Pops", "Festival of Seasons"])),
    ],
];

$webPageSchema = [
    "@type" => "WebPage",
    "@id" => HOME_URL . "/#webpage",
    "url" => HOME_URL . "/",
    "name" => "Buy Event Tickets for Concerts, Sports & Theater",
    "isPartOf" => ["@id" => HOME_URL . "/#website"],
    "about" => ["@id" => HOME_URL . "/#organization"],
    "primaryImageOfPage" => [
        "@type" => "ImageObject",
        "url" => HOME_URL . "/images/seatoutlet-logo.webp"
    ],
    "description" => "Buy event tickets online, explore concert tickets online, browse a sports tickets marketplace, and compare ticket prices online."
];
?>
<!-- ============================
STRUCTURED DATA (JSON-LD)
============================ -->

<?php
outputJsonLdGraph(array_merge(
    [
        buildOrganizationSchema(),
        buildWebsiteSchema(),
        $webPageSchema,
        $popularCities,
        $venuesSchema,
    ],
    $eventsSchema,
    $imageSchema,
    $performerLists,
    $categoryLists
));
