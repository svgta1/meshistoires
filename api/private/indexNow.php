<?php
use GuzzleHttp\Client;
use Meshistoires\Api\backend\db;
use Meshistoires\Api\controller\v2r0\menu;

require dirname(__FILE__, 2) . '/vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(dirname(__FILE__, 2));
$dotenv->load();

class indexNow
{
  private static $delay = 7; //jours
  private static $menus = [];
  private static $lastUpdateF = 'lastUpdate.json';
  private static $lastDeleteF = 'lastDelete.json';

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
          'lastUpdate' => 0
        ]));

      $lastDeleteF = $_ENV['INDEX_NOW_DATAPATH'] . '/' . self::$lastDeleteF;
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
    if(!isset($_ENV['INDEX_NOW']))
      return ['INDEX_NOW' => 'No parameters'];
    if($_ENV['INDEX_NOW'] == 0)
      return ['INDEX_NOW' => 'Not activated'];
    $list = null;
    if(is_null($list))
      return ['INDEX_NOW' => 'No List'];

    /* todo un jour */

    $params = [
      'host' => $_ENV['DOMAIN'],
      'key' => $_ENV['INDEX_NOW_KEY'],
      'keyLocation' => 'https://' . $_ENV['DOMAIN'] . '/' . $_ENV['INDEX_NOW_KEY'] . '.txt',
      'urlList' => $list,
    ];
    $resp = $this->client->post($_ENV['INDEX_NOW_API'],[
      'json' => $params
    ]);
    $lastDeleteF = $_ENV['INDEX_NOW_DATAPATH'] . '/' . self::$lastDeleteF;
    file_put_contents($lastDeleteF, json_encode([
      'lastDelete' => time()
    ]));
    return $list;
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
    print_r('Response code ' . $resp->getStatusCode() . PHP_EOL);
    print_r('Retour ' . $resp->getBody()->getContents());
    print_r(PHP_EOL);
    $lastUpdateF = $_ENV['INDEX_NOW_DATAPATH'] . '/' . self::$lastUpdateF;
    file_put_contents($lastUpdateF, json_encode([
      'lastUpdate' => time()
    ]));
    return $list;
  }
  private function toIndex(): ?array
  {
    $res = [
      'histoires' => $this->getHistoires(),
      'collections' => $this->getCollections(),
    ];
    if(is_array($res['histoires']) && is_array($res['collections']))
      return array_merge($res['histoires'], $res['collections']);
    if(is_array($res['histoires']))
      return $res['histoires'];
    if(is_array($res['collections']))
      return $res['collections'];
    return null;
  }
  private function getHistoires()
  {
    $ret = $this->_getList('oeuvres');
    if($ret["count"] == 0)
      return null;
    $list = [];
    foreach($ret['cursor'] as $c){
      $data = menu::getHistoireData($c->uuid);
      $url = 'https://' . $_ENV['DOMAIN'] . $data['ariane'][1]['uri'];
      $list[] = $url;
    }
    return $list;
  }
  private function getCollections()
  {
    $ret = $this->_getList('collections');
    if($ret["count"] == 0)
      return null;
        $list = [];
    foreach($ret['cursor'] as $c){
      $data = menu::getCollectionData($c->uuid);
      $url = 'https://' . $_ENV['DOMAIN'] . $data['ariane'][1]['uri'];
      $list[] = $url;
    }
    return $list;
  }
  private function _getList($col)
  {
    return [
      'count' => $this->dbRes['class']::count(
          col: $col,
          param : [
            'dateUpdate' => ['$gte' => $this->lastUpdate]
          ]
        ),
      'cursor' => $this->dbRes['class']::get(
          col: $col,
          param : [
            'dateUpdate' => ['$gte' => $this->lastUpdate]
          ],
          projection: ['uuid']
        )
    ];
  }
}

$index = new indexNow();

$ret = [
  'toIndex' => $index->index(),
  'toDelete' => $index->delete()
];
echo json_encode($ret, JSON_PRETTY_PRINT);
