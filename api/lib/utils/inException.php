<?php
namespace Meshistoires\Api\utils;
use Meshistoires\Api\utils\trace;
use Meshistoires\Api\utils\response;

class inException extends \Exception
{
  public function __construct($message = null, $trace = null, $retCode = 500)
  {
    if($retCode < 500)
      trace::$useTrace = true;
    trace::set([
      'Exception' => [
        'Message' => $message,
        'trace' => is_null($trace) ? $message : $trace,
      ]
    ]);
    response::json_e($retCode, "Server error");
    parent::__construct($message);
  }
}
