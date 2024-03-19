<?php
namespace Meshistoires\Api\controller\v1r0;
use Meshistoires\Api\utils\trace;
use Meshistoires\Api\utils\response;
use Meshistoires\Api\utils\cache;
use Meshistoires\Api\backend\db;
use Meshistoires\Api\utils\seo;

class sitemap
{
  private $dbRes = null;
  private $className = null;
  private $scopes = null;
  private $request = [];

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
  }

  public function articles()
  {
    $cache_id = $this->className.'_'.__FUNCTION__;
    $cache = cache::getXml($cache_id);

    $xw  = $this->xw;
    $col = "articles";
    $col_menu = "menus";

    $cursor = $this->dbRes['class']::get(
      col: $col,
      param: ['visible' => true, 'deleted' => false],
      projection: ['title', 'dateUpdate', 'parent'],
      order: ['dateUpdate' => -1]
    );
    foreach($cursor as $doc)
    {
      if(!$doc->title)
        continue;
      $ar = [
        'title' => $doc->title,
        'update' => $doc->dateUpdate,
      ];
      $rM = $this->dbRes['class']::getOne(
        col: $col_menu,
        param: ['uuid' => $doc->parent, 'deleted' => false, 'visible' => true],
        projection: ['name', 'uuid']
      );
      if(is_null($rM))
        continue;
      if(isset($rM->visible) && !$rM->visible)
        continue;

      $ar['uri'] = $_SERVER["REQUEST_SCHEME"]. "://".$_SERVER["HTTP_HOST"] . '/' . seo::seofy($rM->name) . '/' . seo::seofy($doc->title);

      $xw->startElement('url');
      $xw->startElement('loc');
      $xw->text($ar['uri']);
      $xw->endElement(); //loc
      $xw->startElement('lastmod');
      $xw->text(\date('Y-m-d', $ar['update']));
      $xw->endElement(); //lastmod
      $xw->endElement(); //url
    }
    $xw->endElement(); //urset
    $xw->endDocument();
    $res = $xw->outputMemory();

    cache::set($cache_id, json_encode($res));
    response::xml(200, $res);
  }
  public function menus()
  {
    $cache_id = $this->className.'_'.__FUNCTION__;
    $cache = cache::getXml($cache_id);

    $xw  = $this->xw;
    $col = "menus";

    $cursor = $this->dbRes['class']::get(
      col: $col,
      param: ['visible' => true, 'deleted' => false],
      projection: ['name', 'dateUpdate'],
      order: ['dateUpdate' => -1]
    );
    if(is_null($cursor))
      response::json(404, 'Menus not found');
    foreach($cursor as $doc){
      $ar = [
        'name' => $doc->name,
        'update' => $doc->dateUpdate,
        'articles' => isset($doc->articles) ? $doc->articles : [],
      ];
      $ar['uri'] = $_SERVER["REQUEST_SCHEME"]. "://".$_SERVER["HTTP_HOST"] . '/' . seo::seofy($doc->name);

      $xw->startElement('url');
      $xw->startElement('loc');
      $xw->text($ar['uri']);
      $xw->endElement(); //loc
      $xw->startElement('lastmod');
      $xw->text(\date('Y-m-d', $ar['update']));
      $xw->endElement(); //lastmod
      $xw->endElement(); //url
    }

    $xw->endElement(); //urset
    $xw->endDocument();
    $res = $xw->outputMemory();
    cache::set($cache_id, json_encode($res));
    response::xml(200, $res);
  }
  public function index()
  {
    $xw = new \XMLWriter();
    $xw->openMemory();
    $xw->startDocument("1.0", "UTF-8");
    $xw->startElement('sitemapindex');
    $xw->startAttribute('xmlns');
    $xw->text("http://www.sitemaps.org/schemas/sitemap/0.9");
    $xw->endAttribute();

    $xw->startElement('sitemap');
    $xw->startElement('loc');
    $xw->text($_SERVER["REQUEST_SCHEME"]. "://".$_SERVER["HTTP_HOST"] . $_ENV['BASE_PATH'] . '/v1/sitemap/articles');
    $xw->endElement(); //loc
    $xw->endElement(); //sitemap

    $xw->startElement('sitemap');
    $xw->startElement('loc');
    $xw->text($_SERVER["REQUEST_SCHEME"]. "://".$_SERVER["HTTP_HOST"] . $_ENV['BASE_PATH'] . '/v1/sitemap/menus');
    $xw->endElement(); //loc
    $xw->endElement(); //sitemap

    $xw->endElement(); //sitemapindex
    $xw->endDocument();
    response::xml(200, $xw->outputMemory());
  }
}
