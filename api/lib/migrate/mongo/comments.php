<?php
namespace Meshistoires\Api\migrate\mongo;
use Meshistoires\Api\backend\db;
use Meshistoires\Api\model\comment as mComment;
use Meshistoires\Api\model\contact as mContact;
use Svgta\Lib\Utils;

class comments extends abstractMig
{
  public function __construct()
  {
    $this->dbRes = db::get_res();
    $this->col = 'comment';
    $this->model = new mComment();
  }
  public function doMigrate($doc)
  {
    if($doc->dateCreate == 0)
      $doc->dateCreate = $this->getFromId($doc->_id);
    if($doc->dateUpdate == 0)
      $doc->dateUpdate = $this->getFromId($doc->_id);
    if(is_null($doc->userUuid)){
      $userDoc = $this->dbRes['class']::getOne(
        col: 'contact',
        param: ['mail' => $doc->mail],
        projection: ['uuid']
      );
      if(is_null($userDoc)){
        $contactUuid = Utils::genUUID();
        $contact = new mContact();
        $contact->uuid = $contactUuid;
        $contact->sn = $doc->sn;
        $contact->givenname = $doc->givenName;
        $contact->mail = $doc->mail;
        $contact->dateCreate = $this->getFromId($doc->_id);
        $contact->dateUpdate = $this->getFromId($doc->_id);
        $this->dbRes['class']::post(
          col: 'contact',
          param: json_decode(json_encode($contact), true)
        );
        $doc->userUuid = $contactUuid;
      }
    }
    $ar = $this->docToArray($doc);
    unset($ar['_id']);
    unset($ar['uuid']);
    $cursor = $this->dbRes['class']::put(
      col: $this->col,
      uuid: $doc->uuid,
      param: $ar
    );
  }
}
