<?php


	   
$varsdeinicio=ini_get_all();
$errorpath=$varsdeinicio['error_log']['local_value'];
echo 'Leyendo '.$errorpath.'<br/><br/><br/>';
 echo '<pre style="text-align:left;font-size:9px;">';
echo tail($errorpath);
echo '</pre>';
	
	function tail($file, $num_to_get=100) { 
  $fp = fopen($file, 'r'); 
  $position = filesize($file); 
  fseek($fp, $position-1); 
  $chunklen = 4096; 
  while($position >= 0 ) 
  { 
    $position = $position - $chunklen; 
    if ($position < 0) { $chunklen = abs($position); $position=0;} 
    fseek($fp, $position); 
    $data = fread($fp, $chunklen). $data; 
    if (substr_count($data, "\n") >= $num_to_get + 1) 
    { 
       preg_match("!(.*?\n){".($num_to_get-1)."}$!", $data, $match); 
       return $match[0]; 
    } 
	if(feof($fp)) break;
  } 
  fclose($fp); 
  return $data; 
} 
