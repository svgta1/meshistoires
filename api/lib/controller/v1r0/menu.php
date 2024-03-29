<?php
namespace Meshistoires\Api\controller\v1r0;
use Meshistoires\Api\utils\trace;
use Meshistoires\Api\utils\response;
use Meshistoires\Api\utils\request;
use Meshistoires\Api\utils\seo;
use Meshistoires\Api\utils\cache;
use Meshistoires\Api\backend\db;

class menu
{
  private $dbRes = null;
  private $className = null;
  private $scopes = null;
  private $request = [];

  public function __construct(?array $scopes, array $request)
  {
    $this->scopes = $scopes;
    $this->request = $request;
    if(isset($this->request['uuid']))
      request::validate_uuid($this->request['uuid']);
    $c = \explode('\\', __CLASS__);
    $this->className = \array_pop($c);
    $this->dbRes = db::get_res();

  }
  public function get()
  {
    $request = $this->request;
    request::validate_uuid($uuid);
    $cache_id = $this->className.'_'.__FUNCTION__.'_' . $request['uuid'];
    $cache = cache::get($cache_id);

    $col = "menus";
    $res = [];

    $doc = $this->dbRes['class']::getOne(
      col: $col, 
      param: ['uuid' => $request['uuid'], 'visible' => true, 'deleted' => false],
      projection: ['uuid', 'name', 'dateUpdate', 'parent', 'position']
    );
    if(is_null($doc))
      response::json(404, 'Menu not found');
    $res = [
      'uuid' => $doc->uuid,
      'name' => $doc->name,
      'update' => $doc->dateUpdate,
      'parent' => $doc->parent,
      'position' => $doc->position,
    ];
    $res['articles'] = $this->getArticles($doc->uuid);
    $res['subMenu'] = $this->getSubM($doc->uuid);
    $res['uri'] = seo::seofy($doc->name);
    cache::set($cache_id, json_encode($res));
    response::json(200, $res);
  }

  public function list()
  {
    $cache_id = $this->className.'_'.__FUNCTION__;
    $cache = cache::get($cache_id);

    $col = "menus";
    $res = [
      'metadata' => [
        'count' => 0,
        'hash' => null,
      ],
      'list' => [],
    ];

    $cursor = $this->dbRes['class']::get(
      col: $col, 
      param: ['visible' => true, 'deleted' => false],
      projection: ['uuid', 'name', 'dateUpdate', 'parent', 'position']
    );
    if(is_null($cursor))
      response::json(404, 'Menus not found');
    foreach($cursor as $doc){
      $ar = [
        'type' => 'menu',
        'uuid' => $doc->uuid,
        'name' => $doc->name,
        'update' => $doc->dateUpdate,
        'parent' => $doc->parent,
        'position' => $doc->position,
      ];
      $ar['articles'] = $this->getArticles($doc->uuid);
      if($doc->name !== "Accueil")
        $ar['subMenu'] = $this->getSubM($doc->uuid);
      else
        $ar['subMenu'] = $this->menuLastList();
      $ar['uri'] = seo::seofy($doc->name);
      $res['list'][$doc->uuid] = $ar;
    }
    $res['metadata']['count'] = \count($res['list']);
    $res['metadata']['hash'] = \hash('sha256', json_encode($res['list']));

    cache::set($cache_id, json_encode($res));
    response::json(200, $res);
  }

  public function listTop()
  {
    $cache_id = $this->className.'_'.__FUNCTION__;
    $cache = cache::get($cache_id);

    $col = "menus";
    $res = [
      'metadata' => [
        'count' => 0,
        'hash' => null,
      ],
      'list' => [],
    ];

    $cursor = $this->dbRes['class']::get(
      col: $col,
      param: ['visible' => true, 'parent' => false, 'deleted' => false],
      order: ['position' => 1],
      projection: ['uuid', 'name', 'dateUpdate', 'parent', 'position']
    );
    if(is_null($cursor))
      response::json(404, 'Menus not found');
    foreach($cursor as $doc){
      $ar = [
        'type' => 'menu',
        'uuid' => $doc->uuid,
        'name' => $doc->name,
        'update' => $doc->dateUpdate,
        'parent' => $doc->parent,
        'position' => $doc->position,
      ];
      $ar['articles'] = $this->getArticles($doc->uuid);
      if($doc->name != "Accueil")
        $ar['subMenu'] = $this->getSubM($doc->uuid);
      else
        $ar['subMenu'] = $this->menuLastList();
      $ar['uri'] = seo::seofy($doc->name);
      $res['list'][$doc->uuid] = $ar;
    }
    $res['metadata']['count'] = \count($res['list']);
    $res['metadata']['hash'] = \hash('sha256', json_encode($res['list']));
    cache::set($cache_id, json_encode($res));
    response::json(200, $res);
  }
  public function menusRandom()
  {
    response::json(200, $this->menuLastList());
  }
  private function menuLastList(): array
  {
    $list = [];
    $cursor = $this->dbRes['class']::get(
      col: 'menus',
      param: ['visible' => true, 'deleted' => false],
      projection: ['uuid'],
      order: ['position' => 1]
    );
    foreach($cursor as $obj){
      if(0 == $this->dbRes['class']::count(
        col: 'menus',
        param: ['parent' => $obj->uuid]
      ))
        $list[] = $obj->uuid;
    }
    \shuffle($list);
    $ret = [];
    for($i = 0; $i < 6; $i++)
      $ret[] = $list[$i];
    return $ret;
  }
  private function getArticles(string|bool $uuid): array
  {
    return $this->_s('articles', $uuid);
  }
  private function getSubM(string|bool $uuid): array
  {
    return $this->_s('menus', $uuid);
  }
  private function _s(string $col, string|bool $uuid): array
  {
    $list = [];
    $cursor = $this->dbRes['class']::get(
      col: $col,
      param: ['parent' => $uuid, 'visible' => true, 'deleted' => false],
      projection: ['uuid'],
      order: ['position' => 1]
    );
    foreach($cursor as $obj){
      $list[] = $obj->uuid;
    }
    return $list;
  }
}
