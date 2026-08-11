<?php
use Meshistoires\Api\utils\uploadImgAlt;

require dirname(__FILE__, 3) . '/vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(dirname(__FILE__, 3));
$dotenv->load();

$res = new uploadImgAlt();
$res->genDir();