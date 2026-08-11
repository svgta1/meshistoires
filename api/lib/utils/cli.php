<?php
namespace Meshistoires\Api\utils;

class cli
{
  public static function addImgParams($name)
  {
    $dir = $_ENV['BASE_DIR'] . '/data/siteParams/';
    $paramFile = $dir . 'siteParams.yaml';
    $dirImg = $dir . 'imgs';

    $conf = yaml_parse_file($paramFile);
    $scan = scandir($dirImg);
    foreach($scan as $f){
      $file = $dirImg . '/' . $f;
      if(!is_file($file))
        continue;
      if(!in_array($f, $conf[$name]['imgs']))
        $conf[$name]['imgs'][] = $f;
    }
    $conf[$name]['imgs'] = array_unique($conf[$name]['imgs']);
    yaml_emit_file($paramFile, $conf);
  }
}