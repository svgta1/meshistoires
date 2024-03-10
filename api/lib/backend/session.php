<?php
namespace Meshistoires\Api\backend;
use Meshistoires\Api\utils\inException;

class session implements gInterface
{
  private $config = null;
  private static $res = null;
  public function __construct()
  {
    if(isset($_ENV['SESSION_YAML']) && is_file($_ENV['SESSION_YAML']))
    {
      $this->config =  \yaml_parse_file($_ENV['SESSION_YAML']);
    }
  }

  public static function get_res(){
    if(is_null(self::$res)){
      $c = new self();
      $c->set_res();
    }
    if(is_null(self::$res))
      throw new inException('No session ressource set');
    return self::$res;
  }

  public function set_res(): void
  {
    if(is_null($this->config))
      throw new inException('No session set');
    $type = $this->config['type'];
    if($type == 'cache'){
      $this->config = \yaml_parse_file($_ENV['CACHE_YAML']);
      $type = $this->config['type'];
    }
    $class_res = __namespace__ . '\\session\\' . $type . '_res';
    $c_res = new $class_res();
    $class = __namespace__ . '\\session\\' . $type;

    self::$res = [
      'res' => $c_res->res($this->config),
      'class' => new $class(),
      'config' => $this->config,
    ];
  }
}
