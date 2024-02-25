<?php
namespace Meshistoires\Api\utils;

class seo
{
  private static function skip_accents( $str, $charset='utf-8' ) {
    $str = htmlentities( $str, ENT_NOQUOTES, $charset );
    $str = preg_replace( '#&([A-za-z])(?:acute|cedil|caron|circ|grave|orn|ring|slash|th|tilde|uml);#', '\1', $str );
    $str = preg_replace( '#&([A-za-z]{2})(?:lig);#', '\1', $str );
    $str = preg_replace( '#&[^;]+;#', '', $str );

    return $str;
  }

  public static function seofy ($sString = ''){
    $sString = self::skip_accents($sString);
    $sString = preg_replace ('/[^\pL\d_]+/u', '-', $sString);
    $sString = trim ($sString, "-");
    $sString = iconv ('utf-8', "us-ascii//TRANSLIT", $sString);
    $sString = strtolower ($sString);
    $sString = preg_replace ('/[^-a-z0-9_]+/', '', $sString);

    return $sString;
  }
}
