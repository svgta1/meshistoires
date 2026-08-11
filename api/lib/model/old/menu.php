<?php
namespace Meshistoires\Api\model;
class menu extends absModel
{
  public string $uuid = "";
  public string $name = "";
  public string|bool $parent = false;
  public bool $visible = false;
  public int $position = 0;
  public int $dateCreate = 0;
  public int $dateUpdate = 0;
  public bool $deleted = false;
}
