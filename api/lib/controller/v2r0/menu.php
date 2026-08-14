<?php
namespace Meshistoires\Api\controller\v2r0;
use Meshistoires\Api\utils\trace;
use Meshistoires\Api\utils\response;
use Meshistoires\Api\utils\request;
use Meshistoires\Api\utils\seo;
use Meshistoires\Api\utils\cache;
use Meshistoires\Api\utils\opt;
use Meshistoires\Api\backend\db;
use Meshistoires\Api\backend\stockage;
use Meshistoires\Api\utils\utilsMenu;

class menu
{
  private $dbRes = null;
  private $dbStockage = null;
  private $className = null;
  private $scopes = null;
  private $request = [];
  private static $cache = false;
  private static $cacheId = "Cache_Menus_";
  private static $menuL = [
    'Accueil' => 'accueil',
    'Collections' => 'collections',
    'Histoires' => 'histoires',
    'Images' => 'images'
  ];
  private static $menuLVisibility = [
    'accueil' => true,
    'collections' => true,
    'histoires' => true,
    'images' => false
  ];
  private $method = [
    'accueil' => 'getAccueil',
    'collections' => 'getCollections',
    'histoires' => 'getHistoires',
    'images' => 'getImages',
  ];

  private $modelRetMenu =[
    'contents' => null,
    'template' => null,
    'ariane' => null,
    'menuLi' => null,
    'isMenu' => true,
    'title' => null,
  ];

