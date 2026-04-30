<title>Seat Outlet – Verified Ticket Marketplace Network for Concerts & Sports Tickets</title>
<meta name="description" content="Seat Outlet is a verified ticket marketplace network to buy concert, sports, and event tickets online. Compare prices, find deals, and book securely.">
<meta name="keywords" content="Verified Ticket Marketplace Network, buy event tickets online, concert tickets online, sports tickets marketplace, compare ticket prices online">
<link rel="canonical" href="https://beta.seatoutlet.com/">

<meta property="og:title" content="Verified Ticket Marketplace Network | Seat Outlet">
<meta property="og:description" content="Compare ticket prices and buy event tickets online securely.">
<meta property="og:url" content="https://beta.seatoutlet.com/">
<meta property="og:type" content="website">
<meta property="og:image" content="https://beta.seatoutlet.com/assets/seatoutlet-logo.webp">

<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="Seat Outlet Ticket Marketplace">
<meta name="twitter:description" content="Buy tickets for concerts, sports, and events online.">
<meta name="twitter:image" content="https://beta.seatoutlet.com/assets/seatoutlet-logo.webp">

<?php 
 
$images = [
  "https://beta.seatoutlet.com/assets/home-slider.webp",
  "https://beta.seatoutlet.com/assets/hunt-tickets.webp",
  "https://beta.seatoutlet.com/assets/lite.webp",
  "https://beta.seatoutlet.com/assets/mindshare-logo.webp",
  "https://beta.seatoutlet.com/assets/viralpep.webp",
  "https://beta.seatoutlet.com/assets/jimbeam.webp",
  "https://beta.seatoutlet.com/assets/gtn.webp",
  "https://beta.seatoutlet.com/assets/ticket-scanner.webp",
  "https://beta.seatoutlet.com/assets/ticketnetwork.webp"
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
    $imageSchema[] = [
        "@type" => "ImageObject",
        "url" => $cachedImage
    ];
}



$venues_schema = [
    "@type" => "ItemList",
    "name" => "Top Venues",
    "itemListElement" => $itemListVenues
];


$files = [
  'cache/home_events_concerts.json',
  'cache/home_events_sports.json',
  'cache/home_events_theatre.json',
  'cache/home_events_festival.json'
];

$eventsSchema = [];

foreach ($files as $file) {

  if (!file_exists($file)) continue;

  $tab = str_replace(['cache/home_events_', '.json'], '', $file);

  $data = json_decode(file_get_contents($file), true);

  if (!is_array($data)) continue;

  foreach ($data as $event) {

      // 🔥 Skip bad dates (1970 issue)
      if (empty($event['edate']) || $event['edate'] < time()) continue;

      // 🔥 Split location
      $locParts = explode(',', $event['loc'] ?? '');
      $city = trim($locParts[0] ?? '');
      $state = trim($locParts[1] ?? '');

      // 🔥 Convert UNIX → ISO
      $startDate = date('Y-m-d', $event['edate']);

      // 🔥 Price fallback
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
      $imageSchema[] = [
          "@type" => "ImageObject",
          "url" => $cachedImage
      ];
  }
}

$data = json_decode(file_get_contents('cache/top_performers.json'), true);

$base = "https://beta.seatoutlet.com";

// helper function
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

$graph = [];

if (!empty($data['concerts'])) {
    $graph[] = buildItemList($data['concerts'], "Top Concert Performers", $base);
}

if (!empty($data['sports'])) {
    $graph[] = buildItemList($data['sports'], "Top Sports Performers", $base);
}

if (!empty($data['theater'])) {
    $graph[] = buildItemList($data['theater'], "Top Theater Performers", $base);
}

?>
<!-- ============================
STRUCTURED DATA (JSON-LD)
============================ -->

