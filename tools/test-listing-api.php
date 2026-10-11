<?php
/** Execute the real AJAX endpoint code against local adapters, checking status, caching and query construction. */
$root = dirname(__DIR__);
$dir = sys_get_temp_dir() . '/so-listing-api-' . bin2hex(random_bytes(6));
mkdir($dir, 0700);
$socket = stream_socket_server('tcp://127.0.0.1:0', $errno, $error);
if (!$socket) throw new RuntimeException($error);
$address = stream_socket_get_name($socket, false); fclose($socket);
$functions = file_get_contents($root . '/functions.php');
$helpers = '<?php require_once ' . var_export($root . '/inc/date-range.php', true) . ';';
foreach (['soQs','soQsInt','soSnapGeo'] as $name) {
    $source = file_get_contents($root . '/inc/request-guard.php');
    if (!preg_match('/function ' . $name . '\(.*?\n}\n/s', $source, $m)) throw new RuntimeException('Missing helper');
    $helpers .= $m[0];
}
foreach (['listingDateRange','listingSortParams','locationListingParams','categoryListingParams'] as $name) {
    if (!preg_match('/function ' . $name . '\(.*?\n}/s', $functions, $m)) throw new RuntimeException('Missing helper');
    $helpers .= $m[0];
}
$helpers .= "function soEventHorizon() { return date('Y-m-d', strtotime('+3 years')); }";
$helpers .= <<<'PHP'
const LISTING_WHEN = ['today'=>'Today','weekend'=>'This weekend','week'=>'Next 7 days','month'=>'Next 30 days'];
const LISTING_PRICE = [50=>'Under $50'];
const TN_CATEGORY_PATH_CONCERTS = '.1.';
const TN_CATEGORY_PATH_SPORTS = '.2.';
const TN_CATEGORY_PATH_THEATER = '.3.';
const TN_CATEGORY_PATH_FESTIVAL = '.4.';
class SoTnUnavailable extends RuntimeException {}
/* Fixture adapter. */ function cache_get($key, $ttl) { return false; }
/* Fixture adapter. */ function cache_set($key, $data) {}
/* Fixture adapter. */ function soRateHit(...$args) { return true; }
/* Fixture adapter. */ function soIpHash($ip) { return ''; }
/* Fixture adapter. */ function soClientIp() { return '127.0.0.1'; }
/* Fixture adapter. */ function tnEscapeFilterValue($v) { return str_replace("'", "''", $v); }
/* Fixture adapter. */ function soEventSlug($e) { return 'fixture-'.$e['id']; }
/* Fixture adapter. */ function soSlug(...$args) { return 'fixture'; }
/* Fixture adapter. */ function getCategoryFallbackImage(...$args) { return '/images/fixture.webp'; }
/* Fixture adapter. */ function soImageFirst($events, ...$args) { return $events; }
/* Fixture adapter. */ function tnRequest($path, $params) {
    if (($_GET['fixture'] ?? '') === 'exception') throw new RuntimeException('Fixture failure');
    if (($_GET['fixture'] ?? '') !== 'ordered') return null;
    if (($_GET['kind'] ?? '') === 'lastminute' && !str_contains($params['filter'], 'date/date le '.date('Y-m-d',strtotime('+6 days')))) throw new RuntimeException('Last-minute window exceeds seven days');
    $events=[];
    for ($i=1;$i<=60;$i++) $events[]=['id'=>$i,'text'=>['name'=>'Fixture '.$i],'date'=>['date'=>date('Y-m-d',strtotime('2028-01-01 -'.$i.' days'))],'pricingInfo'=>['lowPrice'=>['text'=>['formatted'=>'$'.$i]]]];
    if (($params['sort'] ?? '') === 'date/date') $events=array_reverse($events);
    return ['results'=>array_slice($events,(($params['page'] ?? 1)-1)*$params['perPage'],$params['perPage']),'totalCount'=>60];
}
/* Fixture adapter. */ function getKeywordSearchResults($q) {
    if (!in_array($_GET['fixture'] ?? '', ['partial','lookup-down'], true)) return getTnEvents([])['results'];
    return ['performers'=>['results'=>[['id'=>42,'name'=>'Fixture artist'],['name'=>'Missing id']]],'venues'=>['results'=>[['id'=>43,'name'=>'Fixture venue'],['id'=>44]]]];
}
/* Fixture adapter. */ function getTnPerformersByIds($ids) { if (($_GET['fixture'] ?? '') === 'lookup-down') throw new SoTnUnavailable('Fixture lookup outage'); return [42=>['defaultCategory'=>['text'=>[],'ancestors'=>[[]]]]]; }
/* Fixture adapter. */ function soApiDegraded() { return ($_GET['fixture'] ?? '') === 'degraded'; }
/* Fixture adapter. */ function getTnEvents($params) { if (($_GET['fixture'] ?? '') === 'exception') throw new RuntimeException('Fixture failure'); return ['results'=>[], 'totalCount'=>0]; }
/* Fixture adapter. */ function getHeaderSearchEvents($p) { return getTnEvents($p); }
/* Fixture adapter. */ function getLoadMoreEvents($p) { return getTnEvents($p); }
/* Fixture adapter. */ function getKeywordSearchSuggestions($q) { return getTnEvents([])['results']; }
/* Fixture adapter. */ function soSlugWarmEvents($e) {}
/* Fixture adapter. */ function soEventSlugBundle($e) { return []; }
/* Fixture adapter. */ function soRenderListingRows($e) { return ''; }
PHP;
file_put_contents($dir . '/helpers.php', $helpers);
foreach (['load-events','load-more-events','keyword-search','get-home-feed','get-suggestions'] as $name) {
    $source = file_get_contents($root . '/ajax/' . $name . '.php');
    $source = str_replace("require_once __DIR__ . '/../functions.php';", "require_once __DIR__ . '/helpers.php';", $source);
    file_put_contents($dir . '/' . $name . '.php', $source);
}
$process = proc_open([PHP_BINARY, '-c', php_ini_loaded_file() ?: '', '-S', $address, '-t', $dir], [0=>['pipe','r'],1=>['file',$dir.'/server.log','a'],2=>['file',$dir.'/server.log','a']], $pipes);
if (!is_resource($process)) throw new RuntimeException('Server start failed');
register_shutdown_function(function () use ($process,$dir) { proc_terminate($process); proc_close($process); foreach (glob($dir.'/*') as $f) unlink($f); rmdir($dir); });
for ($i=0;$i<100;$i++) { $ready=@stream_socket_client('tcp://'.$address,$errno,$error,0.05); if ($ready) { fclose($ready); break; } usleep(20000); }
$request = function ($name, $params) use ($address) {
    $ch=curl_init('http://'.$address.'/'.$name.'.php?'.http_build_query($params));
    curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_HEADER=>true,CURLOPT_TIMEOUT=>5]);
    $raw=curl_exec($ch); $status=curl_getinfo($ch,CURLINFO_RESPONSE_CODE); $size=curl_getinfo($ch,CURLINFO_HEADER_SIZE);
    return [$status,substr($raw,0,$size),json_decode(substr($raw,$size),true,512,JSON_THROW_ON_ERROR)];
};
foreach (['load-events','load-more-events','keyword-search','get-home-feed','get-suggestions'] as $name) {
    foreach (['degraded','exception'] as $mode) {
        [$status,$headers,$body]=$request($name,['fixture'=>$mode,'q'=>'fixture','kind'=>'near']);
        if ($status < 500 || !str_contains($headers,'Cache-Control: no-store') || empty($body['error'])) throw new RuntimeException($name.' caches or disguises an outage');
    }
}
[$status,$headers,$body]=$request('load-events',['when'=>'today','startDate'=>'2027-02-30','endDate'=>'2027-03-01','sort'=>'price','lat'=>'30.123','lng'=>'-97.712','pid'=>'42']);
if ($status!==200 || !str_contains($body['params']['filter'],'date/date ge '.date('Y-m-d')) || !str_contains($body['params']['filter'],'hasTickets eq true') || $body['params']['sort']!=='pricingInfo/lowPrice/value' || $body['params']['geoFilter']!=='nearby(30.100000,-97.700000,3000mi)' || $body['params']['performerFilter']!=='id eq 42') throw new RuntimeException('Invalid range fallback, price or location query failed');
[$status,$headers,$body]=$request('load-events',['startDate'=>'2027-01-01','endDate'=>'2027-01-10','sort'=>'soonest']);
if (!str_contains($body['params']['filter'],'date/date le 2027-01-10') || $body['params']['sort']!=='date/date') throw new RuntimeException('Custom range sorting failed');
echo "ok   real endpoints: outage statuses and no-store, strict dates, snapped geo, performer scope, price/soonest queries\n";

