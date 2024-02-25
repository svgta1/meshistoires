<?php
namespace Meshistoires\Api\controller\v1r0;
use Meshistoires\Api\controller\v1r0\ui;
use Meshistoires\Api\utils\response;
use Meshistoires\Api\utils\request;
use Meshistoires\Api\utils\auth as utilsAuth;
use Meshistoires\Api\backend\db;
use Meshistoires\Api\utils\mail;
use Svgta\WebAuthn\client as webauthnClient;
use Svgta\OidcClient as oidcCLient;
use Svgta\Lib\Utils;

class auth
{
  private $scopes = null;
  private $request = [];
  public function __construct(?array $scopes, array $request)
  {
    $this->scopes = $scopes;
    $this->request = $request;
    if(isset($this->request['uuid']))
      request::validate_string($this->request['uuid'], 4, 'oidc id');
    $this->conf = \yaml_parse_file($_ENV['AUTH_YAML']);
    $this->dbRes = db::get_res()['class'];
  }

  public function logout()
  {
    utilsAuth::verifyScope($this->scopes);
    $_SESSION = [];
    response::json(204, '');
  }

  public function genCode()
  {
    $request = $this->request;
    request::validate_email($request['email']);
    $doc = $this->dbRes::getOne(
      col: 'contact',
      param: ['mail' => $request['email']]
    );
    $name = "";
    if(!is_null($doc)){
      if($doc->deleted)
        response::json(400, 'User deleted');
      $name = $doc->givenname;
    }
    $code = Utils::randomString(36);
    $mail = new mail();
    $tpl = \file_get_contents($_ENV['MAIL_TPL'] . '/code.tpl');
    $tpl = str_replace('##code##', $code, $tpl);
    $tpl = str_replace('##date##', \date("d-m-Y H:i", time() + 3600), $tpl);

    $mail->send(
      subject: "Code d'accès",
      body: $tpl,
      toMail: $request['email'],
      toName: $name
    );
    $_SESSION['authCode'] = [
      'code' => $code,
      'exp' => time() + 3600,
      'db' => $doc,
      'cpt' => 0,
    ];
    response::json(204, "");
  }
  public function verifyCode()
  {
    if(!isset($_SESSION['authCode']))
      response::json(400, 'Bad accès');
    $request = $this->request;
    request::validate_email($request['email']);

    $_SESSION['authCode']['cpt'] += 1;
    if($_SESSION['authCode']['cpt'] > 3){
      $_SESSION['authCode'] = [];
      response::json(400, 'To many Bad code');
    }

    if(is_null($_SESSION['authCode']['db'])){
      request::validate_giveName($request['name']);
      if($request['name'] == "false")
        response::json(400, "Bad user name");
    }

    if($_SESSION['authCode']['code'] !== $request['code'])
      response::json(400, 'Bad code');
    if($_SESSION['authCode']['exp'] < time())
      response::json(400, 'Code expired');

    $contact = ui::getContactInfo(['mail' => $request['email']]);
    if(is_null($contact)){
      $contact = new \stdClass();
      $contact->mail = $request['email'];
      $contact->givenname = $request['name'];
      $contact->sn = null;
      $contact->uuid = ui::insertContactInfo(
        email: $contact->mail,
        sn: $contact->sn,
        givenname: $contact->givenname
      );
    }

    $jws = $this->setAuth($contact, 'code');
    $_SESSION['authCode'] = [];
    response::json(200, $jws);
  }
  public function renewJWT()
  {
    $request = $this->request;
    $scopes = $this->scopes;
    utilsAuth::verifyScope($scopes);
    try{
      $payload = utilsAuth::$authPayload;
      $jws = utilsAuth::set_jws(
        scope: 'auth',
        email: $payload['email'],
        givenName: $payload['givenName'],
        rp: $payload['rp']
      );
    }catch(\Throwable $t){
      response::json(403, 'Bad token');
    }
    response::json(200, $jws);
  }
  public function list()
  {
    $ret = [];
    foreach($this->conf as $ar){
      $_ret = [
        'id' => $ar['id'],
        'desc' => $ar['desc'],
        'type' => $ar['type'],
      ];
      $ret[] = $_ret;
    }
    response::json(200, $ret);
  }

