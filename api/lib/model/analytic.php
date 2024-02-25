<?php
namespace Meshistoires\Api\model;
class analytic extends absModel
{
  public string $browserUuid = "";
  public string $browserLang = "";
  public string $browserTz = "";
  public string $browserUa = "";
  public bool $isBot = false;
  public string $ip = "";
  public string $ua = "";
  public string $path = "";
  public int $createTs = 0;
  public ?string $userUuid = null;
}
