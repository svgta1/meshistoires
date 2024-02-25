<?php
namespace Meshistoires\Api\utils;
use Meshistoires\Api\utils\trace;
use Meshistoires\Api\utils\response;

class extException extends \Exception
{
  public function __construct(?\Throwable $t = null)
  {
    trace::set([
      'Exception' => [
        'Message' => $t->getMessage(),
        'trace' => $t->getTrace()
      ]
    ]);
    response::json_e(500, "Server error");
    parent::__construct($t);
  }
}
