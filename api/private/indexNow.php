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
    if(!is_dir($_ENV['INDEX_NOW_DATAPATH']))
      mkdir($_ENV['INDEX_NOW_DATAPATH']);
    $lastUpdateF = $_ENV['INDEX_NOW_DATAPATH'] . '/' . self::$lastUpdateF;
    if(!is_file($lastUpdateF))
      file_put_contents($lastUpdateF, json_encode([
        'lastUpdate' => time() - self::$delay * 24 * 60 * 60
      ]));

    $lU = json_decode(file_get_contents($lastUpdateF));
    $this->lastUpdate = $lU->lastUpdate;
  }
  public function index(): array
  {
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
        if($art->title === "")
          continue;
      }
      $menu = self::$menus[$art->parent];
      $res[] = 'https://' . $_ENV['DOMAIN'] . '/' . seo::seofy($menu->name) . '/' . seo::seofy($art->title);
    }
    if(count($res) === 0)
      return null;
    return $res;
  }
}

$index = new indexNow();
$ret = $index->index();
echo json_encode($ret, JSON_PRETTY_PRINT);
