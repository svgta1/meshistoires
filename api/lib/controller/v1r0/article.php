<?php
namespace Meshistoires\Api\controller\v1r0;
use Meshistoires\Api\utils\trace;
use Meshistoires\Api\utils\response;
use Meshistoires\Api\utils\request;
use Meshistoires\Api\utils\seo;
use Meshistoires\Api\utils\cache;
use Meshistoires\Api\backend\db;

class article
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
  public function prev()
  {
    $request = $this->request;
    $cache_id = $this->className.'_'.__FUNCTION__.'_' . $request['uuid'];
    $cache = cache::get($cache_id);

    $article = $this->getArticle($request['uuid']);
    if(is_null($article))
      response::json(404, 'Article not found');
    $prev = $this->nextPrev($article['position'] - 1, $article['parent']);
    cache::set($cache_id, json_encode($prev));
    response::json(200, $prev);
  }
  public function next()
  {
    $request = $this->request;
    $cache_id = $this->className.'_'.__FUNCTION__.'_' . $request['uuid'];
    $cache = cache::get($cache_id);

    $article = $this->getArticle($request['uuid']);
    if(is_null($article))
      response::json(404, 'Article not found');
    $next = $this->nextPrev($article['position'] + 1, $article['parent']);
    cache::set($cache_id, json_encode($next));
    response::json(200, $next);
  }
  private function nextPrev(int $position, string $parent): array
  {
    $np = $this->dbRes['class']::getOne(
      col: 'articles',
      param: [
        'parent' => $parent, 
        'deleted' => false, 
        'visible' => true,
        'position' => $position
      ],
      projection: ['uuid']
    );
    if(is_null($np))
      response::json(404, 'Article not found');

    $np = $this->getArticle($np->uuid);
    if(is_null($np))
      response::json(404, 'Article not found');
    $res = [
      'title' => $np['title'],
      'update' => $np['update'],
      'uuid' => $np['uuid'],
      'uri' => $np['uri']
    ];
    return $res;
  }
  
  public function list()
  {
    $request = $this->request;
    $cache_id = $this->className.'_'.__FUNCTION__;
    $cache = cache::get($cache_id);

    $col = "articles";
    $col_menu = "menus";
    $cursor = $this->dbRes['class']::get(
      col: $col,
      param: ['visible' => true, 'deleted' => false],
      projection: ['title', 'uuid', 'parent']
    );
    $res = [
      'metadata' => [
        'count' => 0,
        'hash' => null,
      ],
      'list' => [],
    ];
    foreach($cursor as $doc)
    {
      if(!$doc->title)
        continue;
      $ar = [
        'title' => $doc->title,
        'uuid' => $doc->uuid,
      ];
      $rM = $this->dbRes['class']::getOne(
        col: $col_menu,
        param: ['uuid' => $doc->parent, 'visible' => true, 'deleted' => false],
        projection: ['name', 'uuid']
      );
      if(is_null($rM))
        continue;
      if(isset($rM->visible) && !$rM->visible)
        continue;

      $ar['menu_uuid'] = $rM->uuid;
      $ar['uri'] = seo::seofy($rM->name) . '/' . seo::seofy($doc->title);
      $ar['type'] = "article";

      $res['list'][] = $ar;
    }
    $res['metadata']['count'] = \count($res['list']);
    $res['metadata']['hash'] = \hash('sha256', json_encode($res['list']));
    cache::set($cache_id, json_encode($res));
    response::json(200, $res);
  }
  public function getFromParent()
  {
    $request = $this->request;
    $cache_id = $this->className.'_'.__FUNCTION__.'_' . $request['uuid'];
    $cache = cache::get($cache_id);

    $col = "articles";
    $col_menu = "menus";
    $res = [];

    $rM = $this->dbRes['class']::getOne(col: $col_menu, param: ['uuid' => $request['uuid'], 'visible' => true, 'deleted' => false]);
    if(is_null($rM))
      response::json(200, $res);
    $cursor = $this->dbRes['class']::get(
      col: $col,
      param: ['parent' => $request['uuid'], 'visible' => true, 'deleted' => false],
      limit: 15
    );

    foreach($cursor as $doc){
      if(!is_null($doc) && isset($doc->content)){
        $change = $this->changeImg($doc->content);
      }else{
        $change = [
          'content' => null,
          'firstImg' =>  null,
        ];
      }
      $ar = [
        'title' => $doc->title,
        'update' => $doc->dateUpdate,
        'resume' => isset($doc->resume) ? $doc->resume : false,
        'uuid' => $doc->uuid,
        'content' => $change['content'],
        'firstImage' => $change['firstImg'],
        'comment' => isset($doc->comment) ? $doc->comment : false,
      ];

      $ar['menu_name'] = $rM->name;
      $ar['menu_uuid'] = $rM->uuid;
      $ar['uri'] = seo::seofy($rM->name) . '/' . seo::seofy($doc->title);
      $res[] = $ar;
    }

    cache::set($cache_id, json_encode($res));
    response::json(200, $res);
  }
  public function get(){
    $article = $this->getArticle($this->request['uuid']);
    if(is_null($article))
      response::json(404, 'Article not found');
    response::json(200, $article);
  }
  private function getArticle($uuid)
  {
    $cache_id = $this->className.'_'.__FUNCTION__.'_' . $uuid;
    $cache = cache::_get($cache_id);
    if($cache)
      return \json_decode($cache, true);

    $res = [];
    $doc = $this->dbRes['class']::getOne(
      col: 'articles', 
      param: ['uuid' => $uuid, 'visible' => true, 'deleted' => false]
    );
    if(is_null($doc))
      return null;
    if(isset($doc->content)){
      $change = $this->changeImg($doc->content);
    }else{
      $change = [
        'content' => null,
        'firstImg' =>  null,
      ];
    }
    $res = [
      'title' => $doc->title,
      'update' => $doc->dateUpdate,
      'resume' => $doc->resume,
      'uuid' => $doc->uuid,
      'content' => $change['content'],
      'firstImage' => $change['firstImg'],
      'comment' => $doc->comment,
      'position' => $doc->position,
      'parent' => $doc->parent,
    ];
    $rM = $this->dbRes['class']::getOne(
      col: 'menus', 
      param: ['uuid' => $doc->parent, 'visible' => true, 'deleted' => false]
    );
    if(is_null($rM))
      return null;
    $res['menu_name'] = $rM->name;
    $res['menu_uuid'] = $rM->uuid;
    $res['uri'] = seo::seofy($rM->name) . '/' . seo::seofy($doc->title);
    cache::set($cache_id, json_encode($res));
    return $res;
  }
  private function changeImg(string $content)
  {
    $firstImage = null;
    preg_match_all('/(src=\"([a-zA-Z0-9\.\?\/\=\:]*)\")/', $content, $matches,  PREG_SET_ORDER);
    foreach($matches as $matche)
    {
      $matche = array_unique($matche);
      $toReplace = null;
      foreach($matche as $m){
        if(is_null($toReplace))
        {
          $toReplace = $m;
        }else{
          $fileName = pathinfo($m)['filename'] . '.' . pathinfo($m)['extension'];
          $fileName = str_replace('getImage.php?image=', '', $fileName);
          if(is_null($firstImage))
            $firstImage = $fileName;
        }
      }
      $uri = $_SERVER["REQUEST_SCHEME"]. "://".$_SERVER["HTTP_HOST"] . $_ENV['BASE_PATH'] . '/v1/image/' . $fileName;
      $content = str_replace($toReplace, 'src="'.$uri.'"', $content);
    }
    return [
      'content' => $content,
      'firstImg' =>  $firstImage,
    ];
  }
}
