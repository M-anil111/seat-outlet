<?php
  $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
  if ($id <= 0) {
    $slug  = $_GET['slug'] ?? '';
    $parts = explode('-', (string) $slug);
    $id    = (int) end($parts);
    if ($id <= 0) {
      echo '<div class="container"><p>Invalid event.</p></div>';
      include 'footer.php';
      exit;
    }
  }
  $event = getTnEventById($id);
  $metaDescription = "Buy {$event['text']['name']} tickets at {$event['venue']['text']['name']} in {$event['city']['text']['name']}, {$event['stateProvince']['text']['abbr']}. View event date, venue details, seating options, and secure your tickets online at Seat Outlet.";
  $keywords[] = $event['text']['name'] . " tickets";
  $keywords[] = "buy " . $event['text']['name'] . " tickets";
  $keywords[] = $event['venue']['text']['name'] . " tickets";
  $keywords[] = "events in " . $event['city']['text']['name'] . " " . $event['stateProvince']['text']['abbr'];
  $keywords[] = "tickets in " . $event['city']['text']['name'] . " " . $event['stateProvince']['text']['abbr'];
  $metaKeywords = implode(", ", array_unique($keywords));
?>
<title>Seat Outlet – <?php echo $event['text']['name']; ?></title>
<meta name="description" content="<?php echo $metaDescription; ?>">
<meta name="keywords" content="<?php echo htmlspecialchars($metaKeywords, ENT_QUOTES, 'UTF-8'); ?>">
<link rel="canonical" href="https://beta.seatoutlet.com/event/<?php echo $event['uriComponent']; ?>">

<meta property="og:title" content="<?php echo $event['text']['name']; ?> | Seat Outlet">
<meta property="og:description" content="<?php echo $metaDescription; ?>">
<meta property="og:url" content="https://beta.seatoutlet.com/event/<?php echo $event['uriComponent']; ?>">
<meta property="og:type" content="website">
<meta property="og:image" content="https://beta.seatoutlet.com/images/seatoutlet-logo.webp">

<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="<?php echo $event['text']['name']; ?> | Seat Outlet">
<meta name="twitter:description" content="<?php echo $metaDescription; ?>">
<meta name="twitter:image" content="https://beta.seatoutlet.com/images/seatoutlet-logo.webp">

<?php
$eventSchema = null;
if (!empty($event)) {
    $city = $event['city']['text']['name'];
    $state = $event['stateProvince']['text']['abbr'];
    $startDate = $event['date']['date'];
    $price = $event['pricingInfo']['lowPrice']['value'] ?? null;

    $eventSchema = [
        "@type" => "Event",
        "name" => $event['text']['name'],
        "startDate" => $startDate,
        "eventStatus" => "https://schema.org/EventScheduled",

        "location" => [
            "@type" => "Place",
            "name" => $event['venue']['text']['name'] ?? '',
            "address" => [
                "@type" => "PostalAddress",
                "addressLocality" => $city,
                "addressRegion" => $state,
                "addressCountry" => "US"
            ]
        ],
        "performer" => [
            "@type" => "PerformingGroup",
            "name" => $event['performers'][0]['name'] ?? $event['text']['name']
        ],

        "offers" => [
            "@type" => "Offer",
            "url" => "https://beta.seatoutlet.com/event/" . $event['uriComponent'],
            "price" => $price ?? "0",
            "priceCurrency" => "USD",
            "availability" => "https://schema.org/InStock"
        ]
    ];
}

$webPageSchema = [
    "@type" => "WebPage",
    "@id" => "https://beta.seatoutlet.com/event/" . $event['uriComponent'] . "#webpage",
    "url" => "https://beta.seatoutlet.com/event/" . $event['uriComponent'],
    "name" => $event['text']['name'],
    "isPartOf" => ["@id" => "https://beta.seatoutlet.com/#website"],
    "about" => ["@id" => "https://beta.seatoutlet.com/#organization"],
    "description" => $metaDescription,
];

$breadcrumbSchema = buildBreadcrumbListSchema([
    ["label" => "Home", "url" => "https://beta.seatoutlet.com"],
    ["label" => "Events", "url" => "https://beta.seatoutlet.com/tickets"],
], $event['text']['name'] ?? null);
?>
<!-- ============================
STRUCTURED DATA (JSON-LD)
============================ -->

<?php
outputJsonLdGraph([
    buildOrganizationSchema(),
    buildWebsiteSchema(),
    $webPageSchema,
    $breadcrumbSchema,
    $eventSchema,
]);
