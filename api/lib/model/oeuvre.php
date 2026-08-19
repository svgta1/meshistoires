<?php
namespace Meshistoires\Api\model;
class oeuvre extends absModel
{
  public string $uuid = "";
  public int $gristId = 0;
  public string $gristuuid = "";
  public string $title = "";
  public string $desc = "";
  public ?string $collectionUuid = null;
  public int $dateCreate = 0;
  public int $dateUpdate = 0;
  public ?string $distanteLink = null;
  public ?string $imageUuid = null;
  public ?array $categorieUuid = null;
  public ?string $publicUuid = null;
  public string $sha = "";
  public bool $visible = true;
  public string $keywords = "";
}