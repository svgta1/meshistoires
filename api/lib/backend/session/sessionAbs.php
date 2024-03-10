<?php
namespace Meshistoires\Api\backend\session;

abstract class sessionAbs implements \SessionHandlerInterface
{
  protected static $config = null;
  protected static $res = null;
  protected $name = "sess_";

  abstract protected static function get_res();
  abstract protected static function enc(string $str): string;
  abstract protected static function dec(string $jwe): ?string;
}
