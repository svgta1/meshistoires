<?php
namespace Meshistoires\Api\model;
class attachments extends absModel
{
  public string $uuid = "";
  public int $gristId = 0;
  public string $fileName = "";
  public int $fileSize = 0;
  public int $dateCreate = 0;
  public int $dateUpdate = 0;
  public ?string $intFileName = null;
}