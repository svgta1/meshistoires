<?php
namespace Meshistoires\Api\controller\v2r0;
use Meshistoires\Api\utils\trace;
use Meshistoires\Api\utils\response;
use Meshistoires\Api\backend\db;
use Meshistoires\Api\utils\utilsMenu;
use Meshistoires\Api\controller\v2r0\menu;

class sitemap
{
  public $dbRes = null;
  private $className = null;
  private $scopes = null;
  private $request = [];
  private $cacheId = "sitemap_";

  public function __construct(?array $scopes, array $request)
  {
    $this->scopes = $scopes;
    $this->request = $request;
    $c = \explode('\\', __CLASS__);
    $this->className = \array_pop($c);

    $this->dbRes = db::get_res();
    $xw = new \XMLWriter();
    $xw->openMemory();
    $xw->startDocument("1.0", "UTF-8");
    $xw->startElement('urlset');
    $xw->startAttribute('xmlns');
    $xw->text("http://www.sitemaps.org/schemas/sitemap/0.9");
    $xw->endAttribute();

    $this->xw = $xw;
    if(isset($_SERVER["REQUEST_SCHEME"]))
      $shem = $_SERVER["REQUEST_SCHEME"];
    else
      $shem = 'https';
    $this->uriSite = $shem .'://' . $_ENV['DOMAIN'];
  }
  public static function global()
  {
    $self = new self(null, []);
    $xw  = $self->xw;

    $xw->startAttribute('xmlns:image');
    $xw->text("http://www.google.com/schemas/sitemap-image/1.1");
    $xw->endAttribute();

    //menu
    $list = menu::_menuList();
    foreach($list['list'] as $menu){
      $uri = $self->uriSite . '/' . $menu['uri'];
      $xw->startElement('url');
      $xw->startElement('loc');
      $xw->text($uri);
      $xw->endElement(); //loc
      $xw->startElement('lastmod');
      $xw->text(\date('Y-m-d', $menu['update']));
      $xw->endElement(); //lastmod
      $xw->endElement(); //url
    }

    //catégories
    $cursor = $self->dbRes['class']::get(
      col: 'categories',
      order: ['dateUpdate' => -1],
      projection: ['uuid']
    );
    foreach($cursor as $c)
    {
      $data = utilsMenu::getCategorieData($c->uuid);
      if(!isset($data['histoires']))
        continue;
      if($data['histoires']['nbr'] == 0)
        continue;
      $uri = $self->uriSite . $data['ariane'][1]['uri'];
      $xw->startElement('url');
      $xw->startElement('loc');
      $xw->text($uri);
      $xw->endElement(); //loc
      $xw->startElement('lastmod');
      $xw->text(\date('Y-m-d', $data['doc']->dateUpdate));
      $xw->endElement(); //lastmod
      $xw->endElement(); //url
    }

    //histoires
    $cursor = $self->dbRes['class']::get(
      col: 'oeuvres',
      order: ['dateUpdate' => -1],
      projection: ['uuid']
    );
    foreach($cursor as $c)
    {
      $data = utilsMenu::getHistoireData($c->uuid);
      $uri = $self->uriSite . $data['ariane'][1]['uri'];
      $xw->startElement('url');
      $xw->startElement('loc');
      $xw->text($uri);
      $xw->endElement(); //loc
      $xw->startElement('image:image');
      $xw->startElement('image:loc');
      $imgUri = $self->uriSite . '/api/v2/image/' . $data['doc']->imageUuid;
      $xw->text($imgUri);
      $xw->endElement(); //image:loc
      $xw->endElement(); //image:image
      $dataImgs = utilsMenu::getAltImgData($c->uuid);
      if(!is_null($dataImgs)){
        foreach($dataImgs as $img){
          $xw->startElement('image:image');
          $xw->startElement('image:loc');
          $imgUri = $self->uriSite . '/api/v2/image/' . $img->uuid;
          $xw->text($imgUri);
          $xw->endElement(); //image:loc
          $xw->endElement(); //image:image
        }
      }
      $xw->startElement('lastmod');
      $xw->text(\date('Y-m-d', $data['doc']->dateUpdate));
      $xw->endElement(); //lastmod
      $xw->endElement(); //url
    }

    //collections
    $cursor = $self->dbRes['class']::get(
      col: 'collections',
      order: ['dateUpdate' => -1],
      projection: ['uuid']
    );
    foreach($cursor as $c)
    {
      $data = utilsMenu::getCollectionData($c->uuid);
      $uri = $self->uriSite . $data['ariane'][1]['uri'];
      $xw->startElement('url');
      $xw->startElement('loc');
      $xw->text($uri);
      $xw->endElement(); //loc
      $xw->startElement('image:image');
      $xw->startElement('image:loc');
      $imgUri = $self->uriSite . '/api/v2/image/' . $data['doc']->imageUuid;
      $xw->text($imgUri);
      $xw->endElement(); //image:loc
      $xw->endElement(); //image:image
      $xw->startElement('lastmod');
      $xw->text(\date('Y-m-d', $data['doc']->dateUpdate));
      $xw->endElement(); //lastmod
      $xw->endElement(); //url
    }

    $xw->endElement(); //urset
    $xw->endDocument();
    $res = $xw->outputMemory();
    $file = $_ENV['BASE_DIR'] . '/../html/sitemap.xml';
    $tmp = '/tmp/sitemap.xml';
    file_put_contents($tmp, $res);
    rename($tmp, $file);
    //response::xml(200, $res);
  }
  public function categories()
  {
    $col = "categories";
    $cursor = $this->dbRes['class']::get(
      col: $col,
      order: ['dateUpdate' => -1],
      projection: ['uuid']
    );

    $xw  = $this->xw;
    foreach($cursor as $c)
    {
      $data = utilsMenu::getCategorieData($c->uuid);
      if(!isset($data['histoires']))
        continue;
      if($data['histoires']['nbr'] == 0)
        continue;
      $uri = $this->uriSite . $data['ariane'][1]['uri'];
      $xw->startElement('url');
      $xw->startElement('loc');
      $xw->text($uri);
      $xw->endElement(); //loc
      $xw->startElement('lastmod');
      $xw->text(\date('Y-m-d', $data['doc']->dateUpdate));
      $xw->endElement(); //lastmod
      $xw->endElement(); //url
    }
    $xw->endElement(); //urset
    $xw->endDocument();
    $res = $xw->outputMemory();

    response::xml(200, $res);
  }
  public function histoires()
  {
    $col = "oeuvres";
    $cursor = $this->dbRes['class']::get(
      col: $col,
      order: ['dateUpdate' => -1],
      projection: ['uuid']
    );

    $xw  = $this->xw;
    foreach($cursor as $c)
    {
      $data = utilsMenu::getHistoireData($c->uuid);
      $uri = $this->uriSite . $data['ariane'][1]['uri'];
      $xw->startElement('url');
      $xw->startElement('loc');
      $xw->text($uri);
      $xw->endElement(); //loc
      $xw->startElement('lastmod');
      $xw->text(\date('Y-m-d', $data['doc']->dateUpdate));
      $xw->endElement(); //lastmod
      $xw->endElement(); //url
    }
    $xw->endElement(); //urset
    $xw->endDocument();
    $res = $xw->outputMemory();

    response::xml(200, $res);
  }
  public function collections()
  {
    $col = "collections";
    $cursor = $this->dbRes['class']::get(
      col: $col,
      order: ['dateUpdate' => -1],
      projection: ['uuid']
    );
    $xw  = $this->xw;
    foreach($cursor as $c)
    {
      $data = utilsMenu::getCollectionData($c->uuid);
      $uri = $this->uriSite . $data['ariane'][1]['uri'];
      $xw->startElement('url');
      $xw->startElement('loc');
      $xw->text($uri);
      $xw->endElement(); //loc
      $xw->startElement('lastmod');
      $xw->text(\date('Y-m-d', $data['doc']->dateUpdate));
      $xw->endElement(); //lastmod
      $xw->endElement(); //url
    }
    $xw->endElement(); //urset
    $xw->endDocument();
    $res = $xw->outputMemory();
    response::xml(200, $res);
  }
  public function images()
  {
    $xw  = $this->xw;
    $xw->startAttribute('xmlns:image');
    $xw->text("http://www.google.com/schemas/sitemap-image/1.1");
    $xw->endAttribute();
    //Collections
    $cursor = $this->dbRes['class']::get(
      col: "collections",
      order: ['dateUpdate' => -1],
      projection: ['uuid']
    );
    foreach($cursor as $c)
    {
      $data = utilsMenu::getCollectionData($c->uuid);
      $uri = $this->uriSite . $data['ariane'][1]['uri'];
      $xw->startElement('url');
      $xw->startElement('loc');
      $xw->text($uri);
      $xw->endElement(); //loc
      $xw->startElement('image:image');
      $xw->startElement('image:loc');
      $imgUri = $this->uriSite . '/api/v2/image/' . $data['doc']->imageUuid;
      $xw->text($imgUri);
      $xw->endElement(); //image:loc
      $xw->endElement(); //image:image
      $xw->endElement();
    }
    //histoires
    $cursor = $this->dbRes['class']::get(
      col: "oeuvres",
      order: ['dateUpdate' => -1],
      projection: ['uuid']
    );
    foreach($cursor as $c)
    {
      $data = utilsMenu::getHistoireData($c->uuid);
      $uri = $this->uriSite . $data['ariane'][1]['uri'];
      $xw->startElement('url');
      $xw->startElement('loc');
      $xw->text($uri);
      $xw->endElement(); //loc
      $xw->startElement('image:image');
      $xw->startElement('image:loc');
      $imgUri = $this->uriSite . '/api/v2/image/' . $data['doc']->imageUuid;
      $xw->text($imgUri);
      $xw->endElement(); //image:loc
      $xw->endElement(); //image:image
      $xw->endElement();
    }
    $xw->endElement(); //urset
    $xw->endDocument();
    response::xml(200, $xw->outputMemory());
  }
  public function index()
  {
    $xw = $this->xw;

    $xw->startElement('sitemap');
    $xw->startElement('loc');
    $xw->text($this->uriSite . $_ENV['BASE_PATH'] . '/v2/sitemap/histoires');
    $xw->endElement(); //loc
    $xw->endElement(); //sitemap

    $xw->startElement('sitemap');
    $xw->startElement('loc');
    $xw->text($this->uriSite . $_ENV['BASE_PATH'] . '/v2/sitemap/collections');
    $xw->endElement(); //loc
    $xw->endElement(); //sitemap

    $xw->startElement('sitemap');
    $xw->startElement('loc');
    $xw->text($this->uriSite . $_ENV['BASE_PATH'] . '/v2/sitemap/images');
    $xw->endElement(); //loc
    $xw->endElement(); //sitemap

    $xw->startElement('sitemap');
    $xw->startElement('loc');
    $xw->text($this->uriSite . $_ENV['BASE_PATH'] . '/v2/sitemap/categories');
    $xw->endElement(); //loc
    $xw->endElement(); //sitemap

    $xw->endElement(); //sitemapindex
    $xw->endDocument();
    response::xml(200, $xw->outputMemory());
  }
}
