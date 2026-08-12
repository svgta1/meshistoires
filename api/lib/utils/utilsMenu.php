<?php
namespace Meshistoires\Api\utils;
use Meshistoires\Api\utils\cache;
use Meshistoires\Api\backend\db;
use Meshistoires\Api\model\siteParamsStats;
use Meshistoires\Api\utils\seo;

class utilsMenu
{
  private static $cache = false;
  private static $cacheId = "Cache_Menus_";
  private static $dbRes = null;

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
    return $ar;
  }
  public static function getAltImg($uuid, $canDelete = false)
  {
    $cursor = self::getAltImgData($uuid);
    if(is_null($cursor))
      return null;
    $tpl = file_get_contents($_ENV['HTML_TPL'] . '/histoireAltImg.tpl');
    $tplLi = file_get_contents($_ENV['HTML_TPL'] . '/histoireAltImg_li.tpl');
    $tplLiDel = file_get_contents($_ENV['HTML_TPL'] . '/histoireAltImg_li_delete.tpl');
    $cursor = self::$dbRes['class']->get(
      col: "altImages",
      param: ['oeuvreUuid' => $uuid],
      order: ['name' => 1],
      projection: ['uuid', 'name', 'thmbWidth', 'thmbHeight']
    );
    $html = "";
    foreach($cursor as $doc){
      if($canDelete){
        $li = str_replace("##delete##", $tplLiDel, $tplLi);
      }else{
        $li = str_replace("##delete##", "", $tplLi);
      }
      $li = str_replace("##histoireImageId##", $doc->uuid, $li);
      $li = str_replace("##name##", $doc->name, $li);
      $li = str_replace("##width##", $doc->thmbWidth, $li);
      $li = str_replace("##height##", $doc->thmbHeight, $li);
      $html .= $li;
    }
    $tpl = str_replace('##contents##', $html, $tpl);
    return $tpl;
  }
  public static function errorPage($error, $libelle)
  {
    if(is_null(self::$dbRes))
      self::$dbRes = db::get_res();

    $tpl = file_get_contents($_ENV['HTML_TPL'] . '/' . $error . '.tpl');
    $doc = self::$dbRes['class']->getOne(
      col: 'siteparams',
      param: ['name' => $error]
    );

    $nbrImg = 0;
    if($doc){
      $nbrImg = count($doc->imagesUuid);
    }
    if($nbrImg > 0){
      $l = [];
      foreach($doc->imagesUuid as $uuid){
        $nbr = self::getImageStatAccess($uuid);
        if(is_null($nbr))
          continue;
        if(!isset($l[$nbr]))
          $l[$nbr] = [];
        $l[$nbr][] = $uuid;
      }
      ksort($l);
      $k = array_key_first($l);
      $rand = random_int(0, count($l[$k]) - 1);
      $tpl = str_replace('##imageId##', $l[$k][$rand], $tpl);
      self::setImageStatAccess($l[$k][$rand], $error);
    }else{
      $tpl = str_replace('##class##', "hidden", $tpl);
    }
    $random = self::$dbRes['res']->oeuvres->aggregate([
      ['$sample' => ["size" => (int)$_ENV['AC_HIST_LIMIT']]],
      ['$project' => ["uuid" => 1]]
    ]);
    $tplLi = file_get_contents($_ENV['HTML_TPL'] . '/error_li.tpl');
    $html = '';
    foreach($random as $c){
      $_data = self::getHistoireData($c->uuid);
      $li = str_replace("##histUri##", $_data['ariane'][1]['uri'], $tplLi);
      $li = str_replace("##histTitle##", $_data['doc']->title, $li);
      $li = str_replace("##histoireImageId##", $_data['doc']->imageUuid, $li);
      $li = str_replace("##distantLink##", $_data['doc']->distanteLink, $li);
      $html .= $li;
    }
    $tpl = str_replace('##content##', $html, $tpl);
    $data = [
      'ariane' => self::ariane([
        [
        'name' => 'Accueil',
        'uri' => '/accueil',
        ],
        [
          'name' => $libelle,
          'uri' => '/accueil/' . $error,
        ]
      ]),
      'template' => $tpl,
      'menuLi' => 'accueil',
      'isMenu' => false,
      'title' => $libelle,
      'contents' => [
        'desc' => 'Page non trouvée.',
      ],
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
      'categories' => null
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
        'name' => 'Histoires',
        'uri' => '/histoires',
      ],
      [
        'name' => $doc->title,
        'uri' => '/histoires/' . seo::seofy($doc->title),
        //'uri' => '/histoires/' . $doc->uuid . '/' . seo::seofy($doc->title),
      ]
    ];
    $data['doc'] = self::_unset($doc);
    $data['collection'] = self::getCollectionData($doc->collectionUuid);
    $categories = [];
    foreach($doc->categorieUuid as $categorie){
      $cat = self::getCategorieData($categorie);
      $categories[$cat['doc']->name] = $cat;
    }
    ksort($categories);
    $data['categories'] = $categories;
    
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
          'name' => 'Collections',
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
      projection: ['uuid']
    );
    foreach($cursor as $doc){
      $data['histoires']['nbr'] += 1;
      $data['histoires']['list'][] = $doc->uuid;
    }
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
        'name' => 'Histoires',
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
      projection: ['uuid']
    );
    foreach($cursor as $doc){
      $data['histoires']['nbr'] += 1;
      $data['histoires']['list'][] = $doc->uuid;
    }
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
      $tplLi = file_get_contents($_ENV['HTML_TPL'] . '/categorie.tpl');
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
    $cache = cache::_get($key);
    if(!$cache)
      return $cache;
    return unserialize($cache);
  }
  public static function setCache($key, $data)
  {
    $key = self::$cacheId . $key;
    cache::set($key, serialize($data));
  }
  public static function _unset($doc){
    unset($doc->_id);
    unset($doc->gristId);
    unset($doc->gristuuid);
    unset($doc->dateCreate);
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
    if(is_null(self::$dbRes))
      self::$dbRes = db::get_res();

    $cptImg = self::$dbRes['class']::count(
      col: "images.files",
      param: ['filename' => $uuid]
    );
    if($cptImg == 0){
      $cptImg = self::$dbRes['class']::count(
        col: "images.files",
        param: ['metadata.title' => $uuid]
      );
      if($cptImg != 1)
        return;
      $img = self::$dbRes['class']::getOne(
        col: "images.files",
        param: ['metadata.title' => $title],
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
    $tplLi = file_get_contents($_ENV['HTML_TPL'] . '/ariane_li.tpl');
    foreach($ar as $a){
      $li = str_replace("##href##", $a["uri"], $tplLi);
      $li = str_replace("##name##", $a["name"], $li);
      $html .= $li;
    }
    $tpl = file_get_contents($_ENV['HTML_TPL'] . '/ariane.tpl');
    return str_replace("##ariane##", $html, $tpl);
  }
}