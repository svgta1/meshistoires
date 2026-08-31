<?php
use Meshistoires\Api\backend\db;
require dirname(__FILE__, 2) . '/vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(dirname(__FILE__, 2));
$dotenv->load();

$dbRes = db::get_res();
$cursor = $dbRes['class']::get(
  col: "siteParamsStats"
);
foreach($cursor as $doc){
  $dbRes['class']::putMany(
    col: "siteParamsStats",
    filter: ['deleted' => false],
    param: ['nbrAccess' => 0, 'dateUpdate' => time()]
  );
}