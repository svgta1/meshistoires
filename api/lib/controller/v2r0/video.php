<?php
namespace Meshistoires\Api\controller\v2r0;
use Meshistoires\Api\utils\trace;
use Meshistoires\Api\utils\response;
use Meshistoires\Api\utils\request;
use Meshistoires\Api\backend\stockage;
use Meshistoires\Api\utils\seo;

class video
{
  private $stockageRes = null;
  private $scopes = null;
  private $request = [];

  const HEADER_EXPIRE = 150 * 60*60*24;

  public function __construct(?array $scopes, array $request)
  {
    $this->scopes = $scopes;
    $this->request = $request;
    if(isset($this->request['uuid']))
      request::validate_string($this->request['uuid'], 5, 'video');
    $this->stockageRes = stockage::get_res();
  }

  public function get()
  {
    $request = $this->request;
    try{
      $doc = $this->stockageRes['class']::getVideoInfo($request['uuid']);
      $this->setResponse($doc);
    }catch(\Throwable $t){
      response::json('404', 'No Video found');
    }
  }

  private function setResponse($doc)
  {
    header('Content-type: ' . $doc['metadata']->metadata->type);
    header('Cache-Control: public, max-age=604800, must-revalidate');
    header('Content-Length: ' . $doc['metadata']->length);
    header('Accept-Ranges: bytes');
    echo $this->stockageRes['class']::getStream($doc['stream']);
  }
}