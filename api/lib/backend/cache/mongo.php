<?php
namespace Meshistoires\Api\backend\cache;
use Meshistoires\Api\utils\trace;

class mongo extends cacheAbs implements cacheInt
{
  private static $cacheData = [];

  public static function get(string $id): ?string
  {
    if(!(bool)$_ENV['USE_CACHE'])
      return false;

    if(isset(self::$cacheData[$id]))
      return self::$cacheData[$id];

    $apcuKey = $_ENV['DOMAIN'].$id;
    if($cache = apcu_fetch($apcuKey))
      return $cache;

    self::deleteTs();
    $col = 'cache';
    $doc = self::get_res()->{$col}->findOne(['uuid' => $id]);
    if(is_null($doc))
      return false;
    if($doc->exp < time()){
      self::delete($id);
      return false;
    }
    return self::dec($doc->str);
  }
  public static function clean()
  {
    self::$cacheData = [];
    apcu_clear_cache();
    $col = 'cache';
    self::get_res()->{$col}->deleteMany(['type' => 'cache']);
  }
  public static function delete(string $id)
  {
    if(isset(self::$cacheData[$id]))
      unset(self::$cacheData[$id]);

    $apcuKey = $_ENV['DOMAIN'].$id;
    apcu_delete($apcuKey);

    $col = 'cache';
    self::get_res()->{$col}->deleteOne(['uuid' => $id]);
  }
  public static function deleteTs()
  {
    $col = 'cache';
    self::get_res()->{$col}->deleteMany(['exp' => ['$lte' => time()]]);
  }
  public static function add(string $id, string $data, int $lifetTime = 3600, string $type = 'cache')
  {
    if(!(bool)$_ENV['USE_CACHE'])
      return;

    self::$cacheData[$id] = $data;
    $apcuKey = $_ENV['DOMAIN'].$id;
    apcu_store($apcuKey, $data, $_ENV['EXP_CACHE']);
    $data = self::enc($data);
    $col = 'cache';
    $cpt = self::get_res()->{$col}->count(['uuid' => $id]);
    if($cpt == 0){
      self::get_res()->{$col}->insertOne(['uuid' => $id, 'str' => $data, 'exp' => time() + $lifetTime, 'type' => $type]);
    }else{
      self::get_res()->{$col}->updateOne(['uuid' => $id], ['$set' => ['str' => $data, 'exp' => time() + $lifetTime]]);
    }
  }
}
