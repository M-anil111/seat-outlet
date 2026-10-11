<?php
/** Isolated checks and real shared filter markup for the browser suite. No database or external services. */
require_once dirname(__DIR__) . '/inc/date-range.php';
require_once dirname(__DIR__) . '/inc/listing.php';
$source = file_get_contents(dirname(__DIR__) . '/functions.php');
$from = strpos($source, 'function listingDateRange(');
$to = strpos($source, 'const LISTING_WHEN', $from);
eval(substr($source, $from, $to - $from));
if (($argv[1] ?? '') !== '--html') {
    foreach ([['2027-02-28','2027-03-01',true],['2027-02-29','2027-03-01',false],['2028-02-29','2028-03-01',true],['2027-03-02','2027-03-01',false],['2027-1-01','2027-03-01',false],['','',false]] as [$a,$b,$valid]) {
        if ((soValidDateRange($a,$b) !== null) !== $valid) throw new RuntimeException('Date validation failed');
    }
    foreach (['week'=>7,'month'=>30] as $key=>$days) {
        [$a,$b] = listingDateRange($key);
        if ((new DateTimeImmutable($a))->diff(new DateTimeImmutable($b))->days + 1 !== $days) throw new RuntimeException('Inclusive date window failed');
    }
    // Exercise the real search link builder without bootstrapping integrations.
    $search = file_get_contents(dirname(__DIR__) . '/search.php');
    $from = strpos($search, '$filterHref = function');
    $to = strpos($search, '$icCal =', $from);
    $searchInput = ['keywordHeader'=>'Fixture artist','startInputHeader'=>'2027-01-01','endInputHeader'=>'2027-01-10'];
    $_GET = ['max'=>'50'];
    eval(substr($search,$from,$to-$from));
    parse_str(parse_url($filterHref('when','today'), PHP_URL_QUERY), $q);
    if (isset($q['startInputHeader']) || $q['when'] !== 'today' || $q['max'] !== '50') throw new RuntimeException('Quick filter did not replace custom range');
    parse_str(parse_url($filterHref('sort','price'), PHP_URL_QUERY), $q);
    if ($q['startInputHeader'] !== '2027-01-01' || $q['sort'] !== 'price') throw new RuntimeException('Sort lost custom range');
    echo "ok   strict dates, inclusive quick windows, date replacement and sort preservation\n";
    exit;
}
?>
<!doctype html><html lang="en"><head><meta name="viewport" content="width=device-width,initial-scale=1"><link rel="stylesheet" href="/css/bootstrap.min.css"><link rel="stylesheet" href="/css/style.css"></head><body><main class="container py-4"><h1>Fixture artist tickets</h1><p>Controlled local fixtures for shared listing controls.</p>
<?php soRenderFilterBar([
    ['label'=>'Dates','icon'=>'','param'=>'when','current'=>'','options'=>[''=>'All dates','today'=>'Today','weekend'=>'This weekend','week'=>'Next 7 days','month'=>'Next 30 days']],
    ['label'=>'Price','icon'=>'','param'=>'max','current'=>'0','options'=>['0'=>'Any price']+LISTING_PRICE],
    ['label'=>'Sort','icon'=>'','param'=>'sort','current'=>'','options'=>[''=>'Best match','soonest'=>'Soonest','price'=>'Lowest price']],
], fn($p,$v)=>'/?'.http_build_query([$p=>$v])); ?>
<input id="latEvent" type="hidden"><input id="lngEvent" type="hidden"><input id="sdateEvent" type="hidden"><input id="edateEvent" type="hidden"><input id="pidEvent" type="hidden" value="42" data-name="Fixture artist">
<input id="locationInput" aria-label="Location"><button id="locationInputReset">Reset location</button><div id="locationResults"></div><div id="locationHeading"></div>
<button data-so-sort="price">Lowest price</button><button data-so-when="today">Today</button>
<div id="location-no-results" role="status"></div><div id="eventsSection"><div class="performer-event-item">Initial event</div></div><p id="results_count">1 result</p><span id="loadedCount">1</span>/<span id="totalCount">1</span><div id="progressBar"></div>
<button id="loadMoreBtn" data-page="2" data-total="4" data-perpage="20" data-params="{}">More events<span id="btnSpinner" class="d-none"></span></button><button id="backToTopJs">Back to top</button><p id="loadMoreError" hidden>Could not load more</p>
</main><script>function getCookie(){return '';}function setCookie(){}function getMonthCount(){return 1;}</script><script src="/js/events-listing.js"></script></body></html>
