<?php
use Google\Client;
use Google\Service\SearchConsole;
use Meshistoires\Api\backend\db;

require dirname(__FILE__, 2) . '/vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(dirname(__FILE__, 2));
$dotenv->load();

class indexGoogle
{
  private static $lastUpdateF = 'lastUpdate.json';
  private $searchConsoleService = null;

  public function __construct()
  {
    $this->dbRes = db::get_res();
    if(isset($_ENV['INDEX_GOOGLE'])){
      $client = new Client();
      $client->setAuthConfig($_ENV['INDEX_GOOGLE_KEY']);
      $client->addScope(SearchConsole::WEBMASTERS);
      $this->searchConsoleService = new SearchConsole($client);


      if(!is_dir($_ENV['INDEX_GOOGLE_DATAPATH']))
        mkdir($_ENV['INDEX_GOOGLE_DATAPATH']);
      $lastUpdateF = $_ENV['INDEX_GOOGLE_DATAPATH'] . '/' . self::$lastUpdateF;
      if(!is_file($lastUpdateF))
        file_put_contents($lastUpdateF, json_encode([
          'lastUpdate' => 0
        ]));

      $lU = json_decode(file_get_contents($lastUpdateF));
      $this->lastUpdate = $lU->lastUpdate;
    }
  }

  public function index(): bool
  {
    if(!isset($_ENV['INDEX_GOOGLE']))
      return ['INDEX_GOOGLE' => 'No parameters'];
    if($_ENV['INDEX_GOOGLE'] == 0)
      return ['INDEX_GOOGLE' => 'Not activated'];
    if(!$this->toIndex())
      return false;

    if(isset($_SERVER["REQUEST_SCHEME"]))
      $shem = $_SERVER["REQUEST_SCHEME"];
    else
      $shem = 'https';

    $siteUrl = $shem . '://' . $_ENV['DOMAIN'];
    $sitemapUrl = $siteUrl . '/sitemap.xml';
    try {
        // Exécution de la requête PUT (submit)
        $this->searchConsoleService->sitemaps->submit($siteUrl, $sitemapUrl);
        
        // Log ou message de succès
        error_log("[Google API] Sitemap soumis avec succès le " . date('Y-m-d H:i:s'));
        
    } catch (\Exception $e) {
        // Gestion des erreurs (ex: problème de droits ou d'URL)
        error_log("[Google API] Erreur lors de la soumission : " . $e->getMessage());
        return false;
    }

    $lastUpdateF = $_ENV['INDEX_GOOGLE_DATAPATH'] . '/' . self::$lastUpdateF;
    file_put_contents($lastUpdateF, json_encode([
      'lastUpdate' => time()
    ]));
    return true;
  }
  private function toIndex(): bool
  {
    if($this->getHistoires())
      return true;
    if($this->getCollections())
      return true;

    return false;
  }
  private function getHistoires()
  {
    $ret = $this->_getList('oeuvres');
    if($ret['count'] == 0)
      return false;
    return true;
  }
  private function getCollections()
  {
    $ret = $this->_getList('collections');
    if($ret['count'] == 0)
      return false;
    return true;
  }
  private function _getList($col)
  {
    return [
      'count' => $this->dbRes['class']::count(
          col: $col,
          param : [
            'dateUpdate' => ['$gte' => $this->lastUpdate]
          ]
        )
    ];
  }
}

$index = new indexGoogle();

$ret = [
  'toIndex' => $index->index(),
];
echo json_encode($ret, JSON_PRETTY_PRINT);