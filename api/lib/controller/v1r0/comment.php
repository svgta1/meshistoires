<?php
namespace Meshistoires\Api\controller\v1r0;
use Meshistoires\Api\model\comment as mComment;
use Meshistoires\Api\utils\trace;
use Meshistoires\Api\utils\response;
use Meshistoires\Api\utils\cache;
use Meshistoires\Api\utils\request;
use Meshistoires\Api\utils\seo;
use Meshistoires\Api\utils\mail;
use Meshistoires\Api\backend\db;
use Meshistoires\Api\utils\auth as utilsAuth;
use Svgta\Lib\Utils;

class comment
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

  private function _getCursorListUser(string $param, string $value)
  {
    $cursor = $this->dbRes['class']::get(
      col: 'comment',
      param: [$param => $value, 'deleted' => false],
      limit: 100,
      order: ['dateCreate' => -1]
    );
    return $cursor;
  }

  private function _getListUser($cursor, array &$array)
  {
    $listArt = [];
    foreach($cursor as $doc){
      if(in_array($doc->uuid, $this->listUserCommentsUUID)){
        continue;
      }else{
        $this->listUserCommentsUUID[] = $doc->uuid;
      }
      if(isset($doc->deleted) && $doc->deleted)
        continue;
      if(!isset($doc->artUUID))
        continue;
      if(!isset($listArt[$doc->artUUID])){
        $art = $this->dbRes['class']::getOne(
          col: 'articles',
          param: ['uuid' => $doc->artUUID, 'visible' => true, 'deleted' => false],
          projection: ['title', 'parent']
        );
        if(!is_null($art)){
          $menu = $this->dbRes['class']::getOne(
            col: 'menus',
            param: ['uuid' => $art->parent, 'visible' => true, 'deleted' => false],
            projection: ['name']
          );
          if(!is_null($menu)){
            $listArt[$doc->artUUID] = [
              "artTitle" => $art->title,
              "menuName" => $menu->name,
              "uri" => seo::seofy($menu->name) . '/' . seo::seofy($art->title),
            ];
          }else{
            $listArt[$doc->artUUID] = null;
          }
        }else{
          $listArt[$doc->artUUID] = null;
        }
      }
      if(is_null($listArt[$doc->artUUID]))
        continue;
      $ar = [
        'valide' => $doc->valide,
        'dateCreate' => $doc->dateCreate,
        'msg' => $doc->msg,
        'art' => $listArt[$doc->artUUID],
      ];
      $array[] = $ar;
    }
  }

  public function listUser()
  {
    if(!isset($_SESSION['ui']) || is_null($_SESSION['ui']['uuid']) ){
        response::json(403, 'Bad user - attack detected.');
    }
    utilsAuth::verifyScope($this->scopes);
    $this->listUserCommentsUUID = [];
    $cursor = $this->_getCursorListUser('userUuid', $_SESSION['ui']['uuid']);
    $verifiedComment = [];
    $this->_getListUser($cursor, $verifiedComment);

    response::json(200, $verifiedComment);
  }

  public function post()
  {
    utilsAuth::verifyScope($this->scopes);
    $request = $this->request;
    if(!\boolval($_ENV['COMMENT_ACTIF']))
      response::json(403, 'No comment are enable');
    if($request['uuid'] != $request['articleUuid'])
      response::json(400, 'Bad request UUID');

    request::validate_comment($request['comment']);
    if(!isset($_SESSION['ui']) || is_null($_SESSION['ui']['uuid']) ){
        response::json(403, 'hacking detected');
    }

    $artDoc = $this->dbRes['class']::getOne(
      col: "articles",
      param: ['uuid' => $request['articleUuid'], 'visible' => true, 'comment' => true, 'deleted' => false],
      projection: ['title', 'parent']
    );
    if(is_null($artDoc))
      response::json(400, 'No article available');

    $menuDoc = $this->dbRes['class']::getOne(
      col: "menus",
      param: ['uuid' => $artDoc->parent, 'visible' => true, 'deleted' => false],
      projection: ['name']
    );
    if(is_null($menuDoc))
      response::json(400, 'No article available');

    $comment = new mComment();
    $comment->newDate();
    $comment->genUuid();
    $comment->sn = $_SESSION['ui']['familyName'];
    $comment->givenName = $_SESSION['ui']['givenName'];
    $comment->mail = $_SESSION['ui']['email'];
    $comment->msg = $request['comment'];
    $comment->valide = boolval($_ENV['COMMENT_AUTO_VALID']);
    $comment->userUuid = $_SESSION['ui']['uuid'];
    $comment->artUUID = $request['articleUuid'];

    $res = $this->dbRes['class']::post(col: "comment", param: $comment->_toArray());
    if(!$res){
      response::json(400, 'Error on create comment');
    }
    $this->mailNewCommentAdm($comment->givenName, $artDoc, $menuDoc, $comment->msg);
    if($comment->valide)
      $this->mailNewComment($artDoc, $menuDoc);
    response::json(204, '');
  }
  private function mailNewComment($artDoc, $menuDoc)
  {
    $cursor = $this->dbRes['class']::get(
      col: "contact",
      param: ['abo_news' => true, 'deleted' => false]
    );
    $uri = seo::seofy($menuDoc->name) . '/' . seo::seofy($artDoc->title);
    $url = 'https://' . $_ENV['DOMAIN'] . '/' . $uri;
    $content = '<a href="'.$url.'" title="'.$artDoc->title.'">'.$menuDoc->name.' | '.$artDoc->title.'</a>';
    $tpl = \file_get_contents($_ENV['MAIL_TPL'] . '/new_comment.tpl');
    $tpl = \str_replace('##histoire##', $content, $tpl);
          $mail = new mail();
    foreach($cursor as $doc){
      $mail->send(
        subject: "Un nouveau commentaire a été publié sur " . $_ENV['SITE_TITLE'],
        body: $tpl,
        toMail: $doc->mail,
        toName: $doc->givenname
      );
    }
  }
  private function mailNewCommentAdm(string $givenName, $art, $menu, $comment)
  {
    $admin_list = \yaml_parse_file($_ENV['ADMIN_YAML'])['adminList'];
    if(boolval($_ENV['COMMENT_AUTO_VALID']))
      $validCom = "Non";
    else
      $validCom = "Oui";
    $comment = \str_replace('\n','<br>', $comment);
    $comment = \str_replace(PHP_EOL,'<br>', $comment);
    $tpl = \file_get_contents($_ENV['MAIL_TPL'] . '/adm_new_comment.tpl');
    $tpl = \str_replace('##usergivenanme##', $givenName, $tpl);
    $tpl = \str_replace('##articlename##', $art->title, $tpl);
    $tpl = \str_replace('##menuname##', $menu->name, $tpl);
    $tpl = \str_replace('##validcomment##', $validCom, $tpl);
    $tpl = \str_replace('##msgcontent##', $comment, $tpl);
    foreach($admin_list as $adm => $v){
      $doc = $this->dbRes['class']::getOne(
        col: "contact",
        param: ['mail' => $adm, 'deleted' => false]
      );
      $mail = new mail();
      $mail->send(
        subject: "Administration - Un nouveau commentaire a été envoyé",
        body: $tpl,
        toMail: $doc->mail,
        toName: $doc->givenname
      );
    }
  }

  public function get()
  {
    $request = $this->request;
    if(!\boolval($_ENV['COMMENT_ACTIF']))
      response::json(403, 'No comment are enable');
    $cache_id = $this->className.'_'.__FUNCTION__.'_' . $request['uuid'];
    $cache = cache::get($cache_id);

    $col = "comment";
    $res = [];
    $doc = $this->dbRes['class']::getOne(
      col: $col,
      param: ['uuid' => $request['uuid'], 'valide' => true, 'deleted' => false]
    );
    if($doc){
      $res = [
        'uuid' => $doc->uuid,
        'artUuid' => $doc->artUUID,
        'comment' => $doc->msg,
        'date' => $doc->dateUpdate,
        'user' => (\strlen($doc->givenName) > 1) ? $doc->givenName : $doc->sn,
      ];
      if(isset($doc->deleted) && $doc->deleted)
        response::json(404, 'No comment found');
      cache::set($cache_id, json_encode($res));
      response::json(200, $res);
    }
    response::json(404, 'No comment found');
  }
  public function getArticleList()
  {
    $request = $this->request;
    if(!\boolval($_ENV['COMMENT_ACTIF']))
      response::json(403, 'No comment are enable');

    $col = "comment";
    $colArt = "articles";
    $res = [];
    $countArt = $this->dbRes['class']::count(
      col: $colArt,
      param: ['uuid' => $request['uuid'], 'visible' => true, 'comment' =>  true, 'deleted' => false]
    );
    if($countArt == 0)
      response::json(200, $res);

    $cursor = $this->dbRes['class']::get(
      col: $col,
      param: ['artUUID' => $request['uuid'], 'valide' => true, 'deleted' => false],
      order: ['dateUpdate' => -1]
    );

    $userList = [];
    foreach($cursor as $doc){
      if(isset($doc->deleted) && $doc->deleted)
        continue;
      $name = (\strlen($doc->givenName) > 1) ? $doc->givenName : $doc->sn;
      if(isset($doc->userUuid)){
        if(!isset($userList[$doc->userUuid])){
          $user = $this->dbRes['class']::getOne(
            col: 'contact',
            param: [ 'uuid' => $doc->userUuid, 'deleted' => false ]
          );
          $userList[$doc->userUuid] = $user->givenname;
        }
        if(!is_null($userList[$doc->userUuid]))
          $name = $userList[$doc->userUuid];
      }
      $res[] = [
        'uuid' => $doc->uuid,
        'artUuid' => $doc->artUUID,
        'comment' => $doc->msg,
        'date' => $doc->dateUpdate,
        'user' => $name,
      ];
    }
    response::json(200, $res);
  }
}
