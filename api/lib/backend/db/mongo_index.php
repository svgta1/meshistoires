<?php
namespace Meshistoires\Api\backend\db;
use Meshistoires\Api\backend\db;

class mongo_index
{
  private static $res = null;
  public function createIndexes()
  {
    $this->_creaInd($this->siteParamsStats());
    $this->_creaInd($this->siteparams());
    $this->_creaInd($this->altImages());
    $this->_creaInd($this->oeuvres());
    $this->_creaInd($this->collections());
    $this->_creaInd($this->public());
    $this->_creaInd($this->categories());

    $this->_creaInd($this->cache());

    $this->_creaInd($this->image());
    $this->_creaInd($this->thmb300());
    $this->_creaInd($this->thmb());
  }
  private function categories(): array
  {
    $ar = $this->oeuvres();
    $ar['col'] = 'categories';
    return $ar;
  }
  private function collections(): array
  {
    $ar = $this->oeuvres();
    $ar['col'] = 'collections';
    return $ar;
  }
  private function public(): array
  {
    $ar = $this->oeuvres();
    $ar['col'] = 'public';
    return $ar;
  }
  private function siteParamsStats(): array
  {
    $col = 'siteParamsStats';
    $ind = [
      ['key' => ['uuid' => 1], 'unique' => true, 'name' => 'uuid'],
      ['key' => ['deleted' => -1], 'unique' => false, 'name' => 'deleted'],
      ['key' => ['from' => -1], 'unique' => false, 'name' => 'from'],
    ];
    return [
      'col' => $col,
      'ind' => $ind,
    ];
  }
  private function siteparams(): array
  {
    $col = 'siteparams';
    $ind = [
      ['key' => ['uuid' => 1], 'unique' => true, 'name' => 'uuid'],
      ['key' => ['name' => -1], 'unique' => false, 'name' => 'name'],
    ];
    return [
      'col' => $col,
      'ind' => $ind,
    ];
  }
  private function altImages(): array
  {
    $col = 'altImages';
    $ind = [
      ['key' => ['uuid' => 1], 'unique' => true, 'name' => 'uuid'],
      ['key' => ['oeuvreUuid' => 1], 'unique' => false, 'name' => 'oeuvreUuid'],
      ['key' => ['name' => -1], 'unique' => false, 'name' => 'name'],
      ['key' => ['name' => -1, 'oeuvreUuid' => 1], 'unique' => false, 'name' => 'nameOeuvre'],
      ['key' => ['deleted' => -1], 'unique' => false, 'name' => 'deleted'],
    ];
    return [
      'col' => $col,
      'ind' => $ind,
    ];
  }
  private function oeuvres(): array
  {
    $col = 'oeuvres';
    $ind = [
      ['key' => ['uuid' => 1], 'unique' => true, 'name' => 'uuid'],
      ['key' => ['gristuuid' => 1], 'unique' => true, 'name' => 'gristUuid'],
      ['key' => ['dateUpdate' => -1], 'unique' => false, 'name' => 'dateUpdate'],
      ['key' => ['imageUuid' => 1], 'unique' => false, 'name' => 'imageUuid'],
    ];
    return [
      'col' => $col,
      'ind' => $ind,
    ];
  }
  private function _creaInd(array $ar)
  {
    print_r('Create index ' . $ar['col'] . PHP_EOL);
    try{
      $this->dropInd($ar['col']);
    }catch(\Throwable $t){
    }
    self::get_res()->{$ar['col']}->createIndexes($ar['ind']);
  }
  private function dropInd(string $col)
  {
    self::get_res()->{$col}->dropIndexes();
  }
  private function thmb(): array
  {
    $ar = $this->image();
    $ar['col'] = 'thumb.files';
    return $ar;
  }
  private function thmb300(): array
  {
    $ar = $this->image();
    $ar['col'] = 'thumb300.files';
    return $ar;
  }
  private function image(): array
  {
    $col = 'images.files';
    $ind = [
      ['key' => ['filename' => 1], 'unique' => false, 'name' => 'filename'],
			['key' => ['md5' => 1], 'unique' => false, 'name' => 'md5'],
    ];
    return [
      'col' => $col,
      'ind' => $ind,
    ];
  }
  private function cache(): array
  {
    $col = 'cache';
    $ind = [
      ['key' => ['uuid' => -1], 'unique' => true, 'name' => 'uuid'],
      ['key' => ['type' => 1], 'unique' => false, 'name' => 'type'],
      ['key' => ['exp' => 1], 'unique' => false, 'name' => 'exp'],
    ];
    return [
      'col' => $col,
      'ind' => $ind,
    ];
  }
  private static function get_res(){
    if(is_null(self::$res))
      self::$res = db::get_res();
    return self::$res['res'];
  }
}
