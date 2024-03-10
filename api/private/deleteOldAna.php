<?php
use Meshistoires\Api\backend\db;
require dirname(__FILE__, 2) . '/vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(dirname(__FILE__, 2));
$dotenv->load();

$dbRes = db::get_res();
$cpt = $dbRes['class']::count(col: "analytic");
echo 'Nombre d\'enregistrements : ' . $cpt . PHP_EOL;

$delay = 60 * 60 * 24 * 365 * 2; //2 ans
$time = time() - $delay;
$res = $dbRes['class']::deleteMany(col: "analytic", param: ['createTs' => ['$lte' => $time]]);
echo 'Nombre de suppressions : ' . $res . PHP_EOL;