  public function webauthn_params()
  {
    $webauthn = new webauthnClient();
    $webauthn->rp->set(
      name: $_ENV['SITE_TITLE'],
    );
    $webauthn->userVerification->required();
    response::json(200, json_decode($webauthn->authenticate()->toJson()));
  }
  public function webauthn_auth()
  {
    $request = $this->request;
    $webauthn = new webauthnClient();
    $webauthn->rp->set(
      name: $_ENV['SITE_TITLE'],
    );
    $response = $webauthn->authenticate()->response(json_encode($request));
    $doc = $this->dbRes::getOne(
      col: 'keySec',
      param: ['credentialId' => $response['credentialId']]
    );
    if(is_null($doc))
      response::json(400, 'no key found');
    $content = json_decode(json_encode($doc), true);

    if(!($content['credentialId'] == $response['credentialId']))
      response::json(400, [
        'msg' =>'Bad security key',
        'content' => $content['credentialId'],
        'resp' => $response['credentialId']
      ]);
    $user = $this->dbRes::getOne(
      col: 'contact',
      param: ['uuid' => $response['userHandle'], 'deleted' => false]
    );
    if(is_null($user))
      response::json(400, 'no user found');
    $validation = $webauthn->authenticate()->validate(
      device: $content['jsonData']
    );
    $content['jsonData'] = $validation;
    unset($content['_id']);
    $doc = $this->dbRes::put(
      col: 'keySec',
      uuid: $response['credentialId'],
      param: $content
    );

    $userInfo = ui::getContactInfo(['uuid' => $response['userHandle']]);
    $jws = $this->setAuth($userInfo, "webauthn");
    $secKeyName = null;
    foreach($userInfo->sec_keys as $key){
      if($key->credentialId == $response['credentialId']){
        $secKeyName = $key->name;
        break;
      }
    }
    $mail = new mail();
    $tpl = \file_get_contents($_ENV['MAIL_TPL'] . '/auth_webauthn.tpl');
    $tpl = str_replace('##WEBAUTHN##', $secKeyName, $tpl);
    if(isset($_SESSION['ui']) && isset($_SESSION['ui']['browserTz']))
      \date_default_timezone_set($_SESSION['ui']['browserTz']);
    $date = \date('d-m-Y H:i:s');
    $tpl = str_replace('##DATE##', $date, $tpl);

    $mail->send(
      subject: "Nouvelle authentification au site " . $_ENV['SITE_TITLE'],
      body: $tpl,
      toMail: $userInfo->mail,
      toName: $userInfo->givenname
    );
    response::json(200, $jws);
  }
  public function webauthn_enreg_params()
  {
    $scopes = $this->scopes;
    utilsAuth::verifyScope($scopes);
    $webauthn = new webauthnClient();
    $webauthn->rp->set(
        name: $_ENV['SITE_TITLE'],
    );

    $userInfo = ui::userInfo();

    $webauthn->user->set(
      name: \trim($userInfo['sn'] . ' ' . $userInfo['givenname']),
      id: $userInfo['uuid'],
      displayName: $userInfo['givenname'],
    );
    $webauthn->pubKeyCredParams->add('EDDSA');
    $webauthn->pubKeyCredParams->add('ES256');
    $webauthn->pubKeyCredParams->add('RS512');
    $webauthn->pubKeyCredParams->add('RS256');
    $webauthn->userVerification->required();
    $webauthn->residentKey->required();
    $webauthn->authenticatorAttachment->all();
    $webauthn->attestation->none();

    foreach($userInfo['sec_keys'] as $k)
      $webauthn->excludeCredentials->add($k->credentialId);

    response::json(200, json_decode($webauthn->register()->toJson()));
  }
  public function webauthn_enreg()
  {
    $scopes = $this->scopes;
    $request = $this->request;
    utilsAuth::verifyScope($scopes);

    $webauthn = new webauthnClient();
    $webauthn->rp->set(
      name: $_ENV['SITE_TITLE'],
    );
    $keyName = $request['keyName'];
    unset($request['keyName']);
    request::input_to_string($keyName);
    $aaguid = $webauthn->register()->aaguid(\json_encode($request));
    $validation = $webauthn->register()->validate();

    $userInfo = ui::userInfo();
    $userInfo['sec_keys'][] = [
      'credentialId' => $validation['credentialId'],
      'name' => $keyName,
      ];
    ui::updateUserInfo(['sec_keys' => $userInfo['sec_keys']]);

    $validation['uuid'] = $validation['credentialId'];
    $this->dbRes::post(
      col: 'keySec',
      param: $validation
    );
    response::json(200, 'Enreg ok');
  }
  public function oidc_uri()
  {
    $request = $this->request;
    if(!isset($this->conf[$request['uuid']]))
      response::json(404, 'OIDC provider not set ' . $request['uuid']);
    $provider = $this->conf[$request['uuid']];
    if(!$provider['type'] == 'oidc')
      response::json(404, 'OIDC provider not set ' . $request['uuid']);
    if(is_null($provider['discovery'])){
      $client = new oidcCLient\init();
      $client->client_id($provider['client_id']);
      $client->add_OP_info('authorization_endpoint', $provider['endpoints']['authorization_endpoint']);
    }else{
      $client = new oidcCLient\init(
        $provider['discovery'],
        $provider['client_id']
      );
    }
    $auth = $client->authorization($provider['callback']);
    $auth->addScope($provider['scopes']);
    $auth->set_state();
    if($provider['set_nonce'])
      $auth->set_nonce();
    response::json(200, $auth->getUri());
  }
  public function oidc_auth()
  {
    $request = $this->request;
    $uuid = parse_url($request['uuid'])['path'];
    if(!isset($this->conf[$uuid]))
      response::json(404, 'OIDC provider not set ' . $uuid);
    $provider = $this->conf[$uuid];
    if(!$provider['type'] == 'oidc')
      response::json(404, 'OIDC provider not set ' . $uuid);
    if(is_null($provider['discovery'])){
      $client = new oidcCLient\init();
      $client->client_id($provider['client_id']);
      $client->client_secret($provider['client_secret']);
      $client->add_OP_info('token_endpoint', $provider['endpoints']['token_endpoint']);
      $client->add_OP_info('userinfo_endpoint', $provider['endpoints']['userinfo_endpoint']);
    }else{
      $client = new oidcCLient\init(
        $provider['discovery'],
        $provider['client_id']
      );
      if(isset($provider['client_secret']) && !is_null($provider['client_secret'])){
        $client->client_secret($provider['client_secret']);
      }
      if(isset($provider['cert_path']) &&
        !is_null($provider['cert_path']) &&
        isset($provider['private_key_path']) &&
        !is_null($provider['private_key_path'])
      ){
        $secret = isset($provider['private_key_secret']) ? $provider['private_key_secret'] : null;
        $client->keysManager()
          ->use_for_signVerify()
          ->set_private_key_pem_file($provider['private_key_path'], $secret)
          ->set_x509_file($provider['cert_path'])
          ->build();
        }
    }
    $tokenRes = $client->token();
    if(isset($provider['cert_path']) && !is_null($provider['cert_path'])){
      $tokenRes->set_auth_method('private_key_jwt');
      $tokenRes->jwt_headers_options('x5t');
    }

    $tokens = $tokenRes->get_tokens();
    $userInfo = $client->userInfo();

    $contact = ui::getContactInfo(['mail' => $userInfo['email'], 'deleted' => false]);
    if(is_null($contact)){
      $contact = new \stdClass();
      $contact->mail = $userInfo['email'];
      if(isset($userInfo['given_name'])){
        $contact->givenname = $userInfo['given_name'];
      }else if(isset($userInfo['givenname'])){
        $contact->givenname = $userInfo['givenname'];
      }else if(isset($userInfo['name'])){
        $contact->givenname = $userInfo['name'];
      }

      $fn = null;
      if(isset($userInfo['family_name'])){
        $fn = $userInfo['family_name'];
      }else if(isset($userInfo['familyname'])){
        $fn = $userInfo['familyname'];
      }else if(isset($userInfo['login'])){
        $fn = $userInfo['login'];
      }else if(isset($userInfo['id'])){
        $fn = $userInfo['id'];
      }else if(isset($userInfo['sub'])) {
        $fn = $userInfo['sub'];
      }
      $contact->sn = $fn;
      $contact->uuid = ui::insertContactInfo(
        email: $contact->mail,
        sn: $contact->sn,
        givenname: $contact->givenname
      );
    }

    $jws = $this->setAuth($contact, $uuid);
    $mail = new mail();
    $tpl = \file_get_contents($_ENV['MAIL_TPL'] . '/auth_oidc.tpl');
    $tpl = str_replace('##OIDC##', $provider['desc'], $tpl);
    if(isset($_SESSION['ui']) && isset($_SESSION['ui']['browserTz']))
      \date_default_timezone_set($_SESSION['ui']['browserTz']);
    $date = \date('d-m-Y H:i:s');
    $tpl = str_replace('##DATE##', $date, $tpl);

    $mail->send(
      subject: "Nouvelle authentification au site " . $_ENV['SITE_TITLE'],
      body: $tpl,
      toMail: $contact->mail,
      toName: $contact->givenname
    );
    response::json(200, $jws);
  }
  private function setAuth($contact, string $rp): string{
    $_SESSION['ui'] = [];
    $_SESSION['ui']['givenName'] = $contact->givenname;
    $_SESSION['ui']['familyName'] = $contact->sn;
    $_SESSION['ui']['email'] = $contact->mail;
    $_SESSION['ui']['uuid'] = $contact->uuid;

    $jws = utilsAuth::set_jws(
      scope: 'auth',
      email: $contact->mail,
      givenName: $contact->givenname,
      rp: $rp
    );
    return $jws;
  }
}
