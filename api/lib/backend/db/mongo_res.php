<?php
namespace Meshistoires\Api\backend\db;
use Meshistoires\Api\utils\trace;
use Meshistoires\Api\backend\static\mongo;
use Meshistoires\Api\backend\ressourceInterface;
use MongoDB\Client;

class mongo_res implements ressourceInterface
{
  public function res(array $config)
  {
    if(is_null(mongo::$res)){
      $driver = is_array($config["driver"]) ? $config["driver"] : [];
      $conn = new Client($config["uri"], $config["options"], $driver);
      $db = $config['db'];
      mongo::$res = $conn->{$db};
    }
    return mongo::$res;
  }
}
