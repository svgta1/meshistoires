<?php
require dirname(__FILE__, 2) . '/vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(dirname(__FILE__, 2));
$dotenv->load();

$index = new Meshistoires\Api\backend\db\mongo_index();
$index->createIndexes();
