<title>Seat Outlet – Buy Tickets Online</title>
<meta name="description" content="Discover the best deals on live events with our verified ticket marketplace network. Safe checkout, real tickets, and instant access to unforgettable experiences.">
<meta name="keywords" content="Verified Ticket Marketplace Network, buy event tickets online, concert tickets online, sports tickets marketplace, compare ticket prices online">
<link rel="canonical" href="https://beta.seatoutlet.com/">

<meta property="og:title" content="Buy Tickets Online | Seat Outlet">
<meta property="og:description" content="Discover the best deals on live events with our verified ticket marketplace network. Safe checkout, real tickets, and instant access to unforgettable experiences.">
<meta property="og:url" content="https://beta.seatoutlet.com/">
<meta property="og:type" content="website">
<meta property="og:image" content="https://beta.seatoutlet.com/assets/seatoutlet-logo.webp">

<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="Buy Tickets Online | Seat Outlet">
<meta name="twitter:description" content="Discover the best deals on live events with our verified ticket marketplace network. Safe checkout, real tickets, and instant access to unforgettable experiences.">
<meta name="twitter:image" content="https://beta.seatoutlet.com/assets/seatoutlet-logo.webp">

<?php 
 
 $images = [
  "https://beta.seatoutlet.com/assets/event-ticket-buying.webp"
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
if(!empty($results['results'])) {

  foreach ($results['results'] as $event) {    

      
      $city = $event['city']['text']['name'];
      $state = $event['stateProvince']['text']['abbr'];

      // 🔥 Convert UNIX → ISO
      $startDate = $event['date']['date'];

      $price = $event['pricingInfo']['lowPrice']['value'];

      $eventsSchema[] = [
          "@type" => "Event",
          "name" => $event['text']['name'],
          "startDate" => $startDate,
          "eventStatus" => "https://schema.org/EventScheduled",

          "location" => [
              "@type" => "Place",
              "name" => $event['text']['venue'],
              "address" => [
                  "@type" => "PostalAddress",
                  "addressLocality" => $city,
                  "addressRegion" => $state,
                  "addressCountry" => "US"
              ]
          ],
          "performer" => [
              "@type" => "PerformingGroup",
              "name" => $event['performers'][0]['name']
          ],

          "offers" => [
              "@type" => "Offer",
              "url" => "https://beta.seatoutlet.com/event/" . $event['id'],
              "price" => $price,
              "priceCurrency" => "USD",
              "availability" => "https://schema.org/InStock"
          ]
      ];
  }
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

    <?= json_encode([
        "@context" => "https://schema.org",
        "@graph" => $eventsSchema
    ], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT); ?>,
    <?= json_encode([
      "@context" => "https://schema.org",
      "@graph" => $imageSchema
    ], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT); ?>,
   

  ]
}
</script>