foreach (['trending','lastminute'] as $kind) {
    [$status,$headers,$body]=$request('get-home-feed',['fixture'=>'degraded','kind'=>$kind]);
    if ($status!==503 || !str_contains($headers,'Cache-Control: no-store')) throw new RuntimeException('Homepage outage cached as empty success');
}
foreach (['price'=>[1,13,49],'soonest'=>[60,48,12]] as $sort=>$firstIds) {
    foreach ([1,2,5] as $i=>$page) {
        [$status,$headers,$body]=$request('get-home-feed',['fixture'=>'ordered','kind'=>'near','sort'=>$sort,'page'=>$page]);
        if ($status!==200 || count($body['events'])!==12 || $body['events'][0]['id']!==$firstIds[$i] || $body['hasMore']!==($page<5)) throw new RuntimeException('Nationwide '.$sort.' order or pagination failed');
    }
}
[$status,$headers,$body]=$request('get-suggestions',['fixture'=>'partial','q'=>'fixture']);
if ($status!==200 || count($body)!==2 || $body[0]['meta']!=='' || $body[1]['meta']!=='') throw new RuntimeException('Incomplete suggestion fields failed');
[$status,$headers,$body]=$request('get-home-feed',['fixture'=>'ordered','kind'=>'lastminute']);
if ($status!==200 || count($body['events'])!==10) throw new RuntimeException('Last-minute inclusive window failed');
echo "ok   nationwide price/soonest order and five-page continuation, homepage outage statuses, incomplete suggestion fields\n";

[$status,$headers,$body]=$request('get-suggestions',['fixture'=>'lookup-down','q'=>'fixture']);
if ($status!==503 || !str_contains($headers,'Cache-Control: no-store') || empty($body['error'])) throw new RuntimeException('Suggestion enrichment failure cached');
echo "ok   suggestion enrichment outage is a retryable no-store response\n";
