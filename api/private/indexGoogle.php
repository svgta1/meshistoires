<?php
use Google\Client;
use Meshistoires\Api\backend\db;
use Meshistoires\Api\utils\utilsMenu;

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
      $this->auth = $client->authorize();

      if(!is_dir($_ENV['INDEX_GOOGLE_DATAPATH']))
        mkdir($_ENV['INDEX_GOOGLE_DATAPATH']);
      $lastUpdateF = $_ENV['INDEX_GOOGLE_DATAPATH'] . '/' . self::$lastUpdateF;
      if(!is_file($lastUpdateF))
        file_put_contents($lastUpdateF, json_encode([
          'lastUpdate' => 0
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
    $list = null;
    if(is_null($list))
      return ['INDEX_GOOGLE' => 'No List'];

    /* todo un jour */

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
      $response = $this->auth->post($this->endpoint, [ 'body' => $content]);
      if($response->getStatusCode() !== 200)
        return [
          'status' => $response->getStatusCode(),
          'error' => $response->getReasonPhrase(),
        ];
      print_r($response->getBody()->getContents());
      print_r(PHP_EOL);
    }

    $lastUpdateF = $_ENV['INDEX_GOOGLE_DATAPATH'] . '/' . self::$lastUpdateF;
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
      $data = utilsMenu::getHistoireData($c->uuid);
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
      $data = utilsMenu::getCollectionData($c->uuid);
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

$index = new indexGoogle();

$ret = [
  'toIndex' => $index->index(),
  'toDelete' => $index->delete()
];
echo json_encode($ret, JSON_PRETTY_PRINT);
