<?php
namespace Meshistoires\Api\utils;
use Meshistoires\Api\utils\trace;
use Svgta\Lib\Utils;
use \GeoIp2\Database\Reader as geoipReader;

class ui
{
  public static function getGeoIp()
  {
    if(!\boolval($_ENV['GEOIP_ACTIF']))
      return null;
    $ip = Utils::getIP();
    $reader = new geoipReader($_ENV['GEOIP_DB_PATH'], ['en']);
    $record = $reader->city($ip);
    return [
      'continent' => ['id' => $record->continent->code, 'name' => $record->continent->name],
      'country' => ['id' => $record->country->isoCode, 'name' => $record->country->name],
      'state' => ['id' => $record->mostSpecificSubdivision->isoCode, 'name' => $record->mostSpecificSubdivision->name],
      'city' => ['cp' => $record->postal->code, 'name' => $record->city->name],
      'location' => ['lat' => $record->location->latitude, 'long' => $record->location->longitude],
    ];
  }
}
