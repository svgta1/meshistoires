<?php
namespace Meshistoires\Api\backend\session;
use Meshistoires\Api\utils\trace;
use Meshistoires\Api\backend\session;
use Meshistoires\Api\backend\cache\memcache as cacheMemcache;

class memcache extends sessionAbs
{
  public function close(): bool
  {
    return true;
  }
  public function destroy(string $id): bool
  {
    cacheMemcache::delete($this->name . $id);
    return true;
  }
  public function gc($max_lifetime)
  {
    return false;
  }
  public function open($path, $name): bool
  {
    return true;
  }
  public function read(string $id): string|false
  {
    $res = cacheMemcache::get($this->name . $id);
    self::get_res();
    if(self::$config['enc'] && !is_null($res) && $res)
      $res = self::dec($res);
    return $res;
  }
  public function write(string $id, string $data): bool
  {
    self::get_res();
    if(self::$config['enc'])
      $data = self::enc($data);
    cacheMemcache::add($this->name . $id, $data);
    return true;
  }
  protected static function get_res(){
    if(is_null(self::$res))
      self::$res = session::get_res();
    self::$config = \yaml_parse_file($_ENV['SESSION_YAML']);
    return self::$res['res'];
  }
  protected static function enc(string $str): string
  {
    return JWT::encrypt(
      $str,
      self::$config['enc_alg'],
      self::$config['enc_enc'],
      self::$config['enc_key']
    );
  }
  protected static function dec(string $jwe): ?string
  {
    return JWT::decrypt($jwe, null, self::$config['enc_key']);
  }
}
