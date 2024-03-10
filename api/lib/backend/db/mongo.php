<?php
namespace Meshistoires\Api\backend\db;
use Meshistoires\Api\utils\trace;
use Meshistoires\Api\backend\db;

class mongo implements dbInt
{
  private static $res = null;
  public static function replace(
    string $col,
    string $uuid,
    array $replace
  )
  {
    $replace = self::get_res()->{$col}->replaceOne(
      ['uuid' => $uuid],
      $replace
    );
    return $replace->getModifiedCount();
  }
  public static function deleteMany(
    string $col,
    array $param
  ){
    $delete = self::get_res()->{$col}->deleteMany($param);
    return $delete->getDeletedCount();
  }
  public static function delete(
    string $col,
    string $uuid
  ){
    $delete = self::get_res()->{$col}->deleteOne(['uuid' => $uuid]);
    return $delete->getDeletedCount();
  }
  public static function putMany(
    string $col,
    array $filter,
    array $param
  ){
    $update = self::get_res()->{$col}->updateMany(
      $filter,
      ['$set' => $param]
    );
    return $update->getModifiedCount();
  }
  public static function put(
    string $col,
    string $uuid,
    array $param
  ){
    $update = self::get_res()->{$col}->updateOne(
      ['uuid' => $uuid],
      ['$set' => $param]
    );
    return $update->getModifiedCount();
  }
  public static function post(
    string $col,
    array $param = []
  ){
    $insert = self::get_res()->{$col}->insertOne($param);
    if($insert->getInsertedCount() !== 1)
      return false;
    return $insert->getInsertedId();
  }
  public static function count(
    string $col,
    array $param = []
  )
  {
    return self::get_res()->{$col}->count($param);
  }
  public static function getOne(
    string $col,
    array $param = [],
    int $skip = 0,
    ?array $projection = null
  )
  {
    $params = [
      'skip' => $skip,
    ];
    if(!is_null($projection)){
      $proj = [];
      foreach($projection as $v){
        $proj[$v] = 1;
      }

      $params['projection'] = $proj;
    }
    return self::get_res()->{$col}->findOne($param, $params);
  }
  public static function get(
    string $col,
    array $param = [],
    int $limit = 0,
    array $order = ['_id' => 1],
    int $skip = 0,
    ?array $projection = null
  )
  {
    $params = [
      'skip' => $skip,
      'sort' => $order,
      'limit' => $limit
    ];
    if(!is_null($projection)){
      $proj = [];
      foreach($projection as $v){
        $proj[$v] = 1;
      }

      $params['projection'] = $proj;
    }
    $cursor = self::get_res()->{$col}->find($param, $params);
    foreach($cursor as $doc)
      yield $doc;
  }

  private static function get_res(){
    if(is_null(self::$res))
      self::$res = db::get_res();
    return self::$res['res'];
  }
}
