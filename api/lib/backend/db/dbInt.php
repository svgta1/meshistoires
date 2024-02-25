<?php
namespace Meshistoires\Api\backend\db;
interface dbInt
{
  public static function deleteMany(
    string $col,
    array $param
  );
  public static function delete(
    string $col,
    string $uuid
  );
  public static function putMany(
    string $col,
    array $filter,
    array $param
  );
  public static function put(
    string $col,
    string $uuid,
    array $param
  );
  public static function post(
    string $col,
    array $param = []
  );
  public static function count(
    string $col,
    array $param = []
  );
  public static function getOne(
    string $col,
    array $param = [],
    int $skip = 0,
    ?array $projection = null
  );
  public static function get(
    string $col,
    array $param = [],
    int $limit = 0,
    array $order = ['_id' => 1],
    int $skip = 0,
    ?array $projection = null
  );
}
