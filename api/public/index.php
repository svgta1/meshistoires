<?php
require dirname(__FILE__, 2) . '/vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(dirname(__FILE__, 2));
$dotenv->load();
$session = new Meshistoires\Api\utils\session();

$kernel = new Meshistoires\Api\kernel();
