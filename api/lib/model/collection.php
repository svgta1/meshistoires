<?php
namespace Meshistoires\Api\model;
class collection extends absModel
{
  public string $uuid = "";
  public int $gristId = 0;
  public string $gristuuid = "";
  public string $name = "";
  public int $dateCreate = 0;
  public int $dateUpdate = 0;
  public string $desc = "";
  public ?string $distanteLink = null;
  public ?string $imageUuid = null;
  public string $sha = "";
}