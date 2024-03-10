<?php
namespace Meshistoires\Api\utils;
use Meshistoires\Api\backend\session as bckSession;

class session
{
  public function __construct()
  {
    $handler = null;
    $config = null;
    if(isset($_ENV['SESSION_YAML']) && is_file($_ENV['SESSION_YAML'])){
      $handler = bckSession::get_res()['class'];
      $config = \yaml_parse_file($_ENV['SESSION_YAML']);
    }
    if(!is_null($handler))
      session_set_save_handler($handler);
    if(!is_null($config)){
      session_set_cookie_params(
        lifetime_or_options: [
          'lifetime' => $config['lifeTime'],
          'samesite' => $config['samesite'],
          'path' => '/',
          'domain' => $_ENV['DOMAIN'],
          'secure' => $config['secure'],
          'httponly' => $config['httponly']
        ],
      );
      ini_set('session.gc_maxlifetime', $config['lifeTime']);
      \session_name($config["name"]);
    }
    \session_start();
    \session_gc();
  }
}
