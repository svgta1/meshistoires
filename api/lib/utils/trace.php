<?php
namespace Meshistoires\Api\utils;
use Svgta\Lib\Utils;

class trace
{
  private static $traces = [];
  public static $useTrace = false;

  public static function set($o): void
  {
    if($o instanceof \MongoDB\Model\BSONDocument){
      $o = json_decode(\MongoDB\BSON\toJSON(\MongoDB\BSON\fromPHP($o)), TRUE);
    }
    if(is_null($o))
      return;
    self::$traces[] = $o;
    if(isset($_ENV['DEBUG']) && $_ENV['DEBUG']){
      Utils::setLogLevel(LOG_DEBUG);
      Utils::log(LOG_DEBUG, $o);
    }
  }
  public static function get_html_json(bool $pretty = true): void
  {
    if($pretty)
      echo json_encode(self::get(), JSON_PRETTY_PRINT);
    else
      echo json_encode(self::get());
  }
  public static function get(): array
  {
    if(self::$useTrace)
      return self::$traces;
    return [];
  }
}
