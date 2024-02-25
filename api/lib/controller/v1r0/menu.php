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
    $cache_id = $this->className.'_'.__FUNCTION__.'_' . $request['uuid'];
    $cache = cache::get($cache_id);

    $col = "menus";
    $res = [];

    $doc = $this->dbRes['class']::getOne(col: $col, param: ['uuid' => $request['uuid'], 'visible' => true, 'deleted' => false]);
    if(is_null($doc))
      response::json(404, 'Menu not found');
    $res = [
      'uuid' => $doc->uuid,
      'name' => $doc->name,
      'update' => $doc->dateUpdate,
      'articles' => isset($doc->articles) ? $doc->articles : [],
      'subMenu' => isset($doc->subMenu) ? $doc->subMenu : [],
      'parent' => isset($doc->parent) ? $doc->parent : null,
      'position' => $doc->position,
    ];

    foreach($res['articles'] as $k=>$v){
      $c = $this->dbRes['class']::count(col: 'articles', param: ['uuid' => $v, 'visible' => true, 'deleted' => false]);
      if($c === 0)
        unset($res['articles'][$k]);
    }

    foreach($res['subMenu'] as $k=>$v){
      $c = $this->dbRes['class']::count(col: $col, param: ['uuid' => $v, 'visible' => true, 'deleted' => false]);
      if($c === 0)
        unset($res['subMenu'][$k]);
    }

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

    $cursor = $this->dbRes['class']::get(col: $col, param: ['visible' => true, 'deleted' => false]);
    if(is_null($cursor))
      response::json(404, 'Menus not found');
    foreach($cursor as $doc){
      $ar = [
        'type' => 'menu',
        'uuid' => $doc->uuid,
        'name' => $doc->name,
        'update' => $doc->dateUpdate,
        'articles' => isset($doc->articles) ? $doc->articles : [],
        'subMenu' => isset($doc->subMenu) ? $doc->subMenu : [],
        'parent' => isset($doc->parent) ? $doc->parent : null,
        'position' => $doc->position,
      ];
      foreach($ar['articles'] as $k=>$v){
        $c = $this->dbRes['class']::count(col: 'articles', param: ['uuid' => $v, 'visible' => true, 'deleted' => false]);
        if($c === 0)
          unset($ar['articles'][$k]);
      }

      foreach($ar['subMenu'] as $k=>$v){
        $c = $this->dbRes['class']::count(col: $col, param: ['uuid' => $v, 'visible' => true, 'deleted' => false]);
        if($c === 0)
          unset($ar['subMenu'][$k]);
      }
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
    //$cache = cache::get($cache_id);

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
      order: ['position' => 1]
    );
    if(is_null($cursor))
      response::json(404, 'Menus not found');
    foreach($cursor as $doc){
      $ar = [
        'type' => 'menu',
        'uuid' => $doc->uuid,
        'name' => $doc->name,
        'update' => $doc->dateUpdate,
        'articles' => isset($doc->articles) ? $doc->articles : [],
        'subMenu' => isset($doc->subMenu) ? $doc->subMenu : [],
        'parent' => isset($doc->parent) ? $doc->parent : null,
        'position' => $doc->position,
      ];

      foreach($ar['articles'] as $k=>$v){
        $c = $this->dbRes['class']::count(col: 'articles', param: ['uuid' => $v, 'visible' => true, 'deleted' => false]);
        if($c === 0)
          unset($ar['articles'][$k]);
      }

      foreach($ar['subMenu'] as $k=>$v){
        $c = $this->dbRes['class']::count(col: $col, param: ['uuid' => $v, 'visible' => true, 'deleted' => false]);
        if($c === 0)
          unset($ar['subMenu'][$k]);
      }
      $ar['uri'] = seo::seofy($doc->name);
      $res['list'][$doc->uuid] = $ar;
    }
    $res['metadata']['count'] = \count($res['list']);
    $res['metadata']['hash'] = \hash('sha256', json_encode($res['list']));
    cache::set($cache_id, json_encode($res));
    response::json(200, $res);
  }
}
