<?php
use Meshistoires\Api\controller\v2r0\sitemap;

require dirname(__FILE__, 2) . '/vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(dirname(__FILE__, 2));
$dotenv->load();

sitemap::global();