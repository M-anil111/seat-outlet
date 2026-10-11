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
foreach (['listingDateRange'] as $name) {
    if (!preg_match('/function ' . $name . '\(.*?\n}/s', $functions, $m)) throw new RuntimeException('Missing helper');
    $helpers .= $m[0];
}
$helpers .= "function soEventHorizon() { return date('Y-m-d', strtotime('+3 years')); }";
$helpers .= <<<'PHP'
const LISTING_WHEN = ['today'=>'Today','weekend'=>'This weekend','week'=>'Next 7 days','month'=>'Next 30 days'];
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
foreach (['load-events','load-more-events','keyword-search'] as $name) {
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
foreach (['load-events','load-more-events','keyword-search'] as $name) {
    foreach (['degraded','exception'] as $mode) {
        [$status,$headers,$body]=$request($name,['fixture'=>$mode,'q'=>'fixture']);
        if ($status < 500 || !str_contains($headers,'Cache-Control: no-store') || empty($body['error'])) throw new RuntimeException($name.' caches or disguises an outage');
    }
}
[$status,$headers,$body]=$request('load-events',['when'=>'today','startDate'=>'2027-02-30','endDate'=>'2027-03-01','sort'=>'price','lat'=>'30.123','lng'=>'-97.712','pid'=>'42']);
if ($status!==200 || !str_contains($body['params']['filter'],'date/date ge '.date('Y-m-d')) || !str_contains($body['params']['filter'],'hasTickets eq true') || $body['params']['sort']!=='pricingInfo/lowPrice/value' || $body['params']['geoFilter']!=='nearby(30.100000,-97.700000,3000mi)' || $body['params']['performerFilter']!=='id eq 42') throw new RuntimeException('Invalid range fallback, price or location query failed');
[$status,$headers,$body]=$request('load-events',['startDate'=>'2027-01-01','endDate'=>'2027-01-10','sort'=>'soonest']);
if (!str_contains($body['params']['filter'],'date/date le 2027-01-10') || $body['params']['sort']!=='date/date') throw new RuntimeException('Custom range sorting failed');
echo "ok   real endpoints: outage statuses and no-store, strict dates, snapped geo, performer scope, price/soonest queries\n";
