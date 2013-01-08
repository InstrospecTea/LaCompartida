<?php
require_once dirname(__FILE__) . '/../conf.php';
require_once Conf::ServerDir() . '/../fw/classes/Utiles.php';
require_once Conf::ServerDir() . '/../fw/classes/Sesion.php';
require_once Conf::ServerDir() . '/classes/UtilesApp.php';
require_once Conf::ServerDir() . '/classes/Log.php';

class Cron {
	var $Session;

	public function __construct() {
		$this->Sesion = new Sesion(null, true);
	}

	public function query($query) {
		$result = mysql_query($query, $this->Sesion->dbh)
				or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
		$table = array();
		while ($row = mysql_fetch_assoc($result)) {
			$table[] = $row;
		}
		return $table;
	}

	public function log($text, $fileName = null) {
		Log::write($text, $fileName);
	}
}