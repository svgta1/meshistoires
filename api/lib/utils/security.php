<?php
namespace Meshistoires\Api\utils;
use Meshistoires\Api\utils\opt;
use Svgta\Lib\Utils;

class security
{
  public static function is_protectedQuery($query)
  {
    if(!isset($_ENV['SECURITY_YAML']) || !is_file($_ENV['SECURITY_YAML']))
      return true;
    $rules = opt::yaml_parse_file($_ENV['SECURITY_YAML'])['rules'];
    $sure = true;
    $block = [
      'libelle' => '',
      'value' => '',
    ];
    foreach($rules as $k => $v){
      if(str_contains($query, $k) !== false){
        $sure = false;
        $block['libelle'] = $v;
        $block['value'] = $k;
        break;
      }
      if(str_contains(strtolower($query), $k) !== false){
        $sure = false;
        $block['libelle'] = $v;
        $block['value'] = $k;
        break;
      }
      if(str_contains(urldecode($query), $k) !== false){
        $sure = false;
        $block['libelle'] = $v;
        $block['value'] = $k;
        break;
      }
      if(str_contains(self::unicodeConv($query), $k) !== false){
        $sure = false;
        $block['libelle'] = $v;
        $block['value'] = $k;
        break;
      }
      if(str_contains(self::hex2bin($query), $k) !== false){
        $sure = false;
        $block['libelle'] = $v;
        $block['value'] = $k;
        break;
      }
      if(str_contains(html_entity_decode($query), $k) !== false){
        $sure = false;
        $block['libelle'] = $v;
        $block['value'] = $k;
        break;
      }
    }
    if(!$sure){
      Utils::setLogLevel(LOG_ERR);
      Utils::log(LOG_ERR, [
        'query' => $query,
        'block' => $block
      ]);
    }
    return $sure;
  }
  public static function unicodeConv($unicode)
  {
    return preg_replace_callback('/\\\\u([0-9a-fA-F]{4})/', function($m) {
        return chr(hexdec($m[1]));
    }, $unicode);
  }
  public static function hex2bin($hex)
  {
    $hex = preg_replace('/^0x/', '', $hex);
    $hex = preg_replace('/--$/', '', $hex);
    try{
      $ret = @hex2bin($hex);
      if($ret)
        return $ret;
      return $hex;
    }catch(Throwable $t){
      return $hex;
    }
  }
}