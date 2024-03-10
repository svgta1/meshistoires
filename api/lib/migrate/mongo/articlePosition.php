<?php
namespace Meshistoires\Api\migrate\mongo;
use Meshistoires\Api\backend\db;

class articlePosition
{
  public function __construct()
  {
    $this->dbRes = db::get_res();
  }
  public function doMigration()
  {
    $menuCursor = $this->getMenuCursor();
    foreach($menuCursor as $doc){
      if(is_null($doc))
        continue;
      if(!isset($doc->articles))
        continue;
      if(!is_null($doc->articles))
        $this->setPosition($doc->articles);
    }
  }
  private function setPosition(\MongoDB\Model\BSONArray $articles)
  {
    foreach($articles as $k => $uuid){
      $position = $k + 1;
      $this->dbRes['class']::put(
        col: 'articles',
        uuid: $uuid,
        param: ['position' => $position]
      );
    }
  }
  private function getMenuCursor()
  {
    $cursor = $this->dbRes['class']::get(
      col: 'menus',
      param: [],
      projection: ['articles']
    );
    return $cursor;
  }
}