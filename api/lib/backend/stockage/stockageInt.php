<?php
namespace Meshistoires\Api\backend\stockage;

interface stockageInt
{
  public static function getImageInfo(string $uuid): array;
  public static function getStream($stream);
  public static function getThmb300Info(string $uuid): array;
  public static function getThmbInfo(string $uuid): array;
}
