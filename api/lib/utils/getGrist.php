<?php
namespace Meshistoires\Api\utils;
use GuzzleHttp\Client;
use Meshistoires\Api\model\oeuvre as mO;
use Meshistoires\Api\model\attachments as mA;
use Meshistoires\Api\model\mPublic as mP;
use Meshistoires\Api\model\categories as mCa;
use Meshistoires\Api\model\collection as mCo;
use Meshistoires\Api\backend\stockage;
use Meshistoires\Api\backend\db;
use Meshistoires\Api\utils\cache;

class getGrist
{
  private $api_key = null;
  private $api_id = null;
  private $api_uri = null;
  private $tables = [
    'oeuvres' => 'Liste_des_lectures',
    'collections' => 'Collections',
    'public' => 'Public',
    'categories' => 'Categories'
  ];
  private $uri = [];
  private $gClient = null;
  private $stockage = null;
  private $collections = [
    'oeuvres' => 'oeuvres',
    'collections' => 'collections',
    'public' => 'public',
    'categories' => 'categories'
  ];
  private $dbRes = null;
  private $hasUpdate = false;

  public function __construct(string $api_key, string $api_id, string $api_uri){
    $this->api_key = $api_key;
    $this->api_id = $api_id;
    $this->api_uri = $api_uri;
    $this->uri['attachments'] = $this->api_uri . '/' . $this->api_id . '/attachments';
    foreach($this->tables as $k => $v)
      $this->setUriTable($k);
    $this->gClient = new Client();
    $this->stockage = stockage::get_res();
    $this->dbRes = db::get_res();
  }
  public function delCache()
  {
    if($this->update = true){
      cache::clean();
    }
  }
  public function getCat(){
    $res = $this->getClient($this->uri['categories'])->records;
    $col = $this->collections['categories'];
    foreach($res as $k=>$v){
      $create = false;
      $update = false;
      $sha = hash("sha256", json_encode($v));
      $doc = $this->dbRes['class']::getOne(
        col: $col,
        param: [
          'gristuuid' => $v->fields->gristId
        ]
      );
      if(is_null($doc)){
        $create = true;
        $this->hasUpdate = true;
      }else{
        if($doc->sha !== $sha){
          $update = true;
          $this->hasUpdate = true;
        }
      }
      if($create){
        $m = new mCa();
        $m->genUuid();
        $m->newDAte();
        $m->gristId = $v->id;
        $m->gristuuid = $v->fields->gristId;
        $m->name = $v->fields->Categories;
        $m->sha = $sha;

        print_r('Création de ' . $m->name . PHP_EOL);
        $this->dbRes['class']::post(
          col: $col,
          param: $m->_toArray()
        );
      }
      if($update){
        $m = new mCa();
        foreach($doc as $k => $val){
          if(isset($m->{$k}))
            $m->{$k} = $val;
        }
        $m->dateUpdate = time();
        $m->gristId = $v->id;
        $m->gristuuid = $v->fields->gristId;
        $m->name = $v->fields->Categories;
        $m->sha = $sha;
        $m->uuid = $doc->uuid;
        $m->dateCreate = $doc->dateCreate;

        print_r('Mise à jour de ' . $m->name . PHP_EOL);
        $this->dbRes['class']::put(
          col: $col,
          uuid: $doc->uuid,
          param: $m->_toArray()
        );
      }
    }
  }
  public function getPublic(){
    $res = $this->getClient($this->uri['public'])->records;
    $col = $this->collections['public'];
    foreach($res as $k=>$v){
      $create = false;
      $update = false;
      $sha = hash("sha256", json_encode($v));
      $doc = $this->dbRes['class']::getOne(
        col: $col,
        param: [
          'gristuuid' => $v->fields->gristId
        ]
      );
      if(is_null($doc)){
        $create = true;
        $this->hasUpdate = true;
      }else{
        if($doc->sha !== $sha){
          $update = true;
          $this->hasUpdate = true;
        }
      }
      if($create){
        $m = new mP();
        $m->genUuid();
        $m->newDAte();
        $m->gristuuid = $v->fields->gristId;
        $m->gristId = $v->id;
        $m->name = $v->fields->Type_public;
        $m->sha = $sha;

        print_r('Création de ' . $m->name . PHP_EOL);
        $this->dbRes['class']::post(
          col: $col,
          param: $m->_toArray()
        );
      }
      if($update){
        $m = new mP();
        foreach($doc as $k => $val){
          if(isset($m->{$k}))
            $m->{$k} = $val;
        }
        $m->dateUpdate = time();
        $m->dateCreate = $doc->dateCreate;
        $m->gristId = $v->id;
        $m->name = $v->fields->Type_public;
        $m->sha = $sha;
        $m->uuid = $doc->uuid;

        print_r('Mise à jour de ' . $m->name . PHP_EOL);
        $this->dbRes['class']::put(
          col: $col,
          uuid: $doc->uuid,
          param: $m->_toArray()
        );
      }
    }
  }
  public function getCol(){
    $res = $this->getClient($this->uri['collections'])->records;
    $col = $this->collections['collections'];
    foreach($res as $k=>$v){
      $create = false;
      $update = false;
      $sha = hash("sha256", json_encode($v));
      $doc = $this->dbRes['class']::getOne(
        col: $col,
        param: [
          'gristuuid' => $v->fields->gristId
        ]
      );
      if($doc && $doc->uuid == ""){
        $this->dbRes['class']::deleteMany(
          col: $col,
          param: [
            'uuid' => ""
          ]
        );
        print_r("Erreur UUID sur " . $doc->name . ' PHP_EOL');
        print_r("Traitement à relancer" . PHP_EOL);
        continue;
      }
      if(is_null($doc)){
        $create = true;
        $this->hasUpdate = true;
      }else{
        if(!isset($doc->sha) || $doc->sha !== $sha){
          $update = true;
          $this->hasUpdate = true;
        }
      }
      if($create){
        $m = new mCo();
        $m->genUuid();
        $m->newDAte();
        $m->dateUpdate = $v->fields->lastUpdate;
        $m->gristuuid = $v->fields->gristId;
        $m->gristId = $v->id;
        $m->name = $v->fields->Collection;
        $m->desc = $v->fields->Description;
        $m->distanteLink = $v->fields->Lien;
        $m->sha = $sha;
        if(isset($v->fields->Image))
          $m->imageUuid = $this->getAttachmentDl($v->fields->Image[1]);

        print_r('Création de ' . $m->name . PHP_EOL);
        $this->dbRes['class']::post(
          col: $col,
          param: $m->_toArray()
        );
      }
      if($update){
        $m = new mCo();
        foreach($doc as $k => $val){
          if(isset($m->{$k}))
            $m->{$k} = $val;
        }
        $m->uuid = $doc->uuid;
        $m->dateUpdate = $v->fields->lastUpdate;
        $m->name = $v->fields->Collection;
        $m->gristId = $v->id;
        $m->desc = $v->fields->Description;
        $m->distanteLink = $v->fields->Lien;
        $m->sha = $sha;
        if(isset($v->fields->Image))
          $m->imageUuid = $this->getAttachmentDl($v->fields->Image[1]);

        print_r('Mise à jour de ' . $m->name . PHP_EOL);
        $this->dbRes['class']::put(
          col: $col,
          uuid: $doc->uuid,
          param: $m->_toArray()
        );
      }
    }
  }
  public function getOeuvres(){
    $res = $this->getClient($this->uri['oeuvres'])->records;
    $col = $this->collections['oeuvres'];
    foreach($res as $k=>$v){
      $create = false;
      $update = false;
      $sha = hash("sha256", json_encode($v));
      $doc = $this->dbRes['class']::getOne(
        col: $col,
        param: [
          'gristuuid' => $v->fields->gristId
        ]
      );
      if(is_null($doc)){
        $create = true;
        $this->hasUpdate = true;
      }else{
        if(!isset($doc->sha) || $doc->sha !== $sha){
          $update = true;
          $this->hasUpdate = true;
        }
      }
      if($create){
        print_r('Création de ' . $v->fields->Titre . PHP_EOL);
        $m = new mO();
        $m->genUuid();
        $m->dateCreate = $v->fields->Date_publi;
        $m->dateUpdate = $v->fields->lastUpdate;
        $m->gristuuid = $v->fields->gristId;
        $m->gristId = $v->id;
        $m->title = $v->fields->Titre;
        $m->desc = $v->fields->Accroche;
        $m->distanteLink = $v->fields->Lien;
        $m->collectionUuid = $this->getColUuid($v->fields->Collection);
        $m->publicUuid = $this->getPublicUuid($v->fields->Public);
        $m->sha = $sha;
        $m->keywords = $v->fields->keywords;
        if(isset($v->fields->Image))
          $m->imageUuid = $this->getAttachmentDl($v->fields->Image[1]);
        $aC = [];
        if(!is_null($v->fields->Categorie))
        foreach($v->fields->Categorie as $a){
          $ret = $this->getCatUuid($a);
          if(!is_null($ret))
            $aC[] = $ret;
        }
        $m->categorieUuid = $aC;

        $this->dbRes['class']::post(
          col: $col,
          param: $m->_toArray()
        );
      }
      if($update){
        print_r('Mise à jour de ' . $v->fields->Titre . PHP_EOL);
        $m = new mO();
        foreach($doc as $k => $val){
          if(isset($m->{$k}))
            $m->{$k} = $val;
        }
        $m->uuid = $doc->uuid;
        $m->dateCreate = $v->fields->Date_publi;
        $m->dateUpdate = $v->fields->lastUpdate;
        $m->title = $v->fields->Titre;
        $m->gristId = $v->id;
        $m->desc = $v->fields->Accroche;
        $m->gristuuid = $v->fields->gristId;
        $m->distanteLink = $v->fields->Lien;
        $m->collectionUuid = $this->getColUuid($v->fields->Collection);
        $m->publicUuid = $this->getPublicUuid($v->fields->Public);
        $m->sha = $sha;
        $m->keywords = $v->fields->keywords;
        if(isset($v->fields->Image))
          $m->imageUuid = $this->getAttachmentDl($v->fields->Image[1]);
        $aC = [];
        if(!is_null($v->fields->Categorie))
        foreach($v->fields->Categorie as $a){
          $ret = $this->getCatUuid($a);
          if(!is_null($ret))
            $aC[] = $ret;
        }
        $m->categorieUuid = $aC;
        $this->dbRes['class']::put(
          col: $col,
          uuid: $doc->uuid,
          param: $m->_toArray()
        );
      }
    }
  }
  public function deleteNoUuid(){
    foreach($this->collections as $col){
      $this->dbRes['class']::deleteMany(
        col: $col,
        param: ['uuid' => ""]
      );
    }
  }
  private function getCatUuid($gristId){
    $col = $this->collections['categories'];
    $doc = $this->dbRes['class']::getOne(
      col: $col,
      param: [
        'gristId' => $gristId
      ]
    );
    if(!is_null($doc))
      return $doc->uuid;
    return null;
  }
  private function getColUuid(int $gristId){
    $col = $this->collections['collections'];
    $doc = $this->dbRes['class']::getOne(
      col: $col,
      param: [
        'gristId' => $gristId
      ]
    );
    if(!is_null($doc))
      return $doc->uuid;
    return null;
  }
  private function getPublicUuid(int $gristId){
    $col = $this->collections['public'];
    $doc = $this->dbRes['class']::getOne(
      col: $col,
      param: [
        'gristId' => $gristId
      ]
    );
    if(!is_null($doc))
      return $doc->uuid;
    return null;
  }
  public function getAttachments(){
    return $this->getClient($this->uri['attachments']);
  }
  public function getAttachmentDl(int $id){
    $uriData = $this->uri['attachments'] . '/' . $id;
    $uriDownload = $uriData . '/download';
    $data = $this->getClient($uriData);
    $contents = $this->getClient($uriDownload);
    $tmp = sys_get_temp_dir() . '/' . $data->fileName;
    file_put_contents($tmp, $contents);
    try{
      $filename = $this->stockage['class']::post($tmp);
    }catch(\Throwable $t){
      $this->stockage['class']::delete($tmp);
      $filename = $this->stockage['class']::post($tmp);
    }
    return $filename;
  }
  private function setUriTable($table){
    $this->uri[$table] = $this->api_uri . '/' . $this->api_id . '/tables/' . $this->tables[$table] . '/records';
  }
  private function getClient($api_uri){
    $ret = $this->gClient->get($api_uri,[
      'headers' => [
        'Authorization' => "Bearer " . $this->api_key
      ]
    ]);
    $res = $ret->getBody()->getContents();
    if($this->isJson($res))
      return json_decode($res);
    else
      return $res;
  }
  private function isJson($string) {
    json_decode($string);
    return json_last_error() === JSON_ERROR_NONE;
  }
}