<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@graph": [

    {
      "@type": "Organization",
      "@id": "https://beta.seatoutlet.com/#organization",
      "name": "Seat Outlet",
      "url": "https://beta.seatoutlet.com/",
      "aggregateRating": {
        "@type": "AggregateRating",
        "ratingValue": "4.8",
        "reviewCount": "1200",
        "bestRating": "5",
        "worstRating": "1"
      },
      "logo": {
        "@type": "ImageObject",
        "@id": "https://beta.seatoutlet.com/#logo",
        "url": "https://beta.seatoutlet.com/assets/seatoutlet-logo.webp"
      },
      "image": "https://beta.seatoutlet.com/assets/seatoutlet-logo.webp",
      "description": "Verified ticket marketplace network to buy concert, sports, and event tickets online.",
      "sameAs": [
        "https://www.facebook.com/profile.php?id=61588886945534",
        "https://www.instagram.com/seatoutlet/",
        "https://www.youtube.com/@SeatOutlet",
        "https://linktr.ee/seatoutlet"
      ],

      "review": [
        {
          "@type": "Review",
          "author": { "@type": "Person", "name": "Sarah Mitchell" },
          "reviewBody": "Super easy and reliable experience. I’ve used Seat Outlet multiple times and the process is always smooth.",
          "reviewRating": {
            "@type": "Rating",
            "ratingValue": "5",
            "bestRating": "5"
          }
        },
        {
          "@type": "Review",
          "author": { "@type": "Person", "name": "James Davidson" },
          "reviewBody": "Best place to compare ticket prices. It saved me both time and money.",
          "reviewRating": {
            "@type": "Rating",
            "ratingValue": "5",
            "bestRating": "5"
          }
        },
        {
          "@type": "Review",
          "author": { "@type": "Person", "name": "Emily Lopez" },
          "reviewBody": "Got great seats at a great price. Checkout process was simple and secure.",
          "reviewRating": {
            "@type": "Rating",
            "ratingValue": "5",
            "bestRating": "5"
          }
        },
        {
          "@type": "Review",
          "author": { "@type": "Person", "name": "Michael Rodriguez" },
          "reviewBody": "Perfect for sports fans. Easy to find tickets across sellers.",
          "reviewRating": {
            "@type": "Rating",
            "ratingValue": "5",
            "bestRating": "5"
          }
        },
        {
          "@type": "Review",
          "author": { "@type": "Person", "name": "Amanda Wilson" },
          "reviewBody": "Trusted and convenient ticket platform. Everything felt safe and straightforward.",
          "reviewRating": {
            "@type": "Rating",
            "ratingValue": "5",
            "bestRating": "5"
          }
        },
        {
          "@type": "Review",
          "author": { "@type": "Person", "name": "Robert Thompson" },
          "reviewBody": "Great experience for sports events. Pricing is competitive and easy to compare.",
          "reviewRating": {
            "@type": "Rating",
            "ratingValue": "5",
            "bestRating": "5"
          }
        }
      ]
    },

    {
      "@type": "WebSite",
      "@id": "https://beta.seatoutlet.com/#website",
      "url": "https://beta.seatoutlet.com/",
      "name": "Seat Outlet",
      "publisher": {
        "@id": "https://beta.seatoutlet.com/#organization"
      }
    },

    {
      "@type": "WebPage",
      "@id": "https://beta.seatoutlet.com/#webpage",
      "url": "https://beta.seatoutlet.com/",
      "name": "Verified Ticket Marketplace Network for Concerts & Sports Tickets",
      "isPartOf": {
        "@id": "https://beta.seatoutlet.com/#website"
      },
      "about": {
        "@id": "https://beta.seatoutlet.com/#organization"
      },
      "primaryImageOfPage": {
        "@type": "ImageObject",
        "url": "https://beta.seatoutlet.com/assets/seatoutlet-logo.webp"
      },
      "description": "Buy event tickets online, explore concert tickets online, browse a sports tickets marketplace, and compare ticket prices online."
    },

    {
      "@type": "ItemList",
      "name": "Popular Cities",
      "itemListElement": [
        
        { "@type": "ListItem", "position": 1, "item": { "@type": "Place", "name": "New York, NY", "url": "https://beta.seatoutlet.com/city/new-york-3027" } },
        { "@type": "ListItem", "position": 2, "item": { "@type": "Place", "name": "Los Angeles, CA", "url": "https://beta.seatoutlet.com/city/los-angeles-2551" } },
        { "@type": "ListItem", "position": 3, "item": { "@type": "Place", "name": "Chicago, IL", "url": "https://beta.seatoutlet.com/city/chicago-915" } },
        { "@type": "ListItem", "position": 4, "item": { "@type": "Place", "name": "Houston, TX", "url": "https://beta.seatoutlet.com/city/houston-2013" } },
        { "@type": "ListItem", "position": 5, "item": { "@type": "Place", "name": "Phoenix, AZ", "url": "https://beta.seatoutlet.com/city/phoenix-3396" } },
        { "@type": "ListItem", "position": 6, "item": { "@type": "Place", "name": "Philadelphia, PA", "url": "https://beta.seatoutlet.com/city/philadelphia-3394" } },
        { "@type": "ListItem", "position": 7, "item": { "@type": "Place", "name": "San Antonio, TX", "url": "https://beta.seatoutlet.com/city/san-antonio-3846" } },
        { "@type": "ListItem", "position": 8, "item": { "@type": "Place", "name": "San Diego, CA", "url": "https://beta.seatoutlet.com/city/san-diego-3854" } },
        { "@type": "ListItem", "position": 9, "item": { "@type": "Place", "name": "Dallas, TX", "url": "https://beta.seatoutlet.com/city/dallas-1121" } },
        { "@type": "ListItem", "position": 10, "item": { "@type": "Place", "name": "Jacksonville, FL", "url": "https://beta.seatoutlet.com/city/jacksonville-2108" } },
        { "@type": "ListItem", "position": 11, "item": { "@type": "Place", "name": "Fort Worth, TX", "url": "https://beta.seatoutlet.com/city/fort-worth-1558" } },
        { "@type": "ListItem", "position": 12, "item": { "@type": "Place", "name": "San Jose, CA", "url": "https://beta.seatoutlet.com/city/san-jose-3862" } },
        { "@type": "ListItem", "position": 13, "item": { "@type": "Place", "name": "Austin, TX", "url": "https://beta.seatoutlet.com/city/austin-247" } },
        { "@type": "ListItem", "position": 14, "item": { "@type": "Place", "name": "Charlotte, NC", "url": "https://beta.seatoutlet.com/city/charlotte-880" } },
        { "@type": "ListItem", "position": 15, "item": { "@type": "Place", "name": "Columbus, OH", "url": "https://beta.seatoutlet.com/city/columbus-1025" } },
        { "@type": "ListItem", "position": 16, "item": { "@type": "Place", "name": "Indianapolis, IN", "url": "https://beta.seatoutlet.com/city/indianapolis-2061" } }

      ]
    },

    <?= json_encode($venues_schema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT); ?>,
    <?= json_encode([
        "@context" => "https://schema.org",
        "@graph" => $eventsSchema
    ], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT); ?>,
    <?= json_encode([
      "@context" => "https://schema.org",
      "@graph" => $imageSchema
    ], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT); ?>,
    <?= json_encode([
        "@context" => "https://schema.org",
        "@graph" => $graph
    ], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT); ?>,
    
    {
      "@context": "https://schema.org",
      "@graph": [

        {
          "@type": "ItemList",
          "name": "Concert Categories",
          "itemListElement": [
            { "@type": "ListItem", "position": 1, "item": { "@type": "Thing", "name": "Reggae / Reggaeton" } },
            { "@type": "ListItem", "position": 2, "item": { "@type": "Thing", "name": "Religious" } },
            { "@type": "ListItem", "position": 3, "item": { "@type": "Thing", "name": "50s / 60s Era" } },
            { "@type": "ListItem", "position": 4, "item": { "@type": "Thing", "name": "Children / Family" } },
            { "@type": "ListItem", "position": 5, "item": { "@type": "Thing", "name": "New Age" } },
            { "@type": "ListItem", "position": 6, "item": { "@type": "Thing", "name": "Bluegrass" } },
            { "@type": "ListItem", "position": 7, "item": { "@type": "Thing", "name": "Performance Series" } },
            { "@type": "ListItem", "position": 8, "item": { "@type": "Thing", "name": "Holiday" } }
          ]
        },

        {
          "@type": "ItemList",
          "name": "Sports Categories",
          "itemListElement": [
            { "@type": "ListItem", "position": 1, "item": { "@type": "Thing", "name": "Golf" } },
            { "@type": "ListItem", "position": 2, "item": { "@type": "Thing", "name": "Baseball" } },
            { "@type": "ListItem", "position": 3, "item": { "@type": "Thing", "name": "Olympics" } },
            { "@type": "ListItem", "position": 4, "item": { "@type": "Thing", "name": "Cricket" } },
            { "@type": "ListItem", "position": 5, "item": { "@type": "Thing", "name": "Gymnastics" } },
            { "@type": "ListItem", "position": 6, "item": { "@type": "Thing", "name": "Rugby" } },
            { "@type": "ListItem", "position": 7, "item": { "@type": "Thing", "name": "Tennis" } },
            { "@type": "ListItem", "position": 8, "item": { "@type": "Thing", "name": "Mixed Martial Arts" } }
          ]
        },

        {
          "@type": "ItemList",
          "name": "Theatre Categories",
          "itemListElement": [
            { "@type": "ListItem", "position": 1, "item": { "@type": "Thing", "name": "Musical / Play" } },
            { "@type": "ListItem", "position": 2, "item": { "@type": "Thing", "name": "Broadway" } },
            { "@type": "ListItem", "position": 3, "item": { "@type": "Thing", "name": "Children / Family" } },
            { "@type": "ListItem", "position": 4, "item": { "@type": "Thing", "name": "Off-Broadway" } },
            { "@type": "ListItem", "position": 5, "item": { "@type": "Thing", "name": "Ballet" } },
            { "@type": "ListItem", "position": 6, "item": { "@type": "Thing", "name": "Opera" } },
            { "@type": "ListItem", "position": 7, "item": { "@type": "Thing", "name": "Cirque Du Soleil" } },
            { "@type": "ListItem", "position": 8, "item": { "@type": "Thing", "name": "Dance" } }
          ]
        },

        {
          "@type": "ItemList",
          "name": "Festivals",
          "itemListElement": [
            { "@type": "ListItem", "position": 1, "item": { "@type": "Thing", "name": "Austin City Limits Festival" } },
            { "@type": "ListItem", "position": 2, "item": { "@type": "Thing", "name": "Festival of Monologues" } },
            { "@type": "ListItem", "position": 3, "item": { "@type": "Thing", "name": "Festival of Laughs" } },
            { "@type": "ListItem", "position": 4, "item": { "@type": "Thing", "name": "Festival Ballet Providence" } },
            { "@type": "ListItem", "position": 5, "item": { "@type": "Thing", "name": "Festival of Dance" } },
            { "@type": "ListItem", "position": 6, "item": { "@type": "Thing", "name": "Festival Chorale Oregon" } },
            { "@type": "ListItem", "position": 7, "item": { "@type": "Thing", "name": "Festival Orchestra Pops" } },
            { "@type": "ListItem", "position": 8, "item": { "@type": "Thing", "name": "Festival of Seasons" } }
          ]
        }

      ]
    }


  ]
}
</script>


