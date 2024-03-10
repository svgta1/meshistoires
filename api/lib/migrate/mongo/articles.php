<?php
namespace Meshistoires\Api\migrate\mongo;
use Meshistoires\Api\backend\db;
use Meshistoires\Api\model\article as mArticle;

class articles extends abstractMig
{
  public function __construct()
  {
    $this->dbRes = db::get_res();
    $this->col = 'articles';
    $this->model = new mArticle();
  }

  public function doMigrate($doc)
  {
    $this->setDate($doc);
  }
  private function setDate($doc)
  {
    if($doc->dateCreate == 0 || is_null($doc->dateCreate))
      $doc->dateCreate = $this->getFromId($doc->_id);
    if($doc->dateUpdate == 0 || is_null($doc->dateUpdate))
      $doc->dateUpdate = $this->getFromId($doc->_id);

    $ar = $this->docToArray($doc);
    unset($ar['_id']);
    unset($ar['uuid']);
    $this->dbRes['class']::put(
      col: $this->col,
      uuid: $doc->uuid,
      param: $ar
    );
  }
  public function changeModel()
  {
    $cursor = $this->dbRes['class']::get(col: $this->col);
    foreach($cursor as $doc){
      $m = new mArticle();
      $doc = json_decode(json_encode($doc), true);
      foreach($m as $k => $v){
        $m->{$k} = $doc[$k];
      }
      $this->dbRes['class']::replace(
        col: $this->col,
        uuid: $m->uuid,
        replace: $m->_toArray()
      );
    }
  }
}
