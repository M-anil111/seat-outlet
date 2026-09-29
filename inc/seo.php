<title>Seat Outlet – Verified Ticket Marketplace Network for Concerts & Sports Tickets</title>
<meta name="description" content="Seat Outlet is a verified ticket marketplace network to buy concert, sports, and event tickets online. Compare prices, find deals, and book securely.">
<meta name="keywords" content="Verified Ticket Marketplace Network, buy event tickets online, concert tickets online, sports tickets marketplace, compare ticket prices online">
<link rel="canonical" href="https://beta.seatoutlet.com/">

<meta property="og:title" content="Verified Ticket Marketplace Network | Seat Outlet">
<meta property="og:description" content="Compare ticket prices and buy event tickets online securely.">
<meta property="og:url" content="https://beta.seatoutlet.com/">
<meta property="og:type" content="website">
<meta property="og:image" content="https://beta.seatoutlet.com/images/seatoutlet-logo.webp">

<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="Seat Outlet Ticket Marketplace">
<meta name="twitter:description" content="Buy tickets for concerts, sports, and events online.">
<meta name="twitter:image" content="https://beta.seatoutlet.com/images/seatoutlet-logo.webp">

<?php
$images = [
  "https://beta.seatoutlet.com/images/home-slider.webp",
  "https://beta.seatoutlet.com/images/hunt-tickets.webp",
  "https://beta.seatoutlet.com/images/lite.webp",
  "https://beta.seatoutlet.com/images/mindshare-logo.webp",
  "https://beta.seatoutlet.com/images/viralpep.webp",
  "https://beta.seatoutlet.com/images/jimbeam.webp",
  "https://beta.seatoutlet.com/images/gtn.webp",
  "https://beta.seatoutlet.com/images/ticket-scanner.webp",
  "https://beta.seatoutlet.com/images/ticketnetwork.webp"
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
            "image" => "https://beta.seatoutlet.com" . $venue['image'],
            "address" => [
                "@type" => "PostalAddress",
                "addressLocality" => $venue['city'],
                "addressRegion" => $venue['state'],
                "addressCountry" => "US"
            ],
            "url" => "https://beta.seatoutlet.com/venue/" . $venue['slug']
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
      $price = !empty($event['price']) ? $event['price'] : "50";

      $eventsSchema[] = [
          "@type" => "Event",
          "name" => $event['name'],
          "startDate" => $startDate,
          "eventStatus" => "https://schema.org/EventScheduled",

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

          "image" => $event['placeholder'] ?? "",

          "performer" => [
              "@type" => "PerformingGroup",
              "name" => $event['performer'] ?? $event['name']
          ],

          "offers" => [
              "@type" => "Offer",
              "url" => "https://beta.seatoutlet.com/event/" . $event['id'],
              "price" => $price,
              "priceCurrency" => "USD",
              "availability" => "https://schema.org/InStock"
          ]
      ];

      $imageCacheKey = 'so_img_' . md5($tab . '|' . $event['name'] . '|' . $event['performer']);
      $cachedImage = get_image($imageCacheKey);
      if ($cachedImage) {
          $imageSchema[] = [
              "@type" => "ImageObject",
              "url" => $cachedImage
          ];
      }
  }
}

$topPerformersData = json_decode(file_get_contents('cache/top_performers.json'), true);
$base = "https://beta.seatoutlet.com";

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
        ["@type" => "ListItem", "position" => 1, "item" => ["@type" => "Place", "name" => "New York, NY", "url" => "https://beta.seatoutlet.com/city/new-york-3027"]],
        ["@type" => "ListItem", "position" => 2, "item" => ["@type" => "Place", "name" => "Los Angeles, CA", "url" => "https://beta.seatoutlet.com/city/los-angeles-2551"]],
        ["@type" => "ListItem", "position" => 3, "item" => ["@type" => "Place", "name" => "Chicago, IL", "url" => "https://beta.seatoutlet.com/city/chicago-915"]],
        ["@type" => "ListItem", "position" => 4, "item" => ["@type" => "Place", "name" => "Houston, TX", "url" => "https://beta.seatoutlet.com/city/houston-2013"]],
        ["@type" => "ListItem", "position" => 5, "item" => ["@type" => "Place", "name" => "Phoenix, AZ", "url" => "https://beta.seatoutlet.com/city/phoenix-3396"]],
        ["@type" => "ListItem", "position" => 6, "item" => ["@type" => "Place", "name" => "Philadelphia, PA", "url" => "https://beta.seatoutlet.com/city/philadelphia-3394"]],
        ["@type" => "ListItem", "position" => 7, "item" => ["@type" => "Place", "name" => "San Antonio, TX", "url" => "https://beta.seatoutlet.com/city/san-antonio-3846"]],
        ["@type" => "ListItem", "position" => 8, "item" => ["@type" => "Place", "name" => "San Diego, CA", "url" => "https://beta.seatoutlet.com/city/san-diego-3854"]],
        ["@type" => "ListItem", "position" => 9, "item" => ["@type" => "Place", "name" => "Dallas, TX", "url" => "https://beta.seatoutlet.com/city/dallas-1121"]],
        ["@type" => "ListItem", "position" => 10, "item" => ["@type" => "Place", "name" => "Jacksonville, FL", "url" => "https://beta.seatoutlet.com/city/jacksonville-2108"]],
        ["@type" => "ListItem", "position" => 11, "item" => ["@type" => "Place", "name" => "Fort Worth, TX", "url" => "https://beta.seatoutlet.com/city/fort-worth-1558"]],
        ["@type" => "ListItem", "position" => 12, "item" => ["@type" => "Place", "name" => "San Jose, CA", "url" => "https://beta.seatoutlet.com/city/san-jose-3862"]],
        ["@type" => "ListItem", "position" => 13, "item" => ["@type" => "Place", "name" => "Austin, TX", "url" => "https://beta.seatoutlet.com/city/austin-247"]],
        ["@type" => "ListItem", "position" => 14, "item" => ["@type" => "Place", "name" => "Charlotte, NC", "url" => "https://beta.seatoutlet.com/city/charlotte-880"]],
        ["@type" => "ListItem", "position" => 15, "item" => ["@type" => "Place", "name" => "Columbus, OH", "url" => "https://beta.seatoutlet.com/city/columbus-1025"]],
        ["@type" => "ListItem", "position" => 16, "item" => ["@type" => "Place", "name" => "Indianapolis, IN", "url" => "https://beta.seatoutlet.com/city/indianapolis-2061"]],
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
    "@id" => "https://beta.seatoutlet.com/#webpage",
    "url" => "https://beta.seatoutlet.com/",
    "name" => "Verified Ticket Marketplace Network for Concerts & Sports Tickets",
    "isPartOf" => ["@id" => "https://beta.seatoutlet.com/#website"],
    "about" => ["@id" => "https://beta.seatoutlet.com/#organization"],
    "primaryImageOfPage" => [
        "@type" => "ImageObject",
        "url" => "https://beta.seatoutlet.com/images/seatoutlet-logo.webp"
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
