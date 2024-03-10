<?php
namespace Meshistoires\Api\controller\admv1r0;
use Meshistoires\Api\utils\trace;
use Meshistoires\Api\utils\response;
use Meshistoires\Api\utils\request;
use Meshistoires\Api\utils\auth as utilsAuth;
use Meshistoires\Api\utils\mail;
use Meshistoires\Api\utils\seo;
use Meshistoires\Api\backend\db;
use Svgta\Lib\Utils;

class comment
{
  static $listUser = [];
  static $listArt = [];
  static $listMenu = [];

  public function __construct(?array $scopes, array $request)
  {
    $this->scopes = $scopes;
    $this->request = $request;
    utilsAuth::verifyScope($scopes);
    $this->dbRes = db::get_res();
    $this->mail = new mail();
  }
  public function delete()
  {
    $request = $this->request;
    request::input_to_string($request['uuid']);
    $doc = $this->dbRes['class']::getOne(
      col: 'comment',
      param: ['uuid' => $request['uuid']],
    );
    if($doc->deleted){
      $this->delCom($doc->uuid);
      response::json(204, '');
    }
    $this->dbRes['class']::put(
      col: 'comment',
      uuid: $request['uuid'],
      param: ['deleted' => true, "dateUpdate" => time()]
    );
    if(is_null($doc->userUuid))
      response::json(204, '');
    $user = $this->getUser($doc->userUuid);
    if(is_null($user))
      response::json(204, '');
    $art = $this->getArticle($doc->artUUID);
    if(is_null($art))
      response::json(204, '');
    $menu = $this->getMenu($art->parent);
    if(is_null($menu))
      response::json(204, '');
    $this->mailUserDelete($user, $art, $menu);
    response::json(204, '');
  }
  public function switchValid()
  {
    $request = $this->request;
    request::input_to_string($request['uuid']);
    $doc = $this->dbRes['class']::getOne(
      col: 'comment',
      param: ['uuid' => $request['uuid']],
    );
    $ar = [
      'dateUpdate' => time(),
      'valide' => $doc->valide ? false : true,
    ];
    $this->dbRes['class']::put(
      col: 'comment',
      uuid: $request['uuid'],
      param: $ar,
    );

    if(is_null($doc->userUuid))
      response::json(204, '');
    $user = $this->getUser($doc->userUuid);
    if(is_null($user))
      response::json(204, '');
    $art = $this->getArticle($doc->artUUID);
    if(is_null($art))
      response::json(204, '');
    $menu = $this->getMenu($art->parent);
    if(is_null($menu))
      response::json(204, '');

    if($ar['valide']){
      $this->mailUserValid($user, $art, $menu);
      $this->mailNewComment($art, $menu);
    }else{
      $this->mailUserDevalid($user, $art, $menu);
    }
    response::json(204, '');
  }
  private function mailUserDelete($user, $artDoc, $menuDoc)
  {
    $this->_mailUserComment($user, $artDoc, $menuDoc, '/comment_del.tpl', 'supprimé');
  }
  private function mailUserDevalid($user, $artDoc, $menuDoc)
  {
    $this->_mailUserComment($user, $artDoc, $menuDoc, '/comment_devalide.tpl', 'retiré');
  }
  private function mailUserValid($user, $artDoc, $menuDoc)
  {
    $this->_mailUserComment($user, $artDoc, $menuDoc, '/comment_valide.tpl', 'validé');
  }
  private function _mailUserComment($user, $artDoc, $menuDoc, $tpl, $text)
  {
    $uri = $this->getSeoUri($menuDoc->uuid, $artDoc->uuid);
    $url = 'https://' . $_ENV['DOMAIN'] . '/' . $uri;
    $content = '<a href="'.$url.'" title="'.$artDoc->title.'">'.$menuDoc->name.' | '.$artDoc->title.'</a>';
    $tpl = \file_get_contents($_ENV['MAIL_TPL'] . $tpl);
    $tpl = \str_replace('##histoire##', $content, $tpl);
    $this->mail->send(
      subject: "Votre commentaire a été " . $text . " sur " . $_ENV['SITE_TITLE'],
      body: $tpl,
      toMail: $user->mail,
      toName: $user->givenname
    );
  }
  private function mailNewComment($artDoc, $menuDoc)
  {
    $cursor = $this->dbRes['class']::get(
      col: "contact",
      param: ['abo_news' => true, 'deleted' => false]
    );
    $uri = $this->getSeoUri($menuDoc->uuid, $artDoc->uuid);
    $url = 'https://' . $_ENV['DOMAIN'] . '/' . $uri;
    $content = '<a href="'.$url.'" title="'.$artDoc->title.'">'.$menuDoc->name.' | '.$artDoc->title.'</a>';
    $tpl = \file_get_contents($_ENV['MAIL_TPL'] . '/new_comment.tpl');
    $tpl = \str_replace('##histoire##', $content, $tpl);
    foreach($cursor as $doc){
      $this->mail->send(
        subject: "Un nouveau commentaire a été publié sur " . $_ENV['SITE_TITLE'],
        body: $tpl,
        toMail: $doc->mail,
        toName: $doc->givenname
      );
    }
  }
  private function getSeoUri($menuUUID, $artUUID): string
  {
    $menu = $this->getMenu($menuUUID);
    $art = $this->getArticle($artUUID);
    return seo::seofy($menu->name) . '/' . seo::seofy($art->title);
  }
  private function getMenu($uuid)
  {
    if(!isset(self::$listMenu[$uuid])){
      $doc = $this->dbRes['class']::getOne(
        col: 'menus',
        param: ['uuid' => $uuid],
      );
      self::$listMenu[$uuid] = $doc;
    }
    return self::$listMenu[$uuid];
  }
  private function getArticle($uuid)
  {
    if(!isset(self::$listArt[$uuid])){
      $doc = $this->dbRes['class']::getOne(
        col: 'articles',
        param: ['uuid' => $uuid],
      );
      self::$listArt[$uuid] = $doc;
    }
    return self::$listArt[$uuid];
  }
  private function getUser($uuid)
  {
    if(!isset(self::$listUser[$uuid])){
      $doc = $this->dbRes['class']::getOne(
        col: 'contact',
        param: ['uuid' => $uuid],
      );
      self::$listUser[$uuid] = $doc;
    }
    return self::$listUser[$uuid];
  }
  public function getList()
  {
    $res = [
      'nonValide' => [],
      'valide' => [],
      'sup' => [],
    ];
    $cursor = $this->dbRes['class']::get(
      col: 'comment',
      param: [],
      order: ['dateUpdate' => -1]
    );
    foreach($cursor as $doc){
      $ar = $this->modelList($doc);
      if(is_null($ar))
        continue;
      if($ar['deleted']){
        $res['sup'][] = $ar;
        continue;
      }
      if(!$ar['valide']){
        $res['nonValide'][] = $ar;
        continue;
      }
      $res['valide'][] = $ar;
    }

    response::json(200, $res);
  }
  private function modelList($doc): ?array
  {
    $ar = json_decode(json_encode($doc), true);
    $art = $this->getArticle($ar['artUUID']);;
    if(is_null($art)){
      $this->delCom($ar['uuid']);
      return null;
    }
    $menu = $this->getMenu($art['parent']);
    if(!is_null($ar['userUuid'])){
      $user = $this->getUser($art['parent']);
      if(!is_null($user)){
        $ar['sn'] = $user->sn;
        $ar['givenName'] = $user->givenname;
        $ar['mail'] = $user->mail;
      }
    }
    unset($ar['_id']);
    unset($ar['artUUID']);
    $ar['uri'] = $this->getSeoUri($menu->uuid, $art->uuid);
    return $ar;
  }
  private function delCom(string $uuid)
  {
    $this->dbRes['class']::delete(
      col: 'comment',
      uuid: $uuid
    );
  }
}
