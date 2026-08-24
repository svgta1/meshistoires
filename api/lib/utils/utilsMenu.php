<?php
namespace Meshistoires\Api\utils;
use Meshistoires\Api\utils\cache;
use Meshistoires\Api\backend\db;
use Meshistoires\Api\model\siteParamsStats;
use Meshistoires\Api\utils\seo;
use Meshistoires\Api\utils\opt;
use Meshistoires\Api\controller\v2r0\menu;

class utilsMenu
{
  private static $cache = false;
  private static $cacheId = "Cache_Menus_";
  private static $internalCache = [];
  private static $dbRes = null;
  private static $getImageFromCache = [];
  private static $getImageFromAltImages = null;
  private static $getImageFromSiteParamsStats = null;
  private static $getImageFromCollections = null;
  private static $getImageFromOeuvres = null;

  public static function getCursorHistoires()
  {
    if(is_null(self::$dbRes))
      self::$dbRes = db::get_res();

    $order = 'dateCreate';
    $col = 'oeuvres';
    $cursor = self::$dbRes['class']::get(
      col: $col,
      order: [$order => -1],
      projection: ['uuid', 'dateCreate', 'dateUpdate', 'title', 'desc', 'imageUuid']
    );
    return $cursor;
  }
  public static function getCursorCollections()
  {
    if(is_null(self::$dbRes))
      self::$dbRes = db::get_res();

    $col = "collections";
    $cursor = self::$dbRes['class']::get(
      col: $col,
      order: ['name' => 1],
      projection: ['uuid', 'dateUpdate', 'dateCreate', 'name']
    );
    return $cursor;
  }
  public static function getCursorLastHistoires()
  {
    if(is_null(self::$dbRes))
      self::$dbRes = db::get_res();

    $col = "oeuvres";
    $cursor = self::$dbRes['class']::get(
      col: $col,
      order: ['dateCreate' => -1],
      limit: $_ENV['AC_HIST_LIMIT'],
      projection: ['uuid', 'dateUpdate', 'dateCreate', 'title']
    );
    return $cursor;
  }
  public static function getGenre()
  {
    $cacheKey = 'getGenre';
    $cache = self::getCache($cacheKey);
    if($cache){
      return $cache;
    }
    if(is_null(self::$dbRes))
      self::$dbRes = db::get_res();
    $cursor = self::$dbRes['class']::get(
      col: 'categories',
      projection: ['name']
    );
    $ret = [];
    foreach($cursor as $doc){
      $ret[] = $doc->name;
    }
    sort($ret);
    self::setCache($cacheKey, $ret);
    return $ret;
  }
  public static function setImgsSrc($id)
  {
    $scheme = isset($_SERVER['REQUEST_SCHEME']) ? $_SERVER['REQUEST_SCHEME'] : 'https';
    $ar = [
      'img' => $scheme . '://' . $_ENV['DOMAIN'] . $_ENV['BASE_PATH'] . '/' . $_ENV['VERSION_CTRL'] . '/image/' . $id,
      'thumb300' => $scheme . '://' . $_ENV['DOMAIN'] . $_ENV['BASE_PATH'] . '/' . $_ENV['VERSION_CTRL'] . '/imageThumb300/' . $id,
      'thumb' => $scheme . '://' . $_ENV['DOMAIN'] . $_ENV['BASE_PATH'] . '/' . $_ENV['VERSION_CTRL'] . '/imageThumb/' . $id
    ];
    return $ar;
  }
  public static function setImgSrc($id)
  {
    $a = self::setImgsSrc($id);
    return $a['thumb300'];
  }
  public static function getCollectionHtml(&$data)
  {
    $html = '';
    foreach($data['histoires']['list'] as $uuid){
      $h = self::getHistoireData($uuid);
      if($data['doc']->dateUpdate < $h['doc']->dateUpdate)
        $data['doc']->dateUpdate = $h['doc']->dateUpdate;
      $html .= '<li property="itemListElement" typeof="ListItem" id="histoire_'.$uuid.'">' . self::getCollectionHistoireHtml($h, $uuid). '</li>';
    }
    $tpl = opt::file_get_contents($_ENV['HTML_TPL'] . '/collection.tpl');
    $tpl = str_replace("##colName##", $data['doc']->name, $tpl);
    $tpl = str_replace("##imageId##", $data['doc']->imageUuid, $tpl);
    $tpl = str_replace('##imageSrc##', self::setImgSrc($data['doc']->imageUuid), $tpl);
    $tpl = str_replace("##content##", $html, $tpl);
    $desc = '<p>' . $data['doc']->desc . '</p>';
    $tpl = str_replace("##colDesc##", str_replace(PHP_EOL, "</p><p>", $desc), $tpl);
    return $tpl;
  }
  public static function getCollectionHistoireHtml($data, $uuid)
  {
    $li = opt::file_get_contents($_ENV['HTML_TPL'] . '/collection_li.tpl');
    $histoire = $data['doc'];
    $li = str_replace("##histoireImageId##", $histoire->imageUuid, $li);
    $li = str_replace('##imageSrc##', self::setImgSrc($histoire->imageUuid), $li);
    $li = str_replace("##histUri##", $data['ariane'][1]['uri'], $li);
    $li = str_replace("##docTitle##", $histoire->title, $li);
    $desc = '<p>' . $histoire->desc . '</p>';
    $li = str_replace("##docDesc##", str_replace(PHP_EOL, "</p><p>", $desc), $li);
    $li = str_replace("##distantLink##", $histoire->distanteLink, $li);
    $li = str_replace("##categories##", utilsMenu::setCategorieAff($data), $li);
    $cptImg = utilsMenu::getAltImgDataCpt($uuid);
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
    return $li;
  }
  public static function getCategorieHtml($data)
  {
    $tpl = opt::file_get_contents($_ENV['HTML_TPL'] . '/categorie_info.tpl');
    $tpl = str_replace("##nbrHist##", $data['histoires']['nbr'], $tpl);
    $tpl = str_replace("##catName##", $data['doc']->name, $tpl);
    $html = "";
    foreach($data['histoires']['list'] as $uuid){
      $hdata = utilsMenu::getHistoireData($uuid);
      $h = utilsMenu::getHistoiresHistoireHtml($hdata);
      $html .= '<li property="itemListElement" typeof="ListItem" id="histoire_'.$uuid.'">'.$h.'</li>';
    }
    $tpl = str_replace("##content##", $html, $tpl);
    return $tpl;
  }
  Public static function getHistoiresHistoireHtml($data)
  {
    $li = opt::file_get_contents($_ENV['HTML_TPL'] . '/histoires_li.tpl');
    $histoire = $data['doc'];
    $collection = $data['collection'];
    $li = str_replace("##histoireImageId##", $histoire->imageUuid, $li);
    $li = str_replace('##imageSrc##', utilsMenu::setImgSrc($histoire->imageUuid), $li);
    $li = str_replace("##docTitle##", $histoire->title, $li);
    $desc = '<p>' . $histoire->desc . '</p>';
    $li = str_replace("##histUri##", $data['ariane'][1]['uri'], $li);
    $li = str_replace("##distantLink##", $histoire->distanteLink, $li);
    $li = str_replace("##categories##", utilsMenu::setCategorieAff($data), $li);
    $li = str_replace("##CollectionUri##", $collection['ariane'][1]['uri'], $li);
    $li = str_replace("##collectionName##", $collection['doc']->name, $li);
    return $li;
  }
  public static function getHistoireHtml($data, $token = null)
  {
    if(is_null(self::$dbRes))
      self::$dbRes = db::get_res();
    $doc = $data['doc'];

    $tpl = opt::file_get_contents($_ENV['HTML_TPL'] . '/histoire.tpl');
    $tpl = str_replace('##title##', $doc->title, $tpl);
    $tpl = str_replace('##imageId##', $doc->imageUuid, $tpl);
    $tpl = str_replace('##imageSrc##', self::setImgSrc($doc->imageUuid), $tpl);
    $desc = '<p>' . $doc->desc . '</p>';
    $tpl = str_replace('##desc##', str_replace(PHP_EOL, "</p><p>", $desc), $tpl);
    $tpl = str_replace("##distantLink##", $doc->distanteLink, $tpl);
    $tpl = str_replace("##collectionUri##", $data['collection']['ariane'][1]['uri'], $tpl);
    $tpl = str_replace("##collectionName##", $data['collection']['doc']->name, $tpl);
    $tpl = str_replace("##categories##", utilsMenu::setCategorieAff($data), $tpl);
    $tpl = str_replace("##keywords##", $data['keywords'], $tpl);

    $random = self::$dbRes['res']->oeuvres->aggregate([
      ['$match' => [
        'collectionUuid' => $doc->collectionUuid,
        'uuid' => ['$ne' => $doc->uuid]
      ]],
      ['$sample' => ["size" => (int)$_ENV['AC_HIST_LIMIT']]],
      ['$project' => ["uuid" => 1]]
    ]);

    $tplLi = opt::file_get_contents($_ENV['HTML_TPL'] . '/histoire_li.tpl');
    $html = '';
    foreach($random as $rand){
      $_data = utilsMenu::getHistoireData($rand->uuid);
      $li = str_replace("##histUri##", $_data['ariane'][1]['uri'], $tplLi);
      $li = str_replace("##histTitle##", $_data['doc']->title, $li);
      $li = str_replace("##histoireImageId##", $_data['doc']->imageUuid, $li);
      $li = str_replace("##imageSrc##", self::setImgSrc($_data['doc']->imageUuid), $li);
      $li = str_replace("##distantLink##", $_data['doc']->distanteLink, $li);
      $li = str_replace("##categories##", utilsMenu::setCategorieAff($_data), $li);
      $html .= $li;
    }
    $altImg = utilsMenu::getAltImg($doc->uuid, self::is_valid_token($token), $data['doc']->title);
    $htmlAlt = "";
    if(!is_null($altImg)){
      $htmlAlt .= $altImg;
      $tpl = str_replace("##hidden##", "", $tpl);
    }else{
      $tpl = str_replace("##hidden##", "hidden", $tpl);
    }
    $tpl = str_replace("##content##", $html, $tpl);
    $tpl = str_replace("##contentsAltImg##", $htmlAlt, $tpl);
    return $tpl;
  }
  public static function is_valid_token($token = null)
  {
    if(is_null($token))
      return false;
    $admin = opt::yaml_parse_file($_ENV['ADMIN_YAML']);
    if(!isset($admin['tokenList'][$token]))
      return false;
    return true;
  }
  public static function searcheImageCol($uuid, $deleted = false)
  {
    $cacheKey = 'searcheImageCol_' .$uuid . '_' . (string) $deleted;
    $cache = self::getCache($cacheKey);
    if($cache){
      return $cache;
    }
    if(is_null(self::$dbRes))
      self::$dbRes = db::get_res();
    $col = 'altImages';
    $doc = self::$dbRes['class']::getOne(
      col: $col,
      param: ["deleted" => $deleted, "uuid" => $uuid]
    );
    if(is_null($doc)){
      $col = 'siteParamsStats';
      $doc = self::$dbRes['class']::getOne(
        col: $col,
        param: ["deleted" => $deleted, "uuid" => $uuid]
      );
    }
    if(is_null($doc))
      return null;
    self::setCache($cacheKey, $col);
    return $col;
  }
  public static function getImageFromAltImages($uuid)
  {
    if(is_null(self::$getImageFromAltImages)){
      self::$getImageFromAltImages = [];
    
      if(is_null(self::$dbRes))
        self::$dbRes = db::get_res();
      $cursor = self::$dbRes['class']->get(
        col: "altImages",
        projection: ['oeuvreUuid', 'deleted', 'uuid']
      );
      foreach($cursor as $doc){
        self::$getImageFromAltImages[$doc->uuid] = $doc;
      }
    }
    if(isset(self::$getImageFromAltImages[$uuid]))
      return self::$getImageFromAltImages[$uuid];
    return null;
  }
  public static function getImageFromSiteParamsStats($uuid)
  {
    if(is_null(self::$getImageFromSiteParamsStats)){
      self::$getImageFromSiteParamsStats = [];
    
      if(is_null(self::$dbRes))
        self::$dbRes = db::get_res();
      $cursor = self::$dbRes['class']->get(
        col: "siteParamsStats",
        projection: ['from', 'deleted', 'uuid']
      );
      foreach($cursor as $doc){
        self::$getImageFromSiteParamsStats[$doc->uuid] = $doc;
      }
    }
    if(isset(self::$getImageFromSiteParamsStats[$uuid]))
      return self::$getImageFromSiteParamsStats[$uuid];
    return null;
  }
  public static function getImageFromCollections($uuid)
  {
    if(is_null(self::$getImageFromCollections)){
      self::$getImageFromCollections = [];
    
      if(is_null(self::$dbRes))
        self::$dbRes = db::get_res();
      $cursor = self::$dbRes['class']->get(
        col: "collections",
        projection: ['imageUuid', 'name']
      );
      foreach($cursor as $doc){
        self::$getImageFromCollections[$doc->imageUuid] = $doc;
      }
    }
    if(isset(self::$getImageFromCollections[$uuid]))
      return self::$getImageFromCollections[$uuid];
    return null;
  }
  public static function getImageFromOeuvres($uuid)
  {
    if(is_null(self::$getImageFromOeuvres)){
      self::$getImageFromOeuvres = [];
    
      if(is_null(self::$dbRes))
        self::$dbRes = db::get_res();
      $cursor = self::$dbRes['class']->get(
        col: "oeuvres",
        param: ['visible' => true],
        projection: ['imageUuid', 'title']
      );
      foreach($cursor as $doc){
        self::$getImageFromOeuvres[$doc->imageUuid] = $doc;
      }
    }
    if(isset(self::$getImageFromOeuvres[$uuid]))
      return self::$getImageFromOeuvres[$uuid];
    return null;
  }
  public static function getImageFrom($uuid)
  {
    if(is_null(self::$dbRes))
      self::$dbRes = db::get_res();
    $ret = [
      'from' => 'unknown',
      'status' => 'Actif',
      'statusCode' => 1, /* 0, 1, 2*/
    ];
    
    $doc = self::getImageFromAltImages($uuid);
    if(!is_null($doc)){
      $cache = false;
      if(!isset(self::$getImageFromCache['histoires']))
        self::$getImageFromCache['histoires'] = [];
      if(isset(self::$getImageFromCache['histoires'][$doc->oeuvreUuid])){
        $o = self::$getImageFromCache['histoires'][$doc->oeuvreUuid];
        $cache = true;
      }else{
        $o = self::$dbRes['class']->getOne(
          col: "oeuvres",
          param: ['uuid' => $doc->oeuvreUuid],
          projection: ['title']
        );
        self::$getImageFromCache['histoires'][$doc->oeuvreUuid] = $o;
      }
      $ret['from'] = $o->title;
      if($doc->deleted){
        $ret['status'] = 'Deleted';
        $ret['statusCode'] = 0;
      }
      return $ret;
    }

    $doc = self::getImageFromSiteParamsStats($uuid);
    if(!is_null($doc)){
      return null;
      // On ne récupère pas les sites params qui se font via accueil/images
      /*$ret['from'] = $doc->from;
      if($doc->deleted){
        $ret['status'] = 'Deleted';
        $ret['statusCode'] = 0;
      }
      return $ret;*/
    }

    $doc = self::getImageFromCollections($uuid);
    if(!is_null($doc)){
      return null;
      // On ne récupère pas les images des collections car pas d'action dessus
      $ret['from'] = $doc->name;
      $ret['status'] = 'Couv';
      $ret['statusCode'] = 2;
      return $ret;
    }

    $doc = self::$dbRes['class']->getOne(
      col: "oeuvres",
      param: ['imageUuid' => $uuid],
      projection: ['title']
    );
    if(!is_null($doc)){
      $ret['from'] = $doc->title;
      $ret['status'] = 'Couv';
      $ret['statusCode'] = 2;
      return $ret;
    }

    return $ret;
  }
  public static function getAltImgDataCpt($uuid)
  {
    if(is_null(self::$dbRes))
      self::$dbRes = db::get_res();
    return self::$dbRes['class']->count(
      col: "altImages",
      param: ['oeuvreUuid' => $uuid, 'deleted' => false]
    );
  }

