<?php
use Meshistoires\Api\controller\v2r0\menu;
use Meshistoires\Api\utils\siteInfo;
use Meshistoires\Api\utils\utilsMenu;
use Meshistoires\Api\utils\opt;
use Meshistoires\Api\controller\v2r0\menu as ctrlMenu;

require dirname(__FILE__, 2) . '/api/vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(dirname(__FILE__, 2) . '/api/');
$dotenv->load();
$error404 = false;

class setIndex
{
  private $aff = [];
  private $config = null;
  private $version = null;
  private $menu = null;
  private $reqUri = [];
  private $firstKey = 0;
  private $nextKey = 0;
  private $ctrlMenu = null;

  public function __construct()
  {
    $this->config = json_decode(file_get_contents('config/config.json'));
    $this->version = json_decode(file_get_contents('config/version.json'));
    $this->menu = menu::_menuList();
    if($_SERVER['REQUEST_URI'] == '/'){
      header('Location: ' . $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['DOMAIN'] . '/accueil');
      die();
    }
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
      $ret = $this->ctrlMenu->_get($this->reqUri[$this->firstKey]);
      $this->aff = $ret['data']['meta'];
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
      $data = utilsMenu::errorPage(404, 'Erreur 404')['data'];
      $this->aff = $data['meta'];
      return;
    }
    if($name == 'error403'){
      $data = utilsMenu::errorPage(403, 'Erreur 403')['data'];
      $this->aff = $data['meta'];
      return;
    }
    if($name == 'images'){
      $data = $this->ctrlMenu->_getImagesParams();
      $this->aff = $data['data']['meta'];
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
    $this->aff = $data['meta'];
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
    $this->aff = $data['meta'];
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
    $this->aff = $data['meta'];
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


