<?php
namespace Meshistoires\Api\utils;
use FFMpeg\FFMpeg;
use FFMpeg\Format\Video\WebM;
use FFMpeg\Format\Video\X264;
use Meshistoires\Api\utils\seo;
use Meshistoires\Api\backend\stockage;
use Meshistoires\Api\backend\db;

class video
{
  public static function getVideoFromOeuvre(?string $oeuvreUuid = null): array
  {
    $dbRes = db::get_res();
    $cursor = $dbRes['class']::get(
      col: 'videos.files',
      param: ['metadata.oeuvreUuid' => $oeuvreUuid]
    );
    $ret = [];
    foreach($cursor as $doc){
      $ret[] = $doc->filename;
    }

    return $ret;
  }
  public static function convert(string $file, ?string $name = null, ?string $oeuvreUuid = null)
  {
    $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
    if ($extension !== 'mp4' && $extension !== 'webm' && $extension !== 'ogg') {
      throw new \Exception('Format vidéo non valide');
    }

    if(is_null($name)){
      $name = strtolower(pathinfo($file, PATHINFO_FILENAME));
    }
    $name = seo::seofy($name);
    $tmpDir = '/tmp/ffmpeg-tmp';

    $webm = $tmpDir . '/' .$name. '.webm';
    $mp4 = $tmpDir . '/' .$name. '.mp4';

    $stockageRes = stockage::get_res();
  
    $ffmpeg = FFMpeg::create([
      'ffmpeg.binaries'  => '/usr/bin/ffmpeg', // Ajustez le chemin selon votre serveur
      'ffprobe.binaries' => '/usr/bin/ffprobe',
      'temporary_directory' => $tmpDir,
    ]);
    $video = $ffmpeg->open($file);

    $videoStream = $video->getStreams()->videos()->first();
    $width  = $videoStream->get('width');
    $height = $videoStream->get('height');

    if($width > 1024){
      $height = floor($height / $width * 1024);
      $width = 1024;
      $video->filters()->resize(new Dimension($width, $height), FFMpeg\Filters\Video\ResizeFilter::RESIZEMODE_SCALE_WIDTH);
    }

    $video->save(new WebM(), $webm)
      ->save(new X264(), $mp4);

    if(is_file($webm))
      $stockageRes['class']::postVideo(
        file: $webm, 
        name: $name,
        height: $height,
        width: $width,
        oeuvreUuid: $oeuvreUuid
      );
    if(is_file($mp4))
      $stockageRes['class']::postVideo(
        file: $mp4, 
        name: $name,
        height: $height,
        width: $width,
        oeuvreUuid: $oeuvreUuid
      );
    unlink($webm);
    unlink($mp4);
  }
}