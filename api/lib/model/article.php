<?php
namespace Meshistoires\Api\model;
class article extends absModel
{
  public string $uuid = "";
  public string $title = "";
  public string $parent = "";
  public bool $visible = false;
  public int $position = 0;
  public bool $resume = false;
  public string $content = "";
  public bool $comment = false;
  public int $dateCreate = 0;
  public int $dateUpdate = 0;
  public bool $deleted = false;
}
