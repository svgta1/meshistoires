<?php
use Meshistoires\Api\utils\getGrist;

require dirname(__FILE__, 2) . '/vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(dirname(__FILE__, 2));
$dotenv->load();



$api_Key = $_ENV['GRIST_API_KEY'];
$api_id = $_ENV['GRIST_API_ID'];
$uri = $_ENV['GRIST_API_URI'];


$api = new getGrist(
  api_key: $api_Key,
  api_id: $api_id,
  api_uri: $uri
);

$api->deleteNoUuid();
Print_r('# Maj Catégories' . PHP_EOL);
print_r($api->getCat());
Print_r('# Maj Public' . PHP_EOL);
print_r($api->getPublic());
Print_r('# Maj Collections' . PHP_EOL);
print_r($api->getCol());
Print_r('# Maj Oeuvres' . PHP_EOL);
print_r($api->getOeuvres());
$api->delCache();

echo PHP_EOL;

