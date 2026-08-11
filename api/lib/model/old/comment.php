<?php
namespace Meshistoires\Api\model;
class comment extends absModel 
{
  public ?string $sn = null;
  public string $givenName = "";
  public string $mail = "";
  public string $msg = "";
  public string $uuid = "";
  public string $artUUID = "";
  public bool $valide = false;
  public int $dateCreate = 0;
  public int $dateUpdate = 0;
  public ?string $userUuid = null;
  public bool $deleted = false;
}
