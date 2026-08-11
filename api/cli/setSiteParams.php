<?php
use Meshistoires\Api\utils\opt;
use Meshistoires\Api\model\siteparams;
use Meshistoires\Api\backend\db;
use Meshistoires\Api\backend\stockage;

require dirname(__FILE__, 2) . '/vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(dirname(__FILE__, 2));
$dotenv->load();

$dbRes = db::get_res();
$stockageRes = stockage::get_res();

$dir = $_ENV['BASE_DIR'] . '/data/siteParams/';
$ymlFile = $dir . 'siteParams.yaml';
if(!is_file($ymlFile)){
  print_r('Error ' . $ymlFile . ' non trouvé');
  print_r(PHP_EOL);
}
$yml = opt::yaml_parse_file($ymlFile);

foreach($yml as $ar){
  $m = new siteparams();
  $m->name = $ar['name'];
  $cpt = $dbRes['class']->count(
    col: "siteparams",
    param: ['name' => $m->name]
  );
  $create = false;
  if($cpt == 0){
    $m->genUuid();
    $m->newDate();
    $create = true;
  }else{
    $doc = $dbRes['class']->getOne(
      col: "siteparams",
      param: ['name' => $m->name]
    );
    foreach($m as $k => $v){
      $doc = json_decode(json_encode($doc));
      if(isset($doc->{$k}))
        $m->{$k} = $doc->{$k};
    }
    $m->dateUpdate = time();
  }
  foreach($ar['imgs'] as $img){
    $file = $dir . '/imgs/' . $img;
    if(is_file($file)){
      $filename = $stockageRes['class']::post($file);
      $m->imagesUuid[] = $filename;
    }
  }
  if($create){
    $dbRes['class']->post(
      col: "siteparams",
      param: $m->_toArray()
    );
  }else{
    print_r($m);
    $dbRes['class']->put(
      col: "siteparams",
      uuid: $m->uuid,
      param: $m->_toArray()
    );
  }
}
