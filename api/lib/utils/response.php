<?php
namespace Meshistoires\Api\utils;
use Meshistoires\Api\utils\trace;

class response
{
  const HEADER_EXPIRE = 14 * 60*60*24;
  public static function xml(int $code, $return)
  {
    header('Content-type: application/xml');
    http_response_code($code);
    echo $return;
    die();
  }
  public static function json(int $code, $return)
  {
    self::json_e($code, $return);
    die();
  }
  public static function json_e(int $code, $return)
  {
    header('Content-Type: application/json; charset=utf-8');
    http_response_code($code);
    header('Cache-Control: no-cache');
    header("X-Robots-Tag: all", true);
    $trace = trace::get();
    if($trace !== []){
      echo json_encode([
        'response' => $return,
        'trace' => $trace,
      ]);
    }else if(!is_null($return)){
      echo json_encode($return);
    }
  }
}
