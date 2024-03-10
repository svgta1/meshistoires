<?php
namespace Meshistoires\Api\model;
class news extends absModel
{
  public string $uuid = "";
  public string $title = "";
  public string $msg = "";
  public string $userUuid = "";
  public int $dateCreate = 0;
  public int $dateUpdate = 0;
  public bool $published = false;
  public int $datePublished = 0;
}