<?php
namespace Meshistoires\Api\controller\admv1r0;
use Meshistoires\Api\utils\trace;
use Meshistoires\Api\utils\response;
use Meshistoires\Api\utils\request;
use Meshistoires\Api\utils\auth as utilsAuth;
use Meshistoires\Api\backend\stockage;
use Meshistoires\Api\utils\seo;

class image
{
  const HEADER_EXPIRE = 14 * 60*60*24;

  public function __construct(?array $scopes, array $request)
  {
    \set_time_limit(1800);
    \ini_set('upload_max_filesize', '10M');
    \ini_set('post_max_size', '10M');

    $this->scopes = $scopes;
    $this->request = $request;
    if(is_null($scopes))
      $scopes = [];
    utilsAuth::verifyScope($scopes);
    $this->stockage = stockage::get_res();
    if(isset($this->request['uuid']))
      request::validate_string($this->request['uuid'], 10, 'image');
    $dir = $_ENV['WWW_DATA_DIR'] . '/imgs';
    if(!\is_dir($dir)){
      \mkdir($dir, 0755, true);
    }
    $this->dir = $dir;
  }
  public function list()
  {
    $request = $this->request;
    if(isset($request['skip']))
      $request['skip'] = (int)$request['skip'];

    if(!isset($request['skip']))
      $request['skip'] = 0;
    $cursor = $this->stockage['class']::list($request['skip']);
    $res = [
      'skip' => $request['skip'],
      'list' => [],
      'count' => 0,
      'total' => $this->stockage['class']::count()
    ];
    foreach($cursor as $doc)
      $res['list'][] = [
        'title' => $doc->metadata->title,
        'value' => $doc->filename
      ];
    $res['count'] = count($res['list']);
    response::json('200', $res);
  }
  public function delete()
  {
    $request = $this->request;
    $this->stockage['class']::delete($request['uuid']);
    response::json(204, '');
  }
  public function post()
  {
    $request = $this->request;
    if(!\in_array(\strtolower(\pathinfo($request['file_name'], PATHINFO_EXTENSION)), ["gif", "jpg", "png", "jpeg", "webp"]))
      response::json(400, 'extension not supported');

    if(isset($request['content']) && !is_string($request['content']))
      response::json(400, var_dump($request['content']));

    $binary = \base64_decode(\explode( ',', $request['content'])[1]);
  
    $file = $this->dir . '/' . $request['file_name'];
    \file_put_contents($file, $binary);
    $fileName = $this->stockage['class']::post($file);
    response::json(200, ['filename' => $fileName]);
  }

  public function get()
  {
    $request = $this->request;
    try{
      $doc = $this->stockage['class']::getImageInfo($request['uuid']);
      $this->sentImg($doc);
    }catch(\Throwable $t){
      response::json('404', 'No Image found');
    }
  }

  public function getThumb300()
  {
    $request = $this->request;
    try{
      $doc = $this->stockage['class']::getThmb300Info($request['uuid']);
      $this->sentImg($doc);
    }catch(\Throwable $t){
      response::json('404', 'No Image found');
    }
  }

  public function getThumb()
  {
    $request = $this->request;
    try{
      $doc = $this->stockage['class']::getThmbInfo($request['uuid']);
      $this->sentImg($doc);
    }catch(\Throwable $t){
      response::json('404', 'No Image found');
    }
  }

  private function sentImg(array $doc)
  {
    header('Content-type: ' . $doc['metadata']->metadata->ctype);
    header('Cache-Control: public, max-age=604800, must-revalidate');
    $lmt = isset($doc['metadata']->metadata->exif->FileDateTime) ? $doc['metadata']->metadata->exif->FileDateTime : $doc['metadata']->uploadDate;
    if($lmt instanceof \MongoDB\BSON\UTCDateTime)
      $lmt = (integer)$lmt->__toString();
    $etag = 'W/"' . md5($lmt) . '"';
    header('Last-Modified: ' . gmdate('D, d M Y H:i:s', $lmt) . " GMT");
    header("Etag: $etag");
    header("X-Robots-Tag: all", true);
		//header("Cache-Control: public", true);
		header("Pragma: public", true);
		header("Content-length: " . $doc['metadata']->length);
    header('Expires: ' . gmdate('D, d M Y H:i:s', time() + self::HEADER_EXPIRE) . ' GMT', true);
    $name = seo::seofy($_ENV['SITE_TITLE'] . '_'.$doc['metadata']->metadata->title);
    header('Content-Disposition:inline;filename="'.$name.'"');

    echo $this->stockage['class']::getStream($doc['stream']);
  }
}
