<?php
namespace Meshistoires\Api\utils;
use Meshistoires\Api\backend\cache as bckCache;

class cache
{
  public static function clean()
  {
    $res = self::getRes();
    $res['class']::clean();
  }
  public static function set(string $id, string $data)
  {
    $res = self::getRes();
    if(is_null($res))
      return;
    $res['class']::add($id, $data);
  }
  public static function getXml(string $id)
  {
    $cache = self::_get($id);
    if($cache)
      response::xml(200, json_decode($cache, TRUE));
  }
  public static function get(string $id)
  {
    $cache = self::_get($id);
    if($cache){
      response::json(200, json_decode($cache, TRUE));
    }
  }
  public static function _get($id){
    $res = self::getRes();
    if(is_null($res))
      return null;
    $cache = $res['class']::get($id);
    return $cache;
  }
  private static function getRes()
  {
    if(isset($_ENV['USE_CACHE']) && $_ENV['USE_CACHE'])
      return bckCache::get_res();
    return null;
  }
}
