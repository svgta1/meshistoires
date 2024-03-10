<?php
namespace Meshistoires\Api\backend;
use Meshistoires\Api\utils\inException;

class cache implements gInterface
{
  private $config = null;
  private static $res = null;
  public function __construct()
  {
    if(isset($_ENV['CACHE_YAML']) && is_file($_ENV['CACHE_YAML']))
    {
      $this->config =  \yaml_parse_file($_ENV['CACHE_YAML']);
    }
  }

  public static function get_res()
  {
    if(is_null(self::$res)){
      $c = new self();
      $c->set_res();
    }
    if(is_null(self::$res))
      throw new inException('No cache ressource set');
    return self::$res;
  }

  public function set_res(): void
  {
    if(is_null($this->config))
      throw new inException('No cache set');
    $type = $this->config['type'];
    $class_res = __namespace__ . '\\cache\\' . $type . '_res';
    $c_res = new $class_res();
    $class = __namespace__ . '\\cache\\' . $type;
    self::$res = [
      'res' => $c_res->res($this->config),
      'class' => new $class(),
      'config' => $this->config,
    ];
  }
}
