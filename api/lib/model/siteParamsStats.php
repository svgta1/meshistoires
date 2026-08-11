<?php
namespace Meshistoires\Api\model;
class siteParamsStats extends absModel
{
  public string $uuid = "";
  public int $dateCreate = 0;
  public int $dateUpdate = 0;
  public int $nbrAccess = 0;
  public bool $deleted = false;
  public string $from = ""; 
}