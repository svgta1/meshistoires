<?php
namespace Meshistoires\Api\controller\admv1r0;
use Meshistoires\Api\utils\trace;
use Meshistoires\Api\utils\response;
use Meshistoires\Api\utils\request;
use Meshistoires\Api\utils\auth as utilsAuth;
use Meshistoires\Api\utils\seo;
use Meshistoires\Api\utils\cache;
use Meshistoires\Api\model\article as mArticle;
use Meshistoires\Api\backend\db;

class article
{
  private $dbRes = null;
  private $scopes = null;
  private $request = [];

  public function __construct(?array $scopes, array $request)
  {
    $this->scopes = $scopes;
    $this->request = $request;
    utilsAuth::verifyScope($scopes);
    $this->dbRes = db::get_res();
  }
  public function deleteArticle()
  {
    $uuid = $this->request['uuid'];
    request::validate_uuid($uuid);
    $position = $this->changeBroPosition(
      articleUuid: $uuid,
      newPosition: 1000,
      withDeleted: true
    );
    $res = $this->dbRes['class']::put(col: 'articles', uuid: $uuid, param: [
      'deleted' => true,
      'dateUpdate' => $this->request['req_timestamp'],
      'position' => $position
    ]);
    cache::clean();
    response::json(204, 'ok');
  }
  public function updateArticle()
  {
    $request = $this->request;
    request::validate_uuid($request['uuid']);
    $params = [];
    if(isset($request['parent'])){
      request::validate_uuid($request['parent']);
      $params['parent'] = $request['parent'];
      $request['position'] = 1000;
    }    
    if(isset($request['content'])){
      request::validate_tinymce($request['content']);
      $params['content'] = $request['content'];
    }
    if(isset($request['title'])){
      request::validate_string($request['title'], 3, 'title');
      $params['title'] = $request['title'];
    }
    if(isset($request['position'])){
      request::validate_int($request['position'], 'position');
      $params['position'] = $request['position'];
    } 
    if(isset($request['visible'])){
      request::validate_bool($request['visible']);
      $params['visible'] = $request['visible'];
    }   
    if(isset($request['resume'])){
      request::validate_bool($request['resume']);
      $params['resume'] = $request['resume'];
    }     
    if(isset($request['comment'])){
      request::validate_bool($request['comment']);
      $params['comment'] = $request['comment'];
    }
    if($params == [])
      response::json(400, 'Rien à mettre à jour');
     $params['dateUpdate'] = $request['req_timestamp'];

    if(isset($params['position']))
      $params['position'] = $this->changeBroPosition(
        articleUuid: $request['uuid'],
        newPosition: $params['position']
      );
    
    $res = $this->dbRes['class']::put(
      col: "articles", 
      uuid: $request['uuid'], 
      param: $params
    );
    if(isset($request['parent'])){
      $broLength = $this->dbRes['class']::count(
        col: "articles",
        param: ['parent' => $request['parent'], 'deleted' => false]
      );
      $res = $this->dbRes['class']::put(
        col: "articles", 
        uuid: $request['uuid'], 
        param: ['position' => $broLength]
      );
    }
    if($res == 1){
      cache::clean();
      response::json(204, '');
    }else{
      response::json(400, 'Rien à mettre à jour');
    }
  }
  private function changeBroPosition(string $articleUuid, int $newPosition, bool $withDeleted = false): int
  {
    $menu = $this->dbRes['class']::getOne(
      col: "articles",
      param: ['uuid' => $articleUuid, 'deleted' => false],
      projection: ['parent', 'position']
    );
    if(is_null($menu))
      response::json(400, 'Menu non existant');
    $p_deleted = [
      'parent' => $menu->parent,
      'deleted' => false
    ];
    if($withDeleted)
      unset($p_deleted['deleted']);
    $broLength = $this->dbRes['class']::count(
      col: "articles",
      param: $p_deleted
    );
    $newPosition = $this->verifyPosition($newPosition, $broLength);
    if($newPosition == $menu->position)
      return $newPosition;

    if($newPosition > $menu->position)
      $sens = 1;
    else
      $sens = -1;

    $cursor = $this->dbRes['class']::get(
      col: "articles",
      param: $p_deleted,
      projection: ['uuid', 'position'],
      order: ['position' => 1]
    );
    foreach($cursor as $doc){
      if($doc->uuid == $articleUuid)
        continue;
      if($sens == 1){
        if($doc->position < $menu->position)
          continue;
        if($doc->position > $newPosition)
          continue;
        $doc->position -= 1;
      }
      if($sens == -1){
        if($doc->position > $menu->position)
          continue;
        if($doc->position < $newPosition)
          continue;
        $doc->position += 1;
      }
      $doc->position = $this->verifyPosition($doc->position, $broLength);
      $this->dbRes['class']::put(
        col: "articles",
        uuid: $doc->uuid,
        param: [
          'position' => $doc->position
        ]
      );
    }
    return $newPosition;
  }
  private function verifyPosition(int $position, int $max): int
  {
    if($position < 1)
      $position = 1;
    if($position > $max)
      $position = $max;
    return $position;
  }
  public function newArticle()
  {
    $request = $this->request;
    request::validate_uuid($request['parent']);
    $article = new mArticle();
    $article->newDate();
    $article->genUuid();
    $article->parent = $request['parent'];
    $cpt = $this->dbRes['class']::count(
      col: 'articles',
      param: ['parent' => $request['parent'], 'deleted' => false]
    );
    $article->position = $cpt + 1;
    $this->dbRes['class']::post(
      col: 'articles',
      param: $article->_toArray()
    );
    response::json(200, ['uuid' => $article->uuid]);
  }
  public function getFromParent()
  {
    $request = $this->request;
    request::validate_uuid($request['uuid']);

    $cursor = $this->dbRes['class']::get(
      col: 'articles',
      param: ['parent' => $request['uuid'], 'deleted' => false],
      order: ['position' => 1],
      projection: ['uuid', 'title', 'position', 'visible']
    );
    $res = [];
    foreach($cursor as $doc){
      $ar = [
        'title' => $doc->title,
        'uuid' => $doc->uuid,
        'position' => $doc->position,
        'parent' => $request['uuid'],
        'visible' => $doc->visible,
      ];
      $res[] = $ar;
    }
    response::json(200, $res);
  }
  public function getArticle()
  {
    request::validate_uuid($this->request['uuid']);
    $col = "articles";
    $col_menu = "menus";
    $res = [];

    $doc = $this->dbRes['class']::getOne(
      col: $col, 
      param: ['uuid' => $this->request['uuid']]
    );
    if(is_null($doc))
      response::json(200, 'Article not found');
    $change = $this->changeImg($doc->content);
    $res = [
      'title' => $doc->title,
      'update' => $doc->dateUpdate,
      'resume' => $doc->resume,
      'uuid' => $doc->uuid,
      'content' => $change['content'],
      'firstImage' => $change['firstImg'],
      'comment' => $doc->comment,
      'visible' => $doc->visible,
      'position' => $doc->position,
      'parent' => $doc->parent
    ];
    response::json(200, $res);
  }
  private function changeImg(string $content)
  {
    $firstImage = null;
    preg_match_all('/(src=\"([a-zA-Z0-9\.\?\/\=]*)\")/', $content, $matches,  PREG_SET_ORDER);
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
