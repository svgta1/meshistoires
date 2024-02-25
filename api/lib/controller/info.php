<?php
namespace Meshistoires\Api\controller;
use Meshistoires\Api\utils\trace;
use Meshistoires\Api\utils\response;
use Meshistoires\Api\utils\siteInfo;

class info
{
  private $scopes = null;
  private $request = [];

  public function __construct(?array $scopes, array $request)
  {
    $this->scopes = $scopes;
    $this->request = $request;
  }

  public function endpoints(): void
  {
    $route = \yaml_parse_file($_ENV['ROUTES_YAML'])['routes'];
    foreach($route as $k=>$r)
    {
      unset($route[$k]['class']);
      unset($route[$k]['class_method']);
      $route[$k]['url'] = $_SERVER["REQUEST_SCHEME"]. "://".$_SERVER["HTTP_HOST"] . $_ENV['BASE_PATH'] . $r['uri'];
    }
    response::json(200, $route);
  }

  public function info(): void
  {
    response::json(200, siteInfo::info());
  }
  public function commentEnable(): void
  {
    response::json(200, ['comment_enable'=> \boolval($_ENV['COMMENT_ACTIF'])]);
  }
  public function contactEnable(): void
  {
    response::json(200, ['contact_enable'=> \boolval($_ENV['CONTACT_ACTIF'])]);
  }
}
