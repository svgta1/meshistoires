<?php
namespace Meshistoires\Api\utils;

class CreativeWork
{
  public static function gen()
  {
    if(!is_file($_ENV['CREATIVEWORK']))
      return false;
    $crea = opt::yaml_parse_file($_ENV['CREATIVEWORK']);
    $ar = [
      '@context' => 'https://schema.org',
      '@type' => ['CreativeWork'],
      'name' => '',
      'author' => [
        '@type' => 'Person',
        'name' => $crea['author']['name'],
        'url' => $_SERVER['REQUEST_SCHEME'] . '://' . $_ENV['DOMAIN']
      ],
      'description' => '',
      'genre' => [],
      'inCollection' => [
        '@type' => "PublicationSeries",
        'name' => '',
        'url' => '',
      ],
      'url' => '',
      'datePublished' => '',
      'dateModified' => '',
      'inLanguage' => 'fr',
      'publisher' => [
        '@type' => 'Organization',
        'name' => $_ENV['SITE_TITLE'],
        'url' => $_SERVER['REQUEST_SCHEME'] . '://' . $_ENV['DOMAIN']
      ],
      'audience' => [
        '@type' => "PeopleAudience",
        'suggestedMinAge' => 18
      ],
      'isAccessibleForFree' => true,
      'license' => 'https://creativecommons.org/licenses/by-nc-nd/3.0/deed.fr'
    ];
    return $ar;
  }
}