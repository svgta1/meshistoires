<?php
namespace Meshistoires\Api\model;
class mail extends absModel
{
  public string $uuid = "";
  public string $type = "contact"; //contact|response
  public string $userUuid = "";
  public int $createTs = 0;
  public string $msg = "";
  public bool $hasResponse = false;
  public string $responseTo = "";
}
