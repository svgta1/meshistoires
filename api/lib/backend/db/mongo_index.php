<?php
namespace Meshistoires\Api\backend\db;
use Meshistoires\Api\backend\db;

class mongo_index
{
  private static $res = null;
  public function createIndexes()
  {
    $this->_creaInd($this->cache());
    $this->_creaInd($this->article());
    $this->_creaInd($this->menu());
    $this->_creaInd($this->comment());
    $this->_creaInd($this->contact());
    $this->_creaInd($this->analytic());
    $this->_creaInd($this->keySec());
    $this->_creaInd($this->mail());

    $this->_creaInd($this->image());
    $this->_creaInd($this->thmb300());
    $this->_creaInd($this->thmb());
  }
  private function _creaInd(array $ar)
  {
    $this->dropInd($ar['col']);
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
			['key' => ['md5' => 1], 'unique' => true, 'name' => 'md5'],
    ];
    return [
      'col' => $col,
      'ind' => $ind,
    ];
  }
  private function mail(): array
  {
    $col = 'mail';
    $ind = [
      ['key' => ['uuid' => 1], 'unique' => true, 'name' => 'uuid'],
      ['key' => ['responseTo' => 1, 'type' => 1], 'unique' => false, 'name' => 'responseTo'],
      ['key' => ['createTs' => -1], 'unique' => false, 'name' => 'createTs'],
      ['key' => ['userUuid' => 1, 'type' => 1], 'unique' => false, 'name' => 'userUuid'],
    ];
    return [
      'col' => $col,
      'ind' => $ind,
    ];
  }
  private function keySec(): array
  {
    $col = 'keySec';
    $ind = [
      ['key' => ['uuid' => 1], 'unique' => true, 'name' => 'uuid'],
      ['key' => ['credentialId' => 1], 'unique' => true, 'name' => 'credentialId'],
      ['key' => ['userHandle' => 1], 'unique' => false, 'name' => 'userHandle'],
    ];
    return [
      'col' => $col,
      'ind' => $ind,
    ];
  }
  private function analytic(): array
  {
    $col = 'analytic';
    $ind = [
      ['key' => ['path' => 1], 'unique' => false, 'name' => 'path'],
      ['key' => ['createTs' => 1], 'unique' => false, 'name' => 'date'],
      ['key' => ['ip' => 1], 'unique' => false, 'name' => 'ip'],
      ['key' => ['userUuid' => 1], 'unique' => false, 'name' => 'userUuid'],
    ];
    return [
      'col' => $col,
      'ind' => $ind,
    ];
  }
  private function contact(): array
  {
    $col = 'contact';
    $ind = [
      ['key' => ['mail' => 1], 'unique' => true, 'name' => 'mail'],
      ['key' => ['dateCreate' => -1], 'unique' => false, 'name' => 'date'],
    ];
    return [
      'col' => $col,
      'ind' => $ind,
    ];
  }
  private function comment(): array
  {
    $col = 'comment';
    $ind = [
      ['key' => ['artUUID' => 1], 'unique' => false, 'name' => 'artUUID'],
      ['key' => ['userUuid' => 1], 'unique' => false, 'name' => 'userUuid'],
      ['key' => ['dateCreate' => -1], 'unique' => false, 'name' => 'date'],
    ];
    return [
      'col' => $col,
      'ind' => $ind,
    ];
  }
  private function menu(): array
  {
    $col = 'menus';
    $ind = [
      ['key' => ['uuid' => 1], 'unique' => false, 'name' => 'uuid'],
			['key' => ['parent' => 1, ], 'unique' => false, 'name' => 'parent'],
			['key' => ['subMenu' => 1, ], 'unique' => false, 'name' => 'subMenu'],
    ];
    return [
      'col' => $col,
      'ind' => $ind,
    ];
  }
  private function article(): array
  {
    $col = 'articles';
    $ind = [
      ['key' => ['uuid' => 1], 'unique' => true, 'name' => 'uuid'],
			['key' => ['parent' => 1, ], 'unique' => false, 'name' => 'parent'],
			['key' => ['parent' => 1, 'visible' => 1, 'resume' => 1], 'unique' => false, 'name' => 'chapter'],
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
