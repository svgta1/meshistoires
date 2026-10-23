<?php
use Meshistoires\Api\backend\db;
use GuzzleHttp\Client;
use Meshistoires\Api\utils\getGrist;

require dirname(__FILE__, 2) . '/vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(dirname(__FILE__, 2));
$dotenv->load();

class traitement
{
  public function __construct()
  {
    $this->dir = $_ENV['BASE_DIR'] . '/data/tmp';
    if(!is_dir($this->dir))
      mkdir($this->dir);
    $this->file = $this->dir . '/lectures.json';
    $this->dbRes = db::get_res();
    $this->client = new Client();
    $this->grist = new getGrist(
      api_key: $_ENV['GRIST_API_KEY'],
      api_id: $_ENV['GRIST_API_ID'],
      api_uri: $_ENV['GRIST_API_URI']
    );
    $this->prepare();
  }
  public function run()
  {
    $list = $this->getFileContents();
    $rec = new stdClass;
    $rec->records = [];
    foreach($list as $l){
      $r = new stdClass;
      $r->require = new stdClass;
      $r->require->gristId = $l->gristuuid;
      $r->fields = new stdClass;
      $r->fields->Site = (int)$l->lectures;
      $rec->records[] = $r;
    }
    $update = $this->client->put($this->grist->uri['oeuvres'],[
      'headers' => [
        'Authorization' => "Bearer " . $_ENV['GRIST_API_KEY']
      ],
      'json' => $rec
    ]);
    return $rec;
  }
  public function getFromGrist()
  {
    $ret = $this->grist->getClient($this->grist->uri['oeuvres'])->records;
    return $ret;
  }
  public function getFileContents()
  {
    $c = file_get_contents($this->file);
    return json_decode($c);
  }
  private function prepare()
  {
    if(!is_file($this->file))
      return $this->getLectures();

    $t = 60 * 60 * 24;
    if(filemtime($this->file) + $t < time())
      return $this->getLectures();
  }
  private function getLectures()
  {
    print_r('Récup d\'Atra' . PHP_EOL);
    $cursor = $this->dbRes['class']::get(
        col: "oeuvres"
      );
    $ret = [];
    foreach($cursor as $doc){
      $resp = $this->client->get($doc->distanteLink, [
        'headers' => [
          'User-Agent' => "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36",
          'Cookie' => 'not_a_bot=' . $_ENV['ATRA_COOKIE_BOT'] . ';PHPSESSID=' . $_ENV['ATRA_COOKIE_SESSID'],
        ],
        'debug' => false,
        'http_errors' => true
      ]);
      $html = $resp->getBody()->getContents();
      preg_match('/\<span\>(\d.*)&nbsp;lectures/', $html, $matches);
      $doc->lectures = $matches[1];
      $ret[] = $doc;
    }
    file_put_contents($this->file, json_encode($ret));
  }
}


$t = new traitement();
print_r(json_encode($t->run(), JSON_PRETTY_PRINT));
