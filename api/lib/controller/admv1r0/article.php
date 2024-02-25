<?php
namespace Meshistoires\Api\controller\admv1r0;
use Meshistoires\Api\utils\trace;
use Meshistoires\Api\utils\response;
use Meshistoires\Api\utils\request;
use Meshistoires\Api\utils\auth as utilsAuth;
use Meshistoires\Api\utils\seo;
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
  public function getArticle()
  {
    $col = "articles";
    $col_menu = "menus";
    $res = [];

    $doc = $this->dbRes['class']::getOne(col: $col, param: ['uuid' => $this->request['uuid']]);
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
