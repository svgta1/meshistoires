<?php
namespace Meshistoires\Api\controller\admv1r0;
use Meshistoires\Api\utils\trace;
use Meshistoires\Api\utils\response;
use Meshistoires\Api\utils\request;
use Meshistoires\Api\utils\auth as utilsAuth;
use Meshistoires\Api\utils\seo;
use Meshistoires\Api\utils\cache;
use Meshistoires\Api\model\menu as mMenus;
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
  public function createMenu()
  {
    $request = $this->request;
    if(!isset($request['name']))
      response::json(400, 'Menu name required');
    request::validate_string(
      str: $request['name'],
      name: 'Menu name'
    );
    if(!isset($request['parent']))
      response::json(400, 'Parent menu required');
    if(is_bool($request['parent'])){
      if($request['parent'] !== false)
        response::json(400, 'bad parent');
    }else{
      request::validate_uuid($request['parent']);
      $count = $this->dbRes['class']::count(
        col: 'menus', 
        param: [
          'deleted' => false,
          'uuid' => $request['parent']
        ]
      );
      if($count != 1)
       response::json(400, 'bad parent');
    }
    $newMenu = new mMenus();
    $newMenu->newDate();
    $newMenu->genUuid();
    $newMenu->name = $request['name'];
    $newMenu->parent = $request['parent'];
    $cptSub = $this->dbRes['class']::count(
      col: 'menus', 
      param: [
        'deleted' => false,
        'parent' => $request['parent']
      ]
    );
    $newMenu->position = $cptSub + 1;
    $uuid = $this->dbRes['class']::post(
      col: 'menus', 
      param: $newMenu->_toArray()
    );
    response::json(200, ['uuid' => $newMenu->uuid]);
  }
  public function deleteMenu()
  {
    $uuid = $this->request['uuid'];
    request::validate_uuid($uuid);
    $position = $this->changeBroPosition(
      menuUuid: $uuid,
      newPosition: 1000,
      withDeleted: true
    );
    $res = $this->dbRes['class']::put(col: 'menus', uuid: $uuid, param: [
      'deleted' => true,
      'dateUpdate' => $this->request['req_timestamp'],
      'position' => $position
    ]);
    cache::clean();
    response::json(204, 'ok');
  }
  public function updateMenuInfo()
  {
    $request = $this->request;
    request::validate_uuid($request['uuid']);
    $params = [];

    if(isset($request['name'])){
      request::validate_string(
        str: $request['name'],
        name: 'Menu name'
      );
      $params['name'] = $request['name'];
    }
    if(isset($request['visible'])){
      request::validate_bool($request['visible'], 'Visible');
      $params['visible'] = $request['visible'];
    }
    if(isset($request['parent'])){
      if(is_bool($request['parent'])){
        if($request['parent'] !== false)
          response::json(400, 'bad parent');
      }else{
        request::validate_uuid($request['parent']);
      }
      $params['parent'] = $request['parent'];
      $request['position'] = 1000;
    }
    if(isset($request['position'])){
      request::validate_int($request['position'], 'Postion');
      $params['position'] = $request['position'];
    }
      
    if($params == [])
      response::json(400, 'Rien à mettre à jour');

    $params['dateUpdate'] = $request['req_timestamp'];
    if(isset($params['position']))
      $params['position'] = $this->changeBroPosition(
        menuUuid: $request['uuid'],
        newPosition: $params['position']
      );

    $res = $this->dbRes['class']::put(
      col: "menus", 
      uuid: $request['uuid'], 
      param: $params
    );
    if(isset($request['parent'])){
      $broLength = $this->dbRes['class']::count(
        col: "menus",
        param: ['parent' => $request['parent'], 'deleted' => false]
      );
      $res = $this->dbRes['class']::put(
        col: "menus", 
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
  private function changeBroPosition(string $menuUuid, int $newPosition, bool $withDeleted = false): int
  {
    $menu = $this->dbRes['class']::getOne(
      col: "menus",
      param: ['uuid' => $menuUuid, 'deleted' => false],
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
      col: "menus",
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
      col: "menus",
      param: $p_deleted,
      projection: ['uuid', 'position'],
      order: ['position' => 1]
    );
    foreach($cursor as $doc){
      if($doc->uuid == $menuUuid)
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
        col: "menus",
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
  public function getMenu()
  {
    request::validate_uuid($this->request['uuid']);
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
      'parent' => $doc->parent,
      'position' => $doc->position,
      'visible' => $doc->visible,
      'parentChildLength' => $this->dbRes['class']::count(col: $col, param: [
            'parent' => $doc->parent,
            'deleted' => false,
          ]),
    ];

    $res['uri'] = seo::seofy($doc->name);
    response::json(200, $res);
  }
  public function listMenu()
  {
    $request = $this->request;
    request::validate_uuid($request['uuid']);
    $this->getListMenu($request['uuid']);
  }
  public function listTop()
  {
   $this->getListMenu();
  }
  private function getListMenu($parent = false)
  {
    $col = "menus";
    $res = [
      'metadata' => [
        'count' => 0,
      ],
      'list' => [],
    ];

    $cursor = $this->dbRes['class']::get(
      col: $col,
      param: ['parent' => $parent, 'deleted' => false],
      order: ['position' => 1],
      projection: ['uuid', 'name', 'position', 'parent']
    );
    if(is_null($cursor))
      response::json(404, 'Menus not found');
    foreach($cursor as $doc){
      $ar = [
        'uuid' => $doc->uuid,
        'name' => $doc->name,
        'position' => $doc->position,
        'parent' => $doc->parent,
      ];
      $res['list'][] = $ar;
    }
    $res['metadata']['count'] = \count($res['list']);

    response::json(200, $res);
  }
}
