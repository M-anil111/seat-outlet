<?php
// Include-only file: answer 404 if it is requested directly over the web (it would render a fragment or an error).
if (PHP_SAPI !== 'cli' && isset($_SERVER['SCRIPT_FILENAME']) && realpath($_SERVER['SCRIPT_FILENAME']) === __FILE__) { http_response_code(404); exit; }
  $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
  if ($id <= 0) {
    $slug  = $_GET['slug'] ?? '';
    [$id]  = soSlugResolve('event', (string) $slug);   // stored slug, or the old name-and-id form
    $id    = (int) $id;
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
  $evUrl    = rtrim(HOME_URL, '/') . '/event/' . soEventSlug($event);
  $evTs     = !empty($event['date']['date']) ? strtotime($event['date']['date']) : false;
  $evDate   = $evTs ? date('M j, Y', $evTs) : '';
  // Title: what the visitor searches for ("<event> tickets"), the place and the brand, trimmed to fit a result.
  // The date is the first thing to go when the title is too long, so a result never ends in a half word: name + place + date, else name + place, else name.
  $evShort = $evTs ? date('M j', $evTs) : '';
  $evClock = ($evTs && date('H:i', $evTs) !== '00:00') ? date('g:i A', $evTs) : '';
  $evShortT = trim($evShort . ($evClock !== '' ? ' ' . $evClock : ''));
  $metaTitle = soTitle('Event Not Found');
  if (tnEntityUnavailable($event) && $evName === '') {
      $metaTitle = soTitle('Event Temporarily Unavailable');
  } elseif ($evName !== '') {
      // "<event> Tickets—<City, ST> <Mon D>": the place and date go first when the name is long, never half a word.
      $evShort = $evTs ? date('M j', $evTs) : '';
      // Showings of one event in one city share a name, so the time of day is part of the first choice: no two pages get the same title.
      $evClock = ($evTs && date('H:i', $evTs) !== '00:00') ? date('g:i A', $evTs) : '';
      $evShortT = trim($evShort . ($evClock !== '' ? ' ' . $evClock : ''));
      $metaTitle = soTitle(
          ($evPlace !== '' && $evShortT !== '') ? "$evName Tickets in $evPlace on $evShortT" : '',
          ($evPlace !== '' && $evShort !== '') ? "$evName Tickets in $evPlace on $evShort" : '',
          $evPlace !== '' ? "$evName Tickets in $evPlace" : '',
          $evShort !== '' ? "$evName Tickets on $evShort" : '',
          "$evName Tickets"
      );
  }
  if ($evName !== '' && empty($pageFocusKeyword)) {
      // The page's one H1 (the strip above the header). Showings of one event share a name, so the venue, day and time of day make each heading its own.
      $pageFocusKeyword = $evName . ' Tickets' . ($evVenue !== '' ? ' at ' . $evVenue : '') . ($evShortT !== '' ? ', ' . $evShortT : '');
  }   // shown in the strip above the header and the footer
  $evDay = $evTs ? date('D, M j, Y', $evTs) : '';
  $metaDescription = soMetaFit(
      $evName . ' tickets' . ($evDay !== '' ? ' for ' . $evDay . (($evClock ?? '') !== '' ? ' at ' . $evClock : '') : '') . ($evVenue !== '' ? ' at ' . $evVenue : '') . ($evPlace !== '' ? ' in ' . $evPlace : '')
      . '. Pick seats on the live seat map. Orders carry the TicketNetwork guarantee.',
      'Secure checkout and on time delivery.', 'Prices from many sellers in one place.'
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
if (!empty($event) && $evName !== '') {
    // The venue's own record adds the street address, postal code and map position (cached a day: venues rarely change).
    $evVenueId = (int) ($event['venue']['id'] ?? 0);
    $evVenueRec = $evVenueId > 0 ? tnRequestCached('/catalog/v2/venues/' . $evVenueId, [], 86400) : null;
    $eventSchema = soEventNode($event, [
        'venue' => (is_array($evVenueRec) && !tnEntityMissing($evVenueRec)) ? $evVenueRec : null,
        'image' => ($evOgImg !== '' && strpos($evOgImg, 'seatoutlet-logo') === false) ? $evOgImg : '',
        'description' => $metaDescription,
    ]);
}

// Breadcrumb: Home > category hub > performer > this event (the same trail the page links up through).
$evCatPath = (string) ($event['defaultCategory']['path'] ?? '');
$evHub = strpos($evCatPath, '.1872.') !== false ? ['Theater', '/buy-broadway-tickets']   // comedy sits under the Theater tab
    : ((defined('TN_CATEGORY_PATH_SPORTS') && strpos($evCatPath, TN_CATEGORY_PATH_SPORTS) === 0) ? ['Sports', '/game-day-tickets']
    : ((defined('TN_CATEGORY_PATH_THEATER') && strpos($evCatPath, TN_CATEGORY_PATH_THEATER) === 0) ? ['Theater', '/buy-broadway-tickets']
    : ((defined('TN_CATEGORY_PATH_FESTIVAL') && strpos($evCatPath, TN_CATEGORY_PATH_FESTIVAL) === 0) ? ['Festivals', '/upcoming-music-festivals']
    : ((defined('TN_CATEGORY_PATH_CONCERTS') && strpos($evCatPath, TN_CATEGORY_PATH_CONCERTS) === 0) ? ['Concerts', '/concert-tickets-for-sale'] : ['Events', '/buy-tickets-online']))));
$evTrail = [["label" => "Home", "url" => HOME_URL . '/'], ["label" => $evHub[0], "url" => HOME_URL . $evHub[1]]];
$evMainPerf = $event['performers'][0] ?? null;
if (!empty($evMainPerf['id']) && !empty($evMainPerf['name']) && count($event['performers'] ?? []) === 1) {
    $evTrail[] = ["label" => $evMainPerf['name'], "url" => HOME_URL . '/artist/' . soSlug('performer', (string) $evMainPerf['name'], (int) $evMainPerf['id'])];
}
// The last step names the date when the event is called the same as its performer ("Daniel Sloss, Oct 9").
$evCrumbLabel = ($evName !== '' && isset($evTrail[2]) && strcasecmp($evTrail[2]['label'], $evName) === 0 && $evTs) ? $evName . ', ' . date('M j', $evTs) : $evName;
$breadcrumbSchema = buildBreadcrumbListSchema($evTrail, $evCrumbLabel !== '' ? $evCrumbLabel : null);
$breadcrumbSchema['@id'] = $evUrl . '#breadcrumb';

$webPageSchema = [
    "@type" => "ItemPage",
    "@id" => $evUrl . "#webpage",
    "url" => $evUrl,
    "name" => preg_replace('/\x{2014}Seat Outlet$/u', '', (string) $metaTitle),
    "isPartOf" => ["@id" => HOME_URL . "/#website"],
    "publisher" => ["@id" => HOME_URL . "/#organization"],
    "author" => ["@id" => HOME_URL . "/#organization"],
    "inLanguage" => "en-US",
    "breadcrumb" => ["@id" => $evUrl . '#breadcrumb'],
    "description" => $metaDescription,
];
if ($eventSchema) $webPageSchema['mainEntity'] = ['@id' => $eventSchema['@id']];
if ($evOgImg !== '' && strpos($evOgImg, 'seatoutlet-logo') === false) $webPageSchema['primaryImageOfPage'] = ['@type' => 'ImageObject', 'url' => $evOgImg];
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
