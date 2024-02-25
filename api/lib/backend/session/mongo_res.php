<?php
namespace Meshistoires\Api\backend\session;
use Meshistoires\Api\backend\cache\mongo_res as cache;
use Meshistoires\Api\backend\ressourceInterface;

class mongo_res implements ressourceInterface
{
  public function res(array $config)
  {
    $cache = new cache();
    return $cache->res($config);
  }
}
