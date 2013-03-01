<?php
require_once dirname(__FILE__) . '/../conf.php';
require_once Conf::ServerDir() . '/../fw/classes/Utiles.php';
require_once Conf::ServerDir() . '/../fw/classes/Sesion.php';
require_once Conf::ServerDir() . '/classes/UtilesApp.php';
require_once Conf::ServerDir() . '/classes/Log.php';

class Cron {
	var $Sesion;
	var $FileNameLog;

	public function __construct() {
		$this->Sesion = new Sesion(null, true);

		// iniciar sesión como Lemontech
		$this->Sesion->usuario = new Usuario($this->Sesion, '99511620');
	}

	public function query($query) {
		$result = mysql_query($query, $this->Sesion->dbh)
				or Utiles::errorSQL($query, __FILE__, __LINE__, $this->Sesion->dbh);
		$table = array();
		if (!mysql_info($this->Sesion->dbh)) {
			while ($row = mysql_fetch_assoc($result)) {
				$table[] = $row;
			}
		}
		return $table;
	}

	public function log($text, $file_name = null) {
		if (!empty($file_name)) {
			$this->FileNameLog = $file_name;
		}

		Log::write($text, $this->FileNameLog);
	}
}
