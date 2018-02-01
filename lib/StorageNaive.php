<?php namespace StorageNaive;

/*
The 'API' provided is :

* ?action=list_folder&path=</some/path>
* ?action=permanently_delete&path=</some/path>
* ?action=upload&path=</some/path>

The provided </some/path> values are joined to 'ROOT_FOLDER' constant.

Limitations:

1. Causes security holes (can upload malicious files, PHP scripts, htaccess, ...)
2. Does not support accents in paths on Windows (which does not handle utf-8 strings in paths)
3. Does not provide folder creation or file download

Example use is :
<?php
error_reporting(0);
require_once('./lib/StorageNaive.php');
\StorageNaive\main('files/'); // IMPORTANT(nico) '/' at the end
?>

Sadly, I need this to be compatible with PHP 5.4, so no `finally` clauses.

Version 0.0.0
Licence : https://unlicense.org/UNLICENSE
*/

//============================================================================

function main($ROOT_FOLDER) {         // IMPORTANT(nico) $ROOT_FOLDER must end with a '/'

  header('Access-Control-Allow-Origin: *');
  header('Cache-Control: no-cache, must-revalidate');

  $result = null;

  try {

    $action = _checked_GET('action');

    switch ($action) {

    case 'list_folder':
      $path = _checked_GET('path');
      $abspath = _abspath($ROOT_FOLDER, $path);
      $result = _list_folder($abspath);
      break;

    case 'permanently_delete':
      $path = _checked_GET('path');
      $abspath = _abspath($ROOT_FOLDER, $path);
      $result = _permanently_delete($abspath);
      break;

    case 'upload':
      $path = _checked_GET('path');
      $abspath = _abspath($ROOT_FOLDER, $path);
      if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new _HTTPException('Bad Request', 400, 'POST expected');
      }
      $result = _upload($abspath);
      break;

    default:
      throw new _HTTPException('Bad Request', 400, 'Unknown action');
    }
  }
  catch (_HTTPException $e) {
    $e->setErrorHeader();
    $result = ['error'=> ['.tag'=> 'other'], 'error_summary'=> $e->getMessage() . ' ' . $e->content];
  }
  catch (Exception $e) {
    header("HTTP/1.1 500 Internal error");
    $result = ['error'=> ['.tag'=> 'other'], 'error_summary'=> $e->getMessage()];
  }

  header('Content-type:application/json;charset=utf-8');
  echo json_encode($result);

}

//============================================================================

function _upload($abspath) {

  $temppath = tempnam(dirname($abspath), 'temp');
  if (!$temppath) {
    throw new _HTTPException("`tempnam` failed", 500);
  }

  try {

    $dst = fopen($temppath, "wb");
    $src = fopen("php://input", "r"); // POST raw data

    try {

      if (!$src || !$dst) {
        throw new _HTTPException("Could not create file", 500);
      }
      // copy streams
      while ($data = fread($src, 1024))
      {
        if ($data === FALSE) {
          throw new _HTTPException("Could not read source data", 500);     // FIXME(nico) endpoint error ?
        }
        $written = fwrite($dst, $data, 1024);
        if ($written != strlen($data)) {
          throw new _HTTPException("Could not write to file", 500);
        }
      }

    }
    catch (Exception $e) {
      fclose($src);
      fclose($dst);
      throw $e;
    }

    fclose($src);
    fclose($dst);

    // finalize destination file
    if (!rename($temppath, $abspath)) {
      throw new _HTTPException("Could not finalize file", 500);
    }

  }
  catch (Exception $e) {

    if (file_exists($temppath)) {
      unlink($temppath);
    }
    throw $e;
  }

  $name = basename($abspath);
  $result = _metadata($name, $abspath);
  return $result;
}

//----------------------------------------------------------------------------

function _permanently_delete($abspath) {

  if (unlink($abspath)) {         // FIXME(nico) can trigger a warning, check file_exists first, and improve error reporting

    return null;
  }
  else {

    return ['error'=> ['.tag'=> 'other'], 'error_summary'=> "Could not unlink file"];
  }
}

//----------------------------------------------------------------------------

function _list_folder($abspath) {

  $names = array_diff(scandir($abspath), array('..', '.'));
  $result = [];
  foreach ($names as $name) {
    $path = _path_join($abspath, $name);

    $size = filesize($path);
    $server_modified = date(DATE_ISO8601, filemtime($path));
    $tag = null;
    if (is_dir($path)) { $tag='folder'; }
    elseif (is_file($path)) { $tag='file'; }

    $metadata = _metadata($name, $path);
    if ($metadata['.tag'] != null) {
      // NOTE(nico) do not include info on 'undefined' filesystem items
      $result[] = $metadata;
    }
  }
  return [ 'entries'=> $result, 'has_more'=> false ];
}

//============================================================================

function _metadata($name, $path) {

  $size = filesize($path);
  $server_modified = date(DATE_ISO8601, filemtime($path));
  $tag = null;
  if (is_dir($path)) { $tag='folder'; }
  elseif (is_file($path)) { $tag='file'; }

  return [ ".tag"=>$tag, 'name'=>$name, 'server_modified'=>$server_modified, 'size'=>$size ];
}

function _abspath($ROOT_FOLDER, $path) {

  return $ROOT_FOLDER . $path;       // FIXME(nico) security check, path should be absolute starting with '/'
}

function _path_join($root, $path) {

  return $root . $path;            // FIXME(nico) check '/' & stuff
}

function _checked_GET($varname) {
  if (!isset($_GET[$varname])) {
    throw new _HTTPException('Bad Request', 400, 'Missing parameter `' . $varname . '`');
  }
  return $_GET[$varname];
}

class _HTTPException extends \Exception {
  public $content = null;
  public function __construct($message = null, $code = 501, $content = null) {
    parent::__construct($message, $code);
    $this->content = $content;
  }
  public function setErrorHeader() {
    header("HTTP/1.1 " . $this->code . ' ' . $this->getMessage());
  }
}

//============================================================================

?>