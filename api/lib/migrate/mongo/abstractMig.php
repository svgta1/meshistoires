<?php
namespace Meshistoires\Api\migrate\mongo;
use Meshistoires\Api\backend\db;

abstract class abstractMig
{
  public function testMigrate()
  {
    $cursor = $this->dbRes['class']::get(
      col: $this->col,
      param: []
    );
    foreach($cursor as $doc){
      $upgrade = false;
      foreach($this->model as $k => $v){
        if(!isset($doc->{$k})){
          $upgrade = true;
          $doc->{$k} = $v;
        }
      }
      if($upgrade)
        $this->doMigrate($doc);
    }
  }
  protected function docToArray(\MongoDB\Model\BSONDocument $doc): array
  {
    return json_decode(\MongoDB\BSON\toJSON(\MongoDB\BSON\fromPHP($doc)), TRUE);
  }
  protected function getFromId(\MongoDB\BSON\ObjectId $id): int
  {
    return $id->getTimestamp();
  }
}
