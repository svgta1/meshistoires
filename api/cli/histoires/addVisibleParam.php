<?php
use Meshistoires\Api\backend\db;

require dirname(__FILE__, 3) . '/vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(dirname(__FILE__, 3));
$dotenv->load();

$dbRes = db::get_res();
$cursor = $dbRes['class']::get(
  col: "oeuvres",
  param: ['visible' => ['$exists' => false]],
  projection: ['uuid']
);

foreach($cursor as $doc){
  $dbRes['class']::put(
    col: "oeuvres",
    uuid: $doc->uuid,
    param: ['visible' => true]
  );
}