  public function __construct(?array $scopes, array $request)
  {
    $this->scopes = $scopes;
    $this->request = $request;
    $c = \explode('\\', __CLASS__);
    $this->className = \array_pop($c);
    $this->dbRes = db::get_res();
    $this->dbStockage = stockage::get_res();
  }
  private function is_valid_token()
  {
    if(!isset($this->request['token']))
      return false;
    $admin = opt::yaml_parse_file($_ENV['ADMIN_YAML']);
    if(!isset($admin['tokenList'][$this->request['token']]))
      return false;
    return true;
  }
  Public function deleteImage()
  {
    if(!$this->is_valid_token())
      response::json(403, 'Bad token or token not found');
    if(!isset($this->request['uuid']))
      response::json(400, 'uuid not given');
    $col = utilsMenu::searcheImageCol($this->request['uuid']);
    if(is_null($col))
      response::json(400, 'Image non trouvée');
    $this->dbRes['class']::put(
      col: $col,
      uuid: $this->request['uuid'],
      param: ["deleted" => true, "dateUpdate" => time()]
    );
    response::json(204, '');
  }
  public function restaureImage()
  {
    if(!$this->is_valid_token())
      response::json(403, 'Bad token or token not found');
    if(!isset($this->request['uuid']))
      response::json(400, 'uuid not given');
    $col = utilsMenu::searcheImageCol($this->request['uuid'], true);
    if(is_null($col))
      response::json(400, 'Image non trouvée');
    $this->dbRes['class']::put(
      col: $col,
      uuid: $this->request['uuid'],
      param: ['deleted' => false, "dateUpdate" => time()]
    );
    response::json(204, '');
  }
  public function delImageDef()
  {
    if(!$this->is_valid_token())
      response::json(403, 'Bad token or token not found');
    if(!isset($this->request['uuid']))
      response::json(400, 'uuid not given');
    $col = utilsMenu::searcheImageCol($this->request['uuid'], true);
    if(is_null($col))
      response::json(400, 'Image non trouvée');
    $this->dbRes['class']::delete(
      col: $col,
      uuid: $this->request['uuid']
    );
    $this->dbStockage['class']::delete($this->request['uuid']);
    response::json(204, '');
  }
  public function getImagesParams()
  {
    if(!$this->is_valid_token())
      response::json(403, 'Bad token or token not found');
    $imgList = [];
    $imgUuid = [];
    $cursor = utilsMenu::getImagesStatCursor();
    foreach($cursor as $doc){
      if(!isset($imgList[$doc->from]))
        $imgList[$doc->from] = [];
      if(!in_array($doc->uuid, $imgUuid)){
        $imgUuid[] = $doc->uuid;
        $imgList[$doc->from][] = [
          'uuid' => $doc->uuid,
          'nbrAff' => $doc->nbrAccess
        ];  
      }
      if(count($imgList[$doc->from]) > 1)
      usort($imgList[$doc->from], function($a, $b){
        if($a['nbrAff'] > $b['nbrAff']){
          return -1;
        }elseif($a['nbrAff'] < $b['nbrAff']){
          return 1;
        }else{
          return 0;
        }
      });
    }
    $tpl = file_get_contents($_ENV['HTML_TPL'] . '/accueilImages.tpl');
    $tplUl = file_get_contents($_ENV['HTML_TPL'] . '/accueilImages_ul.tpl');
    $tplLi = file_get_contents($_ENV['HTML_TPL'] . '/accueilImages_li.tpl');
    $d = file_get_contents($_ENV['HTML_TPL'] . '/image_delete.tpl');
    $html = '';
    $nbrAff = 0;
    $nbrImages = 0;
    $nbr = [
      'global' => [
        'images' => 0,
        'aff' => 0
      ]
    ];
    foreach($imgList as $name => $imgs){
      $ul = str_replace('##name##', $name, $tplUl);
      $ulHtml = "";
      $nbr[$name] = [
        'images' => 0,
        'aff' => 0
      ];
      foreach($imgs as $img){
        $li = str_replace('##delete##', $d, $tplLi);
        $li = str_replace('##histoireImageId##', $img['uuid'], $li);
        $li = str_replace('##nbr##', $img['nbrAff'], $li);
        $nbr['global']['aff'] += $img['nbrAff'];
        $nbr['global']['images'] += 1;
        $nbr[$name]['aff'] += $img['nbrAff'];
        $nbr[$name]['images'] += 1;
        $ulHtml .= $li;
      }
      $ul = str_replace('##contents##', $ulHtml, $ul);
      $ul = str_replace('##nbrImages##', $nbr[$name]['images'], $ul);
      $ul = str_replace('##nbrAff##', $nbr[$name]['aff'], $ul);
      $html .= $ul;
    }
    $imgDel = utilsMenu::getImageMenuDel();
    $tplDel = file_get_contents($_ENV['HTML_TPL'] . '/accueilImagesDel.tpl');
    $tplLiDel = file_get_contents($_ENV['HTML_TPL'] . '/accueilImagesDel_li.tpl');
    $dr = file_get_contents($_ENV['HTML_TPL'] . '/image_deleteRestore.tpl');
    $ulHtml = "";
    $nbr['del'] = [
      'images' => 0,
      'aff' => 0
    ];
    foreach($imgDel as $img){
      $li = str_replace('##delete##', $dr, $tplLiDel);
      $li = str_replace('##histoireImageId##', $img->uuid, $li);
      $li = str_replace('##nbr##', $img->nbrAccess, $li);
      $li = str_replace('##from##', $img->from, $li);
      $nbr["del"]['aff'] += $img->nbrAccess;
      $nbr["del"]['images'] += 1;
      $ulHtml .= $li;
    }
    $tplDel = str_replace('##contents##', $ulHtml, $tplDel);
    $tplDel = str_replace('##nbrImages##', $nbr["del"]['images'], $tplDel);
    $tplDel = str_replace('##nbrAff##', $nbr["del"]['aff'], $tplDel);
    $html .= $tplDel;

    $tpl = str_replace("##content##", $html, $tpl);
    $tpl = str_replace("##nbrImages##", $nbr['global']['images'], $tpl);
    $tpl = str_replace("##nbrAff##", $nbr['global']['aff'], $tpl);
    $ariane = [
      [
      'name' => 'Accueil',
      'uri' => '/accueil',
      ],
      [
        'name' => "Images Pages Accueils",
        'uri' => '/accueil/images',
      ]
    ];
    $ret = [
      'ariane' => utilsMenu::ariane($ariane),
      'isMenu' => false,
      'menuLi' => 'accueil',
      'contents' => [],
      'title' => 'images',
      'template' => $tpl
    ];
    response::json(200, $ret);
  }
  public function error403()
  {
    $data = utilsMenu::errorPage('error403', 'Erreur 403');
    response::json(200, $data);
  }
  public function error404()
  {
    $data = utilsMenu::errorPage('error404', 'Erreur 404');
    response::json(200, $data);
  }
  public function getCategorieInfo()
  {
    $ret = $this->modelRetMenu;
    if(request::validate_uuid($this->request['uuid'])){
      $uuid = $this->request['uuid'];
    }else{
      $list = utilsMenu::getSeoCategories();
      if(!isset($list[$this->request['uuid']]))
        response::json(404, 'Catégorie non trouvée');
      $uuid = $list[$this->request['uuid']];
    }
    $data = utilsMenu::getCategorieData($uuid);
    if(is_null($data))
      response::json(404, 'Catégorie non trouvée');
    $ret['ariane'] = utilsMenu::ariane($data['ariane']);
    $ret['menuLi'] = 'histoires';
    $ret['isMenu'] = false;
    $ret['title'] = $data['doc']->name;
    $ret['histoires'] = $data['histoires'];
    $ret['contents'] = [
      'desc' => 'Histoires de la catégorie ' . $data['doc']->name,
    ];
    $tpl = file_get_contents($_ENV['HTML_TPL'] . '/categorie_info.tpl');
    $tpl = str_replace("##nbrHist##", $data['histoires']['nbr'], $tpl);
    $tpl = str_replace("##catName##", $data['doc']->name, $tpl);
    $html = "";
    foreach($data['histoires']['list'] as $uuid){
      $html .= '<li property="itemListElement" typeod="ListItem" id="histoire_'.$uuid.'"></li>';
    }
    $ret['template'] = str_replace("##content##", $html, $tpl);
    response::json(200, $ret);
  }
  public function getHistoireInfo()
  {
    $ret = $this->modelRetMenu;
    if(request::validate_uuid($this->request['uuid'])){
      $uuid = $this->request['uuid'];
    }else{
      $list = utilsMenu::getSeoHistoires();
      if(!isset($list[$this->request['uuid']]))
        response::json(404, 'Histoire non trouvée 1');
      $uuid = $list[$this->request['uuid']];
    }
    $data = utilsMenu::getHistoireData($uuid);
    if(is_null($data))
      response::json(404, 'Histoire non trouvée 2 ' . $uuid);
    $ret['ariane'] = utilsMenu::ariane($data['ariane']);
    $ret['menuLi'] = 'histoires';
    $ret['isMenu'] = false;
    $doc = $data['doc'];
    $ret['title'] = $doc->title;
    $tpl = file_get_contents($_ENV['HTML_TPL'] . '/histoire.tpl');
    $tpl = str_replace('##title##', $doc->title, $tpl);
    $tpl = str_replace('##imageId##', $doc->imageUuid, $tpl);
    $desc = '<p>' . $doc->desc . '</p>';
    $tpl = str_replace('##desc##', str_replace(PHP_EOL, "</p><p>", $desc), $tpl);
    $tpl = str_replace("##distantLink##", $doc->distanteLink, $tpl);
    $tpl = str_replace("##collectionUri##", $data['collection']['ariane'][1]['uri'], $tpl);
    $tpl = str_replace("##collectionName##", $data['collection']['doc']->name, $tpl);
    $tpl = str_replace("##categories##", utilsMenu::setCategorieAff($data), $tpl);
    $random = $this->dbRes['res']->oeuvres->aggregate([
      ['$match' => [
        'collectionUuid' => $doc->collectionUuid,
        'uuid' => ['$ne' => $doc->uuid]
      ]],
      ['$sample' => ["size" => (int)$_ENV['AC_HIST_LIMIT']]],
      ['$project' => ["uuid" => 1]]
    ]);
    $tplLi = file_get_contents($_ENV['HTML_TPL'] . '/histoire_li.tpl');
    $html = '';
    foreach($random as $rand){
      $_data = utilsMenu::getHistoireData($rand->uuid);
      $li = str_replace("##histUri##", $_data['ariane'][1]['uri'], $tplLi);
      $li = str_replace("##histTitle##", $_data['doc']->title, $li);
      $li = str_replace("##histoireImageId##", $_data['doc']->imageUuid, $li);
      $li = str_replace("##distantLink##", $_data['doc']->distanteLink, $li);
      $html .= $li;
    }
    $altImg = utilsMenu::getAltImg($doc->uuid, $this->is_valid_token());
    $htmlAlt = "";
    if(!is_null($altImg)){
      $htmlAlt .= $altImg;
      $htmlAlt = str_replace("##hidden##", "", $htmlAlt);
    }else{
      $htmlAlt = str_replace("##hidden##", "hidden", $htmlAlt);
    }
    $tpl = str_replace("##content##", $html, $tpl);
    $tpl = str_replace("##contentsAltImg##", $htmlAlt, $tpl);
    $ret['contents'] = [
      "imageUuid" => $doc->imageUuid,
      "desc" => $doc->desc
    ];
    $ret['template'] = $tpl;
    $ret['data'] = $data;
    response::json(200, $ret);
  }
  public function getCollectionHistoire()
  {
    $data = utilsMenu::getHistoireData($this->request['uuid']);
    if(is_null($data))
      response::json(404, 'Histoire de collection non trouvée');
    $li = file_get_contents($_ENV['HTML_TPL'] . '/collection_li.tpl');
    $histoire = $data['doc'];
    $li = str_replace("##histoireImageId##", $histoire->imageUuid, $li);
    $li = str_replace("##histUri##", $data['ariane'][1]['uri'], $li);
    $li = str_replace("##docTitle##", $histoire->title, $li);
    $desc = '<p>' . $histoire->desc . '</p>';
    $li = str_replace("##docDesc##", str_replace(PHP_EOL, "</p><p>", $desc), $li);
    $li = str_replace("##distantLink##", $histoire->distanteLink, $li);
    $li = str_replace("##categories##", utilsMenu::setCategorieAff($data), $li);
    $cptImg = utilsMenu::getAltImgDataCpt($this->request['uuid']);
    if($cptImg == 0){
      $li = str_replace('##hiden##', 'hidden', $li);
    }else{
      $li = str_replace('##hiden##', '', $li);
      if($cptImg == 1){
        $li = str_replace('##cptImg##', "Découvrir l'histoire et son illustration.", $li);
      }else{
        $li = str_replace('##cptImg##', "Découvrir l'histoire et ses $cptImg illustrations.", $li);
      }
    }
    $ret = [
      'html' => $li
    ];
    response::json(200, $ret);
  }
  public function getCollectionInfo()
  {
    $ret = [
      'contents' => [],
      'template' => null,
      'histoires' => []
    ];
    $col = "collections";
    if(request::validate_uuid($this->request['uuid'])){
      $uuid = $this->request['uuid'];
    }else{
      $list = utilsMenu::getSeoCollections();
      if(!isset($list[$this->request['uuid']]))
        response::json(404, 'Collection non trouvée');
      $uuid = $list[$this->request['uuid']];
    }
    $dataCol = utilsMenu::getCollectionData($uuid);
    if(is_null($dataCol))
      response::json(404, 'Collection non trouvée');
    $ret['histoires'] = $dataCol['histoires'];
    $html = '';
    foreach($ret['histoires']['list'] as $uuid){
      $html .= '<li property="itemListElement" typeod="ListItem" id="histoire_'.$uuid.'"></li>';
    }
    $tpl = file_get_contents($_ENV['HTML_TPL'] . '/collection.tpl');
    $tpl = str_replace("##colName##", $dataCol['doc']->name, $tpl);
    $tpl = str_replace("##imageId##", $dataCol['doc']->imageUuid, $tpl);
    $tpl = str_replace("##content##", $html, $tpl);
    $desc = '<p>' . $dataCol['doc']->desc . '</p>';
    $tpl = str_replace("##colDesc##", str_replace(PHP_EOL, "</p><p>", $desc), $tpl);
    $ret['template'] = $tpl;
    $ret['ariane'] = utilsMenu::ariane($dataCol['ariane']);
    $ret['menuLi'] = 'collections';
    $ret['isMenu'] = false;
    $ret['title'] = 'Collection ' . $dataCol['doc']->name;
    $ret['contents']=[
      'imageUuid' => $dataCol['doc']->imageUuid,
      'desc' => $dataCol['doc']->desc
    ];
    response::json(200, $ret);
  }
  private function setCollectionsTpl($contents)
  {
    $html = '';
    $tplLi = file_get_contents($_ENV['HTML_TPL'] . '/collections_li.tpl');
    foreach($contents as $data){
      $doc = $data['doc'];
      $li = $tplLi;
      $li = str_replace("##imageId##", $doc->imageUuid, $tplLi);
      $li = str_replace("##collectionName##", $doc->name, $li);
      $desc = '<p>' . $doc->desc . '</p>';
      $li = str_replace("##collectionDesc##", str_replace(PHP_EOL, "</p><p>", $desc), $li);
      $li = str_replace("##histCount##", $data['cptHist'], $li);
      $li = str_replace("##collectionId##", $doc->uuid, $li);
      $li = str_replace("##collectionUri##", $data['ariane'][1]['uri'], $li);
      $li = str_replace("##distantLink##", $doc->distanteLink, $li);
      $html .= $li;
    }
    $tpl = file_get_contents($_ENV['HTML_TPL'] . '/collections.tpl');
    $txt = file_get_contents($_ENV['HTML_TPL'] . '/collections.txt');
    $text = '<p>' . str_replace(PHP_EOL, '</p><p>', $txt) . '</p>';
    $tpl = str_replace('##text##', $text, $tpl);
    return str_replace('##content##', $html, $tpl);
  }
  private function getCollections(&$ret)
  {
    $col = "collections";
    $colE = "oeuvres";
    $cursor = $this->dbRes['class']::get(
      col: $col,
      order: ['name' => 1],
      projection: ['uuid']
    );
    $ret['contents'] = [];
    foreach($cursor as $c){
      $data = utilsMenu::getCollectionData($c->uuid);
      $ret['contents'][] = $data;
    }
    $ret['template'] = $this->setCollectionsTpl($ret['contents']);
  }
  public function getHistoireFromHistoires()
  {
    if(request::validate_uuid($this->request['uuid'])){
      $uuid = $this->request['uuid'];
    }else{
      $list = utilsMenu::getSeoHistoires();
      if(!isset($list[$this->request['uuid']]))
        response::json(404, 'Histoire des histoires non trouvée');
      $uuid = $list[$this->request['uuid']];
    }
    $data = utilsMenu::getHistoireData($uuid);
    if(is_null($data))
      response::json(404, 'Histoire des histoires non trouvée');
    $li = file_get_contents($_ENV['HTML_TPL'] . '/histoires_li.tpl');
    $histoire = $data['doc'];
    $collection = $data['collection'];
    $li = str_replace("##histoireImageId##", $histoire->imageUuid, $li);
    $li = str_replace("##docTitle##", $histoire->title, $li);
    $desc = '<p>' . $histoire->desc . '</p>';
    $li = str_replace("##histUri##", $data['ariane'][1]['uri'], $li);
    $li = str_replace("##distantLink##", $histoire->distanteLink, $li);
    $li = str_replace("##categories##", utilsMenu::setCategorieAff($data), $li);
    $li = str_replace("##CollectionUri##", $collection['ariane'][1]['uri'], $li);
    $li = str_replace("##collectionName##", $collection['doc']->name, $li);
    $ret = [
      'html' => $li
    ];
    response::json(200, $ret);
  }
  private function getHistoires(&$ret)
  {
    $order = 'dateCreate';
    //$this->request['order']
    $ret = $this->modelRetMenu;
    $col = 'oeuvres';
    $ret['histoires'] = [
      'nbr' =>$cursor = $this->dbRes['class']::count(
          col: $col
        ),
      'list' => []
    ];
    $cursor = $this->dbRes['class']::get(
      col: $col,
      order: [$order => -1],
      projection: ['uuid']
    );
    $html = '';
    foreach($cursor as $c){
      $ret['histoires']['list'][] = $c->uuid;
      $html .= '<li property="itemListElement" typeod="ListItem" id="histoire_'.$c->uuid.'"></li>';
    }
    $ret['menuLi'] = "histoires";
    $ret['title'] = "Mes histoires";
    $tpl = file_get_contents($_ENV['HTML_TPL'] . '/histoires.tpl');
    $txt = file_get_contents($_ENV['HTML_TPL'] . '/histoires.txt');
    $text = '<p>' . str_replace(PHP_EOL, '</p><p>', $txt) . '</p>';
    $tpl = str_replace('##text##', $text, $tpl);
    $tpl = str_replace("##nbrHist##", $ret['histoires']['nbr'], $tpl);
    $cursorCat = $this->dbRes['class']::get(
      col: 'categories',
      order: ['name' => 1],
      projection: ['uuid']
    );
    $data = [
      'categories' => []
    ];
    foreach($cursorCat as $c){
      $cat = utilsMenu::getCategorieData($c->uuid);
      if($cat['histoires']['nbr'] > 0)
        $data['categories'][] = $cat;
    }
    $tpl = str_replace("##catList##", utilsMenu::setCategorieAff($data), $tpl);
    $ret['template'] = str_replace("##content##", $html, $tpl);
    $ariane = [
      [
        'name' => 'Histoires',
        'uri' => '/histoire',
      ]
    ];
    $ret['ariane'] = utilsMenu::ariane($ariane);
    $ret['contents'] = [
      'desc' => 'Liste globale de mes histoires'
    ];
  }
  private function getImages(&$ret)
  {
    if(!$this->is_valid_token())
      response::json(403, 'Bad token or token not found');
    $col = "thumb300.files";
    $cursor = $this->dbRes['class']::get(
      col: $col,
      projection: ['filename', 'metadata.width', 'metadata.height']
    );

    $liAr = [];
    foreach($cursor as $doc){
      $info = utilsMenu::getImageFrom($doc->filename);
      if(!isset($liAr[$info['from']]))
        $liAr[$info['from']] = [];
      $liAr[$info['from']][] = [
        'status' => $info['status'],
        'doc' => $doc,
        'info' => $info
      ];
    }
    ksort($liAr);
    $ret['contents'] = [];
    $tplUl = file_get_contents($_ENV['HTML_TPL'] . '/images_ul.tpl');
    $tplLi = file_get_contents($_ENV['HTML_TPL'] . '/images_li.tpl');
    $d = file_get_contents($_ENV['HTML_TPL'] . '/image_delete.tpl');
    $dr = file_get_contents($_ENV['HTML_TPL'] . '/image_deleteRestore.tpl');
    $html = '';
    foreach($liAr as $f => $from){
      usort($from, function($a, $b){
        if($a['status'] > $b['status']){
          return -1;
        }elseif($a['status'] < $b['status']){
          return 1;
        }else{
          return 0;
        }
      });
      $ul = str_replace('##from##', $f, $tplUl);
      $htmlUl = '';
      $nbr = 0;
      foreach($from as $ar){
        $nbr += 1;
        $doc = $ar['doc'];
        $info = $ar['info'];
        $li = str_replace("##ImageId##", $doc->filename, $tplLi);
        $width = $doc->metadata->width ?? '';
        $height = $doc->metadata->height ?? '';
        if($info['statusCode'] == 0){
          $li = str_replace('##deleteImg##', $dr, $li);
          $li = str_replace('##red##', 'red', $li);
        }elseif($info['statusCode'] == 2){
          $li = str_replace('##deleteImg##', "", $li);
          $li = str_replace('##red##', 'blue', $li);
        }else{
          $li = str_replace('##deleteImg##', $d, $li);
          $li = str_replace('##red##', '', $li);
        }
        $li = str_replace("##width##", $width, $li);
        $li = str_replace("##width##", $width, $li);
        $li = str_replace('##status##', $info['status'], $li);
        $li = str_replace("##histoireImageId##", $doc->filename, $li);
        $htmlUl .= $li;
      }
      $ul = str_replace('##contents##', $htmlUl, $ul);
      $ul = str_replace('##nbr##', $nbr, $ul);
      $html .= $ul;
    }
    //$ret['contents'] = $liAr;
    $tpl = file_get_contents($_ENV['HTML_TPL'] . '/images.tpl');

    $tpl = str_replace('##content##', $html, $tpl);
    $ret['template'] = $tpl;
  }
  private function getAccueil(&$ret)
  {
    $col = "oeuvres";
    $cursor = $this->dbRes['class']::get(
      col: $col,
      order: ['dateCreate' => -1],
      limit: $_ENV['AC_HIST_LIMIT'],
      projection: ['uuid']
    );
    $ret['contents'] = [];
    $tplLi = file_get_contents($_ENV['HTML_TPL'] . '/accueil_li.tpl');
    $html = '';
    foreach($cursor as $c){
      $data= utilsMenu::getHistoireData($c->uuid);
      $doc = $data['doc'];
      $collection = $data['collection']['doc'];
      $li = str_replace("##histoireTitle##", $doc->title, $tplLi);
      $li = str_replace("##histoireUri##", $data['ariane'][1]['uri'], $li);
      $li = str_replace('##histoireImageId##', $doc->imageUuid, $li);
      $li = str_replace('##distantLink##', $doc->distanteLink, $li);
      $li = str_replace("##collectionName##", $data['collection']['doc']->name, $li);
      $li = str_replace("##collectionUri##", $data['collection']['ariane'][1]['uri'], $li);
      $li = str_replace('##categories##', utilsMenu::setCategorieAff($data), $li);
      $html .= $li;
      $doc = utilsMenu::_unset($doc);
      $ret['contents'][] = $data;
    }
    $tpl = file_get_contents($_ENV['HTML_TPL'] . '/accueil.tpl');
    $txt = file_get_contents($_ENV['HTML_TPL'] . '/accueil.txt');
    $text = '<p>' . str_replace(PHP_EOL, '</p><p>', $txt) . '</p>';
    $tpl = str_replace('##text##', $text, $tpl);

    $res = utilsMenu::getImagesStatsInfo();
    if($res['nbr'] > 0){
      $l = [];
      foreach($res['cursor'] as $d){
        if(!isset($l[$d->nbrAccess]))
          $l[$d->nbrAccess] = [];
        $l[$d->nbrAccess][] = $d->uuid;
      }
      ksort($l);
      $k = array_key_first($l);
      $rand = random_int(0, count($l[$k]) - 1);
      $tpl = str_replace('##imageId##', $l[$k][$rand], $tpl);
      utilsMenu::setImageStatAccess($l[$k][$rand]);
    }else{
      $tpl = str_replace('##class##', "hidden", $tpl);
    }
    $tpl = str_replace('##imageId##', $l[$k][$rand], $tpl);
    $tpl = str_replace('##content##', $html, $tpl);
    $ret['template'] = $tpl;
  }
  public function get()
  {
    if(!in_array($this->request['uuid'], self::$menuL))
      response::json(404, 'Menu non trouvé');
    $method = $this->method[$this->request['uuid']];
    $sha1 = sha1($method);
    if(!is_null($method)){
      $ret = $this->modelRetMenu;
      $ariane = [
        [
          'name' => array_search($this->request['uuid'], self::$menuL),
          'uri' => $this->request['uuid']
        ]
      ];
      $ret['ariane'] = utilsMenu::ariane($ariane);
      $ret['menuLi'] = $this->request['uuid'];
      $ret['title'] = array_search($this->request['uuid'], self::$menuL);
      $this->$method($ret);
    }
    response::json(200, $ret);
  }
  public function list()
  {
    $ret = self::_menuList();
    response::json(200, $ret);
  }
  public static function _menuList()
  {
    $res = [
      'metadata' => [
        'count' => 0,
        'hash' => null,
      ],
      'list' => [],
      'template' => null,
    ];
    $html = "";
    $tplLi = file_get_contents($_ENV['HTML_TPL'] . '/menu_li.tpl');
    if(isset($_SERVER['HTTP_REFERER']))
      $ref = str_replace($_SERVER["REQUEST_SCHEME"] .'://' . $_ENV['DOMAIN'] . '/', '', $_SERVER['HTTP_REFERER']);
    else
      $ref = "";
    foreach(self::$menuL as $k => $menu){
      $ar = [
        'type' => 'menu',
        'uuid' => $menu,
        'name' => $k,
        'update' => time(),
        'parent' => null,
        'position' => $k,
        'subMenu' => []
      ];
      $ar['uri'] = seo::seofy($menu);
      $res['list'][$menu] = $ar;
      if(self::$menuLVisibility[$menu]){
        $li = str_replace("##href##", $ar['uri'], $tplLi);
        $li = str_replace("##name##", $ar["name"], $li);
        if($ar["uri"] == explode('/', $ref)[0]){
          $li = str_replace("##class##", 'class="highLight"', $li);
        }else{
          $li = str_replace("##class##", '', $li);
        }
        $html .= $li;
      }
    };
    $res['metadata']['count'] = \count($res['list']);
    $res['metadata']['hash'] = \hash('sha256', json_encode($res['list']));
    $tpl = file_get_contents($_ENV['HTML_TPL'] . '/menu.tpl');
    $res['template'] = str_replace("##menuList##", $html, $tpl);
    return $res;
  }
}
