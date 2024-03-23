<?php
use GuzzleHttp\Client;
use Meshistoires\Api\backend\db;
use Meshistoires\Api\utils\seo;

require dirname(__FILE__, 2) . '/vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(dirname(__FILE__, 2));
$dotenv->load();

class indexNow
{
  private static $delay = 7; //jours
  private static $menus = [];
  private static $lastUpdateF = 'lastUpdate.json';

  public function __construct()
  {
    $this->client = new Client();
    $this->dbRes = db::get_res();
    if(isset($_ENV['INDEX_NOW'])){
      if(!is_dir($_ENV['INDEX_NOW_DATAPATH']))
        mkdir($_ENV['INDEX_NOW_DATAPATH']);
      $lastUpdateF = $_ENV['INDEX_NOW_DATAPATH'] . '/' . self::$lastUpdateF;
      if(!is_file($lastUpdateF))
        file_put_contents($lastUpdateF, json_encode([
          'lastUpdate' => time() - self::$delay * 24 * 60 * 60,
          'lastDelete' => 0
        ]));

      $lU = json_decode(file_get_contents($lastUpdateF));
      $this->lastUpdate = $lU->lastUpdate;
      if(!isset($lU->lastDelete))
        $this->lastDelete = 0;
      else
        $this->lastDelete = $lU->lastDelete;
    }
  }
  public function delete(): array
  {
    if(!isset($_ENV['INDEX_NOW']))
      return ['INDEX_NOW' => 'No parameters'];
    if($_ENV['INDEX_NOW'] == 0)
      return ['INDEX_NOW' => 'Not activated'];
    $list = $this->toDelete();
    if(is_null($list))
      return ['INDEX_NOW' => 'No List'];
    return $list;
  }
  private function toDelete(): ?array
  {
    $res = [];
    $arts = $this->artToDelete();
    /*foreach($arts as $art)
      $res[] = $art;*/
    $menus = $this->menuToDelete();
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
        'deleted' => true,
        'dateUpdate' => ['$gte' => $this->lastDelete]
      ],
      projection: ['name']
    );
    foreach($menuC as $menu){
      $res[] = 'https://' . $_ENV['DOMAIN'] . '/' . seo::seofy($menu->name);
    }
    if(count($res) === 0)
      return null;
    return $res;
  }
  private function artToDelete()
  {
    $res = [];
    $artC = $this->dbRes['class']::get(
      col: 'articles',
      param: [
        'deleted' => true,
        'dateUpdate' => ['$gte' => $this->lastDelete]
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
    if(!isset($_ENV['INDEX_NOW']))
      return ['INDEX_NOW' => 'No parameters'];
    if($_ENV['INDEX_NOW'] == 0)
      return ['INDEX_NOW' => 'Not activated'];
    $list = $this->toIndex();
    if(is_null($list))
      return ['INDEX_NOW' => 'No List'];

    $params = [
      'host' => $_ENV['DOMAIN'],
      'key' => $_ENV['INDEX_NOW_KEY'],
      'keyLocation' => 'https://' . $_ENV['DOMAIN'] . '/' . $_ENV['INDEX_NOW_KEY'] . '.txt',
      'urlList' => $list,
    ];
    $resp = $this->client->post($_ENV['INDEX_NOW_API'],[
      'json' => $params
    ]);
    $lastUpdateF = $_ENV['INDEX_NOW_DATAPATH'] . '/' . self::$lastUpdateF;
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

$index = new indexNow();

$ret = [
  'toIndex' => $index->index(),
  'toDelete' => $index->delete()
];
echo json_encode($ret, JSON_PRETTY_PRINT);