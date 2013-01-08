<?php
require_once dirname(dirname(__FILE__)) . '/conf.php';

/**
 * Escribe en archivo de log segun parametros
 *
 * @author CPS 2.0
 */
class Log {
	var $logFile = 'app';
	var $logFolder = '/tmp/logs';

	public function __construct() {
	}

	public function write($text = '', $file_name = null) {
		$me = new self;
		if (!empty($file_name)) {
			$me->logFile = $file_name;
		}
		$file = $me->logFolder . '/' . $me->logFile . '.log';
		if (!file_exists($file)) {
			$me->writeFile('', $file);
		}
		if (!is_writable($file)) {
			echo $file . __(' no se puede escribir.');
		}
		$text = date('Y-m-d H:i:s') . " - {$name}: {$text}\n";
		$me->writeFile($text, $file);
	}

	private function writeFile($text, $file) {
		try {
			$fp = fopen($file, 'a');
			fwrite($fp, $text);
			fclose($fp);
		} catch(Exception $e) {
		}
	}
}
