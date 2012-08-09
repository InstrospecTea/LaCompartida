<?php

$filename= realpath(dirname(__FILE__).'/../app/conf.php');
require_once $filename;
 
function autocargaapp($class_name) {
	if (file_exists(Conf::ServerDir() . '/classes/' . $class_name . '.php')) {
		require Conf::ServerDir() . '/classes/' . $class_name . '.php';
	} else if (file_exists(Conf::ServerDir() . '/../fw/classes/' . $class_name . '.php')) {
		require Conf::ServerDir() . '/../fw/classes/' . $class_name . '.php';
	} else {
		   $file =Conf::ServerDir() . '/../fw/classes/' . str_replace('_', DIRECTORY_SEPARATOR, substr($class,5)) . '.php';
			if ( file_exists($file) ) {
				require $file;
			}
	}
}

spl_autoload_register('autocargaapp');	
	
	
 	$sesion = new Sesion(array('ADM'));
		 $pagina = new Pagina($sesion);
		 $pagina->titulo = __('Log de Errores: Últimas 100 filas');
	$pagina->PrintTop();
	   if($sesion->usuario->fields['rut']!='99511620') {
		die('No Autorizado');
	   }  
	   $archivologs=ini_get('error_log');
//$
	   
$varsdeinicio=ini_get_all();
$errorpath=$varsdeinicio['error_log']['local_value'];
echo 'Leyendo '.$errorpath.'<br/><br/><br/>';
 echo '<pre style="text-align:left;font-size:9px;">';
echo tail($errorpath);
echo '</pre>';
	$pagina->PrintBottom();	   

	function tail($file, $num_to_get=100) 
{ 
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
/**
 * Apache Log Parser
 * Parses an Apache log file and runs the strings through filters to find what you're looking for.
 * @author Eric Lamb
 *
 */
class apache_log_parser
{
	/**
	 * The path to the log file
	 * @var string
	 */
	private $file = FALSE;
 
	/**
	 * What filters to apply. Should be in the format of array('KEY_TO_SEARCH' => array('regex' => 'YOUR_REGEX'))
	 * @var array
	 */
	public $filters = FALSE;
 
	/**
	 * Duh.
	 * @param string $file
	 * @return void
	 */
	public function __construct($file)
	{
		if(!is_readable($file))
		{
			return 	FALSE;
		}
 
		$this->file = $file;
	}
 
	/**
	 * Executes the supplied filter to the string
	 * @param $filer
	 * @param $status
	 * @return string
	 */
	private function applyFilters($str)
	{
		if(!$this->filters || !is_array($this->filters))
		{
			return $str;
		}
 
		foreach($this->filters AS $area => $filter)
		{
			if(preg_match($filter['regex'], $str[$area], $matches, PREG_OFFSET_CAPTURE))
			{
				return $str;
			}
		}
	}
 
	/**
	 * Returns an array of all the filtered lines 
	 * @param $limit
	 * @return array
	 */
	public function getData($limit = FALSE)
	{
		$handle = fopen($this->file, 'rb');
		if ($handle) {
			$count = 1;
			$lines = array();
		    while (!feof($handle)) {
		        $buffer = fgets($handle);
		        $data = $this->applyFilters($this->format_line($buffer));
		        if($data)
		        {
		        	$lines[] = $data;
		        }
 
		        if($limit && $count == $limit)
		        {
		        	break;
		        }
		        $count++;
		    }
		    fclose($handle);
		    return $lines;
		}		
	}
 
	/**
	 * Regex to parse the log file line
	 * @param string $line
	 * @return array
	 */
	function format_log_line($line)
	{
		preg_match("/^(\S+) (\S+) (\S+) \[([^:]+):(\d+:\d+:\d+) ([^\]]+)\] \"(\S+) (.*?) (\S+)\" (\S+) (\S+) (\".*?\") (\".*?\")$/", $line, $matches); // pattern to format the line
		return $matches;
	}
 
	/**
	 * Takes the format_log_line array and makes it usable to us stupid humans
	 * @param $line
	 * @return array
	 */
	function format_line($line)
	{
		$logs = $this->format_log_line($line); // format the line
 
		if (isset($logs[0])) // check that it formated OK
		{
			$formated_log = array(); // make an array to store the lin info in
			$formated_log['ip'] = $logs[1];
			$formated_log['identity'] = $logs[2];
			$formated_log['user'] = $logs[2];
			$formated_log['date'] = $logs[4];
			$formated_log['time'] = $logs[5];
			$formated_log['timezone'] = $logs[6];
			$formated_log['method'] = $logs[7];
			$formated_log['path'] = $logs[8];
			$formated_log['protocal'] = $logs[9];
			$formated_log['status'] = $logs[10];
			$formated_log['bytes'] = $logs[11];
			$formated_log['referer'] = $logs[12];
			$formated_log['agent'] = $logs[13];
			return $formated_log; // return the array of info
		}
		else
		{
			$this->badRows++; // if the row is not in the right format add it to the bad rows
			return false;
		}
	}
}
