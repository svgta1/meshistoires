<?php
namespace Meshistoires\Api\utils;

class opt
{
  private static $yamlFileContents = [];
  private static $tplFileContents = [];
  private static $dirCache = [
    'cli' => 'OpCacheCli',
    'web' => 'OpCacheWeb'
  ];

  public static function file_get_contents(string $fileName)
  {
    $key = md5($fileName);
    if(isset(self::$tplFileContents[$key]))
        return self::$tplFileContents[$key];

    if(!is_file($fileName))
      return false;

    self::get_file_contentsTpl($fileName, $key);
    return self::$tplFileContents[$key];
  }

  public static function yaml_parse_file(string $fileName){
    $key = md5($fileName);
    if(isset(self::$yamlFileContents[$key]))
        return self::$yamlFileContents[$key];

    if(!is_file($fileName))
      return false;

    //self::$yamlFileContents[$key] = yaml_parse_file($fileName);
    self::get_file_contentsYaml($fileName, $key);
    return self::$yamlFileContents[$key];
  }

  public static function get_file_contentsTpl(string $fileName, string $key){
    if(!$oc = opcache_get_configuration())
      return self::get_from_tpl($fileName, $file_PHP, $key);

    $opConf = $oc['directives'];
    $revalidate = $opConf['opcache.revalidate_freq'];

    if(php_sapi_name() == 'cli'){
      $file_PHP = self::get_phpfile_path(self::$dirCache['cli'], $key, $opConf['opcache.enable_cli']);
      if(!$opConf['opcache.enable_cli']){
        return self::get_from_tpl($fileName, $file_PHP, $key);
      }
    }      
    else{
      $file_PHP = self::get_phpfile_path(self::$dirCache['web'], $key, $opConf['opcache.enable']);
      if(!$opConf['opcache.enable']){
        return self::get_from_tpl($fileName, $file_PHP, $key);
      }
    }

    if(!is_file($file_PHP)){
      return self::get_from_tpl($fileName, $file_PHP, $key, true);
    }
    
    if($opConf['opcache.validate_timestamps'] && (filemtime($file_PHP) + $revalidate < time())){
      if(filemtime($file_PHP) < filemtime($fileName)){
        return self::get_from_tpl($fileName, $file_PHP, $key, true);
      }else{
        touch($file_PHP);
      }   
    }
    require($file_PHP);
    self::$tplFileContents[$key] = htmlspecialchars_decode($tpl);
  }

  public static function get_file_contentsYaml(string $fileName, string $key){
    if(!$oc = opcache_get_configuration())
      return self::get_from_yaml($fileName, $file_PHP, $key);

    $opConf = $oc['directives'];
    $revalidate = $opConf['opcache.revalidate_freq'];

    if(php_sapi_name() == 'cli'){
      $file_PHP = self::get_phpfile_path(self::$dirCache['cli'], $key, $opConf['opcache.enable_cli']);
      if(!$opConf['opcache.enable_cli']){
        return self::get_from_yaml($fileName, $file_PHP, $key);
      }
    }      
    else{
      $file_PHP = self::get_phpfile_path(self::$dirCache['web'], $key, $opConf['opcache.enable']);
      if(!$opConf['opcache.enable']){
        return self::get_from_yaml($fileName, $file_PHP, $key);
      }
    }

    if(!is_file($file_PHP)){
      return self::get_from_yaml($fileName, $file_PHP, $key, true);
    }
    
    if($opConf['opcache.validate_timestamps'] && (filemtime($file_PHP) + $revalidate < time())){
      if(filemtime($file_PHP) < filemtime($fileName)){
        return self::get_from_yaml($fileName, $file_PHP, $key, true);
      }else{
        touch($file_PHP);
      }   
    }
    require($file_PHP);
    self::$yamlFileContents[$key] = $array;
  }
  public static function get_phpfile_path(string $cacheDir, string $key, bool $createDir = false){
    if(!isset($_ENV['CACHE_DIR']) || !is_dir($_ENV['CACHE_DIR'])){
      $dir = sys_get_temp_dir() . '/' . $cacheDir;
    }else{
      $dir = $_ENV['CACHE_DIR'] . '/' . $cacheDir;
    }
    
    if(!is_dir($dir) && $createDir){
      if(!mkdir($dir))
        throw new \Exception('Dossier de cache ne pouvant être créé à : ' . $dir);
    }
    return $dir . '/' . $key . '.php';
  }
  public static function get_from_yaml(string $fileName, string $file_PHP, string $key, bool $genPhp = false){
    self::$yamlFileContents[$key] = yaml_parse_file($fileName);
    if($genPhp){
      $array = var_export(self::$yamlFileContents[$key], 1);
      $contents = "<?php \$array = $array; ?>";
      file_put_contents($file_PHP, $contents);
    }
  }
  public static function get_from_tpl(string $fileName, string $file_PHP, string $key, bool $genPhp = false){
    self::$tplFileContents[$key] = file_get_contents($fileName);
    if($genPhp){
      $contents = "<?php \$tpl = '" . htmlspecialchars(self::$tplFileContents[$key]) . "'; ?>";
      file_put_contents($file_PHP, $contents);
    }
  }

  public static function resetCache(bool $deleteCache = true){
    if(isset($_ENV['OPCAHE_FILE_CONTROL'])){
      $key = md5($_ENV['OPCAHE_FILE_CONTROL']);
    }else{
      return;
    }
    if(!is_file($_ENV['OPCAHE_FILE_CONTROL']))
      return;

    if(php_sapi_name() == 'cli'){
      $cacheDir = self::$dirCache['cli'];
      $is_cli = true;
    }      
    else{
      $cacheDir = self::$dirCache['web'];
      $is_cli = false;
    }
    $file_PHP = self::get_phpfile_path($cacheDir, $key, true);
    if(!is_file($file_PHP)){
      if($deleteCache)
        self::deleteCache($cacheDir, $is_cli);
      return self::yaml_parse_file($_ENV['OPCAHE_FILE_CONTROL']);
    }
    $diff = filemtime($file_PHP) - filemtime($_ENV['OPCAHE_FILE_CONTROL']);
    if(filemtime($file_PHP) < filemtime($_ENV['OPCAHE_FILE_CONTROL'])){
      self::deleteCache($cacheDir, $is_cli);
      return self::resetCache(false);
    }
      
  }
  public static function deleteCache(?string $cacheDir = null, $is_cli = false){
    if(is_null($cacheDir))
      $cacheDir = self::$dirCache['web'];
    if(!isset($_ENV['CACHE_DIR']) || !is_dir($_ENV['CACHE_DIR'])){
      $dir = sys_get_temp_dir() . '/' . $cacheDir;
    }else{
      $dir = $_ENV['CACHE_DIR'] . '/' . $cacheDir;
    }
    $files = glob($dir . '/*');
    foreach($files as $file){
      if(is_file($file)) {
        unlink($file);
      }
    }
    if(!$oc = opcache_get_configuration())
      return false;

    $opConf = $oc['directives'];
    if($is_cli){
      $enable = $opConf['opcache.enable_cli'];
    }else{
      $enable = $opConf['opcache.enable'];
    }
    
    if($enable){
      if(!opcache_reset()){
        throw new \Exception('opcache_reset échoué. Relancer le service FPM');
      }
    }
  }
}