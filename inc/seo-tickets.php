<title>Seat Outlet – Buy Verified Event Tickets Online</title>
<meta name="description" content="Discover the best deals on live events with our verified ticket marketplace network. Safe checkout, real tickets, and instant access to unforgettable experiences.">
<meta name="keywords" content="Buy verified event tickets online, concert tickets online, sports tickets online, live event tickets, secure ticket marketplace">
<link rel="canonical" href="https://beta.seatoutlet.com/tickets">

<meta property="og:title" content="Buy Verified Event Tickets Online | Seat Outlet">
<meta property="og:description" content="Discover the best deals on live events with our verified ticket marketplace network. Safe checkout, real tickets, and instant access to unforgettable experiences.">
<meta property="og:url" content="https://beta.seatoutlet.com/tickets">
<meta property="og:type" content="website">
<meta property="og:image" content="https://beta.seatoutlet.com/images/seatoutlet-logo.webp">

<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="Buy Verified Event Tickets Online | Seat Outlet">
<meta name="twitter:description" content="Discover the best deals on live events with our verified ticket marketplace network. Safe checkout, real tickets, and instant access to unforgettable experiences.">
<meta name="twitter:image" content="https://beta.seatoutlet.com/images/seatoutlet-logo.webp">

<?php
$images = [
  "https://beta.seatoutlet.com/images/event-ticket-buying.webp"
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
      $startDate = $event['date']['date'] ?? '';
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
          "performer" => [
              "@type" => "PerformingGroup",
              "name" => $event['performers'][0]['name'] ?? ($event['text']['name'] ?? '')
          ],

          "offers" => [
              "@type" => "Offer",
              "url" => "https://beta.seatoutlet.com/event/" . ($event['uriComponent'] ?? $event['id'] ?? ''),
              "price" => $price ?? "0",
              "priceCurrency" => "USD",
              "availability" => "https://schema.org/InStock"
          ]
      ];
  }
}

$webPageSchema = [
    "@type" => "WebPage",
    "@id" => "https://beta.seatoutlet.com/tickets#webpage",
    "url" => "https://beta.seatoutlet.com/tickets",
    "name" => "Buy Verified Event Tickets Online",
    "description" => "Discover the best deals on live events with our verified ticket marketplace network. Safe checkout, real tickets, and instant access to unforgettable experiences.",
    "isPartOf" => ["@id" => "https://beta.seatoutlet.com/#website"],
    "about" => ["@id" => "https://beta.seatoutlet.com/#organization"],
    "primaryImageOfPage" => [
        "@type" => "ImageObject",
        "url" => "https://beta.seatoutlet.com/images/seatoutlet-logo.webp"
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
