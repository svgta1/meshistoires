<?php
namespace Meshistoires\Api\controller\admv1r0;
use Meshistoires\Api\utils\trace;
use Meshistoires\Api\utils\response;
use Meshistoires\Api\utils\request;
use Meshistoires\Api\utils\auth as utilsAuth;
use Meshistoires\Api\utils\mail as utilsMail;
use Meshistoires\Api\utils\seo;
use Meshistoires\Api\model\news as mNews;
use Meshistoires\Api\backend\db;

class news
{
  public function __construct(?array $scopes, array $request)
  {
    $this->scopes = $scopes;
    $this->request = $request;
    utilsAuth::verifyScope($scopes);
    $this->dbRes = db::get_res();
    $this->mail = new utilsMail();
  }
  public function get()
  {
    $request = $this->request;
    request::validate_uuid($request['uuid']);
    $doc = $cursor = $this->dbRes['class']::getOne(
      col: 'news',
      param: ['uuid' => $request['uuid']]
    );
    $news = new mNews();
    $news = $news->_toArray();
    foreach($news as $k => $v)
      $news[$k] = $doc->{$k};
    response::json(200, $news);
  }
  public function publish()
  {
    $request = $this->request;
    request::validate_uuid($request['uuid']);
    $news = $cursor = $this->dbRes['class']::getOne(
      col: 'news',
      param: ['uuid' => $request['uuid'], 'published' => false]
    );
    if(is_null($news))
      response::json(400, 'News non existante ou déjà publiée');

    $cursor = $this->dbRes['class']::get(
      col: 'contact',
      param: ['abo_news' => true, 'deleted' => false, 'ban' => false]
    );
    $subject = 'News Letter : ' . $news->title;
    $body = $news->msg;
    foreach($cursor as $user){
      $this->mail->send(
        subject: $subject,
        body: $body,
        toMail: $user->mail,
        toName: $user->givenname
      );
    }

    $update = [
      'datePublished' => time(),
      'published' => true,
    ];
    $this->dbRes['class']::put(
      col: 'news',
      uuid: $request['uuid'],
      param: $update
    );
    response::json(204, '');
  }
  public function put()
  {
    $request = $this->request;
    $update = [];
    if(isset($request['title'])){
      request::validate_string($request['title']);
      $update['title'] = $request['title'];
    }
      
    if(isset($request['msg'])){
      request::validate_tinymce($request['msg']);
      $update['msg'] = $request['msg'];
    }
    if($update == [])
      response::json(400, 'Nothing to update');

    $update['dateUpdate'] = \time();  
    request::validate_uuid($request['uuid']);
    $this->dbRes['class']::put(
      col: 'news',
      uuid: $request['uuid'],
      param: $update
    );
    response::json(204, '');
  }
  public function post()
  {
    $request = $this->request;
    if(isset($request['title']))
      request::validate_string($request['title'], 3, 'title');
    if(isset($request['msg']))
      request::validate_tinymce($request['msg']);
    $news = new mNews();
    $news->newDate();
    $news->genUuid();
    $news->title = $request['title'];
    $news->msg = $request['msg'];
    $news->userUuid = $_SESSION['ui']['uuid'];
    $this->dbRes['class']::post(
      col: 'news',
      param: $news->_toArray()
    );
    response::json(204, '');
  }
  public function list()
  {
    $cursor = $this->dbRes['class']::get(
      col: 'news',
      param: [],
      order: ['dateCreate' => -1],
      limit: 100
    );
    $res = [];
    foreach($cursor as $doc){
      $news = new mNews();
      $news = $news->_toArray();
      foreach($news as $k => $v)
        $news[$k] = $doc->{$k};
      $res[] = $news;
    }
    response::json(200, $res);
  }
}