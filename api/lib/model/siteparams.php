<?php
namespace Meshistoires\Api\model;
class siteparams extends absModel
{
  public string $uuid = "";
  public array $imagesUuid = [];
  public int $dateCreate = 0;
  public int $dateUpdate = 0;
  public string $name = "";
}