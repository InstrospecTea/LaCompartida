<?php
require_once dirname(dirname(__FILE__)) . '/conf.php';

/**
 * Escribe en archivo de log segun parametros
 *
 * @author CPS 2.0
 */
class Log {
	public $logFile = 'app';
	public $logFolder = null;
	public $debug = false;
	private static $instance;

	public function __construct() {
		$this->logFolder = self::getFolder();
	}

	public static function getFolder() {
		$folder = LOGDIR . Conf::dbUser() . '/ttb/' . date('y-m');
		if (!is_dir($folder)) {
			if (!mkdir($folder, 0777, true)) {
				exit("No es posible crear el directorio '{$folder}'");
			}
		}
		return $folder;
	}

	public static function write($text = '', $file_name = null) {
		$me = self::getInstance();
		if (!empty($file_name)) {
			$me->logFile = $file_name;
		}
		$file = $me->logFolder . '/' . $me->logFile . '.log';
		if (!file_exists($file)) {
			shell_exec("touch $file && chmod a+w $file");
		}
		if (!is_writable($file) && $me->debug) {
			echo $file . __(' no se puede escribir.');
		}
		$text = date('Y-m-d H:i:s') . " - {$text}\n";
		$me->writeFile($text, $file);
	}

	private function writeFile($text, $file) {
		file_put_contents($file, $text, FILE_APPEND);
	}

	public function getInstance() {
		if (empty(self::$instance)) {
			self::$instance = new self;
		}
		return self::$instance;
	}
}
