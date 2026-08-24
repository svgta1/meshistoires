<?php
namespace Meshistoires\Api\utils;
use Meshistoires\Api\utils\utilsMenu;
use Meshistoires\Api\utils\opt;
use Meshistoires\Api\utils\siteInfo;
use Meshistoires\Api\controller\v2r0\menu;

class CreativeWork
{
  public static function setHistoire($uuid)
  {
    $scheme = isset($_SERVER['REQUEST_SCHEME']) ? $_SERVER['REQUEST_SCHEME'] : 'https';
    $data = utilsMenu::getHistoireData($uuid);
    $creative = self::genHistoire();
    $creative['@id'] = $scheme . '://' . $_ENV['DOMAIN'] . '_' . $uuid;
    $creative['name'] = $data['meta']['title'];
    $creative['keywords'] = explode(', ', $data['meta']['keywords']);
    $creative['description'] = $data['meta']['description'];
    foreach($data['categories'] as $catName => $cat){
      $creative['genre'][] = $catName;
    }
    $creative['url'] = $data['meta']['url'];
    $creative['datePublished'] = self::setDate($data['doc']->dateCreate);
    $creative['dateModified'] = self::setDate($data['doc']->dateUpdate);
    foreach(utilsMenu::setImgsSrc($data['doc']->imageUuid) as $img){
      $creative['image'][] = $img;
    }

    $creative['isPartOf'][] = self::setCollection($data['doc']->collectionUuid, false);
    foreach($data['doc']->categorieUuid as $catUuid){
      $creative['isPartOf'][] = self::setCategorie($catUuid, false);
    }

    $illustrations = utilsMenu::getAltImgData($uuid);
    if(!is_null($illustrations)){
      $creative['hasPart'] = [];
      foreach($illustrations as $k => $ill){
        $ilCre = self::genIllustration();
        $ilCre['@id'] .= $ill->uuid;
        $ilCre['name'] .= $data['doc']->title . ' - numéro ' . $k + 1;
        $imgs = utilsMenu::setImgsSrc($ill->uuid);
        foreach($imgs as $img){
          $ilCre['image'][] = $img;
        }
        $creative['hasPart'][] = $ilCre;
      }
    }

    $BreadcrumbList = self::genBreadcrumbList();
    foreach($data['ariane'] as $k => $ariane){
      $br = self::genBreadcrumbListElm();
      $br['name'] = $ariane['name'];
      $br['item'] = $scheme . '://' . $_ENV['DOMAIN']. $ariane['uri'];
      $br['position'] = $k + 1;
      $BreadcrumbList['itemListElement'][] = $br;
    }
    return [$creative, $BreadcrumbList];
  }
  public static function setCollection($uuid, $context = true)
  {
    $scheme = isset($_SERVER['REQUEST_SCHEME']) ? $_SERVER['REQUEST_SCHEME'] : 'https';
    $data = utilsMenu::getCollectionData($uuid);
    $creative = self::genCollection();
    $creative['keywords'] = explode(', ', $data['meta']['keywords']);
    if(!$context){
      unset($creative['@context']);
      unset($creative['author']);
      unset($creative['inLanguage']);
      unset($creative['publisher']);
      unset($creative['audience']);
      unset($creative['isAccessibleForFree']);
      unset($creative['license']);
      unset($creative['keywords']);
      unset($creative['mainEntity']);
    }else{
      $creative['@type'] = ['CollectionPage', $creative['@type']];
    }
    $creative['@id'] .= $uuid;
    $creative['name'] = $data['meta']['title'];
    $creative['description'] = $data['meta']['description'];
    foreach($data['categories'] as $catName => $cat){
      $catData = utilsMenu::getCategorieData($cat);
      $creative['genre'][] = $catData['doc']->name;
    }
    $creative['url'] = $data['meta']['url'];
    $creative['startDate'] = self::setDate($data['doc']->dateCreate);
    foreach(utilsMenu::setImgsSrc($data['doc']->imageUuid) as $img){
      $creative['image'][] = $img;
    }

    if(!$context)
      return $creative;

    $BreadcrumbList = self::genBreadcrumbList();
    foreach($data['ariane'] as $k => $ariane){
      $br = self::genBreadcrumbListElm();
      $br['name'] = $ariane['name'];
      $br['item'] = $scheme . '://' . $_ENV['DOMAIN']. $ariane['uri'];
      $br['position'] = $k + 1;
      $BreadcrumbList['itemListElement'][] = $br;
    }

    $histoires = $data['histoires'];
    $creative['mainEntity']['numberOfItems'] = $histoires['nbr'];
    foreach($histoires['list'] as $k => $uuid){
      $h = utilsMenu::getHistoireData($uuid);
      $cre = self::genHistList();
      $cre['position'] = $k + 1;
      $cre['item']['@id'] .= $uuid;
      $cre['item']['name'] = $h['meta']['title'];
      $cre['item']['url'] = $h['meta']['url'];
      $cre['item']['description'] = $h['meta']['description'];
      foreach(utilsMenu::setImgsSrc($h['doc']->imageUuid) as $img){
        $cre['item']['image'][] = $img;
      }
      $creative['mainEntity']['itemListElement'][] = $cre;
    }
    return [$creative, $BreadcrumbList];
  }
  public static function setCategorie($uuid, $context = true)
  {
    $scheme = isset($_SERVER['REQUEST_SCHEME']) ? $_SERVER['REQUEST_SCHEME'] : 'https';
    $data = utilsMenu::getCategorieData($uuid);
    $creative = self::genCategorie();
    $creative['genre'] = $data['doc']->name;
    $creative['keywords'] = explode(', ', $data['meta']['keywords']);
    if(!$context){
      unset($creative['@context']);
      unset($creative['author']);
      unset($creative['inLanguage']);
      unset($creative['audience']);
      unset($creative['isAccessibleForFree']);
      unset($creative['license']);
      unset($creative['genre']);
      unset($creative['keywords']);
      unset($creative['mainEntity']);
    }
    $creative['@id'] .= $uuid;
    $creative['name'] = $data['meta']['title'];
    $creative['description'] = $data['meta']['description'];
    $creative['url'] = $data['meta']['url'];

    if(!$context)
      return $creative;

    $BreadcrumbList = self::genBreadcrumbList();
    foreach($data['ariane'] as $k => $ariane){
      $br = self::genBreadcrumbListElm();
      $br['name'] = $ariane['name'];
      $br['item'] = $scheme . '://' . $_ENV['DOMAIN']. $ariane['uri'];
      $br['position'] = $k + 1;
      $BreadcrumbList['itemListElement'][] = $br;
    }

    $histoires = $data['histoires'];
    $creative['mainEntity']['numberOfItems'] = $histoires['nbr'];
    foreach($histoires['list'] as $k => $uuid){
      $h = utilsMenu::getHistoireData($uuid);
      $cre = self::genHistList();
      $cre['position'] = $k + 1;
      $cre['item']['@id'] .= $uuid;
      $cre['item']['name'] = $h['meta']['title'];
      $cre['item']['url'] = $h['meta']['url'];
      $cre['item']['description'] = $h['meta']['description'];
      foreach(utilsMenu::setImgsSrc($h['doc']->imageUuid) as $img){
        $cre['item']['image'][] = $img;
      }
      $creative['mainEntity']['itemListElement'][] = $cre;
    }
    return [$creative, $BreadcrumbList];
  }
  public static function setMenuCollections()
  {
    $scheme = isset($_SERVER['REQUEST_SCHEME']) ? $_SERVER['REQUEST_SCHEME'] : 'https';
    $menu = new menu([], []);
    $data = $menu->_get("collections");
    $data = $data['data'];
    $creative = self::genCategorie();
    $creative['@id'] .= 'collections';
    $creative['name'] = 'Les des collections de ' . $_ENV['SITE_TITLE'];
    $creative['url'] .= '/collections';
    $creative['keywords'] = explode(', ', $data['meta']['keywords']);
    $creative['genre'] = utilsMenu::getGenre();
    $cursor = utilsMenu::getCursorCollections();
    $nbr = 0;
    foreach($cursor as $k => $doc){
      $h = utilsMenu::getCollectionData($doc->uuid);
      $cre = self::getCollectionList();
      $cre['position'] = $k + 1;
      $cre['item']['@id'] .= $doc->uuid;
      $cre['item']['name'] = $h['meta']['title'];
      $cre['item']['url'] = $h['meta']['url'];
      $cre['item']['description'] = $h['meta']['description'];
      foreach(utilsMenu::setImgsSrc($h['doc']->imageUuid) as $img){
        $cre['item']['image'][] = $img;
      }
      $creative['mainEntity']['itemListElement'][] = $cre;
      $nbr += 1;
    }
    $creative['mainEntity']['numberOfItems'] = $nbr;

    $BreadcrumbList = self::genBreadcrumbList();
    foreach($data['ariane'] as $k => $ariane){
      $br = self::genBreadcrumbListElm();
      $br['name'] = $ariane['name'];
      $br['item'] = $scheme . '://' . $_ENV['DOMAIN']. $ariane['uri'];
      $br['position'] = $k + 1;
      $BreadcrumbList['itemListElement'][] = $br;
    }
    return [$creative, $BreadcrumbList];
  }
  public static function setMenuHistoires()
  {
    $scheme = isset($_SERVER['REQUEST_SCHEME']) ? $_SERVER['REQUEST_SCHEME'] : 'https';
    $menu = new menu([], []);
    $data = $menu->_get("histoires");
    $data = $data['data'];
    $creative = self::genCategorie();
    $creative['@id'] .= 'histoires';
    $creative['name'] = 'Les des histoires de ' . $_ENV['SITE_TITLE'];
    $creative['url'] .= '/histoires';
    $creative['keywords'] = explode(', ', $data['meta']['keywords']);
    $creative['genre'] = utilsMenu::getGenre();
    $cursor = utilsMenu::getCursorHistoires();
    $nbr = 0;
    foreach($cursor as $k => $doc){
      $h = utilsMenu::getHistoireData($doc->uuid);
      $cre = self::genHistList();
      $cre['position'] = $k + 1;
      $cre['item']['@id'] .= $doc->uuid;
      $cre['item']['name'] = $h['meta']['title'];
      $cre['item']['url'] = $h['meta']['url'];
      $cre['item']['description'] = $h['meta']['description'];
      foreach(utilsMenu::setImgsSrc($h['doc']->imageUuid) as $img){
        $cre['item']['image'][] = $img;
      }
      $creative['mainEntity']['itemListElement'][] = $cre;
      $nbr += 1;
    }
    $creative['mainEntity']['numberOfItems'] = $nbr;

    $BreadcrumbList = self::genBreadcrumbList();
    foreach($data['ariane'] as $k => $ariane){
      $br = self::genBreadcrumbListElm();
      $br['name'] = $ariane['name'];
      $br['item'] = $scheme . '://' . $_ENV['DOMAIN']. $ariane['uri'];
      $br['position'] = $k + 1;
      $BreadcrumbList['itemListElement'][] = $br;
    }
    return [$creative, $BreadcrumbList];
  }
  public static function setMenuAccueil($uuid)
  {
    if($uuid == 'collections'){
      return self::setMenuCollections();
    }
    if($uuid == 'histoires'){
      return self::setMenuHistoires();
    }
    $scheme = isset($_SERVER['REQUEST_SCHEME']) ? $_SERVER['REQUEST_SCHEME'] : 'https';
    $url = '';
    if($uuid != 'accueil'){
      $url = '/' . $uuid;
      $data = utilsMenu::errorPage($uuid);
    }else{
      $menu = new menu([], []);
      $data = $menu->_get($uuid);
    }
    $data = $data['data'];
    $creative = self::genMenuAccueil();
    $creative[0]['@id'] .= $uuid;
    $creative[0]['name'] = $data['meta']['title'];
    $creative[0]['url'] .= $url;
    $creative[0]['description'] = htmlspecialchars(opt::file_get_contents($_ENV['HTML_TPL'] . '/' . $uuid . '.txt'), ENT_NOQUOTES);

    $cursor = utilsMenu::getCursorLastHistoires();
    foreach($cursor as $k => $doc){
      $h = utilsMenu::getHistoireData($doc->uuid);
      $cre = self::genHistList();
      $cre['position'] = $k + 1;
      $cre['item']['@id'] .= $doc->uuid;
      $cre['item']['name'] = $h['meta']['title'];
      $cre['item']['url'] = $h['meta']['url'];
      $cre['item']['description'] = $h['meta']['description'];
      foreach(utilsMenu::setImgsSrc($h['doc']->imageUuid) as $img){
        $cre['item']['image'][] = $img;
      }
      $creative[0]['mainEntity']['itemListElement'][] = $cre;
    }

    $creative[1]['sameAs'] = siteInfo::getSocialAr();

    $BreadcrumbList = self::genBreadcrumbList();
    foreach($data['ariane'] as $k => $ariane){
      $br = self::genBreadcrumbListElm();
      $br['name'] = $ariane['name'];
      $br['item'] = $scheme . '://' . $_ENV['DOMAIN']. $ariane['uri'];
      $br['position'] = $k + 1;
      $BreadcrumbList['itemListElement'][] = $br;
    }

    $creative[] = $BreadcrumbList;
    return $creative;
  }
  public static function genIllustration()
  {
    if(!is_file($_ENV['CREATIVEWORK']))
      return false;
    $scheme = isset($_SERVER['REQUEST_SCHEME']) ? $_SERVER['REQUEST_SCHEME'] : 'https';

    $il = [
      '@type' => "VisualArtwork",
      '@id' => $scheme . '://' . $_ENV['DOMAIN'] . '_illustration_',
      'name' => 'Illustration ',
      'image' => [],
    ];

    return $il;
  }
  public static function genBreadcrumbListElm()
  {
    $e = [
      '@type' => "ListItem",
      'position' => 0,
      'name' => '',
      'item' => '',
    ];
    return $e;
  }
  public static function genBreadcrumbList()
  {
    $b = [
      '@context' => 'https://schema.org',
      '@type' => "BreadcrumbList",
      'itemListElement' => []
    ];

    return $b;
  }
  public static function genMenuAccueil()
  {
    if(!is_file($_ENV['CREATIVEWORK']))
      return false;
    $scheme = isset($_SERVER['REQUEST_SCHEME']) ? $_SERVER['REQUEST_SCHEME'] : 'https';
    $crea = opt::yaml_parse_file($_ENV['CREATIVEWORK']);

    $acc = [
      [
        '@context' => 'https://schema.org',
        '@type' => 'Website',
        '@id' => $scheme . '://' . $_ENV['DOMAIN'] . '_accueil_',
        'name' => '',
        'url' => $scheme . '://' . $_ENV['DOMAIN'] . "/accueil",
        'description' => '',
        'inLanguage' => 'fr',
        'publisher' => [
          '@id' =>$scheme . '://' . $_ENV['DOMAIN'] . '_author_' . $crea['author']['name'],
        ],
        'image' => [
          $scheme . '://' . $_ENV['DOMAIN'] . '/components/' . $_ENV['VERSION_CTRL'] . '/img/logo_mh.png',
          $scheme . '://' . $_ENV['DOMAIN'] . '/components/' . $_ENV['VERSION_CTRL'] . '/img/logo_mh_16.png',
          $scheme . '://' . $_ENV['DOMAIN'] . '/components/' . $_ENV['VERSION_CTRL'] . '/img/logo_mh_32.png',
          $scheme . '://' . $_ENV['DOMAIN'] . '/components/' . $_ENV['VERSION_CTRL'] . '/img/logo_mh_64.png',
          $scheme . '://' . $_ENV['DOMAIN'] . '/components/' . $_ENV['VERSION_CTRL'] . '/img/logo_mh_128.png',
          $scheme . '://' . $_ENV['DOMAIN'] . '/components/' . $_ENV['VERSION_CTRL'] . '/img/inspiration.webp',
        ],
        'mainEntity' => [
          '@type' => 'ItemList',
          'name' => 'Dernières publications',
          'description' => 'Les dernières publications d\'histoires sur le site.',
          'numberOfItems' => $_ENV['AC_HIST_LIMIT'],
          'itemListElement' => [],
        ],
      ],
      [
        '@context' => 'https://schema.org',
        '@type' => 'Person',
        '@id' => $scheme . '://' . $_ENV['DOMAIN'] . '_author_' . $crea['author']['name'],
        "name" => $crea['author']['name'],
        'url' => $scheme . '://' . $_ENV['DOMAIN'],
        'sameAs' => [],
      ],
    ];

    return $acc;
  }
  public static function genCategorie()
  {
    if(!is_file($_ENV['CREATIVEWORK']))
      return false;
    $scheme = isset($_SERVER['REQUEST_SCHEME']) ? $_SERVER['REQUEST_SCHEME'] : 'https';
    
    $crea = opt::yaml_parse_file($_ENV['CREATIVEWORK']);

    $cat = [
      '@context' => 'https://schema.org',
      '@type' => "CollectionPage",
      '@id' => $scheme . '://' . $_ENV['DOMAIN'] . '_categorie_',
      'name' => '',
      'url' => $scheme . '://' . $_ENV['DOMAIN'],
      'genre' => [],
      'inLanguage' => 'fr',
      'keywords' => [],
      "author" => [
        '@type' => 'Person',
        'name' => $crea['author']['name'],
        'url' => $scheme . '://' . $_ENV['DOMAIN'],
        '@id' => $scheme . '://' . $_ENV['DOMAIN'] . '_author_' . $crea['author']['name'],
      ],
      'audience' => [
        '@type' => "PeopleAudience",
        'suggestedMinAge' => 18
      ],
      'isAccessibleForFree' => true,
      'license' => 'https://creativecommons.org/licenses/by-nc-nd/3.0/deed.fr',
      'mainEntity' => [
        '@type' => "ItemList",
        'numberOfItems' => 0,
        'itemListElement' => [],
      ],
    ];
    return $cat;
  }
  public static function genHistList()
  {
    $scheme = isset($_SERVER['REQUEST_SCHEME']) ? $_SERVER['REQUEST_SCHEME'] : 'https';
    $hl = [
      '@type' => 'ListItem',
      'position' => 0,
      'item' => [
        '@type' => "Book",
        '@id' => $scheme . '://' . $_ENV['DOMAIN'] . '_histoire_',
        'name' => '',
        'url' => '',
        'description' => '',
        'image' => [],
      ]
    ];
    return $hl;
  }
  public static function getCollectionList()
  {
    if(!is_file($_ENV['CREATIVEWORK']))
      return false;
    $scheme = isset($_SERVER['REQUEST_SCHEME']) ? $_SERVER['REQUEST_SCHEME'] : 'https';
    
    $crea = opt::yaml_parse_file($_ENV['CREATIVEWORK']);

    $col = [
      '@type' => "ListItem",
      'position' => 0,
      'item' => [
        '@type' => 'BookSeries',
        '@id' => $scheme . '://' . $_ENV['DOMAIN'] . '_collection_',
        'name' => '',
        'url' => $scheme . '://' . $_ENV['DOMAIN'],
        'image' => [],
      ]
    ];
    return $col;
  }
  public static function genCollection()
  {
    if(!is_file($_ENV['CREATIVEWORK']))
      return false;
    $scheme = isset($_SERVER['REQUEST_SCHEME']) ? $_SERVER['REQUEST_SCHEME'] : 'https';
    
    $crea = opt::yaml_parse_file($_ENV['CREATIVEWORK']);

    $col = [
      '@context' => 'https://schema.org',
      '@type' => "BookSeries",
      '@id' => $scheme . '://' . $_ENV['DOMAIN'] . '_collection_',
      'name' => '',
      'keywords' => [],
      'description' => '',
      'genre' => [],
      "author"=> [
        '@type' => 'Person',
        'name' => $crea['author']['name'],
        'url' => $scheme . '://' . $_ENV['DOMAIN'],
        '@id' => $scheme . '://' . $_ENV['DOMAIN'] . '_author_' . $crea['author']['name'],
      ],
      'url' => '',
      'image' => [],
      'startDate' => '',
      'inLanguage' => 'fr',
      'publisher' => [
        '@type' => 'Organization',
        'name' => $_ENV['SITE_TITLE'],
        'url' => $scheme . '://' . $_ENV['DOMAIN'],
        '@id' => $scheme . '://' . $_ENV['DOMAIN'] . '_author_' . $crea['author']['name'],
      ],
      'audience' => [
        '@type' => "PeopleAudience",
        'suggestedMinAge' => 18
      ],
      'isAccessibleForFree' => true,
      'license' => 'https://creativecommons.org/licenses/by-nc-nd/3.0/deed.fr',
      'mainEntity' => [
        '@type' => "ItemList",
        'numberOfItems' => 0,
        'itemListElement' => [],
      ],
    ];
    return $col;
  }
  public static function setDate(int $time)
  {
    return date('Y-m-d', $time);
  }
  public static function genHistoire()
  {
    if(!is_file($_ENV['CREATIVEWORK']))
      return false;
    $scheme = isset($_SERVER['REQUEST_SCHEME']) ? $_SERVER['REQUEST_SCHEME'] : 'https';

    $crea = opt::yaml_parse_file($_ENV['CREATIVEWORK']);
    $ar = [
      '@context' => 'https://schema.org',
      '@type' => 'Book',
      '@id' => $scheme . '://' . $_ENV['DOMAIN'] . '_histoire_',
      'name' => '',
      'keywords' => [],
      'description' => '',
      'author' => [
        '@type' => 'Person',
        '@id' => $scheme . '://' . $_ENV['DOMAIN'] . '_author_' . $crea['author']['name'],
        'name' => $crea['author']['name'],
        'url' => $scheme . '://' . $_ENV['DOMAIN']
      ],
      'genre' => [],
      'image' => [],
      'isPartOf' => [],
      'url' => '',
      'datePublished' => '',
      'dateModified' => '',
      'inLanguage' => 'fr',
      'publisher' => [
        '@type' => 'Organization',
        '@id' => $scheme . '://' . $_ENV['DOMAIN'] . '_author_' . $crea['author']['name'],
        'name' => $_ENV['SITE_TITLE'],
        'url' => $scheme . '://' . $_ENV['DOMAIN']
      ],
      'audience' => [
        '@type' => "PeopleAudience",
        'suggestedMinAge' => 18
      ],
      'isAccessibleForFree' => true,
      'license' => 'https://creativecommons.org/licenses/by-nc-nd/3.0/deed.fr'
    ];
    return $ar;
  }
}