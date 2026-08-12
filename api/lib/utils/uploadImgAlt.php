<?php
namespace Meshistoires\Api\utils;
use Meshistoires\Api\backend\db;
use Meshistoires\Api\utils\seo;
use Meshistoires\Api\backend\stockage;
use Meshistoires\Api\model\altImages;

class uploadImgAlt{
  private $dbRes = null;
  private $stockageRes = null;
  private $dataDir = null;
  private $oeuvreCursor = null;
  private $imgToTrait = [];

  public function __construct()
  {
    $this->dbRes = db::get_res();
    $this->stockageRes = stockage::get_res();
    $this->dataDir = $_ENV['BASE_DIR'] . '/data/histoires';
    if(!is_dir($this->dataDir))
      mkdir($this->dataDir, 0700, true);
  }
  public function genDir()
  {
    if(is_null($this->oeuvreCursor))
      $this->getOeuvres();
    foreach($this->oeuvreCursor as $doc){
      print_r('Traitement de ' . $doc->title . PHP_EOL);
      $hDir = $this->dataDir. '/' . seo::seofy($doc->title);
      $altImgDir = $hDir . '/altImgs';
      if(!is_dir($altImgDir)){
        print_r('Création de ' . $altImgDir . PHP_EOL);
        mkdir($altImgDir, 0700, true);
      }
      $l = scandir($altImgDir);
      $update = false;
      foreach($l as $file){
        if(is_file($altImgDir . '/' . $file)){
          $update = true;
          $info = pathinfo($file);
          $altImg = new altImages();
          $altImg->newDate();
          $altImg->oeuvreUuid = $doc->uuid;
          $altImg->name = $info['filename'];

          $retImg = $this->uploadImg($altImgDir . '/' . $file);
          $altImg->uuid = $retImg['uuid'];
          $altImg->thmbWidth = $retImg['thumb']->metadata->width ?? 200;
          $altImg->thmbHeight = $retImg['thumb']->metadata->height ?? 300;
          $cptAltImg = $this->dbRes['class']::count(
            col: 'altImages',
            param: ['uuid' => $altImg->uuid, "oeuvreUuid" => $altImg->oeuvreUuid]
          );
          if($cptAltImg == 0){
            $this->dbRes['class']::post(
              col: 'altImages',
              param: $altImg->_toArray()
            );
          }else{
            $this->dbRes['class']::put(
              col: 'altImages',
              uuid: $altImg->uuid,
              param: $altImg->_toArray()
            );
          }
          $this->imgToTrait[] = $altImg->_toArray();
        }
      }
      if($update){
        $this->dbRes['class']::put(
          col: 'oeuvres',
          uuid: $doc->uuid,
          param: ['dateUpdate' => time()]
        );
      }
    }
    print_r($this->imgToTrait);
  }
  private function uploadImg($path)
  {
    $filename = $this->stockageRes['class']::post($path);
    $thmb = $this->stockageRes['class']::getThmb300Info($filename);
    $ret = [
      'uuid' => $filename,
      'thumb' => $thmb['metadata']
    ];
    return $ret;
  }
  private function getOeuvres()
  {
    $this->oeuvreCursor = $this->dbRes['class']::get(
      col: 'oeuvres',
      projection: ['title', 'uuid']
    );
  }
}