<?php
namespace Meshistoires\Api\model;
class text extends absModel
{
  public string $uuid = "";
  public int $dateCreate = 0;
  public int $dateUpdate = 0;
  public string $text = "";
  public string $oeuvreUuid = "";
}