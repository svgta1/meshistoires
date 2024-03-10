<?php
namespace Meshistoires\Api\backend\cache;
use Meshistoires\Api\backend\cache;
use Svgta\Lib\JWT;

abstract class cacheAbs implements cacheInt
{
  protected static $res = null;
  protected static $config = null;

  protected static function get_res()
  {
    if(is_null(self::$res))
      self::$res = cache::get_res();
    self::$config = \yaml_parse_file($_ENV['CACHE_YAML']);
    return self::$res['res'];
  }
  protected static function enc(string $str): string
  {
    if(!isset(self::$config['enc']) || !self::$config['enc'])
      return $str;
    return JWT::encrypt(
      $str,
      self::$config['enc_alg'],
      self::$config['enc_enc'],
      self::$config['enc_key']
    );
  }
  protected static function dec(string $jwe): ?string
  {
    if(!isset(self::$config['enc']) || !self::$config['enc'])
      return $jwe;
    return JWT::decrypt($jwe, null, self::$config['enc_key']);
  }
  protected static function getConf()
  {
    self::$config = \yaml_parse_file($_ENV['CACHE_YAML']);
  }
}
