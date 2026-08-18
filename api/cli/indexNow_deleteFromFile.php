<?php
use GuzzleHttp\Client;
use Meshistoires\Api\backend\db;
use Meshistoires\Api\utils\utilsMenu;

require dirname(__FILE__, 2) . '/vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(dirname(__FILE__, 2));
$dotenv->load();

class indexNow
{
  private static $lastDeleteF = 'lastDelete.json';
  private static $fileName = '';

  public function __construct($filename)
  {
    $this->client = new Client();
    $this->dbRes = db::get_res();
    if(isset($_ENV['INDEX_NOW'])){
      $lastDeleteF = $_ENV['INDEX_NOW_DATAPATH'] . '/' . self::$lastDeleteF;
      if(!is_file($lastDeleteF))
        file_put_contents($lastDeleteF, json_encode([
          'lastDelete' => 0
        ]));

      $lD = json_decode(file_get_contents($lastDeleteF));
      $this->lastDelete = $lD->lastDelete;
     self::$fileName = $_ENV['INDEX_NOW_DATAPATH'] . '/' . $filename;
    }
  }
  public function delete(): array
  {
    $list = $this->_getList();
    if(is_null($list))
      return ['INDEX_NOW' => 'No List'];

    $params = [
      'host' => $_ENV['DOMAIN'],
      'key' => $_ENV['INDEX_NOW_KEY'],
      'keyLocation' => 'https://' . $_ENV['DOMAIN'] . '/' . $_ENV['INDEX_NOW_KEY'] . '.txt',
      'urlList' => $list['list'],
    ];
    $resp = $this->client->post($_ENV['INDEX_NOW_API'],[
      'json' => $params
    ]);
    $ret = $resp->getBody()->getContents;
    print_r($ret);
    print_r(PHP_EOL);
    $lastDeleteF = $_ENV['INDEX_NOW_DATAPATH'] . '/' . self::$lastDeleteF;
    file_put_contents($lastDeleteF, json_encode([
      'lastDelete' => time()
    ]));
    return $list;
  }
  private function _getList()
  {
    if(!is_file(self::$fileName))
      return null;
    $contents = file_get_contents(self::$fileName);
    $ar = explode(PHP_EOL, $contents);
    foreach($ar as $k => $v){
      if(substr($v, 0, 4) != 'http')
        unset($ar[$k]);
    }
    if(count($ar) == 0)
      return null;
    $ret = [
      'count' => count($ar),
      'list' => $ar
    ];
    return $ret;
  }
}


$index = new indexNow('delete.csv');

$ret = [
  'toDelete' => $index->delete()
];
echo json_encode($ret, JSON_PRETTY_PRINT);
