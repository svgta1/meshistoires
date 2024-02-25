<?php
namespace Meshistoires\Api\controller\v1r0;
use Meshistoires\Api\utils\trace;
use Meshistoires\Api\utils\response;
use Meshistoires\Api\utils\seo;
use Meshistoires\Api\backend\db;
use Meshistoires\Api\utils\request;
use Meshistoires\Api\utils\auth as utilsAuth;
use Meshistoires\Api\utils\ui as utilsUi;
use Meshistoires\Api\utils\mail;
use Meshistoires\Api\model\contact as mContact;
use Meshistoires\Api\model\analytic as mAnalytic;
use Svgta\Lib\Utils;
use \GeoIp2\Database\Reader as geoipReader;

class ui
{
  private $scopes = null;
  private $request = [];
  public function __construct(?array $scopes, array $request)
  {
    $this->scopes = $scopes;
    $this->request = $request;
    if(isset($this->request['uuid']))
      request::validate_uuid($this->request['uuid']);
    $this->dbRes = db::get_res();
    $this->mail = new mail();
  }
  private function _actionRestore(array $request, $doc)
  {
    if($doc->code_activation !== $request['code'])
      response::json(400, 'bad access');
    $up = [
      'deleted' => false,
      'ban' => false,
      'code_activation' => null,
      'code_supp' => null,
      'dateUpdate' => time(),
    ];
    $this->dbRes['class']::put(
      col: 'contact',
      uuid: $request['uuid'],
      param: $up
    );
    $tpl = \file_get_contents($_ENV['MAIL_TPL'] . '/contact_restore.tpl');
    $tpl = str_replace('##DOMAIN##', $_ENV['DOMAIN'], $tpl);
    $tpl = str_replace('##SITE##', $_ENV['SITE_TITLE'], $tpl);
    $this->mail->send(
      subject: "Restauration de votre compte",
      body: $tpl,
      toMail: $doc->mail,
      toName: $doc->givenname
    );
    response::json(204, '');
  }
  private function _actionDelete(array $request, $doc)
  {
    if($doc->code_supp !== $request['code'])
      response::json(400, 'bad access');

    //anonymous comment
    $this->dbRes['class']::putMany(
      col: 'comment',
      filter: ['userUuid' => $doc->uuid],
      param: [
        'userUuid' => null,
        'sn' => 'Compte supprimé',
        'givenName' => 'Compte supprimé',
        'dateUpdate' => time(),
      ]
    );
    //del mail
    //--- recup mail avec réponses
    $cursor = $this->dbRes['class']::get(
      col: 'mail',
      param: ['hasResponse' => true, 'type' => 'contact', 'userUuid' => $doc->uuid]
    );
    //--- suppression des réponses;
    foreach($cursor as $_doc)
      $this->dbRes['class']::deleteMany(
        col: 'mail',
        param: ['responseTo' => $_doc->uuid, 'type' => 'response']
      );
    //--- suppression des mails
    $this->dbRes['class']::deleteMany(
      col: 'mail',
      param: ['userUuid' => $doc->uuid, 'type' => 'contact']
    );
    //anonymous analytic
    $this->dbRes['class']::putMany(
      col: 'analytic',
      filter: ['userUuid' => $doc->uuid],
      param: [
        'userUuid' => null,
      ]
    );
    //del user
    $this->dbRes['class']::delete(
      col: 'contact',
      uuid: $doc->uuid,
    );
    $tpl = \file_get_contents($_ENV['MAIL_TPL'] . '/contact_deleted.tpl');
    $this->mail->send(
      subject: "Suppression de votre compte",
      body: $tpl,
      toMail: $doc->mail,
      toName: $doc->givenname
    );
    response::json(204, '');
  }
  public function action()
  {
    $request = $this->request;
    if(!isset($request['uuid']))
      response::json(400, 'bad access');
    if(!isset($request['code']))
      response::json(400, 'bad access');
    if(!isset($request['action']))
      response::json(400, 'bad access');
    request::input_to_string($request['uuid']);
    request::input_to_string($request['code']);
    request::input_to_string($request['action']);
    $doc = db::get_res()['class']::getOne(
      col: "contact",
      param: ['uuid' => $request['uuid'], 'ban' => false, 'deleted' => true]
    );
    if(is_null($doc))
      response::json(400, 'bad access');
    if(is_null($doc->code_activation))
      response::json(400, 'bad access');
    if(is_null($doc->code_supp))
      response::json(400, 'bad access');
    switch($request['action']){
      case 'restore':
        $this->_actionRestore($request, $doc);
        break;
      case 'delete':
        $this->_actionDelete($request, $doc);
        break;
      default:
        response::json(400, 'bad access');
    }
  }
  public function history()
  {
    utilsAuth::verifyScope($this->scopes);
    $cursor = db::get_res()['class']::get(
      col: "analytic",
      param: ['userUuid' => $_SESSION['ui']['uuid']],
      limit: 100,
      order: ['createTs' => -1],
      projection: ['path', 'createTs']
    );

    $ret = [];
    foreach($cursor as $doc)
      $ret[] = [
        'timestamp' => $doc->createTs,
        'path' => $doc->path
      ];
    response::json(200, $ret);
  }
  public function removeKey()
  {
    $request = $this->request;
    utilsAuth::verifyScope($this->scopes);
    $uInfo = self::userInfo();
    if(!is_array($uInfo['sec_keys']))
      $uInfo['sec_keys'] = json_decode(json_encode($uInfo['sec_keys']), TRUE);
    foreach($uInfo['sec_keys'] as $k => $key){
      if($key['credentialId'] == $request['key'])
        unset($uInfo['sec_keys'][$k]);
    }
    db::get_res()['class']::delete(
      col: "keySec",
      uuid: $request['key']
    );
    self::updateUserInfo(['sec_keys' => $uInfo['sec_keys']]);
    response::json(200, $uInfo);
  }
  public function updateProfile()
  {
    $request = $this->request;
    utilsAuth::verifyScope($this->scopes);
    $ar = [
      'givenname',
      'sn',
      'abo_news'
    ];
    foreach($request as $k => $v){
      if(!in_array($k, $ar)){
        unset($request[$k]);
        continue;
      }
      if(is_string($v)){
        request::input_to_string($v);
        $request[$k] = $v;
      }
    }
    self::updateUserInfo($request);
    response::json(204, '');
  }
  public function getProfile()
  {
    utilsAuth::verifyScope($this->scopes);
    response::json(200, self::userInfo());
  }
  public static function updateUserInfo(array $toUpdate)
  {
    $uInfo = self::userInfo();
    foreach($toUpdate as $k=>$v)
      $uInfo[$k] = $v;
    $uInfo['dateUpdate'] = time();
    $col = "contact";
    $doc = db::get_res()['class']::put(col: $col, uuid: $uInfo['uuid'], param: $uInfo);
  }
  public static function userInfo()
  {
    if(is_null(utilsAuth::$authPayload))
      return null;
    $doc = self::getContactInfo(['mail' => utilsAuth::$authPayload['email'], 'deleted' => false]);
    if(is_null($doc))
      return null;
    $ret = [
      'uuid' => $doc->uuid,
      'mail' => $doc->mail,
      'givenname' => $doc->givenname,
      'sn' => $doc->sn,
      'abo_news' => isset($doc->abo_news) ? $doc->abo_news : false,
      'sec_keys' => isset($doc->sec_keys) ? $doc->sec_keys : [],
      'dateCreate' => self::getDate($doc, 'dateCreate'),
      'dateUpdate' => self::getDate($doc, 'dateUpdate'),
    ];
    return $ret;
  }
  private static function getDate($doc, $dateSearch){
    $dc = 0;
    if(isset($doc->{$dateSearch}))
      $dc = $doc->{$dateSearch};
    if(
      ($dc == 0) &&
      isset($doc->_id) &&
      ($doc->_id instanceof \MongoDB\BSON\ObjectId)
    )
      $dc = $doc->_id->getTimestamp();
    return $dc;
  }
  public function post()
  {
    $request = $this->request;
    request::input_to_string($request['givenName']);
    if(isset($request[('email')]) && $request['email'])
      request::validate_email($request['email']);
    request::input_to_string($request['familyName']);
    if(!isset($_SESSION['ui']))
      $_SESSION['ui'] = [];
    $ar = [
      'tz' => $request['tz'],
      'ua' => $request['ua'],
      'lang' => $request['lang'],
      'isBot' => Utils::is_bot(),
      'ip' => Utils::getIP(),
      'ua' => Utils::getUA(),
      'geoIP' => utilsUi::getGeoIp(),
    ];
    $_SESSION['ui'] = \array_merge($ar, $_SESSION['ui']);

    $col = 'analytic';
    $analytic = new mAnalytic();
    $analytic->newDate();
    $analytic->browserUuid = $request['uuid'];
    $analytic->browserLang = $request['lang'];
    $analytic->browserTz = $request['tz'];
    $analytic->browserUa = $request['ua'];
    $analytic->isBot = Utils::is_bot();
    $analytic->ip = Utils::getIP();
    $analytic->ua = Utils::getUA()['UA'];
    $analytic->path = $request['path'];
    $analytic->userUuid = isset($_SESSION['ui']['uuid']) ? $_SESSION['ui']['uuid'] : null;

    $this->dbRes['class']::post(col: $col, param: $analytic->_toArray());
    response::json(204, "");
  }
  public static function getContactInfo(array $param)
  {
    $col = "contact";
    $doc = db::get_res()['class']::getOne(col: $col, param: $param);
    return $doc;
  }
  public static function insertContactInfo(string $email, ?string $sn, string $givenname): ?string
  {
    $u = new mContact();
    $u->newDate();
    $u->genUuid();
    $u->mail = $email;
    $u->givenname = $givenname;
    $u->sn = $sn;
    $cpt = db::get_res()['class']::count(col: "contact", param: ['mail' => $email]);
    if($cpt > 0)
      response::json('400', 'User exist');

    $res = db::get_res()['class']::post(col: "contact", param: $u->_toArray());
    if($res != 1)
      response::json('400', 'Error on create user');
    return $u->uuid;
  }
}
