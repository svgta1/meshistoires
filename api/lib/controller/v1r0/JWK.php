<?php
namespace Meshistoires\Api\controller\v1r0;
use Meshistoires\Api\utils\trace;
use Meshistoires\Api\utils\response;
use Meshistoires\Api\backend\db;
use Meshistoires\Api\utils\auth as utilsAuth;
use Svgta\Lib\utils;
use Svgta\Lib\Keys;

class JWK
{
  private $scopes = null;
  private $request = [];

  public function __construct(?array $scopes, array $request)
  {
    $this->scopes = $scopes;
    $this->request = $request;
  }
  public function getSign()
  {
    utilsAuth::verifyScope($this->scopes);
    $key = new Keys();
    $key
      ->set_public_key_pem($_SESSION['signKey']['publicKey'])
      ->use_for_signVerify()
      ->set_kid(\hash('sha256', $_SESSION['ui']['uuid']))
      ->build();
    response::json(200, $key::genKeySetForVerif());
  }
  public function getEnc()
  {
    if(!isset($_SESSION['keySetEncPrivate'])){
      $_SESSION['keySetEncPrivate'] = [
        "keys" => []
      ];
      $_SESSION['keySetEncPublic'] = [
        "keys" => []
      ];
      $kid = utils::genUUID();
      $key =  utils::genRsaKey(
        options: [
          'kid' => $kid,
          'use' => 'enc',
          'alg' => 'RSA-OAEP-256'
        ]
      );
      $_SESSION['keySetEncPrivate']['keys'][$kid] = json_decode($key['JWK']['privateKey'], TRUE);
      $_SESSION['keySetEncPublic']['keys'][$kid] = json_decode($key['JWK']['publicKey'], TRUE);
    }
    $keys = $_SESSION['keySetEncPublic']['keys'];
    response::json(200, array_shift($keys));
  }
}
