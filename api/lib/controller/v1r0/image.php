<?php
namespace Meshistoires\Api\controller\v1r0;
use Meshistoires\Api\utils\trace;
use Meshistoires\Api\utils\response;
use Meshistoires\Api\utils\request;
use Meshistoires\Api\backend\stockage;
use Meshistoires\Api\utils\seo;

class image
{
  private $stockageRes = null;
  private $scopes = null;
  private $request = [];

  const HEADER_EXPIRE = 14 * 60*60*24;

  public function __construct(?array $scopes, array $request)
  {
    $this->scopes = $scopes;
    $this->request = $request;
    if(isset($this->request['uuid']))
      request::validate_string($this->request['uuid'], 10, 'image');
    $this->stockageRes = stockage::get_res();
  }

  public function get()
  {
    $request = $this->request;
    try{
      $doc = $this->stockageRes['class']::getImageInfo($request['uuid']);
      $this->sentImg($doc);
    }catch(\Throwable $t){
      response::json('404', 'No Image found');
    }
  }

  public function getThumb300()
  {
    $request = $this->request;
    try{
      $doc = $this->stockageRes['class']::getThmb300Info($request['uuid']);
      $this->sentImg($doc);
    }catch(\Throwable $t){
      response::json('404', 'No Image found');
    }
  }

  public function getThumb()
  {
    $request = $this->request;
    try{
      $doc = $this->stockageRes['class']::getThmbInfo($request['uuid']);
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

    echo $this->stockageRes['class']::getStream($doc['stream']);
  }
}
