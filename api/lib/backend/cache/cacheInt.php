<?php
namespace Meshistoires\Api\backend\cache;
interface cacheInt
{
  public static function get(string $id): ?string;
  public static function delete(string $id);
  public static function deleteTs();
  public static function add(string $id, string $data, int $lifetTime = 3600, string $type = "cache");
}
