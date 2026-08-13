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
  if($info['status'] == 'Deleted'){
    $toDelete[] = $doc->filename;
  }
}

foreach($toDelete as $uuid){
  $doc = $dbRes['class']->getOne(
    col: "siteParamsStats",
    param: ['uuid' => $uuid, 'deleted' => true]
  );
  if(!is_null($doc)){
    $dbRes['class']->delete(
      col: "siteParamsStats",
      uuid: $uuid
    );
    $stockRes['class']::delete($uuid);
    print_r('suppression de siteParamsStats et stockage de ' . $uuid . PHP_EOL);
    continue;
  }

  $doc = $dbRes['class']->getOne(
    col: "altImages",
    param: ['uuid' => $uuid, 'deleted' => true],
  );
  if(!is_null($doc)){
    $dbRes['class']->delete(
      col: "altImages",
      uuid: $uuid
    );
    $stockRes['class']::delete($uuid);
    print_r('suppression de altImages et stockage de ' . $uuid . PHP_EOL);
    continue;
  }
}

print_r('Liste supprimés : ' . PHP_EOL);
print_r($toDelete);