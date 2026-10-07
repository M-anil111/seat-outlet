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
  // No event data (the feed failed, or the event is gone): there is no address to name, so no canonical and no og:url. The page answers 503 or 404 and
  // used to print /event/-0 here, a canonical that is itself a 404.
  $evUrl    = $evName !== '' ? rtrim(HOME_URL, '/') . '/event/' . soEventSlug($event) : '';
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
      // Spec (inc/page-spec.php, soSpecEventText): "Buy <Performer> Tickets for <Venue> Show in <City>, <ST>" and the equivalents for games,
      // shows and festivals. The brand is added once ("at Seat Outlet"), never a pipe. Up to 70 characters; the day goes first to go.
      $evKind  = soSpecKind((string) ($event['defaultCategory']['path'] ?? ''));
      $evLabel = soSpecLabel($evName, $evKind);
      $evCountryName = ['US' => 'the United States', 'CA' => 'Canada'][(string) ($event['country']['alphaCode'] ?? '')] ?? (trim((string) ($event['country']['text']['name'] ?? '')) ?: 'the United States');
      $evSpec = soSpecEventText($evKind, [
          'label' => $evLabel, 'perf' => (string) ($event['performers'][0]['name'] ?? $evLabel), 'venue' => $evVenue, 'city' => $evCity, 'state' => $evState,
          'day' => $evShort, 'cat' => soCategoryDisplayName((string) ($event['defaultCategory']['text']['name'] ?? '')), 'country' => $evCountryName,
          'hasTickets' => !empty($event['_metadata']['hasTickets']),
      ]);
      $metaTitle = soTitleUpTo(70, ...$evSpec['titles']);
  }
  $evKind  = $evKind ?? soSpecKind((string) ($event['defaultCategory']['path'] ?? ''));
  $evLabel = $evLabel ?? soSpecLabel($evName, $evKind);
  // One keyword, one page: the performer in a city owns "<Performer> Concert Tickets in <City>"; this event owns its venue.
  if ($evName !== '' && empty($pageFocusKeyword)) { $pageFocusKeyword = $evSpec['focus'] ?? ($evLabel . ' Tickets'); }   // shown in the strip above the header and the footer
  $evDay = $evTs ? date('D, M j, Y', $evTs) : '';
  // Spec wording; the urgency line is only in when tickets are listed (the page cannot "sell out" with nothing on it).
  $evHasTickets = !empty($event['_metadata']['hasTickets']);
  if ($evHasTickets && !empty($evSpec['descs'])) {
      $metaDescription = soSpecPick(155, ...$evSpec['descs']);
  } else {
      $metaDescription = soMetaFit(
          $evName . ' tickets' . ($evDay !== '' ? ' for ' . $evDay . (($evClock ?? '') !== '' ? ' at ' . $evClock : '') : '') . ($evVenue !== '' ? ' at ' . $evVenue : '') . ($evPlace !== '' ? ' in ' . $evPlace : '')
          . '. Get an alert when seats are listed. Orders carry the TicketNetwork guarantee.',
          'Secure checkout and on time delivery.', 'Prices from many sellers in one place.'
      );
  }
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
<?php if ($evUrl !== '') { ?><link rel="canonical" href="<?php echo $e($evUrl); ?>"><?php } ?>
<link rel="stylesheet" href="<?php echo $e(soAsset('css/event.css')); ?>">
<link rel="stylesheet" href="<?php echo $e(soAsset('css/spec-cards.css')); ?>">

<?php
  // Share image: the performer's picture when we hold a real one, else the logo.
  $evOgImg = rtrim(HOME_URL, '/') . '/images/seatoutlet-share-1200x630.jpg';
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
<?php if ($evUrl !== '') { ?><meta property="og:url" content="<?php echo $e($evUrl); ?>"><?php } ?>
<meta property="og:type" content="website">
<meta property="og:image" content="<?php echo $e($evOgImg); ?>">

<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="<?php echo $e($metaTitle); ?>">
<meta name="twitter:description" content="<?php echo $e($metaDescription); ?>">
<meta name="twitter:image" content="<?php echo $e($evOgImg); ?>">

<?php
$eventSchema = null;
// The city's other events of this kind: feeds the "similar events" section and its ItemList. Cached by the API layer; [] when the feed is down.
$evCityData = (!empty($event) && $evName !== '') ? soSpecCityEvents($evKind, $event['city']['id'] ?? 0) : [];
// The venue's other events (sports and theater: the same kind; concerts and festivals: everything there). The sections read "Top Upcoming Performances at <Venue>" and the like.
$evVenueData = (!empty($event) && $evName !== '') ? soSpecVenueEvents(in_array($evKind, ['sports', 'theater'], true) ? $evKind : '', $event['venue']['id'] ?? 0) : [];
if (!empty($event) && $evName !== '') {
    // The venue's own record adds the street address, postal code and map position (cached a day: venues rarely change).
    $evVenueId = (int) ($event['venue']['id'] ?? 0);
    $evVenueRec = $evVenueId > 0 ? tnRequestCached('/catalog/v2/venues/' . $evVenueId, [], 86400) : null;
    $evComp = [];
    if ($evKind === 'sports' && count($event['performers'] ?? []) >= 2) {
        foreach (array_slice($event['performers'], 0, 2) as $cp) { if (!empty($cp['name'])) $evComp[] = ['@type' => 'SportsTeam', 'name' => (string) $cp['name']]; }
    }
    $eventSchema = soEventNode($event, [
        'type' => soSpecSchemaType($evKind, (string) ($event['defaultCategory']['path'] ?? '')),
        'competitors' => $evComp,
        'venue' => (is_array($evVenueRec) && !tnEntityMissing($evVenueRec)) ? $evVenueRec : null,
        'image' => ($evOgImg !== '' && strpos($evOgImg, 'seatoutlet-logo') === false) ? $evOgImg : '',
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
if ($eventSchema) { $webPageSchema['mainEntity'] = ['@id' => $eventSchema['@id']]; $webPageSchema['about'] = ['@id' => $eventSchema['@id']]; }
$evSimilarNode = null;
if (!empty($evVenueData['events']) || !empty($evCityData['events'])) {
    $evSimilar = array_values(array_filter($evVenueData['events'] ?? $evCityData['events'], fn($oe) => (int) ($oe['id'] ?? 0) !== (int) $id));
    if ($evSimilar) $evSimilarNode = soEventItemList(array_slice($evSimilar, 0, 6), $evUrl);
}
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
    $evSimilarNode,
]);
