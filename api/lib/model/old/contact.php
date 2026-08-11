<?php
namespace Meshistoires\Api\model;
class contact extends absModel
{
  public ?string $sn = null;
  public ?string $givenname = null;
  public ?string $mail = null;
  public ?string $uuid = null;
  public bool $abo_news = true;
  public int $dateCreate = 0;
  public int $dateUpdate = 0;
  public array $sec_keys = [];
  public bool $deleted = false;
  public ?string $code_activation = null;
  public ?string $code_supp = null;
  public bool $ban = false;
}
