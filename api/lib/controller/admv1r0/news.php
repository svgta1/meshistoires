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

  }
  public function put()
  {

  }
  public function post()
  {

  }
  public function list()
  {
    $cursor = $this->dbRes['class']::get(
      col: 'news',
      param: [],
      order: ['createTs' => -1],
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