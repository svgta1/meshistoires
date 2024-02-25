<?php
namespace Meshistoires\Api\backend\stockage;
use Meshistoires\Api\utils\trace;
use Meshistoires\Api\backend\stockage;

class mongo implements stockageInt
{
  private static $res = null;

  const IMG="images";
  const THUMB="thumb";
  const THUMB300="thumb300";
  const PDF = "pdf";

  public static function getImageInfo(string $uuid): array
  {
    $bucket = self::get_res()->selectGridFSBucket(['bucketName' => self::IMG]);
    $doc = $bucket->findOne(['filename' => $uuid]);

    return [
      'metadata' => $doc,
      'stream' => $bucket->openDownloadStreamByName($uuid, ['revision' => 0])
    ];
  }

  public static function getStream($stream)
  {
    return \stream_get_contents($stream);
  }

  public static function getThmb300Info(string $uuid): array
  {
    $bucket = self::get_res()->selectGridFSBucket(['bucketName' => self::THUMB300]);
    $doc = $bucket->findOne(['filename' => $uuid]);

    return [
      'metadata' => $doc,
      'stream' => $bucket->openDownloadStreamByName($uuid, ['revision' => 0])
    ];
  }

  public static function getThmbInfo(string $uuid): array
  {
    $bucket = self::get_res()->selectGridFSBucket(['bucketName' => self::THUMB]);
    $doc = $bucket->findOne(['filename' => $uuid]);

    return [
      'metadata' => $doc,
      'stream' => $bucket->openDownloadStreamByName($uuid, ['revision' => 0])
    ];
  }

  private static function get_res(){
    if(is_null(self::$res))
      self::$res = stockage::get_res();
    return self::$res['res'];
  }
}
