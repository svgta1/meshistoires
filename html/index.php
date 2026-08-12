<?php
use Meshistoires\Api\controller\v2r0\menu;
use Meshistoires\Api\utils\siteInfo;
use Meshistoires\Api\utils\utilsMenu;

require dirname(__FILE__, 2) . '/api/vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(dirname(__FILE__, 2) . '/api/');
$dotenv->load();
$error404 = false;

class setIndex
{
  private $aff = [];
  private $config = null;
  private $menu = null;
  private $reqUri = [];
  private $firstKey = 0;
  private $nextKey = 0;

  public function __construct()
  {
    $this->config = json_decode(file_get_contents('config/config.json'));
    $this->aff = [
      'title' => $_ENV['SITE_TITLE'],
      'desc' => $_ENV['SITE_DESC'],
      'image' => $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['DOMAIN'] . $this->config->components . 'img/inspiration.webp',
    ];
    $this->menu = menu::_menuList();
    if($_SERVER['REQUEST_URI'] == '/'){
      header('Location: ' . $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['DOMAIN'] . '/accueil');
      die();
    }
    $this->reqUri = explode('/',$_SERVER['REQUEST_URI']);
    unset($this->reqUri[0]);
    $this->firstKey = array_key_first($this->reqUri);
  }
  public function verifyMenu()
  {
    if(!isset($this->menu['list'][$this->reqUri[$this->firstKey]])){
      http_response_code(404);
      return false;
    }
    $this->aff['title'] = $this->menu['list'][$this->reqUri[$this->firstKey]]['name'];
    $this->aff['desc'] = file_get_contents($_ENV['HTML_TPL'] . '/' . $this->menu['list'][$this->reqUri[$this->firstKey]]['uuid'] . '.txt');
    $this->nextKey = $this->firstKey;
    return true;
  }
  public function getAff()
  {
    return $this->aff;
  }
  public function setContents()
  {
    $contents = file_get_contents('index.html.template');
    $contents = str_replace('##image##', $this->aff['image'], $contents);
    $contents = str_replace('##title##', $this->aff['title'], $contents);
    $contents = str_replace('##description##', $this->aff['desc'], $contents);
    $contents = str_replace('##siteTitle##', $_ENV['SITE_TITLE'], $contents);
    $contents = str_replace('##SiteDescription##', $_ENV['SITE_DESC'], $contents);
    $contents = str_replace('##url##', $_SERVER['REQUEST_SCHEME'] . '://' . $_ENV['DOMAIN'], $contents);
    if($this->config->modeDev)
      $version = time();
    else
      $version = $this->config->version;
    $contents = str_replace('##version##', $version, $contents);
    $contents = str_replace('##components##', $this->config->components, $contents);
    $contents = str_replace('##menu##', $this->menu['template'], $contents);
    $contents = str_replace('##social##', siteInfo::getSocial(), $contents);
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
      $this->aff['title'] = 'Erreur 404';
      $this->aff['desc'] = 'La page à laquelle vous essayer d\'accéder n\'existe pas ou n\'existe plus.';
      return;
    }
    if($name == 'error403'){
      $this->aff['title'] = 'Erreur 403';
      $this->aff['desc'] = 'La page à laquelle vous essayer d\'accéder est réservée à l\'administration.';
      return;
    }
    if($name == 'images'){
      $this->aff['title'] = 'Images';
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
    $this->aff['title'] = $data['doc']->name;
    $this->aff['desc'] = $data['doc']->desc;
    $uri = $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['DOMAIN'] . $this->config->api->uri . $this->config->api->version;
    $this->aff['image'] = $uri . '/imageThumb300/' . $data['doc']->imageUuid;
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
    $this->aff['title'] = $data['doc']->title;
    $this->aff['desc'] = $data['doc']->desc;
    $uri = $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['DOMAIN'] . $this->config->api->uri . $this->config->api->version;
    $this->aff['image'] = $uri . '/imageThumb300/' . $data['doc']->imageUuid;
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
    $this->aff['title'] = 'Catégorie ' . $data['doc']->name;
    $this->aff['desc'] = ' Liste des histoires de la catégorie ' . $data['doc']->name;
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

