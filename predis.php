<?php
include 'constants.php';
require 'vendor/autoload.php';

use Predis\Client as PredisClient;

$r = new PredisClient([
  'scheme'   => 'tcp',
  'host'     => REDIS_HOST,
  'port'     => REDIS_PORT,
  'password' => REDIS_PASSWORD,
  'database' => 0,
]);

/*echo $r->set('foo', 'bar'), PHP_EOL;
// >>> OK

echo $r->get('foo'), PHP_EOL;
// >>> bar

$r->hset('user-session:123', 'name', 'John');
$r->hset('user-session:123', 'surname', 'Smith');
$r->hset('user-session:123', 'company', 'Redis');
$r->hset('user-session:123', 'age', 29);

echo var_export($r->hgetall('user-session:123')), PHP_EOL;*/
/* >>>
array (
  'name' => 'John',
  'surname' => 'Smith',
  'company' => 'Redis',
  'age' => '29',
)
*/
