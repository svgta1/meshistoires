<?php
use Googlex\Client;
use Meshistoires\Api\backend\db;
use Meshistoires\Api\utils\seo;

require dirname(__FILE__, 2) . '/vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(dirname(__FILE__, 2));
$dotenv->load();

class indexGoogle
{
  private static $delay = 7; //jours
  private static $menus = [];
  private static $lastUpdateF = 'lastUpdate.json';
  private static $lastDeleteF = 'lastDelete.json';

  public function __construct()
  {
    $this->dbRes = db::get_res();
    if(isset($_ENV['INDEX_GOOGLE'])){
      $client = new Client();
      $client->setAuthConfig($_ENV['INDEX_GOOGLE_KEY']);
      $client->addScope('https://www.googleapis.com/auth/indexing');
      $this->endpoint = 'https://indexing.googleapis.com/v3/urlNotifications:publish';
      $this->auth = $this->client->authorize();

      if(!is_dir($_ENV['INDEX_GOOGLE_DATAPATH']))
        mkdir($_ENV['INDEX_GOOGLE_DATAPATH']);
      $lastUpdateF = $_ENV['INDEX_GOOGLE_DATAPATH'] . '/' . self::$lastUpdateF;
      if(!is_file($lastUpdateF))
        file_put_contents($lastUpdateF, json_encode([
          'lastUpdate' => time() - self::$delay * 24 * 60 * 60
        ]));

      $lastDeleteF = $_ENV['INDEX_GOOGLE_DATAPATH'] . '/' . self::$lastDeleteF;
      if(!is_file($lastDeleteF))
        file_put_contents($lastDeleteF, json_encode([
          'lastDelete' => 0
        ]));

      $lU = json_decode(file_get_contents($lastUpdateF));
      $this->lastUpdate = $lU->lastUpdate;

      $lD = json_decode(file_get_contents($lastDeleteF));
      $this->lastDelete = $lD->lastDelete;
    }
  }
  public function delete(): array
  {
    if(!isset($_ENV['INDEX_GOOGLE']))
      return ['INDEX_GOOGLE' => 'No parameters'];
    if($_ENV['INDEX_GOOGLE'] == 0)
      return ['INDEX_GOOGLE' => 'Not activated'];
    $list = $this->toDelete();
    if(is_null($list))
      return ['INDEX_GOOGLE' => 'No List'];

    foreach($list as $url){
      $content = json_encode([
        'url' => $url,
        'type' => 'URL_DELETED'
      ]);
      $response = $this->auth->post($this->endpoint, [ 'body' => $content ]);
      $status_code = $response->getStatusCode();
      if($status_code >= 300)
        return ['error' => $response->getStatusCode()];
    }

    $lastDeleteF = $_ENV['INDEX_GOOGLE_DATAPATH'] . '/' . self::$lastDeleteF;
    file_put_contents($lastDeleteF, json_encode([
      'lastDelete' => time()
    ]));
    return $list;
  }
  private function toDelete(): ?array
  {
    $res = [];

    $menus = $this->menuToDelete();
    if(is_null($menus))
      return $menus;
    foreach($menus as $menu)
      $res[] = $menu;
    if(count($res) == 0)
      return null;
    return $res;
  }
  private function menuToDelete()
  {
    $res = [];
    $menuC = $this->dbRes['class']::get(
      col: 'menus',
      param: [
        'visible' => false,
        'dateUpdate' => ['$gte' => $this->lastDelete]
      ],
      projection: ['name', 'uuid']
    );
    foreach($menuC as $menu){
      $res[] = 'https://' . $_ENV['DOMAIN'] . '/' . seo::seofy($menu->name);
      $arts = $this->artToDelete($menu->uuid);
      if(!is_null($arts)){
        $res = array_merge($res, $arts);
      }
    }
    if(count($res) === 0)
      return null;
    
    return array_unique($res);
  }
  private function artToDelete(string $parent)
  {
    $res = [];
    $artC = $this->dbRes['class']::get(
      col: 'articles',
      param: [
        'parent' => $parent
      ],
      projection: ['title', 'uuid', 'parent']
    );
    foreach($artC as $art){
      if(!isset(self::$menus[$art->parent])){
        $menu = self::$menus[$art->parent] = $this->dbRes['class']::getOne(
          col: "menus",
          param: ['uuid' => $art->parent],
          projection: ['name', 'uuid']
        );
        if(is_null($menu))
          continue;
        self::$menus[$art->parent] = $menu;
      }
      $menu = self::$menus[$art->parent];
      if($art->title === "")
        $res[] = 'https://' . $_ENV['DOMAIN'] . '/' . seo::seofy($menu->name);
      else
        $res[] = 'https://' . $_ENV['DOMAIN'] . '/' . seo::seofy($menu->name) . '/' . seo::seofy($art->title);
    }
    if(count($res) === 0)
      return null;
    return $res;
  }
  public function index(): array
  {
    if(!isset($_ENV['INDEX_GOOGLE']))
      return ['INDEX_GOOGLE' => 'No parameters'];
    if($_ENV['INDEX_GOOGLE'] == 0)
      return ['INDEX_GOOGLE' => 'Not activated'];
    $list = $this->toIndex();
    if(is_null($list))
      return ['INDEX_GOOGLE' => 'No List'];

    foreach($list as $url){
      $content = json_encode([
        'url' => $url,
        'type' => 'URL_UPDATED'
      ]);
      $response = $this->auth->post($this->endpoint, [ 'body' => $content ]);
      $status_code = $response->getStatusCode();
      if($status_code >= 300)
        return ['error' => $response->getStatusCode()];
    }

    $lastUpdateF = $_ENV['INDEX_GOOGLE_DATAPATH'] . '/' . self::$lastUpdateF;
    file_put_contents($lastUpdateF, json_encode([
      'lastUpdate' => time()
    ]));
    return $list;
  }
  private function toIndex(): ?array
  {
    $res = $this->getArticles();
    if(is_null($res))
      return null;

    foreach(self::$menus as $menu)
      $res[] = 'https://' . $_ENV['DOMAIN'] . '/' . seo::seofy($menu->name);
    if(count($res) === 0)
      return null;
    return $res;
  }
  private function getArticles(): ?array
  {
    $res = [];
    $artC = $this->dbRes['class']::get(
      col: 'articles',
      param: [
        'visible' => true,
        'deleted' => false,
        'dateUpdate' => ['$gte' => $this->lastUpdate]
      ],
      projection: ['title', 'uuid', 'parent']
    );
    foreach($artC as $art){
      if(!isset(self::$menus[$art->parent])){
        $menu = self::$menus[$art->parent] = $this->dbRes['class']::getOne(
          col: "menus",
          param: ['uuid' => $art->parent, 'visible' => true, 'deleted' => false],
          projection: ['name', 'uuid']
        );
        if(is_null($menu))
          continue;
        self::$menus[$art->parent] = $menu;
      }
      $menu = self::$menus[$art->parent];
      if($art->title === "")
        $res[] = 'https://' . $_ENV['DOMAIN'] . '/' . seo::seofy($menu->name);
      else
        $res[] = 'https://' . $_ENV['DOMAIN'] . '/' . seo::seofy($menu->name) . '/' . seo::seofy($art->title);
    }
    if(count($res) === 0)
      return null;
    return $res;
  }
}

$index = new indexGoogle();

$ret = [
  'toIndex' => $index->index(),
  'toDelete' => $index->delete()
];
echo json_encode($ret, JSON_PRETTY_PRINT);
