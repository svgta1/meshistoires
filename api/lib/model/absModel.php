<?php
namespace Meshistoires\Api\model;
use Svgta\Lib\Utils;

abstract class absModel
{
  public function _toArray(): array
  {
    return \json_decode($this->_toJson(), true);
  }
  public function _toJson(): string
  {
    return json_encode($this);
  }
  public function newDate()
  {
    if(isset($this->dateCreate) && $this->dateCreate == 0)
      $this->dateCreate = time();
    if(isset($this->dateUpdate) && $this->dateUpdate == 0)
      $this->dateUpdate = time();
    if(isset($this->createTs) && $this->createTs == 0)
      $this->createTs = time();
  }
  public function genUuid()
  {
    $this->uuid = Utils::genUUID();
  }
}
