<?php
use Meshistoires\Api\backend\db;
require dirname(__FILE__, 2) . '/vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(dirname(__FILE__, 2));
$dotenv->load();

$db = new db();
$res = $db->get_res();

print_r(PHP_EOL . $res['res']->getDatabaseName() . PHP_EOL . PHP_EOL);

$lCollection = $res['res']->listCollectionNames();
foreach($lCollection as $col){
  print_r($col . PHP_EOL);
  print_r("Nombre d'enregistrements : " . $res['res']->{$col}->count() . PHP_EOL);
  print_r(PHP_EOL);
}
