<?php
namespace Meshistoires\Api\backend;
use Meshistoires\Api\utils\inException;

class db implements gInterface
{
  private $config = null;
  private static $res = null;
  public function __construct()
  {
    if(isset($_ENV['DB_YAML']) && is_file($_ENV['DB_YAML']))
    {
      $this->config =  \yaml_parse_file($_ENV['DB_YAML']);
    }
  }

  public static function get_res(){
    if(is_null(self::$res)){
      $c = new self();
      $c->set_res();
    }
    if(is_null(self::$res))
      throw new inException('No DB ressource set');
    return self::$res;
  }

  public function set_res(): void
  {
    if(is_null($this->config))
      throw new inException('No DB set');
    $type = $this->config['type'];
    $class_res = __namespace__ . '\\db\\' . $type . '_res';
    $c_res = new $class_res();
    $class = __namespace__ . '\\db\\' . $type;
    self::$res = [
      'res' => $c_res->res($this->config),
      'class' => new $class()
    ];
  }
}
