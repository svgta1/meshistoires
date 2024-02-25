<?php
namespace Meshistoires\Api\backend;

interface gInterface
{
  public function __construct();
  public static function get_res();
  public function set_res(): void;
}
