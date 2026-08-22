<?php
use Meshistoires\Api\controller\v2r0\menu;
use Meshistoires\Api\utils\siteInfo;
use Meshistoires\Api\utils\utilsMenu;
use Meshistoires\Api\utils\opt;
use Meshistoires\Api\utils\CreativeWork;
use Meshistoires\Api\controller\v2r0\menu as ctrlMenu;

require dirname(__FILE__, 2) . '/api/vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(dirname(__FILE__, 2) . '/api/');
$dotenv->load();
$error404 = false;

class setIndex
{
  private $aff = [];
  private $contents = "";
  private $ariane = "";
  private $config = null;
  private $version = null;
  private $menu = null;
  private $reqUri = [];
  private $firstKey = 0;
  private $nextKey = 0;
  private $ctrlMenu = null;

  public function __construct()
  {
    if(in_array($_SERVER['REQUEST_METHOD'], [
      'POST',
      'PUT',
      'DELETE',
      'PATCH',
      'CONNECT'
    ])){
      http_response_code(403);
      header('Cache-Control: no-cache');
      header("X-Robots-Tag: all", true);
    }
    if($_SERVER['REQUEST_URI'] == '/'){
      header("HTTP/1.1 308 Redirect Permanently");
      header('Location: ' . $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['DOMAIN'] . '/accueil');
      die();
    }
    $this->config = json_decode(file_get_contents('config/config.json'));
    $this->version = json_decode(file_get_contents('config/version.json'));
    $this->menu = menu::_menuList();
    $this->reqUri = explode('/',$_SERVER['REQUEST_URI']);
    unset($this->reqUri[0]);
    $this->firstKey = array_key_first($this->reqUri);
    $this->ctrlMenu = new ctrlMenu([], []);
  }
  public function verifyMenu()
  {
    if(!isset($this->menu['list'][$this->reqUri[$this->firstKey]])){
      http_response_code(404);
      return false;
    }
    if(count($this->reqUri) == 1 && $this->reqUri[$this->firstKey] !== "images"){
      $data = $this->ctrlMenu->_get($this->reqUri[$this->firstKey]);
      $this->ariane = $data['ariane'];
      $this->contents = $data['template'];
      $this->aff = $data['data']['meta'];
      if(isset($data['dateCreate']))
        $this->aff['datePublished'] = $data['dateCreate'];
      if(isset($data['dateUpdate']))
        $this->aff['dateModified'] = $data['dateUpdate'];
      $cat = utilsMenu::getGenre();
      $this->aff['genre'] = $cat;
    }
    
    $this->nextKey = $this->firstKey;
    return true;
  }
  public function getAff()
  {
    return $this->aff;
  }
  public function setContents()
  {
    $contents = opt::file_get_contents($_ENV['INDEXHTML']);
    if(isset($this->aff['image']))
      $contents = str_replace('##image##', $this->aff['image'], $contents);
    if(isset($this->aff['title']))
      $contents = str_replace('##title##', $this->aff['title'], $contents);
     if(isset($this->aff['description']))
      $contents = str_replace('##description##', $this->aff['description'], $contents);
    $contents = str_replace('##siteTitle##', $_ENV['SITE_TITLE'], $contents);
    $contents = str_replace('##SiteDescription##', $_ENV['SITE_DESC'], $contents);
    $contents = str_replace('##url##', $_SERVER['REQUEST_SCHEME'] . '://' . $_ENV['DOMAIN'] . $_SERVER['REQUEST_URI'], $contents);
    if($this->config->modeDev)
      $version = time();
    else
      $version = $this->version->version;
    $contents = str_replace('##version##', $version, $contents);
    $contents = str_replace('##components##', $this->config->components, $contents);
    $contents = str_replace('##menu##', $this->menu['template'], $contents);
    $contents = str_replace('##social##', siteInfo::getSocial(), $contents);
    if(isset($this->aff['keywords']))
      $contents = str_replace('##keywords##', $this->aff['keywords'], $contents);
    $contents = str_replace('##ariane##', $this->ariane, $contents);
    $contents = str_replace('##contents##', $this->contents, $contents);
    $creative = CreativeWork::gen();
    $creative['name'] = $this->aff['title'];
    $creative['description'] = $this->aff['description'];
    $creative['genre'] = [];
    if(isset($this->aff['genre']))
      $creative['genre'] = $this->aff['genre'];
    if(!isset($this->aff['collection'])){
      unset($creative['inCollection']);
    }else{
      $creative['inCollection']['name'] = $this->aff['collection']['name'];
      $creative['inCollection']['url'] = $this->aff['collection']['url'];
      $creative['@type'][] = 'Book';
    }
    $creative['url'] = $_SERVER['REQUEST_SCHEME'] . '://' . $_ENV['DOMAIN'] . $_SERVER['REQUEST_URI'];
    $creative['image'][] = $this->aff['image'];
    if(str_contains($this->aff['image'], 'imageThumb300')){
      $creative['image'][] = str_replace('imageThumb300', 'image', $this->aff['image']);
      $creative['image'][] = str_replace('imageThumb300', 'imagethumb', $this->aff['image']);
    }
    if(isset($this->aff['datePublished']))
      $creative['datePublished'] = date('Y-m-d', (int)$this->aff['datePublished']);
    if(isset($this->aff['dateModified']))
      $creative['dateModified'] = date('Y-m-d',(int)$this->aff['dateModified']);

    $contents = str_replace('##creativework##', json_encode($creative, JSON_PRETTY_PRINT), $contents);

    //echo '<pre>'; print_r($creative); die();

    return $contents;
  }
  public function getFirstKeyName()
  {
    $this->nextKey = $this->firstKey;
    return $this->menu['list'][$this->reqUri[$this->firstKey]]['uuid'];
  }
  public function isNextKeyExist()
  {
    $this->nextKey += 1;
    return isset($this->reqUri[$this->nextKey]);
  }
  public function getNextKeyName()
  {
    $this->nextKey += 1;
    return $this->reqUri[$this->nextKey];
  }
  public function accueil()
  {
    $name = $this->getNextKeyName();
    if($name == 'error404'){
      $data = utilsMenu::errorPage('error403', 'Erreur 404');
      $this->ariane = $data['ariane'];
      $this->contents = $data['template'];
      $this->aff = $data['data']['meta'];
      $this->aff['genre'] = "Erreur 404";
      return;
    }
    if($name == 'error403'){
      $data = utilsMenu::errorPage('error403', 'Erreur 403');
      $this->ariane = $data['ariane'];
      $this->contents = $data['template'];
      $this->aff = $data['data']['meta'];
      $this->aff['genre'] = "Erreur 403";
      return;
    }
    if($name == 'images'){
      $data = $this->ctrlMenu->_getImagesParams();
      $this->ariane = utilsMenu::ariane($data['data']['ariane']);
      $this->contents = $data['template'];
      $this->aff = $data['data']['meta'];
      $this->aff['genre'] = "Acueil images";
      return;
    }
    http_response_code(404);
  }
  public function collections()
  {
    $list = utilsMenu::getSeoCollections();
    $name = $this->getNextKeyName();
    if(!isset($list[$name])){
      http_response_code(404);
      return;
    }
    $uuid = $list[$name];
    $data = utilsMenu::getCollectionData($uuid);
    $this->ariane = utilsMenu::ariane($data['ariane']);
    $this->contents = utilsMenu::getCollectionHtml($data);
    //echo '<pre>'; print_r($data); die();
    $this->aff = $data['meta'];
    $this->aff['datePublished'] = $data['doc']->dateCreate;
    $this->aff['dateModified'] = $data['doc']->dateUpdate;
    $this->aff['genre'] = [];
    foreach($data['categories'] as $cat){
      $catData = utilsMenu::getCategorieData($cat);
      $this->aff['genre'][] = $catData['doc']->name;
    }
  }
  public function histoires()
  {
    $name = $this->getNextKeyName();
    if($name == 'categories'){
      return $this->categories();
    }
    $list = utilsMenu::getSeoHistoires();
    if(!isset($list[$name])){
      http_response_code(404);
      return;
    }
    $uuid = $list[$name];
    $data = utilsMenu::getHistoireData($uuid);
    $tpl = utilsMenu::getHistoireHtml($data);
    $this->ariane = utilsMenu::ariane($data['ariane']);
    $this->contents = $tpl;
    $this->aff = $data['meta'];
    $genre = [];
    foreach($data['categories'] as $k => $v)
      $genre[] = $k;
    $this->aff['genre'] = $genre;
    $this->aff['collection'] = [
      'name' => $data['collection']['doc']->name,
      'url' =>  $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['DOMAIN'] . $data['collection']['ariane'][1]['uri'],
    ];
    $this->aff['datePublished'] = $data['doc']->dateCreate;
    $this->aff['dateModified'] = $data['doc']->dateUpdate;
    $cat = [];
    foreach($data['categories'] as $name => $ar){
      $cat[] = $name;
    }
    $t = explode(' ', $data['doc']->title);
    foreach($t as $sub){
      if(strlen($sub) > 4)
        $cat[] = $sub;
    }
  }
  public function categories()
  {
    $name = $this->getNextKeyName();
    $list = utilsMenu::getSeoCategories();
        if(!isset($list[$name])){
      http_response_code(404);
      return;
    }
    $uuid = $list[$name];
    $data = utilsMenu::getCategorieData($uuid);
    $this->ariane = utilsMenu::ariane($data['ariane']);
    $this->contents = utilsMenu::getCategorieHtml($data);
    $this->aff = $data['meta'];
    $this->aff['genre'] = $data['doc']->name;
    $this->aff['datePublished'] = $data['doc']->dateCreate;
    $this->aff['dateModified'] = $data['doc']->dateUpdate;
  }
}

$index = new setIndex();
if(!$index->verifyMenu()){
  echo $index->setContents();
  die();
}

if(!$index->isNextKeyExist()){
  echo $index->setContents();
  die();
}

$menu = $index->getFirstKeyName();

if($menu == 'accueil'){
  $index->accueil();
  echo $index->setContents();
  die();
}

if($menu == 'collections'){
  $index->collections();
  echo $index->setContents();
  die();
}
if($menu == 'histoires'){
  $index->histoires();
  echo $index->setContents();
  die();
}

echo $index->setContents();