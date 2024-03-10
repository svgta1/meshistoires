<?php
namespace Meshistoires\Api\utils;
use Svgta\Lib\JWT;
use Svgta\Lib\Utils;

class auth
{
  public static $authPayload = null;
  public static $scopes = [];

  public static function verifyScope(array $scopes)
  {
    $verify = [];
    foreach($scopes as $v)
      $verify[$v] = false;
    foreach(self::$scopes as $v){
      if(in_array($v, $scopes))
        $verify[$v] = true;
    }
    foreach($verify as $k=>$v){
      if($v === false)
        response::json(403, 'Not authorized');
    }
  }

  public static function verifyAuthHeaderSignature()
  {
    $headers = \array_change_key_case(\getallheaders());
    if(!isset($headers['authorization']) || !$headers['authorization'])
      return;
    if(!isset($_SESSION['signKey']))
      return;
    list($type, $jws) = explode(' ', $headers['authorization']);
    try{
      JWT::verifyPEM(
        jws: $jws,
        publicKey: $_SESSION['signKey']['publicKey'],
        kid: \hash('sha256', $_SESSION['ui']['uuid'])
      );
    }catch(\Throwable $t){
      response::json(401, 'Bad authorization token');
    }
    $payload = JWT::parseJWS($jws)['payload'];
    if($payload['iss'] !== $_ENV['SITE_TITLE'])
      response::json(401, 'Bad authorization token');
    if(\boolval($_ENV['ADMIN_SECURE_JWS'])){
      if($payload['sub'] !== $_SESSION['ui']['uuid'])
        response::json(401, 'Bad authorization token');

      $h = \hash('sha256', json_encode([
        'uuid' => $_SESSION['ui']['uuid'],
        'ua' => Utils::getUA(),
        'ip' => Utils::getIP(),
      ]));
      if($payload['h'] !== $h)
        response::json(401, 'Bad authorization token');
    }
    self::$authPayload = $payload;
    self::$scopes = \explode(' ', $payload['scope']);
  }
  public static function set_jws(
    string $scope,
    string $email,
    string $givenName,
    string $rp
  ): string
  {
    $admin = \yaml_parse_file($_ENV['ADMIN_YAML'])['adminList'];
    if(isset($admin[$email])){
      $scope .= ' ' . implode(' ', $admin[$email]['scopes']);
    }
    $payload = [
      'scope' => $scope,
      'email' => $email,
      'givenName' => $givenName,
      'iat' => time(),
      'iss' => $_ENV['SITE_TITLE'],
      'sub' => Utils::genUUID(),
      'jti' => Utils::genUUID(),
      'rp' => $rp,
    ];

    if(\boolval($_ENV['ADMIN_SECURE_JWS'])){
      $payload['h'] = hash('sha256', json_encode([
        'uuid' => $_SESSION['ui']['uuid'],
        'ua' => Utils::getUA(),
        'ip' => Utils::getIP(),
      ]));
      $payload['sub'] = $_SESSION['ui']['uuid'];
    }
    if(!isset($_SESSION['signKey'])){
      $_SESSION['signKey'] = utils::genEcKey()['PEM'];
    }
    $jws = JWT::signPEM(
      payload: $payload,
      privateKey: $_SESSION['signKey']['privateKey'],
      kid: \hash('sha256', $_SESSION['ui']['uuid'])
    );
    return $jws;
  }
}
