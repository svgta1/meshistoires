<?php
namespace Meshistoires\Api\controller\admv1r0;
use Meshistoires\Api\utils\trace;
use Meshistoires\Api\utils\response;
use Meshistoires\Api\utils\request;
use Meshistoires\Api\utils\auth as utilsAuth;
use Meshistoires\Api\utils\mail as utilsMail;
use Meshistoires\Api\utils\seo;
use Meshistoires\Api\model\mail as mMail;
use Meshistoires\Api\backend\db;
use Svgta\Lib\Utils;

class mail
{
    static $listUser = [];
    public function __construct(?array $scopes, array $request)
    {
        $this->scopes = $scopes;
        $this->request = $request;
        utilsAuth::verifyScope($scopes);
        $this->dbRes = db::get_res();
        $this->mail = new utilsMail();
    }
    public function post()
    {
        $request = $this->request;
        request::validate_comment($request['msg']);
        request::validate_uuid($request['uuid']);
        $newMsg = new mMail();
        $newMsg->newDate();
        $newMsg->genUuid();

        $newMsg->type = 'response';
        $newMsg->userUuid = $_SESSION['ui']['uuid'];
        $newMsg->msg = $request['msg'];
        $newMsg->responseTo = $request['uuid'];

        $doc = $this->dbRes['class']::getOne(
            col: 'mail',
            param: ['uuid' => $request['uuid']]
        );
        if(is_null($doc))
            response::json(400, 'bad mail source');
        $user = $this->dbRes['class']::getOne(
            col: 'contact',
            param: ['uuid' => $doc->userUuid]
        );
        if(is_null($user))
            response::json(400, 'Contact not found');

        $res = $this->dbRes['class']::post(
            col: 'mail',
            param: $newMsg->_toArray()
        );
        if($res != 1)
            response::json(400, 'Error in enreg');
        $this->dbRes['class']::put(
            col: 'mail',
            uuid: $doc->uuid,
            param: ['hasResponse' => true]
        );
        
        $tpl = \file_get_contents($_ENV['MAIL_TPL'] . '/new_mail.tpl');
        $tpl = \str_replace('##domain##', $_ENV['DOMAIN'], $tpl);
        $this->mail->send(
            subject: "Réponse à votre message",
            body: $tpl,
            toMail: $user->mail,
            toName: $user->givenname
        );
        response::json(200, $newMsg->_toArray());

    }
    public function getList()
    {
        $cursor = $this->dbRes['class']::get(
            col: 'mail',
            param: ['type' => 'contact'],
            order: ['createTs' => -1],
            limit: 500
        );
        $list = [];
        $sort = [];
        foreach($cursor as $doc){
            $user = $this->getUser($doc->userUuid);
            if(!isset($list[$user->uuid])){
                $sort[] = $user->uuid;
                $u = [
                    'uuid' => $user->uuid,
                    'name' => $user->givenname,
                    'mail' => $user->mail,
                ];
                $list[$user->uuid] = [
                    'user' => $u,
                    'msg' => [],
                ];
            }
            $msg = [
                'uuid' => $doc->uuid,
                'createTs' => $doc->createTs,
                'msg' => $doc->msg,
                'hasResponse' => $doc->hasResponse,
                'responses' => $this->getResponse($doc)
            ];                
            $list[$user->uuid]['msg'][] = $msg;
        }

        $res = [
            'sort' => $sort,
            'list' => $list,
        ];
    
        response::json(200, $res);
    }
    private function getResponse($doc): array
    {
        if(!($doc->hasResponse))
            return [];
        $cursor = $this->dbRes['class']::get(
            col: 'mail',
            param: ['type' => 'response', 'responseTo' => $doc->uuid],
            order: ['createTs' => -1]
        );
        $ret = [];
        foreach($cursor as $doc){
            $user = $this->getUser($doc->userUuid);
            $ar = [
                'createTs' => $doc->createTs,
                'msg' => $doc->msg,
                'user' => [
                    'mail' => $user->mail,
                    'name' => $user->givenname,
                    'uuid' => $user->uuid
                ]
            ];
            $ret[] = $ar;
        }
        return $ret;
    }
    private function getUser($uuid)
    {
        if(!isset(self::$listUser[$uuid])){
        $doc = $this->dbRes['class']::getOne(
            col: 'contact',
            param: ['uuid' => $uuid],
        );
        self::$listUser[$uuid] = $doc;
        }
        return self::$listUser[$uuid];
    }
}