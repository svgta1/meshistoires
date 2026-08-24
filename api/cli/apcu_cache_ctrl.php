<?php

require dirname(__FILE__, 2) . '/vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(dirname(__FILE__, 2));
$dotenv->load();

$info = apcu_cache_info();
print_r($info);
echo PHP_EOL;