<?php
use Meshistoires\Api\utils\utilsMenu;
use Meshistoires\Api\backend\db;
use Meshistoires\Api\backend\stockage;

require dirname(__FILE__, 3) . '/vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(dirname(__FILE__, 3));
$dotenv->load();

$dbRes = db::get_res();
$stockRes = stockage::get_res();
$col = "thumb300.files";
$cursor = $dbRes['class']::get(
  col: $col,
);

$toDelete = [];
foreach($cursor as $doc){
  $info = utilsMenu::getImageFrom($doc->filename);
  if($info['from'] == 'unknown'){
    $toDelete[] = $doc->filename;
    $stockRes['class']::delete($doc->filename);
  }
}

print_r('suppression de : ' . PHP_EOL);
print_r($toDelete);