<?php
namespace Meshistoires\Api\model;
class altImages extends absModel
{
  public string $uuid = "";
  public string $oeuvreUuid = "";
  public int $dateCreate = 0;
  public int $dateUpdate = 0;
  public bool $deleted = false;
  public string $name = "";
  public int $thmbWidth = 0;
  public int $thmbHeight = 0;
}