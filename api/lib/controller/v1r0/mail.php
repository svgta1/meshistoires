<?php
namespace Meshistoires\Api\controller\v1r0;
use Meshistoires\Api\utils\response;
use Meshistoires\Api\utils\request;
use Meshistoires\Api\utils\mail as uMail;
use Meshistoires\Api\model\mail as mMail;
use Meshistoires\Api\backend\db;
use Meshistoires\Api\utils\auth as utilsAuth;

class mail
{
  public function __construct(?array $scopes, array $request)
  {
    $this->scopes = $scopes;
    $this->request = $request;
    if(isset($this->request['uuid']))
      request::validate_uuid($this->request['uuid']);
    $this->dbRes = db::get_res();
    if(!isset($_SESSION['ui']['uuid']))
      response::json(400, 'hacking detected');
  }
  public function getResponse()
  {
    utilsAuth::verifyScope($this->scopes);
    $cursor = $this->dbRes['class']::get(
      col: "mail",
      param: ['responseTo' => $this->request['uuid'], 'type' => 'response'],
      limit: 100,
      order: ['createTs' => -1]
    );
    $rep = [];
    foreach($cursor as $doc){
      $ar = [
        'msg' => $doc->msg,
        'msgTs' => $doc->createTs,
      ];
      $rep[] = $ar;
    }
    response::json(200, $rep);
  }
  public function getList()
  {
    utilsAuth::verifyScope($this->scopes);
    $cursor = $this->dbRes['class']::get(
      col: "mail",
      param: ['userUuid' => $_SESSION['ui']['uuid'], 'type' => 'contact'],
      limit: 100,
      order: ['createTs' => -1]
    );
    $rep = [];
    foreach($cursor as $doc){
      $ar = [
        'msg' => $doc->msg,
        'msgTs' => $doc->createTs,
        'hasResponse' => $doc->hasResponse,
        'id' => $doc->uuid
      ];
      $rep[] = $ar;
    }
    response::json(200, $rep);
  }
  public function post()
  {
    utilsAuth::verifyScope($this->scopes);
    $request = $this->request;
    request::validate_comment($request['contact']);
    $contact = new mMail();
    $contact->newDate();
    $contact->genUuid();
    $contact->userUuid = $_SESSION['ui']['uuid'];
    $contact->msg = $request['contact'];
    $user = $this->dbRes['class']::getOne(
      col: 'contact',
      param: ['uuid' => $_SESSION['ui']['uuid'], 'deleted' => false]
    );
    if(is_null($user))
      response::json(400, 'user not found');
    $res = $this->dbRes['class']::post(
      col: 'mail',
      param: $contact->_toArray()
    );
    if(!$res)
      response::json(400, 'Error in enreg');

    $tpl = \file_get_contents($_ENV['MAIL_TPL'] . '/adm_new_mail.tpl');
    $tpl = \str_replace('##givenname##', $user->givenname, $tpl);
    $msg = \str_replace(PHP_EOL, '<br>', $request['contact']);
    $tpl = \str_replace('##msg##', $msg, $tpl);

    $admin_list = \yaml_parse_file($_ENV['ADMIN_YAML'])['adminList'];
    foreach($admin_list as $adm => $v){
      $docAdmin = $this->dbRes['class']::getOne(
        col: "contact",
        param: ['mail' => $adm, 'deleted' => false]
      );
      $mail = new uMail();
      $mail->send(
        subject: "Administration - Un nouveau message de contact vous est adressé",
        body: $tpl,
        toMail: $docAdmin->mail,
        toName: $docAdmin->givenname
      );
    }
    response::json(204, '');
  }
}
