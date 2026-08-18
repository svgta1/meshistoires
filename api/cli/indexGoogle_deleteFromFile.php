<?php
use Google\Client;

require dirname(__FILE__, 2) . '/vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(dirname(__FILE__, 2));
$dotenv->load();

class indexGoogle
{
  private static $lastDeleteF = 'lastDelete.json';
  private static $fileName = '';

  public function __construct($filename)
  {
    if(isset($_ENV['INDEX_GOOGLE'])){
      $client = new Client();
      $client->setAuthConfig($_ENV['INDEX_GOOGLE_KEY']);
      $client->addScope('https://www.googleapis.com/auth/indexing');
      $this->endpoint = 'https://indexing.googleapis.com/v3/urlNotifications:publish';
      $this->auth = $client->authorize();

      $lastDeleteF = $_ENV['INDEX_GOOGLE_DATAPATH'] . '/' . self::$lastDeleteF;
      if(!is_file($lastDeleteF))
        file_put_contents($lastDeleteF, json_encode([
          'lastDelete' => 0
        ]));

      $lD = json_decode(file_get_contents($lastDeleteF));
      $this->lastDelete = $lD->lastDelete;
      self::$fileName = $_ENV['INDEX_GOOGLE_DATAPATH'] . '/' . $filename;
    }
  }
  public function delete(): array
  {
    $list = $this->_getList();
    if(is_null($list))
      return ['INDEX_GOOGLE' => 'No List'];

    foreach($list['list'] as $url){
      $content = json_encode([
        'url' => $url,
        'type' => 'URL_DELETED'
      ]);
      $response = $this->auth->post($this->endpoint, [ 'body' => $content ]);
      $status_code = $response->getStatusCode();
      print_r($response->getBody()->getContents());
      print_r(PHP_EOL);
      if($status_code >= 300)
        return ['error' => $response->getStatusCode()];
    }

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
    $a = [];
    foreach($ar as $k => $v){
      if(substr($v, 0, 4) != 'http'){
        unset($ar[$k]);
        continue;
      }
      $a[] = trim($v);        
    }
    if(count($a) == 0)
      return null;
    $ret = [
      'count' => count($a),
      'list' => $a
    ];
    return $ret;
  }
}


$index = new indexGoogle('delete.csv');

$ret = [
  'toDelete' => $index->delete()
];
echo json_encode($ret, JSON_PRETTY_PRINT);
