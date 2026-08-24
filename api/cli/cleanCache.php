<?php
use Meshistoires\Api\utils\cache;
use Meshistoires\Api\utils\opt;

require dirname(__FILE__, 2) . '/vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(dirname(__FILE__, 2));
$dotenv->load();

apcu_clear_cache();
cache::clean();
try{
  opt::deleteCache();
}catch(Throwable $t){
  print_r($t->getmessage() . PHP_EOL);
  echo 'Relance du service PHP FPM' . PHP_EOL;
  exec('sudo service php8.4-fpm restart', $a, $b);
  print_r($a);
  echo PHP_EOL;
  print_r($b);
  echo PHP_EOL;
}

