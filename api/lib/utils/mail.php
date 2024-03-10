<?php
namespace Meshistoires\Api\utils;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

class mail
{
  public function init()
  {
    $config = \yaml_parse_file($_ENV['MAIL_YAML']);
    try{
      $email = new PHPMailer(true);
      if($config['debug'])
        $email->SMTPDebug = SMTP::DEBUG_SERVER;
      if($config['smtp']['enable']){
        $email->isSMTP();
        $email->Host = $config['smtp']['host'];
        $email->Port = $config['smtp']['port'];
        if($config['smtp']['tls'])
          $email->SMTPSecure = 'tls';
        if($config['smtp']['authRequired']){
          $email->SMTPAuth   = true;
          $email->Username = $config['smtp']['username'];
          $email->Password = $config['smtp']['password'];
        }
      }
      $email->setFrom($config['fromMail'], $config['fromName']);
      $email->isHTML($config['htmlFormat']);
      $email->setLanguage('fr');
    }catch(Exception $e){
      respone::json(500, $e->getMessage());
    }
    $this->config = $config;
    $this->email = $email;
  }

  public function send(string $subject, string $body, string $toMail, string $toName)
  {
    $this->init();
    $header = $this->prepareHeader(userName: $toName);
    $footer = $this->prepareFooter();
    $emailContent = $header . $body . $footer;
    $email = $this->email;
    $email->addAddress($toMail, $toName);
    $email->Subject = '=?UTF-8?B?'.base64_encode($subject).'?=';
    $email->Body = \mb_convert_encoding($emailContent, "iso-8859-1");
    if($this->config['htmlFormat']){
      $email->AltBody = \strip_tags($email->Body);
    }
    $email->send();
  }
  private function prepareFooter(): string
  {
    $footer = \file_get_contents($_ENV['MAIL_TPL'] . '/footer.tpl');
    $siteInfo = siteInfo::infoFooterMail();
    $footer = str_replace('##social##', $siteInfo['social'], $footer);
    $footer = str_replace('##copyright##', $siteInfo['cr'], $footer);
    $footer = str_replace('##signature##', $this->config['imageSignature'], $footer);
    return $footer;
  }
  private function prepareHeader(string $userName): string
  {
    $header = \file_get_contents($_ENV['MAIL_TPL'] . '/header.tpl');
    $header = str_replace('##imgUrl##', $this->config['imageHeader'], $header);
    $header = str_replace('##ident##', $userName, $header);
    return $header;
  }
}
