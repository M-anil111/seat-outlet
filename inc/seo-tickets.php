<?php // Include-only file: answer 404 if it is requested directly over the web (it would render a fragment or an error).
if (PHP_SAPI !== 'cli' && isset($_SERVER['SCRIPT_FILENAME']) && realpath($_SERVER['SCRIPT_FILENAME']) === __FILE__) { http_response_code(404); exit; } ?>
<title>Seat Outlet – Buy Verified Event Tickets Online</title>
<meta name="description" content="Discover deals on live events with our verified ticket marketplace network. Safe checkout, real tickets and instant access to unforgettable experiences.">
<meta name="keywords" content="Buy verified event tickets online, concert tickets online, sports tickets online, live event tickets, secure ticket marketplace">
<link rel="canonical" href="<?php echo HOME_URL; ?>/tickets">

<meta property="og:title" content="Buy Verified Event Tickets Online | Seat Outlet">
<meta property="og:description" content="Discover deals on live events with our verified ticket marketplace network. Safe checkout, real tickets and instant access to unforgettable experiences.">
<meta property="og:url" content="<?php echo HOME_URL; ?>/tickets">
<meta property="og:type" content="website">
<meta property="og:image" content="<?php echo HOME_URL; ?>/images/seatoutlet-logo.webp">

<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="Buy Verified Event Tickets Online | Seat Outlet">
<meta name="twitter:description" content="Discover deals on live events with our verified ticket marketplace network. Safe checkout, real tickets and instant access to unforgettable experiences.">
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

      $city = $event['city']['text']['name'] ?? '';
      $state = $event['stateProvince']['text']['abbr'] ?? '';
      $startDate = $event['date']['datetimeOffset'] ?? ($event['date']['date'] ?? '');
      $price = $event['pricingInfo']['lowPrice']['value'] ?? null;

      $eventsSchema[] = [
          "@type" => "Event",
          "name" => $event['text']['name'] ?? '',
          "startDate" => $startDate,
          "eventStatus" => "https://schema.org/EventScheduled",

          "location" => [
              "@type" => "Place",
              // Was $event['text']['venue'] - the venue name lives under
              // event.venue.text.name in the TicketNetwork API response,
              // not event.text.venue. Confirmed against a live sandbox
              // event: {"venue":{"text":{"name":"Royal Alexandra Theatre"}}}.
              "name" => $event['venue']['text']['name'] ?? '',
              "address" => [
                  "@type" => "PostalAddress",
                  "addressLocality" => $city,
                  "addressRegion" => $state,
                  "addressCountry" => "US"
              ]
          ],
          "performer" => buildEventPerformerSchema($event),
      ];
      $offer = !empty($event['_metadata']['hasTickets']) ? seoOffer(HOME_URL . "/event/" . ($event['uriComponent'] ?? $event['id'] ?? ''), $price) : null;
      if ($offer) {
          $eventsSchema[array_key_last($eventsSchema)]["offers"] = $offer;
      }
  }
}

$webPageSchema = [
    "@type" => "WebPage",
    "@id" => HOME_URL . "/buy-tickets-online#webpage",
    "url" => HOME_URL . "/buy-tickets-online",
    "name" => "Buy Verified Event Tickets Online",
    "description" => "Discover deals on live events with our verified ticket marketplace network. Safe checkout, real tickets and instant access to unforgettable experiences.",
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
