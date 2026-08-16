<?php
namespace Meshistoires\Api\utils;
use Svgta\Lib\Utils;
use Meshistoires\Api\utils\opt;

class siteInfo
{
  public static function info(): array
  {
    //$social = null;
    //if(is_file($_ENV['SOCIAL_YAML']))
    //$social = opt::yaml_parse_file($_ENV['SOCIAL_YAML']);
    $cr = str_replace('#AAAA#', \date('Y'), $_ENV['COPYRIGHT']);
    $version = opt::yaml_parse_file($_ENV['BASE_DIR'] . '/version.yaml')['version'];
    $res = [
      'title' => $_ENV['SITE_TITLE'],
      'description' => $_ENV['SITE_DESC'],
      'endpoints' => $_SERVER["REQUEST_SCHEME"]. "://".$_SERVER["HTTP_HOST"] . $_ENV['BASE_PATH'] . '/endpoints',
      //'social' => $social,
      'copyRight' => $cr,
      'isBot' => Utils::is_bot(),
      'adult_content' => \boolval($_ENV['ADULT_CONTENT']),
      'enc_POST' => isset($_ENV['REQUEST_POST_ENC']) && \boolval($_ENV['REQUEST_POST_ENC']),
      'enc_PUT' => isset($_ENV['REQUEST_PUT_ENC']) && \boolval($_ENV['REQUEST_PUT_ENC']),
      'enc_DELETE' => isset($_ENV['REQUEST_DELETE_ENC']) && \boolval($_ENV['REQUEST_DELETE_ENC']),
      'version' => $version,
    ];
    return $res;
  }
  public static function infoFooterMail(): array
  {
    $info = self::info();
    /*$social = "";
    foreach($info['social'] as $s){
      $social .= '<a href="'.$s['url'].'" title"'.$s['title'].'"><img src="'.$s['icon'].'" alt="'.$s['name'].'"></a>';
    }*/

    return [
      //'social' => $social,
      'cr' => $info['copyRight'],
    ];
  }
  public static function getSocial()
  {
    $social = opt::yaml_parse_file($_ENV['SOCIAL_YAML']);
    $tplLi = opt::file_get_contents($_ENV['HTML_TPL'] . '/social_li.tpl');
    $html = "";
    foreach($social as $k => $s){
      $li = str_replace('##socialHref##', $s['url'], $tplLi);
      $li = str_replace('##socialTitle##', $s['title'], $li);
      $li = str_replace('##social##', $k, $li);
      $html .= $li;
    }
    return $html;
  }
}
