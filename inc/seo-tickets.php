<?php // Include-only file: answer 404 if it is requested directly over the web (it would render a fragment or an error).
if (PHP_SAPI !== 'cli' && isset($_SERVER['SCRIPT_FILENAME']) && realpath($_SERVER['SCRIPT_FILENAME']) === __FILE__) { http_response_code(404); exit; } ?>
<title>Buy Tickets Online&#x2014;All Events, Secure Checkout&#x2014;Seat Outlet</title>
<meta name="description" content="Buy tickets online for concerts, sports, theater and festivals. Search every upcoming event, compare seats and prices, and check out with a 100% guarantee.">
<meta name="keywords" content="Buy event tickets online, concert tickets online, sports tickets online, live event tickets, secure ticket marketplace">
<link rel="canonical" href="<?php echo HOME_URL; ?>/tickets">

<meta property="og:title" content="Buy Tickets Online&#x2014;All Events, Secure Checkout&#x2014;Seat Outlet">
<meta property="og:description" content="Buy tickets online for concerts, sports, theater and festivals. Search every upcoming event, compare seats and prices, and check out with a 100% guarantee.">
<meta property="og:url" content="<?php echo HOME_URL; ?>/tickets">
<meta property="og:type" content="website">
<meta property="og:image" content="<?php echo HOME_URL; ?>/images/seatoutlet-logo.webp">

<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="Buy Tickets Online&#x2014;All Events, Secure Checkout&#x2014;Seat Outlet">
<meta name="twitter:description" content="Buy tickets online for concerts, sports, theater and festivals. Search every upcoming event, compare seats and prices, and check out with a 100% guarantee.">
<meta name="twitter:image" content="<?php echo HOME_URL; ?>/images/seatoutlet-logo.webp">

<?php
$images = [
  HOME_URL . "/images/event-ticket-buying.webp"
];

$imageSchema = [];

foreach ($images as $img) {
    $imageSchema[] = [
        "@type" => "ImageObject",
        "url" => $img
    ];
}

$eventsSchema = [];
$results = getAllEvents();
if (!empty($results['results'])) {

  foreach ($results['results'] as $event) {

      // The same Event node as every other page (image, description, offers with validFrom).
      $node = soEventNode($event);
      if (empty($event['_metadata']['hasTickets'])) unset($node['offers']);
      $eventsSchema[] = $node;
  }
}

$webPageSchema = [
    "@type" => "WebPage",
    "@id" => HOME_URL . "/buy-tickets-online#webpage",
    "url" => HOME_URL . "/buy-tickets-online",
    "name" => "Buy Tickets Online",
    "description" => "Buy tickets online for concerts, sports, theater and festivals. Search every upcoming event, compare seats and prices, and check out with a 100% guarantee.",
    "isPartOf" => ["@id" => HOME_URL . "/#website"],
    "about" => ["@id" => HOME_URL . "/#organization"],
    "primaryImageOfPage" => [
        "@type" => "ImageObject",
        "url" => HOME_URL . "/images/seatoutlet-logo.webp"
    ]
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
    ],
    $eventsSchema,
    $imageSchema
));
