<?php
namespace Meshistoires\Api\controller\admv1r0;
use Meshistoires\Api\utils\trace;
use Meshistoires\Api\utils\response;
use Meshistoires\Api\utils\request;
use Meshistoires\Api\utils\auth as utilsAuth;
use Meshistoires\Api\utils\seo;
use Meshistoires\Api\backend\db;

class menu
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
  public function deleteMenu()
  {
    $uuid = $this->request['uuid'];
    $colM = "menus";
    $colA = "articles";

    $doc = $this->dbRes['class']::getOne(col: $colM, param: ['uuid' => $uuid]);
    if(count($doc->subMenu) > 0)
      response::json(403, 'Supprimez les sous-menus en premier');
    $res = $this->dbRes['class']::put(col: $col, uuid: $uuid, param: [
      'deleted' => true
    ]);

    response::json(204, 'ok');
  }
  public function updateMenuInfo()
  {
    $request = $this->request;
    request::validate_string(
      str: $request['name'],
      name: 'Menu name'
    );
    request::validate_bool($request['visible'], 'Visible');
    request::validate_int($request['position'], 'Postion');
    $col = "menus";
    $res = $this->dbRes['class']::put(col: $col, uuid: $request['uuid'], param: [
      'name' => $request['name'],
      'position' => (integer)$request['position'],
      'visible' => (boolean)$request['visible'],
    ]);
    if($res == 1)
      response::json(204, '');
    else
      response::json(400, 'Rien à mettre à jour');
  }
  public function getMenu()
  {
    $col = "menus";
    $res = [];

    $doc = $this->dbRes['class']::getOne(col: $col, param: [
      'uuid' => $this->request['uuid'],
      'deleted' => false,
    ]);
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
      'visible' => $doc->visible,
      'parentObj' => null,
    ];
    if($res['parent']){
      $docP = $this->dbRes['class']::getOne(col: $col, param: ['uuid' => $res['parent']]);
      $res['parentObj'] = [
        'uuid' => $docP->uuid,
        'name' => $docP->name,
        'update' => $docP->dateUpdate,
        'articles' => isset($docP->articles) ? $docP->articles : [],
        'subMenu' => isset($docP->subMenu) ? $docP->subMenu : [],
        'parent' => isset($docP->parent) ? $docP->parent : null,
        'visible' => $docP->visible,
        'position' => $docP->position,
      ];
    }

    $res['uri'] = seo::seofy($doc->name);
    response::json(200, $res);
  }
  public function listTop()
  {
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
      param: ['parent' => false, 'deleted' => false],
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
        'visible' => $doc->visible,
      ];

      $ar['uri'] = seo::seofy($doc->name);
      $res['list'][] = $ar;
    }
    $res['metadata']['count'] = \count($res['list']);
    $res['metadata']['hash'] = \hash('sha256', json_encode($res['list']));

    response::json(200, $res);
  }
}
