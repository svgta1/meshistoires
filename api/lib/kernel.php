<?php
namespace Meshistoires\Api;
use Meshistoires\Api\utils\trace;
use Meshistoires\Api\utils\response;
use Meshistoires\Api\utils\extException;
use Meshistoires\Api\utils\inException;
use Meshistoires\Api\utils\auth;
use Meshistoires\Api\utils\request;
use Meshistoires\Api\utils\opt;
use Svgta\Lib\Utils;

class kernel
{
  private $routes;

  public function __construct()
  {
    if(isset($_ENV['TRACE_RESPONSE']) && \boolval($_ENV['TRACE_RESPONSE']))
      trace::$useTrace = true;

    auth::verifyAuthHeaderSignature();
    $this->routes = opt::yaml_parse_file($_ENV['ROUTES_YAML'])['routes'];
    $route = $this->verifyRoute($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
    if($route['error'])
      response::json(404, $route);
    if(!is_null($route['route']['version']))
      $class = __namespace__ . '\\controller\\' . $route['route']['version'] . '\\' . $route['route']['class'];
    else
      $class = __namespace__ . '\\controller\\' . $route['route']['class'];
    if(!\class_exists($class))
      throw new inException('Controller does not exist', [
        'route' => $route,
        'class' => $class,
      ]);
    try{
      $req = [];
      if(!is_null($route['uuid']))
        $req['uuid'] = $route['uuid'];
      $c = new $class(
        scopes: $route['route']['scopes'],
        request: $this->ctrlRequest(Utils::getRequest($req))
      );
      $method =  $route['route']['class_method'];
      $c->$method();
    }catch(\Throwable $t){
      print_r($t);
      throw new extException($t);
    }
  }

  private function ctrlRequest($req): array
  {
    $method = $_SERVER['REQUEST_METHOD'];
    $env = 'REQUEST_' . $method . '_ENC';
    if(isset($_ENV[$env]) && \boolval($_ENV[$env])){
      return request::JWE_dec($req, true);
    }
    return request::validate_security($req, true);
  }

  private function verifyRoute(string $method, string $uri): array
  {
    $uri = str_replace($_ENV['BASE_PATH'], '', explode('?', $uri)[0]);
    $route = ["error" => true, "msg" => "no route found", "uuid" => null];
    $routeExist = false;
    foreach($this->routes as $r)
    {
      if(($r['uri'] == $uri) && ($r['method'] == $method))
      {
        $route['route'] = $r;
        $route['error'] = false;
        $route['msg'] = 'route found';
        $routeExist = true;
        break;
      }
    }
    if(!$routeExist)
      $this->routeFindUUId($route, $method, $uri);
    return $route;
  }

  private function routeFindUUId(array &$route, string $method, string $uri): void
  {
    $uriA = explode('/', $uri);
    $uuid = null;
    foreach($this->routes as $r)
    {
      $rUriA = explode('/', $r['uri']);
      if(count($uriA) != count($rUriA))
        continue;
      foreach($rUriA as $k => $v){
        if($v !== '{uuid}'){
          if($v !== $uriA[$k]){
            continue 2;
          }
        }else{
          $uuid = $uriA[$k];
          break;
        }
      }
      if($r['method'] !== $method)
        continue;
      $route['route'] = $r;
      $route['error'] = false;
      $route['msg'] = 'route found';
      $route['uuid'] = $uuid;
      break;
    }
  }
}
