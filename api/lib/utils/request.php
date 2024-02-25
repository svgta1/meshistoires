<?php
namespace Meshistoires\Api\utils;
use Meshistoires\Api\utils\response;
use Svgta\Lib\JWT;

class request
{
  public static function JWE_dec(array $request, bool $jsonArray = false)
  {
    if(!isset($request['type']) || (isset($request['type']) && $request['type'] != 'enc')){
      response::json('403', 'The request must be encypted');
    }
    if(isset($request['type']) && $request['type'] == 'enc')
      unset($request['type']);
    try{
      $res = JWT::decrypt($request['cypher'], $_SESSION['keySetEncPrivate']);
    }catch(\Throwable $t){
      response::json('403', 'Session out');
    }

    $h = \array_change_key_case(\getallheaders());
    if(isset($h['content-type']) && isset($h['content-type']) == 'application/json')
      $res = json_decode($res, $jsonArray);
    unset($request['cypher']);
    unset($request['kid']);
    foreach($request as $k=>$v){
      if($jsonArray)
        $res[$k] = $v;
      else
        $res->{$k} = $v;
    }
    return $res;
  }
  public static function validate_uuid(mixed $uuid)
  {
    $v = is_string($uuid) && preg_match('/^[a-f\d]{8}(-[a-f\d]{4}){4}[a-f\d]{8}$/i', $uuid);
    if(!$v)
      response::json(400, 'Bad uuid format');
  }
  public static function validate_email(string &$email)
  {
    self::input_to_string($email);
    if(!\filter_var($email, FILTER_VALIDATE_EMAIL))
      response::json(400, 'Bad email address');
  }
  public static function validate_giveName(string &$givenName)
  {
    self::validate_string(
      str: $givenName,
      name: 'GivenName'
    );
  }
  public static function validate_familyName(string &$familyName)
  {
    self::input_to_string($familyName);
  }
  public static function validate_comment(string &$comment)
  {
    self::validate_string(
      str: $comment,
      name: 'Comment'
    );
  }
  public static function validate_string(string &$str, int $len = 3, string $name = '')
  {
    self::input_to_string($str);
    if(\strlen($str) < $len)
      response::json(400, $name . ' to short');
  }
  public static function validate_bool($bool, string $name = '')
  {
    if(!is_bool($bool))
      response::json(400, $name . ' not a boolean');
  }
  public static function validate_int(int $int, string $name = '')
  {
    if(!is_int($int))
      response::json(400, $name . ' not an integer');
  }
  public static function input_to_string(?string &$str)
  {
    $str = \htmlspecialchars(\strip_tags(\trim($str)));
  }
}
