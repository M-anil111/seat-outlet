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
  $e = fn($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
  $evName   = (string) ($event['text']['name'] ?? '');
  $evVenue  = (string) ($event['venue']['text']['name'] ?? '');
  $evCity   = (string) ($event['city']['text']['name'] ?? '');
  $evState  = (string) ($event['stateProvince']['text']['abbr'] ?? '');
  $evPlace  = trim($evCity . ($evState !== '' ? ', ' . $evState : ''));
  $evUrl    = HOME_URL . '/event/' . ($event['uriComponent'] ?? '');
  $evTs     = !empty($event['date']['date']) ? strtotime($event['date']['date']) : false;
  $evDate   = $evTs ? date('M j, Y', $evTs) : '';
  // Title: what the visitor searches for ("<event> tickets"), the place and the brand, trimmed to fit a result.
  $metaTitle = seoClampTitle($evName . ' Tickets' . ($evPlace !== '' ? ' in ' . $evPlace : '') . ' | Seat Outlet');
  $metaDescription = seoClampDescription(
      'Buy ' . $evName . ' tickets' . ($evVenue !== '' ? ' at ' . $evVenue : '') . ($evPlace !== '' ? ' in ' . $evPlace : '')
      . ($evDate !== '' ? ' on ' . $evDate : '') . '. Compare seats and prices, then check out securely at Seat Outlet.'
  );
  $keywords = [];
  $keywords[] = $evName . " tickets";
  $keywords[] = "buy " . $evName . " tickets";
  if ($evVenue !== '') $keywords[] = $evVenue . " tickets";
  if ($evPlace !== '') { $keywords[] = "events in " . $evPlace; $keywords[] = "tickets in " . $evPlace; }
  $metaKeywords = implode(", ", array_unique($keywords));
?>
<title><?php echo $e($metaTitle); ?></title>
<meta name="description" content="<?php echo $e($metaDescription); ?>">
<meta name="keywords" content="<?php echo $e($metaKeywords); ?>">
<link rel="canonical" href="<?php echo $e($evUrl); ?>">

<meta property="og:title" content="<?php echo $e($metaTitle); ?>">
<meta property="og:description" content="<?php echo $e($metaDescription); ?>">
<meta property="og:url" content="<?php echo $e($evUrl); ?>">
<meta property="og:type" content="website">
<meta property="og:image" content="<?php echo HOME_URL; ?>/images/seatoutlet-logo.webp">

<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="<?php echo $e($metaTitle); ?>">
<meta name="twitter:description" content="<?php echo $e($metaDescription); ?>">
<meta name="twitter:image" content="<?php echo HOME_URL; ?>/images/seatoutlet-logo.webp">

<?php
$eventSchema = null;
if (!empty($event)) {
    $eventSchema = [
        "@type" => "Event",
        "name" => $evName,
        // Full local date-time with offset when the API gives one, so search engines show the right start.
        "startDate" => $event['date']['datetimeOffset'] ?? ($event['date']['date'] ?? ''),
        "eventStatus" => "https://schema.org/EventScheduled",
        "eventAttendanceMode" => "https://schema.org/OfflineEventAttendanceMode",
        "url" => $evUrl,
        "location" => [
            "@type" => "Place",
            "name" => $evVenue,
            "address" => [
                "@type" => "PostalAddress",
                "addressLocality" => $evCity,
                "addressRegion" => $evState,
                "addressCountry" => "US"
            ]
        ],
        "performer" => buildEventPerformerSchema($event),
        "organizer" => ["@id" => HOME_URL . "/#organization"],
    ];
    // Only claim an offer when the API reports a real price and tickets exist.
    $offer = !empty($event['_metadata']['hasTickets']) ? seoOffer($evUrl, $event['pricingInfo']['lowPrice']['value'] ?? null) : null;
    if ($offer) {
        $eventSchema["offers"] = $offer;
    }
}

$webPageSchema = [
    "@type" => "WebPage",
    "@id" => $evUrl . "#webpage",
    "url" => $evUrl,
    "name" => $evName,
    "isPartOf" => ["@id" => HOME_URL . "/#website"],
    "about" => ["@id" => HOME_URL . "/#organization"],
    "description" => $metaDescription,
];

$breadcrumbSchema = buildBreadcrumbListSchema([
    ["label" => "Home", "url" => HOME_URL],
    ["label" => "Events", "url" => HOME_URL . "/tickets"],
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
