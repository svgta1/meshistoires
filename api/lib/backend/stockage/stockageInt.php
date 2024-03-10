<?php
namespace Meshistoires\Api\backend\stockage;

interface stockageInt
{
  public static function getImageInfo(string $uuid): array;
  public static function getStream($stream);
  public static function getThmb300Info(string $uuid): array;
  public static function getThmbInfo(string $uuid): array;
  public static function post(string $file): string;
  public static function list(int $skip = 0);
  public static function count(): int;
  public static function delete(string $file);
}
