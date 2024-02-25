<?php
namespace Meshistoires\Api\migrate\mongo;
use Meshistoires\Api\backend\db;
use Meshistoires\Api\model\menu as mMenu;

class menus extends abstractMig
{
  public function __construct()
  {
    $this->dbRes = db::get_res();
    $this->col = 'menus';
    $this->model = new mMenu();
  }

  public function doMigrate($doc)
  {
    if($doc->dateCreate == 0)
      $doc->dateCreate = $this->getFromId($doc->_id);
    if($doc->dateUpdate == 0)
      $doc->dateUpdate = $this->getFromId($doc->_id);

    $ar = $this->docToArray($doc);
    unset($ar['_id']);
    unset($ar['uuid']);
    $cursor = $this->dbRes['class']::put(
      col: $this->col,
      uuid: $doc->uuid,
      param: $ar
    );
  }
}
