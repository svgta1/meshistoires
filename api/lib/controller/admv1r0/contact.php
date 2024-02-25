<?php
namespace Meshistoires\Api\controller\admv1r0;
use Meshistoires\Api\utils\trace;
use Meshistoires\Api\utils\response;
use Meshistoires\Api\utils\request;
use Meshistoires\Api\utils\auth as utilsAuth;
use Meshistoires\Api\utils\mail;
use Meshistoires\Api\backend\db;
use Svgta\Lib\Utils;

class contact
{
  public function __construct(?array $scopes, array $request)
  {
    $this->scopes = $scopes;
    $this->request = $request;
    utilsAuth::verifyScope($scopes);
    $this->dbRes = db::get_res();
    $this->mail = new mail();
  }
  public function restore()
  {
    $request = $this->request;
    request::input_to_string($request['uuid']);
    $uuid = $request['uuid'];
    $user = $this->dbRes['class']::getOne(
      col: 'contact',
      param: ['uuid' => $uuid]
    );
    if(is_null($user))
      response::json(400, 'No user available');
    $up = [
      'deleted' => false,
      'ban' => false,
      'code_activation' => null,
      'code_supp' => null,
      'dateUpdate' => time(),
    ];
    $this->dbRes['class']::put(
      col: 'contact',
      uuid: $uuid,
      param: $up
    );
    $tpl = \file_get_contents($_ENV['MAIL_TPL'] . '/contact_restore.tpl');
    $tpl = str_replace('##DOMAIN##', $_ENV['DOMAIN'], $tpl);
    $tpl = str_replace('##SITE##', $_ENV['SITE_TITLE'], $tpl);
    $this->mail->send(
      subject: "Restauration de votre compte",
      body: $tpl,
      toMail: $user->mail,
      toName: $user->givenname
    );
    response::json(204, '');
  }
  public function delete()
  {
    $request = $this->request;
    request::input_to_string($request['delType']);
    request::input_to_string($request['uuid']);
    $uuid = $request['uuid'];
    $user = $this->dbRes['class']::getOne(
      col: 'contact',
      param: ['uuid' => $uuid]
    );
    if(is_null($user))
      response::json(400, 'No user available');

    switch($request['delType']){
      case 'desactivation':
        $this->desactivation($user);
        break;
      case 'delete':
        $this->sup($user);
        break;
      case 'ban':
        $this->ban($user);
        break;
      default:
        response::json(400, 'Bad delType');
    }
  }
  private function ban($user)
  {
    $up = [
      'deleted' => true,
      'ban' => true,
      'code_activation' => null,
      'code_supp' => null,
      'dateUpdate' => time(),
    ];
    $this->dbRes['class']::put(
      col: 'contact',
      uuid: $user->uuid,
      param: $up
    );
    $tpl = \file_get_contents($_ENV['MAIL_TPL'] . '/contact_banni.tpl');
    $this->mail->send(
      subject: "Bannissement de votre compte",
      body: $tpl,
      toMail: $user->mail,
      toName: $user->givenname
    );
    response::json(204, '');
  }
  private function desactivation($user)
  {
    $up = [
      'deleted' => true,
      'ban' => false,
      'code_activation' => Utils::randomString(),
      'code_supp' => Utils::randomString(),
      'dateUpdate' => time(),
    ];
    $this->dbRes['class']::put(
      col: 'contact',
      uuid: $user->uuid,
      param: $up
    );
    $tpl = \file_get_contents($_ENV['MAIL_TPL'] . '/contact_desactivated.tpl');
    $tpl = str_replace('##DOMAIN##', $_ENV['DOMAIN'], $tpl);
    $tpl = str_replace('##UUID##', $user->uuid, $tpl);
    $tpl = str_replace('##CODEACTIVE##', $up['code_activation'], $tpl);
    $tpl = str_replace('##CODESUP##', $up['code_supp'], $tpl);
    $this->mail->send(
      subject: "Désactivation de votre compte",
      body: $tpl,
      toMail: $user->mail,
      toName: $user->givenname
    );
    response::json(204, '');
  }
  private function sup($user)
  {
    //anonymous comment
    $this->dbRes['class']::putMany(
      col: 'comment',
      filter: ['userUuid' => $user->uuid],
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
      param: ['hasResponse' => true, 'type' => 'contact', 'userUuid' => $user->uuid]
    );
    //--- suppression des réponses;
    foreach($cursor as $doc)
      $this->dbRes['class']::deleteMany(
        col: 'mail',
        param: ['responseTo' => $doc->uuid, 'type' => 'response']
      );
    //--- suppression des mails
    $this->dbRes['class']::deleteMany(
      col: 'mail',
      param: ['userUuid' => $user->uuid, 'type' => 'contact']
    );
    //anonymous analytic
    $this->dbRes['class']::putMany(
      col: 'analytic',
      filter: ['userUuid' => $user->uuid],
      param: [
        'userUuid' => null,
      ]
    );
    //del user
    $this->dbRes['class']::delete(
      col: 'contact',
      uuid: $user->uuid,
    );
    $tpl = \file_get_contents($_ENV['MAIL_TPL'] . '/contact_deleted.tpl');
    $this->mail->send(
      subject: "Suppression de votre compte",
      body: $tpl,
      toMail: $user->mail,
      toName: $user->givenname
    );
    response::json(204, '');
  }
  public function update()
  {
    $request = $this->request;
    request::input_to_string($request['uuid']);
    $uuid = $request['uuid'];
    $ar = [
      'givenname',
      'sn',
      'abo_news',
      'mail'
    ];
    $uInfo = [];
    foreach($request as $k => $v){
      if(!in_array($k, $ar)){
        continue;
      }
      if(is_string($v))
        request::input_to_string($v);
      if($k == 'mail')
        request::validate_email($v);
      $uInfo[$k] = $v;
    }
    $uInfo['dateUpdate'] = time();
    $doc = db::get_res()['class']::put(col: 'contact', uuid: $uuid, param: $uInfo);
    response::json(204, '');
  }
  public function get()
  {
    request::input_to_string($request['uuid']);
    $uuid = $request['uuid'];
    $doc = $cursor = $this->dbRes['class']::getOne(
      col: 'contact',
      param: ['uuid' => $uuid]
    );
    if(is_null($doc))
      response::json(400, 'Compte inexistant');
    $ar = \json_decode(\json_encode($doc), true);
    unset($ar['_id']);
    unset($ar['sec_keys']);
    unset($ar['code_activation']);
    unset($ar['code_supp']);
    $ar['nbr_keySec'] = \count($doc->sec_keys);
    return response::json(200, $ar);
  }
  public function list()
  {
    $cursor = $this->dbRes['class']::get(
      col: 'contact',
      param: [],
      order: ['dateUpdate' => -1]
    );
    $res = [];
    foreach($cursor as $doc){
      $ar = \json_decode(\json_encode($doc), true);
      unset($ar['_id']);
      unset($ar['sec_keys']);
      unset($ar['code_activation']);
      unset($ar['code_supp']);
      $ar['nbr_keySec'] = \count($doc->sec_keys);
      $res[] = $ar;
    }
    response::json(200, $res);
  }
}
