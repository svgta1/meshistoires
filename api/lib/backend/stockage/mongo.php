<?php
namespace Meshistoires\Api\backend\stockage;
use Meshistoires\Api\utils\trace;
use Meshistoires\Api\backend\stockage;

class mongo implements stockageInt
{
  private static $res = null;

  const IMG="images";
  const THUMB="thumb";
  const THUMB300="thumb300";
  const PDF = "pdf";

  public static function delete(string $file)
  {
    self::deleteImg(self::IMG, $file);
    self::deleteImg(self::THUMB, $file);
    self::deleteImg(self::THUMB300, $file);
  }
  private static function deleteImg(string $bucketName, string $file){
    $bucket = self::get_res()->selectGridFSBucket(['bucketName' => $bucketName]);
    $doc = $bucket->findOne(['filename' => $file]);
    if(!is_null($doc))
      $bucket->delete($doc->_id);
  }
  public static function post(string $file): string
  {
    if (\in_array(\strtolower(\pathinfo($file, PATHINFO_EXTENSION)), array("jpg", "jpeg"))){
      $im=new \Imagick($file);
      $im->optimizeImageLayers();
      $im->setImageCompression(\Imagick::COMPRESSION_JPEG);
      $im->setImageCompressionQuality(80);
      $im->writeImages($file, true);
    }
    $webPFile = self::_toWebp($file);
    $uuid = sha1_file($file);
    $filename = $uuid . '.' . pathinfo($webPFile, PATHINFO_EXTENSION);
    
    $imgData=array(
			"title" => \pathinfo($file, PATHINFO_FILENAME),
			"value" => $filename,
			"ctype" => \image_type_to_mime_type(\exif_imagetype($webPFile)),
      'exif' => self::_setExif(@\exif_read_data($webPFile))
		);

    $bucketImg = self::get_res()->selectGridFSBucket(['bucketName' => self::IMG]);
    $docImg = $bucketImg->findOne(['filename' => $filename]);
    if(is_null($docImg)){
      $bucketImg->uploadFromStream($filename, \fopen($webPFile, 'rb'), ['metadata' => $imgData]);
    }
    $bucket300 = self::get_res()->selectGridFSBucket(['bucketName' => self::THUMB300]);
    $doc300 = $bucket300->findOne(['filename' => $filename]);
    if(is_null($doc300)){
      //300*300
      $resize = self::_dimImg(300, 300, $filename);
      $imgData['exif'] = $resize['exif'];
      self::_uploadStream($bucket300, $filename, $resize['blob'], $imgData);
    }
    $bucketThb = self::get_res()->selectGridFSBucket(['bucketName' => self::THUMB]);
    $docThb = $bucketThb->findOne(['filename' => $filename]);
    if(is_null($docThb)){
      //128*128
      $resize = self::_dimImg(128, 128, $filename);
      $imgData['exif'] = $resize['exif'];
      self::_uploadStream($bucketThb, $filename, $resize['blob'], $imgData);
    }
    unlink($file);
    if($file !== $webPFile)
      unlink($webPFile);
    return $filename;
  }
  private static function _toWebp($file, $quality = 80)
  {
    // check if file exists
    if (!file_exists($file)) {
      return $file;
    }

    // If output file already exists return path
    $output_file = $file . '.webp';
    if (file_exists($output_file)) {
        return $output_file;
    }

    $file_type = strtolower(pathinfo($file, PATHINFO_EXTENSION));

    if (function_exists('imagewebp')) {

        switch ($file_type) {
            case 'jpeg':
            case 'jpg':
                $image = imagecreatefromjpeg($file);
                break;

            case 'png':
                $image = imagecreatefrompng($file);
                imagepalettetotruecolor($image);
                imagealphablending($image, true);
                imagesavealpha($image, true);
                break;

            case 'gif':
                $image = imagecreatefromgif($file);
                break;
            default:
                return $file;
        }

        // Save the image
        $result = imagewebp($image, $output_file, $quality);
        if (false === $result) {
            return $file;
        }

        // Free up memory
        imagedestroy($image);

        return $output_file;
    } elseif (class_exists('Imagick')) {
        $image = new Imagick();
        $image->readImage($file);

        if ($file_type === 'png') {
            $image->setImageFormat('webp');
            $image->setImageCompressionQuality($quality);
            $image->setOption('webp:lossless', 'true');
        }

        $image->writeImage($output_file);
        return $output_file;
    }

    return $file;
  }
  private static function _uploadStream($bucket, string $filename, string $content, array $data)
  {
    $stream = $bucket->openUploadStream($filename, ['metadata' => $data]);
    fwrite($stream, $content);
		fclose($stream);
  }
  private static function _dimImg(int $height, int $width, string $filename): array
  {
    $info = self::getImageInfo($filename);
    $imgM = new \Imagick();
    $imgM->readImageBlob(self::getStream($info['stream']));
    $imgM->scaleImage($width, $height, true);
    $imgM->optimizeImageLayers();
    return [
      'blob' => $imgM->getImageBlob(),
      'exif' => $imgM->getImageProperties('*', true),
    ];
  }
  private static function _setExif($exif)
  {
		if(!$exif)
			return [];
		$exif = \json_encode($exif);
		$pat = '/(\"[a-zA-Z\:]*)(\.)([a-zA-Z\:\,\_\-]*\"\:)/';
		$exif = \preg_replace($pat, '$1_$3', $exif);
		$exif = \json_decode($exif,true);
		return $exif;
	}
  public static function getImageInfo(string $uuid): array
  {
    $bucket = self::get_res()->selectGridFSBucket(['bucketName' => self::IMG]);
    $doc = $bucket->findOne(['filename' => $uuid]);

    return [
      'metadata' => $doc,
      'stream' => $bucket->openDownloadStreamByName($uuid, ['revision' => 0])
    ];
  }

  public static function getStream($stream)
  {
    return \stream_get_contents($stream);
  }

  public static function getThmb300Info(string $uuid): array
  {
    $bucket = self::get_res()->selectGridFSBucket(['bucketName' => self::THUMB300]);
    $doc = $bucket->findOne(['filename' => $uuid]);

    return [
      'metadata' => $doc,
      'stream' => $bucket->openDownloadStreamByName($uuid, ['revision' => 0])
    ];
  }

  public static function getThmbInfo(string $uuid): array
  {
    $bucket = self::get_res()->selectGridFSBucket(['bucketName' => self::THUMB]);
    $doc = $bucket->findOne(['filename' => $uuid]);

    return [
      'metadata' => $doc,
      'stream' => $bucket->openDownloadStreamByName($uuid, ['revision' => 0])
    ];
  }

  public static function list(int $skip = 0)
  {
    $bucket = self::get_res()->selectGridFSBucket(['bucketName' => self::IMG]);
    $cursor = $bucket->find([], [
      'sort' => ['uploadDate' => -1],
      'skip' => $skip,
      'limit' => 50,
      'projection' => ['filename' => 1, 'metadata.title' => 1]
    ]);
    return $cursor;
  }

  public static function count(): int
  {
    $col = self::IMG . '.files';
    return self::get_res()->{$col}->count();
  }

  private static function get_res(){
    if(is_null(self::$res))
      self::$res = stockage::get_res();
    return self::$res['res'];
  }
}
