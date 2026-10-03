<?php
// Include-only file: answer 404 if it is requested directly over the web (it would render a fragment or an error).
if (PHP_SAPI !== 'cli' && isset($_SERVER['SCRIPT_FILENAME']) && realpath($_SERVER['SCRIPT_FILENAME']) === __FILE__) { http_response_code(404); exit; }
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
  // Lowercase "name-id" slug, the same one the sitemap and every internal link use (header.php 301s any other spelling to it).
  $evUrl    = rtrim(HOME_URL, '/') . '/event/' . createSlug($evName, (int) ($event['id'] ?? $id));
  $evTs     = !empty($event['date']['date']) ? strtotime($event['date']['date']) : false;
  $evDate   = $evTs ? date('M j, Y', $evTs) : '';
  // Title: what the visitor searches for ("<event> tickets"), the place and the brand, trimmed to fit a result.
  // The date is the first thing to go when the title is too long, so a result never ends in a half word: name + place + date, else name + place, else name.
  $metaTitle = 'Event not found | Seat Outlet';
  if (tnEntityUnavailable($event) && $evName === '') {
      $metaTitle = 'Event temporarily unavailable | Seat Outlet';
  } elseif ($evName !== '') {
      $tryTitles = [];
      if ($evPlace !== '' && $evTs) $tryTitles[] = $evName . ' Tickets in ' . $evPlace . ' - ' . $evDate . ' | Seat Outlet';
      if ($evPlace !== '') $tryTitles[] = $evName . ' Tickets in ' . $evPlace . ' | Seat Outlet';
      $tryTitles[] = $evName . ' Tickets | Seat Outlet';
      $metaTitle = null;
      foreach ($tryTitles as $tt) { if (mb_strlen($tt) <= 62) { $metaTitle = $tt; break; } }
      if ($metaTitle === null) $metaTitle = seoClampTitle($evName . ' Tickets | Seat Outlet');
  }
  if ($evName !== '' && empty($pageFocusKeyword)) { $pageFocusKeyword = $evName . ' Tickets'; }   // shown in the strip above the header and the footer
  $metaDescription = seoClampDescription(
      'Buy ' . $evName . ' tickets for sale' . ($evVenue !== '' ? ' at ' . $evVenue : '') . ($evPlace !== '' ? ' in ' . $evPlace : '')
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
<link rel="stylesheet" href="<?php echo $e(soAsset('css/event.css')); ?>">

<?php
  // Share image: the performer's picture when we hold a real one, else the logo.
  $evOgImg = rtrim(HOME_URL, '/') . '/images/seatoutlet-logo.webp';
  if ($evName !== '' && !empty($event['text']['name'])) {
      $evOgType = imageEntityTypeForPerformer($event['defaultCategory'] ?? []);
      $evOgWho  = (string) ($event['performers'][0]['name'] ?? $evName);
      $evOgInfo = getEntityImage($evOgType, $evOgWho, ['category' => $event['defaultCategory'] ?? [], 'resolve' => false]);
      if (in_array($evOgInfo['status'] ?? '', ['ok', 'manual'], true) && ($evOgInfo['url'] ?? '') !== '') {
          $evOgImg = preg_match('#^https?://#i', $evOgInfo['url']) ? $evOgInfo['url'] : rtrim(HOME_URL, '/') . '/' . ltrim($evOgInfo['url'], '/');
      }
  }
?>
<meta property="og:title" content="<?php echo $e($metaTitle); ?>">
<meta property="og:description" content="<?php echo $e($metaDescription); ?>">
<meta property="og:url" content="<?php echo $e($evUrl); ?>">
<meta property="og:type" content="website">
<meta property="og:image" content="<?php echo $e($evOgImg); ?>">

<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="<?php echo $e($metaTitle); ?>">
<meta name="twitter:description" content="<?php echo $e($metaDescription); ?>">
<meta name="twitter:image" content="<?php echo $e($evOgImg); ?>">

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
    if ($evOgImg !== '' && strpos($evOgImg, 'seatoutlet-logo') === false) $eventSchema['image'] = [$evOgImg];
    if ($metaDescription !== '') $eventSchema['description'] = $metaDescription;
    // Only claim an offer when the API reports a real price and tickets exist. The range and the count are TicketNetwork's own
    // current figures for this event (lowest and highest listed price, number of tickets listed), nothing estimated.
    $offer = !empty($event['_metadata']['hasTickets']) ? seoOffer($evUrl, $event['pricingInfo']['lowPrice']['value'] ?? null) : null;
    if ($offer) {
        $high = (float) ($event['pricingInfo']['highPrice']['value'] ?? 0);
        $low = (float) $offer['price'];
        $count = (int) ($event['_metadata']['ticketCount'] ?? 0);
        $offer['@type'] = 'AggregateOffer';
        $offer['lowPrice'] = $offer['price'];
        unset($offer['price']);
        if ($high >= $low && $high > 0) $offer['highPrice'] = number_format($high, 2, '.', '');
        if ($count > 0) $offer['offerCount'] = $count;
        $offer['seller'] = ['@id' => HOME_URL . '/#organization'];
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
    ["label" => "Events", "url" => HOME_URL . "/buy-tickets-online"],
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
