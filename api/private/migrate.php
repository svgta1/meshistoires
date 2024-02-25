<?php
require dirname(__FILE__, 2) . '/vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(dirname(__FILE__, 2));
$dotenv->load();


$contacts = new Meshistoires\Api\migrate\mongo\contacts();
$contacts->testMigrate();

$articles = new Meshistoires\Api\migrate\mongo\articles();
$articles->testMigrate();

$comments = new Meshistoires\Api\migrate\mongo\comments();
$comments->testMigrate();

$menus = new Meshistoires\Api\migrate\mongo\menus();
$menus->testMigrate();
