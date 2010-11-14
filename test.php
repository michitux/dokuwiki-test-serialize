<?php
/**
 * Testing tool for comparing JSON vs. var_export vs. DokuWiki JSON vs. serialize.
 * Place testing data in pages/, you can e.g. download a dump from 
 * http://dev.splitbrain.org/download/stuff/wiki.tgz
 * and adjust the settings below, especially DOKU_INC should be adjusted.
 * You can either benchmark metadata or instructions.
 * License: GPL2 (http://www.gnu.org/licenses/gpl.html)
 */

define('DOKU_INC', realpath(dirname(__FILE__).'/../../dokuwiki-git/').'/');
require_once DOKU_INC.'inc/init.php';

$result = array();

// Shall metadata be tested (or instructions otherwise)
$meta = false;
// How many times each test is executed
$times = 10;
// variables for accumulated times
$serialize_time = 0;
$json_encode_time = 0;
$doku_json_encode_time = 0;
$export_time = 0;
$unserialize_time = 0;
$json_decode_time = 0;
$doku_json_decode_time = 0;
$import_time = 0;
$count = 0;
$json = new JSON();
require_once DOKU_INC."inc/parser/metadata.php";

function scanpages($dir) {
  global $times, $serialize_time, $json_encode_time, $export_time, $unserialize_time, $json_decode_time, $import_time, $count, $doku_json_encode_time, $doku_json_decode_time, $json, $meta;
  $dh = opendir($dir);
  if (!$dh) {
    echo 'error';
    return;
  }

  while (($file = readdir($dh)) !== false) {
    if ($file == '.' || $file == '..' ) continue;
    if (is_dir($dir.'/'.$file)) {
      scanpages($dir.'/'.$file);
      continue;
    }
    if (substr($file,-4) == '.txt') {
      $name = substr($file,0,-4);
      $instructions = p_get_instructions(file_get_contents($dir.'/'.$file));

      if ($meta) {
        $renderer = new Doku_Renderer_metadata();
        $renderer->meta = array();
        $renderer->persistent = array();

        // loop through the instructions
        foreach ($instructions as $instruction){
          // execute the callback against the renderer
          call_user_func_array(array(&$renderer, $instruction[0]), (array) $instruction[1]);
        }

        $instructions = array('current'=>$renderer->meta,'persistent'=>$renderer->persistent);

        ++$count;
      }

      $start = microtime(true);
      for ($i = 0; $i < $times; $i++)
        $serialized_instructions = serialize($instructions);
      $after_serialize = microtime(true);
      $serialize_time += $after_serialize - $start;
      echo 'Serialize: ',$after_serialize - $start, "\n";

      for ($i = 0; $i < $times; $i++)
        $json_instructions = json_encode($instructions);
      $after_json = microtime(true);
      $json_encode_time += $after_json - $after_serialize;
      echo 'JSON encode: ',$after_json - $after_serialize, "\n";

      for ($i = 0; $i < $times; $i++)
        $doku_json_instructions = $json->encode($instructions);
      $after_doku_json = microtime(true);
      $doku_json_encode_time += $after_doku_json - $after_json;
      echo 'DokuWiki JSON encode: ', $after_doku_json - $after_json, "\n";

      for ($i = 0; $i < $times; $i++)
        $exported_instructions = var_export($instructions, true);
      $after_export = microtime(true);
      $export_time += $after_export - $after_doku_json;
      echo 'Export: ',$after_export - $after_doku_json, "\n";

      for ($i = 0; $i < $times; $i++)
        $unserialized_instructions = unserialize($serialized_instructions);
      $after_unserialize = microtime(true);
      $unserialize_time += $after_unserialize - $after_export;
      echo 'Unserialize: ',$after_unserialize - $after_export, "\n";

      for ($i = 0; $i < $times; $i++)
        $decodedjson_instructions = json_decode($json_instructions);
      $after_jsondecode = microtime(true);
      $json_decode_time += $after_jsondecode - $after_unserialize;
      echo 'JSON decode: ',$after_jsondecode - $after_unserialize, "\n";

      for ($i = 0; $i < $times; $i++)
        $dokudecodedjson_instructions = $json->decode($doku_json_instructions);
      $after_doku_json_decode = microtime(true);
      $doku_json_decode_time += $after_doku_json_decode - $after_jsondecode;
      echo 'DokuWiki JSON decode: ', $after_doku_json_decode - $after_jsondecode, "\n";

      for ($i = 0; $i < $times; $i++)
        $imported_instructions = eval($exported_instructions.';');
      $after_import = microtime(true);
      $import_time += $after_import - $after_doku_json_decode;
      echo 'Import: ',$after_import - $after_doku_json_decode, "\n";

      if ($count % 100 == 0) {
        echo 'Serialize time: ', $serialize_time, "\n";
        echo 'Unserialize time: ', $unserialize_time, "\n";
        echo 'JSON encode time: ', $json_encode_time, "\n";
        echo 'JSON decode time: ', $json_decode_time, "\n";
        echo 'DokuWiki JSON encode time: ', $doku_json_encode_time, "\n";
        echo 'DokuWiki JSON decode time: ', $doku_json_decode_time, "\n";
        echo 'Export time: ', $export_time, "\n";
        echo 'Import time: ', $import_time, "\n";
      }
    }
  }
  closedir($dh);
}

scanpages(dirname(__FILE__).'/pages');
echo 'Serialize time: ', $serialize_time, "\n";
echo 'Unserialize time: ', $unserialize_time, "\n";
echo 'JSON encode time: ', $json_encode_time, "\n";
echo 'JSON decode time: ', $json_decode_time, "\n";
echo 'DokuWiki JSON encode time: ', $doku_json_encode_time, "\n";
echo 'DokuWiki JSON decode time: ', $doku_json_decode_time, "\n";
echo 'Export time: ', $export_time, "\n";
echo 'Import time: ', $import_time, "\n";
