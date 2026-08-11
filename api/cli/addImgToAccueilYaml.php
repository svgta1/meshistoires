<?php
use Meshistoires\Api\utils\cli;
require dirname(__FILE__, 2) . '/vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(dirname(__FILE__, 2));
$dotenv->load();

cli::addImgParams('accueil');

