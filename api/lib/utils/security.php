<?php
namespace Meshistoires\Api\utils;
use Meshistoires\Api\utils\opt;

class security
{
  public static function is_protectedQuery($query)
  {
    if(!isset($_ENV['SECURITY_YAML']) || !is_file($_ENV['SECURITY_YAML']))
      return true;
    $rules = opt::yaml_parse_file($_ENV['SECURITY_YAML'])['rules'];
    $sure = true;
    foreach($rules as $k => $v){
      if(strpos($query, $k) !== false){
        $sure = false;
        break;
      }
      if(strpos(urldecode($query), $k) !== false){
        $sure = false;
        break;
      }
    }
    return $sure;
  }
}