  public static function getAltImgData($uuid)
  {
    $cacheKey = 'getAltImgData' .$uuid;
    $cache = self::getCache($cacheKey);
    if($cache){
      return $cache;
    }
    if(is_null(self::$dbRes))
      self::$dbRes = db::get_res();
    $cpt = self::getAltImgDataCpt($uuid);
    if($cpt == 0)
      return null;

    $ar = [];
    $cursor = self::$dbRes['class']->get(
      col: "altImages",
      param: ['oeuvreUuid' => $uuid, 'deleted' => false],
      order: ['name' => 1],
      projection: ['uuid', 'name', 'thmbWidth', 'thmbHeight']
    );
    foreach($cursor as $doc){
      unset($doc->_uid);
      $ar[] = json_decode(json_encode($doc));
    }
    self::setCache($cacheKey, $ar);
    return $ar;
  }
  public static function getAltImg($uuid, $canDelete = false, ?string $title = null)
  {
    $cursor = self::getAltImgData($uuid);
    if(is_null($cursor))
      return null;
    $tpl = opt::file_get_contents($_ENV['HTML_TPL'] . '/histoireAltImg.tpl');
    $tplLi = opt::file_get_contents($_ENV['HTML_TPL'] . '/histoireAltImg_li.tpl');
    $d = opt::file_get_contents($_ENV['HTML_TPL'] . '/image_delete.tpl');
    $cpt = self::$dbRes['class']->count(
      col: "altImages",
      param: ['oeuvreUuid' => $uuid, 'deleted' => false],
    );
    if($cpt == 0)
      return null;
    $cursor = self::$dbRes['class']->get(
      col: "altImages",
      param: ['oeuvreUuid' => $uuid, 'deleted' => false],
      order: ['name' => 1],
      projection: ['uuid', 'name', 'thmbWidth', 'thmbHeight']
    );
    $html = "";
    foreach($cursor as $doc){
      if($canDelete){
        $li = str_replace("##delete##", $d, $tplLi);
      }else{
        $li = str_replace("##delete##", "", $tplLi);
      }
      $li = str_replace("##histoireImageId##", $doc->uuid, $li);
      $li = str_replace("##imageSrc##", self::setImgSrc($doc->uuid), $li);
      $li = str_replace("##name##", $title . ' ' . $doc->name, $li);
      $li = str_replace("##width##", $doc->thmbWidth, $li);
      $li = str_replace("##height##", $doc->thmbHeight, $li);
      $html .= $li;
    }
    $tpl = str_replace('##contents##', $html, $tpl);
    return $tpl;
  }
  public static function errorPage($error, $libelle = '')
  {
    if($libelle == ''){
      if($error == 'error404')
        $libelle = 'Erreur 404';
      if($error == 'error403')
        $libelle = 'Erreur 403';
    }
    if(is_null(self::$dbRes))
      self::$dbRes = db::get_res();

    $tpl = opt::file_get_contents($_ENV['HTML_TPL'] . '/' . $error . '.tpl');
    $txt = opt::file_get_contents($_ENV['HTML_TPL'] . '/' . $error . '.txt');
    $text = '<p>' . str_replace(PHP_EOL, '</p><p>', $txt) . '</p>';
    $res = utilsMenu::getImagesStatsInfo($error);
    if($res['nbr'] > 0){
      $l = [];
      foreach($res['cursor'] as $doc){
        if(!isset($l[$doc->nbrAccess]))
          $l[$doc->nbrAccess] = [];
        $l[$doc->nbrAccess][] = $doc->uuid;
      }
      ksort($l);
      $k = array_key_first($l);
      $rand = random_int(0, count($l[$k]) - 1);
      $tpl = str_replace('##imageId##', $l[$k][$rand], $tpl);
      $tpl = str_replace('##setImgSrc##', self::setImgSrc($l[$k][$rand]), $tpl);
      $tpl = str_replace('##txt##', $text, $tpl);
      utilsMenu::setImageStatAccess($l[$k][$rand], $error);
    }else{
      $tpl = str_replace('##class##', "hidden", $tpl);
    }
    $tplLi = opt::file_get_contents($_ENV['HTML_TPL'] . '/error_li.tpl');
    $html = '';
    $random = self::$dbRes['res']->oeuvres->aggregate([
      ['$sample' => ["size" => (int)$_ENV['AC_HIST_LIMIT']]],
      ['$project' => ["uuid" => 1]]
    ]);
    foreach($random as $c){
      $_data = self::getHistoireData($c->uuid);
      $li = str_replace("##histUri##", $_data['ariane'][1]['uri'], $tplLi);
      $li = str_replace("##histTitle##", $_data['doc']->title, $li);
      $li = str_replace("##histoireImageId##", $_data['doc']->imageUuid, $li);
      $li = str_replace('##setImgSrc##', self::setImgSrc($_data['doc']->imageUuid), $tpl);
      $li = str_replace("##distantLink##", $_data['doc']->distanteLink, $li);
      $html .= $li;
    }
    $tpl = str_replace('##content##', $html, $tpl);
    $data = [
      'data' => [],
      'template' => $tpl,
      'menuLi' => 'accueil',
      'isMenu' => false,
      'title' => $libelle,
    ];

    $data['data']['ariane'] = [
      [
      'name' => array_search('accueil', menu::$menuL),
      'uri' => '/accueil',
      ],
      [
        'name' => $libelle,
        'uri' => '/accueil/' . $error,
      ]
    ];
    $data['ariane'] = self::ariane( $data['data']['ariane']);
    $scheme = isset($_SERVER['REQUEST_SCHEME']) ? $_SERVER['REQUEST_SCHEME'] : 'https';
    $data['data']['meta'] = [
      'title' => $libelle . ' - ' . $_ENV['SITE_TITLE'],
      'image' => $scheme . '://' . $_ENV['DOMAIN'] . '/components/' . $_ENV['VERSION_CTRL'] . '/img/inspiration.webp',
      'url' => $scheme . '://' . $_ENV['DOMAIN'] . $data['data']['ariane'][1]['uri'],
      'description' => htmlspecialchars('Page non trouvée ou interdite.', ENT_NOQUOTES),
      'keywords' => $libelle . ', ' . $_ENV['KEYWORDS'],
    ];
    return $data;
  }
  public static function getHistoireData($uuid)
  {
    $cacheKey = 'getHistoireData_' . $uuid;
    $cache = self::getCache($cacheKey);
    if($cache){
      return $cache;
    }
    $data = [
      'doc' => null,
      'collection' => null,
      'categories' => null,
      'keywords' => null
    ];
    if(is_null(self::$dbRes))
      self::$dbRes = db::get_res();

    $doc = self::$dbRes['class']::getOne(
      col: 'oeuvres',
      param: ['uuid' => $uuid]
    );
    if(is_null($doc))
      return $doc;
    $data['ariane'] = [
      [
        'name' => array_search('histoires', menu::$menuL),
        'uri' => '/histoires',
      ],
      [
        'name' => $doc->title,
        'uri' => '/histoires/' . seo::seofy($doc->title),
      ]
    ];
    $data['doc'] = self::_unset($doc);
    $data['collection'] = self::getCollectionData($doc->collectionUuid);
    $categories = [];
    $scheme = isset($_SERVER['REQUEST_SCHEME']) ? $_SERVER['REQUEST_SCHEME'] : 'https';
    $keywords = isset($doc->keywords) ? $doc->keywords . ', ' : '';
    $data['meta'] = [
      'title' => $data['doc']->title . ' - ' . $_ENV['SITE_TITLE'],
      'image' => $scheme . '://' . $_ENV['DOMAIN'] . $_ENV['BASE_PATH'] . '/' . $_ENV['VERSION_CTRL'] . '/imageThumb300/' . $data['doc']->imageUuid,
      'url' => $scheme . '://' . $_ENV['DOMAIN'] . $data['ariane'][1]['uri'],
      'description' => htmlspecialchars($data['doc']->desc, ENT_NOQUOTES),
      'keywords' => $keywords . $_ENV['KEYWORDS'],
    ];
    $keyw = [];
    foreach($doc->categorieUuid as $categorie){
      $cat = self::getCategorieData($categorie);
      $categories[$cat['doc']->name] = $cat;
      $keyw[] = $cat['doc']->name;
    }
    $data['meta']['keywords'] = implode(', ', $keyw) . ', ' . $data['meta']['keywords'];
    ksort($categories);
    $data['categories'] = $categories;
    $data['keywords'] = isset($doc->keywords) ? $doc->keywords : '';
    self::setCache($cacheKey, $data);
    return $data;
  }
  public static function getCollectionData($uuid)
  {
    $cacheKey = 'getCollectionData_' . $uuid;
    $cache = self::getCache($cacheKey);
    if($cache){
      return $cache;
    }
    if(is_null(self::$dbRes))
      self::$dbRes = db::get_res();

    $collection = self::$dbRes['class']::getOne(
      col: 'collections',
      param: ['uuid' => $uuid]
    );
    if(is_null($collection))
      return $collection;
    $data = [
      'doc' => self::_unset($collection),
      'ariane' => [
        [
          'name' => array_search('collections', menu::$menuL),
          'uri' => '/collections',
        ],
        [
          'name' => $collection->name,
          //'uri' => '/collections/' . $collection->uuid . '/' . seo::seofy($collection->name),
          'uri' => '/collections/' . seo::seofy($collection->name),
        ]
      ],
      'cptHist' => self::$dbRes['class']::count(
          col: 'oeuvres',
          param: ['collectionUuid' => $uuid]
        )
    ];
    $data['histoires'] = [
      'nbr' => 0,
      'list' => []
    ];
    $cursor = self::$dbRes['class']->get(
      col: 'oeuvres',
      param: ['collectionUuid' => $uuid],
      order: ['title' => 1],
      projection: ['uuid', 'categorieUuid']
    );
    $data['categories'] = [];
    foreach($cursor as $doc){
      $data['histoires']['nbr'] += 1;
      $data['histoires']['list'][] = $doc->uuid;
      foreach($doc->categorieUuid as $cat){
        if(!in_array($cat, $data['categories']))
          $data['categories'][] = $cat;
      }
    }
    $scheme = isset($_SERVER['REQUEST_SCHEME']) ? $_SERVER['REQUEST_SCHEME'] : 'https';
    $data['meta'] = [
      'title' => 'Collection ' . $data['doc']->name . ' - ' . $_ENV['SITE_TITLE'],
      'image' => $scheme . '://' . $_ENV['DOMAIN'] . $_ENV['BASE_PATH'] . '/' . $_ENV['VERSION_CTRL'] . '/imageThumb300/' . $data['doc']->imageUuid,
      'url' => $scheme . '://' . $_ENV['DOMAIN'] . $data['ariane'][1]['uri'],
      'description' => htmlspecialchars($data['doc']->desc, ENT_NOQUOTES),
      'keywords' => $data['doc']->name . ', ' . $_ENV['KEYWORDS'],
    ];
    self::setCache($cacheKey, $data);
    return $data;
  }
  public static function getCategorieData($uuid)
  {
    $cacheKey = 'getCategorieData_' . $uuid;
    $cache = self::getCache($cacheKey);
    if($cache){
      return $cache;
    }
    if(is_null(self::$dbRes))
      self::$dbRes = db::get_res();

    $categorie = self::$dbRes['class']::getOne(
      col: 'categories',
      param: ['uuid' => $uuid]
    );
    if(is_null($categorie))
      return $categorie;
    $data = [
      'doc' => self::_unset($categorie)
    ];
    $data['ariane'] = [
      [
        'name' => array_search('histoires', menu::$menuL),
        'uri' => '/histoires',
      ],
      [
        'name' => "Catégorie " . $categorie->name,
        'uri' => '/histoires/categories/' . seo::seofy($categorie->name),
        //'uri' => '/histoires/categories/' . $categorie->uuid . '/' . seo::seofy($categorie->name),
      ]
    ];
    $data['histoires'] = [
      'nbr' => 0,
      'list' => []
    ];
    $cursor = self::$dbRes['class']->get(
      col: 'oeuvres',
      param: ['categorieUuid' => $uuid],
      order: ['dateCreate' => -1],
      projection: ['uuid', 'dateUpdate']
    );
    foreach($cursor as $doc){
      $data['histoires']['nbr'] += 1;
      $data['histoires']['list'][] = $doc->uuid;
      if($data['doc']->dateUpdate < $doc->dateUpdate)
        $data['doc']->dateUpdate = $doc->dateUpdate;
    }
    $scheme = isset($_SERVER['REQUEST_SCHEME']) ? $_SERVER['REQUEST_SCHEME'] : 'https';
    $data['meta'] = [
      'title' => $data['doc']->name . ' - ' . $_ENV['SITE_TITLE'],
      'image' => $scheme . '://' . $_ENV['DOMAIN'] . '/components/' . $_ENV['VERSION_CTRL'] . '/img/inspiration.webp',
      'url' => $scheme . '://' . $_ENV['DOMAIN'] . $data['ariane'][1]['uri'],
      'description' => htmlspecialchars('Histoires de la catégorie ' . $data['doc']->name, ENT_NOQUOTES),
      'keywords' => $data['doc']->name . ', ' . $_ENV['KEYWORDS'],
    ];
    self::setCache($cacheKey, $data);
    return $data;
  }
  public static function getSeoHistoires()
  {
    return self::_getSeo('oeuvres', 'title');
  }
  public static function getSeoCategories()
  {
    return self::_getSeo('categories');
  }
  public static function getSeoCollections()
  {
    return self::_getSeo('collections');
  }
  private static function _getSeo($col, $project = 'name'){
    $cacheKey = 'getSeo' . $col;
    $cache = self::getCache($cacheKey);
    if($cache){
      return $cache;
    }
    if(is_null(self::$dbRes))
      self::$dbRes = db::get_res();
    $cursor = self::$dbRes['class']::get(
      col: $col,
      projection: ['uuid', $project]
    );
    $list = [];
    foreach($cursor as $doc){
      $seo = seo::seofy($doc->{$project});
      $list[$seo] = $doc->uuid;
    }
    self::setCache($cacheKey, $list);
    return $list;
  }
  public static function setCategorieAff($data)
  {
    $html = 'Catégorie';
    $nbrCat = count($data['categories']);
    if($nbrCat == 0){
      $html .= ' : Non catégorisée';
    }else if($nbrCat == 1){
      $html .= ' : ';
    }
    else{
      $html .= 's : ';
    }
    if($nbrCat !== 0){
      $nbr = 0;
      $catHtml = "";
      $tplLi = opt::file_get_contents($_ENV['HTML_TPL'] . '/categorie.tpl');
      foreach($data['categories'] as $cat){
        $li = str_replace('##catUri##', $cat['ariane'][1]['uri'], $tplLi);
        $li = str_replace('##catName##', '« '.$cat['doc']->name.' »', $li);
        if($nbr == 0){
          $catHtml .= $li;
        }else{
          $catHtml .= ', ' . $li;
        }
        $nbr += 1;
      }
      $html .= '<i>' . $catHtml . '</i>';
    }
    return $html;
  }
  public static function getCache($key)
  {
    $key = self::$cacheId . $key;
    if(isset(self::$internalCache[$key]))
      return self::$internalCache[$key];
    if($cache = apcu_fetch($key))
      return $cache;

    $cache = cache::_get($key);
    if(!$cache)
      return $cache;
    return unserialize($cache);
  }
  public static function setCache($key, $data)
  {
    $key = self::$cacheId . $key;
    self::$internalCache[$key] = $data;
    apcu_store($key, $data, $_ENV['EXP_CACHE']);
    cache::set($key, serialize($data));
  }
  public static function _unset($doc){
    unset($doc->_id);
    unset($doc->gristId);
    unset($doc->gristuuid);
    //unset($doc->dateCreate);
    unset($doc->sha);
    return $doc;
  }
  public static function getImageMenuDel()
  {
    $cursor = self::getImagesStatCursor(true);
    $l = [];
    foreach($cursor as $doc){
      $l[] = $doc;
    }
    return $l;
  }
  public static function getImagesStatsInfo($from = 'accueil')
  {
    if(is_null(self::$dbRes))
      self::$dbRes = db::get_res();
    $cpt = self::$dbRes['class']->count(
      col: 'siteParamsStats',
      param: ['deleted' => false, 'from' => $from],
    );
    $cursor = self::$dbRes['class']->get(
      col: 'siteParamsStats',
      param: ['deleted' => false, 'from' => $from],
      projection: ['uuid', 'nbrAccess'],
      order: ['dateUpdate' => -1, 'nbrAccess' => 1]
    );
    $ret = [
      'nbr' => $cpt,
      'cursor' => $cursor,
    ];
    return $ret;
  }
  public static function getImagesStatCursor($deleted = false)
  {
    if(is_null(self::$dbRes))
      self::$dbRes = db::get_res();
    $cursor = self::$dbRes['class']->get(
      col: 'siteParamsStats',
      param: ['deleted' => $deleted],
      projection: ['uuid', 'nbrAccess', 'from', '_uid' => -1],
      order: ['dateUpdate' => -1, 'nbrAccess' => -1]
    );
    return $cursor;
  }
  public static function getImageStatAccess($uuid, $from = "accueil")
  {
    if(is_null(self::$dbRes))
      self::$dbRes = db::get_res();
    $doc = self::$dbRes['class']->getOne(
      col: 'siteParamsStats',
      param: ['uuid' => $uuid, 'deleted' => false],
      projection: ['nbrAccess']
    );
    if(is_null($doc)){
      self::setImageStatAccess($uuid, $from, false);
      return 0;
    }
    return $doc->nbrAccess;
  }
  public static function setImageStatAccess($uuid, $from = "accueil", $incAccess = true)
  {
    if($incAccess && \Svgta\Lib\Utils::is_bot())
      return;

    if(is_null(self::$dbRes))
      self::$dbRes = db::get_res();

    $cptImg = self::$dbRes['class']::count(
      col: "images.files",
      param: ['filename' => $uuid]
    );
    if($cptImg == 0){
      $cptImg = self::$dbRes['class']::count(
        col: "images.files",
        param: ['$or' => [['metadata.title' => $uuid], ['metadata.title' => pathinfo($uuid)['filename']]]]
      );
      if($cptImg != 1)
        return;
      $img = self::$dbRes['class']::getOne(
        col: "images.files",
        param: ['$or' => [['metadata.title' => $uuid], ['metadata.title' => pathinfo($uuid)['filename']]]],
        projection: ['filename']
      );
      $uuid = $img->filename;
    }

    $doc = self::$dbRes['class']->getOne(
      col: 'siteParamsStats',
      param: ['uuid' => $uuid]
    );
    
    if(is_null($doc)){
      $m = new siteParamsStats();
      $m->newDate();
      $m->uuid = $uuid;
      $m->from = $from;
      if($incAccess)
        $m->nbrAccess = 1;
     self::$dbRes['class']->post(
        col: 'siteParamsStats',
        param: $m->_toArray()
      );
    }else{
      self::$dbRes['class']->put(
        col: 'siteParamsStats',
        uuid: $uuid,
        param: [
          'nbrAccess' => $incAccess ? $doc->nbrAccess + 1 : $doc->nbrAccess, 
          'dateUpdate' => time(),
          'deleted' => false,
          'from' => $from
        ]
      );
    }
  }
  public static function ariane(array $ar){
    $html = "";
    $tplLi = opt::file_get_contents($_ENV['HTML_TPL'] . '/ariane_li.tpl');
    foreach($ar as $a){
      $li = str_replace("##href##", $a["uri"], $tplLi);
      $li = str_replace("##name##", $a["name"], $li);
      $html .= $li;
    }
    $tpl = opt::file_get_contents($_ENV['HTML_TPL'] . '/ariane.tpl');
    return str_replace("##ariane##", $html, $tpl);
  }
}